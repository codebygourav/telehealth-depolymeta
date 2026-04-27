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

const USER_KEY = "@user";
const TOKEN_KEY = "@token";

export function UserProvider({ children }: { children: React.ReactNode }) {

    const [user, setUser] = useState<User | null>(null);
    const [token, setToken] = useState<string | null>(null);
    const [initializing, setInitializing] = useState(true);

    useEffect(() => {
        (async () => {
            try {

                const storedUser = localStorage.getItem(USER_KEY)
                const storedToken = localStorage.getItem(TOKEN_KEY)

                if (storedUser) setUser(JSON.parse(storedUser));

                if (storedToken) {
                    setToken(storedToken);
                    setAuthToken(storedToken);
                }
            } catch (e) {
                console.log("Error loading auth data", e);
            } finally {
                setInitializing(false);
            }
        })();
    }, []);

    const login = async (userData: User, authToken: string) => {
        console.log("UserContext: login called with", { userData, authToken });
        setUser(userData);
        setToken(authToken);
        setAuthToken(authToken);
        try {
            localStorage.setItem(USER_KEY, JSON.stringify(userData));
            localStorage.setItem(TOKEN_KEY, authToken);
            document.cookie = `token=${authToken}; path=/; max-age=604800; samesite=lax`;
            document.cookie = `role=${userData.role}; path=/; max-age=604800; samesite=lax`;
            console.log("UserContext: values saved to localStorage and cookies");
        } catch (e) {
            console.log("Error saving auth data", e);
        }
    };

    const logout = async () => {
        setUser(null);
        setToken(null);
        setAuthToken(null);
        try {
            localStorage.removeItem(USER_KEY);
            localStorage.removeItem(TOKEN_KEY);
            document.cookie = "token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
            document.cookie = "role=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
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