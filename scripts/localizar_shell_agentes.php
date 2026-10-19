<?php declare(strict_types=1);

/**
 * Traduce al espanol el shell HTML que genera Expo en public/agentes/index.html.
 *
 * Expo escribe su plantilla en ingles y aqui no se puede corregir desde
 * app/+html.tsx porque la salida es de tipo "single". El despliegue hace lo
 * mismo con sed (ver scripts/vercel-install.sh); esta version en PHP existe
 * para que la reconstruccion local deje el artifact identico al de produccion.
 *
 * Uso: php scripts/localizar_shell_agentes.php [ruta-index.html]
 */

$ruta = $argv[1] ?? dirname(__DIR__) . '/public/agentes/index.html';

if (! is_file($ruta)) {
    fwrite(STDERR, 'No existe el archivo: ' . $ruta . PHP_EOL);
    exit(1);
}

$html = (string) file_get_contents($ruta);
$antes = $html;

$reemplazos = [
    'lang="en"' => 'lang="es"',
    '/<title>[^<]*<\/title>/'                       => '<title>Agentes de Tránsito · Municipio de Uriangato</title>',
    '/You need to enable JavaScript to run this app\./' => 'Necesitas habilitar JavaScript para usar esta aplicación.',
    '/<!-- Use static rendering with Expo Router to support running without JavaScript\. -->/' => '<!-- Renderizado inicial: la interfaz aparece al cargar JavaScript. -->',
    '/<!-- The root element for your Expo app\. -->/'   => '<!-- Contenedor raíz de la aplicación. -->',
];

foreach ($reemplazos as $buscar => $reemplazo) {
    if (is_string($buscar) && ! str_starts_with($buscar, '/')) {
        $html = str_replace($buscar, $reemplazo, $html);
        continue;
    }
    $html = preg_replace($buscar, $reemplazo, $html, 1);
}

// El shell debe quedar en espanol: cualquier cadena en ingles que sobreviva es
// un cambio de Expo que todavia no esta cubierto aqui.
$pistasIngles = ['You need to enable', 'Use static rendering', 'root element for your Expo app', 'lang="en"'];
$sobrevivientes = array_values(array_filter($pistasIngles, static fn (string $p): bool => str_contains($html, $p)));

if ($sobrevivientes !== []) {
    fwrite(STDERR, 'Quedan textos en ingles en el shell: ' . implode(', ', $sobrevivientes) . PHP_EOL);
    exit(1);
}

if ($html === $antes) {
    echo 'Shell ya localizado, sin cambios.' . PHP_EOL;
    exit(0);
}

file_put_contents($ruta, $html);
echo 'Shell localizado a espanol: ' . $ruta . PHP_EOL;
