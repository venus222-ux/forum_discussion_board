import { create } from "zustand";
import { refreshToken } from "../api";

import type { User, Role } from "@/types";

interface AppState {
  isAuth: boolean;
  token: string | null;
  role: Role | null;
  user: User | null;

  theme: "light" | "dark";

  setAuth: (token: string, role: Role | undefined, user: User) => void;
  logout: () => void;
  setUser: (user: User | null) => void;

  setIsAuth: (auth: boolean, token?: string) => void;
  setToken: (token: string | null) => void;

  toggleTheme: () => void;
  startTokenRefreshLoop: () => void;
}

let refreshInterval: ReturnType<typeof setInterval> | null = null;

// ---------------- ROLE GUARD ----------------
const isRole = (value: any): value is Role => {
  return value === "user" || value === "moderator" || value === "admin";
};

export const useStore = create<AppState>((set, get) => {
  const savedToken = localStorage.getItem("token");

  let savedUser: User | null = null;
  try {
    const raw = localStorage.getItem("user");
    savedUser = raw ? JSON.parse(raw) : null;
  } catch {
    savedUser = null;
  }

  const rawSavedRole = localStorage.getItem("role");
  const savedRole: Role | null = isRole(rawSavedRole) ? rawSavedRole : null;

  return {
    isAuth: !!savedToken,
    token: savedToken,
    role: savedRole,
    user: savedUser,

    theme:
      (localStorage.getItem("theme") as "light" | "dark") || "light",

    // ---------------- AUTH ----------------
    setAuth: (token, roleParam, user) => {
      const rawRole = roleParam ?? user?.role ?? null;
      const normalizedRole: Role | null = isRole(rawRole)
        ? rawRole
        : null;

      localStorage.setItem("token", token);

      if (normalizedRole) {
        localStorage.setItem("role", normalizedRole);
      } else {
        localStorage.removeItem("role");
      }

      localStorage.setItem("user", JSON.stringify(user));

      set({
        isAuth: true,
        token,
        role: normalizedRole,
        user,
      });
    },

    // ---------------- LOGOUT ----------------
    logout: () => {
      localStorage.removeItem("token");
      localStorage.removeItem("role");
      localStorage.removeItem("user");

      if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
      }

      set({
        isAuth: false,
        token: null,
        role: null,
        user: null,
      });
    },

    // ---------------- USER UPDATE ----------------
    setUser: (user) => {
      const rawRole = user?.role;
      const role: Role | null = isRole(rawRole) ? rawRole : null;

      if (user) {
        localStorage.setItem("user", JSON.stringify(user));
        if (role) localStorage.setItem("role", role);
        else localStorage.removeItem("role");
      } else {
        localStorage.removeItem("user");
        localStorage.removeItem("role");
      }

      set({
        user,
        role,
      });
    },

    // ---------------- AUTH HELPERS ----------------
    setIsAuth: (auth, token) => {
      if (auth && token) {
        localStorage.setItem("token", token);
        set({ isAuth: true, token });
      } else {
        localStorage.clear();
        set({
          isAuth: false,
          token: null,
          role: null,
          user: null,
        });
      }
    },

    setToken: (token) => {
      if (token) {
        localStorage.setItem("token", token);
        set({ token });
      } else {
        localStorage.clear();
        set({
          token: null,
          role: null,
          user: null,
        });
      }
    },

    // ---------------- THEME ----------------
    toggleTheme: () => {
      set((state) => {
        const newTheme = state.theme === "light" ? "dark" : "light";
        localStorage.setItem("theme", newTheme);
        document.documentElement.setAttribute(
          "data-bs-theme",
          newTheme,
        );
        return { theme: newTheme };
      });
    },

    // ---------------- TOKEN REFRESH ----------------
    startTokenRefreshLoop: () => {
      if (refreshInterval) return;

      refreshInterval = setInterval(async () => {
        const token = localStorage.getItem("token");
        if (!token) return;

        try {
          const res = await refreshToken();

          const newToken = res.data.token;
          const user = res.data.user;

          get().setToken(newToken);
          get().setUser(user);
        } catch {
          get().logout();
          window.location.replace("/login");
        }
      }, 4 * 60 * 1000);
    },
  };
});