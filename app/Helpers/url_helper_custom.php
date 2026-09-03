<?php declare(strict_types=1);

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
    function formatear_fecha(?string $fecha, string $formato = 'd/m/Y H:i'): string
    {
        if (empty($fecha)) {
            return '-';
        }
        $dt = new \DateTime($fecha);
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
