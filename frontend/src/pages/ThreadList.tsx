// src/pages/ThreadList.tsx
import { useState } from "react";
import { Link, useParams } from "react-router-dom";
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import styles from "../styles/ThreadList.module.css";
import dayjs from "dayjs";
import relativeTime from "dayjs/plugin/relativeTime";
import API from "../api";

dayjs.extend(relativeTime);

export default function ThreadList() {
  const { categorySlug: slug } = useParams();
  const [page, setPage] = useState(1);

  const timeAgo = (date?: string) =>
    date ? dayjs(date).fromNow() : "unknown";

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ["threads", slug, page],
    placeholderData: keepPreviousData,

    queryFn: async () => {
      const url = slug
        ? `/categories/${slug}/threads?page=${page}`
        : `/threads/recent?page=${page}`;

      const res = await API.get(url);
      return res.data;
    },
  });

  const threads = data?.data || [];
  const lastPage = data?.last_page || 1;

  const handlePrev = () => setPage((p) => Math.max(1, p - 1));
  const handleNext = () => setPage((p) => (p < lastPage ? p + 1 : p));

  return (
    <div className={styles.container}>
      <h1 className={styles.pageTitle}>
        {slug ? `Category: ${slug}` : "Recent Threads"}
      </h1>

      {isLoading || isFetching ? (
        <p>Loading threads...</p>
      ) : threads.length === 0 ? (
        <p>No threads found.</p>
      ) : (
        <div className={styles.threadList}>
          {threads.map((thread: any) => (
            <div key={thread.id} className={styles.threadCard}>
              <div className={styles.threadHeader}>
                <div className={styles.categoryBadge}>
                  {thread.category?.name || "Unknown"}
                </div>

                <h2 className={styles.threadTitle}>
                  <Link to={`/threads/${thread.slug}`}>
                    {thread.title}
                  </Link>
                </h2>

                <div className={styles.threadMeta}>
                  <div className={styles.author}>
                    <div className={styles.avatar}>
                      {thread.user?.name
                        ? thread.user.name
                            .split(" ")
                            .map((n: string) => n[0])
                            .join("")
                            .toUpperCase()
                        : "?"}
                    </div>

                    <span className={styles.authorName}>
                      {thread.user?.name || "Unknown"}
                    </span>
                  </div>

                  <div className={styles.metaStats}>
                    <span>📅 {timeAgo(thread.created_at)}</span>

                    {"views" in thread &&
                      thread.views !== undefined && (
                        <span>👁️ {thread.views} views</span>
                      )}

                    {Array.isArray(thread.replies) && (
                      <span>💬 {thread.replies.length} replies</span>
                    )}
                  </div>
                </div>
              </div>

              <div className={styles.threadContent}>
                <div
                  className={styles.contentBody}
                  dangerouslySetInnerHTML={{
                    __html: thread.content || "",
                  }}
                />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Pagination */}
      <div className={styles.pagination}>
        <button onClick={handlePrev} disabled={page === 1}>
          ← Previous
        </button>

        <span>
          Page {page} of {lastPage}
        </span>

        <button
          onClick={handleNext}
          disabled={page === lastPage}
        >
          Next →
        </button>
      </div>
    </div>
  );
}