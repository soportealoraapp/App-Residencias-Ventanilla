import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  FlatList,
  Alert,
  Modal,
  Image,
  ScrollView,
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { useInfracciones } from "../src/contexts/InfraccionesContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { Infraccion } from "../src/types/infraccion";
import { Ionicons } from "@expo/vector-icons";

export default function DashboardScreen() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const {
    infracciones,
    pendientes,
    sincronizadas,
    totalPendientes,
    totalSincronizadas,
    isSyncing,
    ultimaSincronizacion,
    valorUma,
    sincronizar,
    eliminar,
  } = useInfracciones();
  const { colors, isDark } = useTheme();

  const [filtro, setFiltro] = useState<"todas" | "pendientes" | "sincronizadas">("todas");
  const [infraccionSeleccionada, setInfraccionSeleccionada] = useState<Infraccion | null>(null);
  const [fotoZoom, setFotoZoom] = useState<string | null>(null);

  const infraccionesFiltradas =
    filtro === "pendientes"
      ? pendientes
      : filtro === "sincronizadas"
      ? sincronizadas
      : infracciones;

  const handleSincronizar = async () => {
    try {
      const res = await sincronizar();
      if (res.exito) {
        Alert.alert(
          "Sincronización al Día",
          res.totalSincronizadas > 0
            ? `Se sincronizaron ${res.totalSincronizadas} boleta(s) exitosamente con el servidor central de Uriangato.`
            : "No hay boletas pendientes de sincronizar. Todo el registro local está al día."
        );
      } else {
        Alert.alert(
          "Aviso de Red",
          res.errores?.[0] || "No se pudo conectar con el servidor. Las boletas permanecen seguras en el dispositivo."
        );
      }
    } catch {
      Alert.alert(
        "Aviso de Red",
        "Dispositivo sin conexión a internet. Las boletas permanecen guardadas localmente y se enviarán al reconectar."
      );
    }
  };

  const handleEliminar = (id: string, folio: string) => {
    Alert.alert(
      "Confirmar Eliminación",
      `¿Desea descartar la boleta ${folio}? Esta acción no se puede deshacer.`,
      [
        { text: "Cancelar", style: "cancel" },
        {
          text: "Eliminar",
          style: "destructive",
          onPress: async () => {
            await eliminar(id);
            setInfraccionSeleccionada(null);
          },
        },
      ]
    );
  };

  const formatearFechaHora = (fechaIso?: string) => {
    if (!fechaIso) return "--:--";
    try {
      const d = new Date(fechaIso);
      return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    } catch {
      return fechaIso;
    }
  };

  const renderTicketCard = ({ item }: { item: Infraccion }) => {
    const esPendiente = item.estado === "pendiente";
    const minPesos = Number(item.falta.montoMinUma) * valorUma;
    const maxPesos = Number(item.falta.montoMaxUma) * valorUma;

    return (
      <View
        style={[
          styles.ticketCard,
          {
            backgroundColor: colors.surface,
            borderColor: esPendiente ? (isDark ? "rgba(245, 158, 11, 0.4)" : "#fed7aa") : colors.border,
          },
        ]}
      >
        {/* Cabecera de la Tarjeta */}
        <View style={styles.ticketCardHeader}>
          <View style={styles.folioBadgeRow}>
            <View style={[styles.folioBadge, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <Text style={[styles.ticketFolioText, { color: colors.text }]}>{item.folio}</Text>
            </View>
            <View
              style={[
                styles.syncBadge,
                {
                  backgroundColor: esPendiente ? colors.warningBg : colors.successBg,
                  borderColor: esPendiente ? colors.warning : colors.success,
                },
              ]}
            >
              <Ionicons
                name={esPendiente ? "cloud-offline" : "cloud-done"}
                size={12}
                color={esPendiente ? colors.warning : colors.success}
              />
              <Text
                style={[
                  styles.syncBadgeText,
                  { color: esPendiente ? colors.warning : colors.success },
                ]}
              >
                {esPendiente ? "PENDIENTE" : "SINCRONIZADA"}
              </Text>
            </View>
          </View>

          <View style={styles.timeTag}>
            <Ionicons name="time-outline" size={13} color={colors.textMuted} />
            <Text style={[styles.ticketDateText, { color: colors.textMuted }]}>
              {item.generales.fecha} {item.generales.hora}
            </Text>
          </View>
        </View>

        {/* Bloque Falta / Infracción */}
        <View style={[styles.offenseBlock, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
          <View style={styles.offenseTagRow}>
            <View style={[styles.lawPill, { backgroundColor: colors.dangerBg }]}>
              <Text style={[styles.lawPillText, { color: colors.danger }]}>
                {item.falta.fundamentoLegal}
              </Text>
            </View>
            <Text style={[styles.categoryPillText, { color: colors.textMuted }]}>
              {item.falta.categoria}
            </Text>
          </View>
          <Text style={[styles.offenseDescText, { color: colors.text }]} numberOfLines={2}>
            {item.falta.descripcion}
          </Text>
        </View>

        {/* Bloque Vehículo e Infractor */}
        <View style={styles.infoGridRow}>
          <View style={[styles.vehiclePlateBox, { backgroundColor: isDark ? "#1c1917" : "#f1f5f9", borderColor: colors.border }]}>
            <Text style={[styles.plateMiniState, { color: colors.primary }]}>GTO</Text>
            <Text style={[styles.plateMiniNumber, { color: colors.text }]}>
              {item.vehiculo.sinPlacas ? "SIN PLACAS" : item.vehiculo.placas}
            </Text>
          </View>

          <View style={styles.vehicleDetailsCol}>
            <Text style={[styles.vehicleModelText, { color: colors.text }]} numberOfLines={1}>
              {item.vehiculo.marca} {item.vehiculo.lineaModelo} · {item.vehiculo.color}
            </Text>
            <View style={styles.locationInlineRow}>
              <Ionicons name="location-outline" size={13} color={colors.textMuted} />
              <Text style={[styles.locationText, { color: colors.textSecondary }]} numberOfLines={1}>
                {item.generales.lugar}
              </Text>
            </View>
          </View>
        </View>

        {/* Miniaturas de Evidencias */}
        {item.evidencias && (
          <View style={styles.evidenceThumbsRow}>
            {item.evidencias.fotoPlaca && (
              <TouchableOpacity
                onPress={() => setFotoZoom(item.evidencias.fotoPlaca)}
                style={styles.thumbWrap}
              >
                <Image source={{ uri: item.evidencias.fotoPlaca }} style={styles.thumbImage} />
                <View style={styles.thumbLabelBadge}>
                  <Text style={styles.thumbLabelText}>Placa</Text>
                </View>
              </TouchableOpacity>
            )}
            {item.evidencias.fotoContexto && (
              <TouchableOpacity
                onPress={() => setFotoZoom(item.evidencias.fotoContexto)}
                style={styles.thumbWrap}
              >
                <Image source={{ uri: item.evidencias.fotoContexto }} style={styles.thumbImage} />
                <View style={styles.thumbLabelBadge}>
                  <Text style={styles.thumbLabelText}>Lugar</Text>
                </View>
              </TouchableOpacity>
            )}
            {item.evidencias.fotoDocumento && (
              <TouchableOpacity
                onPress={() => setFotoZoom(item.evidencias.fotoDocumento)}
                style={styles.thumbWrap}
              >
                <Image source={{ uri: item.evidencias.fotoDocumento }} style={styles.thumbImage} />
                <View style={styles.thumbLabelBadge}>
                  <Text style={styles.thumbLabelText}>Garantía</Text>
                </View>
              </TouchableOpacity>
            )}
          </View>
        )}

        {/* Pie de Tarjeta con Sanción y Botón de Detalle */}
        <View style={[styles.ticketCardFooter, { borderTopColor: colors.border }]}>
          <View style={styles.amountCol}>
            <Text style={[styles.amountLabel, { color: colors.textMuted }]}>SANCIÓN ESTIMADA</Text>
            <Text style={[styles.amountValue, { color: colors.success }]}>
              ${minPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} – $
              {maxPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} MXN
            </Text>
          </View>

          <TouchableOpacity
            style={[styles.detailActionBtn, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}
            onPress={() => setInfraccionSeleccionada(item)}
            activeOpacity={0.8}
          >
            <Text style={[styles.detailActionBtnText, { color: colors.primary }]}>Ver Boleta Completa</Text>
            <Ionicons name="chevron-forward" size={14} color={colors.primary} />
          </TouchableOpacity>
        </View>
      </View>
    );
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      {/* 1. Header Superior con Datos del Agente */}
      <View style={[styles.topHeader, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <View style={styles.officerProfileRow}>
          <View style={[styles.officerAvatar, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
            <Ionicons name="shield-checkmark" size={20} color={colors.primary} />
          </View>
          <View style={styles.officerTextWrap}>
            <View style={styles.officerBadgeLine}>
              <Text style={[styles.officerBadge, { color: colors.primary }]}>{user?.placa || "AGT-204"}</Text>
              <View style={styles.onDutyPill}>
                <View style={styles.greenDot} />
                <Text style={styles.onDutyText}>EN TURNO</Text>
              </View>
            </View>
            <Text style={[styles.officerName, { color: colors.text }]} numberOfLines={1}>
              {user?.nombre || "Oficial Carlos Mendoza"}
            </Text>
          </View>
        </View>

        <View style={styles.headerRightActions}>
          <ThemeToggle compact={true} />
          <TouchableOpacity
            style={[styles.logoutBtn, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
            onPress={logout}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
            accessibilityLabel="Cerrar sesión"
          >
            <Ionicons name="log-out-outline" size={18} color={colors.danger} />
          </TouchableOpacity>
        </View>
      </View>

      <FlatList
        data={infraccionesFiltradas}
        keyExtractor={(item) => item.id}
        renderItem={renderTicketCard}
        contentContainerStyle={styles.listContent}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={
          <>
            {/* 2. Banner Institucional Uriangato */}
            <View style={[styles.institutionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.institutionHeader}>
                <View style={styles.institutionTitleWrap}>
                  <Text style={[styles.institutionGov, { color: colors.textMuted }]}>
                    GOBIERNO MUNICIPAL DE URIANGATO
                  </Text>
                  <Text style={[styles.institutionDept, { color: colors.text }]}>
                    Dirección de Tránsito y Movilidad
                  </Text>
                </View>
                <View style={[styles.umaBadge, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.umaBadgeLabel, { color: colors.textMuted }]}>UMA 2026</Text>
                  <Text style={[styles.umaBadgeValue, { color: colors.primary }]}>${valorUma.toFixed(2)} MXN</Text>
                </View>
              </View>
            </View>

            {/* 3. Botón Hero: Levantar Nueva Infracción */}
            <TouchableOpacity
              style={[styles.heroButton, { backgroundColor: colors.primary }]}
              onPress={() => router.push("/nueva-infraccion")}
              activeOpacity={0.88}
            >
              <View style={styles.heroButtonLeft}>
                <View style={styles.heroIconCircle}>
                  <Ionicons name="add" size={26} color={colors.primary} />
                </View>
                <View>
                  <Text style={styles.heroButtonTitle}>LEVANTAR NUEVA INFRACCIÓN</Text>
                  <Text style={styles.heroButtonSubtitle}>
                    Folio automático · GPS · 3 fotos obligatorias
                  </Text>
                </View>
              </View>
              <Ionicons name="arrow-forward-circle" size={28} color="#ffffff" />
            </TouchableOpacity>

            {/* 4. Tarjetas de Métricas en 3 Columnas */}
            <View style={styles.statsCardsRow}>
              <View style={[styles.statCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[styles.statNumber, { color: colors.text }]}>{infracciones.length}</Text>
                <Text style={[styles.statLabel, { color: colors.textMuted }]}>Emitidas Hoy</Text>
              </View>

              <View
                style={[
                  styles.statCard,
                  {
                    backgroundColor: colors.surface,
                    borderColor: totalPendientes > 0 ? (isDark ? "rgba(245, 158, 11, 0.5)" : "#fcd34d") : colors.border,
                  },
                ]}
              >
                <View style={styles.statIconBadge}>
                  <Text style={[styles.statNumber, { color: totalPendientes > 0 ? colors.warning : colors.text }]}>
                    {totalPendientes}
                  </Text>
                  {totalPendientes > 0 && (
                    <Ionicons name="cloud-offline" size={14} color={colors.warning} style={{ marginLeft: 4 }} />
                  )}
                </View>
                <Text style={[styles.statLabel, { color: totalPendientes > 0 ? colors.warning : colors.textMuted }]}>
                  Offline Pend.
                </Text>
              </View>

              <View style={[styles.statCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <View style={styles.statIconBadge}>
                  <Text style={[styles.statNumber, { color: colors.success }]}>{totalSincronizadas}</Text>
                  <Ionicons name="cloud-done" size={14} color={colors.success} style={{ marginLeft: 4 }} />
                </View>
                <Text style={[styles.statLabel, { color: colors.textMuted }]}>Sincronizadas</Text>
              </View>
            </View>

            {/* 5. Panel de Sincronización */}
            <View style={[styles.syncPanelCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.syncPanelLeft}>
                <View style={styles.syncStatusRow}>
                  <Ionicons
                    name={totalPendientes > 0 ? "sync-circle-outline" : "checkmark-circle"}
                    size={20}
                    color={totalPendientes > 0 ? colors.warning : colors.success}
                  />
                  <Text style={[styles.syncPanelTitle, { color: colors.text }]}>
                    {totalPendientes > 0
                      ? `${totalPendientes} boleta${totalPendientes > 1 ? "s" : ""} lista${totalPendientes > 1 ? "s" : ""} para enviar`
                      : "Todas las boletas respaldadas"}
                  </Text>
                </View>
                <Text style={[styles.syncPanelSubtitle, { color: colors.textSecondary }]}>
                  {ultimaSincronizacion
                    ? `Último envío: ${formatearFechaHora(ultimaSincronizacion)}`
                    : "Modo offline disponible en todo momento"}
                </Text>
              </View>

              <TouchableOpacity
                style={[
                  styles.syncActionBtn,
                  { backgroundColor: colors.primary },
                  isSyncing && styles.buttonDisabled,
                ]}
                onPress={handleSincronizar}
                disabled={isSyncing}
                activeOpacity={0.8}
              >
                {isSyncing ? (
                  <ActivityIndicator size="small" color="#ffffff" />
                ) : (
                  <>
                    <Ionicons name="cloud-upload" size={16} color="#ffffff" />
                    <Text style={styles.syncActionBtnText}>Sincronizar</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>

            {/* 6. Filtros de Pestañas */}
            <View style={styles.sectionHeaderRow}>
              <Text style={[styles.sectionTitle, { color: colors.text }]}>HISTORIAL DE BOLETAS</Text>
              <Text style={[styles.sectionCountText, { color: colors.textMuted }]}>
                {infraccionesFiltradas.length} boleta{infraccionesFiltradas.length !== 1 ? "s" : ""}
              </Text>
            </View>

            <View style={[styles.tabFilterBar, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <TouchableOpacity
                style={[
                  styles.tabFilterBtn,
                  filtro === "todas" && [styles.tabFilterBtnActive, { backgroundColor: colors.primary }],
                ]}
                onPress={() => setFiltro("todas")}
              >
                <Text
                  style={[
                    styles.tabFilterBtnText,
                    { color: filtro === "todas" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Todas ({infracciones.length})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tabFilterBtn,
                  filtro === "pendientes" && [styles.tabFilterBtnActive, { backgroundColor: colors.warning }],
                ]}
                onPress={() => setFiltro("pendientes")}
              >
                <Text
                  style={[
                    styles.tabFilterBtnText,
                    { color: filtro === "pendientes" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Pendientes ({totalPendientes})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tabFilterBtn,
                  filtro === "sincronizadas" && [styles.tabFilterBtnActive, { backgroundColor: colors.success }],
                ]}
                onPress={() => setFiltro("sincronizadas")}
              >
                <Text
                  style={[
                    styles.tabFilterBtnText,
                    { color: filtro === "sincronizadas" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Enviadas ({totalSincronizadas})
                </Text>
              </TouchableOpacity>
            </View>
          </>
        }
        ListEmptyComponent={
          <View style={[styles.emptyCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Ionicons name="document-text-outline" size={48} color={colors.textMuted} />
            <Text style={[styles.emptyCardTitle, { color: colors.text }]}>No hay boletas en este apartado</Text>
            <Text style={[styles.emptyCardDesc, { color: colors.textSecondary }]}>
              {filtro === "pendientes"
                ? "No existen boletas pendientes de sincronización."
                : "Presione 'Levantar Nueva Infracción' para emitir la primera boleta."}
            </Text>
          </View>
        }
      />

      {/* MODAL DETALLE COMPLETO DE INFRACCIÓN */}
      <Modal
        visible={!!infraccionSeleccionada}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setInfraccionSeleccionada(null)}
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContentCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.modalTopBar, { borderBottomColor: colors.border }]}>
              <View>
                <Text style={[styles.modalSubtitle, { color: colors.textMuted }]}>DETALLE OFICIAL DE BOLETA</Text>
                <Text style={[styles.modalTitle, { color: colors.text }]}>{infraccionSeleccionada?.folio}</Text>
              </View>
              <TouchableOpacity
                style={[styles.closeModalBtn, { backgroundColor: colors.surfaceElevated }]}
                onPress={() => setInfraccionSeleccionada(null)}
              >
                <Ionicons name="close" size={22} color={colors.text} />
              </TouchableOpacity>
            </View>

            {infraccionSeleccionada && (
              <ScrollView style={styles.modalScrollBody} showsVerticalScrollIndicator={false}>
                {/* Estado de Sincronización */}
                <View
                  style={[
                    styles.modalSyncBanner,
                    {
                      backgroundColor:
                        infraccionSeleccionada.estado === "pendiente" ? colors.warningBg : colors.successBg,
                      borderColor:
                        infraccionSeleccionada.estado === "pendiente" ? colors.warning : colors.success,
                    },
                  ]}
                >
                  <Ionicons
                    name={infraccionSeleccionada.estado === "pendiente" ? "cloud-offline" : "cloud-done"}
                    size={20}
                    color={infraccionSeleccionada.estado === "pendiente" ? colors.warning : colors.success}
                  />
                  <Text
                    style={[
                      styles.modalSyncBannerText,
                      {
                        color:
                          infraccionSeleccionada.estado === "pendiente" ? colors.warning : colors.success,
                      },
                    ]}
                  >
                    {infraccionSeleccionada.estado === "pendiente"
                      ? "Guardada localmente · Pendiente de sincronizar a Supabase"
                      : "Sincronizada con el Panel Central de Uriangato"}
                  </Text>
                </View>

                {/* Sección 1: Datos de Emisión y Agente */}
                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>1. DATOS DE EMISIÓN</Text>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Fecha y Hora:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.generales.fecha} a las {infraccionSeleccionada.generales.hora} hrs
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Agente Asignado:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.agente.nombre} ({infraccionSeleccionada.agente.placa})
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Lugar:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.generales.lugar}
                    </Text>
                  </View>
                  {infraccionSeleccionada.generales.coordenadas && (
                    <View style={styles.modalDetailRow}>
                      <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>GPS Satelital:</Text>
                      <Text style={[styles.modalDetailVal, { color: colors.primary }]}>
                        {infraccionSeleccionada.generales.coordenadas.latitud.toFixed(5)},{" "}
                        {infraccionSeleccionada.generales.coordenadas.longitud.toFixed(5)}
                      </Text>
                    </View>
                  )}
                </View>

                {/* Sección 2: Vehículo e Infractor */}
                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>2. VEHÍCULO E INFRACTOR</Text>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Placas:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text, fontWeight: "900" }]}>
                      {infraccionSeleccionada.vehiculo.sinPlacas
                        ? "SIN PLACAS"
                        : infraccionSeleccionada.vehiculo.placas}
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Vehículo:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.vehiculo.marca} {infraccionSeleccionada.vehiculo.lineaModelo} (
                      {infraccionSeleccionada.vehiculo.color})
                    </Text>
                  </View>
                  <View style={styles.modalDetailRow}>
                    <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>Conductor:</Text>
                    <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.infractor.conductorAusente
                        ? "Conductor Ausente en el Sitio"
                        : infraccionSeleccionada.infractor.nombre}
                    </Text>
                  </View>
                  {infraccionSeleccionada.infractor.numeroLicencia && (
                    <View style={styles.modalDetailRow}>
                      <Text style={[styles.modalDetailKey, { color: colors.textSecondary }]}>No. Licencia:</Text>
                      <Text style={[styles.modalDetailVal, { color: colors.text }]}>
                        {infraccionSeleccionada.infractor.numeroLicencia}
                      </Text>
                    </View>
                  )}
                </View>

                {/* Sección 3: Falta y Sanción */}
                <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.modalSectionTitle, { color: colors.text }]}>3. SANCIÓN Y REGLAMENTO</Text>
                  <View style={styles.modalOffenseLegal}>
                    <View style={[styles.lawPill, { backgroundColor: colors.dangerBg }]}>
                      <Text style={[styles.lawPillText, { color: colors.danger }]}>
                        {infraccionSeleccionada.falta.fundamentoLegal}
                      </Text>
                    </View>
                    <Text style={[styles.categoryPillText, { color: colors.textMuted }]}>
                      {infraccionSeleccionada.falta.categoria}
                    </Text>
                  </View>
                  <Text style={[styles.modalOffenseDesc, { color: colors.text }]}>
                    {infraccionSeleccionada.falta.descripcion}
                  </Text>
                  <View style={[styles.modalUmaBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                    <Text style={[styles.modalUmaText, { color: colors.primary }]}>
                      Sanción: {infraccionSeleccionada.falta.montoMinUma} a {infraccionSeleccionada.falta.montoMaxUma} UMA
                    </Text>
                    <Text style={[styles.modalPesosText, { color: colors.success }]}>
                      $
                      {(Number(infraccionSeleccionada.falta.montoMinUma) * valorUma).toLocaleString("es-MX", {
                        maximumFractionDigits: 0,
                      })}{" "}
                      a $
                      {(Number(infraccionSeleccionada.falta.montoMaxUma) * valorUma).toLocaleString("es-MX", {
                        maximumFractionDigits: 0,
                      })}{" "}
                      MXN
                    </Text>
                  </View>
                  {infraccionSeleccionada.hechos ? (
                    <View style={[styles.modalHechosBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                      <Text style={[styles.modalHechosTitle, { color: colors.textMuted }]}>OBSERVACIONES:</Text>
                      <Text style={[styles.modalHechosText, { color: colors.text }]}>
                        {infraccionSeleccionada.hechos}
                      </Text>
                    </View>
                  ) : null}
                </View>

                {/* Sección 4: Evidencias Fotográficas */}
                {infraccionSeleccionada.evidencias && (
                  <View style={[styles.modalSectionCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                    <Text style={[styles.modalSectionTitle, { color: colors.text }]}>4. EVIDENCIAS FOTOGRÁFICAS</Text>
                    <View style={styles.modalPhotosGrid}>
                      {infraccionSeleccionada.evidencias.fotoPlaca && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccionSeleccionada.evidencias.fotoPlaca)}
                        >
                          <Image
                            source={{ uri: infraccionSeleccionada.evidencias.fotoPlaca }}
                            style={styles.modalPhotoImage}
                          />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Placa</Text>
                        </TouchableOpacity>
                      )}
                      {infraccionSeleccionada.evidencias.fotoContexto && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccionSeleccionada.evidencias.fotoContexto)}
                        >
                          <Image
                            source={{ uri: infraccionSeleccionada.evidencias.fotoContexto }}
                            style={styles.modalPhotoImage}
                          />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Contexto</Text>
                        </TouchableOpacity>
                      )}
                      {infraccionSeleccionada.evidencias.fotoDocumento && (
                        <TouchableOpacity
                          style={styles.modalPhotoItem}
                          onPress={() => setFotoZoom(infraccionSeleccionada.evidencias.fotoDocumento)}
                        >
                          <Image
                            source={{ uri: infraccionSeleccionada.evidencias.fotoDocumento }}
                            style={styles.modalPhotoImage}
                          />
                          <Text style={[styles.modalPhotoTag, { color: colors.textSecondary }]}>Documento</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                  </View>
                )}

                {/* Acciones de la Boleta */}
                <View style={styles.modalActionsRow}>
                  {infraccionSeleccionada.estado === "pendiente" && (
                    <TouchableOpacity
                      style={[styles.modalDeleteBtn, { backgroundColor: colors.dangerBg, borderColor: colors.danger }]}
                      onPress={() => handleEliminar(infraccionSeleccionada.id, infraccionSeleccionada.folio)}
                    >
                      <Ionicons name="trash-outline" size={16} color={colors.danger} />
                      <Text style={[styles.modalDeleteBtnText, { color: colors.danger }]}>Descartar Boleta</Text>
                    </TouchableOpacity>
                  )}
                  <TouchableOpacity
                    style={[styles.modalCloseBottomBtn, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
                    onPress={() => setInfraccionSeleccionada(null)}
                  >
                    <Text style={[styles.modalCloseBottomBtnText, { color: colors.text }]}>Cerrar</Text>
                  </TouchableOpacity>
                </View>
              </ScrollView>
            )}
          </View>
        </View>
      </Modal>

      {/* MODAL ZOOM DE FOTO */}
      <Modal
        visible={!!fotoZoom}
        animationType="fade"
        transparent={true}
        onRequestClose={() => setFotoZoom(null)}
      >
        <View style={styles.zoomOverlay}>
          <TouchableOpacity style={styles.zoomCloseBtn} onPress={() => setFotoZoom(null)}>
            <Ionicons name="close-circle" size={38} color="#ffffff" />
          </TouchableOpacity>
          {fotoZoom && <Image source={{ uri: fotoZoom }} style={styles.zoomImage} resizeMode="contain" />}
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  topHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 14,
    borderBottomWidth: 1,
  },
  officerProfileRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    flex: 1,
  },
  officerAvatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  officerTextWrap: {
    flex: 1,
  },
  officerBadgeLine: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  officerBadge: {
    fontSize: 12,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  onDutyPill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "rgba(16, 185, 129, 0.15)",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  greenDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: "#10b981",
  },
  onDutyText: {
    fontSize: 9,
    fontWeight: "800",
    color: "#10b981",
  },
  officerName: {
    fontSize: 14,
    fontWeight: "700",
    marginTop: 1,
  },
  headerRightActions: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  logoutBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  listContent: {
    padding: 16,
    paddingBottom: 60,
  },

  // Institución Card
  institutionCard: {
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    marginBottom: 16,
  },
  institutionHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  institutionTitleWrap: {
    flex: 1,
  },
  institutionGov: {
    fontSize: 9,
    fontWeight: "800",
    letterSpacing: 1,
    marginBottom: 2,
  },
  institutionDept: {
    fontSize: 15,
    fontWeight: "800",
  },
  umaBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    borderWidth: 1,
    alignItems: "flex-end",
  },
  umaBadgeLabel: {
    fontSize: 9,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  umaBadgeValue: {
    fontSize: 12,
    fontWeight: "900",
  },

  // Hero Button
  heroButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 18,
    borderRadius: 20,
    marginBottom: 20,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
    elevation: 4,
  },
  heroButtonLeft: {
    flexDirection: "row",
    alignItems: "center",
    gap: 14,
    flex: 1,
  },
  heroIconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "#ffffff",
    alignItems: "center",
    justifyContent: "center",
  },
  heroButtonTitle: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  heroButtonSubtitle: {
    color: "rgba(255,255,255,0.85)",
    fontSize: 11,
    marginTop: 2,
  },

  // Stats Grid
  statsCardsRow: {
    flexDirection: "row",
    gap: 10,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: "center",
  },
  statIconBadge: {
    flexDirection: "row",
    alignItems: "center",
  },
  statNumber: {
    fontSize: 22,
    fontWeight: "900",
  },
  statLabel: {
    fontSize: 10,
    fontWeight: "700",
    marginTop: 4,
    textAlign: "center",
  },

  // Sync Panel
  syncPanelCard: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 20,
  },
  syncPanelLeft: {
    flex: 1,
    marginRight: 12,
  },
  syncStatusRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 2,
  },
  syncPanelTitle: {
    fontSize: 13,
    fontWeight: "800",
  },
  syncPanelSubtitle: {
    fontSize: 11,
  },
  syncActionBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
  },
  syncActionBtnText: {
    color: "#ffffff",
    fontSize: 12,
    fontWeight: "800",
  },
  buttonDisabled: {
    opacity: 0.6,
  },

  // Tab Filter Bar
  sectionHeaderRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: "900",
    letterSpacing: 0.8,
  },
  sectionCountText: {
    fontSize: 12,
    fontWeight: "600",
  },
  tabFilterBar: {
    flexDirection: "row",
    borderRadius: 14,
    padding: 4,
    borderWidth: 1,
    marginBottom: 18,
    gap: 4,
  },
  tabFilterBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 10,
    alignItems: "center",
  },
  tabFilterBtnActive: {},
  tabFilterBtnText: {
    fontSize: 11,
    fontWeight: "800",
  },

  // Tarjeta de Infracción
  ticketCard: {
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    marginBottom: 16,
  },
  ticketCardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  folioBadgeRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  folioBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    borderWidth: 1,
  },
  ticketFolioText: {
    fontSize: 13,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  syncBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 6,
    borderWidth: 1,
  },
  syncBadgeText: {
    fontSize: 9,
    fontWeight: "800",
  },
  timeTag: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  ticketDateText: {
    fontSize: 11,
    fontWeight: "500",
  },

  // Offense Block
  offenseBlock: {
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 12,
  },
  offenseTagRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 4,
  },
  lawPill: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  lawPillText: {
    fontSize: 10,
    fontWeight: "900",
  },
  categoryPillText: {
    fontSize: 10,
    fontWeight: "700",
  },
  offenseDescText: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: "600",
  },

  // Info Grid Row
  infoGridRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    marginBottom: 12,
  },
  vehiclePlateBox: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    alignItems: "center",
  },
  plateMiniState: {
    fontSize: 8,
    fontWeight: "900",
    letterSpacing: 1,
  },
  plateMiniNumber: {
    fontSize: 13,
    fontWeight: "900",
    letterSpacing: 1,
  },
  vehicleDetailsCol: {
    flex: 1,
  },
  vehicleModelText: {
    fontSize: 12,
    fontWeight: "700",
  },
  locationInlineRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginTop: 2,
  },
  locationText: {
    fontSize: 11,
    flex: 1,
  },

  // Evidencias Thumbs
  evidenceThumbsRow: {
    flexDirection: "row",
    gap: 8,
    marginBottom: 12,
  },
  thumbWrap: {
    flex: 1,
    height: 70,
    borderRadius: 8,
    overflow: "hidden",
    position: "relative",
  },
  thumbImage: {
    width: "100%",
    height: "100%",
  },
  thumbLabelBadge: {
    position: "absolute",
    bottom: 2,
    left: 2,
    backgroundColor: "rgba(0,0,0,0.7)",
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 4,
  },
  thumbLabelText: {
    color: "#ffffff",
    fontSize: 8,
    fontWeight: "700",
  },

  // Ticket Card Footer
  ticketCardFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingTop: 12,
    borderTopWidth: 1,
  },
  amountCol: {
    flex: 1,
  },
  amountLabel: {
    fontSize: 8,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  amountValue: {
    fontSize: 13,
    fontWeight: "900",
    marginTop: 1,
  },
  detailActionBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
  },
  detailActionBtnText: {
    fontSize: 11,
    fontWeight: "800",
  },

  // Empty Card
  emptyCard: {
    padding: 36,
    borderRadius: 20,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 20,
    gap: 8,
  },
  emptyCardTitle: {
    fontSize: 15,
    fontWeight: "800",
    marginTop: 4,
  },
  emptyCardDesc: {
    fontSize: 12,
    textAlign: "center",
    lineHeight: 18,
    paddingHorizontal: 20,
  },

  // Modales
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
  modalSubtitle: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 0.8,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "900",
    marginTop: 2,
  },
  closeModalBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  modalScrollBody: {
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 40,
  },
  modalSyncBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 14,
  },
  modalSyncBannerText: {
    fontSize: 12,
    fontWeight: "700",
    flex: 1,
  },
  modalSectionCard: {
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 14,
    gap: 8,
  },
  modalSectionTitle: {
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 0.8,
    marginBottom: 4,
  },
  modalDetailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    gap: 12,
  },
  modalDetailKey: {
    fontSize: 12,
    width: 120,
  },
  modalDetailVal: {
    fontSize: 12,
    fontWeight: "600",
    flex: 1,
    textAlign: "right",
  },
  modalOffenseLegal: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  modalOffenseDesc: {
    fontSize: 13,
    lineHeight: 18,
    fontWeight: "600",
  },
  modalUmaBox: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 10,
    borderRadius: 10,
    borderWidth: 1,
    marginTop: 4,
  },
  modalUmaText: {
    fontSize: 12,
    fontWeight: "800",
  },
  modalPesosText: {
    fontSize: 12,
    fontWeight: "800",
  },
  modalHechosBox: {
    padding: 10,
    borderRadius: 10,
    borderWidth: 1,
    marginTop: 4,
  },
  modalHechosTitle: {
    fontSize: 9,
    fontWeight: "800",
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  modalHechosText: {
    fontSize: 11,
    lineHeight: 16,
  },
  modalPhotosGrid: {
    flexDirection: "row",
    gap: 10,
    marginTop: 6,
  },
  modalPhotoItem: {
    flex: 1,
    alignItems: "center",
  },
  modalPhotoImage: {
    width: "100%",
    height: 90,
    borderRadius: 10,
  },
  modalPhotoTag: {
    fontSize: 10,
    fontWeight: "700",
    marginTop: 4,
  },
  modalActionsRow: {
    flexDirection: "row",
    gap: 12,
    marginTop: 10,
    marginBottom: 30,
  },
  modalDeleteBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    gap: 6,
  },
  modalDeleteBtnText: {
    fontSize: 12,
    fontWeight: "800",
  },
  modalCloseBottomBtn: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
  },
  modalCloseBottomBtnText: {
    fontSize: 12,
    fontWeight: "800",
  },

  // Zoom
  zoomOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.95)",
    justifyContent: "center",
    alignItems: "center",
  },
  zoomCloseBtn: {
    position: "absolute",
    top: 50,
    right: 20,
    zIndex: 10,
  },
  zoomImage: {
    width: "92%",
    height: "80%",
  },
});
