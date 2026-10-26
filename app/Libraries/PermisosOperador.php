<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\ParametroAppModel;

/**
 * Permisos del operador de ventanilla (rol "operador_ventanilla").
 *
 * El administrador define desde el panel qué secciones puede usar la ventanilla.
 * Se guardan como JSON en la tabla parametros_app bajo la clave
 * "permisos_operador_ventanilla" (sin migraciones: reutiliza una tabla existente).
 *
 * Criterios de diseño:
 *  - Sin fila guardada = todo permitido (comportamiento histórico de la ventanilla).
 *  - El administrador NUNCA se filtra: siempre tiene acceso completo.
 *  - Las bajas definitivas (eliminar) siguen siendo exclusivas del administrador
 *    y NO son configurables por seguridad.
 *  - La caché es por petición (entornos serverless como Vercel).
 */
final class PermisosOperador
{
    /** Clave del parámetro en parametros_app. */
    public const CLAVE_PARAMETRO = 'permisos_operador_ventanilla';

    /**
     * Catálogo de permisos. Cada entrada: nombre, descripción, icono de
     * Bootstrap Icons y prefijos de URI (sin barra inicial) que protege.
     * La coincidencia se hace por prefijo más largo.
     *
     * @var array<string, array{nombre: string, descripcion: string, icono: string, rutas: string[]}>
     */
    private const CATALOGO = [
        'solicitudes' => [
            'nombre'      => 'Solicitudes y trámites',
            'descripcion' => 'Ver solicitudes, cambiar estatus, validar pagos, evaluaciones UR-04/UR-05 y descargar documentos.',
            'icono'       => 'folder2-open',
            'rutas'       => ['admin/solicitudes'],
        ],
        'cobros' => [
            'nombre'      => 'Cobros',
            'descripcion' => 'Listado de cobros de trámites y multas: cobrar, mandar a completar y cancelar con motivo.',
            'icono'       => 'cash-stack',
            'rutas'       => ['admin/cobros'],
        ],
        'usuarios' => [
            'nombre'      => 'Usuarios y ciudadanos',
            'descripcion' => 'Crear y editar cuentas de ciudadanos desde la ventanilla (eliminar sigue siendo solo del administrador).',
            'icono'       => 'people',
            'rutas'       => ['admin/usuarios'],
        ],
        'tarifas' => [
            'nombre'      => 'Tarifario',
            'descripcion' => 'Administrar los catálogos de tarifas y precios de trámites.',
            'icono'       => 'cash-coin',
            'rutas'       => ['admin/tarifas'],
        ],
        'concesiones' => [
            'nombre'      => 'Concesiones',
            'descripcion' => 'Administrar el registro de concesiones de transporte.',
            'icono'       => 'card-list',
            'rutas'       => ['admin/concesiones'],
        ],
        'formatos' => [
            'nombre'      => 'Formatos de trámite',
            'descripcion' => 'Subir y quitar formatos descargables del portal ciudadano.',
            'icono'       => 'file-earmark-richtext',
            'rutas'       => ['admin/formatos'],
        ],
        'auditoria' => [
            'nombre'      => 'Auditoría',
            'descripcion' => 'Consultar el registro de acciones del sistema.',
            'icono'       => 'shield-check',
            'rutas'       => ['admin/auditoria'],
        ],
        'documentos' => [
            'nombre'      => 'Documentos recibidos',
            'descripcion' => 'Consultar el almacén de archivos subidos por los ciudadanos y ubicar su expediente.',
            'icono'       => 'file-earmark',
            'rutas'       => ['admin/documentos'],
        ],
        'verificaciones' => [
            'nombre'      => 'Verificaciones físicas',
            'descripcion' => 'Consultar la agenda de inspecciones y qué dictamen lleva cada cita.',
            'icono'       => 'clipboard2-check',
            'rutas'       => ['admin/verificaciones'],
        ],
        'convocatorias' => [
            'nombre'      => 'Convocatorias UR-01',
            'descripcion' => 'Publicar y editar convocatorias, evaluar postulaciones y seleccionar ganadores (la baja definitiva es solo del administrador).',
            'icono'       => 'award-fill',
            'rutas'       => ['admin/convocatorias'],
        ],
        'app_transito_config' => [
            'nombre'      => 'App Tránsito: configuración y agentes',
            'descripcion' => 'Parámetros de la app, UMA y altas/edición de agentes de tránsito (la baja definitiva es solo del administrador).',
            'icono'       => 'sliders',
            'rutas'       => ['admin/app-transito'],
        ],
        'app_transito_boletas' => [
            'nombre'      => 'App Tránsito: boletas de campo',
            'descripcion' => 'Consultar y editar las boletas capturadas por los agentes.',
            'icono'       => 'receipt-cutoff',
            'rutas'       => ['admin/app-transito/boletas'],
        ],
        'app_transito_catalogo' => [
            'nombre'      => 'App Tránsito: catálogo de faltas',
            'descripcion' => 'Administrar el catálogo de infracciones y sus UMA.',
            'icono'       => 'journal-bookmark',
            'rutas'       => ['admin/app-transito/catalogo'],
        ],
        'app_transito_sesiones' => [
            'nombre'      => 'App Tránsito: sesiones de acceso',
            'descripcion' => 'Consultar qué accesos de la app están vigentes, vencidos o revocados. Nunca se muestra el token.',
            'icono'       => 'key',
            'rutas'       => ['admin/sesiones-app'],
        ],
    ];

    /** @var array<string, bool>|null Caché por petición. */
    private static ?array $cache = null;

    /**
     * Definiciones visibles para la pantalla de configuración y validación.
     *
     * @return array<string, array{nombre: string, descripcion: string, icono: string, rutas: string[]}>
     */
    public static function definiciones(): array
    {
        return self::CATALOGO;
    }

    /**
     * @return string[]
     */
    public static function claves(): array
    {
        return array_keys(self::CATALOGO);
    }

    /**
     * Mapa completo clave => permitido. Sin fila guardada, todo permitido.
     *
     * @return array<string, bool>
     */
    public static function todos(): array
    {
        if (self::$cache === null) {
            $valores = [];
            foreach (self::CATALOGO as $clave => $_def) {
                $valores[$clave] = true;
            }

            $guardado = (new ParametroAppModel())->getValor(self::CLAVE_PARAMETRO);
            if ($guardado !== null && $guardado !== '') {
                $decodificado = json_decode($guardado, true);
                if (is_array($decodificado)) {
                    foreach ($valores as $clave => $_permitido) {
                        if (array_key_exists($clave, $decodificado)) {
                            $valores[$clave] = filter_var($decodificado[$clave], FILTER_VALIDATE_BOOLEAN);
                        }
                    }
                }
            }

            self::$cache = $valores;
        }

        return self::$cache;
    }

    public static function activo(string $clave): bool
    {
        return self::todos()[$clave] ?? true;
    }

    /**
     * Persiste el mapa de permisos. El mapa se trata como COMPLETO: las claves
     * conocidas que no lleguen se guardan como bloqueadas (coherente con el
     * formulario, que envía exactamente los interruptores encendidos).
     * Solo se guardan claves del catálogo; las desconocidas se ignoran.
     *
     * @param array<string, bool> $mapa
     */
    public static function guardar(array $mapa): bool
    {
        $limpio = [];
        foreach (self::CATALOGO as $clave => $_def) {
            $limpio[$clave] = !empty($mapa[$clave]);
        }

        $ok = (new ParametroAppModel())->setValor(
            self::CLAVE_PARAMETRO,
            (string) json_encode($limpio),
            'Permisos de secciones del panel para el rol operador_ventanilla',
            'json'
        );

        self::reiniciarCache();

        return (bool) $ok;
    }

    /**
     * Devuelve la clave del permiso requerido para una URI del panel, o null
     * si la URI no está restringida (por ejemplo, el dashboard).
     */
    public static function requeridoParaUri(string $uri): ?string
    {
        $uri  = trim($uri, '/');
        $mejor = null;
        $largo = -1;

        foreach (self::CATALOGO as $clave => $def) {
            foreach ($def['rutas'] as $prefijo) {
                if (strlen($prefijo) > $largo && str_starts_with($uri, $prefijo)) {
                    $mejor = $clave;
                    $largo = strlen($prefijo);
                }
            }
        }

        return $mejor;
    }

    /** Descarta la caché por petición (usado al guardar y en pruebas). */
    public static function reiniciarCache(): void
    {
        self::$cache = null;
    }
}
