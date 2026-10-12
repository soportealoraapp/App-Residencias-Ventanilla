import React from "react";
import { Modal, StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { useTheme } from "../contexts/ThemeContext";

interface LogoutConfirmationModalProps {
  visible: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}

export function LogoutConfirmationModal({
  visible,
  onCancel,
  onConfirm,
}: LogoutConfirmationModalProps) {
  const { colors } = useTheme();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onCancel}
    >
      <View style={styles.overlay}>
        <View style={[styles.dialog, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.title, { color: colors.text }]}>¿Cerrar sesión?</Text>
          <Text style={[styles.message, { color: colors.textSecondary }]}>
            ¿Estás seguro de que deseas cerrar tu sesión?
          </Text>
          <View style={styles.actions}>
            <TouchableOpacity
              style={[styles.button, { backgroundColor: colors.surfaceElevated }]}
              onPress={onCancel}
              activeOpacity={0.75}
            >
              <Text style={[styles.buttonText, { color: colors.text }]}>Cancelar</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.button, { backgroundColor: colors.danger }]}
              onPress={onConfirm}
              activeOpacity={0.75}
            >
              <Text style={[styles.buttonText, { color: "#ffffff" }]}>Cerrar sesión</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, justifyContent: "center", padding: 24, backgroundColor: "rgba(0,0,0,0.65)" },
  dialog: { width: "100%", maxWidth: 420, alignSelf: "center", borderWidth: 1, borderRadius: 18, padding: 22 },
  title: { fontSize: 18, fontWeight: "900", marginBottom: 10 },
  message: { fontSize: 13, lineHeight: 19, marginBottom: 20 },
  actions: { flexDirection: "row", justifyContent: "flex-end", gap: 10 },
  button: { minHeight: 42, alignItems: "center", justifyContent: "center", paddingHorizontal: 16, paddingVertical: 10, borderRadius: 10 },
  buttonText: { fontSize: 13, fontWeight: "700" },
});
