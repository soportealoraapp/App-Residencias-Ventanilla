import React, { useEffect, useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  FlatList,
  Alert,
  ActivityIndicator,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { useInfracciones } from "../src/contexts/InfraccionesContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { AgentTabBar } from "../src/components/AgentTabBar";
import { AgentScreenHeader } from "../src/components/AgentScreenHeader";
import { AgentInfraccionDetailModal } from "../src/components/AgentInfraccionDetailModal";
import { LogoutConfirmationModal } from "../src/components/LogoutConfirmationModal";
import { Infraccion } from "../src/types/infraccion";
import { Ionicons } from "@expo/vector-icons";

function fechaISOValida(fecha: string): boolean {
  const coincidencia = /^(\d{4})-(\d{2})-(\d{2})$/.exec(fecha);
  if (!coincidencia) return false;

  const [, anioTexto, mesTexto, diaTexto] = coincidencia;
  const anio = Number(anioTexto);
  const mes = Number(mesTexto);
  const dia = Number(diaTexto);
  const fechaLocal = new Date(anio, mes - 1, dia);

  return (
    fechaLocal.getFullYear() === anio &&
    fechaLocal.getMonth() === mes - 1 &&
    fechaLocal.getDate() === dia
  );
}

export default function DashboardScreen() {
  const router = useRouter();
  const { user, isLoading: authLoading, logout } = useAuth();
  const {
    infracciones,
    totalPendientes,
    totalSincronizadas,
    isSyncing,
    ultimaSincronizacion,
    valorUma,
    sincronizar,
    eliminar,
  } = useInfracciones();
  const { colors, isDark } = useTheme();
  const primaryActionTextColor = isDark ? colors.background : "#ffffff";
  const horaActual = new Date().getHours();
  const fechaActual = new Date();
  const fechaHoy = [
    fechaActual.getFullYear(),
    String(fechaActual.getMonth() + 1).padStart(2, "0"),
    String(fechaActual.getDate()).padStart(2, "0"),
  ].join("-");
  const saludo =
    horaActual < 12 ? "BUENOS DÍAS" : horaActual < 19 ? "BUENAS TARDES" : "BUENAS NOCHES";

  const [infraccionSeleccionada, setInfraccionSeleccionada] = useState<Infraccion | null>(null);
  const [logoutConfirmacionVisible, setLogoutConfirmacionVisible] = useState(false);

  const handleLogout = async () => {
    console.log("[LOGOUT] handleLogout iniciado");
    await logout();
    console.log("[LOGOUT] logout terminó");
    console.log("[LOGOUT] ejecutando router.replace('/')");
    router.replace("/");
  };

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  const obtenerFechaCreacion = (item: Infraccion) => {
    const fechaCreacion = Date.parse(item.creadoEn);
    return Number.isNaN(fechaCreacion)
      ? Date.parse(`${item.generales.fecha}T${item.generales.hora}:00`)
      : fechaCreacion;
  };

  const infraccionesRecientes = [...infracciones]
    .sort((a, b) => obtenerFechaCreacion(b) - obtenerFechaCreacion(a))
    .slice(0, 5);
  const emitidasHoy = infracciones.filter(
    (item) => fechaISOValida(item.generales.fecha) && item.generales.fecha === fechaHoy
  ).length;
  const totalErroresSincronizacion = infracciones.filter(
    (item) => item.estado === "error_sincronizacion"
  ).length;
  const totalPorSincronizar = totalPendientes + totalErroresSincronizacion;

  const handleSincronizar = async () => {
    try {
      const res = await sincronizar();
      if (res.exito) {
        Alert.alert(
          totalErroresSincronizacion > 0 ? "Sincronización incompleta" : "Sincronización al día",
          res.totalSincronizadas > 0
            ? `Se sincronizaron ${res.totalSincronizadas} boleta(s) exitosamente con el servidor central de Uriangato.${totalErroresSincronizacion > 0 ? ` Aún hay ${totalErroresSincronizacion} boleta(s) con error de sincronización y sin respaldo confirmado.` : ""}`
            : totalErroresSincronizacion > 0
              ? `No se sincronizaron boletas nuevas. Hay ${totalErroresSincronizacion} boleta(s) con error de sincronización y sin respaldo confirmado.`
              : "No hay boletas pendientes de sincronizar. El registro local disponible está al día."
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
    const estado = {
      pendiente: {
        label: "Pendiente de sincronización",
        icon: "cloud-upload-outline" as const,
        color: colors.warning,
        backgroundColor: colors.warningBg,
      },
      sincronizada: {
        label: "Sincronizada",
        icon: "cloud-done-outline" as const,
        color: colors.success,
        backgroundColor: colors.successBg,
      },
      error_sincronizacion: {
        label: "Error de sincronización",
        icon: "alert-circle-outline" as const,
        color: colors.danger,
        backgroundColor: colors.dangerBg,
      },
    }[item.estado];
    const etiquetasVehiculo: Record<Infraccion["vehiculo"]["tipo"], string> = {
      particular: "Particular",
      transporte_publico: "Transporte público",
      carga: "Carga",
      motocicleta: "Motocicleta",
      otro: "Otro",
    };
    const totalFaltas = item.faltas?.length ?? 0;

    return (
      <TouchableOpacity
        style={[styles.recentTicket, { backgroundColor: colors.surface, borderColor: colors.border }]}
        onPress={() => setInfraccionSeleccionada(item)}
        activeOpacity={0.75}
        accessibilityLabel={`Abrir detalle de boleta ${item.folio}`}
      >
        <View style={styles.recentTicketTop}>
          <Text style={[styles.recentTicketFolio, { color: colors.text }]} numberOfLines={1}>
            {item.folio}
          </Text>
          <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
        </View>
        <Text style={[styles.recentTicketDate, { color: colors.textSecondary }]}>
          {item.generales.fecha} · {item.generales.hora}
        </Text>
        <View style={styles.recentTicketBottom}>
          <View style={styles.recentTicketDetails}>
            <Text style={[styles.recentTicketMeta, { color: colors.textMuted }]}>
              {etiquetasVehiculo[item.vehiculo.tipo]}
            </Text>
            {totalFaltas > 0 && (
              <Text style={[styles.recentTicketMeta, { color: colors.textMuted }]}>
                {totalFaltas} {totalFaltas === 1 ? "falta" : "faltas"}
              </Text>
            )}
          </View>
          <View style={[styles.recentTicketStatus, { backgroundColor: estado.backgroundColor }]}>
            <Ionicons name={estado.icon} size={14} color={estado.color} />
            <Text style={[styles.recentTicketStatusText, { color: estado.color }]}>{estado.label}</Text>
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  if (authLoading || !user) {
    return (
      <View style={[styles.container, { backgroundColor: colors.background, alignItems: "center", justifyContent: "center" }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <AgentScreenHeader
        actions={
          <>
          <ThemeToggle compact={true} />
          <TouchableOpacity
            style={[styles.logoutBtn, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
            onPress={() => setLogoutConfirmacionVisible(true)}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
            accessibilityLabel="Cerrar sesión"
          >
            <Ionicons name="log-out-outline" size={18} color={colors.danger} />
            <Text style={[styles.logoutText, { color: colors.danger }]}>Cerrar sesión</Text>
          </TouchableOpacity>
          </>
        }
      />

      <FlatList
        data={infraccionesRecientes}
        keyExtractor={(item) => item.id}
        renderItem={renderTicketCard}
        contentContainerStyle={styles.listContent}
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={
          <>
            <View style={styles.dashboardContent}>
              <View style={styles.welcomeBlock}>
                <Text style={[styles.greetingText, { color: colors.textMuted }]}>
                  {saludo}
                </Text>
                {!!user?.nombre && (
                  <Text style={[styles.agentName, { color: colors.text }]}>{user.nombre}</Text>
                )}
                <View style={styles.agentMetaRow}>
                  {!!user?.placa && (
                    <Text style={[styles.agentPlate, { color: colors.primary }]}>{user.placa}</Text>
                  )}
                  {!!user?.sector && (
                    <Text style={[styles.agentMeta, { color: colors.textSecondary }]}>{user.sector}</Text>
                  )}
                  {!!user?.rol && (
                    <Text style={[styles.agentMeta, { color: colors.textSecondary }]}>{user.rol}</Text>
                  )}
                </View>
              </View>

              <View style={[styles.umaLine, { borderTopColor: colors.border, borderBottomColor: colors.border }]}>
                <Text style={[styles.umaLineLabel, { color: colors.textSecondary }]}>UMA vigente</Text>
                <Text style={[styles.umaLineValue, { color: colors.text }]}>${valorUma.toFixed(2)} MXN</Text>
              </View>

              <TouchableOpacity
                style={[styles.primaryAction, { backgroundColor: colors.primary }]}
                onPress={() => router.push("/nueva-infraccion")}
                activeOpacity={0.85}
              >
                <View style={styles.primaryActionIcon}>
                  <Ionicons name="add" size={25} color={colors.primary} />
                </View>
                <View style={styles.primaryActionCopy}>
                  <Text style={[styles.primaryActionTitle, { color: primaryActionTextColor }]}>Registrar infracción</Text>
                  <Text style={[styles.primaryActionSubtitle, { color: primaryActionTextColor }]}>
                    Inicia una boleta y registra una o varias faltas.
                  </Text>
                </View>
                <Ionicons name="arrow-forward" size={20} color={primaryActionTextColor} />
              </TouchableOpacity>

              <View style={styles.sectionHeading}>
                <Text style={[styles.sectionHeadingTitle, { color: colors.text }]}>Boletas disponibles en este dispositivo</Text>
                <Text style={[styles.activityCaption, { color: colors.textSecondary }]}>
                  Cifras de la colección local; no representan el total del servidor.
                </Text>
              </View>
              <View style={[styles.metricsRow, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <View style={styles.metricItem}>
                  <Text style={[styles.metricValue, { color: colors.text }]}>{emitidasHoy}</Text>
                  <Text style={[styles.metricLabel, { color: colors.textSecondary }]}>Emitidas hoy</Text>
                </View>
                <View style={[styles.metricDivider, { backgroundColor: colors.border }]} />
                <View style={styles.metricItem}>
                  <Text style={[styles.metricValue, { color: totalErroresSincronizacion > 0 ? colors.danger : totalPendientes > 0 ? colors.warning : colors.text }]}>
                    {totalPorSincronizar}
                  </Text>
                  <Text style={[styles.metricLabel, { color: colors.textSecondary }]}>Por sincronizar</Text>
                </View>
                <View style={[styles.metricDivider, { backgroundColor: colors.border }]} />
                <View style={styles.metricItem}>
                  <Text style={[styles.metricValue, { color: colors.success }]}>{totalSincronizadas}</Text>
                  <Text style={[styles.metricLabel, { color: colors.textSecondary }]}>Sincronizadas</Text>
                </View>
              </View>

              <View style={[styles.syncSummary, { borderTopColor: colors.border }]}>
                <View style={styles.syncSummaryCopy}>
                  <View style={styles.syncSummaryTitleRow}>
                    <Ionicons
                      name={isSyncing ? "sync-outline" : totalErroresSincronizacion > 0 ? "alert-circle-outline" : totalPendientes > 0 ? "cloud-upload-outline" : "cloud-done-outline"}
                      size={18}
                      color={isSyncing ? colors.primary : totalErroresSincronizacion > 0 ? colors.danger : totalPendientes > 0 ? colors.warning : colors.success}
                    />
                    <Text style={[styles.syncSummaryTitle, { color: colors.text }]}>
                      {isSyncing
                        ? "Sincronización en curso"
                        : totalErroresSincronizacion > 0
                        ? "Hay boletas con errores de sincronización"
                        : totalPendientes > 0
                        ? "Boletas pendientes conservadas en el dispositivo"
                        : "Sin boletas pendientes de respaldo"}
                    </Text>
                  </View>
                  {totalErroresSincronizacion > 0 && (
                    <Text style={[styles.syncSummarySubtitle, { color: colors.danger }]}>
                      {totalPendientes} pendientes · {totalErroresSincronizacion} con error. No todas tienen respaldo confirmado.
                    </Text>
                  )}
                  {totalErroresSincronizacion === 0 && totalPendientes > 0 && (
                    <Text style={[styles.syncSummarySubtitle, { color: colors.textSecondary }]}>
                      {totalPendientes} pendiente(s) de sincronizar.
                    </Text>
                  )}
                  {totalErroresSincronizacion === 0 && totalPendientes === 0 && ultimaSincronizacion && (
                    <Text style={[styles.syncSummarySubtitle, { color: colors.textSecondary }]}>
                      Último envío: {formatearFechaHora(ultimaSincronizacion)}
                    </Text>
                  )}
                  {totalErroresSincronizacion === 0 && totalPendientes === 0 && !ultimaSincronizacion && (
                    <Text style={[styles.syncSummarySubtitle, { color: colors.textSecondary }]}>
                      Aún no hay un envío registrado.
                    </Text>
                  )}
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
            </View>

            <View style={styles.activitySection}>
              <View style={styles.activityHeading}>
                <View>
                  <Text style={[styles.sectionHeadingTitle, { color: colors.text }]}>Actividad reciente</Text>
                  <Text style={[styles.activityCaption, { color: colors.textSecondary }]}>
                    Últimas {Math.min(5, infracciones.length)} boletas
                  </Text>
                </View>
              </View>
            </View>
          </>
        }
        ListEmptyComponent={
          <View style={[styles.emptyRecent, { borderColor: colors.border }]}>
            <Ionicons name="document-text-outline" size={22} color={colors.textMuted} />
            <Text style={[styles.emptyRecentText, { color: colors.textSecondary }]}>
              Aún no hay boletas registradas.
            </Text>
          </View>
        }
      />

      <AgentTabBar selected="inicio" />

      <LogoutConfirmationModal
        visible={logoutConfirmacionVisible}
        onCancel={() => setLogoutConfirmacionVisible(false)}
        onConfirm={() => {
          setLogoutConfirmacionVisible(false);
          void handleLogout();
        }}
      />

      <AgentInfraccionDetailModal
        infraccion={infraccionSeleccionada}
        valorUma={valorUma}
        onClose={() => setInfraccionSeleccionada(null)}
        onDiscard={eliminar}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  dashboardContent: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
    paddingHorizontal: 2,
  },
  welcomeBlock: {
    paddingTop: 23,
    paddingBottom: 16,
  },
  greetingText: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1.1,
    marginBottom: 6,
  },
  agentName: {
    fontSize: 23,
    lineHeight: 29,
    fontWeight: "800",
  },
  agentMetaRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    alignItems: "center",
    columnGap: 10,
    rowGap: 4,
    marginTop: 6,
  },
  agentPlate: {
    fontSize: 13,
    fontWeight: "800",
    letterSpacing: 0.4,
  },
  agentMeta: {
    fontSize: 12,
  },
  umaLine: {
    minHeight: 42,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 18,
  },
  umaLineLabel: {
    fontSize: 12,
    fontWeight: "600",
  },
  umaLineValue: {
    fontSize: 13,
    fontWeight: "800",
  },
  primaryAction: {
    minHeight: 76,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 13,
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginBottom: 25,
  },
  primaryActionIcon: {
    width: 42,
    height: 42,
    borderRadius: 11,
    backgroundColor: "#ffffff",
    alignItems: "center",
    justifyContent: "center",
  },
  primaryActionCopy: {
    flex: 1,
  },
  primaryActionTitle: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
  },
  primaryActionSubtitle: {
    color: "rgba(255,255,255,0.86)",
    fontSize: 11,
    lineHeight: 16,
    marginTop: 3,
  },
  sectionHeading: {
    marginBottom: 10,
  },
  sectionHeadingTitle: {
    fontSize: 16,
    fontWeight: "800",
  },
  metricsRow: {
    minHeight: 78,
    borderWidth: 1,
    borderRadius: 13,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 18,
  },
  metricItem: {
    flex: 1,
    alignItems: "center",
    paddingHorizontal: 4,
    paddingVertical: 12,
  },
  metricValue: {
    fontSize: 21,
    lineHeight: 25,
    fontWeight: "800",
  },
  metricLabel: {
    fontSize: 10,
    textAlign: "center",
    marginTop: 4,
  },
  metricDivider: {
    width: StyleSheet.hairlineWidth,
    height: 38,
  },
  syncSummary: {
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingTop: 15,
    paddingBottom: 19,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 12,
  },
  syncSummaryCopy: {
    flex: 1,
  },
  syncSummaryTitleRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 8,
  },
  syncSummaryTitle: {
    flex: 1,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: "700",
  },
  syncSummarySubtitle: {
    fontSize: 11,
    lineHeight: 16,
    marginTop: 4,
    marginLeft: 26,
  },
  activityHeading: {
    paddingTop: 4,
    paddingBottom: 12,
  },
  activitySection: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
  },
  activityCaption: {
    fontSize: 11,
    marginTop: 3,
  },
  recentTicket: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 9,
  },
  recentTicketTop: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 8,
  },
  recentTicketFolio: {
    flex: 1,
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.15,
  },
  recentTicketDate: {
    fontSize: 11,
    marginTop: 3,
  },
  recentTicketBottom: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    flexWrap: "wrap",
    gap: 8,
    marginTop: 10,
  },
  recentTicketDetails: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
  },
  recentTicketMeta: {
    fontSize: 11,
  },
  recentTicketStatus: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 7,
  },
  recentTicketStatusText: {
    fontSize: 10,
    fontWeight: "700",
  },
  emptyRecent: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
    borderWidth: 1,
    borderStyle: "dashed",
    borderRadius: 12,
    padding: 18,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 9,
  },
  emptyRecentText: {
    fontSize: 12,
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
  logoutBtn: {
    minWidth: 38,
    height: 38,
    paddingHorizontal: 10,
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: "row",
    gap: 6,
    alignItems: "center",
    justifyContent: "center",
  },
  logoutText: { fontSize: 12, fontWeight: "700" },
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

});
