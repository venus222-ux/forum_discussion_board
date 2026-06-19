import type { Thread } from "./thread";
import type { NotificationItem } from "./notification";

export interface ApiResponse<T> {
  data: T;
  message?: string;
  success?: boolean;
}

export interface ThreadsResponse {
  data: Thread[];
  next_cursor?: string | null;
}

export interface NotificationResponse {
  data: NotificationItem[];
  next_cursor: string | null;
  unread_count: number;
}