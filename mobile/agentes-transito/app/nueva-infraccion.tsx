import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  ScrollView,
  Alert,
  Modal,
  Image,
  Switch,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { useRouter } from "expo-router";
import * as Location from "expo-location";
import { useAuth } from "../src/contexts/AuthContext";
import { useInfracciones } from "../src/contexts/InfraccionesContext";
import { useTheme } from "../src/contexts/ThemeContext";
import { ThemeToggle } from "../src/components/ThemeToggle";
import {
  FaltaCatalogo,
  GarantiaRetenida,
  Infraccion,
  TipoVehiculo,
} from "../src/types/infraccion";
import { Ionicons } from "@expo/vector-icons";

type TipoFoto = "placa" | "contexto" | "documento";

export default function NuevaInfraccionScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const { guardarInfraccion, valorUma, catalogo } = useInfracciones();
  const { colors, isDark } = useTheme();

  // Generación de Folio Oficial Único
  const [folio] = useState(() => {
    const numRandom = Math.floor(1000 + Math.random() * 9000);
    return `BOLETA-URI-2026-${numRandom}`;
  });

  // Generales
  const [fecha, setFecha] = useState("");
  const [hora, setHora] = useState("");
  const [lugar, setLugar] = useState("");
  const [coordenadas, setCoordenadas] = useState<{
    latitud: number;
    longitud: number;
  } | null>(null);
  const [buscandoGps, setBuscandoGps] = useState(false);

  // Infractor
  const [conductorAusente, setConductorAusente] = useState(false);
  const [nombreInfractor, setNombreInfractor] = useState("");
  const [domicilioInfractor, setDomicilioInfractor] = useState("");
  const [licenciaInfractor, setLicenciaInfractor] = useState("");

  // Vehículo
  const [placas, setPlacas] = useState("");
  const [sinPlacas, setSinPlacas] = useState(false);
  const [marca, setMarca] = useState("");
  const [lineaModelo, setLineaModelo] = useState("");
  const [color, setColor] = useState("");
  const [tipoVehiculo, setTipoVehiculo] = useState<TipoVehiculo>("particular");

  // Falta seleccionada del catálogo
  const [faltaSeleccionada, setFaltaSeleccionada] = useState<FaltaCatalogo>(
    catalogo[0] || {
      id: "f-1",
      fundamentoLegal: "Art. 39 Frac. IX",
      descripcion: "No obedecer la señal de alto cuando la luz del semáforo esté en rojo",
      categoria: "MANEJO Y VIALIDAD",
      montoMinUma: 15,
      montoMaxUma: 25,
    }
  );
  const [modalCatalogoVisible, setModalCatalogoVisible] = useState(false);
  const [busquedaCatalogo, setBusquedaCatalogo] = useState("");
  const [categoriaFiltro, setCategoriaFiltro] = useState<string>("todas");

  // Hechos / Observaciones
  const [hechos, setHechos] = useState("");

  // Garantías Retenidas
  const [garantias, setGarantias] = useState<GarantiaRetenida[]>([]);
  const [inventarioGrua, setInventarioGrua] = useState("");

  // Evidencias Fotográficas
  const [fotoPlaca, setFotoPlaca] = useState<string | null>(null);
  const [fotoContexto, setFotoContexto] = useState<string | null>(null);
  const [fotoDocumento, setFotoDocumento] = useState<string | null>(null);
  const [capturandoTipoFoto, setCapturandoTipoFoto] = useState<TipoFoto | null>(null);
  const [modalCamaraVisible, setModalCamaraVisible] = useState(false);
  const [fotoPreviewGrande, setFotoPreviewGrande] = useState<string | null>(null);
  const [guardando, setGuardando] = useState(false);

  // Precargar fecha, hora y GPS al abrir
  useEffect(() => {
    const ahora = new Date();
    const yyyy = ahora.getFullYear();
    const mm = String(ahora.getMonth() + 1).padStart(2, "0");
    const dd = String(ahora.getDate()).padStart(2, "0");
    const hh = String(ahora.getHours()).padStart(2, "0");
    const min = String(ahora.getMinutes()).padStart(2, "0");

    setFecha(`${yyyy}-${mm}-${dd}`);
    setHora(`${hh}:${min}`);

    obtenerUbicacionGps();
  }, []);

  const obtenerUbicacionGps = async () => {
    setBuscandoGps(true);
    try {
      let { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== "granted") {
        setCoordenadas({ latitud: 20.1419, longitud: -101.1764 });
        if (!lugar) {
          setLugar("Av. Hidalgo esq. Juárez, Zona Centro, Uriangato, Gto.");
        }
        return;
      }

      const loc = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
      });

      setCoordenadas({
        latitud: loc.coords.latitude,
        longitud: loc.coords.longitude,
      });

      try {
        const reverse = await Location.reverseGeocodeAsync({
          latitude: loc.coords.latitude,
          longitude: loc.coords.longitude,
        });

        if (reverse && reverse.length > 0) {
          const r = reverse[0];
          const calle = r.street || "Vialidad Urbana";
          const col = r.district || r.subregion || "Zona Centro";
          setLugar(`${calle}, ${col}, Uriangato, Gto.`);
        }
      } catch {
        if (!lugar) {
          setLugar("Sector Centro, Uriangato, Gto.");
        }
      }
    } catch {
      setCoordenadas({ latitud: 20.1419, longitud: -101.1764 });
      if (!lugar) {
        setLugar("Prolongación Morelos, Uriangato, Gto.");
      }
    } finally {
      setBuscandoGps(false);
    }
  };

  const toggleGarantia = (tipo: GarantiaRetenida) => {
    if (garantias.includes(tipo)) {
      setGarantias(garantias.filter((g) => g !== tipo));
    } else {
      setGarantias([...garantias, tipo]);
    }
  };

  // Simulación y captura de foto
  const simularCapturaFoto = (tipo: TipoFoto) => {
    const urlsMuestra: Record<TipoFoto, string> = {
      placa: "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&q=80",
      contexto: "https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=600&q=80",
      documento: "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=600&q=80",
    };

    if (tipo === "placa") setFotoPlaca(urlsMuestra.placa);
    if (tipo === "contexto") setFotoContexto(urlsMuestra.contexto);
    if (tipo === "documento") setFotoDocumento(urlsMuestra.documento);

    setModalCamaraVisible(false);
    setCapturandoTipoFoto(null);
  };

  // Llenar las 3 fotos demo con 1 click
  const handleCargarFotosDemo = () => {
    setFotoPlaca("https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&q=80");
    setFotoContexto("https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=600&q=80");
    setFotoDocumento("https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=600&q=80");
    Alert.alert("Fotos Cargadas", "Se agregaron las 3 evidencias fotográficas de demostración.");
  };

  const totalFotosListas = (fotoPlaca ? 1 : 0) + (fotoContexto ? 1 : 0) + (fotoDocumento ? 1 : 0);

  const handleGuardarBoleta = async () => {
    if (!lugar.trim()) {
      Alert.alert("Falta Ubicación", "Debe registrar la ubicación o calle de la infracción.");
      return;
    }

    if (!sinPlacas && !placas.trim()) {
      Alert.alert("Falta Placa", "Ingrese la placa del vehículo o marque la casilla 'Vehículo Sin Placas'.");
      return;
    }

    if (!marca.trim()) {
      Alert.alert("Falta Marca", "Ingrese la marca del vehículo intervenido.");
      return;
    }

    if (!conductorAusente && !nombreInfractor.trim()) {
      Alert.alert("Falta Conductor", "Ingrese el nombre del conductor o active la casilla 'Conductor Ausente en el Sitio'.");
      return;
    }

    if (!fotoPlaca || !fotoContexto || !fotoDocumento) {
      Alert.alert(
        "Evidencias Incompletas",
        "El reglamento exige las 3 fotos de evidencia:\n1. Placa\n2. Contexto de la falta\n3. Garantía o Documento\n\nPuede presionar 'Cargar 3 fotos de prueba' si está evaluando la app."
      );
      return;
    }

    setGuardando(true);

    const nuevaBoleta: Infraccion = {
      id: `inf-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`,
      folio,
      agente: {
        placa: user?.placa || "AGT-204",
        nombre: user?.nombre || "Oficial de Tránsito",
        rol: user?.rol || "Agente Vial",
      },
      generales: {
        fecha,
        hora,
        lugar: lugar.trim(),
        coordenadas,
      },
      infractor: {
        conductorAusente,
        nombre: conductorAusente ? "Conductor Ausente" : nombreInfractor.trim(),
        domicilio: conductorAusente ? "No disponible" : domicilioInfractor.trim(),
        numeroLicencia: conductorAusente ? undefined : licenciaInfractor.trim(),
      },
      vehiculo: {
        placas: sinPlacas ? "SIN_PLACAS" : placas.toUpperCase().trim(),
        sinPlacas,
        marca: marca.trim(),
        lineaModelo: lineaModelo.trim() || "No especificada",
        color: color.trim() || "No especificado",
        tipo: tipoVehiculo,
      },
      falta: faltaSeleccionada,
      hechos:
        hechos.trim() ||
        `Infracción al Reglamento de Movilidad de Uriangato: ${faltaSeleccionada.descripcion}.`,
      garantiasRetenidas: garantias,
      detalleGarantia: {
        inventarioGrua: inventarioGrua.trim() || undefined,
      },
      evidencias: {
        fotoPlaca,
        fotoContexto,
        fotoDocumento,
      },
      estado: "pendiente",
      creadoEn: new Date().toISOString(),
      sincronizadoEn: null,
    };

    try {
      await guardarInfraccion(nuevaBoleta);
      setGuardando(false);
      Alert.alert(
        "Boleta Guardada Exitosamente",
        `La boleta ${folio} ha sido almacenada de forma segura en la memoria del dispositivo. Se sincronizará automáticamente con el panel de administración al tener conexión.`,
        [
          {
            text: "Aceptar",
            onPress: () => router.replace("/dashboard"),
          },
        ]
      );
    } catch {
      setGuardando(false);
      Alert.alert("Error", "No se pudo guardar la boleta en la base local del dispositivo.");
    }
  };

  // Categorías únicas del catálogo
  const categoriasCatalogo = ["todas", ...Array.from(new Set(catalogo.map((c) => c.categoria)))];

  const catalogoFiltrado = catalogo.filter((item) => {
    const coincideTexto =
      item.descripcion.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.fundamentoLegal.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.categoria.toLowerCase().includes(busquedaCatalogo.toLowerCase());
    const coincideCategoria =
      categoriaFiltro === "todas" || item.categoria === categoriaFiltro;
    return coincideTexto && coincideCategoria;
  });

  const minMontoPesos = Number(faltaSeleccionada.montoMinUma) * valorUma;
  const maxMontoPesos = Number(faltaSeleccionada.montoMaxUma) * valorUma;

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.keyboardContainer, { backgroundColor: colors.background }]}
    >
      <View style={[styles.container, { backgroundColor: colors.background }]}>
        {/* Barra Superior con Navegación y Tema */}
        <View style={[styles.topHeader, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
          <TouchableOpacity
            style={[styles.backButton, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
            onPress={() => router.back()}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            accessibilityLabel="Volver al panel"
          >
            <Ionicons name="arrow-back" size={20} color={colors.text} />
          </TouchableOpacity>
          <View style={styles.headerTitleWrap}>
            <Text style={[styles.headerTitle, { color: colors.text }]}>NUEVA BOLETA DE INFRACCIÓN</Text>
            <Text style={[styles.headerSubtitle, { color: colors.textSecondary }]}>
              Dirección de Tránsito y Movilidad · Uriangato
            </Text>
          </View>
          <ThemeToggle compact={true} />
        </View>

        <ScrollView
          style={styles.formScroll}
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
        >
          {/* Banner Principal de Folio y Estado Offline */}
          <View style={[styles.folioBannerCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.folioBannerLeft}>
              <View style={styles.folioBadgeTag}>
                <Ionicons name="shield" size={14} color={colors.primary} />
                <Text style={[styles.folioBadgeTagText, { color: colors.primary }]}>FOLIO OFICIAL</Text>
              </View>
              <Text style={[styles.folioNumberText, { color: colors.text }]}>{folio}</Text>
              <View style={styles.offlinePill}>
                <View style={styles.greenPulseDot} />
                <Text style={[styles.offlinePillText, { color: colors.success }]}>
                  Almacenamiento Local Offline Activo
                </Text>
              </View>
            </View>

            <View style={[styles.agentBox, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <Ionicons name="person-circle-outline" size={22} color={colors.primary} />
              <Text style={[styles.agentPlacaLabel, { color: colors.text }]}>{user?.placa || "AGT-204"}</Text>
              <Text style={[styles.agentSectorLabel, { color: colors.textMuted }]}>AGENTE VIAL</Text>
            </View>
          </View>

          {/* ========================================================
              CARD 1: UBICACIÓN Y MOMENTO (CON GPS)
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: colors.primaryBg }]}>
                <Ionicons name="location" size={20} color={colors.primary} />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>1. Momento y Ubicación de los Hechos</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  Coordenadas satelitales y fecha del evento
                </Text>
              </View>
            </View>

            <View style={styles.twoColRow}>
              <View style={[styles.fieldCol, { marginRight: 8 }]}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>FECHA</Text>
                <View style={[styles.readOnlyInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                  <Ionicons name="calendar-outline" size={18} color={colors.primary} />
                  <Text style={[styles.readOnlyInputText, { color: colors.text }]}>{fecha || "Cargando..."}</Text>
                </View>
              </View>

              <View style={[styles.fieldCol, { marginLeft: 8 }]}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>HORA</Text>
                <View style={[styles.readOnlyInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                  <Ionicons name="time-outline" size={18} color={colors.primary} />
                  <Text style={[styles.readOnlyInputText, { color: colors.text }]}>{hora || "Cargando..."}</Text>
                </View>
              </View>
            </View>

            <View style={styles.inputGroup}>
              <View style={styles.fieldLabelRow}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>
                  LUGAR DE LA INFRACCIÓN / VIALIDAD *
                </Text>
                <TouchableOpacity
                  style={[styles.gpsActionBtn, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}
                  onPress={obtenerUbicacionGps}
                  disabled={buscandoGps}
                  activeOpacity={0.7}
                >
                  {buscandoGps ? (
                    <ActivityIndicator size="small" color={colors.primary} />
                  ) : (
                    <>
                      <Ionicons name="locate" size={14} color={colors.primary} />
                      <Text style={[styles.gpsActionBtnText, { color: colors.primary }]}>Re-escanear GPS</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>

              <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                <Ionicons name="map-outline" size={20} color={colors.textMuted} style={styles.textInputIcon} />
                <TextInput
                  style={[styles.textInput, { color: colors.text }]}
                  placeholder="Ej. Av. Hidalgo esq. Morelos, Centro"
                  placeholderTextColor={colors.textMuted}
                  value={lugar}
                  onChangeText={setLugar}
                />
              </View>

              {coordenadas && (
                <View style={[styles.coordsChip, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
                  <Ionicons name="checkmark-circle" size={16} color={colors.success} />
                  <Text style={[styles.coordsChipText, { color: colors.textSecondary }]}>
                    GPS Fijo: Lat {coordenadas.latitud.toFixed(5)}, Lon {coordenadas.longitud.toFixed(5)}
                  </Text>
                </View>
              )}
            </View>
          </View>

          {/* ========================================================
              CARD 2: VEHÍCULO INTERVENIDO
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: colors.accentBg }]}>
                <Ionicons name="car-sport" size={20} color={colors.accent} />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>2. Identificación del Vehículo</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  Placas, marca, modelo y clasificación vial
                </Text>
              </View>
            </View>

            {/* Recuadro Estilo Placa Vehicular */}
            <View style={styles.plateContainer}>
              <View style={[styles.plateBox, { backgroundColor: isDark ? "#1c1917" : "#ffffff", borderColor: sinPlacas ? colors.border : colors.primary }]}>
                <View style={styles.plateHeader}>
                  <Text style={[styles.plateHeaderText, { color: colors.primary }]}>GUANAJUATO · MÉXICO</Text>
                </View>
                <TextInput
                  style={[
                    styles.plateInput,
                    { color: sinPlacas ? colors.textMuted : colors.text },
                    sinPlacas && { opacity: 0.4 },
                  ]}
                  placeholder="GTC-000-A"
                  placeholderTextColor={colors.textMuted}
                  value={sinPlacas ? "SIN PLACAS" : placas}
                  onChangeText={(v) => setPlacas(v.toUpperCase())}
                  autoCapitalize="characters"
                  editable={!sinPlacas}
                  maxLength={10}
                />
              </View>

              <TouchableOpacity
                style={[
                  styles.sinPlacasToggleCard,
                  { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                  sinPlacas && { borderColor: colors.warning, backgroundColor: colors.warningBg },
                ]}
                onPress={() => {
                  setSinPlacas(!sinPlacas);
                  if (!sinPlacas) setPlacas("");
                }}
                activeOpacity={0.8}
              >
                <Ionicons
                  name={sinPlacas ? "checkbox" : "square-outline"}
                  size={20}
                  color={sinPlacas ? colors.warning : colors.textMuted}
                />
                <Text style={[styles.sinPlacasToggleText, { color: sinPlacas ? colors.warning : colors.textSecondary }]}>
                  Vehículo no porta placas o ilegibles
                </Text>
              </TouchableOpacity>
            </View>

            {/* Selector Visual de Tipo de Vehículo */}
            <View style={styles.inputGroup}>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>TIPO DE VEHÍCULO</Text>
              <View style={styles.vehicleTypeGrid}>
                {[
                  { id: "particular", label: "Particular", icon: "car" },
                  { id: "transporte_publico", label: "Transporte Púb.", icon: "bus" },
                  { id: "motocicleta", label: "Motocicleta", icon: "bicycle" },
                  { id: "carga", label: "Carga Pesada", icon: "cube" },
                ].map((item) => {
                  const seleccionado = tipoVehiculo === item.id;
                  return (
                    <TouchableOpacity
                      key={item.id}
                      style={[
                        styles.vehicleTypeCard,
                        { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                        seleccionado && { backgroundColor: colors.primaryBg, borderColor: colors.primary },
                      ]}
                      onPress={() => setTipoVehiculo(item.id as TipoVehiculo)}
                      activeOpacity={0.8}
                    >
                      <Ionicons
                        name={item.icon as any}
                        size={22}
                        color={seleccionado ? colors.primary : colors.textMuted}
                      />
                      <Text
                        style={[
                          styles.vehicleTypeCardText,
                          { color: seleccionado ? colors.primary : colors.textSecondary },
                          seleccionado && { fontWeight: "800" },
                        ]}
                      >
                        {item.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </View>

            {/* Marca y Modelo */}
            <View style={styles.twoColRow}>
              <View style={[styles.fieldCol, { marginRight: 8 }]}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>MARCA *</Text>
                <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                  <TextInput
                    style={[styles.textInput, { color: colors.text }]}
                    placeholder="Ej. Nissan, VW..."
                    placeholderTextColor={colors.textMuted}
                    value={marca}
                    onChangeText={setMarca}
                  />
                </View>
              </View>

              <View style={[styles.fieldCol, { marginLeft: 8 }]}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>LÍNEA / MODELO</Text>
                <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                  <TextInput
                    style={[styles.textInput, { color: colors.text }]}
                    placeholder="Ej. Versa 2022"
                    placeholderTextColor={colors.textMuted}
                    value={lineaModelo}
                    onChangeText={setLineaModelo}
                  />
                </View>
              </View>
            </View>

            {/* Color del Vehículo */}
            <View style={styles.inputGroup}>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>COLOR PREDOMINANTE</Text>
              <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                <Ionicons name="color-palette-outline" size={18} color={colors.textMuted} style={styles.textInputIcon} />
                <TextInput
                  style={[styles.textInput, { color: colors.text }]}
                  placeholder="Ej. Blanco, Rojo Tinto, Gris Oxford..."
                  placeholderTextColor={colors.textMuted}
                  value={color}
                  onChangeText={setColor}
                />
              </View>
            </View>
          </View>

          {/* ========================================================
              CARD 3: DATOS DEL CONDUCTOR
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: colors.warningBg }]}>
                <Ionicons name="person" size={20} color={colors.warning} />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>3. Conductor / Infractor</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  Identificación de la persona responsable
                </Text>
              </View>
            </View>

            {/* Switch Conductor Ausente */}
            <View style={[styles.switchToggleCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <View style={{ flex: 1, paddingRight: 10 }}>
                <Text style={[styles.switchTitle, { color: colors.text }]}>¿Conductor Ausente en el Sitio?</Text>
                <Text style={[styles.switchDesc, { color: colors.textSecondary }]}>
                  Marque si el vehículo está estacionado en lugar prohibido o abandonado
                </Text>
              </View>
              <Switch
                value={conductorAusente}
                onValueChange={setConductorAusente}
                trackColor={{ false: colors.border, true: colors.warning }}
                thumbColor="#ffffff"
              />
            </View>

            {conductorAusente ? (
              <View style={[styles.infoBanner, { backgroundColor: colors.warningBg, borderColor: colors.warning }]}>
                <Ionicons name="information-circle" size={20} color={colors.warning} />
                <Text style={[styles.infoBannerText, { color: colors.warning }]}>
                  La boleta se emitirá como 'Conductor Ausente' y se dejará colocada en el parabrisas del vehículo.
                </Text>
              </View>
            ) : (
              <>
                <View style={styles.inputGroup}>
                  <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>
                    NOMBRE COMPLETO DEL CONDUCTOR *
                  </Text>
                  <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                    <Ionicons name="person-outline" size={18} color={colors.textMuted} style={styles.textInputIcon} />
                    <TextInput
                      style={[styles.textInput, { color: colors.text }]}
                      placeholder="Nombre y Apellidos"
                      placeholderTextColor={colors.textMuted}
                      value={nombreInfractor}
                      onChangeText={setNombreInfractor}
                    />
                  </View>
                </View>

                <View style={styles.twoColRow}>
                  <View style={[styles.fieldCol, { marginRight: 8 }]}>
                    <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>NO. DE LICENCIA</Text>
                    <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                      <Ionicons name="card-outline" size={18} color={colors.textMuted} style={styles.textInputIcon} />
                      <TextInput
                        style={[styles.textInput, { color: colors.text }]}
                        placeholder="GTO-LIC-00000"
                        placeholderTextColor={colors.textMuted}
                        value={licenciaInfractor}
                        onChangeText={setLicenciaInfractor}
                        autoCapitalize="characters"
                      />
                    </View>
                  </View>

                  <View style={[styles.fieldCol, { marginLeft: 8 }]}>
                    <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>DOMICILIO / CIUDAD</Text>
                    <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                      <Ionicons name="home-outline" size={18} color={colors.textMuted} style={styles.textInputIcon} />
                      <TextInput
                        style={[styles.textInput, { color: colors.text }]}
                        placeholder="Calle, Col, Uriangato"
                        placeholderTextColor={colors.textMuted}
                        value={domicilioInfractor}
                        onChangeText={setDomicilioInfractor}
                      />
                    </View>
                  </View>
                </View>
              </>
            )}
          </View>

          {/* ========================================================
              CARD 4: MOTIVO DE INFRACCIÓN (CATÁLOGO OFICIAL)
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: colors.dangerBg }]}>
                <Ionicons name="alert-circle" size={20} color={colors.danger} />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>4. Motivo y Fundamento Legal</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  Reglamento de Movilidad y Tabulador de UMAS
                </Text>
              </View>
            </View>

            {/* Tarjeta de la Infracción Seleccionada */}
            <View style={[styles.selectedOffenseCard, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <View style={styles.selectedOffenseHeader}>
                <View style={[styles.categoryBadge, { backgroundColor: colors.primaryBg, borderColor: colors.border }]}>
                  <Text style={[styles.categoryBadgeText, { color: colors.primary }]}>
                    {faltaSeleccionada.categoria}
                  </Text>
                </View>
                <View style={[styles.articlePill, { backgroundColor: colors.dangerBg }]}>
                  <Text style={[styles.articlePillText, { color: colors.danger }]}>
                    {faltaSeleccionada.fundamentoLegal}
                  </Text>
                </View>
              </View>

              <Text style={[styles.selectedOffenseDesc, { color: colors.text }]}>
                {faltaSeleccionada.descripcion}
              </Text>

              {/* Cálculo financiero en UMA y Pesos */}
              <View style={[styles.calculationBar, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <View style={styles.calcCol}>
                  <Text style={[styles.calcColLabel, { color: colors.textMuted }]}>SANCIÓN TABULADOR</Text>
                  <Text style={[styles.calcColValue, { color: colors.primary }]}>
                    {faltaSeleccionada.montoMinUma} a {faltaSeleccionada.montoMaxUma} UMA
                  </Text>
                </View>
                <View style={styles.calcDivider} />
                <View style={styles.calcCol}>
                  <Text style={[styles.calcColLabel, { color: colors.textMuted }]}>IMPORTE EN PESOS (MXN)</Text>
                  <Text style={[styles.calcColValue, { color: colors.success }]}>
                    ${minMontoPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} – $
                    {maxMontoPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} MXN
                  </Text>
                </View>
              </View>

              <TouchableOpacity
                style={[styles.changeOffenseBtn, { backgroundColor: colors.primary }]}
                onPress={() => setModalCatalogoVisible(true)}
                activeOpacity={0.85}
              >
                <Ionicons name="search" size={16} color="#ffffff" />
                <Text style={styles.changeOffenseBtnText}>CAMBIAR O BUSCAR OTRA FALTA EN EL CATÁLOGO</Text>
              </TouchableOpacity>
            </View>

            {/* Hechos circunstanciados */}
            <View style={[styles.inputGroup, { marginTop: 16 }]}>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>
                HECHOS CIRCUNSTANCIADOS (OBSERVACIONES DEL AGENTE)
              </Text>
              <TextInput
                style={[
                  styles.textAreaInput,
                  { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text },
                ]}
                placeholder="Describa brevemente las circunstancias de tiempo, modo y lugar..."
                placeholderTextColor={colors.textMuted}
                multiline
                numberOfLines={3}
                value={hechos}
                onChangeText={setHechos}
              />
            </View>
          </View>

          {/* ========================================================
              CARD 5: GARANTÍA RETENIDA
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: colors.successBg }]}>
                <Ionicons name="shield-checkmark" size={20} color={colors.success} />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>5. Garantía Retenida</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  Documento o bien retenido para asegurar el pago de la infracción
                </Text>
              </View>
            </View>

            <View style={styles.garantiasGrid}>
              {[
                { id: "licencia", label: "Licencia de Conducir", icon: "card-outline" },
                { id: "placa", label: "Placa Delantera/Trasera", icon: "pricetag-outline" },
                { id: "tarjeta_circulacion", label: "Tarjeta de Circulación", icon: "document-text-outline" },
                { id: "vehiculo", label: "Vehículo Remitido (Grúa)", icon: "car-outline" },
              ].map((item) => {
                const checked = garantias.includes(item.id as GarantiaRetenida);
                return (
                  <TouchableOpacity
                    key={item.id}
                    style={[
                      styles.garantiaCard,
                      { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                      checked && { backgroundColor: colors.primaryBg, borderColor: colors.primary },
                    ]}
                    onPress={() => toggleGarantia(item.id as GarantiaRetenida)}
                    activeOpacity={0.8}
                  >
                    <Ionicons
                      name={checked ? "checkmark-circle" : (item.icon as any)}
                      size={24}
                      color={checked ? colors.primary : colors.textMuted}
                    />
                    <Text
                      style={[
                        styles.garantiaCardText,
                        { color: checked ? colors.primary : colors.textSecondary },
                        checked && { fontWeight: "800" },
                      ]}
                    >
                      {item.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {garantias.includes("vehiculo") && (
              <View style={[styles.inputGroup, { marginTop: 14 }]}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>
                  NÚMERO DE INVENTARIO / EMPRESA DE GRÚA
                </Text>
                <View style={[styles.textInputWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                  <Ionicons name="document-attach-outline" size={18} color={colors.textMuted} style={styles.textInputIcon} />
                  <TextInput
                    style={[styles.textInput, { color: colors.text }]}
                    placeholder="Ej. Grúas Uriangato - Inventario #045"
                    placeholderTextColor={colors.textMuted}
                    value={inventarioGrua}
                    onChangeText={setInventarioGrua}
                  />
                </View>
              </View>
            )}
          </View>

          {/* ========================================================
              CARD 6: EVIDENCIAS FOTOGRÁFICAS (3 OBLIGATORIAS)
          ========================================================= */}
          <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.cardHeader}>
              <View style={[styles.cardIconBadge, { backgroundColor: "rgba(6, 182, 212, 0.12)" }]}>
                <Ionicons name="camera" size={20} color="#06b6d4" />
              </View>
              <View style={styles.cardHeaderTitles}>
                <Text style={[styles.cardTitle, { color: colors.text }]}>6. Evidencia Fotográfica Digital</Text>
                <Text style={[styles.cardSubtitle, { color: colors.textSecondary }]}>
                  3 evidencias fotográficas obligatorias para validez legal
                </Text>
              </View>
            </View>

            {/* Botón de llenado rápido de fotos para pruebas */}
            <TouchableOpacity
              style={[styles.demoPhotosButton, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
              onPress={handleCargarFotosDemo}
              activeOpacity={0.8}
            >
              <Ionicons name="flash" size={16} color={colors.primary} />
              <Text style={[styles.demoPhotosButtonText, { color: colors.primary }]}>
                Cargar 3 Fotos de Demostración (Prueba Rápida)
              </Text>
            </TouchableOpacity>

            <View style={styles.photosGridList}>
              {/* Foto 1: Placa */}
              <View style={[styles.photoCardItem, { backgroundColor: colors.surfaceElevated, borderColor: fotoPlaca ? colors.success : colors.border }]}>
                <View style={styles.photoCardHeader}>
                  <Text style={[styles.photoCardTitle, { color: colors.text }]}>FOTO 1: PLACA</Text>
                  <View style={[styles.photoStatusPill, { backgroundColor: fotoPlaca ? colors.successBg : colors.surface }]}>
                    <Ionicons
                      name={fotoPlaca ? "checkmark-circle" : "time-outline"}
                      size={14}
                      color={fotoPlaca ? colors.success : colors.textMuted}
                    />
                    <Text style={[styles.photoStatusText, { color: fotoPlaca ? colors.success : colors.textMuted }]}>
                      {fotoPlaca ? "LISTA" : "PENDIENTE"}
                    </Text>
                  </View>
                </View>

                {fotoPlaca ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoPlaca }} style={styles.photoPreviewImage} />
                    <View style={styles.photoActionsRow}>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.surfaceElevated }]}
                        onPress={() => setFotoPreviewGrande(fotoPlaca)}
                      >
                        <Ionicons name="eye-outline" size={14} color={colors.text} />
                        <Text style={[styles.photoActionBtnText, { color: colors.text }]}>Ver</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.primaryBg }]}
                        onPress={() => {
                          setCapturandoTipoFoto("placa");
                          setModalCamaraVisible(true);
                        }}
                      >
                        <Ionicons name="camera-outline" size={14} color={colors.primary} />
                        <Text style={[styles.photoActionBtnText, { color: colors.primary }]}>Cambiar</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[styles.photoPlaceholderBox, { backgroundColor: colors.inputBg }]}
                    onPress={() => {
                      setCapturandoTipoFoto("placa");
                      setModalCamaraVisible(true);
                    }}
                    activeOpacity={0.7}
                  >
                    <Ionicons name="camera" size={32} color={colors.primary} />
                    <Text style={[styles.photoPlaceholderText, { color: colors.text }]}>Tomar Foto de la Placa</Text>
                    <Text style={[styles.photoPlaceholderSub, { color: colors.textMuted }]}>
                      Enfoque nítido de la matrícula
                    </Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Foto 2: Contexto */}
              <View style={[styles.photoCardItem, { backgroundColor: colors.surfaceElevated, borderColor: fotoContexto ? colors.success : colors.border }]}>
                <View style={styles.photoCardHeader}>
                  <Text style={[styles.photoCardTitle, { color: colors.text }]}>FOTO 2: CONTEXTO</Text>
                  <View style={[styles.photoStatusPill, { backgroundColor: fotoContexto ? colors.successBg : colors.surface }]}>
                    <Ionicons
                      name={fotoContexto ? "checkmark-circle" : "time-outline"}
                      size={14}
                      color={fotoContexto ? colors.success : colors.textMuted}
                    />
                    <Text style={[styles.photoStatusText, { color: fotoContexto ? colors.success : colors.textMuted }]}>
                      {fotoContexto ? "LISTA" : "PENDIENTE"}
                    </Text>
                  </View>
                </View>

                {fotoContexto ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoContexto }} style={styles.photoPreviewImage} />
                    <View style={styles.photoActionsRow}>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.surfaceElevated }]}
                        onPress={() => setFotoPreviewGrande(fotoContexto)}
                      >
                        <Ionicons name="eye-outline" size={14} color={colors.text} />
                        <Text style={[styles.photoActionBtnText, { color: colors.text }]}>Ver</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.primaryBg }]}
                        onPress={() => {
                          setCapturandoTipoFoto("contexto");
                          setModalCamaraVisible(true);
                        }}
                      >
                        <Ionicons name="camera-outline" size={14} color={colors.primary} />
                        <Text style={[styles.photoActionBtnText, { color: colors.primary }]}>Cambiar</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[styles.photoPlaceholderBox, { backgroundColor: colors.inputBg }]}
                    onPress={() => {
                      setCapturandoTipoFoto("contexto");
                      setModalCamaraVisible(true);
                    }}
                    activeOpacity={0.7}
                  >
                    <Ionicons name="camera" size={32} color={colors.primary} />
                    <Text style={[styles.photoPlaceholderText, { color: colors.text }]}>Tomar Foto del Contexto</Text>
                    <Text style={[styles.photoPlaceholderSub, { color: colors.textMuted }]}>
                      Vehículo y señalamiento vial
                    </Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Foto 3: Garantía / Documento */}
              <View style={[styles.photoCardItem, { backgroundColor: colors.surfaceElevated, borderColor: fotoDocumento ? colors.success : colors.border }]}>
                <View style={styles.photoCardHeader}>
                  <Text style={[styles.photoCardTitle, { color: colors.text }]}>FOTO 3: GARANTÍA O BOLETA</Text>
                  <View style={[styles.photoStatusPill, { backgroundColor: fotoDocumento ? colors.successBg : colors.surface }]}>
                    <Ionicons
                      name={fotoDocumento ? "checkmark-circle" : "time-outline"}
                      size={14}
                      color={fotoDocumento ? colors.success : colors.textMuted}
                    />
                    <Text style={[styles.photoStatusText, { color: fotoDocumento ? colors.success : colors.textMuted }]}>
                      {fotoDocumento ? "LISTA" : "PENDIENTE"}
                    </Text>
                  </View>
                </View>

                {fotoDocumento ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoDocumento }} style={styles.photoPreviewImage} />
                    <View style={styles.photoActionsRow}>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.surfaceElevated }]}
                        onPress={() => setFotoPreviewGrande(fotoDocumento)}
                      >
                        <Ionicons name="eye-outline" size={14} color={colors.text} />
                        <Text style={[styles.photoActionBtnText, { color: colors.text }]}>Ver</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.photoActionBtn, { backgroundColor: colors.primaryBg }]}
                        onPress={() => {
                          setCapturandoTipoFoto("documento");
                          setModalCamaraVisible(true);
                        }}
                      >
                        <Ionicons name="camera-outline" size={14} color={colors.primary} />
                        <Text style={[styles.photoActionBtnText, { color: colors.primary }]}>Cambiar</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[styles.photoPlaceholderBox, { backgroundColor: colors.inputBg }]}
                    onPress={() => {
                      setCapturandoTipoFoto("documento");
                      setModalCamaraVisible(true);
                    }}
                    activeOpacity={0.7}
                  >
                    <Ionicons name="camera" size={32} color={colors.primary} />
                    <Text style={[styles.photoPlaceholderText, { color: colors.text }]}>Tomar Foto del Documento</Text>
                    <Text style={[styles.photoPlaceholderSub, { color: colors.textMuted }]}>
                      Licencia, placa o folio notificado
                    </Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          </View>

          {/* ========================================================
              CARD 7: RESUMEN Y EMISIÓN FINAL DE BOLETA
          ========================================================= */}
          <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Text style={[styles.summaryTitle, { color: colors.text }]}>Resumen de Emisión</Text>

            <View style={styles.summaryItemRow}>
              <Text style={[styles.summaryKey, { color: colors.textSecondary }]}>Folio Registrado:</Text>
              <Text style={[styles.summaryVal, { color: colors.primary }]}>{folio}</Text>
            </View>

            <View style={styles.summaryItemRow}>
              <Text style={[styles.summaryKey, { color: colors.textSecondary }]}>Sanción Estimada:</Text>
              <Text style={[styles.summaryVal, { color: colors.success }]}>
                ${minMontoPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} – $
                {maxMontoPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} MXN
              </Text>
            </View>

            <View style={styles.summaryItemRow}>
              <Text style={[styles.summaryKey, { color: colors.textSecondary }]}>Evidencias Fotográficas:</Text>
              <Text style={[styles.summaryVal, { color: totalFotosListas === 3 ? colors.success : colors.warning }]}>
                {totalFotosListas} de 3 listas
              </Text>
            </View>

            <View style={[styles.offlineAssuranceBox, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <Ionicons name="cloud-offline-outline" size={18} color={colors.success} />
              <Text style={[styles.offlineAssuranceText, { color: colors.textSecondary }]}>
                La boleta quedará asegurada localmente y se sincronizará de forma transparente con el panel central de Uriangato.
              </Text>
            </View>

            <TouchableOpacity
              style={[
                styles.saveBigButton,
                { backgroundColor: colors.primary },
                guardando && styles.buttonDisabled,
              ]}
              onPress={handleGuardarBoleta}
              disabled={guardando}
              activeOpacity={0.85}
            >
              {guardando ? (
                <ActivityIndicator color="#ffffff" size="small" />
              ) : (
                <>
                  <Ionicons name="checkmark-done-circle" size={24} color="#ffffff" />
                  <Text style={styles.saveBigButtonText}>EMITIR Y GUARDAR BOLETA OFICIAL</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        </ScrollView>

        {/* MODAL DE CATÁLOGO COMPLETO DE INFRACCIONES */}
        <Modal
          visible={modalCatalogoVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setModalCatalogoVisible(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={[styles.modalCatalogContent, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={[styles.modalCatalogHeader, { borderBottomColor: colors.border }]}>
                <View>
                  <Text style={[styles.modalCatSubtitle, { color: colors.textMuted }]}>
                    REGLAMENTO DE MOVILIDAD Y TRANSPORTE
                  </Text>
                  <Text style={[styles.modalCatTitle, { color: colors.text }]}>Catálogo Oficial de Infracciones</Text>
                </View>
                <TouchableOpacity
                  onPress={() => setModalCatalogoVisible(false)}
                  style={[styles.closeCatalogBtn, { backgroundColor: colors.surfaceElevated }]}
                >
                  <Ionicons name="close" size={22} color={colors.text} />
                </TouchableOpacity>
              </View>

              {/* Barra de Búsqueda */}
              <View style={[styles.searchBarWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                <Ionicons name="search" size={18} color={colors.textMuted} />
                <TextInput
                  style={[styles.searchBarInput, { color: colors.text }]}
                  placeholder="Buscar por artículo, falta o palabra clave..."
                  placeholderTextColor={colors.textMuted}
                  value={busquedaCatalogo}
                  onChangeText={setBusquedaCatalogo}
                />
                {busquedaCatalogo.length > 0 && (
                  <TouchableOpacity onPress={() => setBusquedaCatalogo("")}>
                    <Ionicons name="close-circle" size={18} color={colors.textMuted} />
                  </TouchableOpacity>
                )}
              </View>

              {/* Filtros de Categorías */}
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                style={styles.categoryChipsScroll}
                contentContainerStyle={styles.categoryChipsContent}
              >
                {categoriasCatalogo.map((cat) => {
                  const activa = categoriaFiltro === cat;
                  return (
                    <TouchableOpacity
                      key={cat}
                      style={[
                        styles.catFilterPill,
                        { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                        activa && { backgroundColor: colors.primary, borderColor: colors.primary },
                      ]}
                      onPress={() => setCategoriaFiltro(cat)}
                    >
                      <Text
                        style={[
                          styles.catFilterPillText,
                          { color: activa ? "#ffffff" : colors.textSecondary },
                          activa && { fontWeight: "800" },
                        ]}
                      >
                        {cat === "todas" ? "Todas las faltas" : cat}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>

              <ScrollView style={styles.catalogList}>
                {catalogoFiltrado.map((item) => {
                  const seleccionada = faltaSeleccionada.id === item.id;
                  const itemMinPesos = Number(item.montoMinUma) * valorUma;
                  const itemMaxPesos = Number(item.montoMaxUma) * valorUma;

                  return (
                    <TouchableOpacity
                      key={item.id}
                      style={[
                        styles.catalogItemCard,
                        { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                        seleccionada && { borderColor: colors.primary, backgroundColor: colors.primaryBg },
                      ]}
                      onPress={() => {
                        setFaltaSeleccionada(item);
                        setModalCatalogoVisible(false);
                      }}
                      activeOpacity={0.75}
                    >
                      <View style={styles.catItemTop}>
                        <View style={[styles.lawTag, { backgroundColor: colors.dangerBg }]}>
                          <Text style={[styles.catItemLaw, { color: colors.danger }]}>{item.fundamentoLegal}</Text>
                        </View>
                        <Text style={[styles.catItemUma, { color: colors.primary }]}>
                          {item.montoMinUma} - {item.montoMaxUma} UMAS
                        </Text>
                      </View>
                      <Text style={[styles.catItemDesc, { color: colors.text }]}>{item.descripcion}</Text>
                      <View style={styles.catItemBottom}>
                        <Text style={[styles.catItemCategory, { color: colors.textMuted }]}>{item.categoria}</Text>
                        <Text style={[styles.catItemPesos, { color: colors.success }]}>
                          ${itemMinPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} – $
                          {itemMaxPesos.toLocaleString("es-MX", { maximumFractionDigits: 0 })} MXN
                        </Text>
                      </View>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* MODAL SIMULADOR DE CÁMARA */}
        <Modal
          visible={modalCamaraVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setModalCamaraVisible(false)}
        >
          <View style={styles.cameraOverlay}>
            <View style={[styles.cameraModalCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.cameraHeader}>
                <Text style={[styles.cameraTitle, { color: colors.text }]}>
                  {capturandoTipoFoto === "placa"
                    ? "Capturar Foto 1: Placa"
                    : capturandoTipoFoto === "contexto"
                    ? "Capturar Foto 2: Contexto"
                    : "Capturar Foto 3: Garantía / Documento"}
                </Text>
                <TouchableOpacity
                  onPress={() => setModalCamaraVisible(false)}
                  style={[styles.closeCameraBtn, { backgroundColor: colors.surfaceElevated }]}
                >
                  <Ionicons name="close" size={22} color={colors.text} />
                </TouchableOpacity>
              </View>

              <View style={[styles.cameraViewfinder, { backgroundColor: colors.background, borderColor: colors.border }]}>
                <Ionicons name="scan-outline" size={80} color={colors.primary} />
                <Text style={[styles.viewfinderHint, { color: colors.textSecondary }]}>
                  {capturandoTipoFoto === "placa"
                    ? "Encuadre la placa frontal o trasera dentro del recuadro"
                    : capturandoTipoFoto === "contexto"
                    ? "Encuadre el vehículo completo y la señalización vial"
                    : "Encuadre la licencia o documento retenido"}
                </Text>
              </View>

              <TouchableOpacity
                style={[styles.shutterBtn, { backgroundColor: colors.primary }]}
                onPress={() => capturandoTipoFoto && simularCapturaFoto(capturandoTipoFoto)}
                activeOpacity={0.85}
              >
                <Ionicons name="camera" size={24} color="#ffffff" />
                <Text style={styles.shutterBtnText}>CAPTURAR FOTOGRAFÍA</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>

        {/* MODAL PARA VER FOTO COMPLETA EN GRANDE */}
        <Modal
          visible={!!fotoPreviewGrande}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setFotoPreviewGrande(null)}
        >
          <View style={styles.fullPhotoOverlay}>
            <TouchableOpacity
              style={styles.closeFullPhotoBtn}
              onPress={() => setFotoPreviewGrande(null)}
            >
              <Ionicons name="close-circle" size={36} color="#ffffff" />
            </TouchableOpacity>
            {fotoPreviewGrande && (
              <Image
                source={{ uri: fotoPreviewGrande }}
                style={styles.fullPhotoImage}
                resizeMode="contain"
              />
            )}
          </View>
        </Modal>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  keyboardContainer: {
    flex: 1,
  },
  container: {
    flex: 1,
  },
  topHeader: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 14,
    borderBottomWidth: 1,
    gap: 12,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  headerTitleWrap: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 14,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  headerSubtitle: {
    fontSize: 11,
    marginTop: 1,
  },
  formScroll: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 60,
  },

  // Folio Banner
  folioBannerCard: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 18,
    borderRadius: 18,
    borderWidth: 1,
    marginBottom: 20,
  },
  folioBannerLeft: {
    flex: 1,
  },
  folioBadgeTag: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginBottom: 4,
  },
  folioBadgeTagText: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1,
  },
  folioNumberText: {
    fontSize: 20,
    fontWeight: "900",
    letterSpacing: 0.5,
    marginBottom: 6,
  },
  offlinePill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  greenPulseDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: "#10b981",
  },
  offlinePillText: {
    fontSize: 11,
    fontWeight: "600",
  },
  agentBox: {
    alignItems: "center",
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    marginLeft: 12,
  },
  agentPlacaLabel: {
    fontSize: 13,
    fontWeight: "800",
    marginTop: 2,
  },
  agentSectorLabel: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
  },

  // Cards Estructuradas
  card: {
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    marginBottom: 20,
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginBottom: 18,
  },
  cardIconBadge: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: "center",
    justifyContent: "center",
  },
  cardHeaderTitles: {
    flex: 1,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: "800",
  },
  cardSubtitle: {
    fontSize: 12,
    marginTop: 2,
  },

  // Campos y filas
  twoColRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 16,
  },
  fieldCol: {
    flex: 1,
  },
  fieldLabel: {
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 0.6,
    marginBottom: 8,
  },
  fieldLabelRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  inputGroup: {
    marginBottom: 16,
  },
  readOnlyInputWrap: {
    flexDirection: "row",
    alignItems: "center",
    height: 48,
    borderRadius: 12,
    borderWidth: 1,
    paddingHorizontal: 14,
    gap: 8,
  },
  readOnlyInputText: {
    fontSize: 14,
    fontWeight: "600",
  },
  textInputWrap: {
    flexDirection: "row",
    alignItems: "center",
    height: 50,
    borderRadius: 12,
    borderWidth: 1,
    paddingHorizontal: 14,
  },
  textInputIcon: {
    marginRight: 8,
  },
  textInput: {
    flex: 1,
    fontSize: 14,
    fontWeight: "500",
    paddingVertical: 0,
  },
  textAreaInput: {
    height: 90,
    borderRadius: 12,
    borderWidth: 1,
    padding: 14,
    fontSize: 14,
    textAlignVertical: "top",
  },
  gpsActionBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    borderWidth: 1,
  },
  gpsActionBtnText: {
    fontSize: 11,
    fontWeight: "700",
  },
  coordsChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    marginTop: 8,
  },
  coordsChipText: {
    fontSize: 11,
    fontWeight: "600",
  },

  // Placa Vehicular
  plateContainer: {
    marginBottom: 16,
  },
  plateBox: {
    borderWidth: 2,
    borderRadius: 14,
    paddingVertical: 8,
    paddingHorizontal: 16,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 10,
  },
  plateHeader: {
    marginBottom: 2,
  },
  plateHeaderText: {
    fontSize: 10,
    fontWeight: "900",
    letterSpacing: 2,
  },
  plateInput: {
    fontSize: 24,
    fontWeight: "900",
    letterSpacing: 4,
    textAlign: "center",
    width: "100%",
    paddingVertical: 4,
  },
  sinPlacasToggleCard: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
  },
  sinPlacasToggleText: {
    fontSize: 12,
    fontWeight: "600",
  },

  // Tipo de vehículo
  vehicleTypeGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  vehicleTypeCard: {
    flex: 1,
    minWidth: "45%",
    flexDirection: "row",
    alignItems: "center",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    gap: 8,
  },
  vehicleTypeCardText: {
    fontSize: 12,
    fontWeight: "600",
  },

  // Switch Card
  switchToggleCard: {
    flexDirection: "row",
    alignItems: "center",
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 16,
  },
  switchTitle: {
    fontSize: 14,
    fontWeight: "700",
    marginBottom: 2,
  },
  switchDesc: {
    fontSize: 11,
    lineHeight: 15,
  },
  infoBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 14,
  },
  infoBannerText: {
    fontSize: 12,
    lineHeight: 16,
    flex: 1,
    fontWeight: "600",
  },

  // Falta seleccionada
  selectedOffenseCard: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
  },
  selectedOffenseHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },
  categoryBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    borderWidth: 1,
  },
  categoryBadgeText: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  articlePill: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  articlePillText: {
    fontSize: 11,
    fontWeight: "800",
  },
  selectedOffenseDesc: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: "600",
    marginBottom: 14,
  },
  calculationBar: {
    flexDirection: "row",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 14,
  },
  calcCol: {
    flex: 1,
  },
  calcColLabel: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  calcColValue: {
    fontSize: 13,
    fontWeight: "800",
  },
  calcDivider: {
    width: 1,
    height: "100%",
    backgroundColor: "rgba(150, 150, 150, 0.2)",
    marginHorizontal: 10,
  },
  changeOffenseBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderRadius: 12,
    gap: 8,
  },
  changeOffenseBtnText: {
    color: "#ffffff",
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.5,
  },

  // Garantías
  garantiasGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
  },
  garantiaCard: {
    width: "48%",
    flexDirection: "row",
    alignItems: "center",
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    gap: 10,
  },
  garantiaCardText: {
    fontSize: 12,
    fontWeight: "600",
    flex: 1,
  },

  // Evidencias Fotográficas
  demoPhotosButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginBottom: 16,
    gap: 6,
  },
  demoPhotosButtonText: {
    fontSize: 13,
    fontWeight: "700",
  },
  photosGridList: {
    gap: 14,
  },
  photoCardItem: {
    borderRadius: 16,
    borderWidth: 1,
    padding: 14,
  },
  photoCardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },
  photoCardTitle: {
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  photoStatusPill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  photoStatusText: {
    fontSize: 10,
    fontWeight: "800",
  },
  photoPreviewWrapper: {
    borderRadius: 12,
    overflow: "hidden",
  },
  photoPreviewImage: {
    width: "100%",
    height: 160,
    borderRadius: 12,
  },
  photoActionsRow: {
    flexDirection: "row",
    gap: 10,
    marginTop: 8,
  },
  photoActionBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 8,
    borderRadius: 8,
    gap: 6,
  },
  photoActionBtnText: {
    fontSize: 12,
    fontWeight: "700",
  },
  photoPlaceholderBox: {
    borderRadius: 12,
    paddingVertical: 24,
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  photoPlaceholderText: {
    fontSize: 14,
    fontWeight: "700",
  },
  photoPlaceholderSub: {
    fontSize: 11,
  },

  // Resumen Final
  summaryCard: {
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    marginBottom: 20,
  },
  summaryTitle: {
    fontSize: 17,
    fontWeight: "800",
    marginBottom: 14,
  },
  summaryItemRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
  },
  summaryKey: {
    fontSize: 13,
  },
  summaryVal: {
    fontSize: 14,
    fontWeight: "800",
  },
  offlineAssuranceBox: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    marginTop: 6,
    marginBottom: 16,
  },
  offlineAssuranceText: {
    fontSize: 11,
    lineHeight: 16,
    flex: 1,
  },
  saveBigButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    height: 56,
    borderRadius: 14,
    gap: 10,
  },
  saveBigButtonText: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  buttonDisabled: {
    opacity: 0.6,
  },

  // Modales
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.75)",
    justifyContent: "flex-end",
  },
  modalCatalogContent: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "88%",
    borderWidth: 1,
    paddingTop: 16,
  },
  modalCatalogHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingBottom: 12,
    borderBottomWidth: 1,
  },
  modalCatSubtitle: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 0.8,
  },
  modalCatTitle: {
    fontSize: 18,
    fontWeight: "800",
    marginTop: 2,
  },
  closeCatalogBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  searchBarWrap: {
    flexDirection: "row",
    alignItems: "center",
    marginHorizontal: 16,
    marginTop: 14,
    marginBottom: 10,
    height: 46,
    borderRadius: 12,
    borderWidth: 1,
    paddingHorizontal: 14,
    gap: 8,
  },
  searchBarInput: {
    flex: 1,
    fontSize: 13,
    fontWeight: "500",
  },
  categoryChipsScroll: {
    maxHeight: 40,
    marginBottom: 10,
  },
  categoryChipsContent: {
    paddingHorizontal: 16,
    gap: 8,
  },
  catFilterPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    borderWidth: 1,
  },
  catFilterPillText: {
    fontSize: 11,
    fontWeight: "600",
  },
  catalogList: {
    paddingHorizontal: 16,
    paddingBottom: 30,
  },
  catalogItemCard: {
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 10,
  },
  catItemTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  lawTag: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  catItemLaw: {
    fontSize: 12,
    fontWeight: "800",
  },
  catItemUma: {
    fontSize: 12,
    fontWeight: "800",
  },
  catItemDesc: {
    fontSize: 13,
    lineHeight: 18,
    marginBottom: 8,
  },
  catItemBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  catItemCategory: {
    fontSize: 10,
    fontWeight: "700",
  },
  catItemPesos: {
    fontSize: 12,
    fontWeight: "800",
  },

  // Modal Cámara
  cameraOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.85)",
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  cameraModalCard: {
    width: "100%",
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
  },
  cameraHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  cameraTitle: {
    fontSize: 16,
    fontWeight: "800",
  },
  closeCameraBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: "center",
    justifyContent: "center",
  },
  cameraViewfinder: {
    height: 240,
    borderRadius: 16,
    borderWidth: 2,
    borderStyle: "dashed",
    alignItems: "center",
    justifyContent: "center",
    padding: 20,
    marginBottom: 20,
  },
  viewfinderHint: {
    fontSize: 13,
    textAlign: "center",
    marginTop: 14,
    lineHeight: 18,
  },
  shutterBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    height: 52,
    borderRadius: 14,
    gap: 8,
  },
  shutterBtnText: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "800",
  },

  // Modal Foto Grande
  fullPhotoOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.95)",
    justifyContent: "center",
    alignItems: "center",
  },
  closeFullPhotoBtn: {
    position: "absolute",
    top: 50,
    right: 20,
    zIndex: 10,
  },
  fullPhotoImage: {
    width: "92%",
    height: "80%",
  },
});
