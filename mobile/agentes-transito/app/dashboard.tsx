import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  FlatList,
  Modal,
  Image,
  ScrollView,
  ActivityIndicator,
  Alert,
  RefreshControl,
} from "react-native";
import { useRouter } from "expo-router";
import { useAuth } from "../src/contexts/AuthContext";
import { useInfracciones } from "../src/contexts/InfraccionesContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { Infraccion } from "../src/types/infraccion";
import { Ionicons } from "@expo/vector-icons";

type FiltroTab = "todas" | "pendientes" | "sincronizadas";

export default function DashboardScreen() {
  const router = useRouter();
  const { user, signOut } = useAuth();
  const { colors, isDark } = useTheme();
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
    recargar,
  } = useInfracciones();

  const [tabActual, setTabActual] = useState<FiltroTab>("todas");
  const [infraccionSeleccionada, setInfraccionSeleccionada] = useState<Infraccion | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const handleRefresh = async () => {
    setRefreshing(true);
    await recargar();
    setRefreshing(false);
  };

  const handleSyncPress = async () => {
    try {
      const res = await sincronizar();
      Alert.alert("Sincronización con Supabase", res.mensaje);
    } catch (err: any) {
      Alert.alert("Error de Conexión", err.message || "No se pudo conectar con Supabase.");
    }
  };

  const handleCerrarSesion = () => {
    Alert.alert(
      "Cerrar Sesión",
      "¿Desea salir de la aplicación? Sus boletas locales permanecerán guardadas de forma segura.",
      [
        { text: "Cancelar", style: "cancel" },
        {
          text: "Salir",
          style: "destructive",
          onPress: async () => {
            await signOut();
            router.replace("/");
          },
        },
      ]
    );
  };

  const handleEliminarBoleta = (id: string, folio: string) => {
    Alert.alert(
      "Eliminar Boleta Local",
      `¿Está seguro de eliminar la boleta ${folio}? Esta acción no se puede deshacer.`,
      [
        { text: "Cancelar", style: "cancel" },
        {
          text: "Eliminar",
          style: "destructive",
          onPress: async () => {
            await eliminar(id);
            if (infraccionSeleccionada?.id === id) {
              setInfraccionSeleccionada(null);
            }
          },
        },
      ]
    );
  };

  const listaFiltrada =
    tabActual === "pendientes"
      ? pendientes
      : tabActual === "sincronizadas"
      ? sincronizadas
      : infracciones;

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      {/* Barra Superior con Información del Agente y Toggle de Tema */}
      <View style={[styles.topBar, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <View style={styles.agentInfoWrap}>
          <View style={[styles.agentAvatar, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
            <Ionicons name="person" size={20} color={colors.primary} />
          </View>
          <View>
            <View style={styles.badgeRow}>
              <View style={[styles.placaPill, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                <Text style={[styles.placaText, { color: colors.text }]}>{user?.placa || "AGT-204"}</Text>
              </View>
              <View style={[styles.statusPill, { backgroundColor: colors.successBg }]}>
                <View style={styles.statusDot} />
                <Text style={[styles.statusText, { color: colors.success }]}>EN TURNO</Text>
              </View>
            </View>
            <Text style={[styles.agentName, { color: colors.text }]} numberOfLines={1}>
              {user?.nombre || "Oficial de Tránsito"}
            </Text>
          </View>
        </View>

        <View style={styles.topRightActions}>
          <ThemeToggle compact={true} />
          <TouchableOpacity
            onPress={handleCerrarSesion}
            style={[styles.logoutButton, { backgroundColor: colors.dangerBg, borderColor: colors.border }]}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            accessibilityLabel="Cerrar sesión"
          >
            <Ionicons name="power-outline" size={18} color={colors.danger} />
          </TouchableOpacity>
        </View>
      </View>

      <FlatList
        data={listaFiltrada}
        keyExtractor={(item) => item.id}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.primary}
          />
        }
        contentContainerStyle={styles.listContainer}
        ListHeaderComponent={
          <>
            {/* Banner Institucional Uriangato */}
            <View style={[styles.heroCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.heroTextCol}>
                <View style={styles.heroTagRow}>
                  <Text style={[styles.municipioHeader, { color: colors.primary }]}>URIANGATO, GTO.</Text>
                  <View style={[styles.umaTag, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
                    <Ionicons name="pricetag-outline" size={12} color={colors.primary} />
                    <Text style={[styles.umaTagText, { color: colors.primary }]}>
                      1 UMA = ${valorUma.toFixed(2)} MXN
                    </Text>
                  </View>
                </View>
                <Text style={[styles.heroTitle, { color: colors.text }]}>Control Operativo de Tránsito</Text>
                <Text style={[styles.heroSubtitle, { color: colors.textSecondary }]}>
                  {user?.sector || "Sector Centro - Módulo Móvil"}
                </Text>
              </View>
              <View style={[styles.badgeSeal, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
                <Ionicons name="car-sport" size={26} color={colors.primary} />
              </View>
            </View>

            {/* BOTÓN PRINCIPAL DE ACCIÓN: LEVANTAR NUEVA INFRACCIÓN */}
            <TouchableOpacity
              style={[styles.newInfractionButton, { backgroundColor: colors.primary }]}
              onPress={() => router.push("/nueva-infraccion")}
              activeOpacity={0.88}
            >
              <View style={styles.btnIconWrap}>
                <Ionicons name="add-circle" size={32} color="#ffffff" />
              </View>
              <View style={styles.btnTextWrap}>
                <Text style={styles.btnMainTitle}>LEVANTAR NUEVA INFRACCIÓN</Text>
                <Text style={styles.btnSubTitle}>
                  Boleta oficial con GPS, catálogo y 3 fotos obligatorias
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={24} color="#ffffff" />
            </TouchableOpacity>

            {/* Tarjetas de Estadísticas Rápidas */}
            <View style={styles.statsRow}>
              <View style={[styles.statBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[styles.statNumber, { color: colors.text }]}>{infracciones.length}</Text>
                <Text style={[styles.statLabel, { color: colors.textMuted }]}>TOTAL BOLETAS</Text>
              </View>

              <View style={[styles.statBox, { backgroundColor: colors.surface, borderColor: totalPendientes > 0 ? colors.warning : colors.border }]}>
                <View style={styles.statHeader}>
                  <Text style={[styles.statNumber, { color: colors.warning }]}>
                    {totalPendientes}
                  </Text>
                  {totalPendientes > 0 && (
                    <Ionicons name="cloud-offline" size={16} color={colors.warning} />
                  )}
                </View>
                <Text style={[styles.statLabel, { color: colors.warning }]}>
                  PENDIENTES
                </Text>
              </View>

              <View style={[styles.statBox, { backgroundColor: colors.surface, borderColor: totalSincronizadas > 0 ? colors.success : colors.border }]}>
                <View style={styles.statHeader}>
                  <Text style={[styles.statNumber, { color: colors.success }]}>
                    {totalSincronizadas}
                  </Text>
                  <Ionicons name="checkmark-done" size={16} color={colors.success} />
                </View>
                <Text style={[styles.statLabel, { color: colors.success }]}>
                  SINCRONIZADAS
                </Text>
              </View>
            </View>

            {/* Módulo de Sincronización con Supabase */}
            <View style={[styles.syncCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.syncInfo}>
                <View style={styles.syncIconRow}>
                  <Ionicons
                    name={totalPendientes > 0 ? "cloud-upload-outline" : "checkmark-circle-outline"}
                    size={22}
                    color={totalPendientes > 0 ? colors.warning : colors.success}
                  />
                  <Text style={[styles.syncTitle, { color: colors.text }]}>
                    {totalPendientes > 0
                      ? `${totalPendientes} Boletas Pendientes de Envío`
                      : "Base de Datos Sincronizada"}
                  </Text>
                </View>
                <Text style={[styles.syncSubtitle, { color: colors.textSecondary }]}>
                  {ultimaSincronizacion
                    ? `Último envío a Supabase: ${new Date(
                        ultimaSincronizacion
                      ).toLocaleTimeString("es-MX", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                      })}`
                    : "Conexión directa activa a Supabase PostgreSQL."}
                </Text>
              </View>

              <TouchableOpacity
                style={[
                  styles.syncButton,
                  { backgroundColor: totalPendientes > 0 ? colors.warning : colors.primary },
                  isSyncing && styles.buttonDisabled,
                ]}
                onPress={handleSyncPress}
                disabled={isSyncing}
                activeOpacity={0.8}
              >
                {isSyncing ? (
                  <ActivityIndicator color="#ffffff" size="small" />
                ) : (
                  <>
                    <Ionicons
                      name="refresh-outline"
                      size={18}
                      color="#ffffff"
                    />
                    <Text style={styles.syncButtonText}>
                      {totalPendientes > 0 ? "SINCRONIZAR AHORA" : "COMPROBAR"}
                    </Text>
                  </>
                )}
              </TouchableOpacity>
            </View>

            {/* Pestañas de Filtro */}
            <View style={styles.sectionHeaderRow}>
              <Text style={[styles.sectionTitle, { color: colors.text }]}>HISTORIAL DE BOLETAS</Text>
              <Text style={[styles.sectionCounter, { color: colors.textMuted }]}>{listaFiltrada.length} registros</Text>
            </View>

            <View style={[styles.tabsWrapper, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <TouchableOpacity
                style={[
                  styles.tabBtn,
                  tabActual === "todas" && [styles.tabBtnActive, { backgroundColor: colors.primary }],
                ]}
                onPress={() => setTabActual("todas")}
              >
                <Text
                  style={[
                    styles.tabBtnText,
                    { color: tabActual === "todas" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Todas ({infracciones.length})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tabBtn,
                  tabActual === "pendientes" && [styles.tabBtnActive, { backgroundColor: colors.warning }],
                ]}
                onPress={() => setTabActual("pendientes")}
              >
                <Text
                  style={[
                    styles.tabBtnText,
                    { color: tabActual === "pendientes" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Pendientes ({totalPendientes})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tabBtn,
                  tabActual === "sincronizadas" && [styles.tabBtnActive, { backgroundColor: colors.success }],
                ]}
                onPress={() => setTabActual("sincronizadas")}
              >
                <Text
                  style={[
                    styles.tabBtnText,
                    { color: tabActual === "sincronizadas" ? "#ffffff" : colors.textSecondary },
                  ]}
                >
                  Enviadas ({totalSincronizadas})
                </Text>
              </TouchableOpacity>
            </View>
          </>
        }
        ListEmptyComponent={
          <View style={[styles.emptyContainer, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Ionicons name="document-text-outline" size={54} color={colors.textMuted} />
            <Text style={[styles.emptyTitle, { color: colors.text }]}>No hay boletas en esta sección</Text>
            <Text style={[styles.emptySubtitle, { color: colors.textSecondary }]}>
              {tabActual === "pendientes"
                ? "Todas las boletas han sido transmitidas a Supabase."
                : tabActual === "sincronizadas"
                ? "Aún no ha sincronizado boletas en este turno."
                : "Presione 'Levantar Nueva Infracción' para registrar la primera boleta."}
            </Text>
          </View>
        }
        renderItem={({ item }) => {
          const esPendiente = item.estado === "pendiente";
          const minPesos = Number(item.falta.montoMinUma) * valorUma;
          const maxPesos = Number(item.falta.montoMaxUma) * valorUma;

          return (
            <TouchableOpacity
              style={[
                styles.ticketCard,
                { backgroundColor: colors.surface, borderColor: esPendiente ? colors.warning : colors.border },
              ]}
              onPress={() => setInfraccionSeleccionada(item)}
              activeOpacity={0.75}
            >
              <View style={styles.ticketTopRow}>
                <View style={styles.folioBadgeRow}>
                  <Text style={[styles.ticketFolio, { color: colors.primary }]}>{item.folio}</Text>
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
                      name={esPendiente ? "cloud-offline" : "checkmark-circle"}
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
                <Text style={[styles.ticketDate, { color: colors.textMuted }]}>
                  {item.generales.fecha} · {item.generales.hora}
                </Text>
              </View>

              <View style={styles.ticketBody}>
                <View style={styles.vehicleRow}>
                  <View style={[styles.platePill, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                    <Ionicons name="car" size={15} color={colors.primary} />
                    <Text style={[styles.platePillText, { color: colors.text }]}>
                      {item.vehiculo.sinPlacas
                        ? "SIN PLACAS"
                        : item.vehiculo.placas || "S/P"}
                    </Text>
                  </View>
                  <Text style={[styles.vehicleModel, { color: colors.textSecondary }]} numberOfLines={1}>
                    {item.vehiculo.marca} {item.vehiculo.lineaModelo} ({item.vehiculo.color})
                  </Text>
                </View>

                <View style={styles.locationRow}>
                  <Ionicons name="location-outline" size={15} color={colors.textMuted} />
                  <Text style={[styles.locationText, { color: colors.textSecondary }]} numberOfLines={1}>
                    {item.generales.lugar}
                  </Text>
                </View>

                <View style={[styles.violationHighlight, { backgroundColor: colors.dangerBg, borderColor: colors.border }]}>
                  <Text style={[styles.violationLegal, { color: colors.danger }]}>
                    {item.falta.fundamentoLegal}
                  </Text>
                  <Text style={[styles.violationDesc, { color: colors.text }]} numberOfLines={2}>
                    {item.falta.descripcion}
                  </Text>
                </View>

                <View style={styles.ticketFooter}>
                  <View style={styles.amountWrap}>
                    <Text style={[styles.amountLabel, { color: colors.textMuted }]}>IMPORTE ESTIMADO</Text>
                    <Text style={[styles.amountValue, { color: colors.success }]}>
                      ${minPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} - ${maxPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} MXN
                    </Text>
                  </View>
                  <View style={styles.detailLink}>
                    <Text style={[styles.detailLinkText, { color: colors.primary }]}>Ver Detalles</Text>
                    <Ionicons name="chevron-forward" size={16} color={colors.primary} />
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          );
        }}
      />

      {/* MODAL DE DETALLE COMPLETO DE LA BOLETA */}
      <Modal
        visible={!!infraccionSeleccionada}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setInfraccionSeleccionada(null)}
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContent, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.modalHeader, { borderBottomColor: colors.border }]}>
              <View>
                <Text style={[styles.modalSubtitle, { color: colors.textMuted }]}>DETALLE OFICIAL DE INFRACCIÓN</Text>
                <Text style={[styles.modalTitle, { color: colors.primary }]}>
                  {infraccionSeleccionada?.folio}
                </Text>
              </View>
              <TouchableOpacity
                onPress={() => setInfraccionSeleccionada(null)}
                style={[styles.closeModalBtn, { backgroundColor: colors.surfaceElevated }]}
              >
                <Ionicons name="close" size={24} color={colors.text} />
              </TouchableOpacity>
            </View>

            {infraccionSeleccionada && (
              <ScrollView
                style={styles.modalScroll}
                contentContainerStyle={styles.modalScrollInner}
              >
                {/* Estatus de Sincronización */}
                <View
                  style={[
                    styles.syncStatusBanner,
                    {
                      backgroundColor:
                        infraccionSeleccionada.estado === "pendiente"
                          ? colors.warningBg
                          : colors.successBg,
                      borderColor:
                        infraccionSeleccionada.estado === "pendiente"
                          ? colors.warning
                          : colors.success,
                    },
                  ]}
                >
                  <Ionicons
                    name={
                      infraccionSeleccionada.estado === "pendiente"
                        ? "cloud-offline"
                        : "checkmark-circle"
                    }
                    size={20}
                    color={
                      infraccionSeleccionada.estado === "pendiente"
                        ? colors.warning
                        : colors.success
                    }
                  />
                  <Text
                    style={[
                      styles.syncStatusText,
                      {
                        color:
                          infraccionSeleccionada.estado === "pendiente"
                            ? colors.warning
                            : colors.success,
                      },
                    ]}
                  >
                    {infraccionSeleccionada.estado === "pendiente"
                      ? "Guardada localmente — Pendiente de sincronizar a Supabase"
                      : `Sincronizada con Supabase exitosamente`}
                  </Text>
                </View>

                {/* Sección 1: Generales y Ubicación */}
                <View style={[styles.detailSection, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.detailSectionTitle, { color: colors.primary }]}>LUGAR Y FECHA</Text>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Fecha y Hora:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.generales.fecha} a las {infraccionSeleccionada.generales.hora} hrs
                    </Text>
                  </View>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Ubicación:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.generales.lugar}
                    </Text>
                  </View>
                  {infraccionSeleccionada.generales.coordenadas && (
                    <View style={styles.detailRow}>
                      <Text style={[styles.detailKey, { color: colors.textMuted }]}>Coordenadas GPS:</Text>
                      <Text style={[styles.detailVal, { color: colors.text }]}>
                        {infraccionSeleccionada.generales.coordenadas.latitud.toFixed(5)},{" "}
                        {infraccionSeleccionada.generales.coordenadas.longitud.toFixed(5)}
                      </Text>
                    </View>
                  )}
                </View>

                {/* Sección 2: Vehículo */}
                <View style={[styles.detailSection, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.detailSectionTitle, { color: colors.primary }]}>VEHÍCULO</Text>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Placas:</Text>
                    <Text style={[styles.detailVal, { color: colors.text, fontWeight: "700" }]}>
                      {infraccionSeleccionada.vehiculo.sinPlacas
                        ? "SIN PLACAS REGISTRADAS"
                        : infraccionSeleccionada.vehiculo.placas}
                    </Text>
                  </View>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Marca / Modelo:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.vehiculo.marca} {infraccionSeleccionada.vehiculo.lineaModelo}
                    </Text>
                  </View>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Color / Tipo:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.vehiculo.color} · {infraccionSeleccionada.vehiculo.tipo}
                    </Text>
                  </View>
                </View>

                {/* Sección 3: Infractor */}
                <View style={[styles.detailSection, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.detailSectionTitle, { color: colors.primary }]}>CONDUCTOR / INFRACTOR</Text>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Estado:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.infractor.conductorAusente
                        ? "Conductor Ausente en el Sitio"
                        : "Conductor Presente"}
                    </Text>
                  </View>
                  {!infraccionSeleccionada.infractor.conductorAusente && (
                    <>
                      <View style={styles.detailRow}>
                        <Text style={[styles.detailKey, { color: colors.textMuted }]}>Nombre:</Text>
                        <Text style={[styles.detailVal, { color: colors.text }]}>
                          {infraccionSeleccionada.infractor.nombre}
                        </Text>
                      </View>
                      <View style={styles.detailRow}>
                        <Text style={[styles.detailKey, { color: colors.textMuted }]}>Licencia:</Text>
                        <Text style={[styles.detailVal, { color: colors.text }]}>
                          {infraccionSeleccionada.infractor.numeroLicencia || "No presentada"}
                        </Text>
                      </View>
                    </>
                  )}
                </View>

                {/* Sección 4: Falta e Importes */}
                <View style={[styles.detailSection, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.detailSectionTitle, { color: colors.danger }]}>INFRACCIÓN COMETIDA</Text>
                  <Text style={[styles.detailLawText, { color: colors.danger }]}>
                    {infraccionSeleccionada.falta.fundamentoLegal}
                  </Text>
                  <Text style={[styles.detailDescText, { color: colors.text }]}>
                    {infraccionSeleccionada.falta.descripcion}
                  </Text>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Sanción en UMAS:</Text>
                    <Text style={[styles.detailVal, { color: colors.text }]}>
                      {infraccionSeleccionada.falta.montoMinUma} a {infraccionSeleccionada.falta.montoMaxUma} UMAS
                    </Text>
                  </View>
                  <View style={styles.detailRow}>
                    <Text style={[styles.detailKey, { color: colors.textMuted }]}>Equivalente en Pesos:</Text>
                    <Text style={[styles.detailVal, { color: colors.success, fontWeight: "700" }]}>
                      ${(Number(infraccionSeleccionada.falta.montoMinUma) * valorUma).toFixed(2)} - $
                      {(Number(infraccionSeleccionada.falta.montoMaxUma) * valorUma).toFixed(2)} MXN
                    </Text>
                  </View>
                  <View style={[styles.hechosBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                    <Text style={[styles.hechosLabel, { color: colors.textMuted }]}>HECHOS CIRCUNSTANCIADOS:</Text>
                    <Text style={[styles.hechosText, { color: colors.text }]}>
                      {infraccionSeleccionada.hechos}
                    </Text>
                  </View>
                </View>

                {/* Sección 5: Evidencias Fotográficas */}
                <View style={[styles.detailSection, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Text style={[styles.detailSectionTitle, { color: colors.primary }]}>EVIDENCIAS FOTOGRÁFICAS (3 FOTOS)</Text>
                  <View style={styles.photosGrid}>
                    <View style={styles.photoItem}>
                      <Image
                        source={{ uri: infraccionSeleccionada.evidencias.fotoPlaca }}
                        style={[styles.evidenceThumb, { borderColor: colors.border }]}
                      />
                      <Text style={[styles.photoLabel, { color: colors.textSecondary }]}>Placa</Text>
                    </View>
                    <View style={styles.photoItem}>
                      <Image
                        source={{ uri: infraccionSeleccionada.evidencias.fotoContexto }}
                        style={[styles.evidenceThumb, { borderColor: colors.border }]}
                      />
                      <Text style={[styles.photoLabel, { color: colors.textSecondary }]}>Contexto</Text>
                    </View>
                    <View style={styles.photoItem}>
                      <Image
                        source={{ uri: infraccionSeleccionada.evidencias.fotoDocumento }}
                        style={[styles.evidenceThumb, { borderColor: colors.border }]}
                      />
                      <Text style={[styles.photoLabel, { color: colors.textSecondary }]}>Garantía</Text>
                    </View>
                  </View>
                </View>

                {/* Acciones del Modal */}
                <View style={styles.modalActionButtons}>
                  <TouchableOpacity
                    style={[styles.deleteButton, { backgroundColor: colors.dangerBg, borderColor: colors.danger }]}
                    onPress={() =>
                      handleEliminarBoleta(
                        infraccionSeleccionada.id,
                        infraccionSeleccionada.folio
                      )
                    }
                  >
                    <Ionicons name="trash-outline" size={18} color={colors.danger} />
                    <Text style={[styles.deleteButtonText, { color: colors.danger }]}>Eliminar Local</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.closeBottomBtn, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
                    onPress={() => setInfraccionSeleccionada(null)}
                  >
                    <Text style={[styles.closeBottomBtnText, { color: colors.text }]}>Cerrar</Text>
                  </TouchableOpacity>
                </View>
              </ScrollView>
            )}
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  topBar: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 12,
    borderBottomWidth: 1,
  },
  agentInfoWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    flex: 1,
  },
  agentAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  badgeRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 2,
  },
  placaPill: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
  },
  placaText: {
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  statusPill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: "#10b981",
  },
  statusText: {
    fontSize: 10,
    fontWeight: "700",
  },
  agentName: {
    fontSize: 14,
    fontWeight: "700",
  },
  topRightActions: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  logoutButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  listContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  heroCard: {
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 14,
  },
  heroTextCol: {
    flex: 1,
  },
  heroTagRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 4,
    flexWrap: "wrap",
  },
  municipioHeader: {
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 1,
  },
  umaTag: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    borderWidth: 1,
  },
  umaTagText: {
    fontSize: 10,
    fontWeight: "700",
  },
  heroTitle: {
    fontSize: 18,
    fontWeight: "800",
    letterSpacing: -0.3,
  },
  heroSubtitle: {
    fontSize: 12,
    marginTop: 2,
  },
  badgeSeal: {
    width: 48,
    height: 48,
    borderRadius: 24,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    marginLeft: 10,
  },
  newInfractionButton: {
    flexDirection: "row",
    alignItems: "center",
    padding: 16,
    borderRadius: 16,
    marginBottom: 16,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  btnIconWrap: {
    marginRight: 12,
  },
  btnTextWrap: {
    flex: 1,
  },
  btnMainTitle: {
    color: "#ffffff",
    fontSize: 16,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  btnSubTitle: {
    color: "rgba(255, 255, 255, 0.85)",
    fontSize: 11,
    marginTop: 2,
  },
  statsRow: {
    flexDirection: "row",
    gap: 10,
    marginBottom: 14,
  },
  statBox: {
    flex: 1,
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
  },
  statHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  statNumber: {
    fontSize: 22,
    fontWeight: "800",
  },
  statLabel: {
    fontSize: 10,
    fontWeight: "700",
    letterSpacing: 0.5,
    marginTop: 4,
  },
  syncCard: {
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    marginBottom: 20,
    gap: 12,
  },
  syncInfo: {
    flex: 1,
  },
  syncIconRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 4,
  },
  syncTitle: {
    fontSize: 14,
    fontWeight: "700",
  },
  syncSubtitle: {
    fontSize: 11,
  },
  syncButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 10,
    paddingVertical: 10,
    gap: 6,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  syncButtonText: {
    color: "#ffffff",
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  sectionHeaderRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.8,
  },
  sectionCounter: {
    fontSize: 11,
  },
  tabsWrapper: {
    flexDirection: "row",
    borderRadius: 12,
    padding: 4,
    borderWidth: 1,
    marginBottom: 14,
    gap: 4,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    alignItems: "center",
  },
  tabBtnActive: {},
  tabBtnText: {
    fontSize: 12,
    fontWeight: "700",
  },
  emptyContainer: {
    padding: 36,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 10,
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: "700",
    marginTop: 12,
    marginBottom: 4,
  },
  emptySubtitle: {
    fontSize: 12,
    textAlign: "center",
    lineHeight: 18,
  },
  ticketCard: {
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    marginBottom: 12,
  },
  ticketTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  folioBadgeRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  ticketFolio: {
    fontSize: 14,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  syncBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
  },
  syncBadgeText: {
    fontSize: 9,
    fontWeight: "800",
  },
  ticketDate: {
    fontSize: 11,
  },
  ticketBody: {
    gap: 6,
  },
  vehicleRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  platePill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
  },
  platePillText: {
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  vehicleModel: {
    fontSize: 12,
    flex: 1,
  },
  locationRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  locationText: {
    fontSize: 11,
    flex: 1,
  },
  violationHighlight: {
    padding: 10,
    borderRadius: 8,
    borderWidth: 1,
    marginTop: 4,
  },
  violationLegal: {
    fontSize: 11,
    fontWeight: "800",
    marginBottom: 2,
  },
  violationDesc: {
    fontSize: 12,
    lineHeight: 16,
  },
  ticketFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-end",
    marginTop: 6,
    paddingTop: 6,
  },
  amountWrap: {
    flex: 1,
  },
  amountLabel: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
  },
  amountValue: {
    fontSize: 13,
    fontWeight: "800",
  },
  detailLink: {
    flexDirection: "row",
    alignItems: "center",
    gap: 2,
  },
  detailLinkText: {
    fontSize: 12,
    fontWeight: "700",
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.75)",
    justifyContent: "flex-end",
  },
  modalContent: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "90%",
    borderWidth: 1,
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderBottomWidth: 1,
  },
  modalSubtitle: {
    fontSize: 10,
    fontWeight: "700",
    letterSpacing: 0.8,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: "800",
  },
  closeModalBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  modalScroll: {
    flexGrow: 0,
  },
  modalScrollInner: {
    padding: 16,
    gap: 12,
    paddingBottom: 32,
  },
  syncStatusBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
  },
  syncStatusText: {
    fontSize: 12,
    fontWeight: "700",
    flex: 1,
  },
  detailSection: {
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    gap: 6,
  },
  detailSectionTitle: {
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 0.8,
    marginBottom: 4,
  },
  detailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    gap: 12,
  },
  detailKey: {
    fontSize: 12,
    width: 130,
  },
  detailVal: {
    fontSize: 12,
    fontWeight: "500",
    flex: 1,
    textAlign: "right",
  },
  detailLawText: {
    fontSize: 13,
    fontWeight: "800",
  },
  detailDescText: {
    fontSize: 12,
    lineHeight: 17,
  },
  hechosBox: {
    padding: 10,
    borderRadius: 8,
    borderWidth: 1,
    marginTop: 6,
  },
  hechosLabel: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  hechosText: {
    fontSize: 11,
    lineHeight: 16,
  },
  photosGrid: {
    flexDirection: "row",
    gap: 10,
    marginTop: 6,
  },
  photoItem: {
    flex: 1,
    alignItems: "center",
  },
  evidenceThumb: {
    width: "100%",
    height: 90,
    borderRadius: 8,
    borderWidth: 1,
  },
  photoLabel: {
    fontSize: 10,
    fontWeight: "600",
    marginTop: 4,
  },
  modalActionButtons: {
    flexDirection: "row",
    gap: 10,
    marginTop: 10,
  },
  deleteButton: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    gap: 6,
  },
  deleteButtonText: {
    fontSize: 12,
    fontWeight: "700",
  },
  closeBottomBtn: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
  },
  closeBottomBtnText: {
    fontSize: 12,
    fontWeight: "700",
  },
});
