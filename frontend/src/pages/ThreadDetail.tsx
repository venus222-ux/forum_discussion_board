import {
  useState,
  useEffect,
  useCallback,
  lazy,
  Suspense, 
} from "react";
import { useParams, Link, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useStore } from "../store/useStore";
import { useThreadStore } from "../store/useThreadStore";
import type { Reply } from "@/types";
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
   {/* THREAD HEADER */}
<div className={styles.threadCard}>
  {/* Header Section */}
  <div className={styles.threadHeader}>
    {/* Category Badge */}
    {thread.category && (
      <div className={styles.categoryBadge}>
        {thread.category.name}
      </div>
    )}

    {/* Title */}
    <h1 className={styles.threadTitle}>
      {thread.title}
    </h1>

    {/* Author & Meta */}
    <div className={styles.threadMeta}>
      <div className={styles.author}>
        <div className={styles.avatar}>
          {thread.user?.name?.[0]?.toUpperCase() || "U"}
        </div>
        <div className={styles.authorInfo}>
          <div className={styles.authorName}>
            {thread.user?.name}
          </div>
          {thread.user?.badge && (
            <span className={styles.authorBadge}>{thread.user.badge}</span>
          )}
        </div>
      </div>

      <div className={styles.metaStats}>
        <div className={styles.metaItem}>
          📅 {timeAgo(thread.created_at)}
        </div>
        <div className={styles.metaItem}>
          👁️ {thread.views?.toLocaleString() || 0} views
        </div>
        <div className={styles.metaItem}>
          💬 {enrichedReplies.length} replies
        </div>
      </div>
    </div>
  </div>

  {/* Content Body */}
  <div className={styles.threadContent}>
    <div
      className={styles.contentBody}
      dangerouslySetInnerHTML={{
        __html: thread.content ?? "",
      }}
    />
  </div>

  {/* Optional: Thread Actions */}
  <div className={styles.threadActions}>
    {/* You can add like, share, bookmark buttons here later */}
  </div>
</div>

  {/* TABS */}
<div className={styles.tabs}>
  <button
    className={`${styles.tab} ${activeTab === "discussion" ? styles.activeTab : ""}`}
    onClick={() => setActiveTab("discussion")}
  >
    💬 Discussion
    {enrichedReplies.length > 0 && (
      <span className={styles.tabCount}>
        {enrichedReplies.length}
      </span>
    )}
  </button>

  <button
    className={`${styles.tab} ${activeTab === "details" ? styles.activeTab : ""}`}
    onClick={() => setActiveTab("details")}
  >
    📋 Details
  </button>
</div>

      {/* DISCUSSION */}
      {activeTab === "discussion" && (
        <div>
          {/* FORM */}
       {/* Reply Form */}
{isAuth ? (
  <div className={styles.replyForm}>
    <form onSubmit={handleReplySubmit}>
      <div className={styles.replyFormHeader}>
        <div className={styles.replyAvatar}>You</div>
        <div className={styles.replyName}>Write a reply...</div>
      </div>

      <textarea
        className={styles.replyInput}
        value={replyContent}
        onChange={(e) => setReplyContent(e.target.value)}
        placeholder="Share your thoughts, questions, or feedback..."
        rows={5}
      />

      <div className={styles.replyActions}>
        <button
          type="button"
          className={styles.cancelButton}
          onClick={() => setReplyContent("")}
        >
          Cancel
        </button>
        <button
          type="submit"
          className={styles.submitButton}
          disabled={submitting || !replyContent.trim()}
        >
          {submitting ? "Posting..." : "Post Reply"}
        </button>
      </div>
    </form>
  </div>
) : (
  <div className={styles.loginPrompt}>
    <p>You need to be logged in to join the discussion.</p>
    <Link to="/login" className={styles.loginButton}>
      Sign in to reply
    </Link>
  </div>
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