import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { Ionicons } from "@expo/vector-icons";

export default function LoginScreen() {
  const router = useRouter();
  const { signIn, isLoading, isAuthenticated, user } = useAuth();

  const [placa, setPlaca] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Redirigir al dashboard si ya hay sesión activa
  useEffect(() => {
    if (isAuthenticated && user) {
      router.replace("/dashboard");
    }
  }, [isAuthenticated, user]);

  const handleLogin = async () => {
    setErrorMsg(null);
    if (!placa.trim()) {
      setErrorMsg("Ingrese su número de placa o credencial oficial.");
      return;
    }
    if (!password) {
      setErrorMsg("Ingrese su contraseña de acceso.");
      return;
    }

    try {
      await signIn(placa.trim(), password);
      router.replace("/dashboard");
    } catch (err: any) {
      const msg = err?.message || "Credenciales incorrectas o error en el sistema.";
      setErrorMsg(msg);
      Alert.alert("Error de Acceso", msg);
    }
  };

  const handleAutoFillDemo = () => {
    setPlaca("AGT-204");
    setPassword("transito2026");
    setErrorMsg(null);
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={styles.keyboardContainer}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        {/* Header Institucional de Uriangato */}
        <View style={styles.header}>
          <View style={styles.shieldBadge}>
            <Ionicons name="shield-checkmark" size={44} color="#3b82f6" />
          </View>
          <Text style={styles.govTitle}>MUNICIPIO DE URIANGATO</Text>
          <Text style={styles.deptTitle}>Movilidad y Transporte</Text>
          <View style={styles.badgePill}>
            <View style={styles.onlineDot} />
            <Text style={styles.badgePillText}>SISTEMA DE BOLETAS DE INFRACCIÓN</Text>
          </View>
        </View>

        {/* Tarjeta de Formulario de Acceso */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Acceso de Agentes</Text>
          <Text style={styles.cardSubtitle}>
            Identifíquese con su número de placa/credencial asignado.
          </Text>

          {errorMsg && (
            <View style={styles.errorBox}>
              <Ionicons name="alert-circle" size={18} color="#ef4444" />
              <Text style={styles.errorText}>{errorMsg}</Text>
            </View>
          )}

          {/* Campo Placa / Credencial */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>NÚMERO DE PLACA / CREDENCIAL</Text>
            <View style={styles.inputWrapper}>
              <Ionicons name="id-card-outline" size={20} color="#a1a1aa" style={styles.inputIcon} />
              <TextInput
                style={styles.input}
                placeholder="Ej. AGT-204"
                placeholderTextColor="#71717a"
                value={placa}
                onChangeText={(val) => {
                  setPlaca(val.toUpperCase());
                  setErrorMsg(null);
                }}
                autoCapitalize="characters"
                autoCorrect={false}
              />
            </View>
          </View>

          {/* Campo Contraseña */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>CONTRASEÑA</Text>
            <View style={styles.inputWrapper}>
              <Ionicons name="lock-closed-outline" size={20} color="#a1a1aa" style={styles.inputIcon} />
              <TextInput
                style={styles.input}
                placeholder="••••••••"
                placeholderTextColor="#71717a"
                secureTextEntry={!showPassword}
                value={password}
                onChangeText={(val) => {
                  setPassword(val);
                  setErrorMsg(null);
                }}
              />
              <TouchableOpacity
                onPress={() => setShowPassword(!showPassword)}
                style={styles.eyeIcon}
                hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
              >
                <Ionicons
                  name={showPassword ? "eye-off-outline" : "eye-outline"}
                  size={20}
                  color="#a1a1aa"
                />
              </TouchableOpacity>
            </View>
          </View>

          {/* Botón de Entrada Principal */}
          <TouchableOpacity
            style={[styles.primaryButton, isLoading && styles.buttonDisabled]}
            onPress={handleLogin}
            disabled={isLoading}
            activeOpacity={0.8}
          >
            {isLoading ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <View style={styles.buttonContent}>
                <Ionicons name="log-in-outline" size={22} color="#ffffff" />
                <Text style={styles.primaryButtonText}>INGRESAR AL SISTEMA</Text>
              </View>
            )}
          </TouchableOpacity>

          {/* Botón de prueba rápida */}
          <TouchableOpacity
            style={styles.demoButton}
            onPress={handleAutoFillDemo}
            activeOpacity={0.7}
          >
            <Ionicons name="flash-outline" size={16} color="#60a5fa" />
            <Text style={styles.demoButtonText}>Autocompletar Agente de Prueba (AGT-204)</Text>
          </TouchableOpacity>
        </View>

        {/* Indicador de Respaldo Offline */}
        <View style={styles.offlineNotice}>
          <Ionicons name="cloud-offline-outline" size={20} color="#10b981" />
          <View style={styles.offlineNoticeTextWrap}>
            <Text style={styles.offlineNoticeTitle}>Operación Offline Garantizada</Text>
            <Text style={styles.offlineNoticeDesc}>
              El sistema guarda las boletas en la memoria del dispositivo y sincroniza al recuperar señal.
            </Text>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  keyboardContainer: {
    flex: 1,
    backgroundColor: "#09090b",
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: "center",
    paddingHorizontal: 20,
    paddingVertical: 36,
  },
  header: {
    alignItems: "center",
    marginBottom: 28,
  },
  shieldBadge: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "#18181b",
    borderWidth: 2,
    borderColor: "#27272a",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 16,
    shadowColor: "#3b82f6",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
    elevation: 8,
  },
  govTitle: {
    fontSize: 12,
    letterSpacing: 2,
    color: "#a1a1aa",
    fontWeight: "700",
    textTransform: "uppercase",
  },
  deptTitle: {
    fontSize: 22,
    fontWeight: "800",
    color: "#ffffff",
    marginTop: 4,
    textAlign: "center",
  },
  badgePill: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#18181b",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    marginTop: 10,
    borderWidth: 1,
    borderColor: "#27272a",
  },
  onlineDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: "#10b981",
    marginRight: 8,
  },
  badgePillText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#d4d4d8",
    letterSpacing: 0.5,
  },
  card: {
    backgroundColor: "#121214",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#27272a",
    padding: 24,
    shadowColor: "#000000",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 12,
    elevation: 10,
  },
  cardTitle: {
    fontSize: 19,
    fontWeight: "700",
    color: "#ffffff",
  },
  cardSubtitle: {
    fontSize: 13,
    color: "#a1a1aa",
    marginTop: 4,
    marginBottom: 20,
    lineHeight: 18,
  },
  errorBox: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(239, 68, 68, 0.15)",
    borderWidth: 1,
    borderColor: "rgba(239, 68, 68, 0.4)",
    borderRadius: 8,
    padding: 10,
    marginBottom: 16,
    gap: 8,
  },
  errorText: {
    color: "#f87171",
    fontSize: 13,
    flex: 1,
  },
  inputGroup: {
    marginBottom: 18,
  },
  label: {
    fontSize: 11,
    fontWeight: "700",
    color: "#d4d4d8",
    letterSpacing: 1,
    marginBottom: 8,
  },
  inputWrapper: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderColor: "#3f3f46",
    borderRadius: 10,
    paddingHorizontal: 14,
    minHeight: 52,
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    color: "#ffffff",
    fontSize: 16,
    fontWeight: "600",
    paddingVertical: 12,
  },
  eyeIcon: {
    padding: 4,
  },
  primaryButton: {
    backgroundColor: "#2563eb",
    borderRadius: 10,
    paddingVertical: 15,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 8,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 6,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonContent: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  primaryButtonText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
    letterSpacing: 0.8,
  },
  demoButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    marginTop: 16,
    paddingVertical: 10,
    backgroundColor: "rgba(59, 130, 246, 0.1)",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "rgba(59, 130, 246, 0.3)",
    gap: 8,
  },
  demoButtonText: {
    color: "#93c5fd",
    fontSize: 13,
    fontWeight: "600",
  },
  offlineNotice: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "rgba(16, 185, 129, 0.1)",
    borderWidth: 1,
    borderColor: "rgba(16, 185, 129, 0.25)",
    borderRadius: 12,
    padding: 14,
    marginTop: 24,
    gap: 12,
  },
  offlineNoticeTextWrap: {
    flex: 1,
  },
  offlineNoticeTitle: {
    color: "#34d399",
    fontSize: 13,
    fontWeight: "700",
  },
  offlineNoticeDesc: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 2,
    lineHeight: 16,
  },
});
