"use client";

import { setAuthToken } from "@/lib/authToken";
import { User } from "@/types/user-context";
import { createContext, useContext, useEffect, useState } from "react";

interface UserContextType {
  user: User | null;
  token: string | null;
  initializing: boolean;
  login: (user: User, token: string) => Promise<void>;
  logout: () => Promise<void>;
  updateUser: (updatedData: Partial<User>) => Promise<void>;
}

const UserContext = createContext<UserContextType | undefined>(undefined);

const USER_KEY = "@doctor_user";
const TOKEN_KEY = "@token";
const TOKEN_COOKIE = "doctor_token";
const ROLE_COOKIE = "doctor_role";
const LOGIN_PATH = "/auth/login";
const EXPECTED_ROLE = "doctor";
const LEGACY_TOKEN_KEYS = [
  TOKEN_KEY,
  "@doctor_token",
  "doctorToken",
  "token",
  "access_token",
];
const LEGACY_USER_KEYS = [USER_KEY];

function getCookieValue(name: string): string | null {
  if (typeof document === "undefined") {
    return null;
  }

  const cookie = document.cookie
    .split("; ")
    .find((value) => value.startsWith(`${name}=`));

  return cookie
    ? decodeURIComponent(cookie.split("=").slice(1).join("="))
    : null;
}

function setCookie(name: string, value: string): void {
  document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=604800; samesite=lax`;
}

function clearCookie(name: string): void {
  document.cookie = `${name}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; max-age=0; samesite=lax`;
}

function clearPersistedAuth(): void {
  if (typeof window === "undefined") {
    return;
  }

  LEGACY_USER_KEYS.forEach((key) => localStorage.removeItem(key));
  LEGACY_TOKEN_KEYS.forEach((key) => localStorage.removeItem(key));

  clearCookie(TOKEN_COOKIE);
  clearCookie(ROLE_COOKIE);
}

export function UserProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [initializing, setInitializing] = useState(true);

  useEffect(() => {
    try {
      const storedUser = localStorage.getItem(USER_KEY);
      const storedToken = localStorage.getItem(TOKEN_KEY);
      const cookieToken = getCookieValue(TOKEN_COOKIE);
      const cookieRole = getCookieValue(ROLE_COOKIE);

      if (
        !storedUser ||
        !storedToken ||
        !cookieToken ||
        storedToken !== cookieToken
      ) {
        clearPersistedAuth();
        return;
      }

      const parsedUser = JSON.parse(storedUser) as User;

      if (
        !parsedUser?.role ||
        parsedUser.role !== EXPECTED_ROLE ||
        cookieRole !== EXPECTED_ROLE
      ) {
        clearPersistedAuth();
        return;
      }

      setUser(parsedUser);
      setToken(storedToken);
      setAuthToken(storedToken);
    } catch (e) {
      clearPersistedAuth();
      console.log("Error loading auth data", e);
    } finally {
      setInitializing(false);
    }
  }, []);

  useEffect(() => {
    const handleUnauthorized = () => {
      setUser(null);
      setToken(null);
      setAuthToken(null);
      clearPersistedAuth();

      if (window.location.pathname !== LOGIN_PATH) {
        window.location.href = LOGIN_PATH;
      }
    };

    window.addEventListener("auth:unauthorized", handleUnauthorized);

    return () => {
      window.removeEventListener("auth:unauthorized", handleUnauthorized);
    };
  }, []);

  const login = async (userData: User, authToken: string) => {
    setUser(userData);
    setToken(authToken);
    setAuthToken(authToken);
    try {
      localStorage.setItem(USER_KEY, JSON.stringify(userData));
      localStorage.setItem(TOKEN_KEY, authToken);
      setCookie(TOKEN_COOKIE, authToken);
      setCookie(ROLE_COOKIE, userData.role);
    } catch (e) {
      console.log("Error saving auth data", e);
    }
  };

  const logout = async () => {
    setUser(null);
    setToken(null);
    setAuthToken(null);
    try {
      clearPersistedAuth();
    } catch (e) {
      console.log("Error clearing auth data", e);
    }
  };

  const updateUser = async (updatedData: Partial<User>) => {
    if (!user) return;
    const updatedUser = { ...user, ...updatedData };
    setUser(updatedUser);
    try {
      localStorage.setItem(USER_KEY, JSON.stringify(updatedUser));
    } catch (e) {
      console.log("Error updating user data", e);
    }
  };

  return (
    <UserContext.Provider
      value={{ user, token, initializing, login, logout, updateUser }}
    >
      {children}
    </UserContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(UserContext);
  if (!context) throw new Error("useAuth must be used within a UserProvider");
  return context;
};
