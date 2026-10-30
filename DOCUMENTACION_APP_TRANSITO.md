# App Móvil para Agentes de Tránsito
## Documentación Funcional y Técnica — `mobile/agentes-transito/`

**Gobierno Municipal de Uriangato, Guanajuato**
**Dirección de Movilidad y Transporte**
**Versión de la aplicación:** 1.0.0 (`package.json`, `app.json` y parámetro `app_version` del panel, los tres coincidentes)

> **Estado del documento:** actualizado al 29 de octubre de 2026
> **Alcance:** app de campo completa —pantallas, componentes, contextos, almacenamiento offline, contratos de sincronización con el backend municipal, catálogo de faltas, compilación web y APK, y verificación automatizada— más las dos superficies en que se publica: SPA en `/agentes` y binario Android
> **Fuente de verdad:** código del repositorio (`mobile/agentes-transito/`), contrastado con `app/Config/Routes.php` y `app/Controllers/Api/`
> **Documentos relacionados:** [README.md](./README.md) · [DOCUMENTACION_GENERAL.md](./DOCUMENTACION_GENERAL.md) · [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md) · [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) · [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md)

---

## Índice

1. [Visión, propósito y alcance por rol](#1-visión-propósito-y-alcance-por-rol)
   * 1.1. El problema que resuelve
   * 1.2. Alcance por rol
   * 1.3. Lo que la app no hace (y por qué)
2. [Stack verificado y configuración de entorno](#2-stack-verificado-y-configuración-de-entorno)
   * 2.1. Dependencias reales
   * 2.2. Variables `EXPO_PUBLIC_*`
   * 2.3. Resolución de la URL del backend
3. [Estructura de carpetas](#3-estructura-de-carpetas)
   * 3.1. Árbol real
   * 3.2. Capas y responsabilidad
   * 3.3. Código heredado sin uso
4. [Pantallas, componentes y contextos](#4-pantallas-componentes-y-contextos)
   * 4.1. `app/index.tsx` — Bienvenida
   * 4.2. `app/login.tsx` — Inicio de turno
   * 4.3. `app/dashboard.tsx` — Centro de mando
   * 4.4. `app/nueva-infraccion.tsx` — Captura de boleta
   * 4.5. `app/boletas.tsx` — Historial y consulta
   * 4.6. `app/perfil.tsx` — Identidad del oficial
   * 4.7. `app/+not-found.tsx` — Ruta inexistente
   * 4.8. Componentes reutilizables
   * 4.9. Contextos de estado global
5. [Estrategia offline-first](#5-estrategia-offline-first)
   * 5.1. Llaves reales de almacenamiento
   * 5.2. Máquina de estados de una boleta local
   * 5.3. Los cuatro disparadores de sincronización
   * 5.4. Política de reintentos y por qué existe `HEAD /`
6. [Sincronización con el backend municipal](#6-sincronización-con-el-backend-municipal)
   * 6.1. Contrato por endpoint
   * 6.2. Respaldo de lectura en Supabase y su implicación de seguridad
   * 6.3. Ciclo de vida del token de agente
   * 6.4. Del folio de campo al cobro
7. [Catálogo de faltas y cálculo de montos](#7-catálogo-de-faltas-y-cálculo-de-montos)
   * 7.1. Las 78 casillas y sus 15 categorías
   * 7.2. Cálculo en UMA y en pesos
   * 7.3. Paridad con el cálculo del servidor
8. [Compilación, empaquetado y publicación](#8-compilación-empaquetado-y-publicación)
   * 8.1. SPA web en `/agentes`
   * 8.2. APK Android con EAS
   * 8.3. Las tres trampas del haz web
9. [Verificación y solución de problemas](#9-verificación-y-solución-de-problemas)
   * 9.1. Verificador de flujo sin dispositivo
   * 9.2. Los 18 casos y qué protegen
   * 9.3. Matriz de sincronía con el backend
   * 9.4. Problemas conocidos y su diagnóstico

---

## 1. Visión, propósito y alcance por rol

### 1.1. El problema que resuelve

La app sustituye el levantamiento de boletas en papel por captura digital en la vía pública. No es una versión móvil del sistema de ventanilla: es la **herramienta de trabajo del oficial en el momento del hecho**, y su diseño responde a las tres condiciones reales de ese momento, que ninguna de ellas se parece a las de una oficina:

| Condición de campo | Cómo la resuelve la app |
|---|---|
| **Puede no haber datos móviles** | La boleta se guarda primero en el dispositivo y se envía después; nada se pierde por falta de cobertura (§5) |
| **El oficial opera de pie, con una mano y prisa** | Botón primario de altura mínima 54 px, objetivos táctiles de 46-72 px, navegación inferior de tres pestañas (§4.8) |
| **Lo que se registre tiene valor probatorio** | Folio único por agente y sello de tiempo, coordenadas GPS del lugar, tres fotografías obligatorias y garantías retenidas declaradas (§4.4) |

El cálculo de importes es la otra mitad del problema: el oficial no debe multiplicar UMA por fracciones a mano ni memorizar mínimos y máximos por falta. La app muestra el rango en UMA y su equivalente en pesos al instante; el monto **exigible** lo fija después el juez calificador en el sistema web (§6.4), y esa separación es deliberada.

### 1.2. Alcance por rol

La app sirve a dos de los seis actores del sistema —los dos que están en la calle— y a ningún otro:

| Rol (`agentes_transito.rol`) | Qué hace en la app | Qué **no** puede hacer |
|---|---|---|
| **Agente operativo** | Inicia turno con placa y contraseña; levanta boletas con faltas, infractor, vehículo, evidencias y garantías; consulta y sincroniza su propio historial | Ver boletas de otros agentes; calificar; cobrar; consultar montos exigibles; dar de alta agentes |
| **Jefe de turno** | Lo mismo que el agente operativo, con la misma interfaz: la app no distingue jerarquías en pantalla | Aprobar, supervisar ni modificar boletas ajenas desde el teléfono |

> **Por qué el jefe de turno no tiene pantallas propias.** La jerarquía del jefe se ejerce en el **panel web**, donde sí existe la bandeja completa, la auditoría y la configuración de la app. Duplicar esa supervisión en el teléfono habría exigido exponer boletas de terceros en un dispositivo que se presta, se pierde y se moja. La app es una herramienta de emisión, no un tablero de mando; el único mando que ejerce es sobre **sus propias** boletas.

El aislamiento entre agentes no depende de la interfaz, sino de dos candados que se refuerzan:

1. **En el dispositivo:** el contexto filtra la lista completa por pertenencia —identificador del agente o, en su defecto, placa— antes de exponerla a cualquier pantalla. Un teléfono compartido puede contener boletas de dos oficiales; cada uno ve solo las suyas.
2. **En el servidor:** el filtro `agenteToken` valida el portador y el controlador revalida que el folio pertenezca a la placa del token, incluso en la subida de evidencias. Si el folio es ajeno, responde `403` con el texto «El folio … pertenece a otro agente.» (§6.1).

### 1.3. Lo que la app no hace (y por qué)

Estas ausencias son decisiones, no pendientes, y conviene tenerlas escritas porque son las que más se preguntan:

* **No hay autorregistro de agentes.** Un agente tiene capacidad de sanción; su alta es un acto administrativo. Existió un `POST /api/agentes/registro` con su pantalla de enrolamiento y se retiró del código. Solo el administrador da de alta agentes desde `/admin/app-transito`.
* **No hay recuperación de contraseña desde el teléfono.** El restablecimiento lo hace el administrador; un PIN de campo no debe poder reiniciarse por correo en un dispositivo compartido.
* **No se cobra ni se califica.** Ni el agente ni el jefe fijan el monto exigible: eso ocurre en el panel del juez y en el módulo de cobros (§6.4).
* **No hay respaldo de boletas en Supabase.** Las boletas y las evidencias viajan **solo** al backend municipal, que es quien valida el token y la propiedad del folio. De Supabase se lee únicamente catálogo y parámetros, como respaldo de lectura (§6.2).
* **No se consultan boletas ajenas ni el estado del cobro.** El estado que la app muestra es el de **sincronización**, no el del expediente de cobro.

---

## 2. Stack verificado y configuración de entorno

### 2.1. Dependencias reales

Versiones tomadas de `package.json` y `app.json` al corte; no son las «recomendadas» por la documentación de Expo, son las que el proyecto tiene instaladas y con las que se compiló el haz publicado.

| Pieza | Versión | Para qué se usa |
|---|---|---|
| `expo` | `~57.0.30` | SDK base; `main` apunta a `expo-router/entry` |
| `react-native` | `0.86.3` | Motor de interfaz nativa |
| `react` / `react-dom` | `19.2.3` | Componentes y renderizado web |
| `expo-router` | `~57.0.28` | Enrutamiento por archivos sobre `app/` |
| `typescript` | `~6.0.3` | Tipado; verificado con `tsc --noEmit` |
| `@supabase/supabase-js` | `^2.117.2` | Respaldo de **lectura** de catálogo y parámetros |
| `@react-native-async-storage/async-storage` | `2.2.0` | Cola de boletas, caché de catálogo, perfil |
| `expo-secure-store` | `~57.0.4` | Token de portador cifrado en nativo |
| `@react-native-community/netinfo` | `12.0.1` | Detección de conectividad para el autosincronizado |
| `expo-camera` | `~57.0.6` | Captura de las tres fotografías de evidencia |
| `expo-location` | `~57.0.21` | Coordenadas del hecho y dirección aproximada |
| `expo-font`, `@expo/vector-icons` | `^57.0.4`, `^15.1.1` | Ionicons como única familia de iconos |
| `react-native-safe-area-context` | `~5.7.0` | Áreas seguras; la barra inferior respeta el *notch* |
| `react-native-screens`, `react-native-web` | `~4.26.0`, `^0.21.2` | Navegación nativa y destino web |
| `expo-constants` | `^57.0.20` | `hostUri` para resolver el backend en desarrollo |

`package.json` **no declara** scripts `test` ni `typecheck`: los dos comandos de verificación se invocan directamente (§9.1). Añadirlos es el cambio mínimo para integrarlos en un flujo de integración continua.

Permisos declarados en `app.json` para Android: `ACCESS_COARSE_LOCATION`, `ACCESS_FINE_LOCATION` y `CAMERA`. La orientación está fijada en `portrait`, el esquema de enlace es `agentes-transito` y `experiments.baseUrl` es `/agentes`, que es lo que permite publicar la SPA bajo esa ruta en el dominio municipal.

### 2.2. Variables `EXPO_PUBLIC_*`

Solo se nombran las variables, nunca sus valores: el archivo `.env` del cliente no se versiona.

| Variable | Obligatoria | Efecto |
|---|---|---|
| `EXPO_PUBLIC_API_BASE_URL` | Sí en producción | Origen del backend municipal. Sin ella se asume `http://localhost:8080`, que solo sirve en desarrollo |
| `EXPO_PUBLIC_APP_ENV` | No | `development` habilita los datos de demostración y el registro de la URL en consola; cualquier otro valor los desactiva |
| `EXPO_PUBLIC_SUPABASE_URL` | No | Habilita el respaldo de lectura; sin ella el cliente se construye con un marcador y el respaldo falla en silencio hacia la caché |
| `EXPO_PUBLIC_SUPABASE_ANON_KEY` | No | Llave anónima de Supabase |

> **Advertencia de seguridad sobre `EXPO_PUBLIC_*`.** Todo lo que lleve ese prefijo **se incrusta en el haz que el navegador descarga**: no es un secreto, es configuración pública. La llave `anon` de Supabase está diseñada para ello, pero su seguridad **depende por completo de que las políticas RLS de las tablas estén activas**; sin RLS, esa llave pública es una credencial de lectura abierta para cualquiera que abra las herramientas del navegador. Por eso la app lee exactamente dos tablas de Supabase —`catalogo_infracciones_app` y `parametros_app`— y nunca escribe: la escritura de boletas y evidencias pasa por el backend, que valida el token del agente. Ninguna llave de servicio (`service_role`), contraseña de base de datos ni secreto de firma puede aparecer jamás en este archivo.

### 2.3. Resolución de la URL del backend

`src/constants/config.ts` resuelve el origen en tres escenarios distintos, y cada rama existe por un fallo real de desarrollo:

| Escenario | Regla | Por qué |
|---|---|---|
| **Web servida desde el dominio municipal** | Se usa `window.location.origin` | La SPA y el backend comparten origen; no hay CORS ni URL que configurar |
| **Web de desarrollo en el puerto 8081** | Si el `.env` apunta a un host local, se conserva ese host; el puerto del backend no se toca | Evita depender de que la IP del `.env` coincida con la red del día |
| **Teléfono o emulador en desarrollo** | Si el `.env` apunta a un host local (privado o *loopback*), se sustituye el host por el de la PC que sirve Expo (`hostUri`) | El teléfono no puede resolver `localhost`: sin esta sustitución, cada cambio de red obligaría a editar el `.env` |

Se considera «host local» a `localhost`, `127.0.0.1` y los rangos privados `10.x`, `192.168.x` y `172.16-31.x`. En producción la variable apunta al dominio público y ninguna de estas ramas se activa.

---

## 3. Estructura de carpetas

### 3.1. Árbol real

```
mobile/agentes-transito/
├── app/                          Enrutamiento por archivos (Expo Router)
│   ├── _layout.tsx               Pila raíz + proveedores (tema → sesión → boletas) + fuentes
│   ├── index.tsx                 Bienvenida y redirección si ya hay sesión
│   ├── login.tsx                 Inicio de turno (placa y contraseña)
│   ├── dashboard.tsx             Centro de mando: turno, métricas y sincronización
│   ├── nueva-infraccion.tsx      Captura completa de la boleta (el archivo más extenso del cliente)
│   ├── boletas.tsx               Historial con filtros y visor de evidencias
│   ├── perfil.tsx                Identidad del oficial y cierre de sesión
│   └── +not-found.tsx            Ruta no declarada
├── src/
│   ├── api/
│   │   └── client.ts             fetch con token, tiempo límite y errores tipados (ApiError)
│   ├── components/
│   │   ├── AgentTabBar.tsx       Barra inferior: Inicio · Boletas · Perfil
│   │   ├── AgentScreenHeader.tsx Cabecera institucional con acciones a la derecha
│   │   ├── AgentInfraccionDetailModal.tsx  Detalle completo de una boleta del historial
│   │   ├── LogoutConfirmationModal.tsx     Confirmación de cierre de sesión
│   │   └── ThemeToggle.tsx       Alternancia claro / oscuro
│   ├── constants/
│   │   ├── config.ts             Resolución de API_BASE_URL y APP_ENV
│   │   ├── catalogoInfracciones.ts  Respaldo local del catálogo: 78 casillas en 15 categorías
│   │   └── theme.ts              Paletas de color para ambos temas
│   ├── contexts/
│   │   ├── AuthContext.tsx       Sesión, perfil, token y caducidad
│   │   ├── InfraccionesContext.tsx  Cola local, catálogo, UMA, conectividad y autosincronizado
│   │   └── ThemeContext.tsx      Tema activo y su persistencia
│   ├── navigation/               Navigador legado (sin uso; véase §3.3)
│   ├── screens/                  Pantallas legadas (sin uso; véase §3.3)
│   ├── services/
│   │   ├── storage.ts            Lectura y escritura de la cola de boletas y del sello de sincronización
│   │   ├── authProfileStorage.ts Perfil del agente y limpieza de llaves antiguas
│   │   ├── supabase.ts           Cliente Supabase, transformador a boleta y llamadas al backend
│   │   └── sync.ts               Orquestador de sincronización con política de sesión expirada
│   └── types/
│       └── infraccion.ts         Infraccion, FaltaCatalogo, evidencias, garantías y estados
├── tests/
│   └── auth-flow.test.cjs        Verificador ejecutable con Node puro (18 casos)
├── assets/                       Iconos, fondos adaptativos y favicon
├── app.json                      Configuración de Expo (permisos, esquema, baseUrl)
├── package.json                  Dependencias y scripts de Expo
└── tsconfig.json                 Configuración de TypeScript
```

### 3.2. Capas y responsabilidad

La separación no es estética: cada capa existe porque tiene una razón distinta para cambiar.

| Capa | Archivos | Razón para cambiar |
|---|---|---|
| **Pantallas** (`app/`) | 7 archivos | Cambios de flujo o de presentación |
| **Componentes** (`src/components/`) | 5 archivos | Cambios de lenguaje visual compartido entre pantallas |
| **Contextos** (`src/contexts/`) | 3 archivos | Cambios en las reglas de sesión, cola o tema |
| **Servicios** (`src/services/`) | 4 archivos | Cambios en el contrato con el backend o en el almacenamiento |
| **Contrato HTTP** (`src/api/`) | 1 archivo | Cambios en autenticación de transporte, tiempos límite o formato de error |
| **Constantes** (`src/constants/`) | 3 archivos | Cambios en el catálogo normativo, los colores o el origen del backend |
| **Tipos** (`src/types/`) | 1 archivo | Cambios en la forma de los datos de una boleta |

Un cambio de regla de negocio debe caer en una sola de estas filas. Cuando una modificación obliga a tocar tres capas a la vez, la señal habitual es que la regla se está escribiendo en la pantalla en lugar del contexto.

### 3.3. Código heredado sin uso

El repositorio conserva cuatro archivos de una arquitectura anterior —`App.tsx`, `index.ts`, `src/navigation/RootNavigator.tsx` y las dos pantallas de `src/screens/`— que **no participan en la ejecución**: el punto de entrada declarado en `package.json` es `expo-router/entry`, de modo que la app arranca en `app/_layout.tsx` y nunca monta el navegador legado. Se documentan aquí para que nadie los tome por la ruta viva ni los «arregle» creyendo que están en uso, y para que su eliminación futura sea una decisión consciente y no un descubrimiento. Su única diferencia funcional relevante: el legado no incluye el proveedor de infracciones ni el de tema.

---

## 4. Pantallas, componentes y contextos

Cada ficha sigue la misma plantilla para que puedan compararse entre sí y contra las fichas web de [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md).

### 4.1. `app/index.tsx` — Bienvenida

- **Ruta / archivo:** `/` de la SPA (`/agentes` en el dominio) · `app/index.tsx`
- **Roles y filtros:** público; sin sesión no redirige, con sesión **sí** (a `/dashboard`) y mientras se restaura la sesión muestra un indicador de carga
- **Propósito:** puerta de entrada de un oficial que abre la app o la SPA; no permite registrarse
- **Qué muestra:** etiqueta «SISTEMA DE AGENTES» con punto verde de estado, botón de tema, escudo institucional, «MUNICIPIO DE URIANGATO», «Movilidad y Transporte», la insignia «SISTEMA DE BOLETAS DE INFRACCIÓN», la instrucción «Ingrese con la placa y contraseña que le asignó el administrador» y el aviso de que las boletas se almacenan localmente y se sincronizan al recuperar conexión
- **Campos y controles:** ninguno de captura; dos controles: alternancia de tema y botón «Iniciar sesión»
- **Acciones:** «Iniciar sesión» → `router.push('/login')`; alternar tema → `ThemeContext`
- **Estados y casos límite:** sesión en restauración → indicador de carga y ninguna acción posible; sesión válida → redirección automática que **impide ver la bienvenida** (y evita el bucle de volver atrás hacia el acceso); error de sesión guardada → recuadro rojo con el mensaje antes de permitir entrar
- **Mensajes al usuario:** «Ingrese con la placa y contraseña que le asignó el administrador.» · «Las boletas se almacenan localmente y se sincronizan al contar con conexión.» · recuadro de `sessionError` con el texto que produjo la restauración, por ejemplo «Tu sesión expiró. Inicia sesión nuevamente; las boletas pendientes se conservan en el dispositivo.»
- **Datos que consume y produce:** lee `isLoading`, `user` y `sessionError` de la sesión; no escribe nada
- **Enlaces salientes:** `/login`; redirección de salida a `/dashboard`

### 4.2. `app/login.tsx` — Inicio de turno

- **Ruta / archivo:** `/login` · `app/login.tsx`
- **Roles y filtros:** público; es la única pantalla que emite credencial de acceso
- **Propósito:** identificar al oficial contra la tabla `agentes_transito` y abrir turno con un token de portador
- **Qué muestra:** cabecera con «SISTEMA EN LÍNEA», el escudo institucional, «MUNICIPIO DE URIANGATO», «Movilidad y Transporte», el título «Acceso de Agentes», dos campos y el botón «INGRESAR AL SISTEMA», más la nota «Acceso con conexión requerida»
- **Campos y controles:**

| Campo | Tipo | Obligatorio | Regla | Origen del dato |
|---|---|---|---|---|
| Número de placa / credencial | texto | Sí | Se normaliza a mayúsculas y se recorta; vacío se rechaza antes de salir del dispositivo | Alta del agente en el panel |
| Contraseña | texto enmascarado con botón de visibilidad | Sí | No vacía | La asigna el administrador (hash BCRYPT en el servidor) |

- **Acciones:** «INGRESAR AL SISTEMA» → `signIn(placa, password)` → `POST /api/agentes/login` → guarda token y perfil → navega a `/dashboard`
- **Estados y casos límite:** campo vacío → mensaje local, sin petición de red; credenciales inválidas → `401` traducido; agente dado de baja → `401` (el servidor no distingue «no existe» de «inactivo»); servicio inalcanzable → mensaje de conexión, nunca una sesión fabricada; credenciales aceptadas pero almacenamiento lleno → se revierte el token y se avisa que no se pudo guardar la sesión
- **Mensajes al usuario:** «Debe ingresar su número de placa/credencial y contraseña.» · «Credenciales incorrectas o agente inactivo.» · «Ingrese una placa y contraseña válidas.» · «El servicio de autenticación no está disponible. Inténtelo más tarde.» · «No se pudo conectar con el servidor. Revise su conexión e inténtelo de nuevo.» · «Credenciales aceptadas, pero no se pudo guardar la sesión en este dispositivo.»
- **Datos que consume y produce:** consume `POST /api/agentes/login`; produce el token (almacenamiento cifrado en nativo), el perfil `@agente_perfil_autenticado_v2` y la sesión en memoria
- **Enlaces salientes:** `/dashboard` en éxito; `/` si se decide no iniciar turno

### 4.3. `app/dashboard.tsx` — Centro de mando del turno

- **Ruta / archivo:** `/dashboard` · `app/dashboard.tsx`
- **Roles y filtros:** requiere sesión; sin perfil válido o con token ausente redirige a `/`
- **Propósito:** responder de un vistazo «¿qué llevo hoy y qué me falta enviar?» y ofrecer las dos acciones del turno: levantar una boleta y sincronizar
- **Qué muestra:** cabecera institucional con el botón de cerrar sesión; tarjeta del oficial (nombre, placa, sector); línea «UMA vigente» con el valor en pesos; cuatro indicadores — «Emitidas hoy», «Por sincronizar», «Sincronizadas» y «Boletas disponibles en este dispositivo» —; el botón primario «Registrar infracción»; el botón «Sincronizar» con su estado; y la lista «Actividad reciente» con las últimas boletas y su etiqueta de estado
- **Campos y controles:** no hay captura; el botón de sincronización se deshabilita mientras hay una sincronización en curso
- **Acciones:** «Registrar infracción» → `/nueva-infraccion`; «Sincronizar» → `sincronizar()` → sube evidencias y boletas pendientes → recarga los indicadores; cerrar sesión → confirmación → `/`
- **Estados y casos límite:** sin boletas → indicadores en cero y lista vacía, sin pantalla en blanco; con pendientes → insignia de alerta en «Por sincronizar»; sin conexión → aviso explícito de que las boletas permanecen guardadas; sincronización con fallos parciales → mensaje de «Sincronización incompleta» y no de éxito; token rechazado → la sesión se cierra conservando las boletas; doble pulsación de sincronizar → la segunda llamada devuelve la misma promesa en curso y **no** reenvía nada
- **Mensajes al usuario:** «Sincronización al día» · «Sincronización incompleta» · «Se sincronizaron N boleta(s) exitosamente con el servidor central de Uriangato.» · «No se sincronizaron boletas nuevas. Hay N boleta(s) con error de sincronización y sin respuesta del servidor.» · «No hay boletas pendientes de sincronizar. El registro local disponible está al día.» · «Dispositivo sin conexión a internet. Las boletas permanecen guardadas localmente y se enviarán al reconectar.»
- **Datos que consume y produce:** consume la cola local, la UMA y el estado de red; produce el envío de `POST /api/infracciones/evidencias` y `POST /api/infracciones/sincronizar`, y la marca `@agentes_transito_ultima_sync_v1`
- **Enlaces salientes:** `/nueva-infraccion`, `/boletas` (pestaña), `/perfil` (pestaña)

### 4.4. `app/nueva-infraccion.tsx` — Captura de boleta

- **Ruta / archivo:** `/nueva-infraccion` · `app/nueva-infraccion.tsx`
- **Roles y filtros:** requiere sesión; es la pantalla con más controles del cliente y la única que escribe en la cola local
- **Propósito:** registrar en un solo formulario la falta o faltas, los datos del infractor y del vehículo, las evidencias fotográficas, las garantías retenidas y la narración de los hechos, con folio y geolocalización automáticos
- **Qué muestra:** banner con el folio oficial generado; bloque de generales con fecha, hora, lugar, referencia y coordenadas GPS; bloque de infractor con la casilla de conductor ausente; bloque de vehículo con la casilla de «sin placas»; el catálogo de faltas agrupado por categoría, con el rango en UMA y su equivalente en pesos por casilla; el resumen del importe total en UMA y en pesos; el área de cámara con las tres tomas; el bloque de garantías con los campos de grúa cuando aplica; el campo de hechos; y el botón de guardar
- **Campos y controles:**

| Bloque | Campo | Obligatorio | Regla |
|---|---|---|---|
| Generales | Fecha y hora | Sí | Se proponen del reloj del dispositivo y son editables |
| Generales | Lugar | Sí | Texto; se precarga con la dirección aproximada obtenida del GPS |
| Generales | Referencia | No | Punto de referencia visible |
| Generales | Coordenadas | No | Se solicitan al entrar; si el permiso se niega, la boleta puede guardarse sin ellas |
| Infractor | Conductor ausente | Sí (casilla) | Si se marca, los datos personales dejan de ser exigibles |
| Infractor | Nombre, domicilio, licencia | Condicional | Obligatorios cuando **no** se marcó conductor ausente |
| Vehículo | Placas / sin placas | Sí | La casilla «sin placas» sustituye al número |
| Vehículo | Marca, línea, color, tipo | No | Tipo entre particular, transporte público, carga, motocicleta u otro |
| Faltas | Selección múltiple | Sí, al menos una | Del catálogo vigente; suma de mínimos y de máximos en UMA |
| Evidencias | Foto de placa, de contexto y de documento | Según `fotos_obligatorias` | Se capturan con la cámara integrada y admiten recaptura |
| Garantías | Licencia, placa, tarjeta de circulación, vehículo | No | Si se retiene el vehículo, se piden empresa, número de grúa e inventario |
| Hechos | Narración | Sí | Texto libre |

- **Acciones:** seleccionar casillas del catálogo → recalcula el total; capturar o recapturar cada foto; solicitar GPS; «Guardar» → construye la boleta con folio único → la escribe en la cola con estado `pendiente` → si hay conexión, dispara la sincronización al instante
- **Estados y casos límite:** permiso de ubicación negado → la captura continúa sin coordenadas; permiso de cámara negado → no se pueden completar las tomas obligatorias y el guardado se bloquea con aviso; folio repetido → imposible por diseño, el folio combina placa, marca de tiempo y un contador de secuencia en base 36; hoja de cálculo sin conexión → el guardado **siempre** procede y la boleta queda en cola; navegación atrás con datos capturados → se intercepta el botón físico de Android para no perder la captura por accidente; doble pulsación de guardar → se bloquea el reenvío mientras la escritura local está en curso
- **Mensajes al usuario:** confirmación con el folio y el total estimado; aviso de guardado local pendiente de sincronización cuando no hay red; mensajes de permiso denegado para cámara y ubicación; error explícito si el almacenamiento local falla («No se pudo guardar la infracción en el almacenamiento local.»)
- **Datos que consume y produce:** consume el catálogo, la UMA vigente, el perfil del agente y los servicios de cámara y ubicación; produce un objeto `Infraccion` en `@agentes_transito_infracciones_v1` y, si hay red, el envío de sus tres evidencias y de la boleta
- **Enlaces salientes:** `/dashboard` al terminar; `/boletas` para revisar lo capturado

### 4.5. `app/boletas.tsx` — Historial y consulta

- **Ruta / archivo:** `/boletas` · `app/boletas.tsx`
- **Roles y filtros:** requiere sesión; muestra **solo** las boletas del agente en turno
- **Propósito:** auditar lo emitido en el turno, encontrar una boleta concreta y verificar si ya llegó al servidor
- **Qué muestra:** cabecera con tres indicadores — «Total registradas», «Por sincronizar» y las sincronizadas —, un panel «Filtros» con «PERIODO» (desde/hasta), «ESTADO DE SINCRONIZACIÓN», «TIPO DE VEHÍCULO» y el botón «Limpiar», y la lista «Resultados» de tarjetas con folio, placas, fecha y hora, monto estimado y etiqueta de estado
- **Campos y controles:** fechas «Desde» y «Hasta»; selector de estado (pendiente, sincronizada, con error); selector de tipo de vehículo; el botón «Limpiar» reinicia los tres
- **Acciones:** pulsar una tarjeta → abre el detalle completo (faltas, coordenadas, garantías y galería de evidencias); «Limpiar» → restablece filtros; eliminar una boleta propia desde el detalle → confirmación y borrado local
- **Estados y casos límite:** sin boletas → lista vacía con indicadores en cero; filtros que no arrojan resultados → «Resultados» vacío sin confundirse con «no hay boletas»; boleta de otro agente en el mismo teléfono → invisible en la lista y el borrado se rechaza con «Solo puedes eliminar boletas emitidas por ti.»; evidencia que solo existe como URI local → la galería la muestra desde el dispositivo y no desde una URL remota
- **Mensajes al usuario:** «Solo puedes eliminar boletas emitidas por ti.»; etiquetas de estado «Pendiente de sincronización», «Sincronizada» y «Error de sincronización»
- **Datos que consume y produce:** consume la cola local filtrada por pertenencia y el sello de la última sincronización; produce el borrado local de una boleta propia
- **Enlaces salientes:** `/dashboard`, `/perfil` y el modal de detalle

### 4.6. `app/perfil.tsx` — Identidad del oficial

- **Ruta / archivo:** `/perfil` · `app/perfil.tsx`
- **Roles y filtros:** requiere sesión; sin perfil redirige a `/`
- **Propósito:** mostrar con qué identidad está trabajando el dispositivo y ofrecer el cierre de turno
- **Qué muestra:** cabecera institucional con el botón de tema; título «Mi perfil» y subtítulo «Información de tu cuenta operativa»; avatar con inicial institucional, nombre y rol; la sección «Datos operativos» con placa, rol y sector —los renglones vacíos se omiten en lugar de mostrarse en blanco—; y el botón rojo «Cerrar sesión» con la leyenda «Salir de tu cuenta en este dispositivo»
- **Campos y controles:** ningún campo editable; el perfil se administra desde el panel, no desde el teléfono
- **Acciones:** «Cerrar sesión» → modal de confirmación → limpieza de perfil y token → `/`
- **Estados y casos límite:** perfil restaurado sin token → no se muestra el perfil y se pide iniciar sesión (la regla vive en el servicio de almacenamiento, no en la pantalla); error al limpiar el almacenamiento → se cierra la sesión en memoria y se informa de lo que no se pudo borrar, en lugar de dejar una credencial viva; fila vacía (por ejemplo, sector no asignado) → se omite
- **Mensajes al usuario:** «Tu sesión expiró. Inicia sesión nuevamente; las boletas pendientes se conservan en el dispositivo.» · «No se pudo eliminar el perfil local del dispositivo.» (y sus variantes de token y perfil antiguo)
- **Datos que consume y produce:** consume el perfil y el token; produce la eliminación de `@agente_perfil_autenticado_v2`, de la llave heredada `@agente_sesion_activa_v1` y del token
- **Enlaces salientes:** `/` tras cerrar sesión; `/dashboard` y `/boletas` por la barra inferior

> **El cierre de sesión no borra boletas.** Es la decisión más importante de esta pantalla: un oficial puede cerrar turno con boletas pendientes, y esas boletas deben seguir en el dispositivo para el siguiente turno o para el siguiente oficial. Solo se destruye la credencial y el perfil; la cola permanece.

### 4.7. `app/+not-found.tsx` — Ruta inexistente

- **Ruta / archivo:** cualquier ruta no declarada por el enrutador de archivos · `app/+not-found.tsx`
- **Roles y filtros:** público
- **Propósito:** cerrar con dignidad una URL mal escrita, un enlace viejo o un `F5` sobre una ruta de la SPA que no existe
- **Qué muestra:** mensaje de ruta no encontrada con el recurso institucional y el botón «Ir al inicio»
- **Campos y controles:** ninguno
- **Acciones:** «Ir al inicio» → `/`
- **Estados y casos límite:** es la pantalla que **no** existe como `+unmatched.tsx`; la convención correcta del enrutador es `+not-found.tsx`, y la equivocada produce una pantalla en blanco en lugar de este cierre
- **Mensajes al usuario:** título y explicación de ruta inexistente; ninguna traza técnica
- **Datos que consume y produce:** ninguno
- **Enlaces salientes:** `/`

### 4.8. Componentes reutilizables

| Componente | Qué resuelve | Detalle que importa |
|---|---|---|
| **`AgentTabBar`** | Navegación inferior de tres pestañas: Inicio, Boletas, Perfil | Usa `router.replace` y no `push`, para que el historial no crezca con cada cambio de pestaña; respeta el área segura inferior; altura mínima de 46 px por objetivo y estado de selección accesible (`accessibilityRole="tab"`) |
| **`AgentScreenHeader`** | Cabecera institucional «TRÁNSITO · URIANGATO / Sistema operativo para agentes» con un espacio de acciones a la derecha | Es el único lugar donde vive la identidad visual de cabecera; las cinco pantallas internas la consumen en lugar de repetirla |
| **`AgentInfraccionDetailModal`** | Detalle completo de una boleta del historial: faltas, coordenadas, garantías y galería | Es el visor de evidencias; distingue URI local de URL remota para no romper la imagen cuando aún no se ha subido |
| **`LogoutConfirmationModal`** | Confirmar el cierre de sesión | Evita el cierre accidental con un toque; el cierre efectivo ocurre solo al confirmar |
| **`ThemeToggle`** | Alternar tema claro y oscuro | Tiene variante compacta para la cabecera; el tema elegido se conserva entre sesiones (§4.9) |

**Regla de composición.** Ninguna pantalla define su propia cabecera ni su propia barra inferior: todas usan los dos componentes compartidos. Es lo que permite que un cambio de altura de la barra o de texto de la cabecera se aplique a las cinco pantallas internas de una sola vez, y lo que evita que la app se degrade en pantallas distintas según quién la editó por última vez.

### 4.9. Contextos de estado global

Los tres proveedores se anidan en un orden que **no es arbitrario**: tema → sesión → boletas. Las boletas dependen de la sesión porque la pertenencia de cada boleta se resuelve contra el agente en turno; la sesión depende del tema solo por presentación.

| Contexto | Estado que expone | Regla que implementa |
|---|---|---|
| **`ThemeContext`** | `colors`, `isDark`, alternancia y persistencia | Paletas de alto contraste para uso a la intemperie; el tema sobrevive al cierre de la app |
| **`AuthContext`** | `user`, `isLoading`, `isAuthenticated`, `sessionError`, `signIn`, `signOut`, `logout`, `expireSession` | Restaura el perfil **solo si existe token**; sin token borra el perfil y pide login; normaliza el rol (`operativo` se presenta como «Agente Vial Operativo») y aplica un sector por omisión cuando el servidor no lo trae; distingue el cierre voluntario (`signOut`) del cierre por token rechazado (`expireSession`), que además fija el mensaje que verá el oficial |
| **`InfraccionesContext`** | `infracciones`, `pendientes`, `sincronizadas`, `totalPendientes`, `totalSincronizadas`, `isLoading`, `isSyncing`, `ultimaSincronizacion`, `valorUma`, `catalogo`, `enLinea`, `guardarInfraccion`, `sincronizar`, `eliminar`, `recargar` | Es el motor de la app: filtra por pertenencia, mantiene la cola, carga catálogo y UMA, escucha la red y dispara el autosincronizado (§5.3) |

**Dos detalles del contexto de boletas que evitan defectos difíciles de reproducir:**

1. **Una sola sincronización a la vez.** Si ya hay una en curso, la siguiente llamada devuelve la misma promesa. Sin esta guarda, dos disparadores simultáneos —la recuperación de red y el retorno a primer plano— enviarían la misma boleta dos veces.
2. **Una sola fuente de verdad para la cola.** El contexto guarda **todas** las boletas del dispositivo y expone solo las del agente. Así, un teléfono compartido no pierde el trabajo del oficial anterior al cambiar de sesión: las boletas siguen ahí, invisibles para el nuevo usuario pero intactas.

**Datos de demostración, nunca en producción.** El contexto contiene dos boletas de ejemplo con folios `BOLETA-URI-2026-AGT204-DEMO1` y `DEMO2`, y solo se cargan cuando `EXPO_PUBLIC_APP_ENV` vale exactamente `development` **y** la cola está vacía. En cualquier otro entorno la app arranca en cero. Es una salvaguarda explícita: una demostración sembrada en producción parecería una boleta real en el historial de un agente.

---

## 5. Estrategia offline-first

El principio es simple y no admite excepciones: **la boleta se escribe primero en el dispositivo y se envía después**. La red es un lujo, no un requisito. Ninguna acción del oficial depende de que haya cobertura, salvo el inicio de turno —que requiere validar credenciales— y la sincronización.

### 5.1. Llaves reales de almacenamiento

| Llave | Contenido | Quién escribe | Vigencia |
|---|---|---|---|
| `@agentes_transito_infracciones_v1` | Arreglo completo de boletas del dispositivo, de todos los agentes que lo hayan usado | `services/storage.ts` | Permanente hasta borrado explícito |
| `@agentes_transito_ultima_sync_v1` | Marca de tiempo ISO de la última sincronización con éxito | `services/storage.ts` | Se sobrescribe |
| `@catalogo_infracciones_cache_v1` | Copia del catálogo vigente con fundamentos, categorías y rangos en UMA | `services/supabase.ts` | Se refresca en cada carga con red |
| `@parametros_app_cache_v1` | Mapa de parámetros: UMA vigente, fotos obligatorias, versión de app, intervalo de sincronización | `services/supabase.ts` | Se refresca en cada carga con red |
| `@agente_perfil_autenticado_v2` | Perfil del oficial en turno: identificador, placa, nombre, rol y sector | `services/authProfileStorage.ts` | Hasta cerrar sesión |
| `agent_api_token_v1` | Token de portador | Almacenamiento cifrado en nativo; `sessionStorage` en web | Hasta caducar o cerrar sesión |
| `auth_token` | **Llave heredada**, de un esquema anterior | Solo se **borra**, nunca se lee para autenticar | Migración pendiente |
| `@agente_sesion_activa_v1` | **Llave heredada** que llegó a contener identidades simuladas | Solo se **borra** en cada arranque | Migración pendiente |

> **El token no vive donde vive el perfil.** Es la decisión de seguridad central del cliente: en un dispositivo nativo, el token se guarda cifrado con `expo-secure-store`, mientras que el perfil —que no es una credencial— vive en `AsyncStorage`. En la versión web no existe almacenamiento seguro de esa clase, así que el token se guarda en `sessionStorage` y **se pierde al cerrar la pestaña**; es una degradación consciente y preferible a dejar un portador permanente en un navegador compartido.

### 5.2. Máquina de estados de una boleta local

| Estado local | Significado | Cómo se sale |
|---|---|---|
| `pendiente` | Guardada en el dispositivo, aún no confirmada por el servidor | Sincronización con éxito → `sincronizada`; error de red → permanece |
| `sincronizada` | El servidor aceptó la boleta; sus evidencias ya se subieron | Estado final en el cliente |
| `error_sincronizacion` | El servidor respondió pero rechazó el contenido, o falló repetidamente | Se cuenta junto con las pendientes en el tablero y se reintenta |

El estado `error_sincronizacion` **no** se produce por falta de red: para la app, la red ausente es «aún no», no «mal». Un fallo de conexión deja la boleta en `pendiente` y el reintento periódico se encarga; un rechazo del servidor sí es un error que alguien debe mirar.

### 5.3. Los cuatro disparadores de sincronización

El autosincronizado silencioso no corre a ciegas: exige **sesión activa**, **conexión conocida como disponible** y **al menos una boleta pendiente**. Bajo esas tres condiciones, se dispara por cuatro caminos distintos:

| # | Disparador | Por qué existe |
|---|---|---|
| 1 | **Recuperación de conectividad** (`NetInfo`) | Es el caso normal de campo: el oficial sale de la zona sin señal y vuelve. Se sincroniza al recuperar, no al perder |
| 2 | **Retorno de la app a primer plano** | El teléfono pasa horas en el bolsillo; al volver a abrirla es cuando conviene enviar |
| 3 | **Reintento periódico** con intervalo de 60 s mientras haya pendientes | Cubre el caso del servidor caído: la red estaba bien y el envío falló, y no habrá cambio de conectividad que lo reintente |
| 4 | **Guardado de una boleta nueva** | Si hay cobertura, el envío ocurre en el mismo instante de la captura |

La conectividad se considera disponible cuando hay interfaz de red **y** `isInternetReachable` no es explícitamente falso. Cuando aún no se conoce el estado (`enLinea` es `null`, antes del primer evento), la app **no** se bloquea: intenta y deja que el error decida.

### 5.4. Política de reintentos y por qué existe `HEAD /`

El reintento es indefinido y con espaciado fijo de un minuto, sin retroceso exponencial, y es una decisión razonada: la cola es pequeña (decenas de boletas por turno, no miles de eventos), el costo de un intento fallido es bajo y el costo de esperar demasiado es que el oficial vea su trabajo sin llegar al servidor. Un contador de intentos que crece hasta el silencio sería peor para el usuario que un reintento constante.

El **`401` es la excepción a la regla**: no se reintenta. Un token ausente, vencido o de un agente dado de baja significa que no tiene caso seguir enviando. La app detiene el recorrido, conserva **todas** las boletas pendientes —incluidas las que iban después de la fallida— y pide volver a iniciar sesión. La lógica es explícita en el orquestador: el `401` corta el bucle y marca `requiereLogin`, mientras que cualquier otro error solo incrementa el contador de fallidos.

> **Por qué el backend tiene una ruta `HEAD /`.** `NetInfo` en web no basta para saber si el servidor **responde**: hay redes que dan una interfaz activa y no llegan a ningún sitio. La app sondea el origen y solo considera que hay internet si contesta correctamente. CodeIgniter no resuelve `HEAD` contra rutas `GET`, así que ese sondeo caía en `404`, la app se declaraba sin conexión y el autosincronizado no se disparaba **nunca** en la SPA publicada. La ruta `HEAD /` existe en `app/Config/Routes.php` exclusivamente para eso: contesta vacío y con `200`, sin abrir sesión y sin pasar por un controlador. Es una ruta de diagnóstico y debe seguir existiendo mientras el cliente la use.

---

## 6. Sincronización con el backend municipal

La app consume **cinco endpoints** y ningún otro. La lista es corta a propósito: cada uno existe porque una pantalla lo necesita, y cada uno tiene su contraparte exacta en `app/Config/Routes.php`.

### 6.1. Contrato por endpoint

**1. `POST /api/agentes/login`** — inicio de turno

| Aspecto | Detalle |
|---|---|
| Cuerpo | `placa` (o `usuario`) y `password`; se envía JSON y el servidor acepta también formulario |
| Éxito | `200` con `success`, `token`, `token_type` «Bearer», `expira_en`, `agente` (identificador, placa, nombre, rol, sector, activo) y `user` con `id`, `nombre` y `rol` |
| Errores | `400` «La solicitud no contiene datos válidos.» · `422` «Ingresa tu placa/usuario y contraseña.» · `401` «Credenciales inválidas o agente no activo.» o «Credenciales inválidas.» |
| Cliente | Sin autenticación previa; tiempo límite de 10 s; una respuesta sin token se considera sesión inválida aunque el cuerpo sea `200` |

**2. `GET /api/checklist-infracciones`** — catálogo de faltas

| Aspecto | Detalle |
|---|---|
| Respuesta | `success`, `valor_uma_vigente`, `total_items` y `categorias`, un mapa de categoría a arreglo de casillas con `fundamento_legal`, `descripcion_oficial`, `monto_min_uma`, `monto_max_uma` y sus equivalentes en pesos |
| Fuente declarada | `catalogo_infracciones_app` cuando el administrador lo ha poblado; `checklist_oficial` como respaldo, y en ese caso añade `total_mapeados` y `total_sin_mapeo` |
| Cliente | Sin autenticación; normaliza la respuesta a la forma `FaltaCatalogo` y la guarda en caché. Si el campo de rango viene nulo, aplica el mínimo de 2 y el máximo de 5 UMA como salvaguarda |

**3. `GET /api/parametros`** — configuración vigente

| Aspecto | Detalle |
|---|---|
| Respuesta | `success` y `parametros`: `valor_uma_vigente`, `fotos_obligatorias`, `app_version`, `sync_intervalo_segundos`, `municipio` y `modo_estricto_fotos` |
| Cliente | Sin autenticación; guarda el mapa en caché. Los valores de fábrica del cliente son UMA 117.31, tres fotos, versión 1.0.0, 300 s, «Uriangato, Guanajuato» y modo estricto activo |

**4. `POST /api/infracciones/sincronizar`** (alias `POST /api/boletas/sincronizar`) — envío de boletas

| Aspecto | Detalle |
|---|---|
| Filtro | **`agenteToken`**: sin portador válido no se llega al controlador |
| Cuerpo | Folio, placa y nombre del agente, fecha y hora, lugar, coordenadas, datos del infractor y del vehículo, faltas en JSON más el resumen de la falta principal, montos mínimos y máximos sumados en UMA, hechos, garantías retenidas, inventario de grúa y las tres URL de evidencia |
| Éxito | `200` con `success`, `sincronizadas`, `errores` y un mensaje del tipo «Se sincronizaron N boleta(s) correctamente.» |
| Errores | `401` portador inválido o expirado · `403` folio de otro agente · `400` lote inválido |
| Reglas del servidor | El **estado lo fija el servidor** en «recibida», no la app; si la boleta ya existe y su estado **no** es «recibida» —es decir, si el juez ya la calificó— se descartan estado, fecha, datos del infractor y faltas del envío, para que una resincronización no revierta una calificación. Las fotografías y los datos operativos sí se completan |
| Cliente | Autenticado; tiempo límite de 20 s; un `401` se propaga como error tipado para disparar el cierre de sesión; un rechazo de negocio se muestra con el mensaje del servidor |

**5. `POST /api/infracciones/evidencias`** — subida de fotografías

| Aspecto | Detalle |
|---|---|
| Filtro | **`agenteToken`** |
| Cuerpo | `multipart/form-data` con `folio`, `tipo` (`placa`, `contexto`, `documento`) y el archivo `foto` |
| Éxito | `200` con `success` y `url` pública del archivo |
| Errores | `401` sesión no válida · `403` «El folio … pertenece a otro agente.» · `415` «Solo se aceptan imágenes JPG, PNG o WEBP.» · `400` «No se recibió un archivo de imagen o base64 válido.» |
| Reglas del servidor | La extensión se deduce del **contenido**, nunca del nombre enviado; el folio se sanea antes de usarse como carpeta; se acepta también `base64` como alternativa al archivo |
| Cliente | Autenticado; tiempo límite de 30 s; una URI que ya es URL pública se conserva sin volver a subirse |

**Orden de las operaciones.** El orquestador sube **primero** las tres evidencias y **después** la boleta, con las URL ya resueltas. El orden no es casual: el servidor exige que el folio pertenezca al agente para aceptar la evidencia, y eso solo se puede verificar una vez que la boleta existe o que el folio no está tomado. Una evidencia que falla detiene esa boleta y la deja pendiente, sin afectar a las demás.

### 6.2. Respaldo de lectura en Supabase y su implicación de seguridad

De Supabase la app lee **dos tablas y nada más**, y siempre como segunda opción:

| Dato | Ruta primaria | Respaldo | Última red |
|---|---|---|---|
| Catálogo de faltas | `GET /api/checklist-infracciones` | `catalogo_infracciones_app` filtrada por `activo = 1` y ordenada | Caché `@catalogo_infracciones_cache_v1` |
| Parámetros | `GET /api/parametros` | `parametros_app` (clave y valor) | Caché `@parametros_app_cache_v1` |

**Boletas y evidencias no tienen respaldo en la nube, y no debe añadirse sin revisar esta decisión.** Escribir directamente en Supabase desde el cliente significaría que cualquier llave extraída del haz —que es pública por definición— podría insertar boletas a nombre de cualquier placa. El backend municipal es el único que puede comprobar que el folio corresponde al agente del token, y por eso es el único camino de escritura.

La consecuencia práctica para quien mantenga el sistema es concreta: **si las políticas RLS de `catalogo_infracciones_app` y `parametros_app` se desactivan o se abren de más, la llave anónima pasa a ser una credencial de lectura pública**. Esas dos tablas contienen el catálogo normativo y la UMA, que son información publicable; aun así, el RLS debe permanecer activo por política, para que la excepción no se convierta en costumbre.

### 6.3. Ciclo de vida del token de agente

| Etapa | Dónde | Qué ocurre |
|---|---|---|
| **Emisión** | `POST /api/agentes/login` | El servidor genera un portador y su fecha de expiración; el cliente lo recibe junto con el perfil |
| **Persistencia** | `client.ts` | Nativo: `expo-secure-store` (cifrado). Web: `sessionStorage`, que muere con la pestaña |
| **Uso** | Cada petición autenticada | Se adjunta como `Authorization: Bearer …`; las tres lecturas públicas no lo llevan |
| **Rechazo** | `401` en cualquier escritura | El orquestador detiene el lote, marca `requiereLogin` y el contexto llama a `expireSession` con el mensaje de sesión expirada |
| **Cierre voluntario** | `/perfil` | Se borran perfil, token y llaves heredadas; la cola **no** se toca |
| **Revocación administrativa** | Panel, `/admin/sesiones-app` | El administrador puede invalidar la sesión; el siguiente envío del agente recibe `401` y la app pide credenciales nuevas |

### 6.4. Del folio de campo al cobro

La app termina su trabajo cuando la boleta queda sincronizada; a partir de ahí el ciclo es del sistema web, y conviene que el agente lo conozca porque es lo que el ciudadano le preguntará en la calle:

| Etapa | Dónde ocurre | Qué cambia |
|---|---|---|
| 1. **Recibida** | `POST /api/infracciones/sincronizar` | La boleta entra a `boletas_infracciones` con estado «recibida». **No hay monto exigible todavía**: en campo solo se estimó un rango en UMA |
| 2. **Calificada** | Panel del juez (`/juez`) | El juez confirma faltas y valores en UMA; se guarda `boleta_calificaciones` con su detalle y el monto base queda firme |
| 3. **Cobro pendiente** | Módulo de cobros | Se emite un cobro con folio `COB-AAAA-NNNNNN` y se aplica el descuento por pronto pago o el recargo por mora que corresponda |
| 4. **Pagada** | Ventanilla o módulo de cobros | Se registra el pago con folio de recibo `REC-AAAA-NNNNNN` y lugar de pago; el cobro queda cerrado |

El agente **no** interviene después de la etapa 1, y la app no necesita cambios para ello: el estado que muestra es el de la sincronización, no el del cobro. Para consultar el monto exigible se usa la hoja de pago del sistema web (`/documentos/hoja-pago/boleta/{id}`), que imprime el desglose y los lugares de pago habilitados. La ruta es deliberadamente **web**: el agente no emite documentos de cobro desde el teléfono.

---

## 7. Catálogo de faltas y cálculo de montos

### 7.1. Las 78 casillas y sus 15 categorías

`src/constants/catalogoInfracciones.ts` es la **transcripción fiel de las casillas impresas en la boleta física** de Uriangato, con su fundamento legal del Reglamento de Movilidad y sus montos oficiales en UMA. Son **78 casillas en 15 categorías**, y esa cifra es la que vale: cualquier recuento anterior que hable de «80 fracciones» o de categorías numeradas `1.1-15.5` corresponde a una versión previa de la transcripción.

| Categoría | Casillas | Categoría | Casillas |
|---|---|---|---|
| DOCUMENTACIÓN | 5 | CIRCULACIÓN | 4 |
| PLACAS | 4 | ESTACIONAMIENTOS | 14 |
| PARTES INTEGRALES DEL VEHÍCULO | 13 | REMOLCAR | 1 |
| MANEJO | 6 | ACCIDENTES | 5 |
| REBASAR | 5 | ESTADO FÍSICO DEL CONDUCTOR | 3 |
| PREFERENCIA | 3 | FALTAS A LA AUTORIDAD | 2 |
| SEÑALAMIENTOS | 7 | MEDIO AMBIENTE | 4 |
| CARGA | 2 | **Total** | **78** |

Este archivo local **no es la fuente de verdad operativa**: es el respaldo que garantiza que la app abra con un catálogo completo aunque no haya red ni caché. El catálogo vigente es el que administra el panel desde `/admin/app-transito/catalogo` y sirve `GET /api/checklist-infracciones`; si la consulta y el respaldo local difieren, gana la consulta y la caché se sobrescribe. Cuando la diferencia persiste después de un ciclo con red, el problema está en la configuración del panel y no en el teléfono.

### 7.2. Cálculo en UMA y en pesos

El cálculo de la app es una **estimación de campo**, y su alcance debe quedar claro porque es la fuente de la mayoría de las dudas del ciudadano:

* Cada casilla aporta su rango: `montoMinUma` y `montoMaxUma`.
* Por cada casilla se muestran sus dos extremos en pesos: mínimo y máximo multiplicados por la UMA vigente.
* Al seleccionar varias casillas, la app **suma los mínimos** por un lado y **los máximos** por el otro, y presenta el total como una horquilla («de X a Y UMA», y su equivalente en pesos).
* La UMA usada es siempre la que el contexto tiene en memoria, cargada de `GET /api/parametros` o de su caché. El valor de fábrica es 117.31 y solo se usa si nunca hubo respuesta: es un valor de arranque, no un valor oficial.
* Los importes se presentan formateados a dos decimales en pesos; las UMA se presentan con un decimal. No se redondea el importe exigible en ninguna etapa del cliente.

**Lo que la app no calcula y no debe calcular:** descuento por pronto pago, recargo por mora, condonación, ni monto exigible. Esas cuatro operaciones son del servidor (§7.3), porque dependen de fechas y de autorizaciones que el dispositivo no puede ver ni acreditar.

### 7.3. Paridad con el cálculo del servidor

El servidor no recalcula el mismo número que la app: aplica una regla distinta y posterior. La comparación es la que sigue, y es la que debe consultarse cuando un importe no cuadre entre el teléfono y la ventanilla:

| Concepto | App móvil | Servidor (`ResolverMontoInfraccionService`) |
|---|---|---|
| Base | Rango de la casilla en UMA, sumado | Mínimo y máximo en UMA del catálogo, sumado por falta |
| UMA | La de `GET /api/parametros` | La de `parametros_app`, con el mismo valor por omisión |
| Descuento por pronto pago | **No se aplica** | 40 % si se paga dentro de los 10 días hábiles siguientes, contados a partir del día posterior al hecho |
| Recargo por mora | **No se aplica** | 5 % por mes transcurrido cuando se supera un mes, con un mínimo de un mes |
| Caso regular | **Es lo que se muestra** | Sin descuento ni recargo cuando no se cumple ninguno de los dos supuestos |
| Días hábiles | No los cuenta | De lunes a viernes |
| Monto exigible | No lo determina | Es el resultado final y único que se cobra |

Dicho de otro modo: **la app muestra la horquilla del reglamento; el servidor determina el importe de la multa aplicando el calendario.** Un oficial que vea «de 2 a 5 UMA» está viendo la falta, no la sanción; la sanción depende de cuándo se pague, y eso solo el sistema web puede saberlo. Los tres parámetros que gobiernan ese cálculo —porcentaje de descuento, días hábiles del descuento y porcentaje de recargo mensual— son configurables y los edita el administrador, no la app.

---

## 8. Compilación, empaquetado y publicación

La app se publica en **dos formatos distintos** y con consecuencias distintas: una SPA que se sirve desde el backend municipal y un binario Android que se instala en los teléfonos.

### 8.1. SPA web en `/agentes`

El haz web se genera dentro de `public/agentes/`, que es lo que el dominio sirve bajo la ruta `/agentes`. **No se exporta con el comando suelto**: se usa el script del repositorio, porque `expo export --clear` por sí solo deja el artefacto incompleto.

```powershell
cd mobile/agentes-transito
..\..\scripts\exportar_agentes_web.ps1
```

El script hace tres pasos, y cada uno responde a un defecto comprobado:

1. **Exporta** el haz web con las variables de entorno de `mobile/.env`.
2. **Repone las 19 fuentes `.ttf`** que el exportador elimina de `public/agentes/fonts/`. Sin ellas, los iconos se caen en producción y la interfaz pierde todos sus símbolos.
3. **Localiza el shell** `public/agentes/index.html`, que Expo genera en inglés: `lang="en"`, el título, el aviso de «necesitas habilitar JavaScript» y los comentarios de plantilla. El localizador sustituye esas cadenas y **falla si alguna sobrevive**, de modo que un cambio de redacción en Expo se detecta en el build y no en la publicación.

Al terminar, la comprobación honesta es `git status public/agentes`: si sale vacío, el haz del repositorio ya coincidía con el código fuente.

### 8.2. APK Android con EAS

```bash
cd mobile/agentes-transito
npx eas login
npx eas build -p android --profile preview
```

El binario resultante se instala directamente en los dispositivos de campo. Consideraciones que afectan a la operación:

* Los permisos de ubicación y cámara se declaran en `app.json`; una compilación que los pierda dejará la app sin GPS ni evidencias, y el síntoma será un permiso denegado que el usuario no podrá conceder.
* La orientación está fijada en vertical y el esquema de enlace es `agentes-transito`.
* El identificador de versión vive en `app.json`; el parámetro `app_version` de la base de datos es informativo para el panel y **no** actualiza la app. Mantener los dos sincronizados es una tarea manual y una fuente clásica de confusión.

### 8.3. Las tres trampas del haz web

| Trampa | Síntoma | Regla |
|---|---|---|
| **Haz viejo con fuente nuevo** | La app de demostración funciona distinto a la del teléfono | Cualquier cambio en `mobile/` es **invisible en producción** hasta reexportar. El peor estado posible es el intermedio |
| **Shell en inglés** | Un texto institucional en otro idioma antes de que cargue la app | El shell **no** contiene textos de pantalla: si un botón aparece en inglés, la cadena vive en el `entry-*.js` y debe buscarse en el fuente React, no reescribiendo el HTML |
| **Confundir las dos superficies** | Se edita CSS esperando que cambie la app, o se reexporta creyendo que se arregla el portal | `/agentes` es una SPA de Expo con su propio CSS; el portal y el panel son vistas PHP con `public/css/custom.css`. No se afectan entre sí |

---

## 9. Verificación y solución de problemas

### 9.1. Verificador de flujo sin dispositivo

El cliente incluye un verificador ejecutable con Node puro, sin Metro, sin emulador y sin teléfono:

```bash
cd mobile/agentes-transito
node tests/auth-flow.test.cjs     # 18 casos
npx tsc --noEmit                  # tipos del cliente; debe salir limpio
```

Resultado al corte: **18 de 18 casos** y tipado **sin errores**. El verificador no necesita red ni credenciales porque sustituye el almacenamiento y el transporte por dobles de prueba, y esa es su virtud: puede correr en cualquier máquina y en cualquier momento.

### 9.2. Los 18 casos y qué protegen

| Bloque | Casos | Qué protege |
|---|---|---|
| **Restauración de perfil** | 6 | Guarda y restaura solo el perfil conservando las boletas; **no** restaura la llave antigua que pudo contener identidades simuladas; sin token no restaura perfil y pide login **sin tocar** las boletas; un error al guardar se propaga; rechaza perfiles mal formados; el cierre limpia únicamente perfil y credencial |
| **Almacenamiento del token** | 2 | En web no se llama a `SecureStore` y solo se borra el token antiguo; en nativo el token se guarda cifrado y se envía como `Bearer` |
| **Respuesta del servidor de acceso** | 5 | Un `401` se expone como error tipado con su estado; el login devuelve perfil y token cuando hay éxito; una respuesta sin token **no** produce sesión; un rechazo `401` no produce identidad local; un fallo de red no produce identidad fabricada |
| **Envío de boletas** | 5 | La sincronización propaga el `401` y **no** recurre a Supabase; envía la boleta autenticada; un `401` al sincronizar conserva las pendientes y pide login; las boletas aceptadas antes del `401` quedan sincronizadas y el resto pendiente; otros errores **no** piden iniciar sesión |

El caso más importante operacionalmente es el de la cuarta fila: **un `401` al sincronizar no destruye trabajo**. Un lote de diez boletas que falla en la sexta deja cinco sincronizadas y cinco pendientes, no diez perdidas ni diez duplicadas. Y el simétrico, «otros errores no piden iniciar sesión», evita el defecto inverso: echar al oficial de su turno porque el servidor devolvió un `500`.

### 9.3. Matriz de sincronía con el backend

Cuando el backend o el panel cambian, la pregunta no es «¿la app funciona?» sino «¿sigue alineada con el contrato vigente?».

| # | Verificación | Método | Criterio de aprobación |
|---|---|---|---|
| 1 | **Contrato de API** | Cotejar las rutas invocadas por `src/services/` y `src/api/` contra `app/Config/Routes.php` | Coincidencia exacta en los cinco endpoints; las dos escrituras protegidas por `agenteToken` |
| 2 | **Ausencia de ruptura de contrato** | Revisar los últimos cambios del backend restringidos a los archivos que la app consume | Ninguno toca los cinco endpoints, el modelo de tokens, `boletas_infracciones`, el catálogo ni `parametros_app` |
| 3 | **Versión publicada** | Comparar `package.json`, `app.json` y el parámetro `app_version` del panel | El mismo valor en los tres sitios |
| 4 | **Entorno del cliente** | Revisar `mobile/agentes-transito/.env` | Origen del backend correcto y entorno de producción declarado |
| 5 | **Estado del código** | `git status` sobre `mobile/` | Sin cambios sin compilar |
| 6 | **Haz servido en `/agentes`** | Reexportar con el script y sondear el dominio | `git status public/agentes` vacío y el mismo `entry-*.js` servido desde producción |

> **Cómo se usó esta matriz para descartar un reporte equivocado.** Se reportó que la versión publicada seguía mostrando un botón en inglés porque «el haz desplegado es viejo». Las filas 5 y 6 lo niegan con evidencia: la cadena no existe en el repositorio, el reexport no produce diferencia y el haz de producción es idéntico al commiteado. El defecto real estaba en otro lado y sí era propio del código: los atajos `/login`, `/register` y `/logout` de las vistas PHP estaban muertos por una colisión de nombre de ruta. **Consecuencia metodológica:** antes de reconstruir algo que «se ve viejo», comprobar primero que el fuente no contenga el síntoma.

### 9.4. Problemas conocidos y su diagnóstico

| Síntoma | Causa habitual | Diagnóstico |
|---|---|---|
| «No se pudo conectar con el servidor» en el acceso | `EXPO_PUBLIC_API_BASE_URL` ausente, o el teléfono no alcanza la PC de desarrollo | Revisar la URL resuelta que se imprime en consola en desarrollo; comprobar que el backend atiende en el puerto esperado |
| El acceso funciona pero la sincronización siempre falla con `401` | Token vencido, agente dado de baja o sesión invalidada desde el panel | Revisar `/admin/sesiones-app`; volver a iniciar sesión en el teléfono |
| «El folio … pertenece a otro agente» | El folio se capturó con otra placa y se intenta subir con la actual | No se corrige en el cliente: el folio es del agente que lo emitió |
| Sincronización dice «Sincronización incompleta» una y otra vez | El servidor rechaza el contenido de una boleta concreta, o está caído | Revisar el historial; una boleta con error de sincronización necesita revisión, no reintento ciego |
| El catálogo muestra faltas que el panel no tiene, o al revés | Caché de catálogo desactualizada, o el panel tiene el catálogo vacío y el servidor está devolviendo el checklist oficial | Abrir la app con red; comparar con `/admin/app-transito/catalogo` |
| La UMA no coincide con la del panel | Caché de parámetros vieja, o el panel no ha guardado el valor | Abrir con red, o revisar «Configuración App Agentes de Tránsito» |
| La app se declara sin conexión con red disponible | El sondeo de conectividad falla porque el origen no responde a `HEAD /` | Comprobar que `HEAD /` contesta `200` en el dominio |
| Los iconos aparecen como cuadros | Faltan las fuentes del haz | Reexportar con el script; verificar los 19 `.ttf` en `public/agentes/fonts/` |
| Se ven boletas de otro oficial en el teléfono | Comportamiento esperado de un dispositivo compartido | La app solo muestra las del agente en turno; las demás permanecen guardadas e invisibles |
| Aparecen dos boletas de ejemplo en el historial | `EXPO_PUBLIC_APP_ENV` vale `development` en una compilación publicada | Corregir el entorno y recompilar; jamás publicar con esa bandera |

**Cierre.** La app es la única pieza del sistema que opera sin red por diseño, y esa condición explica casi todas sus decisiones: guardar antes de enviar, reintentar sin rendirse, no cerrar sesión por un error que no sea de credenciales y no destruir trabajo ajeno al cambiar de turno. Cualquier cambio futuro que introduzca una dependencia de red en la captura, o que borre boletas al cerrar sesión, rompe la promesa central del subsistema y debe rechazarse aunque parezca una simplificación.
