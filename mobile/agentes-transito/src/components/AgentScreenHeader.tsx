import React from "react";
import { StyleSheet, Text, View } from "react-native";
import type { ReactNode } from "react";
import { Ionicons } from "@expo/vector-icons";
import { useTheme } from "../contexts/ThemeContext";

interface AgentScreenHeaderProps {
  actions?: ReactNode;
}

export function AgentScreenHeader({ actions }: AgentScreenHeaderProps) {
  const { colors } = useTheme();

  return (
    <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
      <View style={styles.content}>
        <View style={styles.brand}>
          <View style={[styles.mark, { backgroundColor: colors.primaryBg }]}>
            <Ionicons name="shield-checkmark-outline" size={20} color={colors.primary} />
          </View>
          <View style={styles.copy}>
            <Text style={[styles.title, { color: colors.text }]} numberOfLines={1}>
              TRÁNSITO · URIANGATO
            </Text>
            <Text style={[styles.subtitle, { color: colors.textSecondary }]} numberOfLines={1}>
              Sistema operativo para agentes
            </Text>
          </View>
        </View>
        {actions ? <View style={styles.actions}>{actions}</View> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    borderBottomWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  content: {
    width: "100%",
    maxWidth: 720,
    minHeight: 42,
    alignSelf: "center",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 12,
  },
  brand: {
    minWidth: 0,
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  mark: {
    width: 38,
    height: 38,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
    flexShrink: 0,
  },
  copy: { minWidth: 0, flex: 1 },
  title: { fontSize: 13, fontWeight: "800", letterSpacing: 0.45 },
  subtitle: { fontSize: 11, marginTop: 2 },
  actions: { flexDirection: "row", alignItems: "center", gap: 8, flexShrink: 0 },
});
