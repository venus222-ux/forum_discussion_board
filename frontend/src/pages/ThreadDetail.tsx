import {
  useState,
  useEffect,
  useRef,
  useCallback,
  lazy,
  Suspense,
} from "react";
import { useParams, Link, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useStore } from "../store/useStore";
import { useThreadStore, Reply } from "../store/useThreadStore";
import styles from "../styles/ThreadDetail.module.css";
import API from "../api";

const CommentTree = lazy(() => import("../components/CommentTree"));

import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
dayjs.extend(relativeTime);

// Assume Laravel Echo is set up globally as window.Echo

export default function ThreadDetail() {
  const { slug } = useParams<{ slug: string }>();
  const { isAuth } = useStore();
  const navigate = useNavigate();

  // THREAD (Zustand - OK)
  const currentThread = useThreadStore((s) => s.currentThread);
  const isFetchingOne = useThreadStore((s) => s.isFetchingOne);
  const fetchThreadBySlug = useThreadStore((s) => s.fetchThreadBySlug);

  const setCurrentThread = useThreadStore((s) => s.setCurrentThread);

  const [replyContent, setReplyContent] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const [activeTab, setActiveTab] = useState<"discussion" | "details">(
    () =>
      (localStorage.getItem("activeTab") as any) || "discussion"
  );

  // ---------------- THREAD FETCH ----------------
  useEffect(() => {
    if (!slug) return;
    setCurrentThread(null);
    fetchThreadBySlug(slug);
  }, [slug]);

  // ---------------- COMMENTS (React Query = SINGLE SOURCE OF TRUTH) ----------------
  const {
    data: replies = [],
    isLoading: loadingReplies,
    refetch,
  } = useQuery<Reply[]>({
    queryKey: ["threadReplies", slug],

    queryFn: async () => {
      const res = await API.get(`/threads/${slug}/comments`);
      return res.data;
    },

    enabled: !!slug,
  });

  // ---------------- ENRICH ----------------
  const enrichReplies = useCallback(
    (list: Reply[], bestId?: string): Reply[] => {
      return list.map((r) => ({
        ...r,
        isBest: r._id === bestId,
        children: r.children
          ? enrichReplies(r.children, bestId)
          : undefined,
      }));
    },
    []
  );

  const enrichedReplies = enrichReplies(
    replies,
    currentThread?.best_comment_id
  );

  // ---------------- REALTIME ----------------
  useEffect(() => {
    if (!slug) return;

    const echo = (window as any).Echo;
    if (!echo) return;

    const channel = echo.channel(`thread.comments.${slug}`);

    const refresh = () => refetch();

    channel.listen("CommentCreated", refresh);
    channel.listen("CommentFlagged", refresh);
    channel.listen("CommentModerated", refresh);

    return () => {
      channel.stopListening("CommentCreated", refresh);
      channel.stopListening("CommentFlagged", refresh);
      channel.stopListening("CommentModerated", refresh);
      echo.leave(`thread.comments.${slug}`);
    };
  }, [slug, refetch]);

  // ---------------- TOOLTIP ----------------
  useEffect(() => {
    const bootstrap = (window as any).bootstrap;
    if (!bootstrap) return;

    document
      .querySelectorAll('[data-bs-toggle="tooltip"]')
      .forEach((el) => new bootstrap.Tooltip(el));
  }, [replies]);

  // ---------------- POST REPLY ----------------
  const handleReplySubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isAuth) return navigate("/login");
    if (!replyContent.trim() || !slug) return;

    setSubmitting(true);

    try {
      await API.post(`/threads/${slug}/comments`, {
        content: replyContent,
      });

      setReplyContent("");
      refetch();
    } catch (err) {
      console.error(err);
    } finally {
      setSubmitting(false);
    }
  };

  const getUserInitials = (name?: string) =>
    name
      ? name
          .split(" ")
          .map((n) => n[0])
          .join("")
          .toUpperCase()
          .substring(0, 2)
      : "?";

  const formatDate = (date?: string) =>
    date
      ? new Date(date).toLocaleString()
      : "Unknown";

  const timeAgo = (date?: string) =>
    date ? dayjs(date).fromNow() : "unknown";

  // ---------------- LOADING ----------------
  if (isFetchingOne || !currentThread) {
    return (
      <div className={styles.loadingContainer}>
        <p>Loading thread...</p>
      </div>
    );
  }

  const thread = currentThread;

  return (
    <div className={styles.container}>
      {/* HEADER */}
      <div className={styles.threadCard}>
        <h1>{thread.title}</h1>

        <div
          dangerouslySetInnerHTML={{ __html: thread.content }}
        />

        <div>
          📅 {timeAgo(thread.created_at)} • 👁️ {thread.views || 0} views • 💬 {enrichedReplies.length} replies
        </div>
      </div>

      {/* TABS */}
      <div className={styles.tabs}>
        <button
          className={activeTab === "discussion" ? styles.activeTab : ""}
          onClick={() => setActiveTab("discussion")}
        >
          Discussion
        </button>

        <button
          className={activeTab === "details" ? styles.activeTab : ""}
          onClick={() => setActiveTab("details")}
        >
          Details
        </button>
      </div>

      {/* DISCUSSION */}
      {activeTab === "discussion" && (
        <div>
          {/* FORM */}
          {isAuth ? (
            <form onSubmit={handleReplySubmit}>
              <textarea
                value={replyContent}
                onChange={(e) => setReplyContent(e.target.value)}
                placeholder="Write reply..."
              />

              <button disabled={submitting || !replyContent.trim()}>
                {submitting ? "Posting..." : "Post"}
              </button>
            </form>
          ) : (
            <Link to="/login">Login to reply</Link>
          )}

          {/* COMMENTS */}
          <div>
            {loadingReplies ? (
              <p>Loading comments...</p>
            ) : (
              enrichedReplies.map((c) => (
                <Suspense fallback={<p>Loading...</p>} key={c._id}>
                  <CommentTree
                    comment={c}
                    level={0}
                    threadSlug={slug || ""}
                  />
                </Suspense>
              ))
            )}
          </div>
        </div>
      )}

      {/* DETAILS */}
      {activeTab === "details" && (
        <div>
          <p>Author: {thread.user?.name}</p>
          <p>Category: {thread.category?.name}</p>
          <p>ID: #{thread.id}</p>
        </div>
      )}
    </div>
  );
}