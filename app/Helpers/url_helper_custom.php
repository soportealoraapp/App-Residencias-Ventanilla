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
            'UR-TT-T-07' => 'Permiso de Carga y Descarga',
            'UR-TT-T-06' => 'Cesión de Concesión',
            default      => 'Trámite desconocido',
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
