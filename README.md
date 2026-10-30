# Sistema Integral de Ventanilla Única de Movilidad y Transporte — Uriangato

> **Estado del documento:** actualizado al 29 de octubre de 2026
> **Alcance:** puerta de entrada al proyecto: qué es, en qué estado está, cómo se levanta en local, cuentas de demostración, cómo se verifica y dónde se documenta cada tema a detalle
> **Fuente de verdad:** código del repositorio (`app/`, `mobile/agentes-transito/`)
> **Documentos relacionados:** [General](./DOCUMENTACION_GENERAL.md) · [Backend](./DOCUMENTACION_BACKEND.md) · [Portal y Panel](./DOC_PORTAL_PANEL.md) · [App Tránsito](./DOCUMENTACION_APP_TRANSITO.md) · [Metodología](./DOC_METODOLOGIA.md)

Plataforma gubernamental integral para la digitalización de trámites, gestión de expedientes administrativos y supervisión de vialidad y transporte en el municipio de Uriangato, Guanajuato.

[![Producción Vercel](https://img.shields.io/badge/Vercel-Producción%20Online-black?logo=vercel)](https://app-residencias.vercel.app/)
[![App Agentes Web](https://img.shields.io/badge/Expo%20Web-App%20Agentes-4630EB?logo=expo)](https://app-residencias.vercel.app/agentes/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7.4-EF4223?logo=codeigniter)](https://codeigniter.com)
[![PostgreSQL](https://img.shields.io/badge/Supabase-PostgreSQL%2015+-3ECF8E?logo=supabase)](https://supabase.com/)
[![React Native](https://img.shields.io/badge/React%20Native-0.86.3-61DAFB?logo=react)](https://reactnative.dev/)
[![Expo](https://img.shields.io/badge/Expo%20SDK-57.0.30-000020?logo=expo)](https://expo.dev/)
[![React](https://img.shields.io/badge/React-19.2.3-61DAFB?logo=react)](https://react.dev/)
[![TypeScript](https://img.shields.io/badge/TypeScript-6.0.3-3178C6?logo=typescript)](https://www.typescriptlang.org/)

---

## 📌 Enlaces Rápidos a la Documentación Oficial

| Documento | Qué responde | Audiencia |
|---|---|---|
| 📘 [**DOCUMENTACION_GENERAL.md**](./DOCUMENTACION_GENERAL.md) | Visión macro de las tres ramas, glosario canónico, catálogo oficial de los 7 trámites, ciclo de vida del expediente, esquema de datos en resumen, instalación, despliegue y operación | Dirección, jurado, desarrollador nuevo |
| ⚙️ [**DOCUMENTACION_BACKEND.md**](./DOCUMENTACION_BACKEND.md) | **Única fuente de verdad técnica**: ciclo de la petición, configuración y entorno, los 30 controladores, 24 modelos, 22 librerías de servicio, 5 filtros y RBAC, API REST móvil, catálogo completo de rutas, migraciones, diccionario de las 26 tablas y comandos de operación | Backend / mantenimiento |
| 🖥️ [**DOC_PORTAL_PANEL.md**](./DOC_PORTAL_PANEL.md) | Fichas funcionales de las **64 pantallas web** (portal ciudadano, panel administrativo, panel del juez) más índices de navegación, módulo de cobros COB-01 a COB-12, CRUD de catálogos, permisología del operador, seguridad de la superficie y diseño responsivo | Operación, UX, QA |
| 📱 [**DOCUMENTACION_APP_TRANSITO.md**](./DOCUMENTACION_APP_TRANSITO.md) | App de agentes: pantallas, componentes, contextos, *offline-first*, contratos de los cinco endpoints, respaldo de lectura en Supabase, catálogo de 78 casillas en UMA y compilación | Móvil / campo |
| 🎓 [**DOC_METODOLOGIA.md**](./DOC_METODOLOGIA.md) | Metodología, fases con su evidencia, trazabilidad con el informe de Residencia Profesional y mantenimiento documental | Residencia profesional |

> Cada documento abre con su propia cabecera de estado, alcance y fuente de verdad. Si una cifra de este README discrepa de un documento técnico, gana el documento técnico; si discrepa del código, gana el código.

---

## 🏛️ Componentes del Sistema

El ecosistema integra tres ramas de software sobre una misma base PostgreSQL en **Supabase**:

1. **Backend Centralizado (API & Core).** CodeIgniter 4.7.4 sobre PHP 8.2 en arquitectura *serverless* (Vercel). Concentra la lógica de negocio, las validaciones documentales, el tarifario, la máquina de estados del expediente, el motor de cobros, la pasarela bancaria simulada y los endpoints REST que consume la app de campo. **173 rutas registradas** (96 GET, 71 POST, 5 OPTIONS y 1 HEAD) sobre **30 controladores**.

2. **Portal Ciudadano y Panel Administrativo Web.**
   * **Portal Ciudadano (`/portal/`):** plataforma responsiva donde el ciudadano y el concesionario gestionan los 7 trámites oficiales (UR-TT-T-01 a UR-TT-T-07), siguen su expediente, pagan en línea y descargan su hoja de pago. El ciudadano **se autorregistra**; nadie tiene que darlo de alta.
   * **Panel Administrativo (`/admin/`):** consola para funcionarios con acceso por rol (`administrador`, `operador_ventanilla`) y **14 permisos configurables por sección** que el administrador puede conceder o retirar sin tocar código; tableros analíticos con desglose navegable, validación de expedientes con comentario obligatorio, **radicación presencial** para quien acude a ventanilla, auditoría inmutable y CRUD de tarifas, concesiones, convocatorias, formatos, usuarios y agentes de tránsito. Ninguna concesión ni convocatoria con expedientes vinculados se borra: se da de baja cambiando su estatus, para que el historial del ciudadano sobreviva.
   * **Panel del Juez Calificador (`/juez/`):** bandeja de boletas de infracción, **calificación** (con vista previa del cálculo antes de firmar) y **mesa de cobros** con el mismo listado del panel: recibos, descuentos por pronto pago, recargos, condonaciones, corte de caja y lugar de pago. **El juez califica y cobra**; son dos actos distintos y ambos están en su panel.
   * **Módulo de cobros (COB-01 … COB-12):** cada solicitud en firme o boleta calificada genera un **cobro** con folio propio (`COB-AAAA-NNNNNN`) y un historial de movimientos (`REC-AAAA-NNNNNN`); el sistema conserva ambos folios en lugar de sobrescribir el registro. Incluye condonación de recargos con trazabilidad, corte de caja por día/semana/mes/rango con exportación CSV y la **hoja de ayuda para el pago** imprimible desde la solicitud, la boleta y el portal.

3. **App de Agentes de Tránsito (`/agentes/`).** Expo SDK 57 / React Native 0.86 / TypeScript, instalable en Android o ejecutable como Web SPA. Opera *offline-first*: cataloga la infracción, calcula el monto en UMA, toma fotografías geolocalizadas y encola la boleta; cuando vuelve la red, sincroniza contra el backend municipal y deja el folio de campo listo para cobro en ventanilla. Supabase interviene solo como **respaldo de lectura** del catálogo y los parámetros (con RLS): las boletas y evidencias viajan exclusivamente al backend municipal, que es quien valida que la boleta pertenece al agente del token.

> **Acceso rápido a la app de campo:** además de la URL directa, el enlace `/agentes` está en la pantalla de acceso (`/auth/login`), en la portada pública y en el primer renglón de la sección *App Tránsito* de la barra lateral del panel. Abre en pestaña nueva porque la SPA conserva su propia sesión de agente.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnologías Principales |
|---|---|
| **Backend & Core** | PHP 8.2 (Strict Types), CodeIgniter 4.7.4 (`^4.5` en `composer.json`), Composer |
| **Bases de Datos** | PostgreSQL 15+ (Supabase) con PgBouncer Pooler (puerto 6543); SQLite en local y en pruebas |
| **Almacenamiento** | Supabase Storage con tres buckets privados (`documentos`, `formatos`, `ine`) y respaldo local firmado en `writable/uploads/storage` |
| **Criptografía** | SHA-256 inmutable de archivos, BCRYPT para contraseñas, HMAC-SHA256 para enlaces de descarga (1 h de vigencia) |
| **Frontend Web** | Bootstrap 5.3.2, Bootstrap Icons 1.11.1, Plus Jakarta Sans, hoja propia *mobile-first* con design tokens (sin *bundler*) |
| **App Móvil & SPA** | React Native 0.86.3, Expo SDK 57.0.30, React 19.2.3, TypeScript 6.0.3, `expo-router` 57.0.28, `@supabase/supabase-js` 2.117.2, `@react-native-async-storage/async-storage` 2.2.0, `@react-native-community/netinfo` 12.0.1, `expo-secure-store`, `expo-camera`, `expo-location`, `expo-font` |
| **Despliegue & Cloud** | Vercel Serverless (`vercel-php@0.7.4`), CI/CD por Git; EAS para el APK de agentes |
| **Pruebas** | PHPUnit 10.5 sobre arnés SQLite aislado: **43 clases, 410 métodos, 438 casos y 1 876 aserciones** (corrida del 29-oct-2026; entre 2 y 3 min según la carga); verificador propio de la app (`node tests/auth-flow.test.cjs`, 18 casos) y `npx tsc --noEmit` |

---

## 🚀 Inicio Rápido en Entorno Local

### 1. Clonar e Instalar Dependencias
```bash
git clone https://github.com/soportealoraapp/App-Residencias-Ventanilla.git
cd App-Residencias-Ventanilla
composer install
```

### 2. Configurar Variables de Entorno
Copia `.env.example` a `.env` y define tus credenciales de Supabase:
```bash
cp .env.example .env
```
*(El diccionario de variables está en el propio [`.env.example`](./.env.example), agrupado por sección: entorno, app, base de datos —con los tres formatos que acepta el proyecto—, Supabase, cifrado, CSRF, cookies, *feature flags* y correo. Nunca se versiona un `.env` real.)*

> **Documentos de los trámites:** si `SUPABASE_URL` y `SUPABASE_SERVICE_ROLE_KEY` están configurados, los archivos se suben a Supabase Storage. Si no (lo normal en local), se guardan en `writable/uploads/storage/` (carpeta privada, no accesible por web) y se descargan con enlaces firmados que caducan en 1 hora. Puedes cambiar la carpeta con `STORAGE_LOCAL_PATH`.

### 3. Migraciones y Base de Datos
```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

> ⚠️ **Cada vez que hagas `git pull`, vuelve a ejecutar `php spark migrate`.** Si falta una migración (por ejemplo la tabla de tokens de agentes), el login de la app móvil falla. Si el equipo usa la base en Supabase, aplica también los archivos nuevos de `supabase/migrations/` en el **SQL Editor** de la plataforma.
>
> En Windows, si `php` no está en el PATH, usa el PHP incluido en el repo: `./php82/php.exe spark migrate`.
>
> Para comprobar que la base real no se haya quedado atrás respecto al repositorio: `./php82/php.exe scripts/verificar_esquema_bd.php` (solo lectura; agrega `--formato=sql` para que imprima el DDL faltante).

### 4. Compilar la App Móvil (Expo Web)
```bash
cd mobile/agentes-transito
npm install --legacy-peer-deps
cd ../..
powershell -File scripts/exportar_agentes_web.ps1
```
> No uses `npx expo export` suelto. El script exporta a `public/agentes`, vuelve a copiar las fuentes de iconos `.ttf` que Expo borra en el export (hoy son 19) y pasa `scripts/localizar_shell_agentes.php`, que deja el shell HTML en español (`lang`, `<title>`, aviso `noscript`) y **termina con código de error si todavía queda texto en inglés**. Los mensajes de las pantallas no viven en el shell: vienen en el `entry-*.js`, así que si algo sale en inglés hay que corregirlo en el código de `mobile/agentes-transito/src` y volver a exportar.

### 5. Iniciar Servidor
```bash
./php82/php.exe spark serve --host 0.0.0.0 --port 8080
```
Usa `--host 0.0.0.0` para que el servidor también acepte conexiones de tu red local (el teléfono con Expo Go). Con un simple `php spark serve` solo escucha en `localhost` y **la app en el celular no podrá conectarse**. Si Windows pregunta por el firewall, permite el acceso en **redes privadas**.

Accede desde tu navegador:
* Portal Ciudadano: [http://localhost:8080/portal/dashboard](http://localhost:8080/portal/dashboard)
* Panel Administrativo: [http://localhost:8080/admin/dashboard](http://localhost:8080/admin/dashboard)
* Panel del Juez: [http://localhost:8080/juez](http://localhost:8080/juez)
* App Agentes Web: [http://localhost:8080/agentes/](http://localhost:8080/agentes/)

### 6. Comprobaciones de Sanidad
```bash
./php82/php.exe spark routes                  # deben aparecer 173 rutas registradas
./php82/php.exe scripts/verificar_esquema_bd.php   # esquema real vs. repositorio
```
> **Desfase conocido de migraciones:** el repositorio tiene **33 migraciones de CodeIgniter** (`app/Database/Migrations/`, hasta `000033`) y **19 archivos SQL de Supabase** (`supabase/migrations/`, hasta `0018`). El procedimiento de sincronización está en [BACKEND §11](./DOCUMENTACION_BACKEND.md); no se resuelve con un solo comando.

### 7. Probar la App de Agentes con Expo (navegador o celular)
Con el backend del paso 5 corriendo, en otra terminal:
```bash
cd mobile/agentes-transito
npx expo start -c
```
* El celular y la PC deben estar en la **misma red Wi-Fi**.
* En desarrollo la app detecta sola la dirección del backend (en el navegador usa `localhost:8080` y en el celular la IP de la PC que ejecuta Expo), así que no hace falta cambiar la IP del `.env` cada vez que cambias de red. En la consola de Expo aparece `[config] API_BASE_URL = ...` para confirmarlo.
* En la pantalla de login hay botones de **acceso rápido de prueba** (`AGT-204`, `AGT-107`, `JEF-001`, contraseña `transito2026`). Solo aparecen con `EXPO_PUBLIC_APP_ENV=development` y requieren que el backend esté corriendo y que exista el seed `AgentesTransitoSeeder`.

**Si la app dice "No se pudo conectar con el servidor":**
1. ¿El backend está corriendo con `--host 0.0.0.0`?
2. Abre `http://<IP-de-tu-PC>:8080/api/parametros` en el navegador del celular (la IP la ves con `ipconfig`). Debe mostrar un JSON. Si no carga, es el firewall de Windows o están en redes distintas.
3. ¿Corriste `php spark migrate` después del último `git pull`?
4. Reinicia Expo con `npx expo start -c` (las variables `EXPO_PUBLIC_*` se leen al arrancar).

---

## 🔐 Cuentas de Acceso de Demostración

> **Contraseña universal para las cuentas de prueba:** `12345678`

| Usuario | Correo Electrónico | Rol Asignado | Entorno de Acceso | Origen |
|---|---|---|---|---|
| `admin` | `admin@uriangato.gob.mx` | `administrador` | Panel Administrativo (`/admin/`) | `UsuariosDemoSeeder` |
| `operador1` | `operador1@uriangato.gob.mx` | `operador_ventanilla` | Panel Administrativo (`/admin/`) | `UsuariosDemoSeeder` |
| `ciudadano1` | `ciudadano@example.com` | `ciudadano` | Portal Ciudadano (`/portal/`) | `UsuariosDemoSeeder` |
| `juez1` | `juez1@uriangato.gob.mx` | `juez_calificador` | Panel del Juez (`/juez/`) | `UsuariosDemoSeeder` (solo si el rol ya existe) |

> La cuenta `juez1` **califica boletas y registra cobros**: su panel incluye la calificación con vista previa del cálculo y la mesa de cobros con corte de caja, condonaciones y banco. Lo que no hace es administrar catálogos ni usuarios: **cobrar no es administrar**.

### Cuentas de la App de Agentes

| Placa | Nombre | Rol en la app | Contraseña | Origen |
|---|---|---|---|---|
| `AGT-204` | Oficial Carlos Mendoza Ruiz | `operativo` | `transito2026` | `AgentesTransitoSeeder` |
| `AGT-107` | Oficial María Hernández López | `operativo` | `transito2026` | `AgentesTransitoSeeder` |
| `JEF-001` | Coordinador Javier Reyes Cruz | `coordinador` | `transito2026` | `AgentesTransitoSeeder` |

> **El ciudadano se autorregistra** en el portal ([`/auth/register`](http://localhost:8080/auth/register) — también funciona el atajo corto [`/register`](http://localhost:8080/register)); el sistema solo produce cuentas con rol `ciudadano`.
>
> Lo que **no** puede crearse desde el registro público son cuentas de **operador**, **administrador** o **agente de tránsito**: esas las da de alta el administrador desde **Panel → Usuarios y ciudadanos** (`/admin/usuarios`) y **Panel → App Tránsito → Agentes** (`/admin/app-transito`), porque son personal con capacidad de sanción y su identidad debe verificarse físicamente.
>
> Para una cuenta de juez en una instalación distinta a la demo, `JuezCalificadorDemoSeeder` la crea **solo si le pasas las credenciales por variables de entorno** (`JUEZ_DEMO_USERNAME`, `JUEZ_DEMO_EMAIL`, `JUEZ_DEMO_PASSWORD` obligatorias; `JUEZ_DEMO_NOMBRE` opcional) y exige contraseña de 8 caracteres con al menos una letra y un número. Nunca se siembra una contraseña fija en producción.

---

## 🎬 Datos de Demostración ("en funcionamiento")

Para presentaciones y revisiones, `DemoFuncionamientoSeeder` llena el sistema con datos ficticios realistas: 10 ciudadanos, 24 solicitudes de los trámites en distintos estatus (con su historial), una convocatoria abierta y 26 boletas de infracción de las últimas 4 semanas. Las fechas son relativas al día en que se ejecuta.

```bash
./php82/php.exe spark db:seed DemoFuncionamientoSeeder   # crear (se puede repetir: no duplica)
./php82/php.exe spark demo:limpiar                       # quitar exactamente lo que creó
```

* Requiere haber corrido antes `spark migrate` y `spark db:seed DatabaseSeeder` (roles, catálogo, agentes).
* Los ciudadanos demo entran con su CURP en minúsculas como usuario (p. ej. `ragl880214mgtmrr03`) y la contraseña `Demo2026`.
* **En Supabase (versión hosteada):** desde PowerShell, en la raíz del repo, define las variables de la conexión **Session pooler** (Supabase → *Connect*) solo para esa terminal y ejecuta el mismo comando. No se guardan en ningún archivo:

  ```powershell
  $env:DATABASE_DEFAULT_DBDRIVER = "Postgre"
  $env:POSTGRES_HOST     = "aws-0-<region>.pooler.supabase.com"
  $env:POSTGRES_PORT     = "5432"
  $env:POSTGRES_DATABASE = "postgres"
  $env:POSTGRES_USER     = "postgres.<id-del-proyecto>"
  $env:POSTGRES_PASSWORD = "<contraseña de la base>"
  .\php82\php.exe spark db:seed DemoFuncionamientoSeeder
  ```

  Al terminar cierra esa terminal (o borra las variables) para que tu `spark serve` local siga usando SQLite.

**Los 11 seeders del proyecto:**

| Seeder | Qué siembra |
|---|---|
| `DatabaseSeeder` | Orquesta el orden base de una instalación nueva |
| `RolesSeeder` | Los cinco roles del sistema |
| `UsuariosDemoSeeder` | `admin`, `operador1`, `ciudadano1` y `juez1` |
| `TarifasSeeder` | Tarifario base de los trámites |
| `AgentesTransitoSeeder` | `AGT-204`, `AGT-107`, `JEF-001` |
| `CatalogoInfraccionesSeeder` | Catálogo de infracciones del backend |
| `BoletaChecklistSeeder` | Casillas del *checklist* de la app |
| `ConcesionesSeeder` | Concesiones de ejemplo |
| `ConvocatoriasSeeder` | Convocatoria de ejemplo |
| `DemoFuncionamientoSeeder` | Población de demostración completa |
| `JuezCalificadorDemoSeeder` | Cuenta de juez por variables de entorno |

---

## 🧪 Pruebas Automatizadas

### Backend (PHPUnit)

```bash
./vendor/bin/phpunit
```
En Windows, si `php` no está en el PATH: `./php82/php.exe vendor/bin/phpunit`.

La suite tiene **43 clases y 410 métodos declarados**; la corrida completa del **29-oct-2026** reportó **438 casos ejecutados** (los proveedores de datos expanden varios métodos) y **1 876 aserciones**, en 2 a 3 minutos según la carga de la máquina (02:07 en la corrida de referencia). Corre contra **SQLite en memoria** con prefijo `db_`, nunca contra la base institucional, y requiere las migraciones aplicadas (`php spark migrate`).

| Área | Qué cubre |
|---|---|
| **Acceso y roles** | Control de acceso por rol, permisos configurables del operador y su efecto real en la pantalla, alta de ciudadano sin acceso, guardas del panel del juez |
| **Trámites** | Validaciones de los 7 trámites, radicación presencial, tarifas dinámicas (incluido el monto por camiones de UR-07 y la validación fina de UR-05), máquina de estados y acciones de seguimiento |
| **Cobros** | Folios `COB-`/`REC-`, recargos y descuento por pronto pago, condonación con trazabilidad, corte de caja, hoja de pago y permiso fino (el ciudadano solo imprime lo suyo y nunca multas) |
| **Boletas y API** | Calificación de boletas, endpoints REST de agentes, folio ajeno, preflight CORS y sincronización |
| **Plataforma** | Firma HMAC y anti-travesía del almacenamiento local, enrutado serverless, desglose navegable del tablero y candado que impide borrar concesiones o convocatorias con expedientes vinculados |
| **Integridad de vistas** | Cotejo de los **155 enlaces literales** de las vistas (47 destinos únicos) contra el mapa de rutas real y barrido de renderizado (título, cabecera y número correcto de filas) |

### App de Agentes

Se verifica por separado, sin Expo ni dispositivo:

```bash
cd mobile/agentes-transito
node tests/auth-flow.test.cjs     # 18 casos: restauración de perfil, token, acceso y envío de boletas
npx tsc --noEmit                  # tipos del cliente (salida limpia)
```

---

## 🗺️ Mapa del Repositorio

| Ruta | Propósito |
|---|---|
| `app/` | Aplicación CodeIgniter: `Controllers` (30), `Models` (24), `Libraries` (22), `Filters` (5), `Views` (76), `Config`, `Database` (33 migraciones, 11 seeders), `Entities`, `Helpers`, `Interfaces`, `Language`, `Commands` |
| `api/index.php` | Punto de entrada serverless: Vercel reescribe todo aquí y CodeIgniter resuelve la ruta real |
| `mobile/agentes-transito/` | App Expo / React Native de agentes de tránsito (rutas en `app/`, lógica en `src/`) |
| `public/` | Raíz web: `index.php`, `css/`, `js/`, `uploads/`, imágenes y `agentes/` (export del Expo web) |
| `supabase/` | `migrations/` (19 SQL), `demo/` (scripts para el SQL Editor) y utilidades |
| `scripts/` | Operación: exportación de la SPA, localización del shell, verificación de esquema, sincronización de demo, pruebas locales y auditorías de catálogo |
| `tests/` | Suites PHPUnit (`app/Controllers`, `app/Libraries`, `app/Deploy`, `app/Database`, `app/Views`) y soporte de arranque |
| `writable/` | `uploads/storage/` (documentos privados), `cache/`, `logs/`, `session/` |
| `php82/` | PHP 8.2.25 portátil para Windows: permite ejecutar `spark` sin instalar PHP |
| `vendor/`, `node_modules/` | Dependencias de Composer y npm (no se versionan) |
| `vercel.json` | Reglas de despliegue y *whitelist* de archivos estáticos |
| `composer.json`, `phpunit.xml.dist`, `spark` | Dependencias PHP, configuración de pruebas y consola de CodeIgniter |
| `iniciar-servidor.bat` / `.ps1` | Arranque del servidor local en Windows |
| `REPORTE_COB-10_2026-10-24.md` | Reporte técnico del corte de la fase de calificación del juez (se conserva histórico) |

---

## 📊 Estado del Sistema y de la Documentación

**Fase cerrada:** el módulo de cobros está terminado y verificado (COB-01 a COB-12), incluida la calificación del juez (COB-10), la condonación de recargos (COB-11) y las acciones de seguimiento por trámite (COB-12). Los tres paneles web y la app de campo están en operación.

**Inventario vigente (medido el 29-oct-2026):**

| Concepto | Cantidad |
|---|---|
| Rutas registradas | 173 (96 GET, 71 POST, 5 OPTIONS, 1 HEAD) |
| Controladores | 30 |
| Vistas | 76 |
| Librerías de servicio | 22 |
| Modelos | 24 |
| Filtros de petición | 5 |
| Tablas en base de datos | 26 |
| Migraciones | 33 PHP (CI4) + 19 SQL (Supabase) |
| Seeders | 11 |
| Permisos configurables del operador | 14 |
| Pantallas web documentadas | 64 |
| Pantallas de la app móvil | 7 |
| Pruebas | 43 clases · 410 métodos · 438 casos · 1 876 aserciones |

**Pendientes conocidos:**

| Tema | Estado |
|---|---|
| Desfase de migraciones Supabase (`0018`) vs. CodeIgniter (`0033`) | Documentado con su procedimiento de sincronización; **no** es un fallo de la suite |
| Medición responsiva | Registrada con fecha **19-oct-2026** sobre 55 pantallas; las pantallas de cobros y del panel del juez son posteriores y deben volver a medirse si se exige evidencia actualizada |
| Pasarela de pago | Simulada (`BanbajioMockGateway`) y referencias bancarias provisionales: el cobro se registra, el abono lo confirma ventanilla |
| Scripts de prueba en la app móvil | No hay `npm test` ni `npm run typecheck`; se ejecutan con los comandos de la sección de pruebas |

**Última revisión documental:** los seis documentos se actualizaron de forma integral con corte al **29-oct-2026**, con la regla de que **el código es la fuente de verdad** y toda cifra lleva su método de medición. El registro de qué se corrigió y cómo se mantiene está en [METODOLOGÍA](./DOC_METODOLOGIA.md) §3.9 y §3.10.

---

## 📱 Interfaz Web Responsiva (Portal y Panel)

Ambas interfaces son *mobile-first*: el ciudadano y el operador pueden usarlas desde un teléfono sin perder funcionalidad.

* **Objetivos táctiles de 44 px** y campos a 16 px (evita el zoom automático de iOS al enfocar un formulario).
* **Portal:** navegación inferior fija tipo app en móvil, con el contenido desplazado para no quedar tapado.
* **Panel:** la barra lateral se convierte en gaveta deslizante (`min(300px, 86vw)`) con fondo oscurecido y barra superior pegajosa; respeta áreas seguras en dispositivos con muesca (`viewport-fit=cover` + `env(safe-area-inset-*)`).
* **Sin texto recortado, medido el 19-oct-2026:** 55 pantallas revisadas en el navegador a 360/320/290 px con detector de desbordes y página de control; 0 desbordes al cierre. La causa raíz era que un hijo de *flexbox* no puede comprimirse por debajo de su contenido, y el `overflow-x: hidden` del contenedor lo ocultaba al no generar barra de desplazamiento.
* **Portada:** la fotografía institucional se ancla y el desenfoque se reduce en pantallas verticales para conservar el sujeto y el contraste del texto sin sacrificar rendimiento.
* **Accesibilidad:** respeta `prefers-reduced-motion`, conserva foco visible por teclado y un modo de impresión que oculta la interfaz para imprimir expedientes.

> ⚠️ **Restricción de despliegue:** en Vercel solo se sirve lo declarado en `vercel.json`; todo lo demás cae en el comodín que entrega la petición a PHP y devuelve 404. **Si agregas una imagen, icono o archivo estático a `public/`, añade su ruta al arreglo `routes`** y verifica la URL ya desplegada (en local `spark serve` sí sirve todo el directorio, así que el fallo no se ve antes de producción).
>
> ⚠️ **Restricción de enrutado (CodeIgniter 4):** una ruta cuyo *from* coincida con el **nombre** ya asignado con `['as' => ...]` a otra ruta **se descarta en silencio**, sin error. Por eso los atajos `/login`, `/register` y `/logout` llevan nombre propio (`acceso`, `registro`, `salida`). Si una ruta definida en `app/Config/Routes.php` no responde, compruébala con `php spark routes` antes de sospechar del despliegue: **declarada no es lo mismo que registrada**.

---

## 📄 Licencia y Marco Institucional

Desarrollado para el **H. Ayuntamiento de Uriangato, Guanajuato** — Dirección de Movilidad y Transporte. Todos los derechos reservados bajo la normativa de gobierno digital del Estado de Guanajuato.
