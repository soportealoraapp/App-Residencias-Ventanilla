# Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato

Plataforma gubernamental integral para la digitalización de trámites, gestión de expedientes administrativos y supervisión de vialidad y transporte en el municipio de Uriangato, Guanajuato.

[![Producción Vercel](https://img.shields.io/badge/Vercel-Producción%20Online-black?logo=vercel)](https://app-residencias.vercel.app/)
[![App Agentes Web](https://img.shields.io/badge/Expo%20Web-App%20Agentes-4630EB?logo=expo)](https://app-residencias.vercel.app/agentes/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7.4-EF4223?logo=codeigniter)](https://codeigniter.com)
[![PostgreSQL](https://img.shields.io/badge/Supabase-PostgreSQL%2015+-3ECF8E?logo=supabase)](https://supabase.com/)

---

## 📌 Enlaces Rápidos a la Documentación Oficial

* 📘 [**DOCUMENTACION_GENERAL.md**](./DOCUMENTACION_GENERAL.md): Documentación técnica y operativa integral del sistema completo (Visión macro, separación del Portal/Panel Web y la App Móvil de Tránsitos, arquitectura de bases de datos, modelos de acceso, flujos normativos y guía de despliegue en Vercel).
* ⚙️ [**DOCUMENTACION_BACKEND.md**](./DOCUMENTACION_BACKEND.md): Documentación técnica exhaustiva del **Backend en CodeIgniter 4 / PHP 8.2** (Controladores, Modelos, Servicios, Storage Supabase, Filtros RBAC, Seguridad y API REST).
* 📱 [**DOCUMENTACION_APP_TRANSITO.md**](./DOCUMENTACION_APP_TRANSITO.md): Documentación técnica exhaustiva de la **App Móvil para Agentes de Tránsito en React Native / Expo** (Arquitectura Offline-First, Cámara, GPS, cálculo de sanciones en UMA y sincronización con Supabase).
* 🎓 [**DOC_METODOLOGIA_PORTAL_PANEL.md**](./DOC_METODOLOGIA_PORTAL_PANEL.md): Documento metodológico y memoria de arquitectura enfocado en el **Portal Ciudadano y Panel Administrativo Web**, diseñado como base para reporte de Residencia Profesional. Cubre patrones de diseño, esquema y diccionario de datos, pipeline documental, RBAC y permisología configurable del operador, despliegue serverless, los 7 trámites, **radicación presencial en ventanilla**, **diseño responsivo móvil** y **estrategia de pruebas automatizadas**.

---

## 🏛️ Componentes del Sistema

El ecosistema digital integra tres ramas de desarrollo comunicadas a través de la infraestructura en la nube de **Supabase**:

1. **Backend Centralizado (API & Core):** Desarrollado en CodeIgniter 4 sobre PHP 8.2 en arquitectura serverless. Controla la lógica de negocio, validaciones documentales, pasarela mock de pago, servicios de tarifario y endpoints REST para clientes externos.
2. **Portal Ciudadano y Panel Administrativo Web:**
   * **Portal Ciudadano (`/portal/`):** Plataforma web responsiva donde los ciudadanos y concesionarios gestionan los 7 trámites oficiales (UR-TT-T-01 al UR-TT-T-07), consultan expedientes y realizan pagos en línea simulados. El ciudadano **se autorregistra** sin intermediarios.
   * **Panel Administrativo (`/admin/`):** Consola para funcionarios municipales con control de acceso por roles (`administrador`, `operador_ventanilla`) y **permisos configurables por sección**, tableros analíticos en tiempo real, validación de expedientes con comentarios obligatorios, **radicación presencial de solicitudes para el ciudadano que acude a ventanilla**, padrón inmutable de auditoría y gestión de catálogos (tarifas, concesiones, **convocatorias con CRUD completo y publicación de bases**, formatos y cuentas de usuario). Ninguna concesión ni convocatoria con expedientes vinculados puede borrarse: se da de baja cambiando su estatus, para que el historial del ciudadano sobreviva.
3. **App Móvil para Agentes de Tránsito (`/agentes/`):** Aplicación desarrollada con Expo SDK 57 / React Native / TypeScript, utilizable en dispositivos móviles o vía Web SPA. Diseñada para operación *Offline-First*, cálculo automático de multas en UMA, captura fotográfica geolocalizada y sincronización directa con Supabase.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnologías Principales |
|---|---|
| **Backend & Core** | PHP 8.2 (Strict Types), CodeIgniter 4.7.4 (`^4.5` en composer.json), Composer |
| **Bases de Datos** | PostgreSQL 15+ (Supabase) con PgBouncer Pooler (puerto 6543) |
| **Almacenamiento** | Supabase Storage (Buckets privados `documentos` y `evidencias`) con alternativa local firmada |
| **Criptografía** | Hashing SHA-256 inmutable de archivos y contraseñas BCRYPT |
| **Frontend Web** | Bootstrap 5.3.2, Bootstrap Icons 1.11.1, Plus Jakarta Sans, CSS *mobile-first* con design tokens |
| **App Móvil & SPA** | React Native 0.86, Expo SDK 57, TypeScript, AsyncStorage, `@supabase/supabase-js` |
| **Despliegue & Cloud** | Vercel Serverless (`vercel-php@0.7.4`), CI/CD automatizado vía Git |
| **Pruebas** | PHPUnit 10.5 (247 casos / 931 aserciones en 27 clases) sobre harness SQLite aislado; verificador propio de la app (`node tests/auth-flow.test.cjs`, 18 casos) |

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
*(El diccionario de variables está en el propio [`.env.example`](./.env.example), agrupado por sección: entorno, app, base de datos —con los tres formatos que acepta el proyecto—, Supabase, cifrado, CSRF, cookies, *feature flags* y correo.)*

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
* App Agentes Web: [http://localhost:8080/agentes/](http://localhost:8080/agentes/)

### 6. Probar la App de Agentes con Expo (navegador o celular)
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

> **Contraseña universal para todas las cuentas de prueba:** `12345678`

| Correo Electrónico | Rol Asignado | Entorno de Acceso |
|---|---|---|
| `admin@uriangato.gob.mx` | `administrador` | Panel Administrativo (`/admin/`) |
| `operador1@uriangato.gob.mx` | `operador_ventanilla` | Panel Administrativo (`/admin/`) |
| `ciudadano@example.com` | `ciudadano` | Portal Ciudadano (`/portal/`) |

> **El ciudadano se autorregistra** en el portal ([`/auth/register`](http://localhost:8080/auth/register) — también funciona el atajo corto [`/register`](http://localhost:8080/register)); el sistema solo produce cuentas con rol `ciudadano`.
>
> Lo que **no** puede crearse desde el registro público son cuentas de **operador**, **administrador** o **agente de tránsito**: esas las da de alta el administrador desde **Panel → Usuarios y ciudadanos** (`/admin/usuarios`) y **Panel → App Tránsito → Agentes** (`/admin/app-transito`), porque son personal con capacidad de sanción y su identidad debe verificarse físicamente.

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

---

## 🧪 Pruebas Automatizadas (PHPUnit)

Para ejecutar la suite de pruebas unitarias y de integración:
```bash
./vendor/bin/phpunit
```
En Windows, si `php` no está en el PATH: `./php82/php.exe vendor/bin/phpunit`.

Las pruebas (**247 casos / 931 aserciones / 27 clases**) cubren: control de acceso por rol, permisos configurables del operador, validaciones de los 7 trámites, cálculo dinámico de tarifas (incluido el monto por camiones de UR-07), reglas de transición de la máquina de estados, radicación presencial, trazas de auditoría e historial, endpoints REST de agentes, el blindaje del almacenamiento local (firma HMAC, caducidad y rechazo de travesía de ruta), el **desglose navegable del tablero**, el **CRUD de convocatorias**, el **candado que impide borrar concesiones o convocatorias con expedientes vinculados**, el **cotejo de los 133 enlaces literales de las vistas contra el mapa de rutas real** y el **barrido de renderizado de vistas** (que el HTML producido lleve título, cabecera y el número correcto de filas).

La suite corre contra **SQLite en memoria** con prefijo `db_`, nunca contra la base institucional. Requiere que las migraciones estén aplicadas (`php spark migrate`).

La app móvil se verifica por separado, sin Expo ni dispositivo:

```bash
cd mobile/agentes-transito
node tests/auth-flow.test.cjs     # 18 casos: sesión, caducidad de token, cola offline, 401
npx tsc --noEmit                  # tipos del cliente (salida limpia)
```

---

## 📱 Interfaz Web Responsiva (Portal y Panel)

Ambas interfaces son *mobile-first*: el ciudadano y el operador pueden usarlas desde un teléfono sin perder funcionalidad.

* **Objetivos táctiles de 44 px** y campos a 16 px (evita el zoom automático de iOS al enfocar un formulario).
* **Portal:** navegación inferior fija tipo app en móvil, con el contenido desplazado para no quedar tapado.
* **Panel:** la barra lateral se convierte en gaveta deslizante (`min(300px, 86vw)`) con fondo oscurecido y barra superior pegajosa; respeta áreas seguras en dispositivos con muesca (`viewport-fit=cover` + `env(safe-area-inset-*)`).
* **Sin texto recortado, comprobado:** 55 páginas medidas en el navegador a 360/320/290 px con detector de desbordes y página de control. La causa raíz era que un hijo de *flexbox* no puede comprimirse por debajo de su contenido, y el `overflow-x: hidden` del contenedor lo ocultaba al no generar barra de desplazamiento.
* **Portada:** la fotografía institucional se ancla y el desenfoque se reduce en pantallas verticales para conservar el sujeto y el contraste del texto sin sacrificar rendimiento.
* **Accesibilidad:** respeta `prefers-reduced-motion`, conserva foco visible por teclado y un modo de impresión que oculta la interfaz para imprimir expedientes.

> ⚠️ **Restricción de despliegue:** en Vercel solo se sirve lo declarado en `vercel.json`; todo lo demás cae en el comodín que entrega la petición a PHP y devuelve 404. **Si agregas una imagen, icono o archivo estático a `public/`, añade su ruta al arreglo `routes`** y verifica la URL ya desplegada (en local `spark serve` sí sirve todo el directorio, así que el fallo no se ve antes de producción).
>
> ⚠️ **Restricción de enrutado (CodeIgniter 4):** una ruta cuyo *from* coincida con el **nombre** ya asignado con `['as' => ...]` a otra ruta **se descarta en silencio**, sin error. Por eso los atajos `/login`, `/register` y `/logout` llevan nombre propio (`acceso`, `registro`, `salida`). Si una ruta definida en `app/Config/Routes.php` no responde, compruébala con `php spark routes` antes de sospechar del despliegue: **declarada no es lo mismo que registrada**.

---

## 📄 Licencia y Marco Institucional

Desarrollado para el **H. Ayuntamiento de Uriangato, Guanajuato** — Dirección de Movilidad y Transporte. Todos los derechos reservados bajo la normativa de gobierno digital del Estado de Guanajuato.
