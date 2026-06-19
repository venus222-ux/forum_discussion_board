import axios, {
  AxiosInstance,
  AxiosError,
  InternalAxiosRequestConfig,
} from "axios";

import type {
  LoginRequest,
  LoginResponse,
  RegisterRequest,
  ProfileData,
  ProfileUpdateRequest,
  APIMessageResponse,
} from "@/types/auth";


/* =========================
   Axios instance
========================= */

const API: AxiosInstance = axios.create({
  baseURL: "http://localhost:8000/api",
});

/* =========================
   Refresh queue system
========================= */

type FailedQueueItem = {
  resolve: (token: string) => void;
  reject: (err: unknown) => void;
};

type RetryableRequestConfig = InternalAxiosRequestConfig & {
  _retry?: boolean;
};

let isRefreshing = false;
let failedQueue: FailedQueueItem[] = [];

const processQueue = (data: { error?: unknown; token?: string }) => {
  failedQueue.forEach((prom) => {
    if (data.error) {
      prom.reject(data.error);
    } else if (data.token) {
      prom.resolve(data.token);
    }
  });

  failedQueue = [];
};

/* =========================
   Request interceptor
========================= */

API.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  config.headers = config.headers || {};

  const token = localStorage.getItem("token");

  if (token) {
    config.headers["Authorization"] = `Bearer ${token}`;
  }

  return config;
});

/* =========================
   Response interceptor (refresh token)
========================= */

API.interceptors.response.use(
  (res) => res,
  async (err: AxiosError) => {
    const originalRequest = err.config as RetryableRequestConfig | undefined;

    if (!originalRequest) return Promise.reject(err);

    if (err.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise<string>((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers = originalRequest.headers || {};
            originalRequest.headers["Authorization"] = `Bearer ${token}`;
            return API(originalRequest);
          })
          .catch((error) => Promise.reject(error));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const response = await API.post<LoginResponse>("/refresh");
        const newToken = response.data.token;

        localStorage.setItem("token", newToken);
        API.defaults.headers.common["Authorization"] = `Bearer ${newToken}`;

        processQueue({ token: newToken });

        originalRequest.headers = originalRequest.headers || {};
        originalRequest.headers["Authorization"] = `Bearer ${newToken}`;

        return API(originalRequest);
      } catch (refreshError) {
        processQueue({ error: refreshError });

        localStorage.removeItem("token");
        window.location.replace("/login");

        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(err);
  }
);

/* =========================
   API functions
========================= */

export const login = (data: LoginRequest) =>
  API.post<LoginResponse>("/login", data);

export const register = (data: RegisterRequest) =>
  API.post<APIMessageResponse>("/register", data);

export const getProfile = () =>
  API.get<ProfileData>("/profile");

export const updateProfile = (data: ProfileUpdateRequest) =>
  API.put<APIMessageResponse>("/profile", data);

export const deleteProfile = () =>
  API.delete<APIMessageResponse>("/profile");

export const refreshToken = () =>
  API.post<LoginResponse>("/refresh");

export default API;