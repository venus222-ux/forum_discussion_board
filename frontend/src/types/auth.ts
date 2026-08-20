// src/types/auth.ts
import type { User } from "./user";

export interface LoginRequest {
  email: string;
  password: string;
}

export type FailedQueueItem = {
  resolve: (token: string) => void;
  reject: (err: unknown) => void;
};


export interface LoginResponse {
  token: string;
  token_type?: string;
  expires_in?: number;
  user: {
    id: number;
    name: string;
    email: string;
    role: "candidate" | "employer" | "admin";
  };
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
}

export interface ProfileData {
  id?: number;
  email: string;
  name?: string;
  role?: string;
  created_at?: string;
}

export interface ProfileUpdateRequest {
  email: string;
  password?: string;
  password_confirmation?: string;
}

export interface APIMessageResponse {
  message: string;
}

export interface RegisterFormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}