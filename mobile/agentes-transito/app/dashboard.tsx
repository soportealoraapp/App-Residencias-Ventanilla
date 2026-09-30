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
import { Infraccion } from "../src/types/infraccion";
import { Ionicons } from "@expo/vector-icons";

type FiltroTab = "todas" | "pendientes" | "sincronizadas";

export default function DashboardScreen() {
  const router = useRouter();
  const { user, signOut } = useAuth();
  const {
    infracciones,
    pendientes,
    sincronizadas,
    totalPendientes,
    totalSincronizadas,
    isSyncing,
    ultimaSincronizacion,
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
      Alert.alert("Sincronización de Campo", res.mensaje);
    } catch (err: any) {
      Alert.alert("Error de Conexión", err.message || "No se pudo conectar con el servidor central.");
    }
  };

  const handleCerrarSesion = () => {
    Alert.alert(
      "Cerrar Sesión",
      "¿Desea salir de la aplicación? Sus infracciones locales permanecerán guardadas de forma segura.",
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

  // Filtrar lista según pestaña activa
  const listaFiltrada =
    tabActual === "pendientes"
      ? pendientes
      : tabActual === "sincronizadas"
      ? sincronizadas
      : infracciones;

  return (
    <View style={styles.container}>
      {/* Barra Superior con Información del Agente */}
      <View style={styles.topBar}>
        <View style={styles.agentInfoWrap}>
          <View style={styles.agentAvatar}>
            <Ionicons name="person" size={20} color="#3b82f6" />
          </View>
          <View>
            <View style={styles.badgeRow}>
              <View style={styles.placaPill}>
                <Text style={styles.placaText}>{user?.placa || "AGT-204"}</Text>
              </View>
              <View style={styles.statusPill}>
                <View style={styles.statusDot} />
                <Text style={styles.statusText}>EN TURNO</Text>
              </View>
            </View>
            <Text style={styles.agentName} numberOfLines={1}>
              {user?.nombre || "Oficial de Tránsito"}
            </Text>
          </View>
        </View>

        <TouchableOpacity
          onPress={handleCerrarSesion}
          style={styles.logoutButton}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="power-outline" size={20} color="#ef4444" />
        </TouchableOpacity>
      </View>

      <FlatList
        data={listaFiltrada}
        keyExtractor={(item) => item.id}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor="#3b82f6"
          />
        }
        contentContainerStyle={styles.listContainer}
        ListHeaderComponent={
          <>
            {/* Banner Institucional Uriangato */}
            <View style={styles.heroCard}>
              <View style={styles.heroTextCol}>
                <Text style={styles.municipioHeader}>URIANGATO, GTO.</Text>
                <Text style={styles.heroTitle}>Control Operativo de Tránsito</Text>
                <Text style={styles.heroSubtitle}>
                  {user?.sector || "Sector Centro - Módulo Móvil"}
                </Text>
              </View>
              <View style={styles.badgeSeal}>
                <Ionicons name="car-sport" size={26} color="#60a5fa" />
              </View>
            </View>

            {/* BOTÓN PRINCIPAL DE ACCIÓN: LEVANTAR NUEVA INFRACCIÓN */}
            <TouchableOpacity
              style={styles.newInfractionButton}
              onPress={() => router.push("/nueva-infraccion")}
              activeOpacity={0.85}
            >
              <View style={styles.btnIconWrap}>
                <Ionicons name="add-circle" size={32} color="#ffffff" />
              </View>
              <View style={styles.btnTextWrap}>
                <Text style={styles.btnMainTitle}>LEVANTAR NUEVA INFRACCIÓN</Text>
                <Text style={styles.btnSubTitle}>
                  Boleta electrónica oficial con GPS y 3 fotos obligatorias
                </Text>
              </View>
              <Ionicons name="chevron-forward" size={24} color="#ffffff" />
            </TouchableOpacity>

            {/* Tarjetas de Estadísticas Rápidas */}
            <View style={styles.statsRow}>
              <View style={styles.statBox}>
                <Text style={styles.statNumber}>{infracciones.length}</Text>
                <Text style={styles.statLabel}>TOTAL BOLETAS</Text>
              </View>

              <View style={[styles.statBox, styles.statBoxAmber]}>
                <View style={styles.statHeader}>
                  <Text style={[styles.statNumber, { color: "#f59e0b" }]}>
                    {totalPendientes}
                  </Text>
                  {totalPendientes > 0 && (
                    <Ionicons name="cloud-offline" size={16} color="#f59e0b" />
                  )}
                </View>
                <Text style={[styles.statLabel, { color: "#fcd34d" }]}>
                  PENDIENTES
                </Text>
              </View>

              <View style={[styles.statBox, styles.statBoxGreen]}>
                <View style={styles.statHeader}>
                  <Text style={[styles.statNumber, { color: "#10b981" }]}>
                    {totalSincronizadas}
                  </Text>
                  <Ionicons name="checkmark-done" size={16} color="#10b981" />
                </View>
                <Text style={[styles.statLabel, { color: "#6ee7b7" }]}>
                  ENVIADAS
                </Text>
              </View>
            </View>

            {/* Módulo de Sincronización Offline */}
            <View style={styles.syncCard}>
              <View style={styles.syncInfo}>
                <View style={styles.syncIconRow}>
                  <Ionicons
                    name={totalPendientes > 0 ? "sync" : "cloud-done"}
                    size={22}
                    color={totalPendientes > 0 ? "#f59e0b" : "#10b981"}
                  />
                  <Text style={styles.syncTitle}>
                    {totalPendientes > 0
                      ? `${totalPendientes} Boletas Pendientes de Envío`
                      : "Almacenamiento Local Sincronizado"}
                  </Text>
                </View>
                <Text style={styles.syncSubtitle}>
                  {ultimaSincronizacion
                    ? `Última sincronización: ${new Date(
                        ultimaSincronizacion
                      ).toLocaleTimeString("es-MX", {
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                      })}`
                    : "No se ha sincronizado en este turno."}
                </Text>
              </View>

              <TouchableOpacity
                style={[
                  styles.syncButton,
                  totalPendientes === 0 && styles.syncButtonSecondary,
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

            {/* Título de Sección y Selector de Pestañas */}
            <View style={styles.historySectionHeader}>
              <Text style={styles.sectionTitle}>Historial de Infracciones</Text>
              <Text style={styles.sectionSubtitle}>
                Registro local persistente en memoria del dispositivo
              </Text>
            </View>

            <View style={styles.tabsContainer}>
              <TouchableOpacity
                style={[styles.tab, tabActual === "todas" && styles.tabActive]}
                onPress={() => setTabActual("todas")}
              >
                <Text
                  style={[
                    styles.tabText,
                    tabActual === "todas" && styles.tabTextActive,
                  ]}
                >
                  Todas ({infracciones.length})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tab,
                  tabActual === "pendientes" && styles.tabActiveAmber,
                ]}
                onPress={() => setTabActual("pendientes")}
              >
                <Text
                  style={[
                    styles.tabText,
                    tabActual === "pendientes" && styles.tabTextAmber,
                  ]}
                >
                  Pendientes ({totalPendientes})
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.tab,
                  tabActual === "sincronizadas" && styles.tabActiveGreen,
                ]}
                onPress={() => setTabActual("sincronizadas")}
              >
                <Text
                  style={[
                    styles.tabText,
                    tabActual === "sincronizadas" && styles.tabTextGreen,
                  ]}
                >
                  Sincronizadas ({totalSincronizadas})
                </Text>
              </TouchableOpacity>
            </View>
          </>
        }
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Ionicons name="document-text-outline" size={54} color="#3f3f46" />
            <Text style={styles.emptyTitle}>No hay infracciones en esta lista</Text>
            <Text style={styles.emptyDesc}>
              {tabActual === "pendientes"
                ? "Excelente, todas tus boletas han sido sincronizadas con el servidor."
                : "Presiona el botón superior para levantar la primera boleta."}
            </Text>
          </View>
        }
        renderItem={({ item }) => {
          const esPendiente = item.estado === "pendiente";

          return (
            <TouchableOpacity
              style={styles.cardItem}
              onPress={() => setInfraccionSeleccionada(item)}
              activeOpacity={0.75}
            >
              <View style={styles.cardItemHeader}>
                <View style={styles.folioBadgeWrap}>
                  <Text style={styles.cardFolio}>{item.folio}</Text>
                  <Text style={styles.cardDateTime}>
                    {item.generales.fecha} • {item.generales.hora} hrs
                  </Text>
                </View>

                <View
                  style={[
                    styles.statusBadge,
                    esPendiente
                      ? styles.statusBadgeAmber
                      : styles.statusBadgeGreen,
                  ]}
                >
                  <Ionicons
                    name={esPendiente ? "time-outline" : "checkmark-circle"}
                    size={13}
                    color={esPendiente ? "#f59e0b" : "#10b981"}
                  />
                  <Text
                    style={[
                      styles.statusBadgeText,
                      esPendiente
                        ? styles.statusBadgeTextAmber
                        : styles.statusBadgeTextGreen,
                    ]}
                  >
                    {esPendiente ? "Pendiente" : "Sincronizada"}
                  </Text>
                </View>
              </View>

              {/* Fundamento legal y descripción de la falta */}
              <View style={styles.faltaBox}>
                <Text style={styles.fundamentoTag}>
                  {item.falta?.fundamentoLegal || "Art. de Tránsito"}
                </Text>
                <Text style={styles.faltaDesc} numberOfLines={2}>
                  {item.falta?.descripcion}
                </Text>
              </View>

              {/* Datos del infractor y vehículo */}
              <View style={styles.detailsGrid}>
                <View style={styles.detailRow}>
                  <Ionicons name="car" size={15} color="#93c5fd" />
                  <Text style={styles.detailText} numberOfLines={1}>
                    {item.vehiculo.sinPlacas
                      ? "Sin placas"
                      : `Placa: ${item.vehiculo.placas}`}{" "}
                    • {item.vehiculo.marca} {item.vehiculo.lineaModelo}
                  </Text>
                </View>

                <View style={styles.detailRow}>
                  <Ionicons
                    name={item.infractor.conductorAusente ? "warning" : "person"}
                    size={15}
                    color={
                      item.infractor.conductorAusente ? "#fbbf24" : "#a1a1aa"
                    }
                  />
                  <Text style={styles.detailText} numberOfLines={1}>
                    {item.infractor.conductorAusente
                      ? "CONDUCTOR AUSENTE"
                      : item.infractor.nombre}
                  </Text>
                </View>

                <View style={styles.detailRow}>
                  <Ionicons name="location-outline" size={15} color="#a1a1aa" />
                  <Text style={styles.detailText} numberOfLines={1}>
                    {item.generales.lugar}
                  </Text>
                </View>
              </View>

              {/* Garantías Retenidas Chips */}
              {item.garantiasRetenidas && item.garantiasRetenidas.length > 0 && (
                <View style={styles.garantiasRow}>
                  <Text style={styles.garantiasLabel}>Garantía:</Text>
                  {item.garantiasRetenidas.map((g, idx) => (
                    <View key={idx} style={styles.garantiaChip}>
                      <Text style={styles.garantiaChipText}>
                        {g === "licencia"
                          ? "Licencia"
                          : g === "placa"
                          ? "Placa"
                          : g === "tarjeta_circulacion"
                          ? "Tarjeta Circ."
                          : "Vehículo"}
                      </Text>
                    </View>
                  ))}
                </View>
              )}

              {/* Miniaturas de Evidencias */}
              <View style={styles.evidenciasThumbnailsRow}>
                <View style={styles.thumbWrap}>
                  <Image
                    source={{ uri: item.evidencias.fotoPlaca }}
                    style={styles.thumbnail}
                  />
                  <Text style={styles.thumbLabel}>Placa</Text>
                </View>
                <View style={styles.thumbWrap}>
                  <Image
                    source={{ uri: item.evidencias.fotoContexto }}
                    style={styles.thumbnail}
                  />
                  <Text style={styles.thumbLabel}>Contexto</Text>
                </View>
                <View style={styles.thumbWrap}>
                  <Image
                    source={{ uri: item.evidencias.fotoDocumento }}
                    style={styles.thumbnail}
                  />
                  <Text style={styles.thumbLabel}>Documento</Text>
                </View>

                <View style={styles.verDetalleArrow}>
                  <Text style={styles.verDetalleText}>Ver Boleta</Text>
                  <Ionicons name="chevron-forward" size={16} color="#60a5fa" />
                </View>
              </View>
            </TouchableOpacity>
          );
        }}
      />

      {/* MODAL DETALLES COMPLETOS DE LA BOLETA */}
      <Modal
        visible={!!infraccionSeleccionada}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setInfraccionSeleccionada(null)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            {infraccionSeleccionada && (
              <>
                <View style={styles.modalHeader}>
                  <View>
                    <Text style={styles.modalFolio}>
                      {infraccionSeleccionada.folio}
                    </Text>
                    <Text style={styles.modalDate}>
                      {infraccionSeleccionada.generales.fecha} a las{" "}
                      {infraccionSeleccionada.generales.hora} hrs
                    </Text>
                  </View>
                  <TouchableOpacity
                    onPress={() => setInfraccionSeleccionada(null)}
                    style={styles.modalCloseBtn}
                  >
                    <Ionicons name="close" size={24} color="#ffffff" />
                  </TouchableOpacity>
                </View>

                <ScrollView style={styles.modalBody}>
                  {/* Estatus */}
                  <View
                    style={[
                      styles.modalStatusBanner,
                      infraccionSeleccionada.estado === "pendiente"
                        ? styles.statusBadgeAmber
                        : styles.statusBadgeGreen,
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
                          ? "#f59e0b"
                          : "#10b981"
                      }
                    />
                    <Text
                      style={[
                        styles.modalStatusText,
                        infraccionSeleccionada.estado === "pendiente"
                          ? { color: "#f59e0b" }
                          : { color: "#10b981" },
                      ]}
                    >
                      {infraccionSeleccionada.estado === "pendiente"
                        ? "Boleta Local en Dispositivo (Pendiente de Envío)"
                        : "Boleta Sincronizada con el Servidor Central"}
                    </Text>
                  </View>

                  {/* Sección Falta */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>
                      FUNDAMENTO Y FALTA COMETIDA
                    </Text>
                    <Text style={styles.modalFundamento}>
                      {infraccionSeleccionada.falta.fundamentoLegal}
                    </Text>
                    <Text style={styles.modalDesc}>
                      {infraccionSeleccionada.falta.descripcion}
                    </Text>
                    <Text style={styles.modalUma}>
                      Sanción sugerida: {infraccionSeleccionada.falta.montoMinUma}{" "}
                      a {infraccionSeleccionada.falta.montoMaxUma} UMA
                    </Text>
                  </View>

                  {/* Sección Motivación / Hechos */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>
                      MOTIVACIÓN DE LOS HECHOS
                    </Text>
                    <Text style={styles.modalHechos}>
                      {infraccionSeleccionada.hechos || "Sin observaciones adicionales."}
                    </Text>
                  </View>

                  {/* Sección Infractor y Vehículo */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>
                      INFRACTOR Y VEHÍCULO
                    </Text>
                    <Text style={styles.modalInfoLine}>
                      <Text style={styles.bold}>Infractor: </Text>
                      {infraccionSeleccionada.infractor.conductorAusente
                        ? "CONDUCTOR AUSENTE"
                        : infraccionSeleccionada.infractor.nombre}
                    </Text>
                    {!infraccionSeleccionada.infractor.conductorAusente && (
                      <>
                        <Text style={styles.modalInfoLine}>
                          <Text style={styles.bold}>Domicilio: </Text>
                          {infraccionSeleccionada.infractor.domicilio}
                        </Text>
                        <Text style={styles.modalInfoLine}>
                          <Text style={styles.bold}>Licencia: </Text>
                          {infraccionSeleccionada.infractor.numeroLicencia ||
                            "No presentó"}
                        </Text>
                      </>
                    )}
                    <Text style={styles.modalInfoLine}>
                      <Text style={styles.bold}>Vehículo: </Text>
                      {infraccionSeleccionada.vehiculo.marca}{" "}
                      {infraccionSeleccionada.vehiculo.lineaModelo} (
                      {infraccionSeleccionada.vehiculo.color})
                    </Text>
                    <Text style={styles.modalInfoLine}>
                      <Text style={styles.bold}>Placas: </Text>
                      {infraccionSeleccionada.vehiculo.sinPlacas
                        ? "SIN PLACAS"
                        : infraccionSeleccionada.vehiculo.placas}
                    </Text>
                  </View>

                  {/* Lugar y GPS */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>UBICACIÓN Y GPS</Text>
                    <Text style={styles.modalInfoLine}>
                      <Text style={styles.bold}>Lugar: </Text>
                      {infraccionSeleccionada.generales.lugar}
                    </Text>
                    {infraccionSeleccionada.generales.coordenadas && (
                      <Text style={styles.modalInfoLine}>
                        <Text style={styles.bold}>Coordenadas: </Text>
                        {infraccionSeleccionada.generales.coordenadas.latitud},{" "}
                        {infraccionSeleccionada.generales.coordenadas.longitud}
                      </Text>
                    )}
                  </View>

                  {/* Garantías Retenidas */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>
                      GARANTÍA RETENIDA
                    </Text>
                    <Text style={styles.modalInfoLine}>
                      {infraccionSeleccionada.garantiasRetenidas.length > 0
                        ? infraccionSeleccionada.garantiasRetenidas
                            .map((g) => g.toUpperCase())
                            .join(", ")
                        : "Ninguna garantía retenida."}
                    </Text>
                  </View>

                  {/* Evidencias Fotográficas */}
                  <View style={styles.modalSection}>
                    <Text style={styles.modalSectionTitle}>
                      EVIDENCIA FOTOGRÁFICA (3 FOTOS)
                    </Text>
                    <View style={styles.modalPhotosGrid}>
                      <View style={styles.modalPhotoItem}>
                        <Text style={styles.modalPhotoTag}>1. Placa</Text>
                        <Image
                          source={{
                            uri: infraccionSeleccionada.evidencias.fotoPlaca,
                          }}
                          style={styles.modalPhoto}
                        />
                      </View>
                      <View style={styles.modalPhotoItem}>
                        <Text style={styles.modalPhotoTag}>2. Contexto</Text>
                        <Image
                          source={{
                            uri: infraccionSeleccionada.evidencias.fotoContexto,
                          }}
                          style={styles.modalPhoto}
                        />
                      </View>
                      <View style={styles.modalPhotoItem}>
                        <Text style={styles.modalPhotoTag}>3. Documento</Text>
                        <Image
                          source={{
                            uri: infraccionSeleccionada.evidencias.fotoDocumento,
                          }}
                          style={styles.modalPhoto}
                        />
                      </View>
                    </View>
                  </View>

                  {/* Botón eliminar si es local */}
                  <TouchableOpacity
                    style={styles.modalDeleteBtn}
                    onPress={() =>
                      handleEliminarBoleta(
                        infraccionSeleccionada.id,
                        infraccionSeleccionada.folio
                      )
                    }
                  >
                    <Ionicons name="trash-outline" size={18} color="#ef4444" />
                    <Text style={styles.modalDeleteBtnText}>
                      Eliminar Boleta del Registro Local
                    </Text>
                  </TouchableOpacity>
                </ScrollView>
              </>
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
    backgroundColor: "#09090b",
  },
  topBar: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingTop: 50,
    paddingBottom: 16,
    backgroundColor: "#121214",
    borderBottomWidth: 1,
    borderBottomColor: "#27272a",
  },
  agentInfoWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    flex: 1,
  },
  agentAvatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderColor: "#3b82f6",
    justifyContent: "center",
    alignItems: "center",
  },
  badgeRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 2,
  },
  placaPill: {
    backgroundColor: "#2563eb",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  placaText: {
    color: "#ffffff",
    fontWeight: "800",
    fontSize: 11,
    letterSpacing: 0.5,
  },
  statusPill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    backgroundColor: "rgba(16, 185, 129, 0.15)",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: "rgba(16, 185, 129, 0.3)",
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: "#10b981",
  },
  statusText: {
    color: "#34d399",
    fontSize: 10,
    fontWeight: "700",
  },
  agentName: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "700",
    maxWidth: 220,
  },
  logoutButton: {
    padding: 10,
    borderRadius: 10,
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#27272a",
  },
  listContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  heroCard: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 14,
    padding: 16,
    marginBottom: 16,
  },
  heroTextCol: {
    flex: 1,
  },
  municipioHeader: {
    color: "#60a5fa",
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 1.5,
  },
  heroTitle: {
    color: "#ffffff",
    fontSize: 17,
    fontWeight: "800",
    marginTop: 2,
  },
  heroSubtitle: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 2,
  },
  badgeSeal: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: "#1e293b",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#334155",
  },
  newInfractionButton: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#2563eb",
    borderRadius: 16,
    paddingVertical: 18,
    paddingHorizontal: 18,
    marginBottom: 16,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.45,
    shadowRadius: 10,
    elevation: 8,
  },
  btnIconWrap: {
    marginRight: 14,
  },
  btnTextWrap: {
    flex: 1,
  },
  btnMainTitle: {
    color: "#ffffff",
    fontSize: 16,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  btnSubTitle: {
    color: "#bfdbfe",
    fontSize: 12,
    marginTop: 2,
  },
  statsRow: {
    flexDirection: "row",
    gap: 10,
    marginBottom: 16,
  },
  statBox: {
    flex: 1,
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 12,
    padding: 12,
    alignItems: "center",
  },
  statBoxAmber: {
    borderColor: "rgba(245, 158, 11, 0.3)",
    backgroundColor: "rgba(245, 158, 11, 0.08)",
  },
  statBoxGreen: {
    borderColor: "rgba(16, 185, 129, 0.3)",
    backgroundColor: "rgba(16, 185, 129, 0.08)",
  },
  statHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  statNumber: {
    fontSize: 22,
    fontWeight: "800",
    color: "#ffffff",
  },
  statLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#a1a1aa",
    marginTop: 4,
    letterSpacing: 0.5,
  },
  syncCard: {
    backgroundColor: "#121214",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 14,
    padding: 16,
    marginBottom: 20,
    gap: 12,
  },
  syncInfo: {},
  syncIconRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  syncTitle: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "700",
  },
  syncSubtitle: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 3,
  },
  syncButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#d97706",
    paddingVertical: 12,
    borderRadius: 10,
    gap: 8,
  },
  syncButtonSecondary: {
    backgroundColor: "#27272a",
  },
  syncButtonText: {
    color: "#ffffff",
    fontSize: 13,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  historySectionHeader: {
    marginBottom: 12,
  },
  sectionTitle: {
    color: "#ffffff",
    fontSize: 18,
    fontWeight: "800",
  },
  sectionSubtitle: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 2,
  },
  tabsContainer: {
    flexDirection: "row",
    backgroundColor: "#18181b",
    borderRadius: 10,
    padding: 4,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#27272a",
  },
  tab: {
    flex: 1,
    paddingVertical: 9,
    alignItems: "center",
    borderRadius: 8,
  },
  tabActive: {
    backgroundColor: "#2563eb",
  },
  tabActiveAmber: {
    backgroundColor: "#b45309",
  },
  tabActiveGreen: {
    backgroundColor: "#059669",
  },
  tabText: {
    color: "#a1a1aa",
    fontSize: 12,
    fontWeight: "600",
  },
  tabTextActive: {
    color: "#ffffff",
    fontWeight: "800",
  },
  tabTextAmber: {
    color: "#ffffff",
    fontWeight: "800",
  },
  tabTextGreen: {
    color: "#ffffff",
    fontWeight: "800",
  },
  emptyContainer: {
    alignItems: "center",
    paddingVertical: 48,
    paddingHorizontal: 24,
  },
  emptyTitle: {
    color: "#ffffff",
    fontSize: 16,
    fontWeight: "700",
    marginTop: 12,
  },
  emptyDesc: {
    color: "#71717a",
    fontSize: 13,
    textAlign: "center",
    marginTop: 6,
    lineHeight: 18,
  },
  cardItem: {
    backgroundColor: "#121214",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    shadowColor: "#000000",
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 4,
  },
  cardItemHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    marginBottom: 10,
  },
  folioBadgeWrap: {},
  cardFolio: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  cardDateTime: {
    color: "#a1a1aa",
    fontSize: 11,
    marginTop: 2,
  },
  statusBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  statusBadgeAmber: {
    backgroundColor: "rgba(245, 158, 11, 0.15)",
    borderWidth: 1,
    borderColor: "rgba(245, 158, 11, 0.4)",
  },
  statusBadgeGreen: {
    backgroundColor: "rgba(16, 185, 129, 0.15)",
    borderWidth: 1,
    borderColor: "rgba(16, 185, 129, 0.4)",
  },
  statusBadgeText: {
    fontSize: 11,
    fontWeight: "700",
  },
  statusBadgeTextAmber: {
    color: "#f59e0b",
  },
  statusBadgeTextGreen: {
    color: "#10b981",
  },
  faltaBox: {
    backgroundColor: "#18181b",
    padding: 10,
    borderRadius: 8,
    marginBottom: 10,
    borderLeftWidth: 3,
    borderLeftColor: "#3b82f6",
  },
  fundamentoTag: {
    color: "#60a5fa",
    fontSize: 12,
    fontWeight: "800",
  },
  faltaDesc: {
    color: "#e4e4e7",
    fontSize: 13,
    marginTop: 2,
    lineHeight: 18,
  },
  detailsGrid: {
    gap: 6,
    marginBottom: 10,
  },
  detailRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  detailText: {
    color: "#d4d4d8",
    fontSize: 12,
    flex: 1,
  },
  garantiasRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    flexWrap: "wrap",
    marginBottom: 10,
  },
  garantiasLabel: {
    color: "#a1a1aa",
    fontSize: 11,
    fontWeight: "700",
  },
  garantiaChip: {
    backgroundColor: "#27272a",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  garantiaChipText: {
    color: "#f4f4f5",
    fontSize: 11,
    fontWeight: "600",
  },
  evidenciasThumbnailsRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: "#27272a",
  },
  thumbWrap: {
    alignItems: "center",
  },
  thumbnail: {
    width: 48,
    height: 48,
    borderRadius: 6,
    backgroundColor: "#27272a",
  },
  thumbLabel: {
    color: "#a1a1aa",
    fontSize: 9,
    marginTop: 2,
  },
  verDetalleArrow: {
    marginLeft: "auto",
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  verDetalleText: {
    color: "#60a5fa",
    fontSize: 12,
    fontWeight: "700",
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.85)",
    justifyContent: "flex-end",
  },
  modalContent: {
    backgroundColor: "#121214",
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: "90%",
    borderWidth: 1,
    borderColor: "#27272a",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: "#27272a",
  },
  modalFolio: {
    color: "#ffffff",
    fontSize: 18,
    fontWeight: "900",
  },
  modalDate: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 2,
  },
  modalCloseBtn: {
    padding: 6,
    backgroundColor: "#27272a",
    borderRadius: 20,
  },
  modalBody: {
    padding: 20,
  },
  modalStatusBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  modalStatusText: {
    fontSize: 13,
    fontWeight: "700",
    flex: 1,
  },
  modalSection: {
    marginBottom: 18,
    backgroundColor: "#18181b",
    padding: 14,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#27272a",
  },
  modalSectionTitle: {
    color: "#60a5fa",
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 1,
    marginBottom: 8,
  },
  modalFundamento: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
  },
  modalDesc: {
    color: "#d4d4d8",
    fontSize: 13,
    marginTop: 4,
    lineHeight: 18,
  },
  modalUma: {
    color: "#f59e0b",
    fontSize: 12,
    fontWeight: "700",
    marginTop: 6,
  },
  modalHechos: {
    color: "#e4e4e7",
    fontSize: 13,
    lineHeight: 20,
  },
  modalInfoLine: {
    color: "#e4e4e7",
    fontSize: 13,
    marginBottom: 4,
  },
  bold: {
    fontWeight: "700",
    color: "#ffffff",
  },
  modalPhotosGrid: {
    flexDirection: "row",
    gap: 8,
  },
  modalPhotoItem: {
    flex: 1,
    alignItems: "center",
  },
  modalPhotoTag: {
    color: "#a1a1aa",
    fontSize: 10,
    fontWeight: "700",
    marginBottom: 4,
  },
  modalPhoto: {
    width: "100%",
    height: 100,
    borderRadius: 8,
    backgroundColor: "#27272a",
  },
  modalDeleteBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: "rgba(239, 68, 68, 0.1)",
    borderWidth: 1,
    borderColor: "rgba(239, 68, 68, 0.3)",
    paddingVertical: 14,
    borderRadius: 10,
    marginTop: 10,
    marginBottom: 40,
  },
  modalDeleteBtnText: {
    color: "#ef4444",
    fontSize: 13,
    fontWeight: "700",
  },
});
