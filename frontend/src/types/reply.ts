// src/types/reply.ts
export interface Reply {
  _id: string;
  content: string;

  user: {
    id: number;
    name: string;
  };

  createdAt: string;

  upvotes?: number;
  downvotes?: number;

  isBest?: boolean;
  parentId?: string;

  children?: Reply[];

  is_hidden?: boolean;
  official_reply?: boolean;
  moderation_reason?: string;
  total_flags?: number;
}