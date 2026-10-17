# App Agentes de Tránsito (React Native + Expo)

Aplicación móvil/web del MVP de boletas de infracción, dentro del monorepo de la Ventanilla Digital de Movilidad y Transporte de Uriangato. El cliente vive en `mobile/agentes-transito`; el backend CodeIgniter vive en la raíz. El despliegue web de Expo está configurado bajo `/agentes` en Vercel.

## Estado actual

La app admite captura local de boletas. Un inicio de sesión nuevo requiere conexión con el backend municipal; el perfil local puede restaurar la interfaz sin conexión, pero no comprueba la identidad ante el servidor. Las boletas guardadas permanecen disponibles localmente.

- El inicio de sesión valida placa, contraseña y estado activo contra `POST /api/agentes/login`. No existen accesos alternativos locales ni autorregistro móvil.
- Después de que el backend acepta las credenciales, la app guarda solo el perfil necesario en AsyncStorage. La nueva clave de perfil evita restaurar los perfiles antiguos creados por el login simulado.
- Al reabrir la app, el perfil local restaura la interfaz sin conexión. Esto no acredita autorización ante el servidor ni permite detectar offline que un agente fue desactivado.
- El cierre de sesión elimina el perfil local y las credenciales obsoletas del flujo de agentes; las boletas permanecen en su almacenamiento independiente.
- Los agentes se crean desde la administración municipal existente; el acceso a la aplicación no crea cuentas.
- La captura incluye datos del agente, fecha/hora, ubicación, infractor, vehículo, falta, hechos, garantías y tres evidencias requeridas.
- El GPS real intenta obtener permiso y ubicación; si no están disponibles, usa coordenadas de ejemplo de Uriangato.
- Las opciones de foto de este MVP son simuladas con imágenes de muestra; aún no equivalen a capturar y subir fotos reales con la cámara.
- Cada agente solo ve en la app las boletas que él emitió (se filtran por id/placa del agente en sesión), aunque varios agentes compartan el mismo dispositivo.
- Antes de guardar, la app muestra un resumen completo de la boleta; el agente puede volver a editar o confirmar la emisión.
- Las boletas pendientes se envían a `POST /api/infracciones/sincronizar` automáticamente: al recuperar conexión (NetInfo), al volver la app a primer plano, justo después de guardar una boleta y con reintentos cada 60 s mientras queden pendientes. El botón manual sigue disponible. Ya no hay respaldo directo a Supabase: si el servidor no responde, la boleta queda pendiente y se reintenta.
- El backend sí expone `GET /api/checklist-infracciones`; la app todavía no consume ese catálogo desde el servidor y usa su catálogo local.
- `POST /api/agentes/login` devuelve un token Bearer (vigencia 12 h; en BD solo se guarda su hash SHA-256). La app lo guarda con `expo-secure-store` (en web, `sessionStorage`) y lo envía a `api/infracciones/sincronizar` y `api/infracciones/evidencias`. El filtro `agenteToken` rechaza peticiones sin token, con token vencido o de agentes inactivos (401), y el servidor exige que `agente_placa` y los folios existentes pertenezcan al agente del token (403). Ante un 401 la app cierra la sesión, conserva las boletas pendientes y pide volver a iniciar sesión.

## Qué implementaron los últimos cambios

- **Base inicial (`65284f6`, 14 sep 2026):** estructura inicial de Expo, configuración de API, pantalla de acceso y navegación/pantalla principal de demostración.
- **MVP móvil (`37767c5`, 30 sep 2026):** pantallas Expo Router de acceso, dashboard y nueva infracción; contexto de autenticación de demostración; formularios y catálogo local de faltas; guardado offline, conteo de pendientes y sincronización inicial.
- **Publicación web (`c322f4c`, 30 sep 2026):** export estático de Expo Web durante instalación en Vercel, reglas para servir la app bajo `/agentes` y referencia al backend PHP.
- **Corrección de ruta (`c9cf500`, 30 sep 2026):** `baseUrl` de Expo Router y bundle estático alineados para evitar rutas no encontradas bajo `/agentes`.
- **Lockfile/dependencias (`ad8e0f8`, 30 sep 2026):** lockfile npm incluido y dependencias de Expo/React Native ajustadas. Usa `npm ci --legacy-peer-deps` para reproducir exactamente este conjunto.
- **Catálogo y montos de infracciones (`a7a43d5`, `e0c7d68`, 25 sep 2026):** backend con catálogo de 201 infracciones, parámetros de sistema y cálculo a partir de UMA, con reglas de descuento por pronto pago y recargo por mora. Este servicio de backend no sustituye todavía el catálogo local que presenta la app.
- **Checklist para boleta física (`a8b8750`, `1d6d6e8`, 25–28 sep 2026):** seeding de las casillas del checklist y endpoint `GET /api/checklist-infracciones`, que las agrupa e incluye referencias legales y montos. Se actualizó su prueba automatizada; el cliente móvil aún no consume este endpoint.

## Estructura relevante

```text
app/
  _layout.tsx          # proveedores y configuración del router
  index.tsx            # acceso del agente
  dashboard.tsx        # boletas, filtros, resumen y acción de sincronización
  nueva-infraccion.tsx # formulario de captura
src/
  api/                 # cliente HTTP y funciones de auth previstas
  constants/           # API_BASE_URL y catálogo local de faltas
  contexts/             # sesión e infracciones compartidas entre pantallas
  services/             # persistencia local y sincronización
  types/                # modelos TypeScript de infracción
assets/                 # iconos y recursos de Expo
```

## Requisitos

- Node.js y npm instalados (el lockfile fue creado para npm; el entorno probado usa Node 24 y npm 11).
- Para probar en Android nativo: Android Studio/emulador o teléfono Android con Expo Go.
- Para probar en iOS nativo se necesita macOS con Xcode; en Windows se puede usar Expo Go en un iPhone escaneando el QR.
- PHP/Composer y base de datos solo se necesitan para levantar el backend, no para explorar el flujo local/offline.

## Preparar el entorno (Windows / PowerShell)

Desde la raíz del repositorio:

```powershell
cd .\mobile\agentes-transito
npm ci --legacy-peer-deps
```

El `package-lock.json` es el lockfile de esta app. No ejecutes `npm install` desde la raíz del monorepo ni modifiques dependencias para empezar a trabajar.

## Iniciar y probar la app

### Navegador (forma más rápida)

```powershell
cd .\mobile\agentes-transito
npm run web
```

Abre la dirección local que indique Expo en la terminal. La compilación de publicación usa `/agentes` como ruta base; el código fuente de desarrollo lo sirve Expo desde su servidor local.

### Teléfono Android/iPhone con Expo Go

```powershell
cd .\mobile\agentes-transito
npm run start
```

Escanea el QR con Expo Go. El teléfono y la computadora deben estar en la misma red Wi-Fi. Si la conexión LAN está bloqueada, prueba el modo túnel desde Expo con `npx expo start --tunnel`.

Para abrir directamente en Android con un emulador ya iniciado:

```powershell
npm run android
```

En Windows, `npm run ios` no puede compilar ni iniciar el simulador de iOS; para ese caso se requiere macOS/Xcode o Expo Go en un iPhone.

### Recorrido de prueba

1. Para iniciar sesión, usa las credenciales de un agente activo creado desde la administración y asegúrate de que el backend sea accesible.
2. En el dashboard, revisa las boletas locales existentes.
3. Abre **Levantar nueva infracción**, completa los campos obligatorios, elige las tres imágenes de muestra y guarda.
4. Confirma que la boleta aparece como pendiente. En móvil, permite ubicación para probar GPS; sin permiso se usa una coordenada de demostración.
5. Reinicia la app para comprobar que se restaura el perfil local y las boletas. La restauración del perfil no confirma ante el servidor que la cuenta siga activa.
6. Con conexión, la boleta pasa sola a "sincronizada" en segundos; puedes confirmarla en el panel administrativo en **App Tránsito → Boletas de Campo**.

## Backend local para las pruebas (necesario para iniciar sesión)

El login y la sincronización requieren el backend CodeIgniter. Desde la **raíz del repo**, en otra terminal:

```powershell
.\php82\php.exe spark migrate
.\php82\php.exe spark serve --host 0.0.0.0 --port 8080
```

- **`spark migrate` después de cada `git pull`.** Si falta una migración (p. ej. `agentes_transito_tokens`), el login falla aunque las credenciales sean correctas. Si usan la base de Supabase, apliquen también los archivos nuevos de `supabase/migrations/`.
- **`--host 0.0.0.0` es obligatorio para probar en el celular.** Con `spark serve` a secas el servidor solo escucha en `localhost` y el teléfono recibe "No se pudo conectar con el servidor". Si Windows pregunta por el firewall, permite el acceso en redes privadas.
- Si no existen los agentes de prueba: `.\php82\php.exe spark db:seed AgentesTransitoSeeder`.

Luego inicia Expo con `npx expo start -c`. El `.env` de Expo (copia de `.env.example`) debe tener:

```env
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.25:8080/api
EXPO_PUBLIC_APP_ENV=development
```

En desarrollo la IP de esa URL se ajusta sola: el navegador usa `localhost` y el celular usa la IP de la PC que ejecuta Expo (`src/constants/config.ts`), así que no hay que editarla al cambiar de red. Solo se respeta tal cual si apunta a un servidor público. La consola de Expo muestra `[config] API_BASE_URL = ...` para confirmar a dónde se conecta.

Con `EXPO_PUBLIC_APP_ENV=development` el login muestra botones de **acceso rápido de prueba** (`AGT-204`, `AGT-107`, `JEF-001`, contraseña `transito2026`); siguen validándose contra el backend y desaparecen con `production`.

**Diagnóstico rápido:** abre `http://<IP-de-tu-PC>:8080/api/parametros` en el navegador del celular. Si no muestra un JSON, el problema es de red (firewall o Wi-Fi distinto), no de la app.

El `.env` de la raíz es independiente del `.env` de Expo. En esta copia solo declara `CI_ENVIRONMENT`; CodeIgniter toma la conexión de sus valores predeterminados de `app/Config/Database.php` (SQLite). Revisa esa configuración si vas a probar con otra base. No copies credenciales reales a archivos versionados.

## Validaciones disponibles

Desde `mobile/agentes-transito`:

```powershell
npx tsc --noEmit
npx expo install --check
npx expo export --platform web --output-dir "$env:TEMP\agentes-transito-web" --clear
```

La exportación genera un bundle temporal para validar el build; no es necesaria para usar `npm run web`. La prueba backend existente relacionada con el checklist se ejecuta desde la raíz:

```powershell
.\scripts\test-local.ps1 tests/app/Controllers/Api/InfraccionesApiControllerTest.php
```

No hay todavía script de pruebas automatizadas configurado dentro del `package.json` de la app.

## Despliegue web

`scripts/vercel-install.sh` instala dependencias PHP y, desde esta carpeta, exporta Expo Web a `public/agentes`. `vercel.json` dirige `/agentes` y sus recursos estáticos a esa salida. La carpeta `public/agentes` del repositorio puede contener un bundle exportado previamente; para publicación, el script de Vercel lo vuelve a generar.

## Convenciones para continuar

- Mantén las dependencias aisladas en esta carpeta y conserva sincronizados `package.json` y `package-lock.json`.
- Las rutas/pantallas de Expo Router viven en `app/`; la lógica compartida va en `src/`.
- Las variables `EXPO_PUBLIC_*` son públicas dentro del bundle móvil: no guardes secretos ni credenciales privadas ahí.
- Antes de llamar una función “integrada”, confirma tanto la ruta HTTP en `app/Config/Routes.php` como su implementación/controlador y una prueba de extremo a extremo.
