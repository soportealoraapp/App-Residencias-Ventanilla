# Documentación técnica del backend — Ventanilla Única de Movilidad y Transporte

> **Estado del documento:** actualizado al 29 de octubre de 2026
> **Alcance:** fuente de verdad técnica del servidor: arquitectura, configuración, controladores, modelos, librerías, filtros, API móvil, catálogo de rutas, esquema de datos, operación y pruebas.
> **Fuente de verdad:** código del repositorio (`app/`, `supabase/`, `scripts/`), verificado por ejecución de gates el 29-oct-2026.
> **Documentos relacionados:** [README.md](./README.md) · [DOCUMENTACION_GENERAL.md](./DOCUMENTACION_GENERAL.md) · [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) · [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md) · [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md)

Este documento es la **única fuente de verdad técnica** del sistema. El diccionario de datos completo vive aquí (§11); las fichas funcionales de cada pantalla viven en [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) §4-§7 y en [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md) §4. La visión macro de negocio está en [DOCUMENTACION_GENERAL.md](./DOCUMENTACION_GENERAL.md).

---

## Índice

1. [Visión general y arquitectura de la petición](#1-visión-general-y-arquitectura-de-la-petición)
2. [Estructura del directorio `app/`](#2-estructura-del-directorio-app)
3. [Configuración y entorno](#3-configuración-y-entorno)
4. [Capa de controladores](#4-capa-de-controladores)
5. [Modelos](#5-modelos)
6. [Librerías de servicio](#6-librerías-de-servicio)
7. [Filtros y control de acceso (RBAC)](#7-filtros-y-control-de-acceso-rbac)
8. [Seguridad](#8-seguridad)
9. [API REST de la app móvil](#9-api-rest-de-la-app-móvil)
10. [Catálogo de rutas](#10-catálogo-de-rutas)
11. [Base de datos](#11-base-de-datos)
12. [Comandos y operación](#12-comandos-y-operación)
13. [Pruebas y despliegue](#13-pruebas-y-despliegue)

---

## Inventario canónico del backend

Cifras medidas el 29-oct-2026 con los comandos indicados. Cualquier otro documento que cite cantidades debe remitirse a esta tabla.

| Elemento | Cantidad | Método de medición |
|---|---|---|
| Rutas registradas | **173** (96 `GET`, 71 `POST`, 5 `OPTIONS`, 1 `HEAD`) | `./php82/php.exe spark routes`, conteo de la tabla de salida |
| Controladores | **30** (6 en la raíz, 12 en `Admin/`, 9 en `Ciudadano/`, 2 en `Api/`, 1 en `Juez/`) | inventario de `app/Controllers/` |
| Vistas | **76** (5 layouts, 8 de error, 1 paginador, 62 de pantalla y parciales) | inventario de `app/Views/` |
| Librerías de servicio | **22** | inventario de `app/Libraries/` |
| Modelos | **24** | inventario de `app/Models/` |
| Filtros propios | **5** (`auth`, `role`, `permisoOperador`, `adminRedirect`, `agenteToken`) | `app/Filters/` |
| Tablas | **26** (25 creadas por migraciones de CI4 + `ci_sessions`) | `scripts/verificar_esquema_bd.php` |
| Migraciones | **33** de CI4 / **19** de Supabase SQL | inventario de `app/Database/Migrations/` y `supabase/migrations/` |
| Seeders | **11** | inventario de `app/Database/Seeds/` |
| Permisos configurables del operador | **14** | `app/Libraries/PermisosOperador.php` |
| Clases de prueba | **43** | inventario de `tests/` |
| Casos de prueba | **438** con **1 876** aserciones | `./php82/php.exe vendor/bin/phpunit` |
| Comandos `spark` propios | **1** (`demo:limpiar`) | `app/Commands/` |

---

## 1. Visión general y arquitectura de la petición

### 1.1 Qué es el backend

El backend es una aplicación **CodeIgniter 4.7.4** sobre **PHP 8.2** que sirve tres superficies web y una API móvil desde un solo despliegue:

| Superficie | Prefijo de rutas | Consumidor |
|---|---|---|
| Portal del ciudadano | `/portal`, `/auth`, `/` | Personas que inician trámites en línea |
| Panel administrativo | `/admin` | Administrador y operador de ventanilla |
| Panel del juez calificador | `/juez` | Juez calificador de faltas |
| API de la app de tránsito | `/api` | App móvil de agentes de tránsito |
| Archivos y documentos | `/archivos`, `/documentos` | Todas las superficies anteriores |

Cada superficie se separa por **prefijo de ruta + filtro de rol**, no por despliegue. La base de datos es una instancia única de **PostgreSQL 15** alojada en Supabase.

### 1.2 Ciclo de vida de una petición

El recorrido es siempre el mismo y conviene tenerlo presente antes de leer cualquier sección posterior:

1. **Vercel** recibe la petición HTTPS y aplica su tabla de `rewrites` (`vercel.json`). Si la ruta coincide con un recurso estático listado en la *whitelist*, Vercel lo sirve directamente y el backend no interviene.
2. **`api/index.php`** — único punto de entrada en producción. Corrige `SCRIPT_NAME` y `PHP_SELF` para que CI4 genere URLs correctas dentro del contenedor serverless, carga `Paths.php`, `bootstrap.php` y arranca el framework. En local el mismo rol lo cubre `public/index.php`.
3. **`Config\Routes`** resuelve la ruta. El sistema tiene `setAutoRoute(false)`, por lo que **una ruta que no esté declarada responde 404**; no hay descubrimiento automático por convención de nombre. Además `setPrioritize(true)` hace que las rutas declaradas ganen sobre las de menor prioridad.
4. **Filtros globales** (`cors`, `csrf` con excepción de `api/*`, `invalidchars` antes; `toolbar` y `secureheaders` después). Se aplican a toda petición, incluidas las que luego fallan por 404.
5. **Filtros de la ruta** (`auth`, `role:*`, `permisoOperador`, `adminRedirect`, `agenteToken`). Aquí ocurre el control de acceso real.
6. **Controlador**: valida entrada, aplica reglas de negocio simples y delega lo pesado.
7. **Librería de servicio** (`app/Libraries/`) cuando hay lógica de negocio compartida: cobros, estados, montos, almacenamiento, desglose, permisos.
8. **Modelo** (`app/Models/`) para persistencia. Cada modelo encapsula su tabla y sus consultas compuestas.
9. **Respuesta**: vista HTML (renderizada dentro de un layout) o JSON (`$this->response->setJSON(...)`).

### 1.3 Capas y responsabilidad

| Capa | Ubicación | Responsabilidad | Prohibido aquí |
|---|---|---|---|
| Enrutado | `app/Config/Routes.php` | Declarar rutas, alias y filtros de ruta | Lógica de negocio |
| Filtros | `app/Filters/` | Decidir si la petición continúa, redirigir o rechazar | Consultas de negocio |
| Controladores | `app/Controllers/` | Orquestar: validar, llamar servicios, elegir respuesta | Reglas de negocio que deban reutilizarse |
| Librerías | `app/Libraries/` | Reglas de negocio reutilizables y con estado acotado | Renderizar HTML |
| Modelos | `app/Models/` | Persistencia y consultas | Reglas de negocio entre tablas |
| Vistas | `app/Views/` | Presentación | Consultas a base de datos |

La separación no es dogmática. Los controladores de trámite (`Ciudadano/Tramite*Controller`) contienen validación de formulario y mapeo de campos porque cada trámite tiene un formulario distinto; lo que sí se centraliza es la máquina de estados (`EstadoSolicitudService`), el tarifario (`TarifarioService`), la carga de archivos (`DocumentoUploader`) y el alta de ciudadanos (`CuentaCiudadanoService`).

### 1.4 Modelo de petición del panel frente al del portal

La diferencia funcional más importante del sistema:

- **Portal del ciudadano:** cada consulta filtra por `session('user_id')`. Un ciudadano solo puede ver sus propias solicitudes, documentos y cobros. La comprobación de propiedad está en el controlador, no en el filtro.
- **Panel administrativo:** el administrador y el operador ven **todas** las solicitudes y todos los cobros del municipio. La restricción no es de propiedad sino de **permiso de sección** (`permisoOperador`), más el invariante de que las bajas definitivas son exclusivas del administrador.
- **Panel del juez:** ve solo boletas de infracción en estado `recibida` y los cobros que él mismo puede cobrar; no administra catálogos ni usuarios.

### 1.5 Despliegue serverless: tres consecuencias de diseño

1. **Sesiones en base de datos.** En Vercel no hay sistema de archivos persistente entre invocaciones, por lo que el manejador de sesión por archivos perdería la sesión en cada petición. `Config\Session` cambia a `DatabaseHandler` con tabla `ci_sessions` cuando detecta `VERCEL` o `VERCEL_ENV` (§3.4).
2. **Connection pooler.** Vercel bloquea conexiones salientes al puerto 5432. `Config\Database` detecta el entorno serverless y, si existe `POSTGRES_URL`, reescribe host, puerto, usuario, contraseña y base desde esa cadena (que apunta al pooler PgBouncer en el puerto **6543**) (§3.3).
3. **Caché por petición.** Cualquier caché en memoria (permisos del operador, parámetros de la app) vive solo lo que dura una petición; no hay proceso largo que la conserve. El código lo asume de forma explícita: `PermisosOperador` usa una propiedad estática con caché por petición y ofrece `reiniciarCache()` para las pruebas.

---

## 2. Estructura del directorio `app/`

```
app/
├── Commands/          1 comando propio de consola
├── Config/            41 archivos de configuración de CI4 + los del proyecto
├── Controllers/       30 controladores
├── Database/          33 migraciones + 11 seeders
├── Entities/          1 entidad
├── Filters/           5 filtros propios
├── Helpers/           1 helper propio
├── Interfaces/        2 contratos
├── Language/          1 archivo de validación en español
├── Libraries/         22 librerías de servicio
├── Models/            24 modelos
└── Views/             76 vistas y parciales
```

### 2.1 `Commands/`

| Archivo | Comando | Qué hace |
|---|---|---|
| `LimpiarDatosDemo.php` | `demo:limpiar` | Borra los datos generados por los seeders de demostración para dejar la base en estado limpio, sin tocar catálogos base. |

### 2.2 `Config/`

Los archivos con personalización propia del proyecto son los que importan al mantenimiento:

| Archivo | Personalización |
|---|---|
| `App.php` | Resuelve `baseURL` desde `APP_BASEURL` o `app.baseURL`; registra el host en `allowedHostnames`; desactiva `forceGlobalSecureRequests` en serverless; configura proxy de confianza. |
| `Database.php` | Triple formato de variables de entorno, detección de Vercel y lectura del pooler (§3.3). |
| `Filters.php` | Alias de los 5 filtros propios, filtros globales y aplicación masiva de `permisoOperador` (§7). |
| `Routes.php` | Las 173 rutas registradas (§10). |
| `Session.php` | Cambio a `DatabaseHandler` con `ci_sessions` en Vercel (§3.4). |
| `Cors.php` | Orígenes y cabeceras para la API móvil. |
| `Security.php` | CSRF, cookies y expiración. |
| `Feature.php` | Banderas de funcionalidad leídas por `FeatureFlags`. |
| `Email.php` | SMTP para el aviso de restablecimiento de contraseña. |
| `Validation.php` | Reglas de validación con mensajes en español. |

### 2.3 `Entities/`

| Entidad | Uso |
|---|---|
| `Solicitud.php` | Representa una solicitud con métodos de ayuda para leer sus datos de formulario y su estatus actual. Se usa en el portal y en el panel para no repetir el acceso a `solicitud_datos`. |

### 2.4 `Helpers/`

| Helper | Aporta |
|---|---|
| `url_helper_custom.php` | Envoltorios de `site_url()` y generación de enlaces que respetan el `baseURL` resuelto, incluido el caso de Vercel donde `SCRIPT_NAME` cambia. |

Las vistas lo cargan con `helper('url_helper_custom')`; el helper está registrado en `Config\Autoload` para las vistas que lo necesitan de forma global.

### 2.5 `Interfaces/`

| Interfaz | Implementación actual | Propósito |
|---|---|---|
| `PaymentGatewayInterface` | `BanbajioMockGateway` | Aísla la pasarela de pago para poder sustituir el simulador por la integración real de BanBajío sin tocar los controladores de trámite. |
| `ReferenciaBancariaInterface` | `ReferenciaBancariaProvisional` | Contrato de generación y validación de la referencia bancaria impresa en la hoja de pago. |

### 2.6 `Language/`

| Archivo | Contenido |
|---|---|
| `Validation.php` | Traducciones al español de los mensajes de validación de CI4, para que los formularios no muestren texto en inglés. |

---

## 3. Configuración y entorno

### 3.1 Archivo `.env`

El repositorio incluye `.env.example` con 153 líneas organizadas por sección. Se copia a `.env` y se completan los valores. Las secciones son:

| Sección | Variables principales | Obligatoria en local | Obligatoria en Vercel |
|---|---|---|---|
| `ENVIRONMENT` | `CI_ENVIRONMENT` | Sí | Sí |
| `APP` | `APP_BASEURL`, `APP_FORCE_HTTPS`, `APP_ALLOWED_HOSTNAMES`, `APP_TIMEZONE`, `APP_LOCALE` | Sí | Sí |
| `DATABASE` | `POSTGRES_*`, `POSTGRES_URL`, `POSTGRES_PRISMA_URL`, `DATABASE_DEFAULT_*`, `database.default.*` | Sí | Sí |
| `SUPABASE PROJECT` | `SUPABASE_URL`, `SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY`, `SUPABASE_JWT_SECRET`, `SUPABASE_SECRET_KEY` | Sí | Sí |
| `ENCRYPTION` | `encryption.key` | Sí | Sí |
| `SECURITY` | `SECURITY_CSRF*` | No (hay valores por defecto) | Recomendado |
| `COOKIES` | `COOKIE_*` | No | Recomendado |
| `CUSTOM` | `APP_ENABLE_UR_TT_T_06` | No | No |
| `EMAIL` | `EMAIL_SMTP_*`, `EMAIL_FROM*` | Solo para recuperar contraseña | Sí |

> **Nota de seguridad.** En este documento se nombran **variables**, nunca valores. Las claves de servicio de Supabase (`SUPABASE_SERVICE_ROLE_KEY`, `SUPABASE_SECRET_KEY`, `SUPABASE_JWT_SECRET`) omiten RLS y no deben versionarse ni publicarse en documentación. La clave `anon` es pública por diseño, pero solo es segura si las políticas RLS de Supabase están activas (§8.8).

### 3.2 `Config\App`: resolución del `baseURL`

`App::$baseURL` arranca en `http://localhost:8080/` y su constructor lo reemplaza con la primera variable encontrada entre `APP_BASEURL` y `app.baseURL` (el formato con puntos es el legado de CI4). Después:

- Se recorta la barra final y se asegura una sola.
- El host del `baseURL` se agrega automáticamente a `allowedHostnames`.
- Los hosts de `APP_ALLOWED_HOSTNAMES` (separados por coma) se agregan a la misma lista.
- En entornos serverless **no** se activa `forceGlobalSecureRequests`: la redirección la hace el edge de Vercel, y forzarla dentro del contenedor produciría bucles de redirección.
- Si el `baseURL` usa `https`, se activa igualmente la marca de seguridad para las cookies.

El proxy de confianza se declara explícitamente porque Vercel termina TLS en su edge y entrega la petición por HTTP interno; sin esa declaración, CI4 no reconocería el esquema original y generaría enlaces `http://`.

### 3.3 `Config\Database`: tres formatos de variable y detección de serverless

El constructor resuelve cada valor con una cascada de tres candidatos, en este orden:

1. `POSTGRES_*` — nombres estándar de Supabase (`POSTGRES_HOST`, `POSTGRES_DATABASE`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_PORT`).
2. `DATABASE_DEFAULT_*` — formato compatible con Vercel, sin puntos en el nombre (`DATABASE_DEFAULT_HOSTNAME`, `DATABASE_DEFAULT_DATABASE`, `DATABASE_DEFAULT_USERNAME`, `DATABASE_DEFAULT_PASSWORD`, `DATABASE_DEFAULT_PORT`, `DATABASE_DEFAULT_SCHEMA`, `DATABASE_DEFAULT_SSLMODE`, `DATABASE_DEFAULT_FOREIGNKEYS`, `DATABASE_DEFAULT_BUSYTIMEOUT`).
3. `database.default.*` — formato legado de CI4 con puntos, útil con `spark serve` en local.

Se usa el primero que exista y no esté vacío. Consecuencia práctica: **el mismo archivo `.env` sirve para local y para Vercel**, porque en local gana el formato con puntos y en Vercel gana el de Supabase.

Lógica específica de serverless:

- Se considera serverless si `VERCEL` o `VERCEL_ENV` están definidas.
- En ese caso se busca `POSTGRES_URL` y luego `POSTGRES_PRISMA_URL`; se parsea la cadena y se sobrescriben host, puerto, usuario (con `urldecode`), contraseña (con `urldecode`), base de datos y `sslmode`.
- Se fuerza `DBDriver = 'Postgre'` y `schema = 'public'`.
- El puerto del pooler es **6543**; el directo es **5432**. Vercel bloquea la salida hacia 5432, por lo que un `POSTGRES_URL` apuntando al puerto directo produce tiempo de espera agotado, no un error de credenciales.

`Config\Database::$tests` define el grupo de pruebas: SQLite3 en memoria, con prefijo **`db_`** en todas las tablas. Ese prefijo es lo que permite que las pruebas corran en paralelo conceptual con el esquema real sin colisionar nombres, y es la razón por la que los nombres de tabla en pruebas no coinciden literalmente con los de producción.

### 3.4 `Config\Session`: sesiones en base de datos cuando hay serverless

| Parámetro | Valor por defecto | Valor en Vercel |
|---|---|---|
| Manejador | `FileHandler` | `DatabaseHandler` |
| Ruta de guardado | `writable/session` | tabla `ci_sessions` |
| Grupo de base de datos | `null` | `null` (usa el grupo `default`) |
| Nombre de cookie | `ci_session` | `ci_session` |
| Expiración | 7200 s | 7200 s |
| `matchIP` | `false` | `false` |
| `timeToUpdate` | 300 s | 300 s |

La tabla `ci_sessions` **no** está creada por una migración de CI4: se crea con `supabase/migrations/0003_ci_sessions.sql`. Es la única tabla del sistema en esa situación, y por eso el conteo de tablas es 25 + 1 = **26**.

`matchIP` se deja en `false` a propósito: en serverless la IP de origen puede cambiar entre invocaciones y activarlo provocaría cierres de sesión aleatorios.

### 3.5 `Config\Filters`

Los filtros globales se aplican a toda petición:

| Momento | Filtro | Nota |
|---|---|---|
| `before` | `cors` | Responde preflight según `Config\Cors`. |
| `before` | `csrf`, con `except => ['api/*']` | La API móvil no usa cookies de sesión; usa token Bearer. Excluirla evita que la app tenga que obtener un token CSRF. |
| `before` | `invalidchars` | Rechaza caracteres inválidos en la URL y en el cuerpo. |
| `after` | `toolbar` | Barra de depuración, solo visible con `CI_ENVIRONMENT` distinto de `production`. |
| `after` | `secureheaders` | Cabeceras de seguridad (CSP, `X-Frame-Options`, etc.). |

En `ENVIRONMENT === 'testing'` el constructor **elimina** el filtro CSRF de los globales. Sin esa excepción, toda prueba que hiciera `POST` fallaría por token ausente, y las pruebas habrían que rellenar el token en cada caso. Es una concesión aceptada y documentada, no un descuido.

Además de los globales, `Config\Filters::$filters` aplica dos filtros de forma masiva, sin declararlos ruta por ruta:

| Filtro | Rutas donde se aplica |
|---|---|
| `adminRedirect` | `portal`, `portal/dashboard`, `portal/mis-solicitudes`, `portal/solicitud/*`, `portal/mi-perfil` |
| `permisoOperador` | `admin`, `admin/*` |

La aplicación masiva es deliberada: una ruta nueva bajo `admin/` queda automáticamente sujeta a los permisos del operador sin que nadie tenga que recordar añadir el filtro.

### 3.6 `Config\Cors`

Configura los orígenes permitidos para la API móvil. El detalle operativo: los endpoints de escritura de la API (`sincronizar`, `evidencias`, `agentes/login`) **también** escriben las cabeceras CORS dentro del controlador, además del filtro global. La duplicación es intencional: si el filtro global cambia de configuración, la app móvil sigue funcionando porque el controlador responde su propio preflight (`OPTIONS`) con `204` y cuerpo vacío. `AgenteTokenFilter` deja pasar explícitamente los `OPTIONS` por la misma razón: el preflight no lleva credenciales.

### 3.7 `Config\Security`

| Parámetro | Valor | Efecto |
|---|---|---|
| `CSRFProtection` | `true` | Todo `POST` web exige token. |
| `CSRFTokenName` | `csrf_csrf_token` | Nombre del campo oculto en los formularios. |
| `CSRFCookieName` | `csrf_cookie` | Cookie donde viaja el token. |
| `CSRFExpire` | `7200` s | Vigencia del token. |
| `CSRFRegenerate` | `true` | Se regenera en cada envío válido. |
| `CSRFSameSite` | `Lax` | Compatible con navegación normal y con POST desde el propio sitio. |

Todas las vistas que contienen formularios usan `csrf_field()`. Un `POST` sin token no llega al controlador: CI4 responde 403 antes.

### 3.8 `Config\Feature` y banderas de funcionalidad

`FeatureFlags` lee variables con el prefijo `APP_ENABLE_` desde el entorno y considera habilitado cualquier valor de `true`, `1` o `yes` (sin distinguir mayúsculas).

| Bandera | Variable | Efecto cuando está activa |
|---|---|---|
| UR-TT-T-06 (cesión de concesión) | `APP_ENABLE_UR_TT_T_06` | El trámite de cesión aparece y es operable desde el portal. **Valor por defecto: deshabilitado.** |

Cuando la bandera está apagada, el portal no ofrece el acceso al formulario de cesión y el controlador rechaza la operación; el panel administrativo sigue mostrando los expedientes históricos de ese trámite. La bandera existe porque el trámite depende de que la Tesorería Municipal confirme el procedimiento de pago de derechos de cesión.

### 3.9 `Config\Encryption`, `Cache` y `Logger`

| Archivo | Configuración relevante |
|---|---|
| `Encryption.php` | Clave desde `encryption.key` (`hex2bin:` + 64 caracteres hexadecimales). Se genera con `php spark key:generate`. **Si la clave cambia, los valores cifrados previamente dejan de descifrarse.** |
| `Cache.php` | Manejador por archivos con respaldo; no se usa caché de aplicación para datos de negocio. |
| `Logger.php` | Umbral 4 (info y superiores) en producción; escribe en `writable/logs/`. |

### 3.10 `Config\Routes`: dos trampas documentadas

`Routes.php` contiene comentarios que explican dos fallos reales ya corregidos. Ambos se repiten aquí porque cualquier ruta nueva puede tropezar con ellos:

**Trampa 1 — colisión entre `from` y `as`.** CI4 descarta **en silencio** una ruta cuyo patrón (`from`) coincide con el nombre (`as`) de otra ruta ya declarada. Por eso los atajos `login`, `register` y `logout` necesitan alias propios (`acceso`, `registro`, `salida`): sin ellos, `login` colisiona con el alias `login` de `auth/login` y el atajo responde 404 sin ningún error en el log.

**Trampa 2 — dos rutas al mismo método.** `agentes` y `agentes/(:any)` apuntan ambas a `AgentesWebController::shell`. Si la segunda heredara el alias de la primera, CI4 la descartaría. Cada una lleva su propio nombre (`agentes.inicio`, `agentes.ruta`).

**Caso especial — `HEAD /`.** `NetInfo` de la app móvil pregunta `HEAD /` para decidir si hay conexión, y solo acepta una respuesta 200. CI4 no resuelve `HEAD` contra las rutas declaradas con `get()`, así que el sondeo caía en 404, la app se declaraba sin conexión y la sincronización automática al recuperar la red nunca se disparaba. La solución es una ruta `head('/')` que responde 200 con cuerpo vacío **sin pasar por un controlador**, para no abrir una sesión en cada sondeo.

---

## 4. Capa de controladores

30 controladores agrupados por superficie. En cada tabla, la columna **Acceso** indica el filtro efectivo: `público` (sin filtro), `auth` (sesión válida), `role:...` (rol exigido por `RoleFilter`) o `agenteToken` (token Bearer de la app móvil). Todas las rutas del panel heredan además `permisoOperador` de forma masiva (§3.5), que solo afecta al rol `operador_ventanilla`.

### 4.1 Controladores de la raíz (6)

#### `Home`

| Método | Ruta | Acceso | Qué hace | Datos | Respuesta |
|---|---|---|---|---|---|
| `index` | `GET /` | público | Portada institucional del municipio con accesos a consulta de trámites, inicio de sesión y registro. | Ninguno | Vista `portal/home` |

#### `AuthController` (402 líneas)

| Método | Ruta | Acceso | Qué hace | Datos | Respuesta |
|---|---|---|---|---|---|
| `login` | `GET auth/login` (alias `acceso`, `login`) | público | Muestra el formulario de acceso. Si ya hay sesión, redirige según rol. | Sesión | Vista `auth/login` con layout `auth/layout_auth` |
| `attemptLogin` | `POST auth/attempt-login` | público | Valida credenciales, verifica `activo`, carga roles en sesión y redirige por rol: administrador y operador → `/admin/dashboard`; juez → `/juez`; ciudadano → `/portal/dashboard`. | `users`, `user_roles`, `roles`, `auditoria` | Redirección o retorno con errores |
| `register` | `GET auth/register` (alias `registro`, `register`) | público | Formulario de autorregistro **solo para ciudadanos**. | — | Vista `auth/register` |
| `attemptRegister` | `POST auth/attempt-register` | público | Crea la cuenta vía `CuentaCiudadanoService::crear()`. Si detecta un ciudadano previo de ventanilla con la misma CURP, **vincula los trámites existentes** a la nueva cuenta. | `users`, `ciudadanos`, `solicitudes`, `SupabaseStorage` (INE) | Redirección a `auth/login` |
| `logout` | `GET auth/logout` (alias `salida`, `logout`) | público | Destruye la sesión y redirige al inicio. | Sesión | Redirección |
| `forgot` | `GET auth/forgot` | público | Formulario de recuperación por correo. | — | Vista `auth/forgot` |
| `attemptForgot` | `POST auth/attempt-forgot` | público | Genera token de restablecimiento y envía el correo. **Responde siempre el mismo mensaje**, exista o no la cuenta, para no revelar qué correos están registrados. | `users`, `Email` | Redirección con aviso neutro |
| `reset` | `GET` y `POST auth/reset/(:any)` | público | Formulario y aplicación del cambio de contraseña con token temporal. | `users` | Vista `auth/reset` o redirección a `auth/login` |
| `terminos` | `GET auth/terminos` | público | Términos de uso del portal. | — | Vista `auth/terminos` |
| `privacidad` | `GET auth/privacidad` | público | Aviso de privacidad. | — | Vista `auth/privacidad` |

Mensajes al usuario (texto literal):

| Situación | Mensaje |
|---|---|
| Credenciales inválidas | «Usuario o contraseña incorrectos.» |
| Cuenta desactivada | «Tu cuenta está desactivada. Acude a la ventanilla de Tránsito y Transporte.» |
| Registro exitoso con vínculo de trámites previos | «Vinculamos los trámites que ya tenías registrados en ventanilla. Ya puedes iniciar sesión.» |
| Registro exitoso normal | «Cuenta creada con éxito. Ya puedes iniciar sesión.» |
| Alta fallida | «No fue posible crear tu cuenta. Intenta de nuevo.» |
| Recuperación solicitada | «Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.» |
| Token consumido | «Token inválido o expirado.» |
| Contraseña cambiada | «Contraseña actualizada correctamente.» |

#### `CobrosController` (419 líneas)

Lo usan **dos superficies** con el mismo código: `/admin/cobros` y `/juez/cobros`. El controlador detecta la superficie con `uri_string()` y elige el layout (`layouts/admin` o `layouts/juez`) y el prefijo de los enlaces de retorno. Es el controlador más reutilizado del sistema y el núcleo del módulo COB.

| Método | Ruta (admin y juez) | Acceso | Qué hace | Datos / servicios | Respuesta |
|---|---|---|---|---|---|
| `index` | `GET cobros` | `role:administrador,operador_ventanilla` / `role:juez_calificador` | Listado paginado (20 por página) con los pendientes primero. Filtros: texto (`q`), estatus, origen (solicitud/boleta), rango de fechas. | `CobroModel`, `BoletaInfraccionModel` | Vista `cobros/index` |
| `ver` | `GET cobros/(:num)` | ídem | Detalle del cobro: concepto, desglose de cálculo, movimientos del historial, datos del pagador y del expediente o boleta de origen. | `CobroService::historial()` | Vista `cobros/ver` |
| `cobrar` | `POST cobros/(:num)/cobrar` | ídem | Registra el pago. Si el lugar de pago es `banbajio` usa `registrarPagoBancario()` (con referencia e importe); en cualquier otro caso usa `marcarCobrado()`. Ambas rutas recalculan descuento y recargo al momento del cobro. | `CobroService` | Redirección con mensaje |
| `completar` | `POST cobros/(:num)/completar` | ídem | Deja el cobro **pendiente** pero marca `pendiente_documentos = 1` con el motivo capturado. No cambia de estatus. | `CobroService::mandarACompletar()` | Redirección |
| `cancelar` | `POST cobros/(:num)/cancelar` | ídem | Cancela el cobro con motivo obligatorio. Si el cobro viene de una solicitud, la solicitud no cambia de estatus: se cancela solo el cobro. | `CobroService::cancelar()` | Redirección |
| `banco` | `GET cobros/banco` | ídem | Consulta por referencia bancaria: muestra el cobro asociado, su validez y la fecha límite de descuento por pronto pago. | `CobroService::buscarPorReferencia()` | Vista `cobros/banco` |
| `formularioCondonar` | `GET cobros/(:num)/condonar` | ídem | Formulario de condonación del recargo por mora, con el importe condonable y el total resultante. | `CobroService::vistaCondonacion()` | Vista `cobros/condonar` |
| `condonar` | `POST cobros/(:num)/condonar` | ídem | Aplica la condonación. Exige el campo `confirmar=1` y el número `version` del cobro para bloquear dobles envíos. La autorización depende del rol (`CondonacionRecargo::puedeAutorizar`). | `CobroService::condonar()`, `CondonacionRecargo` | Redirección |
| `condonaciones` | `GET cobros/condonaciones` | ídem | Bitácora de condonaciones autorizadas con filtros por fecha y autorizador. | `CorteCajaService::condonaciones()` | Vista `cobros/condonaciones` |
| `corte` | `GET cobros/corte` | ídem | Corte de caja por rango de fechas: totales por lugar de pago, por cobrador y por concepto; condonaciones del periodo. | `CorteCajaService::corte()` | Vista `cobros/corte` |
| `corteCsv` | `GET cobros/corte/csv` | ídem | Exporta el corte en CSV con BOM UTF-8 (para que Excel respete los acentos) y 14 columnas. | `CorteCajaService::corte()` | Descarga |

Mensajes al usuario:

| Situación | Mensaje |
|---|---|
| Cobro exitoso | «Cobro registrado. Recibo … por $…» |
| Cobro enviado a completar | «El cobro quedó pendiente por completar.» |
| Cobro cancelado | «Cobro cancelado.» |
| Condonación sin autorización | «Su cuenta no está autorizada para condonar recargos.» |

#### `DocumentosPagoController` (109 líneas)

| Método | Ruta | Acceso | Qué hace | Datos | Respuesta |
|---|---|---|---|---|---|
| `hojaPagoSolicitud` | `GET documentos/hoja-pago/solicitud/(:segment)` | `auth` | Hoja de pago imprimible de una solicitud (COB-05): concepto, monto, descuento o recargo aplicable, fecha límite, referencia bancaria y lugares de pago. | `HojaPagoService::paraSolicitud()`, `SolicitudModel` | Vista `documentos/hoja_pago` |
| `hojaPagoBoleta` | `GET documentos/hoja-pago/boleta/(:num)` | `auth` | Misma hoja para una boleta de infracción, con el desglose de faltas calificadas. | `HojaPagoService::paraBoleta()` | Vista `documentos/hoja_pago` |

El **permiso fino es de propiedad, no de rol**: la ruta solo exige sesión y el controlador comprueba que el ciudadano sea dueño del expediente; el juez puede imprimir cualquier hoja de boleta y el personal del panel cualquiera. Si no se cumple, redirige con «No tienes permiso para imprimir ese documento.» a la pantalla de inicio de la superficie correspondiente.

#### `ArchivosLocalesController` (50 líneas)

| Método | Ruta | Acceso | Qué hace | Datos | Respuesta |
|---|---|---|---|---|---|
| `servir` | `GET archivos/(:segment)` | público con firma | Sirve un archivo del almacenamiento local (respaldo de Supabase). Valida la firma HMAC-SHA256 del parámetro contra `SupabaseStorage::firmaLocal()` y la vigencia; rechaza rutas con `..` o barras. | `SupabaseStorage`, `writable/uploads/storage/` | Flujo binario con `Content-Type` real |

Es la única ruta que sirve archivos **sin** sesión, y por eso la firma es obligatoria: el enlace caduca (1 hora por defecto) y no se puede adivinar ni reutilizar en otro archivo. Detalle en §8.5.

#### `AgentesWebController` (46 líneas)

| Método | Ruta | Acceso | Qué hace | Datos | Respuesta |
|---|---|---|---|---|---|
| `shell` | `GET agentes` y `GET agentes/(:any)` | público | Devuelve el HTML contenedor de la app Expo compilada para web, con rutas de assets ajustadas para Vercel. No valida sesión: la autenticación ocurre dentro de la app contra `/api/agentes/login`. | Archivos de `public/agentes/` | HTML de la SPA |

Es el *fallback* del enrutador de la app de agentes: un enlace directo a `/agentes/login` o un F5 en cualquier ruta hija debe servir el mismo shell y dejar que `expo-router` resuelva la pantalla. Sin estas dos rutas, la recarga caía en el 404 de la plataforma.

### 4.2 `Admin/` (12 controladores)

Nota de acceso: todo el grupo `admin` exige `auth`; el subgrupo operativo exige `role:administrador,operador_ventanilla`; el subgrupo de bajas definitivas y permisos exige `role:administrador` a secas.

#### `Admin\AdminController` (712 líneas) — el controlador más grande del sistema

| Método | Ruta | Rol | Qué hace | Datos / servicios | Respuesta |
|---|---|---|---|---|---|
| `dashboard` | `GET admin/dashboard` | admin, operador | Conteos por estatus, desglose por trámite, alertas operativas y tiempos de atención; últimos 10 movimientos de historial. | `DesgloseSistema`, `SolicitudModel`, `HistorialEstatusModel` | Vista `admin/dashboard` |
| `index` | — | — | Redirige a `dashboard`; conserva la compatibilidad con enlaces antiguos. | — | Redirección |
| `auditoria` | `GET admin/auditoria` | admin, operador | Bitácora global con filtros por entidad, acción, identificador de entidad, usuario y rango de fechas. | `AuditoriaModel` | Vista `admin/auditoria` |
| `listaSolicitudes` | `GET admin/solicitudes` | admin, operador | Bandeja de expedientes, 20 por página, con 12 filtros: trámite, estatus, texto, origen, estado de pago, resuelto, mes, rango de fechas, fecha de campo, orden y dirección. | `SolicitudModel` | Vista `admin/solicitudes_lista` |
| `verSolicitud` | `GET admin/solicitudes/(:segment)` | admin, operador | Ficha completa del expediente: datos de formulario, documentos, historial de estatus, verificación física, acciones de seguimiento disponibles y cobro vigente. | `SolicitudModel`, `SolicitudDatoModel`, `DocumentoModel`, `HistorialEstatusModel`, `CobroModel`, `AccionesSeguimientoSolicitud` | Vista `admin/solicitudes_ver` |
| `cambiarEstatus` | `POST admin/solicitudes/cambiar-estatus/(:num)` | admin, operador | Cambia el estatus validando la transición contra `EstadoSolicitudService`. Solo se ofrece mediante acciones de seguimiento; los flujos con reglas propias (UR-02, UR-04, UR-05) tienen endpoint dedicado. | `EstadoSolicitudService`, `HistorialEstatusModel`, `CobroService` | Redirección |
| `registrarDictamenUr02` | `POST admin/solicitudes/dictamen-ur02/(:num)` | admin, operador | Registra el dictamen de la constancia de despintado. Exige que la solicitud esté en «Cita agendada» y observaciones no vacías; en transacción pasa a «Verificado» o «Rechazado». | `EstadoSolicitudService`, transacción | Redirección |
| `guardarEvaluacionUr04` | `POST admin/solicitudes/ur04/evaluacion/(:num)` | admin, operador | Captura la evaluación técnica del permiso eventual (11 campos) y avanza el expediente en su cadena de estados. | `SolicitudDatoModel`, `EstadoSolicitudService` | Redirección |
| `validarCierreCalleUr05` | `POST admin/solicitudes/ur05/validacion/(:num)` | admin, operador | Valida los 3 criterios del permiso de cierre de calle y, si procede, autoriza el pago (estado «Pago pendiente»). | `SolicitudDatoModel`, `EstadoSolicitudService` | Redirección |
| `descargarDocumento` | `GET admin/solicitudes/descargar-documento/(:num)` | admin, operador | Genera la URL firmada del documento y redirige a ella. La descarga nunca expone la ruta interna. | `DocumentoModel`, `SupabaseStorage` | Redirección a URL firmada |
| `validarDocumento` | `POST admin/solicitudes/validar-documento/(:num)` | admin, operador | Marca o desmarca un documento como validado y registra la acción en auditoría. | `DocumentoModel`, `AuditoriaModel` | Redirección |
| `formatos` | `GET admin/formatos` | admin, operador | Lista los formatos descargables del portal por trámite. | `FormatoTramiteModel` | Vista `admin/formatos` |
| `verFormato` | `GET admin/formatos/ver/(:segment)` | admin, operador | Abre un formato en el navegador mediante URL firmada. | `FormatoTramiteModel`, `SupabaseStorage` | Redirección |
| `subirFormato` | `POST admin/formatos/subir` | admin, operador | Carga un formato nuevo. Acepta PDF, JPG y PNG hasta 10 MB; bucket `formatos`. | `FormatoTramiteModel`, `SupabaseStorage` | Redirección |
| `eliminarFormato` | `POST admin/formatos/eliminar` | admin, operador | Da de baja lógica un formato (deja de ofrecerse en el portal) y lo registra en auditoría. | `FormatoTramiteModel`, `AuditoriaModel` | Redirección |

#### `Admin\SolicitudPresencialController` (617 líneas)

Alta de trámites en ventanilla para quien llega sin cuenta en el portal (COB-08 → presencial). Flujo de tres pasos.

| Método | Ruta | Rol | Qué hace | Datos / servicios | Respuesta |
|---|---|---|---|---|---|
| `index` | `GET admin/solicitudes/nueva` | admin, operador | Paso 1: identifica al ciudadano por CURP. | `CuentaCiudadanoService::buscarPorCurp()` | Vista `admin/solicitudes_presenciales/index` |
| `buscarCiudadano` | `POST admin/solicitudes/nueva/buscar-ciudadano` | admin, operador | Busca por CURP y avisa cuando hay **homonimia** (mismo nombre o nombre similar) antes de crear una cuenta nueva. | `CuentaCiudadanoService::buscarPorNombre()`, `nombreSimilar()` | Redirección al siguiente paso |
| `registrarCiudadano` | `POST admin/solicitudes/nueva/registrar-ciudadano` | admin, operador | Da de alta una cuenta sin acceso al portal (`crearSinAcceso`); el ciudadano podrá habilitar la contraseña después. | `CuentaCiudadanoService::crearSinAcceso()` | Vista `_alta_ciudadano` |
| `formulario` | `GET admin/solicitudes/nueva/(:segment)` | admin, operador | Paso 3: despliega el formulario del trámite elegido con los campos precargados del ciudadano. | `ConcesionModel`, `ConvocatoriaModel`, `TarifarioService` | Vista `admin/solicitudes_presenciales/formulario` |
| `guardar` | `POST admin/solicitudes/nueva/guardar` | admin, operador | Crea la solicitud, guarda los datos de formulario, sube documentos y deja el expediente en «Recibido». | `SolicitudModel`, `SolicitudDatoModel`, `HistorialEstatusModel`, `DocumentoUploader`, `AuditoriaModel` | Redirección a la ficha del expediente |

Catálogo de campos por trámite que define este controlador (constante `CAMPOS_TRAMITE`): T-01 con 6 campos, T-02 con los propios de la cita, T-03 con datos del vehículo, T-04 con datos de ruta, T-05 con datos del evento y del cierre, T-06 con datos del cedente y del cesionario, T-07 con 10 campos incluidos `num_camiones`, `periodo` (día, mes, semestre, año) y `es_mudanza`.

#### `Admin\AppTransitoController`

Administra la configuración de la app móvil y sus datos maestros.

| Método | Ruta | Rol | Qué hace | Datos / servicios | Respuesta |
|---|---|---|---|---|---|
| `index` | `GET admin/app-transito` | admin, operador | Panel de configuración: valor de la UMA vigente, fotos obligatorias, versión de app, intervalo de sincronización, municipio y modo estricto de fotos; lista de agentes. | `ParametroAppModel`, `AgenteTransitoModel` | Vista `admin/app_transito/index` |
| `guardarParametros` | `POST admin/app-transito/guardar-parametros` | admin, operador | Valida y guarda los 6 parámetros. | `ParametroAppModel::setValor()`, `AuditoriaModel` | Redirección |
| `guardarAgente` | `POST admin/app-transito/agentes/guardar` | admin, operador | Alta y edición de agentes de tránsito (placa, nombre, rol, sector, contraseña). | `AgenteTransitoModel`, `AuditoriaModel` | Redirección |
| `toggleEstadoAgente` | `POST admin/app-transito/agentes/toggle/(:num)` | admin, operador | Activa o desactiva un agente. Un agente inactivo no puede iniciar sesión en la app. | `AgenteTransitoModel` | Redirección |
| `eliminarAgente` | `POST admin/app-transito/agentes/eliminar/(:num)` | **solo admin** | Baja definitiva del agente. | `AgenteTransitoModel` | Redirección |
| `boletas` | `GET admin/app-transito/boletas` | admin, operador | Listado de boletas capturadas en campo, con filtros y semáforo de estatus. | `BoletaInfraccionModel`, `BoletaAccionesService::paraLista()` | Vista `admin/app_transito/boletas` |
| `editarBoleta` | `GET admin/app-transito/boletas/editar/(:num)` | admin, operador | Formulario de corrección de una boleta, habilitado solo si el estatus lo permite (`BoletaAccionesService::motivoNoEditable()`). | `BoletaInfraccionModel` | Vista `admin/app_transito/boleta_form` |
| `actualizarBoleta` | `POST admin/app-transito/boletas/actualizar/(:num)` | admin, operador | Guarda la corrección. **Ignora cualquier intento de cambiar el estatus desde el formulario**, que es campo protegido. | `BoletaInfraccionModel`, `BoletaAccionesService::bloquear()` | Redirección |
| `eliminarBoleta` | `POST admin/app-transito/boletas/eliminar/(:num)` | **solo admin** | Baja definitiva de la boleta. | `BoletaInfraccionModel` | Redirección |
| `catalogo` | `GET admin/app-transito/catalogo` | admin, operador | Catálogo de faltas que consume la app, con sus UMA mínima y máxima. | `CatalogoInfraccionAppModel` | Vista `admin/app_transito/catalogo` |
| `guardarCatalogo` | `POST admin/app-transito/catalogo/guardar` | admin, operador | Alta y edición de faltas del catálogo móvil. | `CatalogoInfraccionAppModel`, `AuditoriaModel` | Redirección |
| `eliminarCatalogo` | `POST admin/app-transito/catalogo/eliminar/(:num)` | **solo admin** | Baja definitiva de una falta. | `CatalogoInfraccionAppModel` | Redirección |

Constantes propias: `ESTADOS_BOLETA` (estados válidos de una boleta de campo) y `TIPOS_VEHICULO`. El valor por defecto de la UMA si no hay parámetro guardado es **117.31**.

#### `Admin\CatalogoTarifasController` y `Admin\CatalogoConcesionesController`

Ambos siguen el patrón CRUD de seis acciones sobre su catálogo.

| Método | Ruta (tarifas / concesiones) | Rol | Qué hace |
|---|---|---|---|
| `index` | `GET admin/tarifas` · `GET admin/concesiones` | admin, operador | Listado del catálogo con buscador |
| `formNuevo` | `GET admin/tarifas/nuevo` · `GET admin/concesiones/nuevo` | admin, operador | Formulario de alta |
| `guardar` | `POST admin/tarifas/guardar` · `POST admin/concesiones/guardar` | admin, operador | Inserta y audita |
| `formEditar` | `GET .../editar/(:num)` | admin, operador | Formulario de edición |
| `actualizar` | `POST .../actualizar/(:num)` | admin, operador | Actualiza y audita |
| `eliminar` | `POST .../eliminar/(:num)` | admin, operador | Baja **bloqueada si hay registros vinculados** |

Reglas de negocio de la baja:

| Catálogo | Comportamiento al eliminar |
|---|---|
| Tarifas | Se elimina sin afectar solicitudes ya procesadas, porque el monto quedó congelado en la solicitud al crearse. |
| Concesiones | Se **impide** si hay solicitudes vinculadas; el mensaje pide cambiar el estatus para dar de baja conservando el historial. |

#### `Admin\ConvocatoriasController` (409 líneas)

| Método | Ruta | Rol | Qué hace |
|---|---|---|---|
| `index` | `GET admin/convocatorias` | admin, operador | Listado de convocatorias UR-01 con su estatus y número de postulaciones |
| `formNuevo` / `formEditar` | `GET admin/convocatorias/nueva` · `GET admin/convocatorias/editar/(:num)` | admin, operador | Formulario de convocatoria, incluidas bases en archivo |
| `guardar` / `actualizar` | `POST admin/convocatorias/guardar` · `POST admin/convocatorias/actualizar/(:num)` | admin, operador | Alta y edición con carga opcional de bases a Supabase |
| `evaluacion` | `GET admin/convocatorias/(:num)/evaluacion` | admin, operador | Comparativa de postulaciones para seleccionar ganador |
| `seleccionar` | `POST admin/convocatorias/(:num)/seleccionar` | admin, operador | Marca al ganador y mueve su expediente a «Seleccionado»; usa `EstadoSolicitudService` |
| `bases` | `GET admin/convocatorias/(:num)/bases` | admin, operador | Descarga las bases mediante URL firmada |
| `eliminar` | `POST admin/convocatorias/eliminar/(:num)` | **solo admin** | Baja definitiva; **se bloquea si la convocatoria tiene postulaciones** |

Mensajes relevantes:

| Situación | Mensaje |
|---|---|
| Publicación | «Convocatoria #N publicada. Ya pueden postular expedientes en el portal ciudadano.» |
| Baja bloqueada | «La convocatoria #N tiene X postulación(es). No se puede eliminar: cámbiele el estatus a Anulada o Cerrada desde Editar.» |

#### `Admin\UsuariosController` (473 líneas)

| Método | Ruta | Rol | Qué hace |
|---|---|---|---|
| `index` | `GET admin/usuarios` | admin, operador | Listado de cuentas con buscador y filtro por rol |
| `formNuevo` / `formEditar` | `GET admin/usuarios/nuevo` · `GET admin/usuarios/editar/(:num)` | admin, operador | Formulario de cuenta |
| `guardar` | `POST admin/usuarios/guardar` | admin, operador | Alta de cuenta con validación de CURP y correo únicos |
| `actualizar` | `POST admin/usuarios/actualizar/(:num)` | admin, operador | Edición. Un operador **no puede editar cuentas internas** (administrador, operador, juez); esa restricción se valida en el controlador, no en la vista. |
| `toggleEstado` | `POST admin/usuarios/toggle/(:num)` | admin, operador | Activa o desactiva la cuenta |
| `eliminar` | `POST admin/usuarios/eliminar/(:num)` | **solo admin** | Baja definitiva |

Mensajes: «No fue posible crear la cuenta.», «Usuario no encontrado.», «Solo un administrador puede editar cuentas internas.»

#### `Admin\PermisosController` y `Admin\DesgloseController`

| Controlador | Método | Ruta | Rol | Qué hace |
|---|---|---|---|---|
| `PermisosController` | `index` | `GET admin/permisos-operador` | **solo admin** | Pinta los **14** interruptores del catálogo `PermisosOperador` con su descripción y el efecto de cada uno |
| `PermisosController` | `guardar` | `POST admin/permisos-operador/guardar` | **solo admin** | Guarda el mapa en `parametros_app['permisos_operador_ventanilla']` como JSON y reinicia la caché; el efecto es inmediato |
| `DesgloseController` | `index` | `GET admin/desglose` | **solo admin** | Desglose global del sistema: todas las métricas de `DesgloseSistema` con su navegación a la lista filtrada correspondiente |

Mensaje de permisos: «Los permisos del operador se guardaron y aplican de inmediato.»

#### Controladores de consulta simple

| Controlador | Método | Ruta | Qué muestra |
|---|---|---|---|
| `DocumentosController` | `index` | `GET admin/documentos` | Almacén de archivos subidos por los ciudadanos, con filtros por tipo, expediente y fecha; permite ubicar el expediente al que pertenece cada archivo |
| `VerificacionesController` | `index` | `GET admin/verificaciones` | Agenda de inspecciones físicas (`verificaciones_fisicas`) con la cita, su resultado y el dictamen de cada una |
| `SesionesAppController` | `index` | `GET admin/sesiones-app` | Sesiones de la app móvil: qué accesos están vigentes, vencidos o revocados, con placa del agente y fechas. **El token nunca se muestra**, solo su existencia y estado |

### 4.3 `Ciudadano/` (9 controladores)

Todo el grupo `portal` exige `auth`. Los controladores de trámite comprueban **propiedad del expediente** en cada acción: un ciudadano no puede operar sobre una solicitud ajena aunque conozca el folio.

#### `Ciudadano\PortalController` (302 líneas)

| Método | Ruta | Qué hace | Datos | Respuesta |
|---|---|---|---|---|
| `dashboard` | `GET portal` y `GET portal/dashboard` | Panel del ciudadano: total de solicitudes, en proceso, concluidas y pendientes de pago. Si el usuario es administrador u operador, **redirige a `/admin/dashboard`**. | `SolicitudModel` | Vista `portal/dashboard` |
| `tramites` | `GET portal/tramites` | Catálogo de trámites con su formato descargable y el estado de la bandera UR-TT-T-06. | `FormatoTramiteModel`, `FeatureFlags` | Vista `portal/tramites` |
| `misSolicitudes` | `GET portal/mis-solicitudes` | Listado propio, filtrado por `user_id` de sesión. | `SolicitudModel` | Vista `portal/mis_solicitudes` |
| `verSolicitud` | `GET portal/solicitud/(:segment)` | Ficha del expediente propio con documentos, historial, cobro vigente y acciones de seguimiento disponibles. Si no es suyo: «Solicitud no encontrada o acceso denegado.» | `SolicitudModel`, `DocumentoModel`, `SolicitudDatoModel`, `AuditoriaModel` | Vista `portal/ver_solicitud` |
| `descargarDocumento` | `GET portal/solicitud/(:segment)/descargar/(:num)` | Descarga un documento propio mediante URL firmada. | `DocumentoModel`, `SupabaseStorage` | Redirección |
| `miPerfil` | `GET portal/mi-perfil` | Formulario de datos personales. | `UserModel` | Vista `portal/mi_perfil` |
| `guardarPerfil` | `POST portal/mi-perfil` | Guarda el perfil. Validaciones: CURP de 18 posiciones, RFC con expresión regular, INE en JPEG o PNG hasta 5 MB, bucket `ine`. | `UserModel`, `SupabaseStorage` | Redirección |
| `descargarFormato` | `GET portal/formato/(:segment)` | Descarga el formato de un trámite desde el bucket `formatos`. | `FormatoTramiteModel`, `SupabaseStorage` | Redirección |

#### `Ciudadano\TramitesController` (240 líneas) — API interna del portal

| Método | Ruta | Rol | Qué hace |
|---|---|---|---|
| `crear` | `POST portal/tramites/solicitudes` | admin, operador, ciudadano | Crea una solicitud genérica y devuelve su folio |
| `consultar` | `GET portal/tramites/solicitudes/(:segment)` | admin, operador, ciudadano | Consulta una solicitud por folio con verificación de propiedad |
| `agendarVerificacion` | `POST portal/tramites/ur-02/solicitudes/(:num)/cita` | admin, operador, ciudadano | Agenda la cita de verificación (UR-02) y registra la fila en `verificaciones_fisicas` |
| `registrarResultado` | `POST portal/tramites/ur-02/solicitudes/(:num)/resultado` | admin, operador | Registra el resultado de la verificación |
| `listarConvocatoria` | `GET portal/tramites/ur-01/convocatorias/(:num)/solicitudes` | admin, operador | Lista las postulaciones de una convocatoria |
| `seleccionar` | `POST portal/tramites/ur-01/convocatorias/(:num)/seleccionar` | admin, operador | Marca al ganador de la convocatoria |

#### Los siete controladores de trámite

Cada trámite tiene su controlador porque cada uno tiene formulario, reglas de validación y resumen de pago propios. Lo que comparten está centralizado: `EstadoSolicitudService` para las transiciones, `TarifarioService` para el monto, `DocumentoUploader` para los archivos, `BanbajioMockGateway` para el pago en línea y `SupabaseStorage` para el almacenamiento.

| Controlador | Trámite | Rutas principales | Métodos propios | Servicios |
|---|---|---|---|---|
| `TramiteConcesionTransporteController` | UR-TT-T-01 | `GET portal/tramites/concesion-transporte`, `GET .../ur-01`, `POST .../concesion-transporte/guardar`, `POST .../ur-01/guardar` | `formulario`, `guardar` | `ConvocatoriaModel`, `EstadoSolicitudService`, `TarifarioService`, `DocumentoUploader` |
| `TramiteDespintadoController` | UR-TT-T-02 | `GET portal/tramites/constancia-despintado`, `GET .../ur-02`, `POST .../ur-02/guardar`, `GET .../ur-02/solicitud/(:any)/cita`, `POST .../ur-02/solicitud/(:any)/cita/guardar` | `formulario`, `guardar`, `agendarCitaForm`, `guardarCita` | `ConcesionModel`, `VerificacionFisicaModel`, `EstadoSolicitudService` |
| `TramiteOrdenPlaqueoController` | UR-TT-T-03 | `GET portal/tramites/orden-plaqueo`, `GET .../ur-03`, `POST .../ur-03/guardar` | `formulario`, `guardar` | `ConcesionModel`, `EstadoSolicitudService` |
| `TramitePermisoEventualController` | UR-TT-T-04 | `GET portal/tramites/permiso-eventual`, `POST .../guardar`, `GET .../resumen/(:any)`, `POST .../pagar/(:num)` | `formulario`, `guardar`, `resumen`, `pagar` | `EstadoSolicitudService`, `TarifarioService`, `DocumentoUploader` |
| `TramiteCierreCalleController` | UR-TT-T-05 | `GET portal/tramites/cierre-calle`, `POST .../guardar`, `GET .../resumen/(:any)`, `POST .../pagar/(:num)`, `GET .../permiso/(:any)` | `formulario`, `guardar`, `resumen`, `pagar`, `permiso` | `EstadoSolicitudService`, `TarifarioService`, `BanbajioMockGateway`, `SupabaseStorage` |
| `TramiteCesionConcesionController` | UR-TT-T-06 | `GET portal/tramites/cesion-concesion`, `POST .../guardar`, `GET .../validar-concesion/(:any)` | `formulario`, `guardar`, `validarConcesionAjax` | `ConcesionModel`, `EstadoSolicitudService`, `TarifarioService` |
| `TramiteCargaDescargaController` | UR-TT-T-07 | `GET portal/tramites/carga-descarga`, `POST .../guardar`, `GET|POST .../calcular-monto`, `GET .../resumen/(:any)`, `POST .../pagar/(:num)`, `GET .../(:any)/descargar/(:num)` | `formulario`, `calcularMontoAjax`, `guardar`, `resumen`, `pagar`, `descargarDocumento` | `EstadoSolicitudService`, `TarifarioService`, `BanbajioMockGateway`, `DocumentoUploader`, `SupabaseStorage` |

Comportamiento común de `pagar()`:

1. Verifica propiedad del expediente («Solicitud no encontrada o acceso denegado.»).
2. Comprueba que el expediente esté en un estado que autorice el pago («La solicitud aún no está autorizada para pago.»).
3. Invoca `BanbajioMockGateway`, que es un **simulador**: no hay cargo real.
4. Registra el movimiento en auditoría y avanza el estado.
5. Si el pago falla: «No fue posible procesar el pago simulado.»

`TramiteCargaDescargaController` calcula el monto en caliente: `calcularMontoAjax` responde JSON y devuelve «No hay tarifa configurada para esta combinación.» cuando no existe tarifa para la combinación de tipo de solicitante, periodo y número de camiones.

### 4.4 `Api/` (2 controladores)

| Controlador | Método | Rutas | Qué hace |
|---|---|---|---|
| `Api\AgentesTransitoApiController` | `login` | `POST` y `OPTIONS api/agentes/login` | Valida placa y contraseña del agente, verifica que esté activo y emite un token Bearer con vigencia. Devuelve los datos de perfil que la app guarda en almacenamiento seguro. |
| `Api\InfraccionesApiController` | `checklist` | `GET api/checklist-infracciones` | Devuelve las casillas del checklist agrupadas por categoría de boleta. |
| `Api\InfraccionesApiController` | `parametros` | `GET` y `OPTIONS api/parametros` | Devuelve los parámetros de operación de la app (UMA vigente, fotos obligatorias, versión, intervalo, municipio, modo estricto) con valores por defecto cuando falta la fila. |
| `Api\InfraccionesApiController` | `sincronizar` | `POST` y `OPTIONS api/infracciones/sincronizar`, `POST` y `OPTIONS api/boletas/sincronizar` | Recibe una boleta o un lote (upsert por folio), valida el lote completo antes de escribir y responde con el conteo de sincronizadas y los errores por folio. Contrato completo en §9. |
| `Api\InfraccionesApiController` | `subirEvidencia` | `POST` y `OPTIONS api/infracciones/evidencias` | Recibe las fotografías de una boleta y las almacena. |

Los dos endpoints de sincronización apuntan al mismo método con dos rutas distintas: `api/infracciones/sincronizar` es el nombre histórico y `api/boletas/sincronizar` el preferido por la app; se conservan ambos para no romper versiones instaladas en campo.

### 4.5 `Juez/` (1 controlador)

#### `Juez\DashboardController` (133 líneas)

Acceso: `auth` + `role:juez_calificador`. El juez **califica y cobra**; no administra catálogos ni usuarios.

| Método | Ruta | Qué hace | Datos / servicios | Respuesta |
|---|---|---|---|---|
| `index` | `GET juez` (alias `juez.inicio`) | Panel con el conteo de boletas pendientes de calificar y los últimos 10 cobros de multa pendientes. | `BoletaInfraccionModel`, `CobroModel` | Vista `juez/dashboard` |
| `boletas` | `GET juez/boletas` | Bandeja de boletas en estatus `recibida`, ordenadas por fecha de infracción. | `BoletaInfraccionModel` | Vista `juez/boletas` |
| `detalle` | `GET juez/boletas/(:num)` | Ficha de la boleta: datos del infractor y del vehículo, faltas capturadas, hechos, garantías y evidencias fotográficas. | `BoletaInfraccionModel` | Vista `juez/boleta_detalle` |
| `vistaPrevia` | `POST juez/boletas/(:num)/vista-previa` | Calcula en vivo el monto de la calificación con las UMA asignadas a cada falta, **sin persistir nada**. | `CalificarBoletaService::vistaPrevia()` | JSON |
| `calificar` | `POST juez/boletas/(:num)/calificar` | Persiste la calificación, genera el cobro pendiente correspondiente y cambia el estado de la boleta. | `CalificarBoletaService::calificar()`, `CobroService` | Redirección |

| Situación | Mensaje |
|---|---|
| Calificación correcta | «Boleta calificada. Se generó el cobro pendiente #N.» |
| Folio inexistente | «La boleta no existe.» |
| Boleta ya calificada o cancelada | «La boleta ya no está pendiente de calificación.» |

Los cobros y la caja del juez se sirven con `CobrosController` (§4.1) bajo el prefijo `/juez`, por lo que el comportamiento es idéntico al del panel administrativo salvo el layout.

---

## 5. Modelos

24 modelos. Todos extienden `CodeIgniter\Model`, declaran su tabla y su llave primaria, y definen `$allowedFields` de forma explícita: un campo que no esté en esa lista se descarta en silencio al insertar o actualizar. Esa es la primera línea de defensa contra la asignación masiva.

`$useTimestamps` marca si CI4 rellena automáticamente las columnas de creación y actualización.

### 5.1 Identidad y acceso

| Modelo | Tabla | PK | Timestamps | `$allowedFields` | Métodos propios |
|---|---|---|---|---|---|
| `UserModel` | `users` | `id` | sí | `username`, `email`, `password_hash`, `nombre_completo`, `curp`, `apellido`, `rfc`, `telefono`, `estado`, `ciudad`, `domicilio`, `ine_frente`, `ine_reverso`, `activo`, `reset_token`, `reset_expira` | `conRoles()` — devuelve los roles del usuario consultando `user_roles`; tolera sesiones antiguas que no los traen en sesión |
| `RoleModel` | `roles` | `id` | sí | `nombre`, `descripcion` | — |
| `AgenteTransitoModel` | `agentes_transito` | `id` | sí | `placa`, `nombre_completo`, `password_hash`, `rol`, `sector`, `activo` | Consultas de autenticación por placa |
| `AgenteTokenModel` | `agentes_transito_tokens` | `id` | sí | `agente_id`, `token_hash`, `expira_en`, `ultimo_uso_en`, `revocado_en` | Búsqueda por hash de token y por vigencia |

Notas de persistencia:

- `users` no guarda el rol: la relación vive en `user_roles`, y esto es deliberado para que un usuario pueda tener más de un rol. El filtro `RoleFilter` y la sesión trabajan con un arreglo de roles, no con un valor único.
- `password_hash` se llena siempre con `password_hash($x, PASSWORD_BCRYPT)`. Ningún modelo ni controlador compara contraseñas en claro.
- `agentes_transito_tokens.token_hash` guarda el **hash** del token, nunca el token. Por eso el panel de sesiones puede mostrar el estado de un acceso pero no el token: no existe en la base.
- `user_roles` no tiene modelo propio: se manipula con el constructor de consultas desde `UserModel` y `AuthController`.

### 5.2 Expediente y trámites

| Modelo | Tabla | PK | Timestamps | `$allowedFields` | Métodos propios |
|---|---|---|---|---|---|
| `SolicitudModel` | `solicitudes` | `id` | sí | `folio`, `tramite`, `ciudadano_id`, `concesion_id`, `convocatoria_id`, `estatus`, `monto`, `fecha_solicitud`, `fecha_resolucion`, `fecha_pago`, `fecha_vigencia_inicio`, `fecha_vigencia_fin`, `comentario_rechazo` | Consultas de bandeja con los 12 filtros del panel y agregados por estatus y trámite |
| `SolicitudDatoModel` | `solicitud_datos` | `id` | sí | `solicitud_id`, `clave`, `valor` | Lectura y escritura del formulario como pares clave-valor |
| `DocumentoModel` | `documentos` | `id` | sí | `solicitud_id`, `tipo_documento`, `nombre_original`, `ruta_interna`, `mime_type`, `tamano_bytes`, `hash_sha256`, `usuario_id`, `fecha_carga`, `validado` | Consultas por expediente y por tipo |
| `HistorialEstatusModel` | `historial_estatus` | `id` | sí | `solicitud_id`, `estatus_anterior`, `estatus_nuevo`, `usuario_id`, `fecha`, `comentario` | Bitácora por expediente, ordenada por fecha descendente |
| `VerificacionFisicaModel` | `verificaciones_fisicas` | `id` | sí | `solicitud_id`, `fecha_cita`, `resultado`, `observaciones` | Agenda por rango de fechas |
| `DocumentoModel` (auditoría de archivo) | — | — | — | — | `hash_sha256` guarda la huella del contenido al momento de subir, lo que permite detectar sustitución del archivo |

El diseño `clave`/`valor` de `solicitud_datos` es la razón por la que conviven siete trámites con formularios distintos en una sola tabla. Su costo es que la validación de campos obligatorios vive en el controlador de cada trámite y no en el esquema.

### 5.3 Catálogos

| Modelo | Tabla | PK | Timestamps | `$allowedFields` | Notas |
|---|---|---|---|---|---|
| `TarifaModel` | `tarifas` | `id` | sí | `tramite`, `criterio`, `monto`, `vigente_desde`, `vigente_hasta`, `descripcion`, `placeholder_oficial` | `placeholder_oficial` marca importes que aún no son la tarifa definitiva de la ficha oficial |
| `ConcesionModel` | `concesiones` | `id` | sí | `numero_titulo`, `titular_actual`, `vehiculo_placas`, `vehiculo_num_serie`, `tipo_persona`, `vigencia_inicio`, `vigencia_fin`, `estatus` | `tipo_persona` distingue física y moral |
| `ConvocatoriaModel` | `convocatorias` | `id` | sí | `titulo`, `fecha_publicacion`, `periodo_registro_inicio`, `periodo_registro_fin`, `bases`, `estatus`, `bases_archivo`, `bases_nombre`, `bases_mime`, `bases_tamano`, `created_by` | Las bases se guardan con sus metadatos de archivo para poder servirlas firmadas |
| `FormatoTramiteModel` | `formatos_tramite` | `id` | sí | `tramite`, `nombre`, `descripcion`, `nombre_archivo`, `ruta_interna`, `mime_type`, `tamano_bytes`, `usuario_id`, `activo` | `activo` es la baja lógica que oculta el formato del portal sin borrarlo |
| `CatalogoInfraccionModel` | `catalogo_infracciones` | `id` | sí | `fundamento_legal`, `descripcion`, `categoria_actor`, `monto_min_uma`, `monto_max_uma` | Catálogo de referencia del servidor |
| `CatalogoInfraccionAppModel` | `catalogo_infracciones_app` | `id` | sí | `fundamento_legal`, `descripcion`, `categoria`, `monto_min_uma`, `monto_max_uma`, `activo`, `orden` | Catálogo que consume la app; tiene `activo` y `orden` para publicar selectivamente |
| `BoletaChecklistItemModel` | `boleta_checklist_item` | `id` | sí | `categoria_boleta`, `etiqueta_casilla`, `catalogo_infraccion_id` | Casillas del checklist que la app agrupa por categoría |

Hay **dos** catálogos de infracciones a propósito. `catalogo_infracciones` es el catálogo normativo del servidor; `catalogo_infracciones_app` es la copia operativa que la app descarga y puede editarse desde el panel sin alterar el normativo. La app usa además un respaldo local compilado (`catalogoInfracciones.ts`) para funcionar sin red (§6 de la app móvil).

### 5.4 Cobros

| Modelo | Tabla | PK | Timestamps | `$allowedFields` | Métodos propios |
|---|---|---|---|---|---|
| `CobroModel` | `cobros` | `id` | sí | `folio`, `origen`, `solicitud_id`, `boleta_id`, `ciudadano_id`, `nombre_pagador`, `concepto`, `monto_base`, `descuento`, `recargo`, `condonado`, `total`, `detalle_calculo`, `estatus`, `pendiente_documentos`, `motivo`, `lugar_pago`, `referencia_pago`, `folio_recibo`, `referencia_bancaria`, `fecha_pago_banco`, `importe_pagado`, `cobrado_por`, `fecha_cobro`, `created_by` | `vigenteDeSolicitud()` — devuelve el cobro pendiente o aprobado de una solicitud, para no duplicarlo |
| `CobroMovimientoModel` | `cobro_movimientos` | `id` | sí | `cobro_id`, `accion`, `estatus_anterior`, `estatus_nuevo`, `motivo`, `detalle`, `usuario_id`, `fecha` | Historial por cobro |
| `BoletaInfraccionModel` | `boletas_infracciones` | `id` | sí | `folio`, `agente_placa`, `agente_nombre`, `fecha_infraccion`, `hora_infraccion`, `lugar`, `latitud`, `longitud`, `conductor_ausente`, `infractor_nombre`, `infractor_domicilio`, `infractor_licencia`, `vehiculo_placas`, `sin_placas`, `vehiculo_marca`, `vehiculo_linea`, `vehiculo_color`, `vehiculo_tipo`, `falta_fundamento_legal`, `falta_descripcion`, `falta_categoria`, `falta_monto_min_uma`, `falta_monto_max_uma`, `faltas_json`, `hechos`, `garantias_retenidas`, `inventario_grua`, `foto_placa_url`, `foto_contexto_url`, `foto_documento_url`, `estado`, `sincronizado_en`, `creado_en_dispositivo` | Bandeja del juez por estado; validación de propiedad por placa de agente |
| `BoletaCalificacionModel` | `boleta_calificaciones` | `id` | sí | `boleta_id`, `juez_id`, `cobro_id`, `nombre_infractor`, `valor_uma`, `total_uma`, `monto_base`, `calificada_en` | Una calificación por boleta |
| `BoletaCalificacionDetalleModel` | `boleta_calificacion_detalles` | `id` | no | `calificacion_id`, `orden`, `falta_id_origen`, `fundamento_legal`, `descripcion`, `categoria`, `monto_min_uma`, `monto_max_uma`, `uma_asignada`, `subtotal_pesos` | Un renglón por falta calificada |

Detalle importante de `boletas_infracciones`: convive con dos esquemas de captura. Las boletas antiguas guardan una sola falta en las columnas `falta_*`; las nuevas guardan el arreglo completo en `faltas_json`. El sistema lee ambos: si `faltas_json` trae contenido, manda sobre las columnas sueltas. `BoletaCalificacionDetalleModel` conserva **copiados** el fundamento legal, la descripción y los mínimos y máximos de UMA al momento de calificar, de modo que una edición posterior del catálogo no altera el historial de una boleta ya calificada.

### 5.5 Plataforma y configuración

| Modelo | Tabla | PK | Timestamps | `$allowedFields` | Notas |
|---|---|---|---|---|---|
| `ParametroAppModel` | `parametros_app` | `id` | sí | `clave`, `valor`, `descripcion`, `tipo`, `editable` | Métodos `getValor($clave, $default)` y `setValor($clave, $valor)`; es el almacén de los parámetros de la app y del mapa de permisos del operador |
| `ParametroSistemaModel` | `parametros_sistema` | `id` | no | `clave`, `valor`, `descripcion`, `actualizado_en` | Parámetros del sistema no editables desde el panel (UMA base, umbrales técnicos) |
| `AuditoriaModel` | `auditoria` | `id` | no | `entidad`, `entidad_id`, `accion`, `usuario_id`, `fecha`, `detalle`, `registro_borrados` | Método `registrar($entidad, $entidadId, $accion, $usuarioId, $detalle)`; se llama desde casi todos los controladores |

`auditoria` tiene `$useTimestamps = false` porque maneja su propia columna `fecha`. `registro_borrados` guarda el volcado del registro antes de una baja definitiva: es lo que permite reconstruir qué se eliminó y quién lo eliminó.

---

## 6. Librerías de servicio

22 librerías. Aquí vive la lógica de negocio que no pertenece a una sola pantalla. Las cinco que un mantenimiento debe entender primero son `CobroService`, `DesgloseSistema`, `EstadoSolicitudService`, `PermisosOperador` y `SupabaseStorage`.

### 6.1 `CobroService` (657 líneas) — núcleo del módulo de cobros

Gestiona el ciclo completo de un cobro, tanto de trámites como de multas.

**Estados y transiciones**

```
pendiente ──cobrar──► aprobado
    │
    ├─ mandar a completar (sigue pendiente, con motivo)
    └─ cancelar ──► cancelado
```

| Constante | Valor | Significado |
|---|---|---|
| `PENDIENTE` | `pendiente` | Cobro creado, aún no pagado |
| `APROBADO` | `aprobado` | Cobro pagado y registrado |
| `CANCELADO` | `cancelado` | Cobro anulado con motivo |

**Lugares de pago** (`LUGARES_PAGO`): `juez_calificador` (Juez calificador), `tesoreria` (Tesorería Municipal), `banbajoi`→`banbajio` (BanBajío), `ventanilla` (Ventanilla), `en_linea` (Pago en línea).

**Métodos**

| Método | Qué hace | Reglas de negocio |
|---|---|---|
| `crearDesdeSolicitud($solicitudId, $usuarioId)` | Crea el cobro pendiente de una solicitud | Si ya existe un cobro vigente (pendiente o aprobado) **devuelve ese** en lugar de duplicarlo. Lanza `CobroException` con «La solicitud no existe.» si el expediente no está. |
| `crearDesdeBoleta(...)` | Crea el cobro de una multa con el monto que fijó el juez | Lo invoca `CalificarBoletaService`. |
| `marcarCobrado($cobroId, ...)` | Cobra en ventanilla, tesorería, juez o en línea | **Recalcula** descuento y recargo al momento del cobro, no al de la creación. |
| `registrarPagoBancario(...)` | Cobra un pago hecho en BanBajío | Requiere referencia bancaria e importe pagado; guarda la fecha del banco. |
| `buscarPorReferencia($referencia)` | Localiza un cobro por su referencia bancaria | Devuelve también la validez y el límite de pronto pago. |
| `mandarACompletar($cobroId, $motivo)` | Marca `pendiente_documentos = 1` con motivo | **No cambia el estatus**: el cobro sigue pendiente. |
| `cancelar($cobroId, $motivo)` | Cancela el cobro | El motivo es obligatorio. Si viene de una solicitud, **no** cambia el estatus de la solicitud. |
| `vistaCondonacion($cobroId)` | Prepara el formulario de condonación | Devuelve el importe condonable y el total resultante. |
| `condonar($cobroId, $importe, $usuarioId, $version)` | Condona el recargo por mora | Exige autorización por rol; usa el campo `version` para bloquear dobles envíos. |
| `sincronizarConSolicitud($solicitudId)` | Alinea el cobro con el estado del expediente | Lo llama `EstadoSolicitudService` cuando la solicitud llega a «Pago pendiente». |
| `crearPendientesFaltantes()` | Genera cobros para solicitudes en «Pago pendiente» que no tienen uno | Se usó para regularizar datos históricos; es idempotente. |
| `historial($cobroId)` | Devuelve los movimientos del cobro | — |

**Reglas transversales**

- Un trámite genera cobro **solo** cuando la solicitud llega a «Pago pendiente».
- Al cobrar, la solicitud pasa a «Pagado»; en UR-TT-T-06 pasa a «Concluido».
- Si la solicitud se rechaza, el cobro pendiente se cancela.
- Los folios se generan con `FolioGenerator`: `COB-AAAA-NNNNNN` para el cobro y `REC-AAAA-NNNNNN` para el recibo.

### 6.2 `DesgloseSistema` (1,038 líneas) — catálogo de métricas navegables

Produce todas las métricas del dashboard administrativo y del desglose global. Cada método devuelve un arreglo listo para pintar, con el conteo y el enlace a la lista ya filtrada.

| Método | Métrica que produce |
|---|---|
| `kpis()` | Indicadores principales del sistema |
| `porEstatus()` | Distribución de expedientes por estatus |
| `porTramite()` | Distribución por trámite (T-01 a T-07) |
| `porCanal()` | Origen: portal frente a ventanilla |
| `serieMensual()` | Serie de altas por mes |
| `flujoEstatus()` | Flujo de transiciones observadas |
| `tiempos()` | Tiempos de atención por etapa |
| `vigencias()` | Permisos y títulos vigentes frente a vencidos |
| `documentos()` | Documentos recibidos y validados |
| `cuentas()` | Cuentas de ciudadano activas |
| `auditoria()` | Volumen de acciones registradas |
| `appTransito()` | Boletas de campo, agentes activos y sesiones |
| `verificaciones()` | Agenda de inspecciones y resultados |
| `catalogos()` | Estado de los catálogos (tarifas, concesiones, convocatorias) |
| `alertas()` | Alertas operativas: expedientes detenidos, cobros pendientes antiguos, citas vencidas |
| `tramitesActivos()` | Trámites con expedientes en curso |

Esta librería es la que permite que `/admin/desglose` sea una pantalla de una sola ruta con navegación interna: no hay un endpoint por métrica, sino un catálogo que se recorre en la vista.

### 6.3 `ResolverMontoInfraccionService` (333 líneas) — cálculo de multas

Resuelve el monto de una infracción y aplica descuento por pronto pago o recargo por mora.

**Parámetros configurables** (se leen de la base con valor por defecto si no existen)

| Clave | Valor por defecto | Efecto |
|---|---|---|
| `porcentaje_descuento_pronto_pago` | `40.00` | Descuento aplicable dentro del plazo |
| `dias_habiles_descuento` | `10` | Días hábiles de plazo para el descuento |
| `porcentaje_recargo_mensual` | `5.00` | Recargo por cada mes de mora |

**Reglas de cálculo** (método `aplicarDescuentoRecargo`)

1. La fecha de pago se resuelve **siempre contra el servidor** cuando viene nula; nunca se confía en la fecha del dispositivo.
2. Se cuentan los **días hábiles** de lunes a viernes entre la infracción y el pago, empezando a contar el día hábil siguiente a la infracción.
3. Se decide el caso:

| Caso | Condición | Cálculo | `tipo_ajuste` |
|---|---|---|---|
| A | No ha pasado más de un mes **y** han transcurrido 10 días hábiles o menos | Descuento de `monto_base × 40 %` | `descuento_pronto_pago` |
| C | Ha pasado más de un mes | Recargo de `monto_base × 5 % × meses` (mínimo un mes) | `recargo_mora` |
| B | Cualquier otro caso | Sin ajuste | `monto_regular` |

El arreglo de salida incluye `monto_base`, `dias_habiles_transcurridos`, `dias_habiles_limite`, `tipo_ajuste`, `aplica_descuento`, `porcentaje_descuento`, `monto_descuento`, `aplica_recargo`, `meses_mora`, `porcentaje_recargo_mensual`, `monto_recargo` y `monto_final`. Ese arreglo completo se guarda en `cobros.detalle_calculo`, por lo que cualquier cobro puede explicarse meses después sin recalcular.

Otros métodos: `calcularMonto($infraccionId)`, `calcularDesglose($infraccionId)`, `calcularCalificacion($faltas, $seleccionesUma)`, `calcularMontoConDescuentoRecargo()`, `calcularMontoConDescuentoRecargoDetalle()` y `contarDiasHabiles()`.

### 6.4 `EstadoSolicitudService` (387 líneas) — máquina de estados

Es la autoridad única sobre qué transición es legal. Cualquier cambio de estatus que no pase por aquí es un error de implementación.

**Métodos**

| Método | Qué hace |
|---|---|
| `transicionValida($tramite, $actual, $nuevo)` | Devuelve verdadero o falso; si el trámite no existe en el catálogo, **devuelve falso** |
| `cambiarEstatus(...)` | Aplica la transición, escribe en `historial_estatus` y sincroniza el cobro |
| `autorizarPagoUr05(...)` | Autoriza el pago del permiso de cierre de calle |
| `calcularVigenciaT07(...)` | Calcula las fechas de vigencia del permiso de carga y descarga |

**Catálogos de estatus**

- `ESTATUS_UR_TT_T_01` (9 estados), `ESTATUS_UR_TT_T_02` (7), `ESTATUS_UR_TT_T_03` (4), `ESTATUS_UR_TT_T_04` (14), `ESTATUS_UR_TT_T_05` (6), `ESTATUS_UR_TT_T_06` (7), más el catálogo base del T-07.
- `ESTATUS_MAESTRO`: **28 estatus** distintos en todo el sistema. Es la lista que valida el panel al filtrar.

**Máquinas de transición** (`TRANSICIONES_VALIDAS`)

| Trámite | Secuencia principal |
|---|---|
| UR-TT-T-01 | Recibido → En revisión → Evaluación comparativa → Seleccionado → Pago pendiente → Pagado → Título emitido |
| UR-TT-T-02 | Recibido → Cita agendada → Verificado → Pago pendiente → Pagado → Constancia emitida |
| UR-TT-T-03 | Recibido → En revisión → Aprobado |
| UR-TT-T-04 | Recibido → En revisión documental → Documentos completos → En estudio técnico → Dictamen favorable → Pendiente de inspección → Pendiente de revista mecánica → Seguro pendiente de validación → Autorizado para pago → Pago pendiente → Pagado → Permiso emitido |
| UR-TT-T-05 | Recibido → En validación → Pago pendiente → Pagado → Permiso emitido |
| UR-TT-T-06 | Recibido → En revisión documental → Dictaminado aprobado → Pago pendiente → Concluido |
| UR-TT-T-07 | Recibido → Pago pendiente → Pagado → Permiso emitido → Vigente → Vencido |

Reglas adicionales: `Prevención` en T-04 y T-06 es un estado **regresable** a «En revisión documental», porque el ciudadano puede subsanar; «Rechazado» y los estados terminales no tienen salida; «No seleccionado» (T-01) es terminal y no genera cobro.

### 6.5 `PermisosOperador` (231 líneas) — permisos configurables

Define qué secciones del panel puede usar el rol `operador_ventanilla`. El administrador los ajusta desde `/admin/permisos-operador`; se guardan como JSON en `parametros_app` bajo la clave `permisos_operador_ventanilla`, **sin migración**, reutilizando una tabla existente.

**Catálogo: 14 permisos.** Cada entrada tiene nombre, descripción, icono y los prefijos de URI que protege.

| # | Clave | Nombre en pantalla | Prefijo de URI protegido |
|---|---|---|---|
| 1 | `solicitudes` | Solicitudes y trámites | `admin/solicitudes` |
| 2 | `cobros` | Cobros | `admin/cobros` |
| 3 | `usuarios` | Usuarios y ciudadanos | `admin/usuarios` |
| 4 | `tarifas` | Tarifario | `admin/tarifas` |
| 5 | `concesiones` | Concesiones | `admin/concesiones` |
| 6 | `formatos` | Formatos de trámite | `admin/formatos` |
| 7 | `auditoria` | Auditoría | `admin/auditoria` |
| 8 | `documentos` | Documentos recibidos | `admin/documentos` |
| 9 | `verificaciones` | Verificaciones físicas | `admin/verificaciones` |
| 10 | `convocatorias` | Convocatorias UR-01 | `admin/convocatorias` |
| 11 | `app_transito_config` | App Tránsito: configuración y agentes | `admin/app-transito` |
| 12 | `app_transito_boletas` | App Tránsito: boletas de campo | `admin/app-transito/boletas` |
| 13 | `app_transito_catalogo` | App Tránsito: catálogo de faltas | `admin/app-transito/catalogo` |
| 14 | `app_transito_sesiones` | App Tránsito: sesiones de acceso | `admin/sesiones-app` |

**Invariantes de diseño** (los cuatro son deliberados y no deben cambiarse sin revisar la seguridad)

1. **Sin fila guardada = todo permitido.** Es el comportamiento histórico de la ventanilla; un despliegue nuevo no deja al operador sin acceso.
2. **El administrador nunca se filtra.** Comprueba el rol antes que el permiso y devuelve paso libre.
3. **Las bajas definitivas no son configurables.** Eliminar usuarios, agentes, boletas, faltas del catálogo o convocatorias sigue siendo exclusivo del administrador por ruta (`role:administrador`), al margen de lo que diga el permiso.
4. **Caché por petición.** En serverless no hay proceso que conserve estado, así que la caché es estática y vive lo que dura la petición; `reiniciarCache()` existe para las pruebas.

La resolución de permisos usa **el prefijo más largo**: `admin/app-transito/boletas` se resuelve con `app_transito_boletas` y no con `app_transito_config`, aunque el segundo también encaje como prefijo.

Métodos: `definiciones()`, `claves()`, `todos()`, `activo($clave)`, `guardar($mapa)`, `requeridoParaUri($uri)`, `reiniciarCache()`.

### 6.6 `SupabaseStorage` (363 líneas) — almacenamiento dual

Almacena y recupera archivos a través de la API REST de Supabase Storage, con respaldo en disco local cuando Supabase no está configurado o falla.

| Método | Qué hace |
|---|---|
| `subir($bucket, $path, $contenido, $mime, $upsert)` | Sube contenido en memoria |
| `subirArchivo($bucket, $path, $file, $mime, $upsert)` | Sube un archivo subido por formulario |
| `eliminar($bucket, $paths)` | Elimina uno o varios objetos |
| `urlFirmada($bucket, $path, $expiraSegundos = 3600)` | URL temporal; **1 hora** por defecto |
| `urlPublica($bucket, $path)` | URL pública (solo para buckets públicos) |
| `descargar($bucket, $path)` | Devuelve el contenido |
| `listar($bucket, $prefix, $limite, $offset)` | Lista objetos |
| `existe($bucket, $path)` | Comprueba existencia |
| `crearBucket($nombre, $publico, $tamanoMaximo, $mimeTypes)` | Crea un bucket con límites |
| `listarBuckets()` | Lista buckets |
| `esLocal()` | Indica si el almacenamiento activo es el local |
| `rutaArchivoLocal($bucket, $path)` | Ruta absoluta del archivo local |
| `firmaLocal($bucket, $path, $expira)` | HMAC-SHA256 de `bucket|path|expira` con el secreto de la aplicación |

**Buckets locales** (constante `BUCKETS_LOCALES`): `documentos`, `formatos`, `ine`.

**Reglas de seguridad del almacenamiento local**

- La URL firmada incluye el vencimiento; el controlador `ArchivosLocalesController` recalcula el HMAC y lo compara.
- Se rechazan rutas con `..` o barras para impedir el recorrido de directorios.
- El fallback local escribe bajo `writable/uploads/storage/{bucket}/`. Es una red de seguridad operativa, **no** un modo pensado para producción multi-instancia.

### 6.7 `CorteCajaService` (229 líneas) — cierre de caja

| Método | Qué produce |
|---|---|
| `corte($desde, $hasta)` | Totales por lugar de pago, por cobrador y por concepto, más el detalle de cada cobro del periodo |
| `condonaciones($desde, $hasta)` | Bitácora de condonaciones con autorizador e importe |
| `autorizadoresCondonacion()` | Usuarios con facultad de condonar |
| `cobradores()` | Usuarios que registraron cobros en el periodo |

El corte alimenta la vista de caja y el CSV de 14 columnas. El CSV lleva **BOM UTF-8** para que Excel en Windows respete los acentos; sin él, «Dirección» se ve como «DirecciÃ³n».

### 6.8 `CondonacionRecargo` (96 líneas) — condonación del recargo

| Método | Qué hace |
|---|---|
| `puedeAutorizar($usuarioId)` | Indica si el usuario tiene facultad de condonar |
| `rolesPermitidos($roles)` | Verifica los roles contra el catálogo permitido |
| `importeACentavos($texto)` | Convierte texto de formulario a centavos |
| `aCentavos($pesos)` / `aPesos($centavos)` | Conversión con redondeo controlado |
| `aplicado($autorizado, $recargo)` | Importe efectivamente condonado |
| `total($calculo, $autorizado)` | Total a pagar después de la condonación |

**Todo el cálculo monetario se hace en centavos enteros** y solo se convierte a pesos al presentar. Es la decisión que evita los errores de redondeo de punto flotante en un módulo que maneja dinero.

### 6.9 `HojaPagoService` (312 líneas) — hoja de pago imprimible

| Método | Qué produce |
|---|---|
| `paraSolicitud($solicitud)` | Hoja de pago de un trámite: concepto, monto base, ajuste, total, fecha límite de descuento, referencia bancaria y lugares de pago |
| `paraBoleta($boleta)` | Hoja de pago de una multa, con el desglose de faltas calificadas |
| `fechaLimiteDescuento($fechaInfraccion)` | Fecha límite del pronto pago |
| `lugaresDePago()` | Lugares con su texto para el ciudadano |

### 6.10 `CalificarBoletaService` (158 líneas) y `BoletaAccionesService` (119 líneas)

| Servicio | Método | Qué hace |
|---|---|---|
| `CalificarBoletaService` | `faltasDeBoleta($boleta)` | Normaliza las faltas: si hay `faltas_json`, manda ese arreglo; si no, construye la falta única desde las columnas `falta_*` |
| `CalificarBoletaService` | `vistaPrevia($boleta, $selecciones)` | Calcula el monto sin persistir |
| `CalificarBoletaService` | `calificar($boleta, $selecciones, $juezId)` | Persiste la calificación y sus renglones, crea el cobro y actualiza la boleta |
| `BoletaAccionesService` | `motivoNoEditable($boleta)` | Devuelve el motivo por el que una boleta no puede editarse, o `null` si sí puede |
| `BoletaAccionesService` | `bloquear($datos)` | Elimina campos protegidos antes de guardar (`estado`, `estatus`, `status`, `state`, `estado_boleta`) |
| `BoletaAccionesService` | `paraLista($boleta)` | Prepara las acciones válidas para pintar el listado |

`BoletaAccionesService::bloquear()` es una defensa concreta: aunque el formulario de edición se manipule a mano para incluir un campo `estado`, el controlador descarta esas claves antes de llegar al modelo. Sin ella, el panel podría «calificar» una boleta sin pasar por el juez.

### 6.11 `CuentaCiudadanoService` (275 líneas) y `AgenteTokenService` (97 líneas)

| Servicio | Método | Qué hace |
|---|---|---|
| `CuentaCiudadanoService` | `buscarPorCurp($curp)` | Localiza una cuenta por CURP |
| `CuentaCiudadanoService` | `buscarPorNombre($nombre)` / `nombreSimilar($a, $b)` | Detección de homonimia antes de crear una cuenta nueva |
| `CuentaCiudadanoService` | `crear($datos)` | Alta con acceso al portal y contraseña elegida por el ciudadano |
| `CuentaCiudadanoService` | `crearSinAcceso($datos)` | Alta en ventanilla sin contraseña: el ciudadano la habilita después |
| `CuentaCiudadanoService` | `habilitarAcceso($id)` | Habilita el acceso al portal de una cuenta creada en ventanilla |
| `CuentaCiudadanoService` | `generarPassword()` | Genera la contraseña temporal |
| `AgenteTokenService` | `emitir($agente)` | Emite un token Bearer, guarda su hash y devuelve el token **una sola vez** |
| `AgenteTokenService` | `agenteDeSolicitud($request)` | Recupera el agente autenticado a partir del encabezado `Authorization` |

La diferencia entre `crear` y `crearSinAcceso` es funcional, no cosmética: permite que una persona sin correo ni acceso a internet sea atendida en ventanilla y, años después, reclame su expediente sin que el personal tenga que recrear los datos.

### 6.12 `DocumentoUploader` (107 líneas) y `TarifarioService` (72 líneas)

| Servicio | Método | Qué hace |
|---|---|---|
| `DocumentoUploader` | `subir($file, $solicitudId, $tipo, $usuarioId)` | Valida tipo y tamaño, calcula el hash SHA-256, genera un nombre UUID y guarda en el bucket correspondiente |
| `DocumentoUploader` | `obtenerExtensionPorMime($mime)` | Traduce el MIME a extensión; no confía en el nombre del archivo |
| `DocumentoUploader` | `generarUuidCompleto()` | Genera el identificador del archivo |
| `TarifarioService` | `calcularMontoUrTtT07($tipoSolicitante, $periodo, $numCamiones)` | Monto del permiso de carga y descarga: multiplica la tarifa del periodo por el número de camiones (mínimo 1) |
| `TarifarioService` | `calcularMontoUrTtT06()` | Monto de la cesión de concesión |
| `TarifarioService` | `calcularMonto($tramite, $criterio)` | Monto por trámite y criterio |
| `TarifarioService` | `esPlaceholder($tramite, $criterio)` | Indica si el importe es provisional y no la tarifa definitiva de la ficha oficial |
| `TarifarioService` | `esPlaceholderT07(...)` / `esPlaceholderT06()` | Atajos de la comprobación anterior |

`esPlaceholder` existe porque algunas tarifas aún se publican con el importe de la ficha oficial sin desglose definitivo. Cuando devuelve verdadero, el portal advierte al ciudadano de que el monto puede ajustarse antes del pago.

### 6.13 `AccionesSeguimientoSolicitud` (113 líneas) y `ReferenciaBancariaProvisional` (59 líneas)

| Servicio | Método | Qué hace |
|---|---|---|
| `AccionesSeguimientoSolicitud` | `genericas($tramite, $estatusActual)` | Devuelve las acciones que el panel puede ofrecer en ese estado |
| `AccionesSeguimientoSolicitud` | `permiteEndpointGenerico($tramite, $estatus, $destino)` | Decide si el cambio puede hacerse por el endpoint genérico o exige uno especializado |
| `AccionesSeguimientoSolicitud` | `descriptor($destino)` | Etiqueta, icono y confirmación de una acción |
| `AccionesSeguimientoSolicitud` | `requiereFlujoEspecializado($tramite, $estatus)` | Verdadero cuando el trámite tiene reglas propias en ese estado |
| `AccionesSeguimientoSolicitud` | `requiereRegistroCobro($tramite, $estatus)` | Verdadero cuando la transición genera cobro |
| `ReferenciaBancariaProvisional` | `generar()` | Genera la referencia que se imprime en la hoja de pago |
| `ReferenciaBancariaProvisional` | `esValida($referencia)` | Valida el formato |
| `ReferenciaBancariaProvisional` | `normalizar($referencia)` | Limpia espacios y guiones antes de comparar |

Esta librería es la razón por la que el panel no ofrece un botón que el backend vaya a rechazar: la vista pinta solo las acciones que `genericas()` autoriza para ese estado, y `permiteEndpointGenerico()` impide que un formulario manipulado salte un flujo con reglas propias.

### 6.14 `FeatureFlags` (21 líneas), `FolioGenerator` (16 líneas) y las excepciones

| Clase | Contenido |
|---|---|
| `FeatureFlags` | `habilitado($flag, $default)` lee `APP_ENABLE_{$flag}` y acepta `true`, `1` o `yes`; `habilitarUrTtT06()` atajo de la bandera de cesión |
| `FolioGenerator` | `generar()` produce los folios de solicitud con formato uniforme y año en curso |
| `CobroException` | Excepción de negocio del módulo de cobros; su mensaje se muestra al usuario tal cual |
| `CalificacionBoletaException` | Excepción de negocio de la calificación de boletas |

Las dos excepciones existen para separar el error de negocio (que se muestra al usuario en lenguaje natural) del error técnico (que se registra en el log y no se muestra). El controlador captura la primera y deja propagar la segunda.

### 6.15 `BanbajioMockGateway` (33 líneas)

Implementa `PaymentGatewayInterface` con `crearCargo`, `confirmarPago` y `reembolsar`. **Es un simulador**: no contacta a BanBajío ni mueve dinero. Su razón de existir es permitir que el flujo de pago en línea esté completo y probado mientras la Tesorería Municipal formaliza la integración real; al sustituirlo por la implementación definitiva, los controladores de trámite no cambian.

---

## 7. Filtros y control de acceso (RBAC)

### 7.1 Los cinco filtros

| Filtro | Archivo | Cuándo interviene | Qué hace exactamente | Respuesta al fallar |
|---|---|---|---|---|
| `auth` | `AuthFilter.php` | Rutas que exigen sesión | Comprueba que `session('user_id')` exista. No valida rol ni permisos. | Aviso «Acceso denegado: debes iniciar sesión» y redirección a `/auth/login` |
| `role` | `RoleFilter.php` | Rutas con `role:rol1,rol2` | Carga los roles del usuario (desde la sesión o, si faltan, desde `UserModel::conRoles()`), compara con los argumentos y deja pasar si coincide al menos uno. Basta un rol coincidente. | Aviso «No tienes permiso para acceder a esta sección» y redirección a `/` |
| `permisoOperador` | `PermisoOperadorFilter.php` | Todo `admin` y `admin/*` (aplicación masiva) | Solo actúa sobre `operador_ventanilla`. El administrador pasa sin evaluar. Resuelve el permiso del catálogo por prefijo más largo de URI y comprueba que esté activo. Si no hay sesión o el rol no es ventanilla, **deja pasar**: el filtro `auth` o `role` de la ruta ya resolvió ese caso. | Aviso «No tienes permiso para usar "Nombre de la sección". Pide al administrador habilitarlo en Permisos del Operador.» y redirección a `/admin/dashboard` con 302 |
| `adminRedirect` | `AdminRedirectFilter.php` | Rutas del portal (aplicación masiva) | Si el usuario es administrador u operador, lo manda a `/admin/dashboard`; si es juez, a `/juez`. Evita que el personal interno vea el portal del ciudadano con una sesión privilegiada. | Redirección |
| `agenteToken` | `AgenteTokenFilter.php` | Endpoints de escritura de la app móvil | Exige encabezado `Authorization: Bearer {token}` y valida el token de agente activo con `AgenteTokenService::agenteDeSolicitud()`. **Deja pasar los `OPTIONS`** porque el preflight CORS no lleva credenciales. | 401 con `WWW-Authenticate: Bearer` y cuerpo `{success:false, message:"Sesión no válida o expirada. Inicia sesión nuevamente."}` |

Orden de intervención efectivo: filtros globales → filtro masivo de la familia de ruta → filtros declarados en la ruta. Consecuencia: en `/admin/*` el filtro `permisoOperador` corre **antes** que el `role` declarado en la ruta, y por eso está escrito para no decidir cuando el rol no es de ventanilla.

### 7.2 Matriz ruta × rol

| Familia de rutas | Ciudadano | Operador de ventanilla | Administrador | Juez calificador | Agente (token) |
|---|---|---|---|---|---|
| `/`, `/auth/*`, `/agentes*`, `/archivos/*` | accede | accede | accede | accede | — |
| `/portal/*` | accede (solo lo propio) | redirigido al panel | redirigido al panel | redirigido a `/juez` | — |
| `/portal/tramites/ur-01/*`, `ur-02/.../resultado` | no | accede | accede | no | — |
| `/admin` (operativo) | no | accede si tiene el permiso | accede siempre | no | — |
| `/admin/permisos-operador`, `/admin/desglose`, `*/eliminar/*` | no | **no** | accede | no | — |
| `/juez/*` | no | no | no | accede | — |
| `/documentos/hoja-pago/*` | accede a lo suyo | accede | accede | accede a boletas | — |
| `/api/checklist-infracciones`, `/api/parametros`, `/api/agentes/login` | — | — | — | — | sin token (públicos) |
| `/api/*/sincronizar`, `/api/infracciones/evidencias` | — | — | — | — | token obligatorio |

### 7.3 Los 14 permisos del operador y su efecto en pantalla

El efecto de negar un permiso es doble y consistente: la entrada desaparece del menú lateral **y** el filtro devuelve al operador al dashboard si escribe la URL a mano.

| # | Permiso | Qué habilita | Efecto visible al negarlo |
|---|---|---|---|
| 1 | `solicitudes` | Bandeja, ficha, cambio de estatus, dictamen UR-02, evaluación UR-04, validación UR-05, alta presencial y descarga de documentos | Sin acceso a `/admin/solicitudes` ni a ninguna acción sobre expedientes |
| 2 | `cobros` | Listado, cobro, completar, cancelar, condonación, corte de caja y CSV | Sin acceso a `/admin/cobros` |
| 3 | `usuarios` | Alta y edición de cuentas de ciudadano | Sin acceso a `/admin/usuarios`; la baja sigue siendo del administrador |
| 4 | `tarifas` | CRUD del tarifario | Sin acceso a `/admin/tarifas` |
| 5 | `concesiones` | CRUD del padrón de concesiones | Sin acceso a `/admin/concesiones` |
| 6 | `formatos` | Subida y baja de formatos descargables | Sin acceso a `/admin/formatos` |
| 7 | `auditoria` | Consulta de la bitácora | Sin acceso a `/admin/auditoria` |
| 8 | `documentos` | Consulta del almacén de archivos | Sin acceso a `/admin/documentos` |
| 9 | `verificaciones` | Consulta de la agenda de inspecciones | Sin acceso a `/admin/verificaciones` |
| 10 | `convocatorias` | Publicar, editar, evaluar y seleccionar ganadores | Sin acceso a `/admin/convocatorias`; la baja definitiva sigue siendo del administrador |
| 11 | `app_transito_config` | Parámetros de la app, UMA y altas de agentes | Sin acceso a `/admin/app-transito` |
| 12 | `app_transito_boletas` | Consulta y edición de boletas de campo | Sin acceso a `/admin/app-transito/boletas` |
| 13 | `app_transito_catalogo` | Catálogo de faltas de la app | Sin acceso a `/admin/app-transito/catalogo` |
| 14 | `app_transito_sesiones` | Estado de los accesos de la app | Sin acceso a `/admin/sesiones-app` |

La pantalla `/admin/permisos-operador` pinta exactamente estos 14 interruptores, cada uno con su descripción. No existe un interruptor para `/admin/dashboard` ni para `/admin/desglose`: el dashboard es el destino de las redirecciones por permiso denegado, y el desglose es exclusivo del administrador por ruta.

### 7.4 Reglas invariantes de autorización

Cinco reglas que no se negocian y que cualquier cambio futuro debe respetar:

1. **El administrador nunca se filtra por permiso.** Privilegio total del rol, sin excepciones.
2. **Las bajas definitivas no son configurables.** `usuarios/eliminar`, `app-transito/agentes/eliminar`, `app-transito/boletas/eliminar`, `app-transito/catalogo/eliminar` y `convocatorias/eliminar` exigen `role:administrador` en la ruta.
3. **Cobrar no es administrar.** Cobrar, completar y cancelar un cobro caben en el permiso `cobros` de la ventanilla; condonar un recargo exige autorización adicional verificada en el servicio, no solo el permiso de sección.
4. **La propiedad se comprueba en el controlador.** Los filtros resuelven la superficie (rol y sección), no la pertenencia del expediente. Un ciudadano con sesión válida que adivine un folio ajeno recibe «Solicitud no encontrada o acceso denegado.».
5. **Sin fila de permisos, todo está permitido.** Es el estado inicial y el comportamiento histórico; se documenta aquí porque un administrador que borre la fila esperando «bloquear todo» obtendrá el efecto contrario.

---

## 8. Seguridad

### 8.1 CSRF y su excepción razonada

`Config\Security` activa `CSRFProtection` para toda petición `POST`, con token en campo `csrf_csrf_token`, vigencia de 7200 s y regeneración por envío. Todas las vistas con formulario incluyen `csrf_field()`. CI4 rechaza con 403 una petición sin token antes de que llegue al controlador.

La excepción son las rutas `api/*`: la app móvil no usa cookies de sesión, se autentica con token Bearer y enviar un token CSRF obligaría a la app a mantener una sesión de navegador que no necesita. La autorización de esas rutas la da `agenteToken`.

En `ENVIRONMENT === 'testing'` el filtro CSRF se retira de los globales (§3.5).

### 8.2 Validación de entrada

| Mecanismo | Dónde | Efecto |
|---|---|---|
| `invalidchars` (global) | Todas las peticiones | Rechaza caracteres inválidos en URI y cuerpo |
| Reglas de validación por formulario | Cada controlador | Tipo, longitud, obligatoriedad, formato (CURP 18, RFC por expresión regular) |
| Validación de enums en servicio | `BoletaAccionesService::bloquear()`, `tipoEvidencia()`, `EstadoSolicitudService` | Un valor fuera de catálogo se descarta o se sustituye por el valor seguro |
| `$allowedFields` en los 24 modelos | Capa de persistencia | Un campo no declarado se descarta en silencio: primera defensa contra asignación masiva |

### 8.3 Cabeceras y cookies

`secureheaders` añade las cabeceras de seguridad en la fase `after`. `Config\Cookie` fija `Secure`, `HttpOnly` y `SameSite = Lax` en producción, con dominio `.app-residencias.vercel.app`. `SameSite = Lax` es compatible con la navegación normal del portal y con los `POST` del propio sitio.

### 8.4 Sesiones

Sesión en base de datos (`ci_sessions`) cuando hay serverless, con expiración de 7200 s y refresco cada 300 s. `matchIP` desactivado a propósito. La sesión guarda `user_id` y el arreglo `roles`; los permisos del operador **no** se copian en la sesión para que un cambio del administrador surta efecto de inmediato.

### 8.5 Almacenamiento y descarga de archivos

| Control | Implementación |
|---|---|
| Tipos aceptados | PDF, JPEG y PNG en documentos de expediente; JPG, PNG y WEBP en evidencias de boleta |
| Tamaño máximo | 10,485,760 bytes (10 MB) en documentos de expediente; INE hasta 5 MB |
| Determinación del tipo | Por **contenido** (`getMimeType()`, `guessExtension()`), nunca por el nombre o la extensión enviada |
| Nombre del archivo | UUID completo + extensión deducida del MIME. El nombre original se guarda aparte, solo para mostrarlo |
| Huella | `hash_sha256` del contenido en `documentos`, lo que permite detectar sustitución posterior del archivo |
| Descarga | Mediada: el controlador genera una URL firmada de 1 hora y redirige. La ruta interna del archivo nunca llega al navegador |
| Almacenamiento local | HMAC-SHA256 sobre `bucket|path|expira`; `ArchivosLocalesController` recalcula y compara. Se rechazan rutas con `..` o barras |

Bucket por tipo de contenido: `documentos`, `formatos`, `ine`.

Las fotografías de evidencia de las boletas son la excepción: `subirEvidencia` las guarda en `public/uploads/infracciones/{folio}/` y devuelve la URL pública, porque la app las referencia directamente. El folio se sanea con `preg_replace('/[^A-Za-z0-9_-]/', '_', $folio)` antes de usarse como nombre de carpeta, lo que impide la manipulación de ruta.

### 8.6 Contraseñas

- Usuarios del portal y del panel: `password_hash()` con `PASSWORD_DEFAULT` (bcrypt en PHP 8.2). Verificación con `password_verify()`.
- Agentes de tránsito: `password_hash()` con `PASSWORD_BCRYPT` explícito.
- Recuperación: token temporal con expiración en `users.reset_token` y `users.reset_expira`.
- Nunca se compara ni se registra una contraseña en claro.

### 8.7 Tokens de agente de tránsito

| Aspecto | Comportamiento |
|---|---|
| Emisión | `AgenteTokenService::emitir()` devuelve el token **una sola vez**; en base solo se guarda `token_hash` |
| Transporte | Encabezado `Authorization: Bearer {token}` |
| Vigencia | Columna `expira_en`; un token vencido no autentica |
| Revocación | Columna `revocado_en`; el panel de sesiones la refleja como «revocado» |
| Última actividad | Columna `ultimo_uso_en` |
| Visibilidad | El panel `/admin/sesiones-app` muestra estado y placa, **nunca el token**: no existe en la base para mostrarlo |
| Revocación por desactivación | Un agente con `activo = 0` no puede iniciar sesión; sus tokens dejan de ser válidos al no poder resolver el agente |

En las rutas protegidas la comprobación se hace **dos veces**: en el filtro y de nuevo en el controlador (`agenteAutenticado()`), con el comentario explícito de «fallar cerrado». Es redundancia deliberada: si alguien retira el filtro de la ruta, el controlador sigue protegiendo el endpoint.

### 8.8 Row Level Security en Supabase

Las tablas de la app móvil (`catalogo_infracciones_app`, `parametros_app`) se consultan también **directamente desde la app** con la clave `anon` (§6 de la app móvil). La clave `anon` es pública por diseño; la protección real son las políticas RLS del proyecto Supabase. Si RLS se desactiva en esas tablas, cualquier cliente con la clave pública puede leerlas o escribirlas. Es un control que vive fuera del repositorio y debe verificarse en el panel de Supabase, no en el código.

### 8.9 Principios de diseño de seguridad del proyecto

- **Cobrar no es administrar.** La ventanilla puede cobrar sin poder eliminar ni configurar el sistema.
- **Separación de superficies.** El juez no administra; el operador no da de baja; el administrador no se filtra.
- **Fallar cerrado.** Ante duda, se niega el paso: la ausencia de catálogo devuelve `false` en `transicionValida()`, la ausencia de agente devuelve 401 y la ausencia de cobro lanza excepción de negocio.
- **El servidor manda sobre el estado.** La app móvil envía `estado` y el backend la ignora: siempre escribe `recibida`. El panel de boletas ignora cualquier campo de estado inyectado en el formulario. La fecha de pago se resuelve contra el servidor, no contra el reloj del dispositivo.

---

## 9. API REST de la app móvil

Base de las rutas: `/api`. Todas las respuestas son JSON. Los endpoints de escritura escriben sus propias cabeceras CORS y responden el preflight `OPTIONS` con `204` y cuerpo vacío.

### 9.1 Resumen de endpoints

| Verbo | Ruta | Autenticación | Cuerpo | Códigos de respuesta |
|---|---|---|---|---|
| `GET` | `/api/checklist-infracciones` | ninguna | — | 200 |
| `GET` | `/api/parametros` | ninguna | — | 200 |
| `OPTIONS` | `/api/parametros` | ninguna | — | 204 |
| `POST` | `/api/agentes/login` | ninguna (es el login) | JSON `{placa, password}` | 200, 400, 401, 422 |
| `OPTIONS` | `/api/agentes/login` | ninguna | — | 204 |
| `POST` | `/api/infracciones/sincronizar` | `agenteToken` | JSON `{boletas:[...]}` o una boleta suelta | 200, 400, 401, 403, 422 |
| `POST` | `/api/boletas/sincronizar` | `agenteToken` | ídem (ruta preferida por la app) | 200, 400, 401, 403, 422 |
| `OPTIONS` | ambas rutas de sincronización | ninguna | — | 204 |
| `POST` | `/api/infracciones/evidencias` | `agenteToken` | multipart (`folio`, `tipo`, `foto`) o JSON con `base64` | 200, 400, 401, 403, 415 |
| `OPTIONS` | `/api/infracciones/evidencias` | ninguna | — | 204 |

### 9.2 `GET /api/checklist-infracciones`

Devuelve el catálogo de faltas agrupado por categoría, con los montos ya convertidos a pesos.

**Fuente de los datos, en orden de preferencia**

1. `catalogo_infracciones_app` con `activo = 1`, ordenado por `orden` y luego por `id`. Es el catálogo que el administrador edita en el panel, de modo que un alta o una baja se refleja en la app en la siguiente sincronización.
2. Si ese catálogo está vacío, el **checklist oficial de la boleta física** (`boleta_checklist_item` enriquecido con `catalogo_infracciones`). Es el respaldo para que la app nunca se quede sin faltas.

**Respuesta 200 (fuente primaria)**

```json
{
  "success": true,
  "fuente": "catalogo_infracciones_app",
  "valor_uma_vigente": 117.31,
  "total_items": 42,
  "categorias": {
    "GENERAL": [
      {
        "checklist_item_id": 7,
        "etiqueta_casilla": "Artículo 123 fracción I",
        "fundamento_legal": "Artículo 123 fracción I",
        "descripcion_oficial": "Descripción de la falta",
        "categoria_actor": "GENERAL",
        "monto_min_uma": 10.0,
        "monto_max_uma": 20.0,
        "monto_min_pesos": 1173.10,
        "monto_max_pesos": 2346.20
      }
    ]
  }
}
```

**Respuesta 200 (fuente de respaldo)** añade `total_mapeados` y `total_sin_mapeo` e incluye además el campo `catalogo_infraccion_id` por casilla. `total_sin_mapeo` es un indicador de salud del catálogo: si crece, hay casillas del checklist oficial que aún no apuntan a una falta del catálogo normativo y por tanto no pueden calcular monto.

El valor de la UMA sale de `parametros_app['valor_uma_vigente']`, con **117.31** como valor por defecto cuando la fila no existe.

### 9.3 `GET /api/parametros`

Devuelve los parámetros operativos de la app. Las claves ausentes se rellenan con estos valores por defecto:

| Clave | Valor por defecto | Uso en la app |
|---|---|---|
| `valor_uma_vigente` | `117.31` | Base del cálculo de montos |
| `fotos_obligatorias` | `3` | Número de fotografías exigidas por boleta |
| `app_version` | `1.0.0` | Versión mínima publicada |
| `sync_intervalo_segundos` | `300` | Cada cuánto intenta sincronizar en segundo plano |
| `municipio` | `Uriangato, Guanajuato` | Encabezado de la boleta |
| `modo_estricto_fotos` | `true` | Si es `true`, la app no permite guardar sin las fotos |

Respuesta: `{"success": true, "parametros": { ... }}`. La app cachea el resultado en `@parametros_app_cache_v1`.

### 9.4 `POST /api/agentes/login`

**Solicitud**

```json
{ "placa": "AGT-204", "password": "********" }
```

El campo de usuario se acepta como `placa` o como `usuario`. La placa se normaliza a mayúsculas y se recorta.

**Validaciones y errores**

| Código | Condición | Mensaje |
|---|---|---|
| 400 | El cuerpo no es JSON válido ni un arreglo de campos | «La solicitud no contiene datos válidos.» |
| 422 | Falta la placa o la contraseña | «Ingresa tu placa/usuario y contraseña.» |
| 401 | El agente no existe o `activo <> 1` | «Credenciales inválidas o agente no activo.» |
| 401 | La contraseña no verifica | «Credenciales inválidas.» |

Los dos casos 401 con mensajes distintos son deliberados en un punto y ambiguos en otro: distinguir «no activo» de «contraseña mal» ayuda al agente que fue dado de baja a entender que debe acudir a la ventanilla. Si se prefiere no revelar la existencia de la placa, ambos deben unificarse.

**Respuesta 200**

```json
{
  "success": true,
  "token": "…",
  "token_type": "Bearer",
  "expira_en": "2026-11-05 10:00:00",
  "agente": { "id": 3, "placa": "AGT-204", "nombre_completo": "…", "rol": "agente", "sector": "Centro", "activo": 1 },
  "user":   { "id": 3, "nombre": "…", "rol": "agente" }
}
```

Los objetos `agente` y `user` van duplicados a propósito: la app lee `user` y el panel lee `agente`; mantener ambos evita romper versiones instaladas en los dispositivos.

### 9.5 `POST /api/boletas/sincronizar` (y `POST /api/infracciones/sincronizar`)

Sincroniza una boleta o un lote. Es un **upsert por folio**: inserta si el folio no existe, actualiza si ya existe.

**Solicitud**

```json
{
  "boletas": [
    {
      "folio": "AGT-204-20261029-0001",
      "agente_placa": "AGT-204",
      "fecha_infraccion": "2026-10-29",
      "hora_infraccion": "09:14:00",
      "lugar": "Av. Principal esquina Calle 5",
      "latitud": 20.1445, "longitud": -101.1883,
      "conductor_ausente": true,
      "sin_placas": false,
      "vehiculo_placas": "GTO-1234-A",
      "vehiculo_marca": "Nissan", "vehiculo_linea": "Versa", "vehiculo_color": "Gris", "vehiculo_tipo": "particular",
      "faltas_json": [ { "fundamento_legal": "…", "descripcion": "…", "monto_min_uma": 10, "monto_max_uma": 20 } ],
      "hechos": "Relato de los hechos",
      "garantias_retenidas": [],
      "inventario_grua": null,
      "foto_placa_url": "…", "foto_contexto_url": "…", "foto_documento_url": "…",
      "creado_en_dispositivo": "2026-10-29 09:20:31"
    }
  ]
}
```

También se acepta una sola boleta sin el envoltorio `boletas`, siempre que traiga `folio`.

**Validación del lote completo antes de escribir**

El controlador recorre **todo** el lote antes de persistir nada. Si un solo elemento falla, no se escribe ninguno. Es lo que evita que un lote a medias deje boletas huérfanas.

| Código | Condición | Mensaje |
|---|---|---|
| 400 | El cuerpo no es un arreglo ni un objeto con `folio` | «Datos de boleta inválidos.» |
| 422 | El lote viene vacío | «No se proporcionaron boletas para sincronizar.» |
| 422 | `conductor_ausente` o `sin_placas` no es booleano | «El campo {campo} de la boleta {folio} debe ser booleano.» |
| 401 | Token ausente, vencido o revocado (lo emite el filtro) | «Sesión no válida o expirada. Inicia sesión nuevamente.» |
| 403 | `agente_placa` del cuerpo no coincide con la placa del token | «La boleta {folio} no corresponde al agente autenticado.» |
| 403 | El folio ya existe y pertenece a otro agente | «El folio {folio} pertenece a otro agente.» |

La validación de tipo estricta sobre `conductor_ausente` y `sin_placas` existe porque son los dos campos que cambian el sentido jurídico de la boleta: un `"false"` de texto se guardaría como verdadero en PostgreSQL y convertiría una boleta con conductor presente en una con conductor ausente.

**Respuesta 200**

```json
{
  "success": true,
  "sincronizadas": 3,
  "errores": [],
  "message": "Se sincronizaron 3 boleta(s) correctamente."
}
```

`errores` recoge los folios descartados individualmente (por ejemplo, sin folio válido) sin abortar el resto. `success` es verdadero solo si al menos una boleta se sincronizó.

**Reglas de escritura en el servidor**

- `estado` se fuerza siempre a `recibida`, con independencia de lo que envíe el cliente.
- `sincronizado_en` lo pone el servidor.
- Si la boleta ya existe y su estado **no** es `recibida` (es decir, el juez ya la calificó o ya se canceló), se protegen y no se sobrescriben: `estado`, `fecha_infraccion`, `infractor_nombre`, `falta_fundamento_legal`, `falta_descripcion`, `falta_categoria`, `falta_monto_min_uma`, `falta_monto_max_uma` y `faltas_json`. Una resincronización tardía puede completar datos operativos o fotografías, pero no revierte la decisión del juez.

### 9.6 `POST /api/infracciones/evidencias`

Dos formas de envío:

| Forma | Contenido |
|---|---|
| `multipart/form-data` | campos `folio`, `tipo`, y el archivo en `foto` |
| JSON | `{ "folio": "...", "tipo": "...", "base64": "data:image/jpeg;base64,..." }` |

`tipo` solo admite `placa`, `contexto` o `documento`; cualquier otro valor se normaliza a `evidencia`.

| Código | Condición | Mensaje |
|---|---|---|
| 415 | La extensión deducida del contenido no es JPG, PNG ni WEBP | «Solo se aceptan imágenes JPG, PNG o WEBP.» |
| 403 | El folio pertenece a otro agente | «El folio {folio} pertenece a otro agente.» |
| 400 | No llegó archivo ni base64 válido | «No se recibió un archivo de imagen o base64 válido.» |
| 200 | Éxito | `{"success": true, "url": "https://…/uploads/infracciones/{folio}/{tipo}_{timestamp}_{folio}.{ext}"}` |

El nombre del archivo combina el tipo, la marca de tiempo y el folio saneado. La extensión se deduce del contenido con `guessExtension()`, y `jpeg` se normaliza a `jpg`.

---

## 10. Catálogo de rutas

**173 rutas registradas** (96 `GET`, 71 `POST`, 5 `OPTIONS`, 1 `HEAD`), medidas con `./php82/php.exe spark routes` el 29-oct-2026. El sistema tiene `setAutoRoute(false)`: una ruta que no aparezca en esta sección responde 404.

La columna **Filtro** indica solo el filtro declarado en la ruta; hay que añadir los filtros globales (§3.5) y los masivos: `permisoOperador` en todo `admin/*` e `adminRedirect` en las rutas del portal listadas en §3.5.

### 10.1 Autenticación (11)

| Verbo | Ruta | Alias | Controlador::método | Filtro |
|---|---|---|---|---|
| `GET` | `auth/login` | `login` | `AuthController::login` | — |
| `POST` | `auth/attempt-login` | — | `AuthController::attemptLogin` | — |
| `GET` | `auth/register` | `register` | `AuthController::register` | — |
| `POST` | `auth/attempt-register` | — | `AuthController::attemptRegister` | — |
| `GET` | `auth/logout` | `logout` | `AuthController::logout` | — |
| `GET` | `auth/forgot` | — | `AuthController::forgot` | — |
| `POST` | `auth/attempt-forgot` | — | `AuthController::attemptForgot` | — |
| `GET` | `auth/reset/(:any)` | — | `AuthController::reset/$1` | — |
| `POST` | `auth/reset/(:any)` | — | `AuthController::reset/$1` | — |
| `GET` | `auth/terminos` | — | `AuthController::terminos` | — |
| `GET` | `auth/privacidad` | — | `AuthController::privacidad` | — |

### 10.2 API móvil (11)

| Verbo | Ruta | Controlador::método | Filtro |
|---|---|---|---|
| `GET` | `api/checklist-infracciones` | `Api\InfraccionesApiController::checklist` | — |
| `OPTIONS` | `api/agentes/login` | `Api\AgentesTransitoApiController::login` | — |
| `POST` | `api/agentes/login` | `Api\AgentesTransitoApiController::login` | — |
| `GET` | `api/parametros` | `Api\InfraccionesApiController::parametros` | — |
| `OPTIONS` | `api/parametros` | `Api\InfraccionesApiController::parametros` | — |
| `OPTIONS` | `api/infracciones/sincronizar` | `Api\InfraccionesApiController::sincronizar` | — |
| `POST` | `api/infracciones/sincronizar` | `Api\InfraccionesApiController::sincronizar` | `agenteToken` |
| `OPTIONS` | `api/boletas/sincronizar` | `Api\InfraccionesApiController::sincronizar` | — |
| `POST` | `api/boletas/sincronizar` | `Api\InfraccionesApiController::sincronizar` | `agenteToken` |
| `OPTIONS` | `api/infracciones/evidencias` | `Api\InfraccionesApiController::subirEvidencia` | — |
| `POST` | `api/infracciones/evidencias` | `Api\InfraccionesApiController::subirEvidencia` | `agenteToken` |

### 10.3 Panel del juez (16)

Grupo con `auth` + `role:juez_calificador`.

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `juez` | `juez.inicio` | `Juez\DashboardController::index` |
| `GET` | `juez/boletas` | `juez.boletas` | `Juez\DashboardController::boletas` |
| `GET` | `juez/boletas/(:num)` | `juez.boleta` | `Juez\DashboardController::detalle/$1` |
| `POST` | `juez/boletas/(:num)/vista-previa` | `juez.boleta.previa` | `Juez\DashboardController::vistaPrevia/$1` |
| `POST` | `juez/boletas/(:num)/calificar` | `juez.boleta.calificar` | `Juez\DashboardController::calificar/$1` |
| `GET` | `juez/cobros` | `juez.cobros` | `CobrosController::index` |
| `GET` | `juez/cobros/banco` | `juez.cobros.banco` | `CobrosController::banco` |
| `GET` | `juez/cobros/corte` | `juez.cobros.corte` | `CobrosController::corte` |
| `GET` | `juez/cobros/corte/csv` | — | `CobrosController::corteCsv` |
| `GET` | `juez/cobros/condonaciones` | `juez.cobros.condonaciones` | `CobrosController::condonaciones` |
| `GET` | `juez/cobros/(:num)/condonar` | — | `CobrosController::formularioCondonar/$1` |
| `POST` | `juez/cobros/(:num)/condonar` | — | `CobrosController::condonar/$1` |
| `GET` | `juez/cobros/(:num)` | `juez.cobro` | `CobrosController::ver/$1` |
| `POST` | `juez/cobros/(:num)/cobrar` | — | `CobrosController::cobrar/$1` |
| `POST` | `juez/cobros/(:num)/completar` | — | `CobrosController::completar/$1` |
| `POST` | `juez/cobros/(:num)/cancelar` | — | `CobrosController::cancelar/$1` |

### 10.4 Documentos de pago (2)

Grupo con `auth`. El permiso fino de propiedad lo resuelve el controlador.

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `documentos/hoja-pago/solicitud/(:segment)` | `documentos.hoja_pago_solicitud` | `DocumentosPagoController::hojaPagoSolicitud/$1` |
| `GET` | `documentos/hoja-pago/boleta/(:num)` | `documentos.hoja_pago_boleta` | `DocumentosPagoController::hojaPagoBoleta/$1` |

### 10.5 Panel administrativo — operativo (68)

Grupo con `auth` + `role:administrador,operador_ventanilla`, más `permisoOperador` por aplicación masiva.

**Panel, consultas y expedientes (18)**

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `admin/dashboard` | `admin.dashboard` | `Admin\AdminController::dashboard` |
| `GET` | `admin/auditoria` | `admin.auditoria` | `Admin\AdminController::auditoria` |
| `GET` | `admin/documentos` | `admin.documentos` | `Admin\DocumentosController::index` |
| `GET` | `admin/verificaciones` | `admin.verificaciones` | `Admin\VerificacionesController::index` |
| `GET` | `admin/sesiones-app` | `admin.sesiones_app` | `Admin\SesionesAppController::index` |
| `GET` | `admin/solicitudes` | `admin.solicitudes` | `Admin\AdminController::listaSolicitudes` |
| `GET` | `admin/solicitudes/nueva` | — | `Admin\SolicitudPresencialController::index` |
| `POST` | `admin/solicitudes/nueva/buscar-ciudadano` | — | `Admin\SolicitudPresencialController::buscarCiudadano` |
| `POST` | `admin/solicitudes/nueva/registrar-ciudadano` | — | `Admin\SolicitudPresencialController::registrarCiudadano` |
| `POST` | `admin/solicitudes/nueva/guardar` | — | `Admin\SolicitudPresencialController::guardar` |
| `GET` | `admin/solicitudes/nueva/(:segment)` | — | `Admin\SolicitudPresencialController::formulario/$1` |
| `GET` | `admin/solicitudes/(:segment)` | `admin.solicitud.ver` | `Admin\AdminController::verSolicitud/$1` |
| `POST` | `admin/solicitudes/cambiar-estatus/(:num)` | — | `Admin\AdminController::cambiarEstatus/$1` |
| `POST` | `admin/solicitudes/ur04/evaluacion/(:num)` | — | `Admin\AdminController::guardarEvaluacionUr04/$1` |
| `POST` | `admin/solicitudes/ur05/validacion/(:num)` | — | `Admin\AdminController::validarCierreCalleUr05/$1` |
| `POST` | `admin/solicitudes/dictamen-ur02/(:num)` | — | `Admin\AdminController::registrarDictamenUr02/$1` |
| `GET` | `admin/solicitudes/descargar-documento/(:num)` | — | `Admin\AdminController::descargarDocumento/$1` |
| `POST` | `admin/solicitudes/validar-documento/(:num)` | — | `Admin\AdminController::validarDocumento/$1` |

> **Colisión de alias conocida.** El alias de `admin.solicitud.ver` convive con `admin.solicitudes`; cualquier ruta nueva bajo `admin/solicitudes/...` debe declararse **antes** de `admin/solicitudes/(:segment)`, porque `setPrioritize(true)` y el orden de declaración hacen que el comodín se coma las rutas posteriores con un solo segmento.

**Cobros (11)**

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `admin/cobros` | `admin.cobros` | `CobrosController::index` |
| `GET` | `admin/cobros/banco` | `admin.cobros.banco` | `CobrosController::banco` |
| `GET` | `admin/cobros/corte` | `admin.cobros.corte` | `CobrosController::corte` |
| `GET` | `admin/cobros/corte/csv` | — | `CobrosController::corteCsv` |
| `GET` | `admin/cobros/condonaciones` | `admin.cobros.condonaciones` | `CobrosController::condonaciones` |
| `GET` | `admin/cobros/(:num)/condonar` | — | `CobrosController::formularioCondonar/$1` |
| `POST` | `admin/cobros/(:num)/condonar` | — | `CobrosController::condonar/$1` |
| `GET` | `admin/cobros/(:num)` | `admin.cobro` | `CobrosController::ver/$1` |
| `POST` | `admin/cobros/(:num)/cobrar` | — | `CobrosController::cobrar/$1` |
| `POST` | `admin/cobros/(:num)/completar` | — | `CobrosController::completar/$1` |
| `POST` | `admin/cobros/(:num)/cancelar` | — | `CobrosController::cancelar/$1` |

**Tarifas (6)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `admin/tarifas` | `Admin\CatalogoTarifasController::index` |
| `GET` | `admin/tarifas/nuevo` | `Admin\CatalogoTarifasController::formNuevo` |
| `POST` | `admin/tarifas/guardar` | `Admin\CatalogoTarifasController::guardar` |
| `GET` | `admin/tarifas/editar/(:num)` | `Admin\CatalogoTarifasController::formEditar/$1` |
| `POST` | `admin/tarifas/actualizar/(:num)` | `Admin\CatalogoTarifasController::actualizar/$1` |
| `POST` | `admin/tarifas/eliminar/(:num)` | `Admin\CatalogoTarifasController::eliminar/$1` |

**Concesiones (6)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `admin/concesiones` | `Admin\CatalogoConcesionesController::index` |
| `GET` | `admin/concesiones/nuevo` | `Admin\CatalogoConcesionesController::formNuevo` |
| `POST` | `admin/concesiones/guardar` | `Admin\CatalogoConcesionesController::guardar` |
| `GET` | `admin/concesiones/editar/(:num)` | `Admin\CatalogoConcesionesController::formEditar/$1` |
| `POST` | `admin/concesiones/actualizar/(:num)` | `Admin\CatalogoConcesionesController::actualizar/$1` |
| `POST` | `admin/concesiones/eliminar/(:num)` | `Admin\CatalogoConcesionesController::eliminar/$1` |

**Convocatorias (8)**

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `admin/convocatorias` | `admin.convocatorias` | `Admin\ConvocatoriasController::index` |
| `GET` | `admin/convocatorias/nueva` | — | `Admin\ConvocatoriasController::formNuevo` |
| `POST` | `admin/convocatorias/guardar` | — | `Admin\ConvocatoriasController::guardar` |
| `GET` | `admin/convocatorias/editar/(:num)` | — | `Admin\ConvocatoriasController::formEditar/$1` |
| `POST` | `admin/convocatorias/actualizar/(:num)` | — | `Admin\ConvocatoriasController::actualizar/$1` |
| `GET` | `admin/convocatorias/(:num)/evaluacion` | `admin.convocatoria.evaluacion` | `Admin\ConvocatoriasController::evaluacion/$1` |
| `POST` | `admin/convocatorias/(:num)/seleccionar` | — | `Admin\ConvocatoriasController::seleccionar/$1` |
| `GET` | `admin/convocatorias/(:num)/bases` | — | `Admin\ConvocatoriasController::bases/$1` |

**Formatos (4)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `admin/formatos` | `Admin\AdminController::formatos` |
| `GET` | `admin/formatos/ver/(:segment)` | `Admin\AdminController::verFormato/$1` |
| `POST` | `admin/formatos/subir` | `Admin\AdminController::subirFormato` |
| `POST` | `admin/formatos/eliminar` | `Admin\AdminController::eliminarFormato` |

**App de tránsito (9)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `admin/app-transito` | `Admin\AppTransitoController::index` |
| `POST` | `admin/app-transito/guardar-parametros` | `Admin\AppTransitoController::guardarParametros` |
| `POST` | `admin/app-transito/agentes/guardar` | `Admin\AppTransitoController::guardarAgente` |
| `POST` | `admin/app-transito/agentes/toggle/(:num)` | `Admin\AppTransitoController::toggleEstadoAgente/$1` |
| `GET` | `admin/app-transito/boletas` | `Admin\AppTransitoController::boletas` |
| `GET` | `admin/app-transito/catalogo` | `Admin\AppTransitoController::catalogo` |
| `POST` | `admin/app-transito/catalogo/guardar` | `Admin\AppTransitoController::guardarCatalogo` |
| `GET` | `admin/app-transito/boletas/editar/(:num)` | `Admin\AppTransitoController::editarBoleta/$1` |
| `POST` | `admin/app-transito/boletas/actualizar/(:num)` | `Admin\AppTransitoController::actualizarBoleta/$1` |

**Usuarios (6)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `admin/usuarios` | `Admin\UsuariosController::index` |
| `GET` | `admin/usuarios/nuevo` | `Admin\UsuariosController::formNuevo` |
| `POST` | `admin/usuarios/guardar` | `Admin\UsuariosController::guardar` |
| `GET` | `admin/usuarios/editar/(:num)` | `Admin\UsuariosController::formEditar/$1` |
| `POST` | `admin/usuarios/actualizar/(:num)` | `Admin\UsuariosController::actualizar/$1` |
| `POST` | `admin/usuarios/toggle/(:num)` | `Admin\UsuariosController::toggleEstado/$1` |

### 10.6 Panel administrativo — solo administrador (8)

Grupo con `auth` + `role:administrador`.

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `admin/permisos-operador` | — | `Admin\PermisosController::index` |
| `POST` | `admin/permisos-operador/guardar` | — | `Admin\PermisosController::guardar` |
| `GET` | `admin/desglose` | `admin.desglose` | `Admin\DesgloseController::index` |
| `POST` | `admin/usuarios/eliminar/(:num)` | — | `Admin\UsuariosController::eliminar/$1` |
| `POST` | `admin/app-transito/agentes/eliminar/(:num)` | — | `Admin\AppTransitoController::eliminarAgente/$1` |
| `POST` | `admin/app-transito/boletas/eliminar/(:num)` | — | `Admin\AppTransitoController::eliminarBoleta/$1` |
| `POST` | `admin/app-transito/catalogo/eliminar/(:num)` | — | `Admin\AppTransitoController::eliminarCatalogo/$1` |
| `POST` | `admin/convocatorias/eliminar/(:num)` | — | `Admin\ConvocatoriasController::eliminar/$1` |

### 10.7 Raíz, atajos, archivos y app de agentes (8)

| Verbo | Ruta | Alias | Controlador::método | Filtro |
|---|---|---|---|---|
| `GET` | `archivos/(:segment)` | — | `ArchivosLocalesController::servir/$1` | firma HMAC |
| `GET` | `/` | — | `Home::index` | — |
| `HEAD` | `/` | — | *closure* que responde 200 vacío | — |
| `GET` | `login` | `acceso` | `AuthController::login` | — |
| `GET` | `register` | `registro` | `AuthController::register` | — |
| `GET` | `logout` | `salida` | `AuthController::logout` | — |
| `GET` | `agentes` | `agentes.inicio` | `AgentesWebController::shell` | — |
| `GET` | `agentes/(:any)` | `agentes.ruta` | `AgentesWebController::shell` | — |

### 10.8 Portal del ciudadano (49)

Grupo con `auth`. Las seis primeras llevan además `adminRedirect` por aplicación masiva.

**Panel, solicitudes y perfil (9)**

| Verbo | Ruta | Alias | Controlador::método |
|---|---|---|---|
| `GET` | `portal` | — | `Ciudadano\PortalController::dashboard` |
| `GET` | `portal/dashboard` | `portal.dashboard` | `Ciudadano\PortalController::dashboard` |
| `GET` | `portal/tramites` | `portal.tramites` | `Ciudadano\PortalController::tramites` |
| `GET` | `portal/mis-solicitudes` | `portal.mis_solicitudes` | `Ciudadano\PortalController::misSolicitudes` |
| `GET` | `portal/solicitud/(:segment)` | `portal.ver_solicitud` | `Ciudadano\PortalController::verSolicitud/$1` |
| `GET` | `portal/solicitud/(:segment)/descargar/(:num)` | `portal.solicitud.descargar` | `Ciudadano\PortalController::descargarDocumento/$1/$2` |
| `GET` | `portal/mi-perfil` | `portal.mi_perfil` | `Ciudadano\PortalController::miPerfil` |
| `POST` | `portal/mi-perfil` | — | `Ciudadano\PortalController::guardarPerfil` |
| `GET` | `portal/formato/(:segment)` | — | `Ciudadano\PortalController::descargarFormato/$1` |

**API interna del portal (6)**

Estas seis llevan `role:administrador,operador_ventanilla,ciudadano` (las dos últimas, solo admin y operador).

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `POST` | `portal/tramites/solicitudes` | `Ciudadano\TramitesController::crear` |
| `GET` | `portal/tramites/solicitudes/(:segment)` | `Ciudadano\TramitesController::consultar/$1` |
| `POST` | `portal/tramites/ur-02/solicitudes/(:num)/cita` | `Ciudadano\TramitesController::agendarVerificacion/$1` |
| `POST` | `portal/tramites/ur-02/solicitudes/(:num)/resultado` | `Ciudadano\TramitesController::registrarResultado/$1` |
| `GET` | `portal/tramites/ur-01/convocatorias/(:num)/solicitudes` | `Ciudadano\TramitesController::listarConvocatoria/$1` |
| `POST` | `portal/tramites/ur-01/convocatorias/(:num)/seleccionar` | `Ciudadano\TramitesController::seleccionar/$1` |

**UR-TT-T-01 Concesión de transporte (4)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/concesion-transporte` | `Ciudadano\TramiteConcesionTransporteController::formulario` |
| `GET` | `portal/tramites/ur-01` | `Ciudadano\TramiteConcesionTransporteController::formulario` |
| `POST` | `portal/tramites/concesion-transporte/guardar` | `Ciudadano\TramiteConcesionTransporteController::guardar` |
| `POST` | `portal/tramites/ur-01/guardar` | `Ciudadano\TramiteConcesionTransporteController::guardar` |

**UR-TT-T-02 Constancia de despintado (6)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/constancia-despintado` | `Ciudadano\TramiteDespintadoController::formulario` |
| `GET` | `portal/tramites/ur-02` | `Ciudadano\TramiteDespintadoController::formulario` |
| `POST` | `portal/tramites/constancia-despintado/guardar` | `Ciudadano\TramiteDespintadoController::guardar` |
| `POST` | `portal/tramites/ur-02/guardar` | `Ciudadano\TramiteDespintadoController::guardar` |
| `GET` | `portal/tramites/ur-02/solicitud/(:any)/cita` | `Ciudadano\TramiteDespintadoController::agendarCitaForm/$1` |
| `POST` | `portal/tramites/ur-02/solicitud/(:any)/cita/guardar` | `Ciudadano\TramiteDespintadoController::guardarCita/$1` |

**UR-TT-T-03 Orden de plaqueo (4)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/orden-plaqueo` | `Ciudadano\TramiteOrdenPlaqueoController::formulario` |
| `GET` | `portal/tramites/ur-03` | `Ciudadano\TramiteOrdenPlaqueoController::formulario` |
| `POST` | `portal/tramites/orden-plaqueo/guardar` | `Ciudadano\TramiteOrdenPlaqueoController::guardar` |
| `POST` | `portal/tramites/ur-03/guardar` | `Ciudadano\TramiteOrdenPlaqueoController::guardar` |

**UR-TT-T-04 Permiso eventual de transporte (4)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/permiso-eventual` | `Ciudadano\TramitePermisoEventualController::formulario` |
| `POST` | `portal/tramites/permiso-eventual/guardar` | `Ciudadano\TramitePermisoEventualController::guardar` |
| `GET` | `portal/tramites/permiso-eventual/resumen/(:any)` | `Ciudadano\TramitePermisoEventualController::resumen/$1` |
| `POST` | `portal/tramites/permiso-eventual/pagar/(:num)` | `Ciudadano\TramitePermisoEventualController::pagar/$1` |

**UR-TT-T-05 Permiso para cierre de calle (5)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/cierre-calle` | `Ciudadano\TramiteCierreCalleController::formulario` |
| `POST` | `portal/tramites/cierre-calle/guardar` | `Ciudadano\TramiteCierreCalleController::guardar` |
| `GET` | `portal/tramites/cierre-calle/resumen/(:any)` | `Ciudadano\TramiteCierreCalleController::resumen/$1` |
| `POST` | `portal/tramites/cierre-calle/pagar/(:num)` | `Ciudadano\TramiteCierreCalleController::pagar/$1` |
| `GET` | `portal/tramites/cierre-calle/permiso/(:any)` | `Ciudadano\TramiteCierreCalleController::permiso/$1` |

**UR-TT-T-06 Cesión de concesión (3)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/cesion-concesion` | `Ciudadano\TramiteCesionConcesionController::formulario` |
| `POST` | `portal/tramites/cesion-concesion/guardar` | `Ciudadano\TramiteCesionConcesionController::guardar` |
| `GET` | `portal/tramites/cesion-concesion/validar-concesion/(:any)` | `Ciudadano\TramiteCesionConcesionController::validarConcesionAjax/$1` |

El acceso a este trámite está condicionado por la bandera `APP_ENABLE_UR_TT_T_06` (§3.8).

**UR-TT-T-07 Permiso de carga y descarga (8)**

| Verbo | Ruta | Controlador::método |
|---|---|---|
| `GET` | `portal/tramites/carga-descarga/formulario` | `Ciudadano\TramiteCargaDescargaController::formulario` |
| `GET` | `portal/tramites/carga-descarga` | `Ciudadano\TramiteCargaDescargaController::formulario` |
| `POST` | `portal/tramites/carga-descarga/guardar` | `Ciudadano\TramiteCargaDescargaController::guardar` |
| `POST` | `portal/tramites/carga-descarga/calcular-monto` | `Ciudadano\TramiteCargaDescargaController::calcularMontoAjax` |
| `GET` | `portal/tramites/carga-descarga/calcular-monto` | `Ciudadano\TramiteCargaDescargaController::calcularMontoAjax` |
| `GET` | `portal/tramites/carga-descarga/resumen/(:any)` | `Ciudadano\TramiteCargaDescargaController::resumen/$1` |
| `POST` | `portal/tramites/carga-descarga/pagar/(:num)` | `Ciudadano\TramiteCargaDescargaController::pagar/$1` |
| `GET` | `portal/tramites/carga-descarga/(:any)/descargar/(:num)` | `Ciudadano\TramiteCargaDescargaController::descargarDocumento/$1/$2` |

`calcular-monto` responde tanto a `GET` como a `POST` porque la app web lo llama por `fetch` con parámetros de consulta y el formulario lo llama por `POST`; ambos caminos ejecutan el mismo cálculo.

### 10.9 Comprobación del inventario

| Grupo | Rutas |
|---|---|
| Autenticación | 11 |
| API móvil | 11 |
| Panel del juez | 16 |
| Documentos de pago | 2 |
| Admin operativo | 68 |
| Admin solo administrador | 8 |
| Raíz, atajos, archivos y agentes | 8 |
| Portal del ciudadano | 49 |
| **Total** | **173** |

---

## 11. Base de datos

PostgreSQL 15 o superior alojado en Supabase. Esquema `public`. El proyecto Supabase verificado es `kuwxjtwjjefqpzubtrlc`.

**Estado medido el 29-oct-2026** con `./php82/php.exe scripts/verificar_esquema_bd.php`:

| Concepto | Valor |
|---|---|
| Tablas definidas en el repositorio | 26 |
| Tablas existentes en la base | 29 |
| Registros en la tabla de migraciones | 31 |
| Tablas presentes en la base y ausentes del repositorio | `migrations`, `ciudadanos`, `administradores` |
| Resultado global | correcto |

Las tres tablas adicionales son residuo de una etapa anterior del proyecto (`ciudadanos` y `administradores` se sustituyeron por `users` + `user_roles`) y de la infraestructura de migraciones de Supabase (`migrations`). **No las usa el código actual**; se dejan porque contienen datos históricos y su eliminación no aporta nada al sistema en operación.

### 11.1 Dos vías de migración y su desfase

El proyecto mantiene el esquema por dos caminos:

| Vía | Ubicación | Archivos | Cómo se aplica |
|---|---|---|---|
| Migraciones de CI4 | `app/Database/Migrations/` | **33** archivos `000001`–`000033` | `./php82/php.exe spark migrate` |
| Migraciones SQL de Supabase | `supabase/migrations/` | **19** archivos `0001`–`0018` (hay dos `0005`) | Ejecución manual en el editor SQL o con la CLI de Supabase |

**Existe desfase real: CI4 llega a `000033` y Supabase a `0018`.** Esto no significa que falten tablas: `supabase/migrations/0008_completar_esquema_pendiente.sql` completó en su momento el esquema que las migraciones de CI4 ya habían creado, y las migraciones posteriores se emparejaron una a una. El desfase se documenta como estado conocido y se resuelve con el procedimiento de §11.4, no «arreglando» el número de archivos.

Correspondencia entre ambas vías:

| Migración CI4 | Contenido | Equivalente en Supabase |
|---|---|---|
| `000001`–`000010` | Esquema base: roles, users, user_roles, concesiones, solicitudes, solicitud_datos, documentos, tarifas, historial_estatus, auditoria | `0001_init_schema.sql` |
| — | Correcciones de contraseñas de demostración | `0002_fix_user_passwords.sql`, `0004_fix_demo_passwords.sql` |
| — | Tabla de sesiones | `0003_ci_sessions.sql` |
| `000011`–`000017` | convocatorias, verificaciones_fisicas, relaciones de trámite, tipo de persona, campos de ciudadano, formatos de trámite, INE | `0008_completar_esquema_pendiente.sql` |
| `000018` | `auditoria.registro_borrados` | `0005_add_registro_borrados_to_auditoria.sql` |
| `000019` | Vistas de roles en la base | Sin equivalente SQL (se crean desde CI4) |
| `000020`–`000021` | Catálogo de infracciones, parámetros del sistema, checklist, tablas de la app de tránsito | `0005_agentes_transito_app.sql` |
| — | Soporte de múltiples faltas por boleta (`faltas_json`) | `0006_multi_infracciones_soporte.sql` |
| `000022` | `agentes_transito_tokens` | `0007_agentes_transito_tokens.sql` |
| `000023` | Campos de edición de convocatorias | `0008_completar_esquema_pendiente.sql` |
| `000024` | `solicitudes.concesion_id` deja de ser `CASCADE` | `0009_concesion_id_sin_cascade.sql` |
| `000025` | Tarifas base faltantes | `0010_tarifas_base_faltantes.sql` |
| `000026` | Resincronización de secuencias de `id` | `0011_resincronizar_secuencias.sql` |
| `000027` | `cobros` y `cobro_movimientos` | `0012_cobros.sql` |
| `000028` | Rol `juez_calificador` | `0013_juez_calificador_role.sql` |
| `000029` | `users.password_hash` admite nulo (ciudadano sin acceso) | `0014_ciudadano_sin_acceso.sql` |
| `000030` | `boleta_calificaciones` y detalles | `0015_boleta_calificaciones.sql` |
| `000031` | Cobros de solicitudes ya en «Pago pendiente» | `0016_cobros_de_solicitudes_pendientes.sql` |
| `000032` | `cobros.referencia_bancaria` | `0017_referencia_bancaria.sql` |
| `000033` | Tarifas de las fichas oficiales | `0018_tarifas_fichas_oficiales.sql` |

> **Advertencia operativa.** `supabase/migrations/0015_boleta_calificaciones.sql` se declara en su encabezado como **alternativa independiente** a la migración de CI4 `000030` y advierte: «No ejecutar ambas». Aplicar las dos crea la tabla dos veces y falla a medio camino. El mismo criterio vale para cualquier par CI4 ↔ Supabase de esta tabla: se ejecuta una vía, no las dos.

Orden de aplicación en una instalación nueva: **una sola vía**. Si se usa CI4, `spark migrate` y los seeders (§12). Si se usa el editor SQL de Supabase, los archivos `.sql` en orden numérico.

### 11.2 Diccionario de las 26 tablas

Convenciones: `id` es siempre `BIGSERIAL` de PostgreSQL salvo indicación; `created_at` y `updated_at` son marcas de tiempo de CI4; las llaves foráneas son `RESTRICT` salvo indicación.

#### Identidad y acceso (5 tablas)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `roles` | `id` | `nombre` (único), `descripcion` | Catálogo de roles: `administrador`, `operador_ventanilla`, `ciudadano`, `juez_calificador`, `agente_transito` |
| `users` | `id` | `username` (único), `email` (único), `password_hash` (admite nulo), `nombre_completo`, `curp`, `apellido`, `rfc`, `telefono`, `estado`, `ciudad`, `domicilio`, `ine_frente`, `ine_reverso`, `activo`, `reset_token`, `reset_expira` | `curp`, `apellido` y los campos de domicilio se agregaron en `000015`; los de INE en `000017`. `password_hash` admite nulo desde `000029` para el ciudadano dado de alta en ventanilla |
| `user_roles` | `id` | `user_id`, `role_id` | FK a `users` y `roles`. Un usuario puede tener varios roles; no hay modelo propio |
| `ciudadanos` | — | — | **Tabla heredada**, no usada por el código actual. Se conserva con datos históricos |
| `administradores` | — | — | **Tabla heredada**, igual que la anterior |

#### Expediente y trámites (6 tablas)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `solicitudes` | `id` | `folio` (único), `tramite`, `ciudadano_id`, `concesion_id`, `convocatoria_id`, `estatus`, `monto`, `fecha_solicitud`, `fecha_resolucion`, `fecha_pago`, `fecha_vigencia_inicio`, `fecha_vigencia_fin`, `comentario_rechazo` | FK a `users` (`ciudadano_id`), `concesiones`, `convocatorias`. `concesion_id` pasó de `CASCADE` a `RESTRICT` en `000024`: borrar una concesión ya no arrastra expedientes |
| `solicitud_datos` | `id` | `solicitud_id`, `clave`, `valor` | FK a `solicitudes`. Almacén clave-valor del formulario de cada trámite |
| `documentos` | `id` | `solicitud_id`, `tipo_documento`, `nombre_original`, `ruta_interna`, `mime_type`, `tamano_bytes`, `hash_sha256`, `usuario_id`, `fecha_carga`, `validado` | FK a `solicitudes` y `users`. `hash_sha256` permite detectar sustitución del archivo |
| `historial_estatus` | `id` | `solicitud_id`, `estatus_anterior`, `estatus_nuevo`, `usuario_id`, `fecha`, `comentario` | FK a `solicitudes` y `users`. Bitácora de cada transición |
| `verificaciones_fisicas` | `id` | `solicitud_id`, `fecha_cita`, `resultado`, `observaciones` | FK a `solicitudes`. Agenda de inspección, usada por T-02 y T-04 |
| `formatos_tramite` | `id` | `tramite`, `nombre`, `descripcion`, `nombre_archivo`, `ruta_interna`, `mime_type`, `tamano_bytes`, `usuario_id`, `activo` | FK a `users`. `activo` es la baja lógica |

#### Catálogos (5 tablas)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `tarifas` | `id` | `tramite`, `criterio`, `monto`, `vigente_desde`, `vigente_hasta`, `descripcion`, `placeholder_oficial` | `placeholder_oficial = 1` marca importes provisionales. El monto se copia a la solicitud al crearse, por lo que editar la tarifa no altera expedientes existentes |
| `concesiones` | `id` | `numero_titulo` (único), `titular_actual`, `vehiculo_placas`, `vehiculo_num_serie`, `tipo_persona`, `vigencia_inicio`, `vigencia_fin`, `estatus` | `tipo_persona` se agregó en `000014`. Con expedientes vinculados, la baja se bloquea |
| `convocatorias` | `id` | `titulo`, `fecha_publicacion`, `periodo_registro_inicio`, `periodo_registro_fin`, `bases`, `estatus`, `bases_archivo`, `bases_nombre`, `bases_mime`, `bases_tamano`, `created_by` | Los metadatos de archivo y `created_by` se agregaron en `000023` |
| `catalogo_infracciones` | `id` | `fundamento_legal`, `descripcion`, `categoria_actor`, `monto_min_uma`, `monto_max_uma` | Catálogo normativo del servidor |
| `catalogo_infracciones_app` | `id` | `fundamento_legal`, `descripcion`, `categoria`, `monto_min_uma`, `monto_max_uma`, `activo`, `orden` | Catálogo que consume la app. `activo` y `orden` permiten publicar selectivamente |

#### Cobros (6 tablas)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `cobros` | `id` | `folio` (único, `COB-AAAA-NNNNNN`), `origen` (`solicitud` \| `boleta`), `solicitud_id`, `boleta_id`, `ciudadano_id`, `nombre_pagador`, `concepto`, `monto_base`, `descuento`, `recargo`, `condonado`, `total`, `detalle_calculo` (JSON), `estatus`, `pendiente_documentos`, `motivo`, `lugar_pago`, `referencia_pago`, `folio_recibo` (`REC-AAAA-NNNNNN`), `referencia_bancaria`, `fecha_pago_banco`, `importe_pagado`, `cobrado_por`, `fecha_cobro`, `created_by` | FK a `solicitudes`, `boletas_infracciones`, `users`. `referencia_bancaria` se agregó en `000032`. `detalle_calculo` guarda el desglose completo del cálculo para poder explicar el monto meses después |
| `cobro_movimientos` | `id` | `cobro_id`, `accion`, `estatus_anterior`, `estatus_nuevo`, `motivo`, `detalle`, `usuario_id`, `fecha` | FK a `cobros` y `users`. Historial de cada acción sobre el cobro |
| `boletas_infracciones` | `id` | `folio` (único), `agente_placa`, `agente_nombre`, `fecha_infraccion`, `hora_infraccion`, `lugar`, `latitud`, `longitud`, `conductor_ausente`, `sin_placas`, `infractor_*`, `vehiculo_*`, `falta_*`, `faltas_json`, `hechos`, `garantias_retenidas`, `inventario_grua`, `foto_placa_url`, `foto_contexto_url`, `foto_documento_url`, `estado`, `sincronizado_en`, `creado_en_dispositivo` | Conviven dos esquemas: columnas `falta_*` (boleta de una falta) y `faltas_json` (varias). `sin_placas` se agregó junto con el soporte de múltiples faltas en `0006_multi_infracciones_soporte.sql` |
| `boleta_calificaciones` | `id` | `boleta_id`, `juez_id`, `cobro_id`, `nombre_infractor`, `valor_uma`, `total_uma`, `monto_base`, `calificada_en` | FK a `boletas_infracciones`, `users`, `cobros`. Una calificación por boleta |
| `boleta_calificacion_detalles` | `id` | `calificacion_id`, `orden`, `falta_id_origen`, `fundamento_legal`, `descripcion`, `categoria`, `monto_min_uma`, `monto_max_uma`, `uma_asignada`, `subtotal_pesos` | FK a `boleta_calificaciones`. Copia los datos del catálogo al momento de calificar |
| `agentes_transito_tokens` | `id` | `agente_id`, `token_hash`, `expira_en`, `ultimo_uso_en`, `revocado_en` | FK a `agentes_transito`. Guarda el **hash**, nunca el token |

#### App de tránsito y plataforma (4 tablas)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `agentes_transito` | `id` | `placa` (único), `nombre_completo`, `password_hash`, `rol`, `sector`, `activo` | `rol` distingue agente y jefe de turno. Un agente inactivo no puede iniciar sesión |
| `boleta_checklist_item` | `id` | `categoria_boleta`, `etiqueta_casilla`, `catalogo_infraccion_id` | FK a `catalogo_infracciones`. Respaldan el checklist cuando el catálogo de la app está vacío |
| `parametros_app` | `id` | `clave` (único), `valor`, `descripcion`, `tipo`, `editable` | Almacena los 6 parámetros de la app **y** el JSON de `permisos_operador_ventanilla` |
| `parametros_sistema` | `id` | `clave` (único), `valor`, `descripcion`, `actualizado_en` | Parámetros técnicos no editables desde el panel |
| `ci_sessions` | `id` (varchar) | `ip_address`, `timestamp`, `data` | Única tabla creada solo por `supabase/migrations/0003_ci_sessions.sql`. Necesaria para serverless |

#### Auditoría (1 tabla)

| Tabla | PK | Columnas clave | Llaves y notas |
|---|---|---|---|
| `auditoria` | `id` | `entidad`, `entidad_id`, `accion`, `usuario_id`, `fecha`, `detalle` (JSON), `registro_borrados` (JSON) | FK a `users`. `registro_borrados` se agregó en `000018` y guarda el volcado del registro antes de una baja definitiva |

### 11.3 Política de integridad histórica

El esquema no está normalizado hasta el extremo a propósito. Cinco decisiones que conviene entender antes de «optimizar» la base:

1. **Los montos se congelan.** El tarifario se copia a `solicitudes.monto` al crear el expediente, y el desglose completo del cálculo de una multa se guarda en `cobros.detalle_calculo`. Cambiar una tarifa o un porcentaje de descuento **no** debe alterar un cobro ya generado.
2. **La calificación copia el catálogo.** `boleta_calificacion_detalles` guarda fundamento legal, descripción, mínimos y máximos en el momento de calificar. Una edición posterior del catálogo no reescribe la historia judicial de una boleta.
3. **Las llaves foráneas son `RESTRICT`.** Borrar una concesión con expedientes vinculados falla. Es la conducta buscada: el historial de un trámite no puede desaparecer por un borrado en cascada. La migración `000024` corrigió precisamente un `CASCADE` que sí lo permitía.
4. **Las bajas definitivas se auditan con volcado.** `auditoria.registro_borrados` permite reconstruir qué se eliminó incluso cuando la fila ya no existe.
5. **El estado del expediente se versiona por historial, no por columna.** `solicitudes.estatus` da el estado actual y `historial_estatus` da la traza completa con quién y cuándo hizo cada cambio.

### 11.4 Procedimiento para sincronizar una migración nueva

Cuando se añade una migración de CI4 que cambia el esquema:

1. Crear el archivo en `app/Database/Migrations/` con el número siguiente (`000034`, `000035`, …).
2. Crear el archivo gemelo en `supabase/migrations/` con el número siguiente de esa serie (`0019`, `0020`, …).
3. Marcar en el encabezado de cada uno si son ejecutables entre sí o alternativos, igual que hace `0015_boleta_calificaciones.sql`.
4. Ejecutar `./php82/php.exe spark migrate`.
5. Ejecutar `./php82/php.exe scripts/verificar_esquema_bd.php` y confirmar que «Tablas definidas en el repositorio» e «Tablas existentes» cuadran o que la diferencia está explicada.
6. Ejecutar la suite completa (§13).

Si el resultado del paso 5 no cuadra, no se continúa: el esquema divergente es la causa más común de fallos intermitentes en producción.

---

## 12. Comandos y operación

Todos los comandos se ejecutan desde la raíz del repositorio con el PHP incluido en el proyecto, salvo que el entorno tenga PHP 8.2 o superior en el `PATH`.

### 12.1 Comandos de CI4 usados en el proyecto

| Comando | Para qué |
|---|---|
| `./php82/php.exe spark migrate` | Aplica las migraciones pendientes |
| `./php82/php.exe spark migrate:status` | Muestra el estado de las migraciones |
| `./php82/php.exe spark migrate:rollback` | Revierte el último lote (usar con precaución en producción) |
| `./php82/php.exe spark db:seed DatabaseSeeder` | Ejecuta el seeder maestro |
| `./php82/php.exe spark db:seed <Nombre>Seeder` | Ejecuta un seeder concreto |
| `./php82/php.exe spark demo:limpiar` | Borra los datos de demostración sin tocar los catálogos base |
| `./php82/php.exe spark routes` | Lista las 173 rutas registradas |
| `./php82/php.exe spark key:generate` | Genera la clave de cifrado de la aplicación |
| `./php82/php.exe spark serve` | Servidor de desarrollo en `http://localhost:8080` |

### 12.2 Seeders (11)

| Seeder | Qué carga | Credenciales o datos que produce |
|---|---|---|
| `DatabaseSeeder` | Orquestador: llama a los demás en orden | — |
| `RolesSeeder` | Los cinco roles del sistema | — |
| `UsuariosDemoSeeder` | Cuentas de demostración del panel y del portal | `admin`, `operador1`, `ciudadano`, `juez1` con contraseña `12345678` |
| `AgentesTransitoSeeder` | Agentes de la app móvil | `AGT-204`, `AGT-107` (agentes) y `JEF-001` (jefe de turno), contraseña `transito2026` |
| `JuezCalificadorDemoSeeder` | Juez calificador adicional | **Requiere variables de entorno** `JUEZ_DEMO_USERNAME`, `JUEZ_DEMO_EMAIL` y `JUEZ_DEMO_PASSWORD`; la contraseña debe tener al menos 8 caracteres con letra y dígito. Sin ellas no crea nada |
| `TarifasSeeder` | Tarifas base de los siete trámites | — |
| `ConcesionesSeeder` | Padrón de concesiones de ejemplo | — |
| `ConvocatoriasSeeder` | Convocatorias UR-01 de ejemplo | — |
| `CatalogoInfraccionesSeeder` | Catálogo de infracciones y el valor de UMA en `parametros_sistema` | — |
| `BoletaChecklistSeeder` | Casillas del checklist oficial de la boleta física | — |
| `DemoFuncionamientoSeeder` | Expedientes y boletas de demostración para que el panel no arranque vacío | Contraseña de los ciudadanos demo: `Demo2026` |

Los seeders son **idempotentes por diseño**: se pueden ejecutar dos veces sin duplicar catálogos. `demo:limpiar` revierte solo lo que generó `DemoFuncionamientoSeeder`.

### 12.3 Scripts de operación y diagnóstico

| Script | Para qué |
|---|---|
| `scripts/verificar_esquema_bd.php` | Compara el esquema real con el del repositorio y reporta tablas faltantes o sobrantes, más el conteo de registros de migraciones |
| `scripts/sync_demo_data.php` | Sincroniza los datos de demostración con la base configurada |
| `scripts/test_local.ps1` | Ejecuta la batería de verificación local (pruebas, rutas, esquema) |
| `scripts/smoke_test_local.ps1` | Prueba de humo rápida contra el servidor local |
| `scripts/test_supabase_connection.php` | Verifica conectividad y credenciales contra Supabase |
| `scripts/verificar_montos_catalogo.php` | Comprueba que los montos del catálogo cuadren con la UMA vigente |
| `scripts/probar_calculo_infracciones.php` | Ejercita el cálculo de montos con casos conocidos |
| `scripts/auditar_mapeo_boleta.php` | Audita el mapeo entre el checklist oficial y el catálogo normativo |
| `scripts/check_infracciones_db.php` | Revisa el estado de los datos de infracciones |
| `scripts/acentos_espanol.php` | Detecta y corrige problemas de codificación en textos |
| `scripts/exportar_agentes_web.ps1` | Compila y exporta la app de agentes para web |
| `scripts/localizar_shell_agentes.php` | Ajusta las rutas del shell de la app exportada |
| `scripts/fix_passwords.php` | Regenera hashes de contraseñas de demostración |
| `scripts/test_auth_and_users.php` | Prueba de autenticación y usuarios contra la base configurada |
| `scripts/mostrar_respuesta_endpoint.php` | Muestra la respuesta cruda de un endpoint de la API móvil |
| `scripts/vercel-install.sh` | Instalación de dependencias en el entorno de Vercel |

### 12.4 Puesta en marcha desde cero

1. Copiar `.env.example` a `.env` y completar la sección `DATABASE` y `SUPABASE PROJECT` (§3.1).
2. Generar la clave de cifrado: `./php82/php.exe spark key:generate`.
3. Aplicar migraciones: `./php82/php.exe spark migrate`.
4. Verificar el esquema: `./php82/php.exe scripts/verificar_esquema_bd.php`.
5. Cargar datos base: `./php82/php.exe spark db:seed DatabaseSeeder`.
6. Confirmar las rutas: `./php82/php.exe spark routes` (deben aparecer 173).
7. Levantar el servidor: `./php82/php.exe spark serve`.
8. Iniciar sesión con una cuenta de demostración y recorrer el portal y el panel.

---

## 13. Pruebas y despliegue

### 13.1 Resultado de la suite

Medido el 29-oct-2026 con `./php82/php.exe vendor/bin/phpunit`:

| Métrica | Valor |
|---|---|
| Clases de prueba | **43** |
| Casos de prueba | **438** |
| Aserciones | **1 876** |
| Duración | alrededor de 2 min (02:07 en la corrida de referencia; 01:58 en una repetición del mismo día) |
| Memoria | 32.00 MB |
| Resultado | OK, sin fallos |

### 13.2 Entorno de pruebas

`Config\Database::$tests` define SQLite3 **en memoria** con prefijo `db_` en todas las tablas. Consecuencias prácticas:

- Cada ejecución arranca con una base vacía y se recrea el esquema; por eso las pruebas no dependen de Supabase ni de la red.
- El prefijo `db_` explica por qué los nombres de tabla en pruebas no coinciden literalmente con los de producción, y por qué una consulta escrita a mano con el nombre «real» de la tabla funciona en producción y falla en pruebas.
- El filtro CSRF se retira en `ENVIRONMENT === 'testing'` (§3.5), de modo que las pruebas pueden hacer `POST` sin token.
- `_support/DatabaseTestCase.php` es la clase base: crea el esquema, siembra los catálogos mínimos y limpia entre pruebas.

### 13.3 Las 43 clases de prueba por área

**Controladores — panel administrativo (12)**

| Clase | Qué cubre |
|---|---|
| `Admin\AccionesSeguimientoTest` | Acciones de seguimiento ofrecidas por estado, y que las especializadas no salgan por el endpoint genérico |
| `Admin\AppTransitoAdminTest` | Configuración de la app, agentes, catálogo y edición de boletas |
| `Admin\ConcesionesAdminTest` | CRUD del padrón y bloqueo de baja con expedientes vinculados |
| `Admin\ConvocatoriasAdminTest` | Publicación, evaluación, selección y bloqueo de baja con postulaciones |
| `Admin\DesgloseSistemaTest` | Todas las métricas de `DesgloseSistema` y sus enlaces filtrados |
| `Admin\DocumentosAdminTest` | Almacén de documentos y validación de archivos |
| `Admin\PermisosOperadorTest` | Los 14 permisos: resolución por prefijo más largo, invariantes y efecto del guardado |
| `Admin\SesionesAppAdminTest` | Estados de token (vigente, vencido, revocado) y que el token nunca se exponga |
| `Admin\SolicitudPresencialTest` | Flujo de tres pasos del alta en ventanilla y detección de homonimia |
| `Admin\UsuariosControllerTest` | Alta, edición, activación y restricción del operador sobre cuentas internas |
| `Admin\ValidacionUr05Test` | Los 3 criterios de validación del permiso de cierre de calle |
| `Admin\VerificacionesAdminTest` | Agenda de inspecciones y resultados |

**Controladores — ciudadano (7)**

| Clase | Qué cubre |
|---|---|
| `Ciudadano\TramitesControllerTest` | API interna del portal: crear, consultar, agendar y seleccionar |
| `Ciudadano\TramitesValidacionRedirectTest` | Redirecciones ante validación fallida en los formularios de trámite |
| `Ciudadano\TramiteCargaDescargaFormTest` | Formulario, cálculo de monto y resumen del T-07 |
| `Ciudadano\TramiteCesionConcesionValidationTest` | Validación del T-06 y de la concesión de origen |
| `Ciudadano\TramiteConcesionTransporteControllerTest` | Alta del T-01 |
| `Ciudadano\TramiteDespintadoControllerTest` | Formulario, cita y resultado del T-02 |
| `Ciudadano\TramiteOrdenPlaqueoControllerTest` | Alta del T-03 |
| `Ciudadano\PortalTramitesT06ViewRenderTest` | Renderizado del portal con la bandera del T-06 en ambos estados |

**Controladores — cobros, juez, documentos y API (6)**

| Clase | Qué cubre |
|---|---|
| `CobrosControllerTest` | Listado, cobro, completar y cancelar en las dos superficies |
| `CondonacionControllerTest` | Formulario y aplicación de la condonación, con autorización y control de versión |
| `DocumentosPagoTest` | Hoja de pago de solicitud y de boleta, y el permiso de propiedad |
| `Juez\JuezCalificadorAccessTest` | Acceso del juez y rechazo de los demás roles |
| `Api\InfraccionesApiControllerTest` | Checklist, parámetros y contrato de sincronización |
| `Api\ApiAgentesTransitoYBoletasTest` | Login de agente, emisión de token y validación de lote |

**Autenticación (2)**

| Clase | Qué cubre |
|---|---|
| `Auth\AuthFormsRenderTest` | Renderizado de los siete formularios de autenticación |
| `Auth\RegistroCurpOpcionalTest` | CURP opcional en el registro y vínculo con trámites previos |

**Librerías (11)**

| Clase | Qué cubre |
|---|---|
| `Libraries\CobroServiceTest` | Ciclo completo del cobro, idempotencia y sincronización con la solicitud |
| `Libraries\CondonacionRecargoTest` | Aritmética en centavos, importes aplicados y totales |
| `Libraries\CorteCajaServiceTest` | Totales del corte y del CSV |
| `Libraries\CalificarBoletaServiceTest` | Vista previa, calificación y generación del cobro |
| `Libraries\EstadoSolicitudServiceTest` | Máquina de estados de los siete trámites |
| `Libraries\HojaPagoServiceTest` | Hoja de pago de solicitud y de boleta |
| `Libraries\TarifarioServiceTest` | Cálculo por periodo y número de camiones, y detección de importes provisionales |
| `Libraries\ResolverMontoInfraccionServiceTest` | Descuento por pronto pago, recargo por mora y conteo de días hábiles |
| `Libraries\ReferenciaBancariaProvisionalTest` | Generación, validación y normalización de la referencia |
| `Libraries\AlmacenamientoLocalTest` | Firma HMAC, vigencia y rechazo de rutas con `..` |
| `Libraries\CuentaCiudadanoServiceTest` | Alta con y sin acceso, vínculo y habilitación |

**Vistas, datos y despliegue (4)**

| Clase | Qué cubre |
|---|---|
| `Views\EnlacesEstaticosTest` | Todo enlace estático de las vistas resuelve contra el mapa de rutas: detecta enlaces a rutas inexistentes antes de que los encuentre un usuario |
| `Views\VistasInyectadasRenderTest` | Barrido de renderizado de las vistas con datos inyectados |
| `Database\DemoFuncionamientoSeederTest` | Idempotencia y contenido del seeder de demostración |
| `Deploy\EnrutadoServerlessTest` | Enrutado en el entorno serverless: `SCRIPT_NAME`, rutas de assets y shells de la app de agentes |

### 13.4 Gates de verificación y cuándo se ejecutan

| Gate | Comando | Cuándo |
|---|---|---|
| Suites de PHP | `./php82/php.exe vendor/bin/phpunit` | Antes de cada commit |
| Catálogo de rutas | `./php82/php.exe spark routes` | Al añadir o cambiar rutas |
| Esquema de base | `./php82/php.exe scripts/verificar_esquema_bd.php` | Tras migrar o desplegar |
| App móvil | `node tests/auth-flow.test.cjs` y `npx tsc --noEmit` | Al cambiar la app |
| Bundle local | `scripts/test_local.ps1` | Antes del despliegue |

### 13.5 Despliegue en Vercel

**Archivos que gobiernan el despliegue**

| Archivo | Papel |
|---|---|
| `vercel.json` | Tabla de `rewrites` y cabeceras de seguridad |
| `api/index.php` | Punto de entrada serverless; equivalente a `public/index.php` con `FCPATH` apuntado a `public/` |
| `scripts/vercel-install.sh` | Instalación de dependencias en el entorno de compilación |

**Tabla de reescritura** (el orden importa: la primera coincidencia gana)

| Patrón | Destino | Efecto |
|---|---|---|
| `/assets/(.*)` | `/public/assets/$1` | Assets del panel y del portal |
| `/(css|js|img)/(.*)` | `/public/$1/$2` | Estáticos heredados |
| `/(favicon\.ico|robots\.txt|.*\.webp)` | `/public/$1` | Iconos, robots e imágenes |
| `/agentes/_expo/(.*)` | `/public/agentes/_expo/$1` | Bundle de la app exportada |
| `/agentes/assets/(.*)` | `/public/agentes/assets/$1` | Assets de la app |
| `/agentes/fonts/(.*)` | `/public/agentes/fonts/$1` | Tipografías de la app |
| `/agentes/favicon\.ico` | `/public/agentes/favicon.ico` | Icono de la app |
| `/agentes` | `/public/agentes/index.html` | Shell estático de la app |
| `/([A-Za-z0-9._-]+\.ttf)` | `/public/$1` | Tipografías en la raíz |
| `/(.*)` | `/api/index.php` | Todo lo demás entra por CI4 |

**Consecuencia de la whitelist.** Cualquier recurso estático nuevo que no esté contemplado en la tabla de arriba no se sirve desde el sistema de archivos: cae en `/(.*)` y lo recibe CodeIgniter, que responderá 404 porque no existe una ruta para él. Cuando una imagen o tipografía «desaparece» en producción pero se ve en local, el problema está en `vercel.json`, no en el HTML.

**Corrección de `SCRIPT_NAME`.** Vercel invoca `api/index.php` como función, así que `SCRIPT_NAME` llega como `/api/index.php` y CI4 recortaría ese prefijo de la URI de la petición, rompiendo todas las rutas. `api/index.php` reescribe `SCRIPT_NAME` y `PHP_SELF` a `/index.php` antes de arrancar el framework. Además define `CI_ENVIRONMENT` como `production` si no viene del entorno y elimina la cabecera `X-Powered-By`.

**Diferencias obligadas respecto de un servidor tradicional**

| Aspecto | Servidor tradicional | Vercel |
|---|---|---|
| Sesión | Archivos en `writable/session` | Tabla `ci_sessions` |
| Puerto de base de datos | 5432 | 6543 (pooler PgBouncer) |
| `forceGlobalSecureRequests` | Puede ser verdadero | Debe ser falso |
| Escritura en disco | Persistente | Efímera: **solo sirve para el respaldo local**, no para datos que deban sobrevivir |
| Caché en memoria | Vive entre peticiones | Vive lo que dura la petición |

La tercera fila es la que más afecta al mantenimiento: cualquier dato que se escriba en `writable/` o en `public/uploads/` desde el contenedor **puede perderse** en el siguiente despliegue. Por eso los documentos de expediente van a Supabase Storage y solo las evidencias de boleta usan la ruta pública local.

### 13.6 Verificación de seguridad previa a producción

| Comprobación | Resultado esperado |
|---|---|
| Variables de entorno con claves de servicio | Nunca en el repositorio; solo en el panel de Vercel |
| Políticas RLS en Supabase | Activas en las tablas que consume la app con la clave `anon` |
| `encryption.key` | Definida y estable: cambiarla invalida los valores cifrados |
| Rol de demostración en producción | Eliminado o con contraseña rotada |
| `CI_ENVIRONMENT` | `production` |
| Barra de depuración | Invisible |
| Mensajes de error | Genéricos al usuario, detallados solo en `writable/logs/` |

Para una revisión profunda de seguridad antes de publicar cambios, el proyecto se somete a la revisión **L3** y luego se hace `push` a `main`.

---

## Mantenimiento de este documento

| Cuándo se actualiza | Qué sección |
|---|---|
| Se añade o cambia una ruta | §10 y el inventario canónico |
| Se añade un controlador, modelo o librería | §4, §5, §6 y el inventario canónico |
| Se añade una migración | §11.1 y §11.2 |
| Cambia la configuración de entorno | §3 |
| Cambia el flujo de cobros o de estados | §6.1 y §6.4 |
| Cambia la API móvil | §9 y, en consecuencia, la documentación de la app |
| Se ejecuta la suite con cifras distintas | §13.1 y el inventario canónico |

La regla es una sola: **el código es la fuente de verdad**. Toda cifra de este documento se obtiene ejecutando el comando indicado, no copiándola de una versión anterior. Cuando una cifra no se pueda medir, se marca con su fecha de medición en lugar de presentarla como vigente.
