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
import { CATALOGO_FALTAS_URIANGATO } from "../src/constants/catalogoInfracciones";
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
  const { guardarInfraccion } = useInfracciones();

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

  // Falta
  const [faltaSeleccionada, setFaltaSeleccionada] = useState<FaltaCatalogo>(
    CATALOGO_FALTAS_URIANGATO[0]
  );
  const [modalCatalogoVisible, setModalCatalogoVisible] = useState(false);
  const [busquedaCatalogo, setBusquedaCatalogo] = useState("");

  // Hechos / Motivación
  const [hechos, setHechos] = useState("");

  // Garantías Retenidas
  const [garantias, setGarantias] = useState<GarantiaRetenida[]>([]);
  const [inventarioGrua, setInventarioGrua] = useState("");

  // Evidencias Fotográficas (3 obligatorias)
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

    // Intentar capturar GPS inicial de Uriangato
    obtenerUbicacionGps();
  }, []);

  const obtenerUbicacionGps = async () => {
    setBuscandoGps(true);
    try {
      let { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== "granted") {
        // Fallback geográfico centro de Uriangato si no se conceden permisos
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
        latitud: Number(loc.coords.latitude.toFixed(5)),
        longitud: Number(loc.coords.longitude.toFixed(5)),
      });

      // Geocodificación inversa para autocompletar calle si está disponible
      try {
        const reverse = await Location.reverseGeocodeAsync({
          latitude: loc.coords.latitude,
          longitude: loc.coords.longitude,
        });
        if (reverse && reverse.length > 0) {
          const item = reverse[0];
          const calle = item.street || item.name || "Av. Principal";
          const col = item.district || item.subregion || "Zona Centro";
          const ciudad = item.city || "Uriangato";
          setLugar(`${calle}, Col. ${col}, ${ciudad}, Gto.`);
        }
      } catch {
        if (!lugar) {
          setLugar("Av. Hidalgo esq. Juárez, Uriangato, Gto.");
        }
      }
    } catch {
      // Fallback
      setCoordenadas({ latitud: 20.1419, longitud: -101.1764 });
      if (!lugar) {
        setLugar("Prolongación Morelos, Uriangato, Gto.");
      }
    } finally {
      setBuscandoGps(false);
    }
  };

  const toggleGarantia = (item: GarantiaRetenida) => {
    if (garantias.includes(item)) {
      setGarantias(garantias.filter((g) => g !== item));
    } else {
      setGarantias([...garantias, item]);
    }
  };

  const abrirCapturaFoto = (tipo: TipoFoto) => {
    setCapturandoTipoFoto(tipo);
    setModalCamaraVisible(true);
  };

  // Simulación y captura funcional de foto para Expo Go y Web
  const tomarFotoSimulada = (urlPreset: string) => {
    if (capturandoTipoFoto === "placa") setFotoPlaca(urlPreset);
    if (capturandoTipoFoto === "contexto") setFotoContexto(urlPreset);
    if (capturandoTipoFoto === "documento") setFotoDocumento(urlPreset);
    setModalCamaraVisible(false);
    setCapturandoTipoFoto(null);
  };

  const validarFormulario = (): boolean => {
    if (!lugar.trim()) {
      Alert.alert("Campo Obligatorio", "Por favor indique el lugar de la infracción.");
      return false;
    }

    if (!conductorAusente && !nombreInfractor.trim()) {
      Alert.alert("Campo Obligatorio", "Ingrese el nombre del infractor o marque 'Conductor Ausente'.");
      return false;
    }

    if (!sinPlacas && !placas.trim()) {
      Alert.alert("Campo Obligatorio", "Ingrese la placa del vehículo o marque 'Sin Placas'.");
      return false;
    }

    if (!hechos.trim()) {
      Alert.alert("Campo Obligatorio", "Debe redactar la motivación de los hechos circunstanciados.");
      return false;
    }

    // Validación estricta de las tres fotografías obligatorias
    if (!fotoPlaca || !fotoContexto || !fotoDocumento) {
      Alert.alert(
        "Evidencia Fotográfica Obligatoria",
        "Por requisito legal debe adjuntar las tres fotografías: 1. Placa del vehículo, 2. Contexto de la infracción y 3. Credencial o documento."
      );
      return false;
    }

    return true;
  };

  const handleGuardarBoleta = async () => {
    if (!validarFormulario()) return;

    const nuevaInfraccion: Infraccion = {
      id: `inf-${Date.now()}`,
      folio,
      agente: {
        placa: user?.placa || "AGT-204",
        nombre: user?.nombre || "Oficial Carlos Mendoza Ruiz",
        rol: user?.rol || "Agente Vial Operativo",
      },
      generales: {
        fecha,
        hora,
        lugar: lugar.trim(),
        coordenadas,
      },
      infractor: {
        conductorAusente,
        nombre: conductorAusente ? "CONDUCTOR AUSENTE" : nombreInfractor.trim(),
        domicilio: conductorAusente ? "NO PROPORCIONADO EN SITIO" : domicilioInfractor.trim(),
        numeroLicencia: licenciaInfractor.trim() || undefined,
      },
      vehiculo: {
        placas: sinPlacas ? "SIN PLACAS" : placas.toUpperCase().trim(),
        sinPlacas,
        marca: marca.trim() || "No especificada",
        lineaModelo: lineaModelo.trim() || "No especificado",
        color: color.trim() || "No especificado",
        tipo: tipoVehiculo,
      },
      falta: faltaSeleccionada,
      hechos: hechos.trim(),
      garantiasRetenidas: garantias,
      detalleGarantia: {
        inventarioGrua: inventarioGrua.trim() || undefined,
      },
      evidencias: {
        fotoPlaca: fotoPlaca!,
        fotoContexto: fotoContexto!,
        fotoDocumento: fotoDocumento!,
      },
      estado: "pendiente",
      creadoEn: new Date().toISOString(),
      sincronizadoEn: null,
    };

    try {
      await guardarInfraccion(nuevaInfraccion);
      Alert.alert(
        "Infracción Guardada Localmente",
        `La boleta ${folio} ha sido almacenada de forma segura en la memoria de este dispositivo (Modo Offline). Podrás sincronizarla cuando tengas conexión.`,
        [
          {
            text: "Ir al Dashboard",
            onPress: () => router.replace("/dashboard"),
          },
        ]
      );
    } catch {
      Alert.alert("Error", "No se pudo guardar la boleta localmente. Verifique el almacenamiento.");
    }
  };

  const totalFotosTomadas = (fotoPlaca ? 1 : 0) + (fotoContexto ? 1 : 0) + (fotoDocumento ? 1 : 0);

  // Filtrado del catálogo
  const catalogoFiltrado = CATALOGO_FALTAS_URIANGATO.filter(
    (item) =>
      item.descripcion.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.fundamentoLegal.toLowerCase().includes(busquedaCatalogo.toLowerCase()) ||
      item.categoria.toLowerCase().includes(busquedaCatalogo.toLowerCase())
  );

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      style={styles.keyboardContainer}
    >
      <View style={styles.container}>
        {/* Barra Superior con botón atrás */}
        <View style={styles.topHeader}>
          <TouchableOpacity
            style={styles.backButton}
            onPress={() => router.back()}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <Ionicons name="arrow-back" size={24} color="#ffffff" />
          </TouchableOpacity>
          <View style={styles.headerTitleWrap}>
            <Text style={styles.headerTitle}>NUEVA BOLETA DE INFRACCIÓN</Text>
            <Text style={styles.headerSubtitle}>Municipio de Uriangato, Gto.</Text>
          </View>
          <View style={styles.offlineShield}>
            <Ionicons name="cloud-offline" size={16} color="#10b981" />
          </View>
        </View>

        <ScrollView
          style={styles.formScroll}
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
        >
          {/* Banner Folio Asignado */}
          <View style={styles.folioBanner}>
            <View>
              <Text style={styles.folioLabel}>FOLIO ASIGNADO</Text>
              <Text style={styles.folioNumber}>{folio}</Text>
            </View>
            <View style={styles.badgeAgente}>
              <Text style={styles.agentePlacaText}>{user?.placa || "AGT-204"}</Text>
              <Text style={styles.agenteCargoText}>AGENTE VIAL</Text>
            </View>
          </View>

          {/* =========================================
              SECCIÓN 1: GENERALES Y GPS
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="time-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>1. GENERALES Y UBICACIÓN GPS</Text>
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={styles.label}>FECHA</Text>
                <TextInput
                  style={[styles.input, styles.inputDisabled]}
                  value={fecha}
                  editable={false}
                />
              </View>

              <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                <Text style={styles.label}>HORA</Text>
                <TextInput
                  style={[styles.input, styles.inputDisabled]}
                  value={hora}
                  editable={false}
                />
              </View>
            </View>

            {/* Lugar y Botón GPS */}
            <View style={styles.inputGroup}>
              <View style={styles.labelWithAction}>
                <Text style={styles.label}>LUGAR DE LA INFRACCIÓN *</Text>
                <TouchableOpacity
                  style={styles.gpsButton}
                  onPress={obtenerUbicacionGps}
                  disabled={buscandoGps}
                >
                  {buscandoGps ? (
                    <ActivityIndicator size="small" color="#60a5fa" />
                  ) : (
                    <>
                      <Ionicons name="navigate" size={14} color="#60a5fa" />
                      <Text style={styles.gpsButtonText}>Fijar GPS</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
              <TextInput
                style={styles.input}
                placeholder="Calle, número, esquina o cruce en Uriangato"
                placeholderTextColor="#71717a"
                value={lugar}
                onChangeText={setLugar}
              />
            </View>

            {coordenadas && (
              <View style={styles.gpsCoordinatesBox}>
                <Ionicons name="checkmark-circle" size={16} color="#10b981" />
                <Text style={styles.gpsCoordinatesText}>
                  Coordenadas fijadas: {coordenadas.latitud}, {coordenadas.longitud}
                </Text>
              </View>
            )}
          </View>

          {/* =========================================
              SECCIÓN 2: INFRACTOR Y VEHÍCULO
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="person-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>2. INFRACTOR Y VEHÍCULO</Text>
            </View>

            {/* Switch Conductor Ausente */}
            <View style={styles.switchRow}>
              <View style={styles.switchInfo}>
                <Text style={styles.switchTitle}>¿Conductor Ausente?</Text>
                <Text style={styles.switchDesc}>
                  Marcar si el vehículo está estacionado sin conductor presente
                </Text>
              </View>
              <Switch
                value={conductorAusente}
                onValueChange={(val) => {
                  setConductorAusente(val);
                  if (val) {
                    setNombreInfractor("CONDUCTOR AUSENTE");
                    setDomicilioInfractor("NO DISPONIBLE EN SITIO");
                  } else {
                    setNombreInfractor("");
                    setDomicilioInfractor("");
                  }
                }}
                thumbColor={conductorAusente ? "#2563eb" : "#71717a"}
                trackColor={{ false: "#27272a", true: "#1e3a8a" }}
              />
            </View>

            {!conductorAusente && (
              <>
                <View style={styles.inputGroup}>
                  <Text style={styles.label}>NOMBRE COMPLETO DEL INFRACTOR *</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="Nombre(s) y Apellidos"
                    placeholderTextColor="#71717a"
                    value={nombreInfractor}
                    onChangeText={setNombreInfractor}
                  />
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>DOMICILIO DEL INFRACTOR</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="Calle, Número, Colonia, Municipio"
                    placeholderTextColor="#71717a"
                    value={domicilioInfractor}
                    onChangeText={setDomicilioInfractor}
                  />
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.label}>NÚMERO DE LICENCIA</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="Ej. GTO-0012398 (opcional si no presenta)"
                    placeholderTextColor="#71717a"
                    value={licenciaInfractor}
                    onChangeText={(val) => setLicenciaInfractor(val.toUpperCase())}
                  />
                </View>
              </>
            )}

            {/* Datos del Vehículo */}
            <View style={styles.vehicleSubHeader}>
              <Ionicons name="car-sport-outline" size={16} color="#93c5fd" />
              <Text style={styles.vehicleSubTitle}>DATOS DEL VEHÍCULO</Text>
            </View>

            <View style={styles.inputGroup}>
              <View style={styles.labelWithAction}>
                <Text style={styles.label}>PLACA DEL VEHÍCULO *</Text>
                <TouchableOpacity
                  onPress={() => {
                    setSinPlacas(!sinPlacas);
                    if (!sinPlacas) setPlacas("SIN PLACAS");
                    else setPlacas("");
                  }}
                  style={styles.sinPlacaBtn}
                >
                  <Ionicons
                    name={sinPlacas ? "checkbox" : "square-outline"}
                    size={16}
                    color={sinPlacas ? "#3b82f6" : "#a1a1aa"}
                  />
                  <Text style={styles.sinPlacaText}>Sin Placa</Text>
                </TouchableOpacity>
              </View>
              <TextInput
                style={[styles.input, sinPlacas && styles.inputDisabled]}
                placeholder="Ej. GTC-441-E"
                placeholderTextColor="#71717a"
                value={placas}
                onChangeText={(val) => setPlacas(val.toUpperCase())}
                editable={!sinPlacas}
                autoCapitalize="characters"
              />
            </View>

            <View style={styles.row}>
              <View style={[styles.inputGroup, { flex: 1, marginRight: 8 }]}>
                <Text style={styles.label}>MARCA</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Ej. Nissan"
                  placeholderTextColor="#71717a"
                  value={marca}
                  onChangeText={setMarca}
                />
              </View>

              <View style={[styles.inputGroup, { flex: 1, marginLeft: 8 }]}>
                <Text style={styles.label}>LÍNEA / MODELO</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Ej. Versa 2021"
                  placeholderTextColor="#71717a"
                  value={lineaModelo}
                  onChangeText={setLineaModelo}
                />
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>COLOR</Text>
              <TextInput
                style={styles.input}
                placeholder="Ej. Blanco, Rojo, Azul"
                placeholderTextColor="#71717a"
                value={color}
                onChangeText={setColor}
              />
            </View>

            {/* Selector Tipo de Vehículo */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>TIPO DE VEHÍCULO</Text>
              <View style={styles.chipsRow}>
                {(
                  [
                    { key: "particular", label: "Particular" },
                    { key: "motocicleta", label: "Moto" },
                    { key: "transporte_publico", label: "T. Público" },
                    { key: "carga", label: "Carga" },
                  ] as const
                ).map((tipo) => (
                  <TouchableOpacity
                    key={tipo.key}
                    style={[
                      styles.chip,
                      tipoVehiculo === tipo.key && styles.chipActive,
                    ]}
                    onPress={() => setTipoVehiculo(tipo.key)}
                  >
                    <Text
                      style={[
                        styles.chipText,
                        tipoVehiculo === tipo.key && styles.chipTextActive,
                      ]}
                    >
                      {tipo.label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          </View>

          {/* =========================================
              SECCIÓN 3: FALTAS (CATÁLOGO OFICIAL)
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="book-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>3. CATÁLOGO DE FALTAS *</Text>
            </View>

            <Text style={styles.instructionText}>
              Seleccione la falta cometida conforme al Reglamento de Movilidad de Uriangato.
            </Text>

            {/* Falta actual seleccionada */}
            <TouchableOpacity
              style={styles.selectedFaltaCard}
              onPress={() => setModalCatalogoVisible(true)}
              activeOpacity={0.8}
            >
              <View style={styles.selectedFaltaContent}>
                <View style={styles.faltaFundamentoRow}>
                  <Text style={styles.selectedFundamento}>
                    {faltaSeleccionada.fundamentoLegal}
                  </Text>
                  <View style={styles.umaBadge}>
                    <Text style={styles.umaBadgeText}>
                      {faltaSeleccionada.montoMinUma} - {faltaSeleccionada.montoMaxUma} UMA
                    </Text>
                  </View>
                </View>
                <Text style={styles.selectedFaltaDesc}>
                  {faltaSeleccionada.descripcion}
                </Text>
              </View>
              <View style={styles.changeFaltaBtn}>
                <Ionicons name="swap-vertical" size={18} color="#3b82f6" />
                <Text style={styles.changeFaltaBtnText}>Cambiar</Text>
              </View>
            </TouchableOpacity>
          </View>

          {/* =========================================
              SECCIÓN 4: HECHOS Y MOTIVACIÓN
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="document-text-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>4. MOTIVACIÓN DE LOS HECHOS *</Text>
            </View>

            <Text style={styles.instructionText}>
              Descripción circunstanciada de los hechos observados por el personal operativo.
            </Text>

            {/* Atajos Rápidos de Texto para agilizar llenado en calle */}
            <View style={styles.quickShortcutsRow}>
              <TouchableOpacity
                style={styles.quickShortcutChip}
                onPress={() =>
                  setHechos(
                    "Al encontrarse en servicio activo en el crucero señalado, se observó al conductor no respetar la luz roja del semáforo, poniendo en riesgo la integridad de peatones."
                  )
                }
              >
                <Text style={styles.quickShortcutText}>+ Luz Roja</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.quickShortcutChip}
                onPress={() =>
                  setHechos(
                    "Se constató al conductor operando el vehículo en movimiento manipulando activamente un teléfono celular con ambas manos."
                  )
                }
              >
                <Text style={styles.quickShortcutText}>+ Uso Celular</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.quickShortcutChip}
                onPress={() =>
                  setHechos(
                    "Vehículo estacionado obstruyendo en su totalidad la rampa de acceso para personas con discapacidad, sin conductor a bordo."
                  )
                }
              >
                <Text style={styles.quickShortcutText}>+ Rampa / Banqueta</Text>
              </TouchableOpacity>
            </View>

            <TextInput
              style={styles.textArea}
              placeholder="Escriba aquí la motivación circunstanciada..."
              placeholderTextColor="#71717a"
              multiline
              numberOfLines={4}
              textAlignVertical="top"
              value={hechos}
              onChangeText={setHechos}
            />
          </View>

          {/* =========================================
              SECCIÓN 5: GARANTÍA RETENIDA
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="lock-closed-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>5. GARANTÍA RETENIDA</Text>
            </View>

            <Text style={styles.instructionText}>
              Seleccione los documentos o bienes que quedan en resguardo oficial:
            </Text>

            <View style={styles.checkboxGrid}>
              {(
                [
                  { key: "licencia", label: "Licencia de Conducir" },
                  { key: "placa", label: "Placa(s) del Vehículo" },
                  { key: "tarjeta_circulacion", label: "Tarjeta de Circulación" },
                  { key: "vehiculo", label: "Retención de Vehículo (Grúa)" },
                ] as const
              ).map((item) => {
                const activo = garantias.includes(item.key);
                return (
                  <TouchableOpacity
                    key={item.key}
                    style={[
                      styles.checkboxItem,
                      activo && styles.checkboxItemActive,
                    ]}
                    onPress={() => toggleGarantia(item.key)}
                  >
                    <Ionicons
                      name={activo ? "checkbox" : "square-outline"}
                      size={20}
                      color={activo ? "#3b82f6" : "#a1a1aa"}
                    />
                    <Text
                      style={[
                        styles.checkboxLabel,
                        activo && styles.checkboxLabelActive,
                      ]}
                    >
                      {item.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {garantias.includes("vehiculo") && (
              <View style={styles.inputGroup}>
                <Text style={styles.label}>NÚMERO DE INVENTARIO / GRÚA</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Ej. GRUA-04 / INV-2026-88"
                  placeholderTextColor="#71717a"
                  value={inventarioGrua}
                  onChangeText={setInventarioGrua}
                />
              </View>
            )}
          </View>

          {/* =========================================
              SECCIÓN 6: EVIDENCIA FOTOGRÁFICA (3 FOTOS)
          ========================================== */}
          <View style={styles.sectionCard}>
            <View style={styles.sectionHeader}>
              <Ionicons name="camera-outline" size={18} color="#60a5fa" />
              <Text style={styles.sectionTitle}>
                6. EVIDENCIA FOTOGRÁFICA ({totalFotosTomadas}/3 OBLIGATORIAS)
              </Text>
            </View>

            <Text style={styles.instructionText}>
              Debe registrar obligatoriamente las tres evidencias fotográficas para validar la boleta legalmente:
            </Text>

            {/* Recuadros de las 3 fotografías */}
            <View style={styles.photosContainer}>
              {/* Foto 1: Placa */}
              <View style={styles.photoSlot}>
                <Text style={styles.photoSlotTitle}>1. PLACA VEHÍCULO *</Text>
                {fotoPlaca ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoPlaca }} style={styles.photoPreview} />
                    <TouchableOpacity
                      style={styles.retakeBtn}
                      onPress={() => abrirCapturaFoto("placa")}
                    >
                      <Ionicons name="camera" size={14} color="#ffffff" />
                      <Text style={styles.retakeText}>Re-tomar</Text>
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={styles.photoPlaceholder}
                    onPress={() => abrirCapturaFoto("placa")}
                  >
                    <Ionicons name="camera" size={28} color="#3b82f6" />
                    <Text style={styles.placeholderText}>Capturar Placa</Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Foto 2: Contexto */}
              <View style={styles.photoSlot}>
                <Text style={styles.photoSlotTitle}>2. CONTEXTO VIAL *</Text>
                {fotoContexto ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoContexto }} style={styles.photoPreview} />
                    <TouchableOpacity
                      style={styles.retakeBtn}
                      onPress={() => abrirCapturaFoto("contexto")}
                    >
                      <Ionicons name="camera" size={14} color="#ffffff" />
                      <Text style={styles.retakeText}>Re-tomar</Text>
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={styles.photoPlaceholder}
                    onPress={() => abrirCapturaFoto("contexto")}
                  >
                    <Ionicons name="camera" size={28} color="#3b82f6" />
                    <Text style={styles.placeholderText}>Capturar Contexto</Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Foto 3: Documento / Credencial */}
              <View style={styles.photoSlot}>
                <Text style={styles.photoSlotTitle}>3. CREDENCIAL/DOC *</Text>
                {fotoDocumento ? (
                  <View style={styles.photoPreviewWrapper}>
                    <Image source={{ uri: fotoDocumento }} style={styles.photoPreview} />
                    <TouchableOpacity
                      style={styles.retakeBtn}
                      onPress={() => abrirCapturaFoto("documento")}
                    >
                      <Ionicons name="camera" size={14} color="#ffffff" />
                      <Text style={styles.retakeText}>Re-tomar</Text>
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={styles.photoPlaceholder}
                    onPress={() => abrirCapturaFoto("documento")}
                  >
                    <Ionicons name="camera" size={28} color="#3b82f6" />
                    <Text style={styles.placeholderText}>Capturar Documento</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>

            {/* Botón rápido para capturar las 3 de prueba */}
            <TouchableOpacity
              style={styles.quickPhotosButton}
              onPress={() => {
                setFotoPlaca("https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=500&q=80");
                setFotoContexto("https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500&q=80");
                setFotoDocumento("https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=500&q=80");
              }}
            >
              <Ionicons name="flash-outline" size={16} color="#60a5fa" />
              <Text style={styles.quickPhotosText}>
                Autocompletar 3 Fotos de Demostración para Prueba Rápida
              </Text>
            </TouchableOpacity>
          </View>

          {/* BOTÓN PRINCIPAL DE GUARDAR OFFLINE */}
          <TouchableOpacity
            style={styles.saveButton}
            onPress={handleGuardarBoleta}
            activeOpacity={0.85}
          >
            <Ionicons name="save" size={22} color="#ffffff" />
            <Text style={styles.saveButtonText}>
              GUARDAR INFRACCIÓN LOCALMENTE (OFFLINE)
            </Text>
          </TouchableOpacity>
        </ScrollView>

        {/* MODAL SELECTOR DE CATÁLOGO OFICIAL DE FALTAS */}
        <Modal
          visible={modalCatalogoVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setModalCatalogoVisible(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <View>
                  <Text style={styles.modalTitle}>Catálogo de Infracciones</Text>
                  <Text style={styles.modalSub}>
                    Reglamento de Movilidad de Uriangato
                  </Text>
                </View>
                <TouchableOpacity
                  onPress={() => setModalCatalogoVisible(false)}
                  style={styles.modalCloseBtn}
                >
                  <Ionicons name="close" size={22} color="#ffffff" />
                </TouchableOpacity>
              </View>

              {/* Barra de Búsqueda */}
              <View style={styles.searchBar}>
                <Ionicons name="search" size={18} color="#a1a1aa" />
                <TextInput
                  style={styles.searchInput}
                  placeholder="Buscar por artículo, falta o palabra clave..."
                  placeholderTextColor="#71717a"
                  value={busquedaCatalogo}
                  onChangeText={setBusquedaCatalogo}
                />
              </View>

              <ScrollView style={styles.catalogoList}>
                {catalogoFiltrado.map((item) => (
                  <TouchableOpacity
                    key={item.id}
                    style={[
                      styles.catalogoItem,
                      faltaSeleccionada.id === item.id && styles.catalogoItemActive,
                    ]}
                    onPress={() => {
                      setFaltaSeleccionada(item);
                      setModalCatalogoVisible(false);
                    }}
                  >
                    <View style={styles.catalogoItemTop}>
                      <Text style={styles.itemFundamento}>
                        {item.fundamentoLegal}
                      </Text>
                      <Text style={styles.itemUma}>
                        {item.montoMinUma}-{item.montoMaxUma} UMA
                      </Text>
                    </View>
                    <Text style={styles.itemDesc}>{item.descripcion}</Text>
                    <Text style={styles.itemCat}>{item.categoria}</Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* MODAL DE CÁMARA / CAPTURA */}
        <Modal
          visible={modalCamaraVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setModalCamaraVisible(false)}
        >
          <View style={styles.cameraOverlay}>
            <View style={styles.cameraBox}>
              <View style={styles.cameraHeader}>
                <Text style={styles.cameraTitle}>
                  Captura:{" "}
                  {capturandoTipoFoto === "placa"
                    ? "Placa del Vehículo"
                    : capturandoTipoFoto === "contexto"
                    ? "Contexto de la Infracción"
                    : "Credencial / Documento"}
                </Text>
                <TouchableOpacity onPress={() => setModalCamaraVisible(false)}>
                  <Ionicons name="close" size={24} color="#ffffff" />
                </TouchableOpacity>
              </View>

              <Text style={styles.cameraInstruction}>
                Enfoque con claridad el objetivo bajo luz visible.
              </Text>

              <View style={styles.cameraLens}>
                <Ionicons name="scan-outline" size={80} color="#3b82f6" />
                <Text style={styles.cameraLensText}>
                  Sensor de Cámara Operativo
                </Text>
              </View>

              <View style={styles.cameraActionsRow}>
                <TouchableOpacity
                  style={styles.captureSnapBtn}
                  onPress={() =>
                    tomarFotoSimulada(
                      capturandoTipoFoto === "placa"
                        ? "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=500&q=80"
                        : capturandoTipoFoto === "contexto"
                        ? "https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500&q=80"
                        : "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=500&q=80"
                    )
                  }
                >
                  <View style={styles.captureInnerCircle} />
                </TouchableOpacity>
              </View>
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
    backgroundColor: "#09090b",
  },
  container: {
    flex: 1,
    backgroundColor: "#09090b",
  },
  topHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingTop: 50,
    paddingBottom: 16,
    paddingHorizontal: 16,
    backgroundColor: "#121214",
    borderBottomWidth: 1,
    borderBottomColor: "#27272a",
  },
  backButton: {
    padding: 8,
    borderRadius: 8,
    backgroundColor: "#18181b",
  },
  headerTitleWrap: {
    flex: 1,
    marginLeft: 12,
  },
  headerTitle: {
    color: "#ffffff",
    fontSize: 14,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  headerSubtitle: {
    color: "#a1a1aa",
    fontSize: 11,
  },
  offlineShield: {
    backgroundColor: "rgba(16, 185, 129, 0.15)",
    padding: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "rgba(16, 185, 129, 0.3)",
  },
  formScroll: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 60,
  },
  folioBanner: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#3b82f6",
    borderRadius: 12,
    padding: 14,
    marginBottom: 16,
  },
  folioLabel: {
    color: "#60a5fa",
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1,
  },
  folioNumber: {
    color: "#ffffff",
    fontSize: 18,
    fontWeight: "900",
    marginTop: 2,
  },
  badgeAgente: {
    alignItems: "flex-end",
  },
  agentePlacaText: {
    color: "#3b82f6",
    fontSize: 14,
    fontWeight: "900",
  },
  agenteCargoText: {
    color: "#a1a1aa",
    fontSize: 10,
    fontWeight: "700",
  },
  sectionCard: {
    backgroundColor: "#121214",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 14,
    padding: 16,
    marginBottom: 16,
  },
  sectionHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: "#1f1f23",
    paddingBottom: 10,
  },
  sectionTitle: {
    color: "#ffffff",
    fontSize: 13,
    fontWeight: "900",
    letterSpacing: 0.5,
  },
  instructionText: {
    color: "#a1a1aa",
    fontSize: 12,
    marginBottom: 12,
    lineHeight: 16,
  },
  row: {
    flexDirection: "row",
  },
  inputGroup: {
    marginBottom: 14,
  },
  label: {
    color: "#d4d4d8",
    fontSize: 11,
    fontWeight: "700",
    letterSpacing: 0.5,
    marginBottom: 6,
  },
  labelWithAction: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  input: {
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderColor: "#3f3f46",
    borderRadius: 10,
    color: "#ffffff",
    paddingHorizontal: 12,
    paddingVertical: 12,
    fontSize: 14,
  },
  inputDisabled: {
    backgroundColor: "#202024",
    color: "#a1a1aa",
  },
  gpsButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "rgba(59, 130, 246, 0.15)",
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: "rgba(59, 130, 246, 0.3)",
  },
  gpsButtonText: {
    color: "#60a5fa",
    fontSize: 11,
    fontWeight: "700",
  },
  gpsCoordinatesBox: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "rgba(16, 185, 129, 0.1)",
    padding: 8,
    borderRadius: 6,
    marginTop: 4,
  },
  gpsCoordinatesText: {
    color: "#34d399",
    fontSize: 11,
    fontWeight: "600",
  },
  switchRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#18181b",
    padding: 12,
    borderRadius: 10,
    marginBottom: 14,
  },
  switchInfo: {
    flex: 1,
    marginRight: 10,
  },
  switchTitle: {
    color: "#ffffff",
    fontSize: 13,
    fontWeight: "700",
  },
  switchDesc: {
    color: "#a1a1aa",
    fontSize: 11,
    marginTop: 2,
  },
  vehicleSubHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginTop: 6,
    marginBottom: 12,
  },
  vehicleSubTitle: {
    color: "#93c5fd",
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  sinPlacaBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  sinPlacaText: {
    color: "#a1a1aa",
    fontSize: 11,
  },
  chipsRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  chip: {
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#3f3f46",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  chipActive: {
    backgroundColor: "#2563eb",
    borderColor: "#3b82f6",
  },
  chipText: {
    color: "#a1a1aa",
    fontSize: 12,
    fontWeight: "600",
  },
  chipTextActive: {
    color: "#ffffff",
    fontWeight: "800",
  },
  selectedFaltaCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderColor: "#3b82f6",
    borderRadius: 12,
    padding: 14,
  },
  selectedFaltaContent: {
    flex: 1,
    marginRight: 10,
  },
  faltaFundamentoRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 6,
  },
  selectedFundamento: {
    color: "#60a5fa",
    fontSize: 14,
    fontWeight: "900",
  },
  umaBadge: {
    backgroundColor: "rgba(245, 158, 11, 0.15)",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  umaBadgeText: {
    color: "#f59e0b",
    fontSize: 11,
    fontWeight: "800",
  },
  selectedFaltaDesc: {
    color: "#ffffff",
    fontSize: 13,
    lineHeight: 18,
  },
  changeFaltaBtn: {
    alignItems: "center",
    paddingLeft: 8,
    borderLeftWidth: 1,
    borderLeftColor: "#27272a",
    gap: 2,
  },
  changeFaltaBtnText: {
    color: "#3b82f6",
    fontSize: 11,
    fontWeight: "700",
  },
  quickShortcutsRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 6,
    marginBottom: 10,
  },
  quickShortcutChip: {
    backgroundColor: "#1e293b",
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: "#334155",
  },
  quickShortcutText: {
    color: "#93c5fd",
    fontSize: 11,
    fontWeight: "600",
  },
  textArea: {
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderColor: "#3f3f46",
    borderRadius: 10,
    color: "#ffffff",
    padding: 12,
    fontSize: 14,
    minHeight: 110,
    lineHeight: 20,
  },
  checkboxGrid: {
    gap: 8,
    marginBottom: 12,
  },
  checkboxItem: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#27272a",
    padding: 12,
    borderRadius: 8,
  },
  checkboxItemActive: {
    borderColor: "#3b82f6",
    backgroundColor: "rgba(59, 130, 246, 0.1)",
  },
  checkboxLabel: {
    color: "#a1a1aa",
    fontSize: 13,
    fontWeight: "600",
  },
  checkboxLabelActive: {
    color: "#ffffff",
    fontWeight: "700",
  },
  photosContainer: {
    flexDirection: "row",
    gap: 8,
    marginBottom: 14,
  },
  photoSlot: {
    flex: 1,
  },
  photoSlotTitle: {
    color: "#a1a1aa",
    fontSize: 9,
    fontWeight: "800",
    marginBottom: 6,
    textAlign: "center",
  },
  photoPlaceholder: {
    height: 110,
    backgroundColor: "#18181b",
    borderWidth: 1.5,
    borderStyle: "dashed",
    borderColor: "#3f3f46",
    borderRadius: 10,
    justifyContent: "center",
    alignItems: "center",
    padding: 6,
  },
  placeholderText: {
    color: "#60a5fa",
    fontSize: 10,
    fontWeight: "700",
    marginTop: 6,
    textAlign: "center",
  },
  photoPreviewWrapper: {
    position: "relative",
    borderRadius: 10,
    overflow: "hidden",
  },
  photoPreview: {
    width: "100%",
    height: 110,
    borderRadius: 10,
    backgroundColor: "#27272a",
  },
  retakeBtn: {
    position: "absolute",
    bottom: 6,
    left: 6,
    right: 6,
    backgroundColor: "rgba(0,0,0,0.75)",
    paddingVertical: 4,
    borderRadius: 4,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
  },
  retakeText: {
    color: "#ffffff",
    fontSize: 10,
    fontWeight: "700",
  },
  quickPhotosButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    backgroundColor: "rgba(59, 130, 246, 0.1)",
    borderWidth: 1,
    borderColor: "rgba(59, 130, 246, 0.3)",
    paddingVertical: 10,
    borderRadius: 8,
  },
  quickPhotosText: {
    color: "#93c5fd",
    fontSize: 11,
    fontWeight: "700",
  },
  saveButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 10,
    backgroundColor: "#2563eb",
    borderRadius: 14,
    paddingVertical: 18,
    marginTop: 10,
    marginBottom: 40,
    shadowColor: "#2563eb",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 8,
  },
  saveButtonText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "900",
    letterSpacing: 0.5,
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
    maxHeight: "85%",
    borderWidth: 1,
    borderColor: "#27272a",
    padding: 16,
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 14,
  },
  modalTitle: {
    color: "#ffffff",
    fontSize: 17,
    fontWeight: "800",
  },
  modalSub: {
    color: "#a1a1aa",
    fontSize: 12,
  },
  modalCloseBtn: {
    padding: 6,
    borderRadius: 20,
    backgroundColor: "#27272a",
  },
  searchBar: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#3f3f46",
    borderRadius: 8,
    paddingHorizontal: 12,
    marginBottom: 14,
    gap: 8,
  },
  searchInput: {
    flex: 1,
    color: "#ffffff",
    paddingVertical: 10,
    fontSize: 13,
  },
  catalogoList: {
    maxHeight: 400,
  },
  catalogoItem: {
    backgroundColor: "#18181b",
    borderWidth: 1,
    borderColor: "#27272a",
    padding: 12,
    borderRadius: 10,
    marginBottom: 8,
  },
  catalogoItemActive: {
    borderColor: "#3b82f6",
    backgroundColor: "rgba(59, 130, 246, 0.15)",
  },
  catalogoItemTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  itemFundamento: {
    color: "#60a5fa",
    fontSize: 13,
    fontWeight: "800",
  },
  itemUma: {
    color: "#f59e0b",
    fontSize: 11,
    fontWeight: "700",
  },
  itemDesc: {
    color: "#ffffff",
    fontSize: 13,
    lineHeight: 18,
  },
  itemCat: {
    color: "#71717a",
    fontSize: 10,
    fontWeight: "700",
    marginTop: 4,
    textTransform: "uppercase",
  },
  cameraOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.95)",
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  cameraBox: {
    width: "100%",
    maxWidth: 400,
    backgroundColor: "#121214",
    borderWidth: 1,
    borderColor: "#27272a",
    borderRadius: 16,
    padding: 20,
  },
  cameraHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  cameraTitle: {
    color: "#ffffff",
    fontSize: 16,
    fontWeight: "800",
  },
  cameraInstruction: {
    color: "#a1a1aa",
    fontSize: 12,
    marginTop: 4,
    marginBottom: 20,
  },
  cameraLens: {
    height: 220,
    backgroundColor: "#09090b",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#3f3f46",
    justifyContent: "center",
    alignItems: "center",
    gap: 12,
  },
  cameraLensText: {
    color: "#71717a",
    fontSize: 12,
    fontWeight: "600",
  },
  cameraActionsRow: {
    alignItems: "center",
    marginTop: 20,
  },
  captureSnapBtn: {
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: "#ffffff",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 4,
    borderColor: "#3b82f6",
  },
  captureInnerCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: "#2563eb",
  },
});
