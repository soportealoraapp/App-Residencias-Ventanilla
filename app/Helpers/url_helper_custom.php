<?php declare(strict_types=1);

if (!function_exists('nombre_mes')) {
    /**
     * Nombre en español del mes (por número o por fecha YYYY-MM).
     * date('F') devuelve el nombre en inglés, así que los rótulos de las
     * gráficas y series mensuales se arman con esta función.
     */
    function nombre_mes(int|string|null $mes = null): string
    {
        static $nombres = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
            7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        if ($mes === null || $mes === '') {
            return $nombres[(int) date('n')];
        }

        if (is_string($mes)) {
            if (preg_match('/^\d{4}-(\d{2})/', $mes, $m) === 1) {
                return $nombres[(int) $m[1]] ?? $mes;
            }
            $mes = (int) $mes;
        }

        return $nombres[$mes] ?? (string) $mes;
    }
}

if (!function_exists('es_municipio_uriangato')) {
    function es_municipio_uriangato(): bool
    {
        return true;
    }
}

if (!function_exists('formatear_dinero')) {
    function formatear_dinero(float $monto): string
    {
        return '$ ' . number_format($monto, 2, '.', ',');
    }
}

if (!function_exists('formatear_fecha')) {
    function formatear_fecha(string|\DateTimeInterface|null $fecha, string $formato = 'd/m/Y H:i'): string
    {
        if (empty($fecha)) {
            return '-';
        }
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format($formato);
        }
        $dt = new \DateTime((string) $fecha);
        return $dt->format($formato);
    }
}

if (!function_exists('tramite_nombre')) {
    function tramite_nombre(string $clave): string
    {
        return match ($clave) {
            'UR-TT-T-01' => 'Concesión de Transporte',
            'UR-TT-T-02' => 'Constancia de Despintado',
            'UR-TT-T-03' => 'Orden de Plaqueo',
            'UR-TT-T-04' => 'Permiso Eventual de Transporte',
            'UR-TT-T-05' => 'Permiso para Cierre de Calle',
            'UR-TT-T-06' => 'Cesión de Concesión',
            'UR-TT-T-07' => 'Permiso de Carga y Descarga',
            default       => $clave,
        };
    }
}

if (!function_exists('tipo_documento_nombre')) {
    /**
     * Etiqueta legible del tipo de archivo.
     *
     * El tipo llega del nombre del campo del formulario, así que no hay catálogo
     * cerrado: se traducen los conocidos y el resto se humaniza sin guiones bajos
     * para que el administrador nunca vea un `identificacion_oficial` en crudo.
     */
    function tipo_documento_nombre(?string $tipo): string
    {
        $tipo = trim((string) $tipo);
        if ($tipo === '') {
            return 'Sin clasificar';
        }

        $nombre = match ($tipo) {
            'identificacion_oficial'   => 'Identificación oficial',
            'tarjeta_circulacion'      => 'Tarjeta de circulación',
            'documento_carga_descarga' => 'Documento de carga y descarga',
            'titulo_concesion_original' => 'Título de concesión (original)',
            'documentos_capacidad'     => 'Documentos de capacidad',
            'comprobante_domicilio'    => 'Comprobante de domicilio',
            'ine_frente'              => 'INE (frente)',
            'ine_reverso'             => 'INE (reverso)',
            default                    => '',
        };

        if ($nombre !== '') {
            return $nombre;
        }

        return ucfirst(str_replace('_', ' ', $tipo));
    }
}

if (!function_exists('formato_bytes')) {
    /**
     * Peso legible de un archivo o de un almacén completo.
     */
    function formato_bytes(int|float|null $bytes): string
    {
        $bits = (float) $bytes;

        if ($bits >= 1073741824) {
            return number_format($bits / 1073741824, 2) . ' GB';
        }
        if ($bits >= 1048576) {
            return number_format($bits / 1048576, 1) . ' MB';
        }
        if ($bits > 0) {
            // Un decimal: un archivo de 300 bytes no debe leerse como «0 KB».
            return number_format($bits / 1024, 1) . ' KB';
        }

        return '0 KB';
    }
}

if (!function_exists('slug')) {
    function slug(string $texto): string
    {
        $texto = strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
        return trim($texto, '_');
    }
}

if (!function_exists('active')) {
    function active(string $ruta): string
    {
        $uri = service('uri');
        $path = trim($uri->getPath(), '/');
        $ruta = trim($ruta, '/');
        return ($path === $ruta || str_starts_with($path, $ruta . '/')) ? 'active' : '';
    }
}

if (!function_exists('estatus_badge')) {
    function estatus_badge(string $estatus): string
    {
        $map = [
            // Procesos iniciales / en tránsito
            'Recibido'                       => 'bg-info text-dark',
            'Cita agendada'                  => 'bg-info text-dark',
            'Documentos completos'           => 'bg-info text-dark',
            'Autorizado para pago'           => 'bg-info text-dark',
            'Evaluación comparativa'         => 'bg-info text-dark',
            // En revisión / estudio
            'En revisión'                    => 'bg-primary',
            'En validación'                  => 'bg-primary',
            'En revisión documental'         => 'bg-primary',
            'En estudio técnico'             => 'bg-primary',
            // Pendientes de acción del usuario
            'Pendiente de inspección'        => 'bg-warning text-dark',
            'Pendiente de revista mecánica'  => 'bg-warning text-dark',
            'Seguro pendiente de validación' => 'bg-warning text-dark',
            'Prevención'                     => 'bg-warning text-dark',
            'Pago pendiente'                 => 'bg-warning text-dark',
            // Aprobados / finalizados positivos
            'Dictamen favorable'             => 'bg-success',
            'Dictaminado aprobado'           => 'bg-success',
            'Pagado'                         => 'bg-success',
            'Permiso emitido'                => 'bg-success',
            'Título emitido'                 => 'bg-success',
            'Constancia emitida'             => 'bg-success',
            'Vigente'                        => 'bg-success',
            'Verificado'                     => 'bg-success',
            'Aprobado'                       => 'bg-success',
            'Seleccionado'                   => 'bg-success',
            // Rechazados / finalizados
            'Vencido'                        => 'bg-danger',
            'Rechazado'                      => 'bg-danger',
            'Concluido'                      => 'bg-dark',
            'No seleccionado'                => 'bg-secondary',
        ];

        return $map[$estatus] ?? 'bg-secondary';
    }
}

if (!function_exists('normalizar_campos_mayusculas')) {
    /**
     * Convierte a mayúsculas (y recorta) campos del POST antes de validarlos,
     * p. ej. RFC o CURP, para que un valor capturado en minúsculas no falle la
     * validación y obligue al ciudadano a recapturar el formulario.
     *
     * @param list<string> $campos
     */
    function normalizar_campos_mayusculas(\CodeIgniter\HTTP\IncomingRequest $request, array $campos): void
    {
        $post = $request->getPost() ?? [];
        $cambios = [];
        foreach ($campos as $campo) {
            if (isset($post[$campo]) && is_string($post[$campo])) {
                $cambios[$campo] = mb_strtoupper(trim($post[$campo]), 'UTF-8');
            }
        }
        if ($cambios === []) {
            return;
        }
        $request->setGlobal('post', array_merge($post, $cambios));
        $request->setGlobal('request', array_merge($request->getVar() ?? [], $cambios));
    }
}
