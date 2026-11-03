<?php declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Seeds\CatalogoInfraccionesAppSeeder;
use CodeIgniter\Database\Migration;

/**
 * Pasa las 201 faltas oficiales del reglamento al catálogo administrable de la app
 * de tránsito, que hasta ahora solo tenía 14 faltas de ejemplo (o ninguna en local).
 *
 * La lógica vive en CatalogoInfraccionesAppSeeder para que una instalación nueva
 * (migrate y luego db:seed) llegue al mismo resultado. En una base sin catálogo
 * oficial todavía no hace nada; el seeder lo completa después.
 *
 * Equivale a supabase/migrations/0019_catalogo_oficial_infracciones.sql: hay que
 * mantener las dos rutas sincronizadas.
 */
class CatalogoAppConInfraccionesOficiales extends Migration
{
    public function up()
    {
        (new CatalogoInfraccionesAppSeeder(config('Database'), $this->db))->run();
    }

    public function down()
    {
        // Datos de catálogo: no se revierten para no borrar lo que el
        // administrador haya editado desde el panel.
    }
}
