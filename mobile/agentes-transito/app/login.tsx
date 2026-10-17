import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { APP_ENV } from "../src/constants/config";
import { Ionicons } from "@expo/vector-icons";

/**
 * Agentes del AgentesTransitoSeeder para entrar rápido durante las pruebas.
 * Solo se muestran con EXPO_PUBLIC_APP_ENV=development y siempre se validan
 * contra el backend: si el agente no existe o está inactivo, el acceso falla.
 */
const AGENTES_PRUEBA =
  APP_ENV === "development"
    ? [
        { placa: "AGT-204", etiqueta: "AGT-204 · Carlos M.", icono: "shield" as const },
        { placa: "AGT-107", etiqueta: "AGT-107 · María H.", icono: "shield" as const },
        { placa: "JEF-001", etiqueta: "JEF-001 · Javier R.", icono: "star" as const },
      ]
    : [];
const PASSWORD_PRUEBA = "transito2026";

export default function LoginScreen() {
  const router = useRouter();
  const { signIn, isLoading, isAuthenticated, user, sessionError } = useAuth();
  const { colors, isDark } = useTheme();

  const [placa, setPlaca] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(sessionError);

  // Redirigir al dashboard si ya hay sesión activa
  useEffect(() => {
    if (isAuthenticated && user) {
      router.replace("/dashboard");
    }
  }, [isAuthenticated, user]);

  useEffect(() => {
    if (sessionError) setErrorMsg(sessionError);
  }, [sessionError]);

  const handleLogin = async (placaIngreso = placa, passwordIngreso = password) => {
    setErrorMsg(null);
    if (!placaIngreso.trim()) {
      setErrorMsg("Ingrese su número de placa o credencial oficial.");
      return;
    }
    if (!passwordIngreso) {
      setErrorMsg("Ingrese su contraseña de acceso.");
      return;
    }

    try {
      await signIn(placaIngreso.trim(), passwordIngreso);
      router.replace("/dashboard");
    } catch (err: any) {
      const msg = err?.message || "No fue posible validar las credenciales.";
      setErrorMsg(msg);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.keyboardContainer, { backgroundColor: colors.background }]}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        {/* Barra superior con selector de tema */}
        <View style={styles.topBar}>
          <View style={styles.topLiveTag}>
            <View style={styles.onlineDot} />
            <Text style={[styles.topLiveText, { color: colors.textSecondary }]}>SISTEMA EN LÍNEA</Text>
          </View>
          <ThemeToggle />
        </View>

        {/* Header Institucional de Uriangato */}
        <View style={styles.header}>
          <View style={[styles.shieldBadge, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
            <Ionicons name="shield-checkmark" size={44} color={colors.primary} />
          </View>
          <Text style={[styles.govTitle, { color: colors.primary }]}>MUNICIPIO DE URIANGATO</Text>
          <Text style={[styles.deptTitle, { color: colors.text }]}>Movilidad y Transporte</Text>
          <View style={[styles.badgePill, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
            <Text style={[styles.badgePillText, { color: colors.textSecondary }]}>
              SISTEMA DE BOLETAS DE INFRACCIÓN
            </Text>
          </View>
        </View>

        {/* Tarjeta de Formulario de Acceso */}
        <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.cardTitle, { color: colors.text }]}>Acceso de Agentes</Text>
          <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
            Identifíquese con su placa/credencial asignada en el padrón de Uriangato.
          </Text>

          {errorMsg && (
            <View style={[styles.errorBox, { backgroundColor: colors.dangerBg, borderColor: colors.danger }]}>
              <Ionicons name="alert-circle" size={18} color={colors.danger} />
              <Text style={[styles.errorText, { color: colors.danger }]}>{errorMsg}</Text>
            </View>
          )}

          {/* Campo Placa / Credencial */}
          <View style={styles.inputGroup}>
            <Text style={[styles.label, { color: colors.textSecondary }]}>NÚMERO DE PLACA / CREDENCIAL</Text>
            <View style={[styles.inputWrapper, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
              <Ionicons name="id-card-outline" size={20} color={colors.textMuted} style={styles.inputIcon} />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="Ej. AGT-204"
                placeholderTextColor={colors.placeholder}
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
            <Text style={[styles.label, { color: colors.textSecondary }]}>CONTRASEÑA</Text>
            <View style={[styles.inputWrapper, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
              <Ionicons name="lock-closed-outline" size={20} color={colors.textMuted} style={styles.inputIcon} />
              <TextInput
                style={[styles.input, { color: colors.text }]}
                placeholder="••••••••"
                placeholderTextColor={colors.placeholder}
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
                  color={colors.textMuted}
                />
              </TouchableOpacity>
            </View>
          </View>

          {/* Botón de Entrada Principal */}
          <TouchableOpacity
            style={[styles.primaryButton, { backgroundColor: colors.primary }, isLoading && styles.buttonDisabled]}
            onPress={() => handleLogin()}
            disabled={isLoading}
            activeOpacity={0.85}
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

          {AGENTES_PRUEBA.length > 0 && (
            <View style={styles.demoSection}>
              <Text style={[styles.demoSectionLabel, { color: colors.textMuted }]}>
                ACCESO RÁPIDO DE PRUEBA (SOLO DESARROLLO)
              </Text>
              <View style={styles.demoChipsRow}>
                {AGENTES_PRUEBA.map((agente) => (
                  <TouchableOpacity
                    key={agente.placa}
                    style={[
                      styles.demoChip,
                      {
                        backgroundColor: colors.surfaceElevated,
                        borderColor: placa === agente.placa ? colors.primary : colors.border,
                      },
                      isLoading && styles.buttonDisabled,
                    ]}
                    onPress={() => {
                      setPlaca(agente.placa);
                      setPassword(PASSWORD_PRUEBA);
                      handleLogin(agente.placa, PASSWORD_PRUEBA);
                    }}
                    disabled={isLoading}
                    activeOpacity={0.7}
                    accessibilityLabel={`Entrar como ${agente.etiqueta}`}
                  >
                    <Ionicons name={agente.icono} size={13} color={colors.primary} />
                    <Text style={[styles.demoChipText, { color: colors.text }]}>{agente.etiqueta}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          )}
        </View>

        {/* Indicador de Respaldo Offline */}
        <View style={[styles.offlineNotice, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Ionicons name="cloud-offline-outline" size={22} color={colors.info || colors.primary} />
          <View style={styles.offlineNoticeTextWrap}>
            <Text style={[styles.offlineNoticeTitle, { color: colors.text }]}>Acceso con conexión requerida</Text>
            <Text style={[styles.offlineNoticeDesc, { color: colors.textSecondary }]}>
              Para iniciar o validar una sesión necesita conexión. Las boletas ya capturadas se conservan localmente.
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
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: "center",
    paddingHorizontal: 20,
    paddingVertical: 24,
  },
  topBar: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 20,
  },
  topLiveTag: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  topLiveText: {
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.5,
  },
  onlineDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: "#10b981",
  },
  header: {
    alignItems: "center",
    marginBottom: 24,
  },
  shieldBadge: {
    width: 80,
    height: 80,
    borderRadius: 40,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 14,
  },
  govTitle: {
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 2,
    marginBottom: 4,
  },
  deptTitle: {
    fontSize: 22,
    fontWeight: "800",
    letterSpacing: -0.5,
    marginBottom: 10,
  },
  badgePill: {
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
    borderWidth: 1,
  },
  badgePillText: {
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.5,
  },
  card: {
    borderRadius: 20,
    padding: 24,
    borderWidth: 1,
    marginBottom: 20,
  },
  cardTitle: {
    fontSize: 19,
    fontWeight: "800",
    marginBottom: 6,
  },
  cardSubtitle: {
    fontSize: 13,
    lineHeight: 18,
    marginBottom: 20,
  },
  errorBox: {
    flexDirection: "row",
    alignItems: "center",
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 16,
    gap: 8,
  },
  errorText: {
    fontSize: 13,
    fontWeight: "600",
    flex: 1,
  },
  inputGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.8,
    marginBottom: 8,
  },
  inputWrapper: {
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 14,
    height: 52,
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    fontSize: 15,
    fontWeight: "500",
    paddingVertical: 0,
  },
  eyeIcon: {
    padding: 6,
  },
  primaryButton: {
    borderRadius: 12,
    height: 52,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 8,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonContent: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  primaryButtonText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  demoSection: {
    marginTop: 18,
  },
  demoSectionLabel: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 0.8,
    marginBottom: 8,
    textAlign: "center",
  },
  demoChipsRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "center",
    gap: 8,
  },
  demoChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
  },
  demoChipText: {
    fontSize: 12,
    fontWeight: "700",
  },
  offlineNotice: {
    flexDirection: "row",
    alignItems: "center",
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    gap: 12,
  },
  offlineNoticeTextWrap: {
    flex: 1,
  },
  offlineNoticeTitle: {
    fontSize: 13,
    fontWeight: "700",
    marginBottom: 2,
  },
  offlineNoticeDesc: {
    fontSize: 12,
    lineHeight: 16,
  },
});
