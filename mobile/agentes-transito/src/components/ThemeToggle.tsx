import React from "react";
import { TouchableOpacity, Text, StyleSheet, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useTheme } from "../contexts/ThemeContext";

interface ThemeToggleProps {
  compact?: boolean;
}

export function ThemeToggle({ compact = false }: ThemeToggleProps) {
  const { theme, isDark, colors, toggleTheme } = useTheme();

  if (compact) {
    return (
      <TouchableOpacity
        style={[
          styles.compactBtn,
          {
            backgroundColor: isDark ? "rgba(255, 255, 255, 0.08)" : "rgba(0, 0, 0, 0.05)",
            borderColor: colors.border,
          },
        ]}
        onPress={toggleTheme}
        activeOpacity={0.7}
        accessibilityLabel={`Cambiar a modo ${isDark ? "claro" : "oscuro"}`}
      >
        <Ionicons
          name={isDark ? "sunny-outline" : "moon-outline"}
          size={18}
          color={isDark ? "#f59e0b" : "#4f46e5"}
        />
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity
      style={[
        styles.pillContainer,
        {
          backgroundColor: isDark ? "#18181b" : "#f1f5f9",
          borderColor: colors.border,
        },
      ]}
      onPress={toggleTheme}
      activeOpacity={0.8}
      accessibilityLabel={`Cambiar a modo ${isDark ? "claro" : "oscuro"}`}
    >
      <View
        style={[
          styles.iconSegment,
          isDark && {
            backgroundColor: "#27272a",
          },
        ]}
      >
        <Ionicons
          name="moon"
          size={14}
          color={isDark ? "#60a5fa" : colors.textMuted}
        />
      </View>
      <View
        style={[
          styles.iconSegment,
          !isDark && {
            backgroundColor: "#ffffff",
            shadowColor: "#000",
            shadowOffset: { width: 0, height: 1 },
            shadowOpacity: 0.1,
            shadowRadius: 2,
            elevation: 2,
          },
        ]}
      >
        <Ionicons
          name="sunny"
          size={14}
          color={!isDark ? "#f59e0b" : colors.textMuted}
        />
      </View>
      <Text style={[styles.pillText, { color: colors.textSecondary }]}>
        {isDark ? "Oscuro" : "Claro"}
      </Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  compactBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  pillContainer: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 6,
    paddingVertical: 4,
    borderRadius: 20,
    borderWidth: 1,
    gap: 4,
  },
  iconSegment: {
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: "center",
    justifyContent: "center",
  },
  pillText: {
    fontSize: 12,
    fontWeight: "600",
    paddingHorizontal: 4,
  },
});
