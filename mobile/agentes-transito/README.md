# App Agentes de Tránsito (React Native + Expo)

App móvil para agentes de tránsito, dentro del monorepo de **Ventanilla Digital de Movilidad y Transporte de Uriangato**. Vive de forma aislada en `mobile/agentes-transito` y no afecta al backend/portal web (CodeIgniter 4) ubicado en la raíz del repo.

## Stack

- Expo (SDK 57) + React Native + TypeScript
- React Navigation (native-stack) para el flujo de pantallas
- `expo-secure-store` para guardar el token de sesión de forma segura
- Consumo del backend vía la API REST existente en [`api/`](../../api/index.php)

## Estructura

```
src/
  api/          # clientes HTTP (fetch wrapper, endpoints por dominio: auth, infracciones, etc.)
  constants/     # configuración (URLs, flags) leída de variables EXPO_PUBLIC_*
  contexts/      # contextos de React (ej. AuthContext)
  navigation/    # stacks/navigators
  screens/       # pantallas, agrupadas por dominio (auth/, home/, ...)
  components/    # componentes reutilizables (crear conforme se necesiten)
  hooks/         # hooks reutilizables (crear conforme se necesiten)
  types/         # tipos/DTOs compartidos
```

## Configuración local

1. Copia `.env.example` a `.env` y ajusta `EXPO_PUBLIC_API_BASE_URL` para que apunte a tu backend local
   (usa la IP de tu máquina en la red local, no `localhost`, para que el dispositivo/emulador pueda alcanzarlo).
2. Instala dependencias:
   ```powershell
   cd mobile/agentes-transito
   npm install
   ```
3. Levanta el servidor de desarrollo:
   ```powershell
   npm run start
   ```
4. Abre la app con Expo Go (Android/iOS) escaneando el QR, o `npm run android` / `npm run ios` / `npm run web`.

## Autenticación con el backend

El backend actual (`AuthController`, `AuthFilter`) usa sesión + cookie con CSRF pensado para el portal web.
Para la app móvil se recomienda exponer/usar un endpoint de autenticación por **token (Bearer/JWT)** dentro de `api/`,
en vez de depender de cookies de sesión. El cliente HTTP (`src/api/client.ts`) ya está preparado para adjuntar
`Authorization: Bearer <token>` automáticamente una vez que el login devuelva un token.

## Convenciones

- No mezclar dependencias de Node con las de PHP/Composer: cada proyecto maneja su propio lockfile.
- Nuevas pantallas van en `src/screens/<dominio>/`, nuevos endpoints en `src/api/<dominio>.ts`.
- Variables sensibles solo en `.env` (ignorado por git), nunca hardcodeadas.
