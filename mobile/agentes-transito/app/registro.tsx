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
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { ApiError } from "../src/api/client";
import { registerAgente } from "../src/api/agents";
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
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmarPassword, setShowConfirmarPassword] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [registroExitoso, setRegistroExitoso] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleRegister = async () => {
    setErrorMsg(null);
    setRegistroExitoso(false);

    const placaNormalizada = placa.toUpperCase().trim();
    const nombreCompleto = nombre.trim();

    if (!placaNormalizada || !nombreCompleto || !password || !confirmarPassword) {
      setErrorMsg("Completa todos los campos para crear tu cuenta.");
      return;
    }

    if (password.length < 3) {
      setErrorMsg("La contraseña debe tener al menos 3 caracteres.");
      return;
    }

    if (password !== confirmarPassword) {
      setErrorMsg("Las contraseñas no coinciden.");
      return;
    }

    setIsSubmitting(true);
    try {
      await registerAgente({
        placa: placaNormalizada,
        nombreCompleto,
        password,
      });
      setRegistroExitoso(true);
    } catch (error) {
      if (error instanceof ApiError) {
        // Try to get the server's message from the response body
        const serverMsg =
          error.body && typeof error.body === "object" && "message" in (error.body as object)
            ? (error.body as { message: string }).message
            : null;
        if (error.status === 409) {
          setErrorMsg("Ya existe un agente registrado con esa placa.");
        } else if (error.status === 404) {
          setErrorMsg("No se encontró el servicio de registro. Contacta al administrador.");
        } else if (error.status >= 500) {
          setErrorMsg(serverMsg || "Ocurrió un error en el servidor. Inténtalo más tarde.");
        } else {
          setErrorMsg(serverMsg || "No se pudo completar el registro. Revisa los datos e inténtalo de nuevo.");
        }
      } else {
        setErrorMsg("No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.");
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const limpiarMensajes = () => {
    setErrorMsg(null);
    setRegistroExitoso(false);
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
            Captura tus datos para crear una cuenta de agente.
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
                  limpiarMensajes();
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
                  limpiarMensajes();
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
                placeholder="Mínimo 3 caracteres"
                placeholderTextColor={colors.textMuted}
                value={password}
                onChangeText={(value) => {
                  setPassword(value);
                  limpiarMensajes();
                }}
                secureTextEntry={!showPassword}
                autoCapitalize="none"
              />
              <TouchableOpacity
                onPress={() => setShowPassword(!showPassword)}
                style={styles.passwordToggle}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                accessibilityLabel={showPassword ? "Ocultar contraseña" : "Ver contraseña"}
              >
                <Ionicons
                  name={showPassword ? "eye-off-outline" : "eye-outline"}
                  size={19}
                  color={colors.textMuted}
                />
              </TouchableOpacity>
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
                placeholder="Repite la contraseña"
                placeholderTextColor={colors.textMuted}
                value={confirmarPassword}
                onChangeText={(value) => {
                  setConfirmarPassword(value);
                  limpiarMensajes();
                }}
                secureTextEntry={!showConfirmarPassword}
                autoCapitalize="none"
              />
              <TouchableOpacity
                onPress={() => setShowConfirmarPassword(!showConfirmarPassword)}
                style={styles.passwordToggle}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                accessibilityLabel={showConfirmarPassword ? "Ocultar contraseña" : "Ver contraseña"}
              >
                <Ionicons
                  name={showConfirmarPassword ? "eye-off-outline" : "eye-outline"}
                  size={19}
                  color={colors.textMuted}
                />
              </TouchableOpacity>
            </View>

            {(errorMsg || registroExitoso) && (
              <View
                accessibilityRole="alert"
                style={[
                  styles.notice,
                  {
                    backgroundColor: registroExitoso ? colors.successBg : colors.dangerBg,
                    borderColor: registroExitoso ? colors.success : colors.danger,
                  },
                ]}
              >
                <Ionicons
                  name={registroExitoso ? "checkmark-circle-outline" : "alert-circle-outline"}
                  size={20}
                  color={registroExitoso ? colors.success : colors.danger}
                />
                <Text style={[styles.noticeText, { color: colors.text }]}>
                  {registroExitoso
                    ? "Cuenta creada correctamente. Ya puedes iniciar sesión."
                    : errorMsg}
                </Text>
              </View>
            )}

            {registroExitoso ? (
              <TouchableOpacity
                style={[styles.createButton, { backgroundColor: colors.primary }]}
                onPress={() => router.replace("/login")}
                activeOpacity={0.82}
              >
                <Ionicons name="log-in-outline" size={20} color="#ffffff" />
                <Text style={styles.createButtonText}>Ir a iniciar sesión</Text>
              </TouchableOpacity>
            ) : (
              <TouchableOpacity
                style={[
                  styles.createButton,
                  { backgroundColor: colors.primary },
                  isSubmitting && styles.buttonDisabled,
                ]}
                onPress={handleRegister}
                disabled={isSubmitting}
                activeOpacity={0.82}
              >
                {isSubmitting ? (
                  <>
                    <ActivityIndicator color="#ffffff" />
                    <Text style={styles.createButtonText}>Registrando...</Text>
                  </>
                ) : (
                  <>
                    <Ionicons name="person-add-outline" size={20} color="#ffffff" />
                    <Text style={styles.createButtonText}>Crear cuenta</Text>
                  </>
                )}
              </TouchableOpacity>
            )}

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
  buttonDisabled: { opacity: 0.7 },
  createButtonText: { color: "#ffffff", fontSize: 14, fontWeight: "800" },
  passwordToggle: { padding: 4 },
});
