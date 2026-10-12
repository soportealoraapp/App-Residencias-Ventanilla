import React from "react";
import { StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useTheme } from "../contexts/ThemeContext";

type AgentTab = "inicio" | "boletas" | "perfil";

interface AgentTabBarProps {
  selected: AgentTab;
}

const tabs: {
  key: AgentTab;
  label: string;
  icon: React.ComponentProps<typeof Ionicons>["name"];
}[] = [
  { key: "inicio", label: "Inicio", icon: "home-outline" },
  { key: "boletas", label: "Boletas", icon: "receipt-outline" },
  { key: "perfil", label: "Perfil", icon: "person-outline" },
];

export function AgentTabBar({ selected }: AgentTabBarProps) {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  const navigate = (tab: AgentTab) => {
    if (tab === "inicio") router.replace("/dashboard");
    if (tab === "boletas") router.replace("/boletas");
    if (tab === "perfil") router.replace("/perfil");
  };

  return (
    <View
      style={[
        styles.container,
        {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          paddingBottom: Math.max(insets.bottom, 8),
        },
      ]}
    >
      <View style={styles.content}>
        {tabs.map((tab) => {
          const isSelected = selected === tab.key;
          const color = isSelected ? colors.primary : colors.textMuted;

          return (
            <TouchableOpacity
              key={tab.key}
              accessibilityRole="tab"
              accessibilityState={{ selected: isSelected }}
              accessibilityLabel={tab.label}
              onPress={() => navigate(tab.key)}
              activeOpacity={0.75}
              style={[
                styles.tab,
                isSelected && { backgroundColor: colors.primaryBg },
              ]}
            >
              <Ionicons name={tab.icon} size={21} color={color} />
              <Text style={[styles.label, { color, fontWeight: isSelected ? "800" : "600" }]}>
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingTop: 6,
  },
  content: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
    flexDirection: "row",
  },
  tab: {
    minHeight: 46,
    flex: 1,
    marginHorizontal: 4,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
    gap: 2,
  },
  label: {
    fontSize: 10,
  },
});
