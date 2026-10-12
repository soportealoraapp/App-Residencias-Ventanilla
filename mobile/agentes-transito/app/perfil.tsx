import React, { useEffect, useState } from "react";
import {
  ActivityIndicator,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { AgentTabBar } from "../src/components/AgentTabBar";
import { AgentScreenHeader } from "../src/components/AgentScreenHeader";
import { LogoutConfirmationModal } from "../src/components/LogoutConfirmationModal";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { useAuth } from "../src/contexts/AuthContext";
import { useTheme } from "../src/contexts/ThemeContext";

export default function PerfilScreen() {
  const router = useRouter();
  const { user, isLoading: authLoading, logout } = useAuth();
  const { colors } = useTheme();
  const [confirmacionVisible, setConfirmacionVisible] = useState(false);

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  const handleLogout = async () => {
    await logout();
    router.replace("/");
  };

  if (authLoading || !user) {
    return (
      <View style={[styles.loading, { backgroundColor: colors.background }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  const datosAgente = [
    { label: "Número de placa", value: user.placa, icon: "card-outline" as const },
    { label: "Rol", value: user.rol, icon: "shield-outline" as const },
    { label: "Sector asignado", value: user.sector, icon: "location-outline" as const },
  ].filter((dato) => dato.value?.trim());

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <AgentScreenHeader actions={<ThemeToggle compact />} />

      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.screenHeading}>
          <Text style={[styles.screenTitle, { color: colors.text }]}>Mi perfil</Text>
          <Text style={[styles.screenSubtitle, { color: colors.textSecondary }]}>
            Información de tu cuenta operativa
          </Text>
        </View>
        <View style={styles.identity}>
          <View style={[styles.avatar, { backgroundColor: colors.primaryBg }]}>
            <Ionicons name="person" size={29} color={colors.primary} />
          </View>
          <View style={styles.identityCopy}>
            <Text style={[styles.name, { color: colors.text }]}>{user.nombre}</Text>
            {!!user.rol && (
              <Text style={[styles.role, { color: colors.textSecondary }]}>{user.rol}</Text>
            )}
          </View>
        </View>

        <View style={[styles.dataList, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.sectionTitle, { color: colors.text }]}>Datos operativos</Text>
          {datosAgente.map((dato, index) => (
            <View
              key={dato.label}
              style={[
                styles.dataRow,
                index < datosAgente.length - 1 && {
                  borderBottomWidth: StyleSheet.hairlineWidth,
                  borderBottomColor: colors.border,
                },
              ]}
            >
              <Ionicons
                name={dato.icon}
                size={18}
                color={colors.textSecondary}
                style={styles.dataIcon}
              />
              <View style={styles.dataCopy}>
                <Text style={[styles.dataLabel, { color: colors.textSecondary }]}>
                  {dato.label.toUpperCase()}
                </Text>
                <Text style={[styles.dataValue, { color: colors.text }]}>{dato.value}</Text>
              </View>
            </View>
          ))}
        </View>

        <TouchableOpacity
          style={[styles.logoutButton, { backgroundColor: colors.danger }]}
          onPress={() => setConfirmacionVisible(true)}
          activeOpacity={0.75}
          accessibilityRole="button"
          accessibilityLabel="Cerrar sesión"
        >
          <Ionicons name="log-out-outline" size={19} color="#ffffff" />
          <View style={styles.logoutCopy}>
            <Text style={styles.logoutLabel}>Cerrar sesión</Text>
            <Text style={styles.logoutDescription}>
              Salir de tu cuenta en este dispositivo
            </Text>
          </View>
        </TouchableOpacity>
      </ScrollView>

      <AgentTabBar selected="perfil" />

      <LogoutConfirmationModal
        visible={confirmacionVisible}
        onCancel={() => setConfirmacionVisible(false)}
        onConfirm={() => {
          setConfirmacionVisible(false);
          void handleLogout();
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  loading: { flex: 1, alignItems: "center", justifyContent: "center" },
  content: { width: "100%", maxWidth: 720, alignSelf: "center", flexGrow: 1, paddingHorizontal: 16, paddingTop: 18, paddingBottom: 28 },
  screenHeading: { marginBottom: 22 },
  screenTitle: { fontSize: 21, fontWeight: "800" },
  screenSubtitle: { fontSize: 11, lineHeight: 16, marginTop: 3 },
  identity: { alignItems: "center", paddingHorizontal: 8, marginBottom: 24 },
  avatar: { width: 76, height: 76, borderRadius: 38, alignItems: "center", justifyContent: "center" },
  identityCopy: { width: "100%", alignItems: "center", marginTop: 12 },
  name: { maxWidth: "100%", fontSize: 18, lineHeight: 24, fontWeight: "800", textAlign: "center", flexShrink: 1 },
  role: { fontSize: 12, marginTop: 4 },
  sectionTitle: { fontSize: 15, fontWeight: "800", paddingTop: 16, paddingBottom: 4 },
  dataList: { borderWidth: 1, borderRadius: 13, paddingHorizontal: 14, paddingBottom: 2 },
  dataRow: { minHeight: 72, flexDirection: "row", alignItems: "center", gap: 12 },
  dataIcon: { width: 20, textAlign: "center", alignSelf: "flex-start", marginTop: 18 },
  dataCopy: { flex: 1, minWidth: 0, paddingVertical: 13 },
  dataLabel: { fontSize: 10, lineHeight: 14, fontWeight: "500" },
  dataValue: { fontSize: 14, lineHeight: 20, fontWeight: "700", marginTop: 3, flexShrink: 1 },
  logoutButton: { minHeight: 64, marginTop: 18, borderRadius: 12, flexDirection: "row", alignItems: "center", paddingHorizontal: 16, gap: 12 },
  logoutCopy: { flex: 1 },
  logoutLabel: { color: "#ffffff", fontSize: 14, fontWeight: "800" },
  logoutDescription: { color: "#ffffff", fontSize: 11, lineHeight: 16, marginTop: 2, opacity: 0.9 },
});
