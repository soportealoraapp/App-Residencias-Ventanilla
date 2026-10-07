import React from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { Ionicons } from "@expo/vector-icons";

export default function WelcomeScreen() {
  const router = useRouter();
  const { isLoading, user } = useAuth();
  const { colors } = useTheme();

  React.useEffect(() => {
    console.log("[INDEX] isLoading:", isLoading, "user:", user);
    if (!isLoading && user) {
      router.replace("/dashboard");
    }
  }, [isLoading, user, router]);

  if (isLoading || user) {
    return (
      <View style={[styles.loadingScreen, { backgroundColor: colors.background }]}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      contentContainerStyle={[
        styles.container,
        { backgroundColor: colors.background },
      ]}
    >
      <View style={styles.topBar}>
        <View style={styles.systemTag}>
          <View style={[styles.statusDot, { backgroundColor: colors.success }]} />
          <Text style={[styles.systemTagText, { color: colors.textSecondary }]}>
            SISTEMA DE AGENTES
          </Text>
        </View>
        <ThemeToggle />
      </View>

      <View style={styles.content}>
        <View
          style={[
            styles.shieldBadge,
            { backgroundColor: colors.primaryBg, borderColor: colors.border },
          ]}
        >
          <Ionicons name="shield-checkmark" size={48} color={colors.primary} />
        </View>
        <Text style={[styles.institution, { color: colors.primary }]}>
          MUNICIPIO DE URIANGATO
        </Text>
        <Text style={[styles.title, { color: colors.text }]}>
          Movilidad y Transporte
        </Text>
        <View
          style={[
            styles.systemBadge,
            { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
          ]}
        >
          <Text style={[styles.systemBadgeText, { color: colors.textSecondary }]}>
            SISTEMA DE BOLETAS DE INFRACCIÓN
          </Text>
        </View>
        <Text style={[styles.description, { color: colors.textSecondary }]}>
          Ingrese al sistema con sus credenciales o consulte la opción de creación
          de cuenta.
        </Text>

        <View
          style={[
            styles.actionsCard,
            { backgroundColor: colors.surface, borderColor: colors.border },
          ]}
        >
          <TouchableOpacity
            style={[styles.primaryButton, { backgroundColor: colors.primary }]}
            onPress={() => router.push("/login")}
            activeOpacity={0.82}
          >
            <Ionicons name="log-in-outline" size={22} color="#ffffff" />
            <Text style={styles.primaryButtonText}>Iniciar sesión</Text>
            <Ionicons name="chevron-forward" size={19} color="#ffffff" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[
              styles.secondaryButton,
              { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
            ]}
            onPress={() => router.push("/registro")}
            activeOpacity={0.78}
          >
            <Ionicons name="person-add-outline" size={21} color={colors.primary} />
            <Text style={[styles.secondaryButtonText, { color: colors.primary }]}>
              Crear una cuenta
            </Text>
            <Ionicons name="chevron-forward" size={19} color={colors.primary} />
          </TouchableOpacity>
        </View>

        <View
          style={[
            styles.offlineNotice,
            { backgroundColor: colors.surface, borderColor: colors.border },
          ]}
        >
          <Ionicons name="cloud-offline-outline" size={21} color={colors.success} />
          <Text style={[styles.offlineText, { color: colors.textSecondary }]}>
            Las boletas se almacenan localmente y se sincronizan al contar con
            conexión.
          </Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  loadingScreen: { flex: 1, alignItems: "center", justifyContent: "center" },
  container: { flexGrow: 1, padding: 20 },
  topBar: {
    width: "100%",
    maxWidth: 520,
    alignSelf: "center",
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  systemTag: { flexDirection: "row", alignItems: "center", gap: 8 },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  systemTagText: { fontSize: 11, fontWeight: "800", letterSpacing: 0.7 },
  content: {
    width: "100%",
    maxWidth: 460,
    alignSelf: "center",
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 32,
  },
  shieldBadge: {
    width: 82,
    height: 82,
    borderRadius: 41,
    borderWidth: 1,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 20,
  },
  institution: { fontSize: 12, fontWeight: "900", letterSpacing: 1.3 },
  title: { fontSize: 25, fontWeight: "800", textAlign: "center", marginTop: 7 },
  systemBadge: {
    borderWidth: 1,
    borderRadius: 20,
    paddingHorizontal: 12,
    paddingVertical: 7,
    marginTop: 14,
  },
  systemBadgeText: { fontSize: 10, fontWeight: "800", letterSpacing: 0.7 },
  description: {
    fontSize: 14,
    lineHeight: 21,
    textAlign: "center",
    marginTop: 22,
    marginBottom: 22,
    paddingHorizontal: 8,
  },
  actionsCard: {
    width: "100%",
    padding: 16,
    borderRadius: 18,
    borderWidth: 1,
    gap: 12,
  },
  primaryButton: {
    minHeight: 54,
    borderRadius: 13,
    paddingHorizontal: 16,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 10,
  },
  primaryButtonText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
    flex: 1,
  },
  secondaryButton: {
    minHeight: 54,
    borderRadius: 13,
    borderWidth: 1,
    paddingHorizontal: 16,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 10,
  },
  secondaryButtonText: { fontSize: 15, fontWeight: "800", flex: 1 },
  offlineNotice: {
    width: "100%",
    marginTop: 18,
    padding: 13,
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  offlineText: { fontSize: 12, lineHeight: 17, flex: 1 },
});
