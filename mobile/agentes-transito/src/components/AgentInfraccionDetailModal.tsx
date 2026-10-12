import React, { useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  Image,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useTheme } from "../contexts/ThemeContext";
import { Infraccion } from "../types/infraccion";

interface AgentInfraccionDetailModalProps {
  infraccion: Infraccion | null;
  valorUma: number;
  onClose: () => void;
  onDiscard: (id: string) => Promise<void>;
}

export function AgentInfraccionDetailModal({
  infraccion,
  valorUma,
  onClose,
  onDiscard,
}: AgentInfraccionDetailModalProps) {
  const { colors } = useTheme();
  const [fotoZoom, setFotoZoom] = useState<string | null>(null);
  const [confirmacionVisible, setConfirmacionVisible] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const deletionInProgress = useRef(false);

  useEffect(() => {
    setFotoZoom(null);
    setConfirmacionVisible(false);
    setIsDeleting(false);
    setDeleteError(null);
  }, [infraccion?.id]);

  const cerrarDetalle = () => {
    if (deletionInProgress.current) return;
    setFotoZoom(null);
    setConfirmacionVisible(false);
    setDeleteError(null);
    onClose();
  };

  const cancelarDescarte = () => {
    if (deletionInProgress.current) return;
    setConfirmacionVisible(false);
    setDeleteError(null);
  };

  const confirmarDescarte = async () => {
    if (!infraccion || infraccion.estado !== "pendiente" || deletionInProgress.current) return;

    deletionInProgress.current = true;
    setIsDeleting(true);
    setDeleteError(null);
    try {
      await onDiscard(infraccion.id);
      deletionInProgress.current = false;
      setIsDeleting(false);
      setConfirmacionVisible(false);
      setFotoZoom(null);
      onClose();
    } catch (error) {
      deletionInProgress.current = false;
      setIsDeleting(false);
      setDeleteError(
        error instanceof Error && error.message
          ? error.message
          : "No se pudo descartar la boleta. Inténtalo de nuevo."
      );
    }
  };

  const estado = infraccion
    ? {
        pendiente: {
          label: "Guardada localmente · Pendiente de sincronizar a Supabase",
          icon: "cloud-offline" as const,
          color: colors.warning,
          backgroundColor: colors.warningBg,
        },
        sincronizada: {
          label: "Sincronizada con el Panel Central de Uriangato",
          icon: "cloud-done" as const,
          color: colors.success,
          backgroundColor: colors.successBg,
        },
        error_sincronizacion: {
          label: "Error de sincronización · Sin respaldo confirmado",
          icon: "alert-circle" as const,
          color: colors.danger,
          backgroundColor: colors.dangerBg,
        },
      }[infraccion.estado]
    : null;

  return (
    <>
      <Modal
        visible={!!infraccion}
        animationType="slide"
        transparent
        onRequestClose={cerrarDetalle}
      >
        <View style={styles.modalOverlay}>
          {infraccion && estado && (
            <View style={[styles.modalContentCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={[styles.modalTopBar, { borderBottomColor: colors.border }]}>
                <View style={styles.modalTitleArea}>
                  <Text style={[styles.modalSubtitle, { color: colors.textMuted }]}>DETALLE OFICIAL DE BOLETA</Text>
                  <Text style={[styles.modalTitle, { color: colors.text }]}>{infraccion.folio}</Text>
                </View>
                <TouchableOpacity
                  style={[styles.closeModalBtn, { backgroundColor: colors.surfaceElevated }]}
                  onPress={cerrarDetalle}
                  accessibilityLabel="Cerrar detalle"
                >
                  <Ionicons name="close" size={22} color={colors.text} />
                </TouchableOpacity>
              </View>

              <ScrollView style={styles.modalScrollBody} showsVerticalScrollIndicator={false}>
                <View
                  style={[
                    styles.modalSyncBanner,
                    { backgroundColor: estado.backgroundColor, borderColor: estado.color },
                  ]}
                >
                  <Ionicons name={estado.icon} size={20} color={estado.color} />
                  <Text style={[styles.modalSyncBannerText, { color: estado.color }]}>
                    {estado.label}
                  </Text>
                </View>

                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>1. DATOS DE EMISIÓN</Text>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Fecha y Hora:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccion.generales.fecha} a las {infraccion.generales.hora} hrs
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Agente Asignado:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccion.agente.nombre} ({infraccion.agente.placa})
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Lugar:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccion.generales.lugar}
                    </Text>
                  </View>
                  {infraccion.generales.coordenadas && (
                    <View style={styles.modalDetailRow}>
                      <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>GPS Satelital:</Text>
                      <Text style={[styles.modalDetailVal, { color: colors.primary }]}>
                        {infraccion.generales.coordenadas.latitud.toFixed(5)},{" "}
                        {infraccion.generales.coordenadas.longitud.toFixed(5)}
                      </Text>
                    </View>
                  )}
                </View>

                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>2. VEHÍCULO E INFRACTOR</Text>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Placas:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text, fontWeight: "900" }]}>
                      {infraccion.vehiculo.sinPlacas ? "SIN PLACAS" : infraccion.vehiculo.placas}
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Vehículo:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccion.vehiculo.marca} {infraccion.vehiculo.lineaModelo} ({infraccion.vehiculo.color})
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Conductor:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccion.infractor.conductorAusente
                        ? "Conductor Ausente en el Sitio"
                        : infraccion.infractor.nombre}
                    </Text>
                  </View>
                  {infraccion.infractor.numeroLicencia && (
                    <View style={styles.modalDetailRow}>
                      <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>No. Licencia:</Text>
                      <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                        {infraccion.infractor.numeroLicencia}
                      </Text>
                    </View>
                  )}
                </View>

                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>3. SANCIÓN Y REGLAMENTO</Text>
                  {(infraccion.faltas ?? []).map((falta, index) => (
                    <View key={falta.id} style={styles.offenseItem}>
                      <View style={styles.modalOffenseLegal}>
                        <View style={[styles.lawPill, { backgroundColor: colors.dangerBg }]}>
                          <Text style={[styles.lawPillText, { color: colors.danger }]}>
                            {falta.fundamentoLegal}
                          </Text>
                        </View>
                        <Text style={[styles.categoryPillText, { color: colors.textMuted }]}>
                          {falta.categoria}
                        </Text>
                      </View>
                      <Text style={[styles.modalOffenseDesc, { color: colors.text }]}>
                        {falta.descripcion}
                      </Text>
                      {index < (infraccion.faltas?.length ?? 1) - 1 && (
                        <View style={[styles.offenseDivider, { backgroundColor: colors.border }]} />
                      )}
                    </View>
                  ))}
                  <View style={[styles.modalUmaBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                    <Text style={[styles.modalUmaText, { color: colors.primary }]}>
                      Sanción Total: {(infraccion.faltas ?? []).reduce((sum, falta) => sum + Number(falta.montoMinUma), 0)} a{" "}
                      {(infraccion.faltas ?? []).reduce((sum, falta) => sum + Number(falta.montoMaxUma), 0)} UMA
                    </Text>
                    <Text style={[styles.modalPesosText, { color: colors.success }]}>
                      ${((infraccion.faltas ?? []).reduce((sum, falta) => sum + Number(falta.montoMinUma), 0) * valorUma).toLocaleString("es-MX", { maximumFractionDigits: 0 })}{" "}
                      a ${((infraccion.faltas ?? []).reduce((sum, falta) => sum + Number(falta.montoMaxUma), 0) * valorUma).toLocaleString("es-MX", { maximumFractionDigits: 0})} MXN
                    </Text>
                  </View>
                  {infraccion.hechos ? (
                    <View style={[styles.modalHechosBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                      <Text style={[styles.modalHechosTitle, { color: colors.textMuted }]}>OBSERVACIONES:</Text>
                      <Text style={[styles.modalHechosText, { color: colors.text }]}>{infraccion.hechos}</Text>
                    </View>
                  ) : null}
                </View>

                {infraccion.evidencias && (
                  <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                    <Text style={[styles.modalSectionTitle, { color: colors.text }]}>4. EVIDENCIAS FOTOGRÁFICAS</Text>
                    <View style={styles.modalPhotosGrid}>
                      {infraccion.evidencias.fotoPlaca && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccion.evidencias.fotoPlaca)}
                        >
                          <Image source={{ uri: infraccion.evidencias.fotoPlaca }} style={styles.modalPhotoImage} />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Placa</Text>
                        </TouchableOpacity>
                      )}
                      {infraccion.evidencias.fotoContexto && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccion.evidencias.fotoContexto)}
                        >
                          <Image source={{ uri: infraccion.evidencias.fotoContexto }} style={styles.modalPhotoImage} />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Contexto</Text>
                        </TouchableOpacity>
                      )}
                      {infraccion.evidencias.fotoDocumento && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccion.evidencias.fotoDocumento)}
                        >
                          <Image source={{ uri: infraccion.evidencias.fotoDocumento }} style={styles.modalPhotoImage} />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Documento</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                )}

                <View style={styles.modalActionsRow}>
                  {infraccion.estado === "pendiente" && (
                    <TouchableOpacity
                      style={[styles.modalDeleteBtn, { backgroundColor: colors.dangerBg, borderColor: colors.danger }]}
                      onPress={() => {
                        setDeleteError(null);
                        setConfirmacionVisible(true);
                      }}
                      disabled={isDeleting}
                    >
                      <Ionicons name="trash-outline" size={16} color={colors.danger} />
                      <Text style={[styles.modalDeleteBtnText, { color: colors.danger }]}>Descartar Boleta</Text>
                    </TouchableOpacity>
                  )}
                  <TouchableOpacity
                    style={[styles.modalCloseBottomBtn, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
                    onPress={cerrarDetalle}
                    disabled={isDeleting}
                  >
                    <Text style={[styles.modalCloseBottomBtnText, { color: colors.text }]}>Cerrar</Text>
                  </TouchableOpacity>
                </View>
              </ScrollView>
            </View>
          )}

          {infraccion && confirmacionVisible && (
            <View style={styles.confirmationOverlay}>
              <View style={[styles.confirmationDialog, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[styles.confirmationTitle, { color: colors.text }]}>Confirmar eliminación</Text>
                <Text style={[styles.confirmationMessage, { color: colors.textSecondary }]}>
                  ¿Desea descartar la boleta {infraccion.folio}? Esta acción no se puede deshacer.
                </Text>
                {deleteError && (
                  <Text accessibilityRole="alert" style={[styles.confirmationError, { color: colors.danger }]}>
                    {deleteError}
                  </Text>
                )}
                <View style={styles.confirmationActions}>
                  <TouchableOpacity
                    style={[styles.confirmationButton, { backgroundColor: colors.surfaceElevated }]}
                    onPress={cancelarDescarte}
                    disabled={isDeleting}
                  >
                    <Text style={[styles.confirmationButtonText, { color: colors.text }]}>Cancelar</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[styles.confirmationButton, { backgroundColor: colors.danger }]}
                    onPress={() => void confirmarDescarte()}
                    disabled={isDeleting}
                  >
                    {isDeleting ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={[styles.confirmationButtonText, styles.confirmationDestructiveText]}>
                        Eliminar
                      </Text>
                    )}
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          )}
        </View>
      </Modal>

      <Modal
        visible={!!fotoZoom && !!infraccion}
        animationType="fade"
        transparent
        onRequestClose={() => setFotoZoom(null)}
      >
        <View style={styles.zoomOverlay}>
          <TouchableOpacity
            style={styles.zoomCloseBtn}
            onPress={() => setFotoZoom(null)}
            accessibilityLabel="Cerrar fotografía ampliada"
          >
            <Ionicons name="close-circle" size={38} color="#ffffff" />
          </TouchableOpacity>
          {fotoZoom && <Image source={{ uri: fotoZoom }} style={styles.zoomImage} resizeMode="contain" />}
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.75)",
    justifyContent: "flex-end",
  },
  modalContentCard: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "90%",
    borderWidth: 1,
    paddingTop: 16,
  },
  modalTopBar: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingBottom: 12,
    borderBottomWidth: 1,
  },
  modalTitleArea: { flex: 1, minWidth: 0 },
  modalSubtitle: { fontSize: 10, fontWeight: "800", letterSpacing: 0.8 },
  modalTitle: { fontSize: 18, fontWeight: "900", marginTop: 2 },
  closeModalBtn: { width: 36, height: 36, borderRadius: 18, alignItems: "center", justifyContent: "center" },
  modalScrollBody: { paddingHorizontal: 20, paddingTop: 14, paddingBottom: 40 },
  modalSyncBanner: { flexDirection: "row", alignItems: "center", gap: 8, padding: 12, borderRadius: 12, borderWidth: 1, marginBottom: 14 },
  modalSyncBannerText: { fontSize: 12, fontWeight: "700", flex: 1 },
  modalSectionCard: { padding: 14, borderRadius: 14, borderWidth: 1, marginBottom: 14, gap: 8 },
  modalSectionTitle: { fontSize: 11, fontWeight: "800", letterSpacing: 0.8, marginBottom: 4 },
  modalDetailRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "flex-start", gap: 12 },
  modalDetailKey: { fontSize: 12, width: 120 },
  modalDetailVal: { fontSize: 12, fontWeight: "600", flex: 1, textAlign: "right" },
  modalOffenseLegal: { flexDirection: "row", alignItems: "center", gap: 8 },
  lawPill: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
  lawPillText: { fontSize: 10, fontWeight: "900" },
  categoryPillText: { fontSize: 10, fontWeight: "700" },
  offenseItem: { marginBottom: 12 },
  offenseDivider: { height: 1, marginTop: 8 },
  modalOffenseDesc: { fontSize: 13, lineHeight: 18, fontWeight: "600" },
  modalUmaBox: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", padding: 10, borderRadius: 10, borderWidth: 1, marginTop: 4 },
  modalUmaText: { fontSize: 12, fontWeight: "800" },
  modalPesosText: { fontSize: 12, fontWeight: "800" },
  modalHechosBox: { padding: 10, borderRadius: 10, borderWidth: 1, marginTop: 4 },
  modalHechosTitle: { fontSize: 9, fontWeight: "800", letterSpacing: 0.5, marginBottom: 2 },
  modalHechosText: { fontSize: 11, lineHeight: 16 },
  modalPhotosGrid: { flexDirection: "row", gap: 10, marginTop: 6 },
  modalPhotoItem: { flex: 1, alignItems: "center" },
  modalPhotoImage: { width: "100%", height: 90, borderRadius: 10 },
  modalPhotoTag: { fontSize: 10, fontWeight: "700", marginTop: 4 },
  modalActionsRow: { flexDirection: "row", gap: 12, marginTop: 10, marginBottom: 30 },
  modalDeleteBtn: { flex: 1, flexDirection: "row", alignItems: "center", justifyContent: "center", padding: 12, borderRadius: 12, borderWidth: 1, gap: 6 },
  modalDeleteBtnText: { fontSize: 12, fontWeight: "800" },
  modalCloseBottomBtn: { flex: 1, alignItems: "center", justifyContent: "center", padding: 12, borderRadius: 12, borderWidth: 1 },
  modalCloseBottomBtnText: { fontSize: 12, fontWeight: "800" },
  confirmationOverlay: { ...StyleSheet.absoluteFill, alignItems: "center", justifyContent: "center", padding: 24, backgroundColor: "rgba(0,0,0,0.65)" },
  confirmationDialog: { width: "100%", maxWidth: 420, borderWidth: 1, borderRadius: 18, padding: 22 },
  confirmationTitle: { fontSize: 18, fontWeight: "900", marginBottom: 10 },
  confirmationMessage: { fontSize: 13, lineHeight: 19, marginBottom: 20 },
  confirmationError: { fontSize: 13, lineHeight: 19, marginTop: -10, marginBottom: 16 },
  confirmationActions: { flexDirection: "row", justifyContent: "flex-end", gap: 10 },
  confirmationButton: { minHeight: 42, minWidth: 88, alignItems: "center", justifyContent: "center", paddingHorizontal: 16, paddingVertical: 10, borderRadius: 10 },
  confirmationButtonText: { fontSize: 13, fontWeight: "700" },
  confirmationDestructiveText: { color: "#ffffff" },
  zoomOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.95)", justifyContent: "center", alignItems: "center" },
  zoomCloseBtn: { position: "absolute", top: 50, right: 20, zIndex: 10 },
  zoomImage: { width: "92%", height: "80%" },
});
