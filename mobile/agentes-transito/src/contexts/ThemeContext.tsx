import React, { createContext, useContext, useEffect, useState, useMemo } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";

export type ThemeMode = "dark" | "light";

export interface ThemeColors {
  background: string;
  surface: string;
  surfaceElevated: string;
  border: string;
  borderSubtle: string;
  text: string;
  textSecondary: string;
  textMuted: string;
  primary: string;
  primaryLight: string;
  primaryBg: string;
  accent: string;
  accentBg: string;
  success: string;
  successBg: string;
  warning: string;
  warningBg: string;
  danger: string;
  dangerBg: string;
  inputBg: string;
  inputBorder: string;
  cardShadow: string;
}

export const darkColors: ThemeColors = {
  background: "#09090b",
  surface: "#121215",
  surfaceElevated: "#18181b",
  border: "#27272a",
  borderSubtle: "#1f1f23",
  text: "#fafafa",
  textSecondary: "#a1a1aa",
  textMuted: "#71717a",
  primary: "#3b82f6",
  primaryLight: "#60a5fa",
  primaryBg: "rgba(59, 130, 246, 0.12)",
  accent: "#06b6d4",
  accentBg: "rgba(6, 182, 212, 0.12)",
  success: "#10b981",
  successBg: "rgba(16, 185, 129, 0.12)",
  warning: "#f59e0b",
  warningBg: "rgba(245, 158, 11, 0.12)",
  danger: "#ef4444",
  dangerBg: "rgba(239, 68, 68, 0.12)",
  inputBg: "#18181b",
  inputBorder: "#27272a",
  cardShadow: "rgba(0, 0, 0, 0.5)",
};

export const lightColors: ThemeColors = {
  background: "#f8fafc",
  surface: "#ffffff",
  surfaceElevated: "#f1f5f9",
  border: "#e2e8f0",
  borderSubtle: "#cbd5e1",
  text: "#0f172a",
  textSecondary: "#475569",
  textMuted: "#64748b",
  primary: "#2563eb",
  primaryLight: "#3b82f6",
  primaryBg: "rgba(37, 99, 235, 0.08)",
  accent: "#0891b2",
  accentBg: "rgba(8, 145, 178, 0.08)",
  success: "#059669",
  successBg: "rgba(5, 150, 105, 0.08)",
  warning: "#d97706",
  warningBg: "rgba(217, 119, 6, 0.08)",
  danger: "#dc2626",
  dangerBg: "rgba(220, 38, 38, 0.08)",
  inputBg: "#f8fafc",
  inputBorder: "#cbd5e1",
  cardShadow: "rgba(0, 0, 0, 0.06)",
};

interface ThemeContextValue {
  theme: ThemeMode;
  isDark: boolean;
  colors: ThemeColors;
  toggleTheme: () => void;
  setTheme: (t: ThemeMode) => void;
}

const THEME_STORAGE_KEY = "@agentes_transito_theme_mode_v1";

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [theme, setThemeState] = useState<ThemeMode>("dark");

  useEffect(() => {
    async function loadSavedTheme() {
      try {
        const saved = await AsyncStorage.getItem(THEME_STORAGE_KEY);
        if (saved === "light" || saved === "dark") {
          setThemeState(saved);
        }
      } catch (err) {
        console.error("Error loading theme preference:", err);
      }
    }
    loadSavedTheme();
  }, []);

  const setTheme = async (t: ThemeMode) => {
    setThemeState(t);
    try {
      await AsyncStorage.setItem(THEME_STORAGE_KEY, t);
    } catch (err) {
      console.error("Error saving theme preference:", err);
    }
  };

  const toggleTheme = () => {
    const next = theme === "dark" ? "light" : "dark";
    setTheme(next);
  };

  const colors = useMemo(() => (theme === "dark" ? darkColors : lightColors), [theme]);

  const value = useMemo(
    () => ({
      theme,
      isDark: theme === "dark",
      colors,
      toggleTheme,
      setTheme,
    }),
    [theme, colors]
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error("useTheme must be used within a <ThemeProvider>");
  return ctx;
}
