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

  // Simulación y captura de foto de alta resolución
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

  const handleGuardarBoleta = async () => {
    // 1. Validaciones
    if (!lugar.trim()) {
      Alert.alert("Dato Requerido", "Debe registrar la ubicación o calle de la infracción.");
      return;
    }

    if (!sinPlacas && !placas.trim()) {
      Alert.alert("Dato Requerido", "Ingrese la placa del vehículo o marque la casilla 'Sin Placas'.");
      return;
    }

    if (!marca.trim()) {
      Alert.alert("Dato Requerido", "Ingrese la marca del vehículo intervenido.");
      return;
    }

    if (!conductorAusente && !nombreInfractor.trim()) {
      Alert.alert("Dato Requerido", "Ingrese el nombre del conductor o marque 'Conductor Ausente'.");
      return;
    }

    // 2. Validación obligatoria de fotos
    if (!fotoPlaca || !fotoContexto || !fotoDocumento) {
      Alert.alert(
        "Evidencias Obligatorias Incompletas",
        "El reglamento exige las 3 evidencias fotográficas para validar la boleta:\n• Foto 1: Placa\n• Foto 2: Contexto\n• Foto 3: Garantía / Documento"
      );
      return;
    }

    // 3. Creación del objeto oficial de Infracción
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
        `Infracción al Reglamento de Movilidad de Uriangato por concepto de: ${faltaSeleccionada.descripcion}.`,
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
      Alert.alert(
        "Boleta Generada Exitosamente",
        `La boleta ${folio} ha sido registrada y guardada de forma segura en la memoria del dispositivo. Se sincronizará automáticamente con Supabase.`,
        [
          {
            text: "Aceptar",
            onPress: () => router.replace("/dashboard"),
          },
        ]
      );
    } catch {
      Alert.alert("Error", "No se pudo guardar la boleta en la base local del dispositivo.");
    }
  };

  const catalogoFiltrado = catalogo.filter(
    (item) =>
      item.descripcion.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.fundamentoLegal.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.categoria.toLowerCase().includes(busquedaCatalogo.toLowerCase())
  );

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={[styles.keyboardContainer, { backgroundColor: colors.background }]}
    >
      <View style={[styles.container, { backgroundColor: colors.background }]}>
        {/* Barra Superior */}
        <View style={[styles.topHeader, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
          <TouchableOpacity
            style={[styles.backButton, { backgroundColor: colors.surfaceElevated }]}
            onPress={() => router.back()}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <Ionicons name="arrow-back" size={22} color={colors.text} />
          </TouchableOpacity>
          <View style={styles.headerTitleWrap}>
            <Text style={[styles.headerTitle, { color: colors.text }]}>NUEVA BOLETA DE INFRACCIÓN</Text>
            <Text style={[styles.headerSubtitle, { color: colors.textSecondary }]}>Municipio de Uriangato, Gto.</Text>
          </View>
          <ThemeToggle compact={true} />
        </View>

        <ScrollView
          style={styles.formScroll}
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
        >
          {/* Banner Folio Asignado */}
          <View style={[styles.folioBanner, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View>
              <Text style={[styles.folioLabel, { color: colors.textMuted }]}>FOLIO OFICIAL ASIGNADO</Text>
              <Text style={[styles.folioNumber, { color: colors.primary }]}>{folio}</Text>
            </View>
            <View style={[styles.badgeAgente, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <Text style={[styles.agentePlacaText, { color: colors.text }]}>{user?.placa || "AGT-204"}</Text>
              <Text style={[styles.agenteCargoText, { color: colors.primary }]}>OFICIAL VIAL</Text>
            </View>
          </View>

          {/* =========================================
              SECCIÓN 1: GENERALES Y GPS
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="time-outline" size={18} color={colors.primary} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>1. GENERALES Y UBICACIÓN GPS</Text>
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>FECHA</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  value={fecha}
                  editable={false}
                />
              </View>

              <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>HORA</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  value={hora}
                  editable={false}
                />
              </View>
            </View>

            <View style={styles.inputGroup}>
              <View style={styles.labelRow}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>LUGAR DE LA INFRACCIÓN / VIALIDAD *</Text>
                <TouchableOpacity
                  style={styles.gpsReloadBtn}
                  onPress={obtenerUbicacionGps}
                  disabled={buscandoGps}
                >
                  {buscandoGps ? (
                    <ActivityIndicator size="small" color={colors.primary} />
                  ) : (
                    <>
                      <Ionicons name="navigate" size={14} color={colors.primary} />
                      <Text style={[styles.gpsReloadText, { color: colors.primary }]}>Actualizar GPS</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
              <TextInput
                style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                placeholder="Ej. Calle Morelos esq. Zaragoza, Zona Centro"
                placeholderTextColor={colors.textMuted}
                value={lugar}
                onChangeText={setLugar}
              />
              {coordenadas && (
                <View style={styles.gpsIndicatorRow}>
                  <Ionicons name="checkmark-circle" size={14} color={colors.success} />
                  <Text style={[styles.gpsCoordsText, { color: colors.textMuted }]}>
                    GPS: {coordenadas.latitud.toFixed(5)}, {coordenadas.longitud.toFixed(5)}
                  </Text>
                </View>
              )}
            </View>
          </View>

          {/* =========================================
              SECCIÓN 2: INFRACTOR
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="person-outline" size={18} color={colors.primary} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>2. DATOS DEL CONDUCTOR</Text>
            </View>

            <View style={[styles.switchRow, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}>
              <View style={{ flex: 1, paddingRight: 10 }}>
                <Text style={[styles.switchTitle, { color: colors.text }]}>Conductor Ausente en el Sitio</Text>
                <Text style={[styles.switchDesc, { color: colors.textSecondary }]}>
                  Vehículo estacionado o abandonado sin conductor a bordo
                </Text>
              </View>
              <Switch
                value={conductorAusente}
                onValueChange={setConductorAusente}
                trackColor={{ false: colors.border, true: colors.primary }}
                thumbColor="#ffffff"
              />
            </View>

            {!conductorAusente && (
              <>
                <View style={styles.inputGroup}>
                  <Text style={[styles.label, { color: colors.textSecondary }]}>NOMBRE COMPLETO DEL CONDUCTOR *</Text>
                  <TextInput
                    style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                    placeholder="Nombre y Apellidos"
                    placeholderTextColor={colors.textMuted}
                    value={nombreInfractor}
                    onChangeText={setNombreInfractor}
                  />
                </View>

                <View style={styles.row}>
                  <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                    <Text style={[styles.label, { color: colors.textSecondary }]}>NÚMERO DE LICENCIA</Text>
                    <TextInput
                      style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                      placeholder="GTO-LIC-00000"
                      placeholderTextColor={colors.textMuted}
                      value={licenciaInfractor}
                      onChangeText={setLicenciaInfractor}
                      autoCapitalize="characters"
                    />
                  </View>
                  <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                    <Text style={[styles.label, { color: colors.textSecondary }]}>DOMICILIO</Text>
                    <TextInput
                      style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                      placeholder="Calle, Número, Colonia"
                      placeholderTextColor={colors.textMuted}
                      value={domicilioInfractor}
                      onChangeText={setDomicilioInfractor}
                    />
                  </View>
                </View>
              </>
            )}
          </View>

          {/* =========================================
              SECCIÓN 3: VEHÍCULO
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="car-sport-outline" size={18} color={colors.primary} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>3. DATOS DEL VEHÍCULO</Text>
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>PLACAS DE CIRCULACIÓN *</Text>
                <TextInput
                  style={[
                    styles.input,
                    { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text },
                    sinPlacas && { opacity: 0.5 },
                  ]}
                  placeholder="GTC-000-A"
                  placeholderTextColor={colors.textMuted}
                  value={placas}
                  onChangeText={(v) => setPlacas(v.toUpperCase())}
                  autoCapitalize="characters"
                  editable={!sinPlacas}
                />
              </View>

              <TouchableOpacity
                style={[
                  styles.sinPlacasCheck,
                  { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                  sinPlacas && { borderColor: colors.warning, backgroundColor: colors.warningBg },
                ]}
                onPress={() => {
                  setSinPlacas(!sinPlacas);
                  if (!sinPlacas) setPlacas("");
                }}
              >
                <Ionicons
                  name={sinPlacas ? "checkbox" : "square-outline"}
                  size={20}
                  color={sinPlacas ? colors.warning : colors.textMuted}
                />
                <Text style={[styles.sinPlacasText, { color: sinPlacas ? colors.warning : colors.textSecondary }]}>
                  Sin Placas
                </Text>
              </TouchableOpacity>
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>MARCA *</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  placeholder="Nissan, VW, Italika..."
                  placeholderTextColor={colors.textMuted}
                  value={marca}
                  onChangeText={setMarca}
                />
              </View>
              <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>LÍNEA / MODELO</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  placeholder="Versa 2022"
                  placeholderTextColor={colors.textMuted}
                  value={lineaModelo}
                  onChangeText={setLineaModelo}
                />
              </View>
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>COLOR</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  placeholder="Blanco, Negro, Azul..."
                  placeholderTextColor={colors.textMuted}
                  value={color}
                  onChangeText={setColor}
                />
              </View>
              <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>TIPO DE VEHÍCULO</Text>
                <View style={styles.tipoVehiculoRow}>
                  {(["particular", "motocicleta", "transporte_publico", "carga"] as TipoVehiculo[]).map(
                    (tipo) => (
                      <TouchableOpacity
                        key={tipo}
                        style={[
                          styles.tipoBtn,
                          { backgroundColor: colors.inputBg, borderColor: colors.inputBorder },
                          tipoVehiculo === tipo && { backgroundColor: colors.primary, borderColor: colors.primary },
                        ]}
                        onPress={() => setTipoVehiculo(tipo)}
                      >
                        <Text
                          style={[
                            styles.tipoBtnText,
                            { color: tipoVehiculo === tipo ? "#ffffff" : colors.textSecondary },
                          ]}
                        >
                          {tipo === "particular"
                            ? "Auto"
                            : tipo === "motocicleta"
                            ? "Moto"
                            : tipo === "transporte_publico"
                            ? "T. Púb"
                            : "Carga"}
                        </Text>
                      </TouchableOpacity>
                    )
                  )}
                </View>
              </View>
            </View>
          </View>

          {/* =========================================
              SECCIÓN 4: FALTA Y FUNDAMENTO LEGAL
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="book-outline" size={18} color={colors.primary} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>4. INFRACCIÓN Y REGLAMENTO</Text>
            </View>

            <TouchableOpacity
              style={[styles.catalogoSelector, { backgroundColor: colors.surfaceElevated, borderColor: colors.border }]}
              onPress={() => setModalCatalogoVisible(true)}
              activeOpacity={0.8}
            >
              <View style={styles.catalogoSelectorHeader}>
                <View style={styles.badgeCategory}>
                  <Text style={styles.badgeCategoryText}>
                    {faltaSeleccionada.categoria}
                  </Text>
                </View>
                <View style={styles.changeLink}>
                  <Text style={[styles.changeLinkText, { color: colors.primary }]}>Cambiar Falta</Text>
                  <Ionicons name="swap-vertical" size={16} color={colors.primary} />
                </View>
              </View>

              <Text style={[styles.legalArticle, { color: colors.danger }]}>
                {faltaSeleccionada.fundamentoLegal}
              </Text>
              <Text style={[styles.legalDesc, { color: colors.text }]}>{faltaSeleccionada.descripcion}</Text>

              <View style={[styles.umaTarifaBar, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <View style={styles.umaItem}>
                  <Text style={[styles.umaLabel, { color: colors.textMuted }]}>SANCIÓN UMAS</Text>
                  <Text style={[styles.umaValue, { color: colors.primary }]}>
                    {faltaSeleccionada.montoMinUma} a {faltaSeleccionada.montoMaxUma} UMAS
                  </Text>
                </View>
                <View style={styles.umaDivider} />
                <View style={styles.umaItem}>
                  <Text style={[styles.umaLabel, { color: colors.textMuted }]}>ESTIMADO EN PESOS</Text>
                  <Text style={[styles.umaValue, { color: colors.success }]}>
                    ${(Number(faltaSeleccionada.montoMinUma) * valorUma).toFixed(2)} - $
                    {(Number(faltaSeleccionada.montoMaxUma) * valorUma).toFixed(2)} MXN
                  </Text>
                </View>
              </View>
            </TouchableOpacity>

            <View style={[styles.inputGroup, { marginTop: 14 }]}>
              <Text style={[styles.label, { color: colors.textSecondary }]}>HECHOS CIRCUNSTANCIADOS (OBSERVACIONES DEL AGENTE)</Text>
              <TextInput
                style={[styles.input, styles.textArea, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                placeholder="Describa brevemente cómo se suscitaron los hechos..."
                placeholderTextColor={colors.textMuted}
                multiline
                numberOfLines={3}
                value={hechos}
                onChangeText={setHechos}
              />
            </View>
          </View>

          {/* =========================================
              SECCIÓN 5: GARANTÍAS RETENIDAS
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="lock-closed-outline" size={18} color={colors.primary} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>5. GARANTÍA RETENIDA</Text>
            </View>

            <View style={styles.garantiasGrid}>
              {[
                { id: "licencia", label: "Licencia de Conducir", icon: "card-outline" },
                { id: "placa", label: "Placa Delantera/Trasera", icon: "pricetag-outline" },
                { id: "tarjeta_circulacion", label: "Tarjeta de Circulación", icon: "document-outline" },
                { id: "vehiculo", label: "Vehículo (Grúa)", icon: "car-outline" },
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
                  >
                    <Ionicons
                      name={checked ? "checkmark-circle" : (item.icon as any)}
                      size={20}
                      color={checked ? colors.primary : colors.textMuted}
                    />
                    <Text
                      style={[
                        styles.garantiaLabel,
                        { color: checked ? colors.primary : colors.textSecondary },
                      ]}
                    >
                      {item.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {garantias.includes("vehiculo") && (
              <View style={[styles.inputGroup, { marginTop: 12 }]}>
                <Text style={[styles.label, { color: colors.textSecondary }]}>FOLIO DE INVENTARIO / EMPRESA DE GRÚA</Text>
                <TextInput
                  style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder, color: colors.text }]}
                  placeholder="Ej. Grúas Uriangato - Inventario #094"
                  placeholderTextColor={colors.textMuted}
                  value={inventarioGrua}
                  onChangeText={setInventarioGrua}
                />
              </View>
            )}
          </View>

          {/* =========================================
              SECCIÓN 6: EVIDENCIAS FOTOGRÁFICAS (3 OBLIGATORIAS)
          ========================================== */}
          <View style={[styles.sectionCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="camera-outline" size={18} color={colors.primary} />
              <View style={{ flex: 1 }}>
                <Text style={[styles.sectionTitle, { color: colors.text }]}>6. EVIDENCIAS FOTOGRÁFICAS (3 OBLIGATORIAS)</Text>
                <Text style={[styles.sectionSubtitle, { color: colors.textSecondary }]}>
                  Requeridas para la validez legal de la infracción en Uriangato
                </Text>
              </View>
            </View>

            <View style={styles.photosUploadGrid}>
              {/* Foto 1: Placa */}
              <TouchableOpacity
                style={[
                  styles.photoUploadBox,
                  { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                  fotoPlaca && { borderColor: colors.success },
                ]}
                onPress={() => {
                  setCapturandoTipoFoto("placa");
                  setModalCamaraVisible(true);
                }}
              >
                {fotoPlaca ? (
                  <>
                    <Image source={{ uri: fotoPlaca }} style={styles.uploadedPhoto} />
                    <View style={styles.photoDoneBadge}>
                      <Ionicons name="checkmark-circle" size={16} color="#10b981" />
                    </View>
                  </>
                ) : (
                  <View style={styles.photoPlaceholder}>
                    <Ionicons name="camera" size={28} color={colors.primary} />
                    <Text style={[styles.photoBoxTitle, { color: colors.text }]}>FOTO 1: PLACA</Text>
                    <Text style={[styles.photoBoxSub, { color: colors.textMuted }]}>Acercamiento nítido</Text>
                  </View>
                )}
              </TouchableOpacity>

              {/* Foto 2: Contexto */}
              <TouchableOpacity
                style={[
                  styles.photoUploadBox,
                  { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                  fotoContexto && { borderColor: colors.success },
                ]}
                onPress={() => {
                  setCapturandoTipoFoto("contexto");
                  setModalCamaraVisible(true);
                }}
              >
                {fotoContexto ? (
                  <>
                    <Image source={{ uri: fotoContexto }} style={styles.uploadedPhoto} />
                    <View style={styles.photoDoneBadge}>
                      <Ionicons name="checkmark-circle" size={16} color="#10b981" />
                    </View>
                  </>
                ) : (
                  <View style={styles.photoPlaceholder}>
                    <Ionicons name="camera" size={28} color={colors.primary} />
                    <Text style={[styles.photoBoxTitle, { color: colors.text }]}>FOTO 2: CONTEXTO</Text>
                    <Text style={[styles.photoBoxSub, { color: colors.textMuted }]}>Vialidad y vehículo</Text>
                  </View>
                )}
              </TouchableOpacity>

              {/* Foto 3: Garantía / Documento */}
              <TouchableOpacity
                style={[
                  styles.photoUploadBox,
                  { backgroundColor: colors.surfaceElevated, borderColor: colors.border },
                  fotoDocumento && { borderColor: colors.success },
                ]}
                onPress={() => {
                  setCapturandoTipoFoto("documento");
                  setModalCamaraVisible(true);
                }}
              >
                {fotoDocumento ? (
                  <>
                    <Image source={{ uri: fotoDocumento }} style={styles.uploadedPhoto} />
                    <View style={styles.photoDoneBadge}>
                      <Ionicons name="checkmark-circle" size={16} color="#10b981" />
                    </View>
                  </>
                ) : (
                  <View style={styles.photoPlaceholder}>
                    <Ionicons name="camera" size={28} color={colors.primary} />
                    <Text style={[styles.photoBoxTitle, { color: colors.text }]}>FOTO 3: GARANTÍA</Text>
                    <Text style={[styles.photoBoxSub, { color: colors.textMuted }]}>Documento retenido</Text>
                  </View>
                )}
              </TouchableOpacity>
            </View>
          </View>

          {/* BOTÓN FINAL DE GUARDADO */}
          <TouchableOpacity
            style={[styles.saveButton, { backgroundColor: colors.primary }]}
            onPress={handleGuardarBoleta}
            activeOpacity={0.85}
          >
            <Ionicons name="save" size={22} color="#ffffff" />
            <Text style={styles.saveButtonText}>EMITIR Y GUARDAR BOLETA OFICIAL</Text>
          </TouchableOpacity>
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
                  <Text style={[styles.modalCatSubtitle, { color: colors.textMuted }]}>REGLAMENTO DE MOVILIDAD</Text>
                  <Text style={[styles.modalCatTitle, { color: colors.text }]}>Seleccionar Infracción</Text>
                </View>
                <TouchableOpacity
                  onPress={() => setModalCatalogoVisible(false)}
                  style={[styles.closeCatalogBtn, { backgroundColor: colors.surfaceElevated }]}
                >
                  <Ionicons name="close" size={22} color={colors.text} />
                </TouchableOpacity>
              </View>

              <View style={[styles.searchBarWrap, { backgroundColor: colors.inputBg, borderColor: colors.inputBorder }]}>
                <Ionicons name="search" size={18} color={colors.textMuted} />
                <TextInput
                  style={[styles.searchBarInput, { color: colors.text }]}
                  placeholder="Buscar por artículo o descripción..."
                  placeholderTextColor={colors.textMuted}
                  value={busquedaCatalogo}
                  onChangeText={setBusquedaCatalogo}
                />
              </View>

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
                    >
                      <View style={styles.catItemTop}>
                        <Text style={[styles.catItemLaw, { color: colors.danger }]}>{item.fundamentoLegal}</Text>
                        <Text style={[styles.catItemUma, { color: colors.primary }]}>
                          {item.montoMinUma} - {item.montoMaxUma} UMAS
                        </Text>
                      </View>
                      <Text style={[styles.catItemDesc, { color: colors.text }]}>{item.descripcion}</Text>
                      <View style={styles.catItemBottom}>
                        <Text style={[styles.catItemCategory, { color: colors.textMuted }]}>{item.categoria}</Text>
                        <Text style={[styles.catItemPesos, { color: colors.success }]}>
                          ${itemMinPesos.toFixed(0)} - ${itemMaxPesos.toFixed(0)} MXN
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
                    : "Capturar Foto 3: Garantía"}
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
              >
                <Ionicons name="camera" size={24} color="#ffffff" />
                <Text style={styles.shutterBtnText}>CAPTURAR FOTOGRAFÍA</Text>
              </TouchableOpacity>
            </View>
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
    paddingTop: 12,
    paddingBottom: 12,
    borderBottomWidth: 1,
    gap: 12,
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
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
  },
  formScroll: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  folioBanner: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 16,
  },
  folioLabel: {
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1,
  },
  folioNumber: {
    fontSize: 18,
    fontWeight: "800",
    marginTop: 2,
  },
  badgeAgente: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    alignItems: "center",
  },
  agentePlacaText: {
    fontSize: 12,
    fontWeight: "800",
  },
  agenteCargoText: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
  },
  sectionCard: {
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    marginBottom: 16,
  },
  sectionHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 14,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  sectionSubtitle: {
    fontSize: 11,
    marginTop: 2,
  },
  row: {
    flexDirection: "row",
    alignItems: "center",
  },
  inputGroup: {
    marginBottom: 12,
  },
  labelRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  label: {
    fontSize: 10,
    fontWeight: "700",
    letterSpacing: 0.8,
    marginBottom: 6,
  },
  input: {
    height: 48,
    borderRadius: 10,
    borderWidth: 1,
    paddingHorizontal: 12,
    fontSize: 14,
  },
  textArea: {
    height: 80,
    paddingTop: 10,
    textAlignVertical: "top",
  },
  gpsReloadBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  gpsReloadText: {
    fontSize: 11,
    fontWeight: "700",
  },
  gpsIndicatorRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginTop: 4,
  },
  gpsCoordsText: {
    fontSize: 11,
  },
  switchRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 12,
  },
  switchTitle: {
    fontSize: 13,
    fontWeight: "700",
  },
  switchDesc: {
    fontSize: 11,
    marginTop: 2,
  },
  sinPlacasCheck: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    height: 48,
    paddingHorizontal: 12,
    borderRadius: 10,
    borderWidth: 1,
    marginTop: 18,
  },
  sinPlacasText: {
    fontSize: 12,
    fontWeight: "700",
  },
  tipoVehiculoRow: {
    flexDirection: "row",
    gap: 6,
  },
  tipoBtn: {
    flex: 1,
    height: 48,
    borderRadius: 10,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  tipoBtnText: {
    fontSize: 11,
    fontWeight: "700",
  },
  catalogoSelector: {
    padding: 14,
    borderRadius: 12,
    borderWidth: 1,
  },
  catalogoSelectorHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  badgeCategory: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: "rgba(59, 130, 246, 0.15)",
  },
  badgeCategoryText: {
    color: "#60a5fa",
    fontSize: 10,
    fontWeight: "800",
  },
  changeLink: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  changeLinkText: {
    fontSize: 12,
    fontWeight: "700",
  },
  legalArticle: {
    fontSize: 14,
    fontWeight: "800",
    marginBottom: 4,
  },
  legalDesc: {
    fontSize: 13,
    lineHeight: 18,
    marginBottom: 10,
  },
  umaTarifaBar: {
    flexDirection: "row",
    borderRadius: 8,
    padding: 10,
    borderWidth: 1,
  },
  umaItem: {
    flex: 1,
  },
  umaLabel: {
    fontSize: 9,
    fontWeight: "700",
    letterSpacing: 0.5,
  },
  umaValue: {
    fontSize: 13,
    fontWeight: "800",
    marginTop: 2,
  },
  umaDivider: {
    width: 1,
    backgroundColor: "rgba(255, 255, 255, 0.1)",
    marginHorizontal: 10,
  },
  garantiasGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  garantiaCard: {
    width: "48%",
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
  },
  garantiaLabel: {
    fontSize: 12,
    fontWeight: "600",
    flex: 1,
  },
  photosUploadGrid: {
    flexDirection: "row",
    gap: 10,
  },
  photoUploadBox: {
    flex: 1,
    height: 120,
    borderRadius: 12,
    borderWidth: 1.5,
    borderStyle: "dashed",
    overflow: "hidden",
    alignItems: "center",
    justifyContent: "center",
  },
  photoPlaceholder: {
    alignItems: "center",
    padding: 6,
  },
  photoBoxTitle: {
    fontSize: 10,
    fontWeight: "800",
    marginTop: 6,
    textAlign: "center",
  },
  photoBoxSub: {
    fontSize: 9,
    textAlign: "center",
  },
  uploadedPhoto: {
    width: "100%",
    height: "100%",
  },
  photoDoneBadge: {
    position: "absolute",
    top: 6,
    right: 6,
    backgroundColor: "#ffffff",
    borderRadius: 10,
  },
  saveButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 16,
    borderRadius: 14,
    gap: 10,
    marginTop: 8,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  saveButtonText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.75)",
    justifyContent: "flex-end",
  },
  modalCatalogContent: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "85%",
    borderWidth: 1,
    paddingBottom: 24,
  },
  modalCatalogHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderBottomWidth: 1,
  },
  modalCatSubtitle: {
    fontSize: 10,
    fontWeight: "700",
    letterSpacing: 1,
  },
  modalCatTitle: {
    fontSize: 17,
    fontWeight: "800",
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
    marginTop: 12,
    marginBottom: 8,
    paddingHorizontal: 12,
    height: 44,
    borderRadius: 10,
    borderWidth: 1,
    gap: 8,
  },
  searchBarInput: {
    flex: 1,
    fontSize: 14,
  },
  catalogList: {
    paddingHorizontal: 16,
  },
  catalogItemCard: {
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 8,
  },
  catItemTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  catItemLaw: {
    fontSize: 13,
    fontWeight: "800",
  },
  catItemUma: {
    fontSize: 11,
    fontWeight: "700",
  },
  catItemDesc: {
    fontSize: 12,
    lineHeight: 16,
  },
  catItemBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginTop: 6,
  },
  catItemCategory: {
    fontSize: 10,
    fontWeight: "600",
  },
  catItemPesos: {
    fontSize: 11,
    fontWeight: "700",
  },
  cameraOverlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.8)",
    justifyContent: "center",
    padding: 20,
  },
  cameraModalCard: {
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
  },
  cameraHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 14,
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
    height: 220,
    borderRadius: 14,
    borderWidth: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 20,
    marginBottom: 16,
  },
  viewfinderHint: {
    fontSize: 12,
    textAlign: "center",
    marginTop: 14,
    lineHeight: 16,
  },
  shutterBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 14,
    borderRadius: 12,
    gap: 8,
  },
  shutterBtnText: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "800",
  },
});
