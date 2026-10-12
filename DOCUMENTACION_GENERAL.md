# Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato
## Documentación Técnica y Operativa General

**Gobierno Municipal de Uriangato, Guanajuato**  
**Dirección de Movilidad y Transporte**  
**Versión del Sistema:** 2.5.0 (Producción / Vercel Serverless + Supabase PostgreSQL)  
**Clasificación:** Documento Técnico Maestro de Arquitectura, Conectividad y Despliegue  
**Fecha de Actualización:** Octubre 2026  

---

## 1. Identificación y Ficha Técnica del Proyecto

* **Nombre Oficial:** Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato.
* **Institución Responsable:** H. Ayuntamiento de Uriangato, Gto. — Dirección de Movilidad y Transporte.
* **Propósito Central:** Digitalizar de extremo a extremo la tramitología de transporte público, permisos de carga y cierres viales, centralizar la supervisión y resolución de expedientes administrativos y dotar al cuerpo de agentes de tránsito de una herramienta móvil para el levantamiento y sincronización de infracciones viales en tiempo real.
* **Público Objetivo:**
  1. **Ciudadanos y Transportistas:** Solicitud de trámites, carga de documentación probatoria, consulta de expedientes en tiempo real y pago digital.
  2. **Personal Administrativo Municipal:** Operadores de ventanilla y directores que validan documentos, agendan inspecciones físicas, emiten dictámenes y dictaminan trámites.
  3. **Agentes de Tránsito:** Oficiales en campo que operan la aplicación móvil para levantar boletas de infracción, capturar evidencia fotográfica georreferenciada y operar tanto conectados como fuera de línea.
* **Marco Jurídico de Referencia:**
  * Ley de Movilidad del Estado de Guanajuato y sus Municipios.
  * Reglamento de Tránsito y Transporte Municipal de Uriangato, Gto.
  * Ley de Ingresos para el Municipio de Uriangato, Gto. (ejercicio fiscal vigente).

---

## 2. Visión Integral del Sistema y Arquitectura de las 3 Ramas

El sistema se compone de tres componentes principales que operan de forma desacoplada pero sincronizada a través de un núcleo de datos común en **Supabase (PostgreSQL + Storage)**:

```mermaid
graph TD
    subgraph CIUDADANIA_Y_ADMINISTRACION [Portal & Panel Web]
        C[Portal Ciudadano Web\nPHP / Bootstrap 5] -->|Sesiones ci_sessions| CI4[Backend Monolítico MVC\nCodeIgniter 4.5.8]
        A[Panel Administrativo\nPHP / Bootstrap 5] -->|RBAC Filter| CI4
    end

    subgraph NUCLEO_CENTRAL [Servicios Centrales & Persistencia]
        CI4 -->|PgBouncer Pooler :6543| DB[(PostgreSQL 15+\nSupabase)]
        CI4 -->|cURL / Storage API REST| STG[Supabase Storage\nBucket: documentos, evidencias]
    end

    subgraph CAMPO_OPERATIVO [Módulo Móvil Operativo]
        M[App Agentes de Tránsito\nExpo SDK 57 / React Native] -->|Supabase JS SDK\nAnon Key| DB
        M -->|Storage Upload\nMultipart| STG
        M -->|HTTP Fetch POST / JSON| CI4_API[API REST CodeIgniter 4\n/api/agentes/registro\n/api/checklist-infracciones]
    end

    CI4_API --> CI4
```

### 2.1. Rama 1: Backend Centralizado (API & Core)
* **Framework:** CodeIgniter 4.5.8 ejecutándose sobre PHP 8.2 en arquitectura Serverless.
* **Funciones:**
  * Gestión del ciclo de vida de los 7 trámites municipales.
  * Control de acceso basado en roles (**RBAC** con filtros `AuthFilter`, `RoleFilter`, `AdminRedirectFilter`).
  * Endpoints REST para registro seguro de agentes (`/api/agentes/registro`) y entrega de catálogos (`/api/checklist-infracciones`).
  * Integración con **Supabase Storage** para la subida, hash SHA-256 y descarga segura de expedientes documentales.
  * Persistencia de sesiones en base de datos (`ci_sessions`) para entornos serverless.

### 2.2. Rama 2: Portal Ciudadano y Panel Administrativo Web
* **Tecnología:** PHP Templates (Views), Bootstrap 5.3.2, Bootstrap Icons, CSS personalizado (`custom.css`), JavaScript nativo.
* **Funciones:**
  * **Portal Ciudadano:** Catálogo de 7 trámites municipales, formularios asistidos, cálculo automático de tarifas/UMA, mock de pasarela de pago bancario, visor de expedientes y módulo "Mi Perfil".
  * **Panel Administrativo:** Dashboard de analítica municipal con KPIs en tiempo real, bandeja de solicitudes con transiciones de estatus reguladas, requerimiento obligatorio de observaciones para prevenciones y rechazos, administración de catálogos (tarifas, concesiones), convocatorias, formatos oficiales y módulo de monitoreo de infracciones de tránsito.

### 2.3. Rama 3: App Móvil para Agentes de Tránsito
* **Tecnología:** React Native 0.86 / Expo SDK 57 / TypeScript, exportada a build nativo y a bundle Web servido en `/agentes/`.
* **Funciones:**
  * Levantamiento de boletas digitales de infracción con cálculo automático de montos según tabulador de UMA vigente.
  * Captura de hasta 3 fotografías obligatorias (placa, contexto, garantía/documento) geolocalizadas mediante GPS.
  * Soporte *Offline-First* con almacenamiento local en `AsyncStorage` y cola de sincronización automática al recuperar conectividad.
  * Registro y autenticación de agentes contra la base de datos centralizada de Supabase.

---

## 3. Pila Tecnológica Exhaustiva (Tech Stack)

| Capa / Módulo | Tecnología | Versión | Propósito en el Proyecto |
|---|---|---|---|
| **Lenguaje Backend** | **PHP** | `8.2.25+` | Lenguaje de tipado estricto (`declare(strict_types=1)`), seguridad y procesamiento del núcleo. |
| **Framework Web** | **CodeIgniter** | `4.5.8` | Framework MVC ligero de alto desempeño, enrutamiento, validación y filtros de seguridad. |
| **Base de Datos** | **PostgreSQL (Supabase)** | `15+` | Base de datos relacional ACID conectada vía Connection Pooler (PgBouncer en puerto `6543`). |
| **Almacenamiento de Archivos** | **Supabase Storage** | `v1` | Buckets privados (`documentos`, `evidencias`) para almacenamiento de PDFs y fotos. |
| **Sesiones Serverless** | **CodeIgniter DatabaseHandler** | Nativo | Persistencia de sesiones en la tabla relacional `ci_sessions`. |
| **Framework Móvil** | **React Native / Expo** | `SDK 57` | Desarrollo de la app móvil y compilación cruzada a Web SPA (`/public/agentes/`). |
| **Librería Móvil Supabase** | **@supabase/supabase-js** | `2.117.2` | Conexión directa desde la app móvil para consultas y subida de evidencia fotográfica. |
| **Estilos Web** | **Bootstrap 5 & Vanilla CSS** | `5.3.2` | Maquetación responsiva, componentes de interfaz y diseño personalizado en `custom.css`. |
| **Iconografía** | **Bootstrap Icons / Ionicons** | `1.11.1 / 15.1` | Iconografía web (`bi bi-*`) e iconografía móvil/web (`@expo/vector-icons`). |
| **Criptografía** | **SHA-256 (PHP `hash_file`)** | Nativo | Huella digital de inmutabilidad y autenticidad para cada archivo subido al expediente. |
| **Plataforma de Despliegue** | **Vercel Serverless** | `v2` | Runtime PHP (`vercel-php@0.7.4`) y distribución global de assets estáticos vía CDN. |

---

## 4. Guía de Instalación y Configuración Local

### 4.1. Prerrequisitos del Entorno de Desarrollo
1. **PHP 8.2 o superior** con las siguientes extensiones habilitadas en `php.ini`:
   * `pdo_pgsql` / `pgsql` (Conexión a PostgreSQL Supabase)
   * `curl` (Llamadas a Storage y APIs externas)
   * `mbstring`, `intl`, `fileinfo`, `gd`, `openssl`
2. **Composer 2.x** para dependencias de PHP.
3. **Node.js 18+ (o 20 LTS)** y `npm` para la app móvil en Expo.
4. **Git** para control de versiones.

### 4.2. Instalación Paso a Paso

#### Paso 1: Clonar el Repositorio
```bash
git clone https://github.com/soportealoraapp/App-Residencias.git
cd App-Residencias
```

#### Paso 2: Instalar Dependencias del Backend PHP
```bash
composer install --no-interaction --prefer-dist
```

#### Paso 3: Configurar Variables de Entorno del Backend
Copia el archivo de ejemplo a la raíz como `.env`:
```bash
cp env .env
```
Edita `.env` con las credenciales de tu proyecto Supabase (ver Sección 5).

#### Paso 4: Ejecutar Migraciones de Base de Datos
Para aplicar las tablas relacionales y el soporte de sesiones:
```bash
php spark migrate
```
*(Opcional: Si estás inicializando una instancia vacía en Supabase, ejecuta los scripts de la carpeta `supabase/migrations/` en el SQL Editor de Supabase en orden numérico).*

#### Paso 5: Instalar y Compilar la App Móvil (Expo Web)
```bash
cd mobile/agentes-transito
npm install --legacy-peer-deps
npx expo export --platform web --output-dir ../../public/agentes --clear
cd ../..
```

#### Paso 6: Iniciar el Servidor de Desarrollo
Para el backend y portal web:
```bash
php spark serve --port 8080
```
La aplicación estará disponible en `http://localhost:8080`:
* Portal Ciudadano: `http://localhost:8080/portal/dashboard`
* Panel Administrativo: `http://localhost:8080/admin/dashboard`
* App de Agentes Web: `http://localhost:8080/agentes/`

Para desarrollar la app móvil con recarga en vivo (Metro):
```bash
cd mobile/agentes-transito
npx expo start
```

---

## 5. Diccionario de Variables de Entorno (`.env`)

### 5.1. Variables del Backend (Raíz `.env`)

| Variable | Tipo | Ejemplo / Valor | Descripción |
|---|---|---|---|
| `CI_ENVIRONMENT` | String | `production` / `development` | Controla la visualización de errores y el modo de ejecución. |
| `app.baseURL` | URL | `https://app-residencias.vercel.app/` | URL canónica base del sistema (debe terminar en diagonal `/`). |
| `app.sessionDriver` | Clase | `CodeIgniter\Session\Handlers\DatabaseHandler` | Manejador de sesiones persistentes en base de datos. |
| `app.sessionSavePath` | String | `ci_sessions` | Nombre de la tabla en PostgreSQL donde se guardan las sesiones. |
| `app.sessionExpiration` | Entero | `7200` | Tiempo de vida de la sesión en segundos (2 horas). |
| `POSTGRES_URL` | DSN | `postgres://postgres.[ref]:[pass]@aws-0-[region].pooler.supabase.com:6543/postgres?sslmode=require` | Conexión PgBouncer para entornos serverless (puerto 6543). |
| `POSTGRES_HOST` | String | `aws-0-[region].pooler.supabase.com` | Host del connection pooler de Supabase. |
| `POSTGRES_PORT` | Entero | `6543` | Puerto de conexión (6543 para pooler, 5432 para directo). |
| `POSTGRES_DATABASE` | String | `postgres` | Nombre de la base de datos en Supabase. |
| `POSTGRES_USER` | String | `postgres.[ref_proyecto]` | Usuario de conexión. |
| `POSTGRES_PASSWORD` | String | `[contraseña_secreta]` | Contraseña de la base de datos Supabase. |
| `SUPABASE_URL` | URL | `https://[ref].supabase.co` | URL base de la instancia del proyecto Supabase. |
| `SUPABASE_ANON_KEY` | JWT | `eyJhbGciOi...` | Clave pública anónima de Supabase. |
| `SUPABASE_SERVICE_ROLE_KEY` | JWT | `eyJhbGciOi...` | Clave privada con permisos de servicio (para backend cURL Storage). |
| `TRAMITE_CESION_CONCESION_ENABLED`| Bool | `false` | Feature flag para activar o desactivar el trámite UR-TT-T-06. |

### 5.2. Variables de la App Móvil (`mobile/agentes-transito/.env`)

| Variable | Tipo | Ejemplo / Valor | Descripción |
|---|---|---|---|
| `EXPO_PUBLIC_API_BASE_URL` | URL | `https://app-residencias.vercel.app` | URL base del backend CodeIgniter 4 para peticiones de API. |
| `EXPO_PUBLIC_SUPABASE_URL` | URL | `https://[ref].supabase.co` | URL del proyecto Supabase para conexión directa del cliente JS. |
| `EXPO_PUBLIC_SUPABASE_ANON_KEY`| JWT | `eyJhbGciOi...` | Llave anónima pública de Supabase. |
| `EXPO_PUBLIC_APP_ENV` | String | `production` | Entorno de ejecución de la app móvil. |

---

## 6. Procedimientos de Despliegue (Vercel + CI/CD)

### 6.1. Configuración de `vercel.json`
El archivo `vercel.json` en la raíz del repositorio orquesta el despliegue serverless dividiendo el tráfico entre el runtime PHP y los activos estáticos:

```json
{
  "version": 2,
  "installCommand": "bash scripts/vercel-install.sh",
  "functions": {
    "api/*.php": {
      "runtime": "vercel-php@0.7.4"
    }
  },
  "routes": [
    { "src": "/assets/(.*)", "dest": "/public/assets/$1" },
    { "src": "/(css|js|img)/(.*)", "dest": "/public/$1/$2" },
    { "src": "/(favicon\\.ico|robots\\.txt|logo-uri\\.webp)", "dest": "/public/$1" },
    { "src": "/agentes/_expo/(.*)", "dest": "/public/agentes/_expo/$1" },
    { "src": "/agentes/assets/(.*)", "dest": "/public/agentes/assets/$1" },
    { "src": "/agentes/fonts/(.*)", "dest": "/public/agentes/fonts/$1" },
    { "src": "/agentes/favicon\\.ico", "dest": "/public/agentes/favicon.ico" },
    { "src": "/agentes(/.*)?", "dest": "/public/agentes/index.html" },
    { "src": "/(.*)", "dest": "/api/index.php" }
  ]
}
```

### 6.2. Script de Instalación Automatizado (`scripts/vercel-install.sh`)
Durante el proceso de build en Vercel, este script ejecuta:
1. Instalación de **Composer** y descarga de dependencias PHP optimizadas (`--no-dev --optimize-autoloader`).
2. Instalación de dependencias de Node.js en `mobile/agentes-transito`.
3. Exportación estática del bundle de Expo hacia `public/agentes/`.
4. Extracción y respaldo de las fuentes tipográficas de iconos en `public/agentes/fonts/`.

### 6.3. Despliegue a Producción
Para desplegar un cambio en producción:
```bash
git add -A
git commit -m "feat/fix: descripcion del cambio"
git push origin main
```
Vercel detecta automáticamente el commit en la rama `main`, ejecuta `scripts/vercel-install.sh`, genera la compilación y publica el sistema de forma atómica en menos de 3 minutos.
