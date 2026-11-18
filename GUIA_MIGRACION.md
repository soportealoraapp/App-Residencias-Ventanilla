# Guía de migración y despliegue al servidor municipal

**Sistema:** Ventanilla Única de Movilidad y Transporte, Uriangato (backend CodeIgniter 4, portal web, panel administrativo, panel del juez calificador y app de agentes)
**Destino:** servidor municipal con Ubuntu Server, Apache, PHP 8.5 y PostgreSQL 18 en `https://movilidad.uriangato.gob.mx`
**Origen:** ambiente de pruebas (Vercel + Supabase)
**Responsables:** área de sistemas de la Dirección de Tecnologías de la Información y Telecomunicaciones, con apoyo del responsable del backend
**Versión:** borrador del 3 de noviembre de 2026

> Los datos del servidor los proporcionó el área de sistemas (comunicación personal, 3 de noviembre de 2026). Lo marcado como **[POR DEFINIR]** queda a decisión de sistemas.

---

## 0. Resumen

| Paso | Qué se hace | Quién |
|---|---|---|
| 1 | Verificar requisitos del servidor | Sistemas |
| 2 | Crear la base de datos PostgreSQL y su usuario | Sistemas |
| 3 | Instalar el código y sus dependencias | Sistemas + backend |
| 4 | Configurar el archivo `.env` de producción | Backend |
| 5 | Crear el esquema con las migraciones y cargar los catálogos | Backend |
| 6 | Configurar el almacenamiento local de documentos | Backend |
| 7 | Configurar el servidor web y HTTPS | Sistemas |
| 8 | Publicar la app de agentes | Backend |
| 9 | Ajustes de seguridad antes de abrir el sistema | Backend |
| 10 | Crear los usuarios reales y probar | Backend + Movilidad |
| 11 | Respaldos y mantenimiento | Sistemas |
| 12 | Activar la pasarela de BanBajío (cuando haya convenio) | Municipio |

**Decisión recomendada:** hacer una **instalación limpia** en el servidor municipal. La base de datos de Supabase solo tiene datos de prueba y de demostración, así que no se migran registros: solo se crea el esquema y se cargan los catálogos oficiales (roles, tarifas, catálogo de infracciones y checklist). Si sistemas o la Dirección deciden conservar algún dato de pruebas, ver la sección 13.

---

## 1. Requisitos del servidor

| Componente | Requisito del sistema | Definido por sistemas |
|---|---|---|
| Sistema operativo | Linux o Windows Server | Ubuntu Server, última versión LTS (26.04) |
| Servidor web | Apache 2.4 con `mod_rewrite` | Apache |
| PHP | 8.2 o superior (lo exige CodeIgniter 4.7; las pruebas se corrieron con 8.2.25) | PHP 8.5, incluido en los repositorios de Ubuntu 26.04; CodeIgniter lo soporta desde la 4.7.0 y el proyecto usa la 4.7.4 |
| Extensiones de PHP | `intl`, `mbstring`, `pgsql`, `curl`, `fileinfo`, `openssl`, `json` | Instalar con `apt` (ver abajo) |
| PostgreSQL | 15 o superior | PostgreSQL 18, local en el mismo servidor y separado de MySQL |
| Composer | 2.x (el repositorio incluye `composer.phar`) | — |
| Node.js | Solo si se reconstruye la app de agentes; puede hacerse en otra computadora | — |
| Dirección de acceso | Dominio o subdominio | `movilidad.uriangato.gob.mx` (subdominio de `uriangato.gob.mx`) |
| HTTPS | Certificado válido | Incluido: el subdominio usa el certificado del dominio principal |
| Acceso desde internet | Necesario para la app de los agentes en la calle | Sí, por ser subdominio público |
| Espacio en disco | Documentos y fotografías de evidencia; al menos 10 GB iniciales | [POR DEFINIR] |

Instalar PHP, Apache y PostgreSQL en Ubuntu:

```bash
sudo apt update
sudo apt install apache2 libapache2-mod-php8.5 php8.5 php8.5-intl php8.5-mbstring php8.5-pgsql php8.5-curl
sudo apt install postgresql-18     # si no aparece, usar el repositorio oficial de PostgreSQL (apt.postgresql.org)
sudo a2enmod rewrite ssl
```

> **PHP 8.5:** el sistema se desarrolló y probó con PHP 8.2 y CodeIgniter 4.7.4. Antes de abrirlo, correr las pruebas con PHP 8.5 en el servidor (`php vendor/bin/phpunit`) para descartar avisos de incompatibilidad. Si aparecen, actualizar CodeIgniter con `php composer.phar update codeigniter4/framework`.

### 1.1. Por qué un subdominio público con HTTPS

- **Acceso desde la calle:** una IP interna (por ejemplo `192.168.1.200`) solo se alcanza dentro de la red del municipio; los agentes de tránsito con datos móviles no podrían iniciar sesión ni sincronizar boletas. El subdominio público lo resuelve, y también servirá para la confirmación de pagos de BanBajío (sección 12).
- **HTTPS:** cifra las contraseñas, permite usar la cámara en la versión web de la app de agentes y evita que Android bloquee la app instalada.
- **Sin cambios de código:** con un subdominio propio el sistema vive en la raíz (`/`), así que no hay que ajustar rutas en el backend ni en la app de agentes.

---

## 2. Base de datos

Crear una base de datos y un usuario exclusivos para el sistema (no usar el superusuario `postgres` en la aplicación):

```sql
CREATE USER ventanilla_movilidad WITH PASSWORD '<contraseña segura>';
CREATE DATABASE ventanilla_movilidad OWNER ventanilla_movilidad ENCODING 'UTF8';
```

La base de datos queda **local**, en el PostgreSQL 18 del mismo servidor. Los demás servicios del municipio usan MySQL, así que el sistema no comparte motor ni tablas con ellos. El sistema está hecho y probado para PostgreSQL; no se instala sobre MySQL.

PostgreSQL no debe aceptar conexiones desde internet: solo la aplicación, en el mismo servidor. En Supabase esto se cubrió con RLS (`supabase/migrations/0020`) porque ahí la base sí tiene una API pública; en el servidor municipal se cubre con la red:

- `postgresql.conf`: `listen_addresses = 'localhost'`.
- `pg_hba.conf`: solo `host ventanilla_movilidad ventanilla_movilidad 127.0.0.1/32 scram-sha-256` (y `::1/128`), sin reglas para otras redes.
- No abrir el puerto 5432 en el firewall.

---

## 3. Instalación del código

```bash
# 1. Obtener el código (desde GitHub o copiando la carpeta del proyecto)
git clone https://github.com/soportealoraapp/App-Residencias-Ventanilla.git
cd App-Residencias-Ventanilla

# 2. Instalar dependencias de producción
php composer.phar install --no-dev --optimize-autoloader

# 3. Permisos de escritura (Linux; ajustar el usuario del servidor web)
chown -R www-data:www-data writable
chmod -R 775 writable
```

No copiar al servidor: `php82/`, `php-8.2.25-Win32-vs16-x64.zip`, `.env` de pruebas, `database.sqlite`, `.composer-cache/`, `.phpunit.cache/` ni la carpeta `tests/`.

---

## 4. Archivo `.env` de producción

Crear `.env` a partir de `.env.example` con estos valores. **No copiar el `.env` del ambiente de pruebas**, porque contiene las credenciales de Supabase.

```ini
CI_ENVIRONMENT = production

APP_BASEURL = https://movilidad.uriangato.gob.mx/
APP_FORCE_HTTPS = true
APP_ALLOWED_HOSTNAMES = movilidad.uriangato.gob.mx
# Solo si hay un proxy inverso o balanceador delante de PHP: su IP o rango (sección 9)
# APP_PROXY_IPS = 10.0.0.5
APP_TIMEZONE = America/Mexico_City
APP_LOCALE = es

# Base de datos (servidor municipal)
DATABASE_DEFAULT_DBDRIVER = Postgre
DATABASE_DEFAULT_HOSTNAME = localhost        # PostgreSQL local
DATABASE_DEFAULT_DATABASE = ventanilla_movilidad
DATABASE_DEFAULT_USERNAME = ventanilla_movilidad
DATABASE_DEFAULT_PASSWORD = <contraseña segura>
DATABASE_DEFAULT_PORT = 5432
DATABASE_DEFAULT_CHARSET = utf8
DATABASE_DEFAULT_SCHEMA = public
DATABASE_DEFAULT_SSLMODE = disable           # local, no requiere SSL

# Almacenamiento local: dejar vacías las variables de Supabase
SUPABASE_URL =
SUPABASE_SERVICE_ROLE_KEY =
STORAGE_LOCAL_PATH = /var/ventanilla/storage # fuera de la carpeta pública

# Correo [POR DEFINIR con sistemas]
EMAIL_FROM = no-reply@uriangato.gob.mx
EMAIL_FROM_NAME = Ventanilla de Movilidad
EMAIL_SMTP_HOST =
EMAIL_SMTP_USER =
EMAIL_SMTP_PASS =
EMAIL_SMTP_PORT = 587

# Llave de cifrado nueva (no reutilizar la de pruebas)
encryption.key =
```

Generar la llave de cifrado:

```bash
php spark key:generate
```

Notas:
- La configuración lee primero las variables `POSTGRES_*`; en el servidor municipal basta con las `DATABASE_DEFAULT_*`. No definir las `POSTGRES_*` para evitar confusiones.
- El sistema decide solo si usa almacenamiento local: lo hace cuando `SUPABASE_URL` o `SUPABASE_SERVICE_ROLE_KEY` están vacías.
- Proteger el `.env` para que solo lo lea el usuario del servidor web (`chmod 640 .env`).

---

## 5. Esquema y catálogos

### 5.1 Crear el esquema

En una base de datos vacía, las migraciones de CodeIgniter crean todo el esquema y registran cada paso en la tabla `migrations`:

```bash
php spark migrate
php spark migrate:status   # todas deben aparecer como aplicadas
```

El proyecto tiene 35 migraciones (de `2026-01-01-000001` a `2026-11-17-000035`). La última agrega las columnas del código de activación del acuse; en el servidor municipal sale con `php spark migrate`, sin el SQL de `supabase/`.

### 5.2 Historial de migraciones (punto delicado)

En el ambiente de pruebas, parte del esquema se aplicó directamente en Supabase con los archivos SQL de `supabase/migrations/` (`0001` a `0019`), sin registrarse en la tabla `migrations` de CodeIgniter. Por eso **no se debe restaurar un respaldo de Supabase y luego ejecutar `php spark migrate`**: las migraciones intentarían crear tablas que ya existen.

Con la instalación limpia el problema no existe, porque el esquema sale solo de las migraciones de CodeIgniter. Para confirmar que coincide con el de pruebas, comparar con el script del repositorio:

```bash
php scripts/verificar_esquema_bd.php
```

### 5.3 Cargar solo los catálogos

El `DatabaseSeeder` completo también carga **usuarios, concesiones, convocatorias y agentes de demostración**, que no deben existir en producción. Ejecutar solo los seeders de catálogos, en este orden:

```bash
php spark db:seed RolesSeeder
php spark db:seed TarifasSeeder
php spark db:seed CatalogoInfraccionesSeeder
php spark db:seed CatalogoInfraccionesAppSeeder
php spark db:seed BoletaChecklistSeeder
```

**No ejecutar:** `DatabaseSeeder`, `UsuariosDemoSeeder`, `ConcesionesSeeder`, `ConvocatoriasSeeder`, `AgentesTransitoSeeder`, `DemoFuncionamientoSeeder` ni `JuezCalificadorDemoSeeder`.

Comprobar:
- Roles: `administrador`, `operador_ventanilla`, `ciudadano` y `juez_calificador`.
- Tarifas: deben aparecer las de las fichas oficiales (por ejemplo, UR-TT-T-04 $156.94, UR-TT-T-05 $287.00).
- Catálogo de infracciones: 201 faltas visibles en la app.
- Parámetro de la UMA: confirmar el valor vigente con la Dirección antes de operar.

### 5.4 Ensayo de instalación (17 nov 2026)

Esta sección se ensayó completa en un PostgreSQL 17 local vacío, con un usuario sin privilegios de superusuario, igual que en la sección 2. El ensayo encontró y corrigió cuatro fallas que solo aparecían en una instalación limpia (en Supabase el esquema se creó con SQL, por eso no se veían):

- La migración `000020` usaba el tipo `ENUM` de MySQL y detenía `php spark migrate`.
- La migración `000029` intentaba cambiar el tipo de `users.password_hash`, que usa la vista `ciudadanos`.
- La sincronización de boletas enviaba `TRUE/FALSE` a columnas `SMALLINT`; ninguna boleta de la app habría llegado al servidor.
- No había forma de crear el primer administrador (ahora `php spark usuarios:crear-admin`, sección 10).

Resultado: las 35 migraciones, los cinco seeders de catálogos y `php scripts/verificar_esquema_bd.php` pasan sin errores; la suite completa de pruebas corre también contra PostgreSQL; el panel abre sin errores y una boleta de la app se sincroniza y aparece en el panel.

Al entrar por primera vez, el inicio del panel avisa de dos tareas de configuración que no se pueden sembrar: subir el formato oficial de cada trámite (**Catálogos → Formatos de trámite**) y asignar el fundamento a las 32 casillas de la boleta física que no lo tienen (**App Tránsito → Catálogo de Faltas**).

---

## 6. Almacenamiento de documentos

1. Crear la carpeta de `STORAGE_LOCAL_PATH` **fuera** de la carpeta pública y dar permisos de escritura al servidor web:

   ```bash
   mkdir -p /var/ventanilla/storage
   chown -R www-data:www-data /var/ventanilla/storage
   chmod 750 /var/ventanilla/storage
   ```

2. Los documentos se entregan con enlaces firmados que caducan (`ArchivosLocalesController`), así que no necesitan estar en `public/`.
3. Las fotografías de evidencia de las boletas se guardan en `STORAGE_LOCAL_PATH/evidencias`, fuera de `public/`, y solo las abre el personal con sesión en `/evidencias/…`.
4. Los documentos de Supabase son de prueba y no se migran.

---

## 7. Servidor web (Apache) y HTTPS

La raíz del sitio debe apuntar a la carpeta **`public/`** del proyecto, nunca a la raíz del repositorio (ahí están `.env`, `app/` y `writable/`). El repositorio ya trae `public/.htaccess`.

1. En el DNS de `uriangato.gob.mx`, crear el registro de `movilidad` que apunte al servidor (si aún no existe).
2. Configurar el sitio en Apache con el certificado del dominio principal:

```apache
<VirtualHost *:80>
    ServerName movilidad.uriangato.gob.mx
    Redirect permanent / https://movilidad.uriangato.gob.mx/
</VirtualHost>

<VirtualHost *:443>
    ServerName movilidad.uriangato.gob.mx
    DocumentRoot /var/www/ventanilla-movilidad/public
    <Directory /var/www/ventanilla-movilidad/public>
        AllowOverride All
        Require all granted
    </Directory>
    SSLEngine on
    SSLCertificateFile    /ruta/al/certificado/uriangato.gob.mx.crt   # el que ya usa sistemas
    SSLCertificateKeyFile /ruta/al/certificado/uriangato.gob.mx.key
</VirtualHost>
```

```bash
sudo a2ensite ventanilla-movilidad.conf
sudo apachectl configtest && sudo systemctl reload apache2
```

3. Comprobar que `https://movilidad.uriangato.gob.mx` abre sin aviso de certificado, también desde un teléfono con datos móviles.

> Si el certificado del dominio principal no cubriera el subdominio, puede emitirse uno gratuito con Let's Encrypt (`sudo certbot --apache -d movilidad.uriangato.gob.mx`), que se renueva solo cada 90 días.

---

## 8. App de agentes de tránsito

- **Versión web** (`/agentes`): es el export de Expo que vive en `public/agentes/`. Usa automáticamente el mismo dominio del servidor como dirección de la API, así que con el subdominio no requiere configuración adicional. Si se modifica el código de la app, regenerarla con `scripts/exportar_agentes_web.ps1` antes de copiar `public/agentes/`.
- **App instalada en teléfonos:** compilarla con la dirección del servidor municipal:

  ```ini
  # mobile/agentes-transito/.env
  EXPO_PUBLIC_API_BASE_URL=https://movilidad.uriangato.gob.mx
  ```

- **Respaldo de Supabase:** la app todavía consulta Supabase como respaldo para el catálogo y los parámetros. Retirar ese respaldo (o dejar vacías sus variables) antes de producción, para que todo pase por la API del servidor municipal.
- **CORS:** `app/Config/Cors.php` arma la lista sola: el dominio de `APP_BASEURL` y, fuera de producción, los `localhost` de Expo. La app web de agentes vive en el mismo dominio (`/agentes`) y la nativa no manda `Origin`, así que no hace falta agregar nada. Solo si se publica la app web en otro dominio, ponerlo en `APP_CORS_ORIGINS` (separados por coma).

---

## 9. Ajustes de seguridad antes de abrir el sistema

| # | Ajuste | Dónde |
|---|---|---|
| 1 | Dejar apagada la bandera `APP_ENABLE_DEMO_LOGIN` (o no definirla). Con ella encendida, el login muestra los botones de "Acceso Rápido de Prueba" con las cuentas demo | `.env` |
| 2 | No cargar usuarios demo; si existen, borrarlos (`php spark demo:limpiar --force` borra los datos de `DemoFuncionamientoSeeder`) | Base de datos |
| 3 | `CI_ENVIRONMENT = production`, para no mostrar errores detallados | `.env` |
| 4 | Proxy de confianza: fuera de Vercel la app ya no confía en `X-Forwarded-For` de cualquier IP. Si hay un proxy inverso o balanceador delante de PHP, poner su IP o rango en `APP_PROXY_IPS` (varias, separadas por coma). Sin eso, el límite de intentos fallidos contaría todo como una sola IP y, si el proxy termina el HTTPS, `APP_FORCE_HTTPS` provocaría redirecciones en bucle. Sin proxy, dejarla sin definir | `.env` |
| 5 | CORS: con `CI_ENVIRONMENT = production` solo se acepta el dominio de `APP_BASEURL` más los de `APP_CORS_ORIGINS` (sección 8) | `.env` |
| 6 | Llave de cifrado nueva y `.env` con permisos restringidos | `.env` |
| 7 | Pasarela de pagos: mientras no haya convenio con BanBajío, dejar apagada la bandera `APP_ENABLE_PAGO_EN_LINEA`. Así el portal solo ofrece la hoja de pago para caja; encendida, usa la pasarela simulada | `.env` |

---

## 10. Usuarios reales y pruebas de aceptación

1. Crear la primera cuenta de administrador desde la consola (en una base nueva no hay ninguna otra forma de entrar al panel):

   ```bash
   php spark usuarios:crear-admin --usuario <usuario> --correo <correo> --nombre "<Nombre completo>"
   ```

   La contraseña se genera al azar y se muestra una sola vez en la terminal. Cambiarla al entrar, en **Admin → Usuarios**.
2. Crear los usuarios de operación: juez calificador, operadores de ventanilla y agentes de tránsito (placa y contraseña).
3. Probar con datos ficticios y después borrarlos:

| Prueba | Resultado esperado |
|---|---|
| Inicio de sesión de cada rol | Cada rol entra a su panel |
| Radicación presencial de un permiso | Se crea la solicitud y su historial |
| Flujo de UR-TT-T-05 hasta "Permiso emitido" | Estatus correctos y cobro con recibo |
| App de agentes: boleta con dos faltas, sin conexión y luego sincronizada | La boleta llega al panel del juez |
| Calificación de la boleta por el juez | Se genera el cobro pendiente con descuento o recargo |
| Registro de un pago presencial | Folio de recibo y aparición en el corte de caja |
| Carga y descarga de un documento | Se guarda en `STORAGE_LOCAL_PATH` y se descarga con enlace firmado |

---

## 11. Respaldos y mantenimiento

- **Base de datos:** respaldo diario con `pg_dump`, conservando al menos 30 días:

  ```bash
  pg_dump -U ventanilla_movilidad -F c ventanilla_movilidad > respaldo_$(date +%F).dump
  ```

- **Documentos:** respaldo de `STORAGE_LOCAL_PATH` (incluye las evidencias de las boletas) con la misma frecuencia.
- **Registros:** revisar `writable/logs/` y limpiar sesiones antiguas en `writable/session/`.
- **Tarea programada pendiente:** el vencimiento automático de los permisos de carga y descarga todavía no está implementado; cuando exista, se ejecutará con `cron` o el Programador de tareas.
- **Actualizaciones:** antes de actualizar el código, respaldar, ejecutar `php spark migrate` y revisar `migrate:status`.

---

## 12. Activación de la pasarela de pagos de BanBajío

El sistema se entrega con la pasarela integrada **a nivel de interfaz**: el cobro en línea funciona con una implementación simulada y el pago presencial opera completo (folio de recibo, corte de caja y hoja de pago). La activación real la realiza el municipio cuando cuente con los datos del convenio con BanBajío. No es requisito para abrir el sistema, porque el pago presencial coexiste con el pago en línea.

**Datos que se necesitan del banco:** credenciales del ambiente de pruebas y del productivo, documentación técnica de la integración, formato oficial de la referencia de pago (longitud, dígito verificador y prefijos) y la dirección a la que el banco enviará la confirmación de cada pago.

**Pasos:**

1. **Pasarela.** Crear una clase que implemente `App\Interfaces\PaymentGatewayInterface` (operaciones `crearCargo()`, `confirmarPago()` y `reembolsar()`) con la conexión real de BanBajío, tomando como referencia `app/Libraries/BanbajioMockGateway.php`. Guardar las credenciales en el `.env`, nunca en el código.
2. **Registro del servicio.** En `app/Config/Services.php`, el método `paymentGateway()` devuelve hoy `BanbajioMockGateway`; cambiarlo para que devuelva la clase nueva.
3. **Referencia bancaria.** Si el formato oficial difiere del provisional (12 dígitos con verificador MOD 97-10), crear una clase que implemente `App\Interfaces\ReferenciaBancariaInterface` y registrarla en `referenciaBancaria()` del mismo archivo. Los cobros ya existentes conservan su referencia.
4. **Confirmación del banco (*webhook*).** Agregar una ruta que reciba la confirmación del pago, **verifique la firma** del mensaje, guarde la referencia del banco y solo entonces registre el pago con `CobroService::marcarCobrado()`. La ruta debe ser accesible por HTTPS desde internet.
5. **Unificar el pago en línea de los permisos.** Hoy UR-TT-T-04, UR-TT-T-05 y UR-TT-T-07 llaman a la pasarela directamente desde su controlador; al activarla, hacer que el pago pase por el módulo de cobros y verificar el estatus antes de cobrar, para que un doble envío no genere dos cargos.
6. **Base normativa.** Confirmar con la Dirección cómo se aplicará el descuento por pronto pago en línea, ya que el reglamento prevé el pago en la caja de la Dirección (art. 189).
7. **Pruebas.** Probar primero con las credenciales del ambiente de pruebas del banco: pago exitoso, pago rechazado, confirmación duplicada y reembolso.

---

## 13. Si se decide conservar datos del ambiente de pruebas

1. Instalar el esquema limpio con las migraciones (sección 5.1).
2. Exportar de Supabase **solo los datos** de las tablas que se quieran conservar, sin la tabla `migrations`:

   ```bash
   pg_dump --data-only --table=<tabla> "<cadena de conexión de Supabase>" > datos.sql
   ```

3. Importar en el servidor municipal y resincronizar las secuencias (la migración `ResyncSecuenciasPostgres` sirve de referencia).
4. Descargar de Supabase Storage los archivos de esos registros y copiarlos a `STORAGE_LOCAL_PATH` conservando las rutas.

---

## 14. Lista de verificación final

- [ ] Requisitos del servidor confirmados (sección 1)
- [ ] Base de datos y usuario creados
- [ ] `.env` de producción configurado, sin credenciales de Supabase
- [ ] `php spark migrate:status` con todas las migraciones aplicadas
- [ ] Solo los seeders de catálogos ejecutados
- [ ] Almacenamiento local fuera de `public/` y con permisos
- [ ] Sitio con HTTPS y raíz en `public/`
- [ ] App de agentes apuntando al servidor municipal
- [ ] Ajustes de seguridad de la sección 9 aplicados
- [ ] Usuarios reales creados y pruebas de aceptación superadas
- [ ] Respaldos programados
- [ ] Datos de prueba borrados
- [ ] Pasarela de BanBajío activada o pago en línea ocultado mientras tanto (sección 12)
