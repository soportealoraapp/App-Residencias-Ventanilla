import React, { useState } from "react";
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from "react-native";
import { useRouter } from "expo-router";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { Ionicons } from "@expo/vector-icons";

export default function RegistroScreen() {
  const router = useRouter();
  const { colors } = useTheme();
  const [placa, setPlaca] = useState("");
  const [nombre, setNombre] = useState("");
  const [password, setPassword] = useState("");
  const [confirmarPassword, setConfirmarPassword] = useState("");
  const [mostrarAviso, setMostrarAviso] = useState(false);

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.keyboardContainer, { backgroundColor: colors.background }]}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.topBar}>
          <TouchableOpacity
            style={[
              styles.backButton,
              { backgroundColor: colors.surface, borderColor: colors.border },
            ]}
            onPress={() => router.replace("/")}
            accessibilityLabel="Volver a la pantalla inicial"
            activeOpacity={0.75}
          >
            <Ionicons name="arrow-back" size={20} color={colors.text} />
          </TouchableOpacity>
          <ThemeToggle compact />
        </View>

        <View style={styles.content}>
          <View
            style={[
              styles.shieldBadge,
              { backgroundColor: colors.primaryBg, borderColor: colors.border },
            ]}
          >
            <Ionicons name="person-add-outline" size={34} color={colors.primary} />
          </View>
          <Text style={[styles.title, { color: colors.text }]}>Crear una cuenta</Text>
          <Text style={[styles.subtitle, { color: colors.textSecondary }]}>
            Captura tus datos para solicitar el acceso al sistema de agentes.
          </Text>

          <View
            style={[
              styles.formCard,
              { backgroundColor: colors.surface, borderColor: colors.border },
            ]}
          >
            <Text style={[styles.label, { color: colors.textSecondary }]}>
              PLACA / CREDENCIAL
            </Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
              ]}
            >
              <Ionicons
                name="id-card-outline"
                size={19}
                color={colors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="Ej. AGT-305"
                placeholderTextColor={colors.textMuted}
                value={placa}
                onChangeText={(value) => {
                  setPlaca(value.toUpperCase());
                  setMostrarAviso(false);
                }}
                autoCapitalize="characters"
                autoCorrect={false}
              />
            </View>

            <Text style={[styles.label, { color: colors.textSecondary }]}>
              NOMBRE COMPLETO
            </Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
              ]}
            >
              <Ionicons
                name="person-outline"
                size={19}
                color={colors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="Nombre y apellidos"
                placeholderTextColor={colors.textMuted}
                value={nombre}
                onChangeText={(value) => {
                  setNombre(value);
                  setMostrarAviso(false);
                }}
                autoCapitalize="words"
                autoCorrect={false}
              />
            </View>

            <Text style={[styles.label, { color: colors.textSecondary }]}>
              CONTRASEÑA
            </Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
              ]}
            >
              <Ionicons
                name="lock-closed-outline"
                size={19}
                color={colors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="Ingresa una contraseña"
                placeholderTextColor={colors.textMuted}
                value={password}
                onChangeText={(value) => {
                  setPassword(value);
                  setMostrarAviso(false);
                }}
                secureTextEntry
                autoCapitalize="none"
              />
            </View>

            <Text style={[styles.label, { color: colors.textSecondary }]}>
              CONFIRMAR CONTRASEÑA
            </Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
              ]}
            >
              <Ionicons
                name="lock-closed-outline"
                size={19}
                color={colors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="Vuelve a ingresar la contraseña"
                placeholderTextColor={colors.textMuted}
                value={confirmarPassword}
                onChangeText={(value) => {
                  setConfirmarPassword(value);
                  setMostrarAviso(false);
                }}
                secureTextEntry
                autoCapitalize="none"
              />
            </View>

            {mostrarAviso && (
              <View
                accessibilityRole="alert"
                style={[
                  styles.notice,
                  { backgroundColor: colors.primaryBg, borderColor: colors.primary },
                ]}
              >
                <Ionicons
                  name="information-circle-outline"
                  size={20}
                  color={colors.primary}
                />
                <Text style={[styles.noticeText, { color: colors.text }]}>
                  El registro aún no está conectado al sistema. No se creó ni guardó
                  ninguna cuenta.
                </Text>
              </View>
            )}

            <TouchableOpacity
              style={[styles.createButton, { backgroundColor: colors.primary }]}
              onPress={() => setMostrarAviso(true)}
              activeOpacity={0.82}
            >
              <Ionicons name="person-add-outline" size={20} color="#ffffff" />
              <Text style={styles.createButtonText}>Crear cuenta</Text>
            </TouchableOpacity>

            <Text style={[styles.pendingText, { color: colors.textMuted }]}>
              Formulario de demostración; todavía no envía ni almacena información.
            </Text>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  keyboardContainer: { flex: 1 },
  scrollContent: { flexGrow: 1, padding: 20 },
  topBar: {
    width: "100%",
    maxWidth: 520,
    alignSelf: "center",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  content: {
    width: "100%",
    maxWidth: 460,
    alignSelf: "center",
    flex: 1,
    justifyContent: "center",
    paddingVertical: 28,
  },
  shieldBadge: {
    width: 66,
    height: 66,
    borderRadius: 33,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    alignSelf: "center",
    marginBottom: 16,
  },
  title: { fontSize: 24, fontWeight: "800", textAlign: "center" },
  subtitle: {
    fontSize: 13,
    lineHeight: 19,
    textAlign: "center",
    marginTop: 8,
    marginBottom: 20,
  },
  formCard: { borderRadius: 18, borderWidth: 1, padding: 18 },
  label: { fontSize: 10, fontWeight: "800", letterSpacing: 0.6, marginBottom: 7 },
  inputWrapper: {
    height: 48,
    borderRadius: 11,
    borderWidth: 1,
    paddingHorizontal: 12,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 15,
  },
  inputIcon: { marginRight: 9 },
  input: { flex: 1, fontSize: 14, paddingVertical: 0 },
  notice: {
    borderRadius: 11,
    borderWidth: 1,
    padding: 11,
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 14,
  },
  noticeText: { flex: 1, fontSize: 12, lineHeight: 17, fontWeight: "600" },
  createButton: {
    minHeight: 52,
    borderRadius: 12,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 9,
    marginTop: 2,
  },
  createButtonText: { color: "#ffffff", fontSize: 14, fontWeight: "800" },
  pendingText: { fontSize: 11, textAlign: "center", lineHeight: 16, marginTop: 12 },
});
