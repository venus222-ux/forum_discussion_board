import type { Comment } from "./comment";

export interface Flag {
  id: number;
  comment_id: string;
  reason: string;
  status: string;

  user: {
    id: number;
    name: string;
  };

  comment: Comment;

  created_at: string;

  ai_hate_label?: string;
  ai_hate_score?: number;
  ai_hate_reason?: string;

  total_flags?: number;
}