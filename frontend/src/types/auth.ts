// src/types/auth.ts
import type { User } from "./user";

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: User;
  token_type?: string;
  expires_in?: number;
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