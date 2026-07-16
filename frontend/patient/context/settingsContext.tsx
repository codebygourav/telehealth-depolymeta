"use client";

import React, { createContext, useContext, useEffect, useState } from "react";
import api from "@/lib/axios";

interface Settings {
  appName: string;
  logoUrl: string | null;
  faviconUrl: string | null;
  primaryColor: string;
  secondaryColor: string;
}

interface SettingsContextType {
  settings: Settings;
  loading: boolean;
}

const defaultSettings: Settings = {
  appName: "Telehealth",
  logoUrl: null,
  faviconUrl: null,
  primaryColor: "#055bd9",
  secondaryColor: "#055bd9bf",
};

const SettingsContext = createContext<SettingsContextType>({
  settings: defaultSettings,
  loading: true,
});

export function SettingsProvider({ children }: { children: React.ReactNode }) {
  const [settings, setSettings] = useState<Settings>(defaultSettings);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await api.get("/settings");
        if (response.data?.success && response.data?.data) {
          const data = response.data.data;
          const appName = data.app?.name || "Telehealth";
          const primaryColor = data.patient_theme?.primary_color || data.app?.primary_color || "#055bd9";
          const logoUrl = data.patient_theme?.logo || data.app?.logo || null;
          const faviconUrl = data.patient_theme?.favicon || data.app?.favicon || null;

          // Clean the hex code to exactly 7 characters (e.g. #055bd9)
          const cleanHex = primaryColor.startsWith("#") ? primaryColor.substring(0, 7) : primaryColor;

          // Append 'bf' for 75% opacity dynamic secondary color
          const secondaryColor = cleanHex + "bf";

          setSettings({
            appName,
            logoUrl,
            faviconUrl,
            primaryColor,
            secondaryColor,
          });

          // 1. Dynamic CSS variables injection into document root
          document.documentElement.style.setProperty("--primary", cleanHex);
          document.documentElement.style.setProperty("--primary-foreground", "#ffffff");
          document.documentElement.style.setProperty("--ring", cleanHex);
          document.documentElement.style.setProperty("--secondary", secondaryColor);

          // Dynamic light primary tint (10% opacity) for background classes
          document.documentElement.style.setProperty("--primary-light", cleanHex + "1a");
          document.documentElement.style.setProperty("--color-primary-container", cleanHex);

          // 2. Dynamic Favicon updater
          if (faviconUrl) {
            let link: HTMLLinkElement | null = document.querySelector("link[rel~='icon']");
            if (!link) {
              link = document.createElement("link");
              link.rel = "icon";
              document.getElementsByTagName("head")[0].appendChild(link);
            }
            link.href = faviconUrl;
          }

          // 3. Dynamic Page Title prefix matcher
          if (document.title.includes("Deploymeta") || document.title.includes("Telehealth")) {
            const parts = document.title.split("-");
            const prefix = parts[0]?.trim();
            if (prefix && prefix !== "Deploymeta Telehealth" && prefix !== "Telehealth" && parts.length > 1) {
              document.title = `${prefix} - ${appName}`;
            } else {
              document.title = appName;
            }
          }
        }
      } catch (error) {
        console.error("Failed to fetch settings from API:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchSettings();
  }, []);

  return (
    <SettingsContext.Provider value={{ settings, loading }}>
      {children}
    </SettingsContext.Provider>
  );
}

export const useSettings = () => useContext(SettingsContext);
