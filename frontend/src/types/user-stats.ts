// src/types/user-stats.ts

export interface ActiveUser {
  id: number;
  name: string;
  postCount: number;
  reputation: number;
}

export interface ActiveUserWithPoints extends ActiveUser {
  points: number;
}