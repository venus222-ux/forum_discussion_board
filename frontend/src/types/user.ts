export type Role = "user" | "moderator" | "admin" | null;

export interface User {
  id: number | string;
  name: string;
  email?: string;
  role?: Role | string;
  reputation?: number;
}