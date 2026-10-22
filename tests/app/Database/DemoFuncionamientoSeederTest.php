<?php declare(strict_types=1);

namespace Tests\Database;

use App\Database\Seeds\DemoFuncionamientoSeeder;
use Tests\Support\DatabaseTestCase;

class DemoFuncionamientoSeederTest extends DatabaseTestCase
{
    protected $seed = 'DatabaseSeeder';

    public function testSiembraEsIdempotenteYSeLimpiaPorCompleto(): void
    {
        ob_start();
        $this->seed(DemoFuncionamientoSeeder::class);
        $this->seed(DemoFuncionamientoSeeder::class);
        ob_end_clean();

        $folios = array_map(static fn (array $s) => DemoFuncionamientoSeeder::folioSolicitud($s[0]), DemoFuncionamientoSeeder::SOLICITUDES);
        $this->assertSame(count($folios), $this->db->table('solicitudes')->whereIn('folio', $folios)->countAllResults());
        $this->assertSame(count(DemoFuncionamientoSeeder::CIUDADANOS), $this->db->table('users')->whereIn('curp', DemoFuncionamientoSeeder::curps())->countAllResults());
        $this->assertSame(DemoFuncionamientoSeeder::TOTAL_BOLETAS, $this->db->table('boletas_infracciones')->like('folio', 'BOLETA-URI-2026-', 'after')->countAllResults());

        // Cada solicitud termina en su estatus y su historial llega a él.
        $s = $this->db->table('solicitudes')->where('folio', DemoFuncionamientoSeeder::folioSolicitud('s18'))->get()->getRow();
        $this->assertSame('Permiso emitido', $s->estatus);
        $ultimo = $this->db->table('historial_estatus')->where('solicitud_id', $s->id)->orderBy('id', 'DESC')->get()->getRow();
        $this->assertSame('Permiso emitido', $ultimo->estatus_nuevo);

        DemoFuncionamientoSeeder::limpiar($this->db);

        $this->assertSame(0, $this->db->table('solicitudes')->whereIn('folio', $folios)->countAllResults());
        $this->assertSame(0, $this->db->table('users')->whereIn('curp', DemoFuncionamientoSeeder::curps())->countAllResults());
        $this->assertSame(0, $this->db->table('boletas_infracciones')->countAllResults());
        $this->assertSame(0, $this->db->table('convocatorias')->where('titulo', DemoFuncionamientoSeeder::TITULO_CONVOCATORIA)->countAllResults());
    }
}
