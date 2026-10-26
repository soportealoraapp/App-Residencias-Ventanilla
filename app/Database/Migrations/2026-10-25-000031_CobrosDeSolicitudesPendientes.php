<?php declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\CobroService;
use CodeIgniter\Database\Migration;

/**
 * COB-02: las solicitudes que ya estaban en "Pago pendiente" antes del módulo
 * de cobros no tenían su cobro, así que no aparecían en el listado. Esta
 * migración se los crea. Es idempotente y no toca solicitudes en otro estatus.
 *
 * Espejo para Supabase: supabase/migrations/0016_cobros_de_solicitudes_pendientes.sql
 */
class CobrosDeSolicitudesPendientes extends Migration
{
    public function up()
    {
        (new CobroService())->crearPendientesFaltantes();
    }

    public function down()
    {
        // Los cobros son registros contables: no se borran al revertir.
    }
}
