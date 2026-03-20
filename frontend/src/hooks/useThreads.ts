import { useInfiniteQuery } from "@tanstack/react-query";
import API from "../api";
import { Thread } from "../store/useThreadStore";

interface ThreadsResponse {
  data: Thread[];
  next_cursor?: string | null;
}

// -----------------------------
// Fetch threads function
// -----------------------------
const fetchCategoryThreads = async (
  categorySlug: string,
  cursor?: string,
): Promise<ThreadsResponse> => {
  const url = cursor
    ? `/categories/${categorySlug}/threads?cursor=${cursor}`
    : `/categories/${categorySlug}/threads`;
  const res = await API.get<ThreadsResponse>(url);
  return res.data;
};

// -----------------------------
// Infinite query hook
// -----------------------------
export const useCategoryThreads = (categorySlug: string) => {
  return useInfiniteQuery({
    queryKey: ["categoryThreads", categorySlug] as const,
    queryFn: ({ pageParam }: { pageParam?: string }) =>
      fetchCategoryThreads(categorySlug, pageParam),
    staleTime: 1000 * 60,
    getNextPageParam: (lastPage) => lastPage.next_cursor ?? undefined,
    initialPageParam: undefined,
  });
};
