import React, { useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useRouter } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "../src/contexts/AuthContext";
import { useInfracciones } from "../src/contexts/InfraccionesContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { AgentTabBar } from "../src/components/AgentTabBar";
import { AgentScreenHeader } from "../src/components/AgentScreenHeader";
import { AgentInfraccionDetailModal } from "../src/components/AgentInfraccionDetailModal";
import { ThemeToggle } from "../src/components/ThemeToggle";
import { Infraccion, TipoVehiculo } from "../src/types/infraccion";

type FiltroEstado = "todos" | Infraccion["estado"];
type FiltroPeriodo = "todas" | "hoy" | "semana" | "personalizado";
type TipoVehiculoFiltro = "todos" | TipoVehiculo;

const filtrosEstado: { value: FiltroEstado; label: string }[] = [
  { value: "todos", label: "Todos" },
  { value: "sincronizada", label: "Sincronizadas" },
  { value: "pendiente", label: "Pendientes" },
  { value: "error_sincronizacion", label: "Con error" },
];

const filtrosPeriodo: { value: Exclude<FiltroPeriodo, "personalizado">; label: string }[] = [
  { value: "todas", label: "Todas" },
  { value: "hoy", label: "Hoy" },
  { value: "semana", label: "Esta semana" },
];

const tiposVehiculo: { value: TipoVehiculo; label: string }[] = [
  { value: "particular", label: "Particular" },
  { value: "transporte_publico", label: "Transporte público" },
  { value: "carga", label: "Carga" },
  { value: "motocicleta", label: "Motocicleta" },
  { value: "otro", label: "Otro" },
];

const etiquetasVehiculo: Record<TipoVehiculo, string> = {
  particular: "Particular",
  transporte_publico: "Transporte público",
  carga: "Carga",
  motocicleta: "Motocicleta",
  otro: "Otro",
};

function fechaLocalIso(fecha: Date): string {
  return [
    fecha.getFullYear(),
    String(fecha.getMonth() + 1).padStart(2, "0"),
    String(fecha.getDate()).padStart(2, "0"),
  ].join("-");
}

function fechaIsoValida(fecha: string): boolean {
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

function obtenerFechaCreacion(item: Infraccion): number {
  const creadoEn = Date.parse(item.creadoEn);
  return Number.isNaN(creadoEn)
    ? Date.parse(`${item.generales.fecha}T${item.generales.hora}:00`)
    : creadoEn;
}

function estadoBoleta(estado: Infraccion["estado"]) {
  if (estado === "pendiente") {
    return { label: "Pendiente de sincronización", icon: "cloud-upload-outline" as const };
  }
  if (estado === "sincronizada") {
    return { label: "Sincronizada", icon: "cloud-done-outline" as const };
  }
  return { label: "Error de sincronización", icon: "alert-circle-outline" as const };
}

export default function BoletasScreen() {
  const router = useRouter();
  const { user, isLoading: authLoading } = useAuth();
  const { infracciones, isLoading, valorUma, eliminar } = useInfracciones();
  const { colors } = useTheme();
  const [busqueda, setBusqueda] = useState("");
  const [filtroEstado, setFiltroEstado] = useState<FiltroEstado>("todos");
  const [filtroPeriodo, setFiltroPeriodo] = useState<FiltroPeriodo>("todas");
  const [tipoVehiculo, setTipoVehiculo] = useState<TipoVehiculoFiltro>("todos");
  const [fechaDesde, setFechaDesde] = useState("");
  const [fechaHasta, setFechaHasta] = useState("");
  const [seleccionada, setSeleccionada] = useState<Infraccion | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.replace("/");
  }, [authLoading, user, router]);

  const errorFechas = useMemo(() => {
    if (fechaDesde && !fechaIsoValida(fechaDesde)) return "Escribe Desde con formato AAAA-MM-DD y una fecha válida.";
    if (fechaHasta && !fechaIsoValida(fechaHasta)) return "Escribe Hasta con formato AAAA-MM-DD y una fecha válida.";
    if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
      return "La fecha Desde no puede ser posterior a Hasta.";
    }
    return null;
  }, [fechaDesde, fechaHasta]);

  const fechasPersonalizadasActivas = Boolean(fechaDesde || fechaHasta);
  const rangoSemana = useMemo(() => {
    const hoy = new Date();
    const lunes = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    lunes.setDate(lunes.getDate() - ((lunes.getDay() + 6) % 7));
    const domingo = new Date(lunes);
    domingo.setDate(lunes.getDate() + 6);
    return { desde: fechaLocalIso(lunes), hasta: fechaLocalIso(domingo) };
  }, []);
  const fechaHoy = fechaLocalIso(new Date());

  const listaFiltrada = useMemo(() => {
    const termino = busqueda.trim().toLocaleLowerCase();

    return [...infracciones]
      .filter((item) => filtroEstado === "todos" || item.estado === filtroEstado)
      .filter((item) => tipoVehiculo === "todos" || item.vehiculo.tipo === tipoVehiculo)
      .filter((item) => !termino || item.folio.toLocaleLowerCase().includes(termino))
      .filter((item) => {
        const fecha = item.generales.fecha;
        if (fechasPersonalizadasActivas) {
          if (errorFechas || !fechaIsoValida(fecha)) return false;
          return (!fechaDesde || fecha >= fechaDesde) && (!fechaHasta || fecha <= fechaHasta);
        }
        if (filtroPeriodo === "hoy") return fechaIsoValida(fecha) && fecha === fechaHoy;
        if (filtroPeriodo === "semana") {
          return fechaIsoValida(fecha) && fecha >= rangoSemana.desde && fecha <= rangoSemana.hasta;
        }
        return true;
      })
      .sort((a, b) => obtenerFechaCreacion(b) - obtenerFechaCreacion(a));
  }, [
    infracciones,
    filtroEstado,
    tipoVehiculo,
    busqueda,
    fechasPersonalizadasActivas,
    errorFechas,
    fechaDesde,
    fechaHasta,
    filtroPeriodo,
    fechaHoy,
    rangoSemana,
  ]);

  const totalPorSincronizar = infracciones.filter(
    (item) => item.estado === "pendiente" || item.estado === "error_sincronizacion"
  ).length;

  const seleccionarPeriodo = (periodo: FiltroPeriodo) => {
    setFiltroPeriodo(periodo);
    setFechaDesde("");
    setFechaHasta("");
  };

  const limpiarFiltros = () => {
    setBusqueda("");
    setFiltroEstado("todos");
    setFiltroPeriodo("todas");
    setTipoVehiculo("todos");
    setFechaDesde("");
    setFechaHasta("");
  };

  const renderBoleta = ({ item }: { item: Infraccion }) => {
    const status = estadoBoleta(item.estado);
    const statusColor =
      item.estado === "pendiente"
        ? colors.warning
        : item.estado === "sincronizada"
          ? colors.success
          : colors.danger;

    return (
      <TouchableOpacity
        style={[styles.ticket, { backgroundColor: colors.surface, borderColor: colors.border }]}
        onPress={() => setSeleccionada(item)}
        activeOpacity={0.75}
        accessibilityLabel={`Ver detalle de la boleta ${item.folio}`}
      >
        <View style={[styles.ticketIcon, { backgroundColor: colors.primaryBg }]}>
          <Ionicons name="document-text-outline" size={20} color={colors.primary} />
        </View>
        <View style={styles.ticketContent}>
          <View style={styles.ticketHeading}>
            <Text style={[styles.folio, { color: colors.text }]} numberOfLines={1}>
              {item.folio}
            </Text>
            <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
          </View>
          <Text style={[styles.date, { color: colors.textSecondary }]}>
            {item.generales.fecha} · {item.generales.hora}
          </Text>
          <View style={styles.ticketFooter}>
            <View style={styles.vehicleInfo}>
              <Text style={[styles.meta, { color: colors.textSecondary }]}>
                {etiquetasVehiculo[item.vehiculo.tipo]} · {item.vehiculo.sinPlacas ? "Sin placas" : item.vehiculo.placas}
              </Text>
              {typeof item.faltas?.length === "number" && (
                <Text style={[styles.meta, { color: colors.textMuted }]}>
                  {item.faltas.length} {item.faltas.length === 1 ? "falta" : "faltas"}
                </Text>
              )}
            </View>
            <View style={styles.status}>
              <Ionicons name={status.icon} size={15} color={statusColor} />
              <Text style={[styles.statusText, { color: statusColor }]}>{status.label}</Text>
            </View>
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  if (authLoading || !user) {
    return (
      <View style={[styles.loading, { backgroundColor: colors.background }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <AgentScreenHeader actions={<ThemeToggle compact />} />

      <FlatList
        data={listaFiltrada}
        keyExtractor={(item) => item.id}
        renderItem={renderBoleta}
        contentContainerStyle={styles.list}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        ListHeaderComponent={
          <View style={styles.controls}>
            <View style={styles.screenHeading}>
              <Text style={[styles.screenTitle, { color: colors.text }]}>Boletas</Text>
              <Text style={[styles.screenSubtitle, { color: colors.textSecondary }]}>
                Consulta el historial disponible en este dispositivo.
              </Text>
            </View>

            <View style={styles.metricsRow}>
              <View style={[styles.metric, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[styles.metricLabel, { color: colors.textSecondary }]}>Total registradas</Text>
                <Text style={[styles.metricValue, { color: colors.text }]}>{infracciones.length}</Text>
              </View>
              <View style={[styles.metric, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <Text style={[styles.metricLabel, { color: colors.textSecondary }]}>Por sincronizar</Text>
                <Text style={[styles.metricValue, { color: totalPorSincronizar > 0 ? colors.warning : colors.success }]}>
                  {totalPorSincronizar}
                </Text>
              </View>
            </View>

            <View
              style={[
                styles.searchBox,
                { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
              ]}
            >
              <Ionicons name="search-outline" size={19} color={colors.textMuted} />
              <TextInput
                value={busqueda}
                onChangeText={setBusqueda}
                placeholder="Buscar por folio..."
                placeholderTextColor={colors.textMuted}
                autoCapitalize="none"
                returnKeyType="search"
                accessibilityLabel="Buscar boletas por folio"
                style={[styles.searchInput, { color: colors.text }]}
              />
              {!!busqueda && (
                <TouchableOpacity
                  onPress={() => setBusqueda("")}
                  accessibilityLabel="Limpiar búsqueda"
                  hitSlop={8}
                >
                  <Ionicons name="close-circle" size={19} color={colors.textMuted} />
                </TouchableOpacity>
              )}
            </View>

            <View style={styles.filterHeading}>
              <Text style={[styles.filterTitle, { color: colors.text }]}>Filtros</Text>
              <TouchableOpacity onPress={limpiarFiltros} accessibilityRole="button">
                <Text style={[styles.clearFilters, { color: colors.primary }]}>Limpiar</Text>
              </TouchableOpacity>
            </View>

            <Text style={[styles.groupLabel, { color: colors.textMuted }]}>PERIODO</Text>
            <View style={styles.chipGroup}>
              {filtrosPeriodo.map((filter) => {
                const selected = !fechasPersonalizadasActivas && filtroPeriodo === filter.value;
                return (
                  <TouchableOpacity
                    key={filter.value}
                    onPress={() => seleccionarPeriodo(filter.value)}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    style={[
                      styles.filterChip,
                      {
                        backgroundColor: selected ? colors.primary : colors.surface,
                        borderColor: selected ? colors.primary : colors.border,
                      },
                    ]}
                  >
                    <Text style={[styles.chipText, { color: selected ? colors.background : colors.textSecondary }]}>
                      {filter.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <Text style={[styles.groupLabel, { color: colors.textMuted }]}>ESTADO DE SINCRONIZACIÓN</Text>
            <View style={styles.chipGroup}>
              {filtrosEstado.map((filter) => {
                const selected = filtroEstado === filter.value;
                return (
                  <TouchableOpacity
                    key={filter.value}
                    onPress={() => setFiltroEstado(filter.value)}
                    accessibilityRole="button"
                    accessibilityState={{ selected }}
                    style={[
                      styles.filterChip,
                      {
                        backgroundColor: selected ? colors.primary : colors.surface,
                        borderColor: selected ? colors.primary : colors.border,
                      },
                    ]}
                  >
                    <Text style={[styles.chipText, { color: selected ? colors.background : colors.textSecondary }]}>
                      {filter.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            <View style={styles.vehicleFilter}>
              <Text style={[styles.groupLabel, { color: colors.textMuted }]}>TIPO DE VEHÍCULO</Text>
              <View style={styles.chipGroup}>
                <TouchableOpacity
                  onPress={() => setTipoVehiculo("todos")}
                  accessibilityRole="button"
                  accessibilityState={{ selected: tipoVehiculo === "todos" }}
                  style={[
                    styles.filterChip,
                    {
                      backgroundColor: tipoVehiculo === "todos" ? colors.primary : colors.surface,
                      borderColor: tipoVehiculo === "todos" ? colors.primary : colors.border,
                    },
                  ]}
                >
                  <Text style={[styles.chipText, { color: tipoVehiculo === "todos" ? colors.background : colors.textSecondary }]}>
                    Todos los vehículos
                  </Text>
                </TouchableOpacity>
                {tiposVehiculo.map((type) => {
                  const selected = tipoVehiculo === type.value;
                  return (
                    <TouchableOpacity
                      key={type.value}
                      onPress={() => setTipoVehiculo(type.value)}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      style={[
                        styles.filterChip,
                        {
                          backgroundColor: selected ? colors.primary : colors.surface,
                          borderColor: selected ? colors.primary : colors.border,
                        },
                      ]}
                    >
                      <Text style={[styles.chipText, { color: selected ? colors.background : colors.textSecondary }]}>
                        {type.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </View>

            <View style={[styles.customDateCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.customDateHeading}>
                <Text style={[styles.groupLabel, styles.customDateLabel, { color: colors.textMuted }]}>
                  FECHAS PERSONALIZADAS
                </Text>
                <Ionicons name="calendar-outline" size={17} color={colors.textSecondary} />
              </View>
              <View style={styles.dateInputs}>
                <View style={styles.dateInputGroup}>
                  <Text style={[styles.dateInputLabel, { color: colors.textSecondary }]}>Desde</Text>
                  <TextInput
                    value={fechaDesde}
                    onChangeText={(value) => {
                      setFiltroPeriodo("personalizado");
                      setFechaDesde(value);
                    }}
                    placeholder="AAAA-MM-DD"
                    placeholderTextColor={colors.textMuted}
                    accessibilityLabel="Fecha Desde"
                    maxLength={10}
                    keyboardType="numbers-and-punctuation"
                    style={[
                      styles.dateInput,
                      {
                        color: colors.text,
                        backgroundColor: colors.inputBg,
                        borderColor: errorFechas && fechaDesde ? colors.danger : colors.inputBorder,
                      },
                    ]}
                  />
                </View>
                <View style={styles.dateInputGroup}>
                  <Text style={[styles.dateInputLabel, { color: colors.textSecondary }]}>Hasta</Text>
                  <TextInput
                    value={fechaHasta}
                    onChangeText={(value) => {
                      setFiltroPeriodo("personalizado");
                      setFechaHasta(value);
                    }}
                    placeholder="AAAA-MM-DD"
                    placeholderTextColor={colors.textMuted}
                    accessibilityLabel="Fecha Hasta"
                    maxLength={10}
                    keyboardType="numbers-and-punctuation"
                    style={[
                      styles.dateInput,
                      {
                        color: colors.text,
                        backgroundColor: colors.inputBg,
                        borderColor: errorFechas && fechaHasta ? colors.danger : colors.inputBorder,
                      },
                    ]}
                  />
                </View>
              </View>
              {errorFechas && (
                <Text accessibilityRole="alert" style={[styles.dateError, { color: colors.danger }]}>
                  {errorFechas}
                </Text>
              )}
            </View>

            <View style={[styles.resultsHeading, { borderTopColor: colors.border }]}>
              <Text style={[styles.resultsTitle, { color: colors.text }]}>Resultados</Text>
              <Text style={[styles.resultsCount, { color: colors.textSecondary }]}>
                {listaFiltrada.length} {listaFiltrada.length === 1 ? "boleta" : "boletas"}
              </Text>
            </View>
          </View>
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            {isLoading ? (
              <ActivityIndicator color={colors.primary} />
            ) : (
              <>
                <Ionicons name="document-text-outline" size={26} color={colors.textMuted} />
                <Text style={[styles.emptyText, { color: colors.textSecondary }]}>
                  {infracciones.length === 0
                    ? "Aún no hay boletas registradas en este dispositivo."
                    : errorFechas
                      ? "Corrige las fechas para consultar resultados."
                      : "No hay boletas que coincidan con la búsqueda o los filtros."}
                </Text>
              </>
            )}
          </View>
        }
      />

      <AgentTabBar selected="boletas" />

      <AgentInfraccionDetailModal
        infraccion={seleccionada}
        valorUma={valorUma}
        onClose={() => setSeleccionada(null)}
        onDiscard={eliminar}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  loading: { flex: 1, alignItems: "center", justifyContent: "center" },
  list: {
    width: "100%",
    maxWidth: 720,
    alignSelf: "center",
    paddingHorizontal: 16,
    paddingBottom: 18,
    flexGrow: 1,
  },
  controls: { paddingTop: 18, paddingBottom: 4 },
  screenHeading: { marginBottom: 14 },
  screenTitle: { fontSize: 21, fontWeight: "800" },
  screenSubtitle: { fontSize: 11, lineHeight: 16, marginTop: 3 },
  metricsRow: { flexDirection: "row", gap: 10, marginBottom: 14 },
  metric: { flex: 1, minWidth: 0, borderWidth: 1, borderRadius: 12, padding: 12 },
  metricLabel: { fontSize: 11, fontWeight: "600" },
  metricValue: { fontSize: 22, lineHeight: 27, fontWeight: "800", marginTop: 2 },
  searchBox: {
    minHeight: 48,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 13,
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 18,
  },
  searchInput: { flex: 1, minWidth: 0, minHeight: 44, fontSize: 14 },
  filterHeading: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 9 },
  filterTitle: { fontSize: 15, fontWeight: "800" },
  clearFilters: { fontSize: 12, fontWeight: "700", paddingVertical: 4 },
  groupLabel: { fontSize: 10, fontWeight: "700", letterSpacing: 0.35, marginBottom: 7 },
  chipGroup: { flexDirection: "row", flexWrap: "wrap", gap: 7, marginBottom: 12 },
  filterChip: { minHeight: 34, justifyContent: "center", paddingHorizontal: 12, borderWidth: 1, borderRadius: 18 },
  chipText: { fontSize: 11, fontWeight: "700" },
  vehicleFilter: { marginTop: 1 },
  customDateCard: { borderWidth: 1, borderRadius: 12, padding: 12, marginTop: 1 },
  customDateHeading: { flexDirection: "row", alignItems: "center", justifyContent: "space-between" },
  customDateLabel: { marginBottom: 10 },
  dateInputs: { flexDirection: "row", gap: 10 },
  dateInputGroup: { flex: 1, minWidth: 0 },
  dateInputLabel: { fontSize: 10, fontWeight: "600", marginBottom: 5 },
  dateInput: { minHeight: 40, borderWidth: 1, borderRadius: 9, paddingHorizontal: 10, fontSize: 12 },
  dateError: { fontSize: 11, lineHeight: 16, marginTop: 8 },
  resultsHeading: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    borderTopWidth: StyleSheet.hairlineWidth,
    paddingTop: 15,
    marginTop: 17,
    marginBottom: 12,
  },
  resultsTitle: { fontSize: 15, fontWeight: "800" },
  resultsCount: { fontSize: 11, fontWeight: "600" },
  ticket: { flexDirection: "row", alignItems: "flex-start", gap: 11, borderWidth: 1, borderRadius: 13, padding: 13, marginBottom: 9 },
  ticketIcon: { width: 36, height: 36, borderRadius: 10, alignItems: "center", justifyContent: "center", flexShrink: 0 },
  ticketContent: { flex: 1, minWidth: 0 },
  ticketHeading: { flexDirection: "row", alignItems: "center", gap: 5 },
  folio: { flex: 1, minWidth: 0, fontSize: 12, fontWeight: "800" },
  date: { fontSize: 11, marginTop: 4 },
  ticketFooter: { flexDirection: "row", flexWrap: "wrap", justifyContent: "space-between", alignItems: "center", gap: 7, marginTop: 9 },
  vehicleInfo: { flexDirection: "row", flexWrap: "wrap", gap: 9, flex: 1, minWidth: 0 },
  meta: { fontSize: 10, fontWeight: "600" },
  status: { flexDirection: "row", alignItems: "center", gap: 4, flexShrink: 0 },
  statusText: { fontSize: 10, fontWeight: "700" },
  empty: { minHeight: 150, alignItems: "center", justifyContent: "center", gap: 10, paddingHorizontal: 24 },
  emptyText: { fontSize: 13, lineHeight: 19, textAlign: "center" },
});
