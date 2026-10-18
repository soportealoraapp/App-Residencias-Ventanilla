import React from "react";
import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";

/**
 * Pantalla para rutas inexistentes dentro de la app de Agentes de Tránsito.
 *
 * Sustituye el "Unmatched Route" en inglés de Expo Router (y, de paso, el 404
 * genérico de la plataforma) por un mensaje claro y humano en español, con una
 * acción para regresar a un punto conocido.
 */
export default function UnmatchedScreen() {
  const router = useRouter();
  const { colors } = useTheme();

  return (
    <ScrollView
      contentContainerStyle={[styles.container, { backgroundColor: colors.background }]}
    >
      <View style={styles.topBar}>
        <Text style={[styles.brand, { color: colors.textSecondary }]}>
          AGENTES DE TRÁNSITO
        </Text>
        <ThemeToggle />
      </View>

      <View style={styles.content}>
        <View
          style={[
            styles.iconBadge,
            { backgroundColor: colors.dangerBg, borderColor: colors.border },
          ]}
        >
          <Ionicons name="compass-outline" size={44} color={colors.danger} />
        </View>

        <Text style={[styles.code, { color: colors.text }]}>
          Página no encontrada
        </Text>
        <Text style={[styles.message, { color: colors.textSecondary }]}>
          La dirección que abriste no existe en la app. Es posible que el enlace
          esté mal escrito o haya cambiado. Desde aquí puedes regresar a un lugar
          conocido para continuar.
        </Text>

        <View style={styles.actions}>
          <TouchableOpacity
            style={[styles.primaryButton, { backgroundColor: colors.primary }]}
            onPress={() => router.replace("/")}
            activeOpacity={0.85}
          >
            <Ionicons name="home-outline" size={20} color="#ffffff" />
            <Text style={styles.primaryButtonText}>Ir al inicio</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[
              styles.secondaryButton,
              { backgroundColor: colors.surface, borderColor: colors.border },
            ]}
            onPress={() => router.back()}
            activeOpacity={0.85}
          >
            <Ionicons name="arrow-undo-outline" size={20} color={colors.text} />
            <Text style={[styles.secondaryButtonText, { color: colors.text }]}>
              Regresar
            </Text>
          </TouchableOpacity>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, padding: 20 },
  topBar: {
    width: "100%",
    maxWidth: 520,
    alignSelf: "center",
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  brand: { fontSize: 11, fontWeight: "800", letterSpacing: 0.7 },
  content: {
    width: "100%",
    maxWidth: 460,
    alignSelf: "center",
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 32,
  },
  iconBadge: {
    width: 84,
    height: 84,
    borderRadius: 42,
    borderWidth: 1,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 20,
  },
  code: { fontSize: 24, fontWeight: "800", textAlign: "center" },
  message: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: "center",
    marginTop: 12,
    marginBottom: 26,
    paddingHorizontal: 8,
  },
  actions: { width: "100%", gap: 12 },
  primaryButton: {
    minHeight: 54,
    borderRadius: 13,
    paddingHorizontal: 18,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 10,
  },
  primaryButtonText: { color: "#ffffff", fontSize: 15, fontWeight: "800" },
  secondaryButton: {
    minHeight: 50,
    borderRadius: 13,
    borderWidth: 1,
    paddingHorizontal: 18,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 10,
  },
  secondaryButtonText: { fontSize: 15, fontWeight: "700" },
});
