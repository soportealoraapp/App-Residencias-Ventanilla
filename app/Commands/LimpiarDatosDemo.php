<?php declare(strict_types=1);

namespace App\Commands;

use App\Database\Seeds\DemoFuncionamientoSeeder;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Quita los datos de demostración que crea DemoFuncionamientoSeeder.
 *
 *     php spark demo:limpiar
 *
 * Solo borra lo que el seeder crea (folios, CURP y convocatoria deterministas);
 * no toca datos capturados por personas reales.
 */
class LimpiarDatosDemo extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:limpiar';
    protected $description = 'Borra los datos de demostración creados por DemoFuncionamientoSeeder.';
    protected $usage       = 'demo:limpiar [--force]';
    protected $options     = ['--force' => 'No pedir confirmación.'];

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        CLI::write('Base de datos: ' . $db->DBDriver . ' · ' . ($db->hostname ?: $db->database), 'yellow');

        if (! array_key_exists('force', $params) && CLI::getOption('force') === null
            && CLI::prompt('¿Borrar los datos de demostración?', ['n', 's']) !== 's') {
            CLI::write('Cancelado.');
            return;
        }

        foreach (DemoFuncionamientoSeeder::limpiar($db) as $tabla => $filas) {
            CLI::write(sprintf('  %-24s %d', $tabla, $filas));
        }
        CLI::write('Datos de demostración eliminados.', 'green');
    }
}
