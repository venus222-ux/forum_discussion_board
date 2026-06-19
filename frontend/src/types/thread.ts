import type { User } from "./user";
import type { Category } from "./category";
import type { Reply } from "./reply";

export interface Thread {
  id: number;
  title: string; 
  slug: string;
  content: string;

  category_id: number;

  user?: User;
  category?: Category;

  created_at: string;

  // Stats
  comment_count?: number;
  reply_count?: number;
  like_count?: number;
  upvotes?: number;
  downvotes?: number;
  views?: number;

  // Replies
  replies?: Reply[];

  // Best answer
  best_comment_id?: string;

  // Frontend-only
  optimistic?: boolean;
}