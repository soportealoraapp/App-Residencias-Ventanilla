# Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato
## Documentación Técnica Exhaustiva de la App Móvil para Agentes de Tránsito

**Gobierno Municipal de Uriangato, Guanajuato**  
**Dirección de Movilidad y Transporte**  
**Versión de la App Móvil:** 2.5.0  
**Stack Principal:** React Native 0.86 | Expo SDK 57 | TypeScript | Expo Router | Supabase JS SDK  
**Directorio en Repositorio:** `mobile/agentes-transito/`  
**Fecha de Actualización:** Octubre 2026  

---

## 1. Visión y Propósito Operativo de la App Móvil

La **App Móvil de Agentes de Tránsito** es una herramienta digital de campo diseñada para dotar a los oficiales de la Dirección de Movilidad y Transporte de Uriangato de una plataforma ágil, segura y legalmente robusta para el levantamiento de infracciones viales en la vía pública.

La aplicación resuelve de raíz las problemáticas históricas del levantamiento manual en papel:
* **Elimina errores de tabulador y cálculo:** Automatiza el cálculo de multas con base en la Unidad de Medida y Actualización (UMA) vigente de Uriangato.
* **Aporte de Evidencia Inobjetable:** Obliga y asiste en la captura de fotografías georreferenciadas con coordenadas GPS de alta precisión en el momento exacto de los hechos.
* **Operatividad Continua (Offline-First):** Funciona al 100% de sus capacidades aún en zonas sin cobertura celular ni señal de datos móviles, sincronizando automáticamente con el sistema central al recuperar conectividad.
* **Trazabilidad y Transparencia:** Genera folios únicos inalterables y registra la identidad del oficial emisor, hora, ubicación y garantías retenidas.

---

## 2. Arquitectura Tecnológica y Estructura del Proyecto

```mermaid
graph TD
    subgraph CAPA_INTERFAZ_USUARIO [Capa de Interfaz & Vistas - app/]
        IDX[index.tsx\nBienvenida y Modo]
        LOG[login.tsx\nInicio de Turno]
        REG[registro.tsx\nEnrolamiento de Oficial]
        DASH[dashboard.tsx\nCentro de Mando del Turno]
        NUEV[nueva-infraccion.tsx\nFormulario Integral de Infracción]
        BOL[boletas.tsx\nHistorial y Detalle de Infracciones]
    end

    subgraph CAPA_ESTADO_GLOBAL [Contextos y Lógica Global - src/contexts/]
        AUTH_CTX[AuthContext.tsx\nSesión, Placa, Credenciales]
        INFR_CTX[InfraccionesContext.tsx\nCatálogo, Cálculo UMA, Cola Offline]
        THEM_CTX[ThemeContext.tsx\nTema Claro / Oscuro Alto Contraste]
    end

    subgraph HARDWARE_Y_SERVICIOS_DISPOSITIVO [Integración con Hardware]
        CAM[expo-camera\nCaptura Fotográfica Asistida]
        GPS[expo-location\nGeolocalización en Tiempo Real]
        ASYNC[(AsyncStorage\nCola Local Offline)]
    end

    subgraph COMUNICACION_Y_NUBE [Servicios en la Nube - Supabase]
        SUPA_DB[(PostgreSQL Supabase\nTabla boletas_infracciones)]
        SUPA_STG[(Supabase Storage\nBucket: evidencias)]
        BACK_API[API REST CodeIgniter 4\n/api/agentes/registro]
    end

    IDX --> AUTH_CTX
    LOG --> AUTH_CTX
    REG --> BACK_API
    DASH --> INFR_CTX
    NUEV --> CAM
    NUEV --> GPS
    NUEV --> INFR_CTX
    INFR_CTX --> ASYNC
    INFR_CTX --> SUPA_DB
    INFR_CTX --> SUPA_STG
```

---

## 3. Estructura de Carpetas del Módulo Móvil

```
mobile/agentes-transito/
├── app/                          # Enrutamiento basado en archivos (Expo Router)
│   ├── _layout.tsx               # Layout raíz: proveedores de contexto (Theme, Auth, Infracciones)
│   ├── index.tsx                 # Pantalla inicial de bienvenida y verificación de estado
│   ├── login.tsx                 # Inicio de turno del oficial (placa y contraseña)
│   ├── registro.tsx              # Registro de nuevo oficial de tránsito con toggles de contraseña
│   ├── dashboard.tsx             # Panel de mando: turno activo, métricas y botón de nueva infracción
│   ├── nueva-infraccion.tsx      # Formulario de captura: cámara, GPS, catálogo y garantías
│   └── boletas.tsx               # Historial de infracciones con filtros y visor de evidencias
├── assets/                       # Logotipos institucionales, iconos y fuentes tipográficas
├── src/                          # Lógica de negocio modularizada
│   ├── api/                      # Clientes HTTP y llamadas de autenticación
│   │   ├── agents.ts             # Registro y validación contra API CodeIgniter 4
│   │   ├── auth.ts               # Autenticación de agentes
│   │   └── client.ts             # Cliente fetch normalizado con manejo seguro de URLs
│   ├── components/               # Componentes visuales reutilizables
│   │   └── ThemeToggle.tsx       # Botón de alternancia de tema Claro / Oscuro
│   ├── constants/                # Constantes globales y variables de entorno
│   │   ├── config.ts             # Resolución dinámica de API_BASE_URL (Web vs Nativo)
│   │   ├── defaultCatalog.ts     # Catálogo normativo base precargado para uso offline
│   │   └── theme.ts              # Paletas de color optimizadas para alto contraste
│   ├── contexts/                 # Contextos de React para estado global
│   │   ├── AuthContext.tsx       # Manejo de sesión y almacenamiento seguro de credenciales
│   │   ├── InfraccionesContext.tsx # Motor de cálculo, persistencia local y sincronización
│   │   └── ThemeContext.tsx      # Gestión de temas Claro/Oscuro
│   ├── services/                 # Integración con APIs externas y Supabase
│   │   └── supabase.ts           # Cliente Supabase JS, subida de fotos y persistencia
│   └── types/                    # Definiciones TypeScript de datos
│       └── infraccion.ts         # Tipos: BoletaInfraccion, InfraccionCatalogo, GarantiaRetenida
├── app.json                      # Configuración de Expo (nombre, bundleId, permisos)
├── package.json                  # Dependencias de npm y scripts de compilación
└── tsconfig.json                 # Configuración del compilador de TypeScript
```

---

## 4. Pantallas y Experiencia de Usuario (UI/UX) al Detalle

### 4.1. `app/index.tsx` (Bienvenida y Selección de Modo)
* **Propósito:** Punto de entrada accesible.
* **Componentes:**
  * Escudo oficial de Movilidad y Transporte Uriangato.
  * Selector de tema (Claro / Oscuro) en la barra superior.
  * Botones de acción rápida: "Iniciar Turno" y "Registrar Oficial".
  * Detección reactiva de sesión: si el agente ya tiene sesión activa guardada, navega directamente a `dashboard.tsx`.

### 4.2. `app/login.tsx` (Inicio de Turno)
* **Propósito:** Identificación del agente en vía pública.
* **Campos:**
  * Número de Placa / Credencial (formato automático en mayúsculas, ej. `AGT-102`).
  * Contraseña / PIN con botón para alternar visibilidad.
* **Validación:**
  * Validación local previa de longitud y caracteres.
  * Consulta contra `agentes_transito` en Supabase o API de backend.
  * En éxito, persiste el estado de sesión en `AsyncStorage` y redirige a `dashboard.tsx`.

### 4.3. `app/registro.tsx` (Enrolamiento de Nuevos Agentes)
* **Propósito:** Registro guiado de oficiales para su incorporación al padrón municipal.
* **Características de UX:**
  * Campos: Placa / Credencial, Nombre Completo, Contraseña y Confirmación de Contraseña.
  * Indicadores visuales y toggles independientes para ver u ocultar ambas contraseñas.
  * Mensajes de error claros e informativos (por ejemplo, placa duplicada, contraseñas no coincidentes, o error de conectividad).
  * En éxito, muestra banner de confirmación y redirige al login.

### 4.4. `app/dashboard.tsx` (Centro de Mando de Turno)
* **Propósito:** Pantalla principal durante la jornada de trabajo del oficial.
* **Módulos Visuales:**
  * **Tarjeta de Oficial:** Fotografía/Avatar, Nombre del Agente, Placa activa y Sector de patrullaje.
  * **Botón Primario Destacado:** `+ Levantar Nueva Infracción` con diseño amplio y accesible para operación rápida a una mano.
  * **Resumen de Turno:**
    * Infracciones levantadas en el turno actual.
    * Infracciones pendientes de sincronización (con badge de alerta amarilla/naranja si existen boletas en cola).
  * **Botón de Sincronización Manual:** Permite forzar la subida a Supabase al conectarse a una red WiFi o tener cobertura estable.
  * **Acceso a Historial:** Botón para navegar a la lista de boletas del turno.

### 4.5. `app/nueva-infraccion.tsx` (Formulario Integral de Infracción)
* **Propósito:** Registro estructurado de la falta cometida, conductor, vehículo, evidencias y garantías.
* **Flujo Paso a Paso:**
  1. **Folio y Geolocalización Automática:**
     * Al entrar, la app solicita geolocalización de alta precisión a `expo-location`.
     * Genera automáticamente el folio único bajo el estándar: `BOLETA-URI-{AÑO}-{PLACA}-{TIMESTAMP}`.
     * Muestra la latitud, longitud y dirección aproximada en tiempo real.
  2. **Datos del Infractor y Vehículo:**
     * Nombre del conductor o indicación de "Ausente / No presente".
     * Placa vehicular, Marca, Modelo, Color, Tipo (Particular / Servicio Público / Carga / Motocicleta) y Número de Serie (VIN).
  3. **Captura Fotográfica de Evidencias (Cámara Integrada):**
     * Toma 1: Fotografía legible de la placa vehicular.
     * Toma 2: Fotografía del contexto o hecho vial (señal omitida, cajón prohibido, choque, etc.).
     * Toma 3: Fotografía de la garantía retenida o vehículo montado en plataforma de grúa.
     * Previsualización instantánea con opción de recaptura.
  4. **Selección de Faltas del Reglamento (Catálogo Tipificado):**
     * Buscador en vivo de motivos de infracción (ej. "Estacionarse en lugar prohibido", "Falta de casco", "Conducir bajo influjo de alcohol", "Pasarse alto de semáforo").
     * Selección múltiple simultánea con cálculo automático del importe total:
       * Sumatoria de rango mínimo en UMAs y en pesos mexicanos ($ MXN).
       * Sumatoria de rango máximo en UMAs y en pesos mexicanos ($ MXN).
  5. **Garantías Retenidas:**
     * Selección de garantía asegurada: Placa vehicular, Licencia de conducir, Tarjeta de circulación o Vehículo remitido a pensión/corralón.
     * En caso de remisión con grúa, campos adicionales para Empresa de Grúa, Número de Grúa y Número de Inventario.
  6. **Observaciones del Agente:**
     * Campo de texto libre para asentar detalles relevantes del operativo.
  7. **Guardado y Transmisión:**
     * Guarda la boleta de manera inmediata en la cola local de `AsyncStorage` con estado `pendiente`.
     * Si hay internet activo, inicia inmediatamente la subida de fotos al bucket `evidencias` y la inserción del registro en PostgreSQL Supabase, actualizando el estado local a `sincronizada`.

### 4.6. `app/boletas.tsx` (Historial y Consulta de Infracciones)
* **Propósito:** Auditoría y verificación de boletas emitidas por el oficial.
* **Funcionalidades:**
  * Pestañas de filtrado: "Todas", "Sincronizadas" (verde), "Pendientes" (amarillo) y "Con Error" (rojo).
  * Tarjetas de resumen con folio, placas del vehículo, fecha/hora y monto estimado.
  * Modal detallado al pulsar una boleta: desglose de motivos sancionados, coordenadas del hecho, garantías y galería de fotografías tomadas.

---

## 5. Arquitectura Offline-First y Manejo de Conectividad

La app utiliza una estrategia rigurosa de persistencia local para asegurar que **ninguna infracción se pierda** por falta de cobertura en campo:

```mermaid
stateDiagram-v2
    [*] --> GuardadoLocal: Agente finaliza boleta
    GuardadoLocal --> ColaPendientes: Boleta almacenada en AsyncStorage (@infracciones_pendientes_v1)
    ColaPendientes --> EvaluandoRed: Inicia intento de transmisión
    EvaluandoRed --> SubiendoFotos: Hay conexión estable (WiFi/4G)
    EvaluandoRed --> ColaPendientes: Sin conexión (permanece en cola)
    SubiendoFotos --> InsertandoPostgreSQL: Fotos cargadas en bucket 'evidencias'
    InsertandoPostgreSQL --> Sincronizada: Registro exitoso en 'boletas_infracciones'
    InsertandoPostgreSQL --> ErrorReintentable: Falla de red o timeout
    ErrorReintentable --> ColaPendientes: Se reprograma para siguiente ciclo
    Sincronizada --> [*]
```

### Llaves de Almacenamiento en `AsyncStorage`:
* `@infracciones_pendientes_v1`: Arreglo de objetos `BoletaInfraccion` pendientes de sincronizar o con error.
* `@infracciones_historico_v1`: Registro histórico de las últimas 50 boletas sincronizadas para consulta rápida del oficial.
* `@catalogo_infracciones_cache_v1`: Copia local del catálogo de infracciones tipificadas para permitir su búsqueda sin internet.
* `@parametros_app_cache_v1`: Valor de la UMA vigente, versión de app y configuración de obligatoriedad de fotografías.
* `@agente_sesion_activa_v1`: Datos de la sesión activa del oficial (placa, nombre, fecha de inicio de turno).

---

## 6. Integración con Servicios en la Nube (Supabase)

### 6.1. Inserción Directa en PostgreSQL (`boletas_infracciones`)
El servicio `src/services/supabase.ts` utiliza `@supabase/supabase-js` para persistir la boleta:
```typescript
const { data, error } = await supabase
  .from('boletas_infracciones')
  .insert([
    {
      folio: boleta.folio,
      agente_placa: boleta.agentePlaca,
      agente_nombre: boleta.agenteNombre,
      conductor_nombre: boleta.conductorNombre,
      vehiculo_placas: boleta.vehiculoPlacas,
      vehiculo_marca: boleta.vehiculoMarca,
      vehiculo_submarca: boleta.vehiculoSubmarca,
      vehiculo_color: boleta.vehiculoColor,
      vehiculo_tipo: boleta.vehiculoTipo,
      vehiculo_serie: boleta.vehiculoSerie,
      latitud: boleta.latitud,
      longitud: boleta.longitud,
      lugar_hechos: boleta.lugarHechos,
      infracciones_articulos: JSON.stringify(boleta.infracciones),
      total_uma_min: boleta.totalUmaMin,
      total_uma_max: boleta.totalUmaMax,
      monto_pesos_min: boleta.montoPesosMin,
      monto_pesos_max: boleta.montoPesosMax,
      garantia_retenida: boleta.garantiaRetenida,
      grua_empresa: boleta.gruaEmpresa,
      grua_numero_inventario: boleta.gruaNumeroInventario,
      observaciones: boleta.observaciones,
      fotos_urls: boleta.fotosUrls,
      fecha_hora: boleta.fechaHora
    }
  ]);
```

### 6.2. Carga de Fotografías de Evidencia (`bucket: evidencias`)
Las fotos capturadas con `CameraView` se convierten en un buffer binario y se cargan al bucket `evidencias` bajo una estructura organizada por fecha y folio:
* Ruta en Storage: `evidencias/{AÑO}/{MES}/{FOLIO}_{TIPO_FOTO}_{TIMESTAMP}.jpg`
* Las URLs generadas se asocian en el arreglo `fotos_urls` de la boleta.

---

## 7. Compilación, Empaquetado y Despliegue Multiplataforma

### 7.1. Compilación Web SPA para Servidor Municipal (`/public/agentes/`)
La app móvil se compila como una Single Page Application (SPA) para estar disponible en los navegadores móviles o computadoras de base:
```bash
cd mobile/agentes-transito
npm install --legacy-peer-deps
npx expo export --platform web --output-dir ../../public/agentes --clear
```
Esta exportación genera el bundle estático en `public/agentes/`, servido por el script `scripts/vercel-install.sh` y enrutado por `vercel.json`.

### 7.2. Compilación de APK Nativo para Dispositivos Android (EAS Build)
Para generar el archivo ejecutable (`.apk`) para los teléfonos de los agentes de tránsito:
```bash
cd mobile/agentes-transito
# Iniciar sesión en EAS CLI
npx eas login

# Compilación de APK de desarrollo o producción
npx eas build -p android --profile preview
```
El archivo `.apk` resultante se instala directamente en los dispositivos de campo de la Dirección de Tránsito.

---

## 8. Verificación de Funcionamiento y Pruebas

1. **Ejecución del Entorno de Desarrollo (Metro Bundler):**
   ```bash
   cd mobile/agentes-transito
   npx expo start
   ```
   * Pulsa `w` en la consola para abrir en el navegador web local.
   * Pulsa `a` para abrir en emulador Android o escanea el código QR con Expo Go en un dispositivo móvil.
2. **Prueba de Modo Fuera de Línea:**
   * Abre la app en el dispositivo o navegador.
   * Activa el "Modo Avión" o desconecta la red en la pestaña Network de DevTools.
   * Levanta una boleta completa y pulsa guardar: se validará el aviso "Infracción guardada localmente (pendiente de sincronización)".
   * Reconecta la red y presiona "Sincronizar": la boleta pasará automáticamente al estado "Sincronizada".
