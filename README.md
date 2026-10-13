# Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato

Plataforma gubernamental integral para la digitalización de trámites, gestión de expedientes administrativos y supervisión de vialidad y transporte en el municipio de Uriangato, Guanajuato.

[![Producción Vercel](https://img.shields.io/badge/Vercel-Producción%20Online-black?logo=vercel)](https://app-residencias.vercel.app/)
[![App Agentes Web](https://img.shields.io/badge/Expo%20Web-App%20Agentes-4630EB?logo=expo)](https://app-residencias.vercel.app/agentes/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.5.8-EF4223?logo=codeigniter)](https://codeigniter.com/)
[![PostgreSQL](https://img.shields.io/badge/Supabase-PostgreSQL%2015+-3ECF8E?logo=supabase)](https://supabase.com/)

---

## 📌 Enlaces Rápidos a la Documentación Oficial

* 📘 [**DOCUMENTACION_GENERAL.md**](./DOCUMENTACION_GENERAL.md): Documentación técnica y operativa integral del sistema completo (Visión macro, separación del Portal/Panel Web y la App Móvil de Tránsitos, arquitectura de bases de datos, flujos normativos y guía de despliegue en Vercel).
* ⚙️ [**DOCUMENTACION_BACKEND.md**](./DOCUMENTACION_BACKEND.md): Documentación técnica exhaustiva del **Backend en CodeIgniter 4 / PHP 8.2** (Controladores, Modelos, Servicios, Storage Supabase, Filtros RBAC, Seguridad y API REST).
* 📱 [**DOCUMENTACION_APP_TRANSITO.md**](./DOCUMENTACION_APP_TRANSITO.md): Documentación técnica exhaustiva de la **App Móvil para Agentes de Tránsito en React Native / Expo** (Arquitectura Offline-First, Cámara, GPS, cálculo de sanciones en UMA y sincronización con Supabase).
* 🎓 [**DOC_METODOLOGIA_PORTAL_PANEL.md**](./DOC_METODOLOGIA_PORTAL_PANEL.md): Documento metodológico y memoria de arquitectura enfocado en el **Portal Ciudadano y Panel Administrativo Web**, diseñado como base para reporte de Residencia Profesional.

---

## 🏛️ Componentes del Sistema

El ecosistema digital integra tres ramas de desarrollo comunicadas a través de la infraestructura en la nube de **Supabase**:

1. **Backend Centralizado (API & Core):** Desarrollado en CodeIgniter 4 sobre PHP 8.2 en arquitectura serverless. Controla la lógica de negocio, validaciones documentales, pasarela mock de pago, servicios de tarifario y endpoints REST para clientes externos.
2. **Portal Ciudadano y Panel Administrativo Web:**
   * **Portal Ciudadano (`/portal/`):** Plataforma web responsiva donde los ciudadanos y concesionarios gestionan los 7 trámites oficiales (UR-TT-T-01 al UR-TT-T-07), consultan expedientes y realizan pagos en línea simulados.
   * **Panel Administrativo (`/admin/`):** Consola para funcionarios municipales con control de acceso por roles (`administrador`, `operador_ventanilla`), tableros analíticos en tiempo real, validación de expedientes con comentarios obligatorios, padrón inmutable de auditoría y gestión de catálogos (tarifas, concesiones, convocatorias y formatos).
3. **App Móvil para Agentes de Tránsito (`/agentes/`):** Aplicación desarrollada con Expo SDK 57 / React Native / TypeScript, utilizable en dispositivos móviles o vía Web SPA. Diseñada para operación *Offline-First*, cálculo automático de multas en UMA, captura fotográfica geolocalizada y sincronización directa con Supabase.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnologías Principales |
|---|---|
| **Backend & Core** | PHP 8.2 (Strict Types), CodeIgniter 4.5.8, Composer |
| **Bases de Datos** | PostgreSQL 15+ (Supabase) con PgBouncer Pooler (puerto 6543) |
| **Almacenamiento** | Supabase Storage (Buckets privados `documentos` y `evidencias`) |
| **Criptografía** | Hashing SHA-256 inmutable de archivos y contraseñas BCRYPT |
| **Frontend Web** | Bootstrap 5.3.2, Bootstrap Icons 1.11.1, Plus Jakarta Sans, CSS Modular |
| **App Móvil & SPA** | React Native 0.86, Expo SDK 57, TypeScript, AsyncStorage, `@supabase/supabase-js` |
| **Despliegue & Cloud** | Vercel Serverless (`vercel-php@0.7.4`), CI/CD automatizado vía Git |

---

## 🚀 Inicio Rápido en Entorno Local

### 1. Clonar e Instalar Dependencias
```bash
git clone https://github.com/soportealoraapp/App-Residencias.git
cd App-Residencias
composer install
```

### 2. Configurar Variables de Entorno
Copia `env` a `.env` y define tus credenciales de Supabase:
```bash
cp env .env
```
*(Consulta el diccionario completo de variables en [DOCUMENTACION_GENERAL.md](./DOCUMENTACION_GENERAL.md#5-diccionario-de-variables-de-entorno-env)).*

### 3. Migraciones y Base de Datos
```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

### 4. Compilar la App Móvil (Expo Web)
```bash
cd mobile/agentes-transito
npm install --legacy-peer-deps
npx expo export --platform web --output-dir ../../public/agentes --clear
cd ../..
```

### 5. Iniciar Servidor
```bash
php spark serve --port 8080
```
Accede desde tu navegador:
* Portal Ciudadano: [http://localhost:8080/portal/dashboard](http://localhost:8080/portal/dashboard)
* Panel Administrativo: [http://localhost:8080/admin/dashboard](http://localhost:8080/admin/dashboard)
* App Agentes Web: [http://localhost:8080/agentes/](http://localhost:8080/agentes/)

---

## 🔐 Cuentas de Acceso de Demostración

> **Contraseña universal para todas las cuentas de prueba:** `12345678`

| Correo Electrónico | Rol Asignado | Entorno de Acceso |
|---|---|---|
| `admin@uriangato.gob.mx` | `administrador` | Panel Administrativo (`/admin/`) |
| `operador1@uriangato.gob.mx` | `operador_ventanilla` | Panel Administrativo (`/admin/`) |
| `ciudadano@example.com` | `ciudadano` | Portal Ciudadano (`/portal/`) |

---

## 🧪 Pruebas Automatizadas (PHPUnit)

Para ejecutar la suite de pruebas unitarias y de integración:
```bash
./vendor/bin/phpunit
```
Las pruebas cubren validaciones de trámites, cálculo dinámico de tarifas, reglas de transición de la máquina de estados y control de acceso.

---

## 📄 Licencia y Marco Institucional

Desarrollado para el **H. Ayuntamiento de Uriangato, Guanajuato** — Dirección de Movilidad y Transporte. Todos los derechos reservados bajo la normativa de gobierno digital del Estado de Guanajuato.
