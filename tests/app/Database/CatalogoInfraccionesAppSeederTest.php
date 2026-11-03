<?php declare(strict_types=1);

namespace Tests\Database;

use App\Database\Seeds\CatalogoInfraccionesAppSeeder;
use Tests\Support\DatabaseTestCase;

class CatalogoInfraccionesAppSeederTest extends DatabaseTestCase
{
    protected $seed = 'DatabaseSeeder';

    public function testInstalacionNuevaTieneLas201FaltasOficialesEnLaApp(): void
    {
        $oficiales = $this->db->table('catalogo_infracciones')->countAllResults();
        $this->assertSame(201, $oficiales);
        $this->assertSame($oficiales, $this->db->table('catalogo_infracciones_app')->where('activo', 1)->countAllResults());

        $falta = $this->db->table('catalogo_infracciones_app')->where('fundamento_legal', 'Art. 87 Frac. IV')->get()->getRow();
        $this->assertSame('No portar tarjeta de circulación original', $falta->descripcion);
        $this->assertSame('CONDUCTORES', $falta->categoria);
    }

    public function testRespetaLoEditadoYApagaLosEjemplosFueraDelReglamento(): void
    {
        // Estado de la base hosteada: solo los ejemplos de supabase/migrations/0005.
        $this->db->table('catalogo_infracciones_app')->emptyTable();
        $ejemplo = static fn (string $fundamento, string $descripcion, string $categoria, float $min, float $max) => [
            'fundamento_legal' => $fundamento, 'descripcion' => $descripcion, 'categoria' => $categoria,
            'monto_min_uma' => $min, 'monto_max_uma' => $max, 'activo' => 1, 'orden' => 1,
        ];
        $this->db->table('catalogo_infracciones_app')->insertBatch([
            // Sin editar y en el reglamento: toma texto y montos oficiales.
            $ejemplo('Art. 87 Frac. IV', 'Falta de tarjeta de circulación vigente del vehículo', 'DOCUMENTACIÓN', 5, 10),
            // Editado por el administrador (monto máximo 12): no se toca.
            $ejemplo('Art. 87 Frac. III', 'Falta de licencia de conducir para el tipo de vehículo o presentar licencia vencida', 'DOCUMENTACIÓN', 5, 12),
            // Sin editar y fuera del reglamento: se desactiva.
            $ejemplo('Art. 82', 'Conducir utilizando teléfonos celulares o dispositivos de comunicación móvil en movimiento', 'MANEJO Y VIALIDAD', 10, 20),
            // Capturada desde el panel: no se toca.
            $ejemplo('Mun. 1', 'Falta municipal capturada por el administrador', 'OTRAS', 1, 2),
        ]);

        $this->seed(CatalogoInfraccionesAppSeeder::class);
        $this->seed(CatalogoInfraccionesAppSeeder::class);

        $fila = fn (string $f) => $this->db->table('catalogo_infracciones_app')->where('fundamento_legal', $f)->get()->getRow();

        $this->assertSame('No portar tarjeta de circulación original', $fila('Art. 87 Frac. IV')->descripcion);
        $this->assertEqualsWithDelta(5.0, (float) $fila('Art. 87 Frac. IV')->monto_max_uma, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $fila('Art. 87 Frac. III')->monto_max_uma, 0.001);
        $this->assertSame(0, (int) $fila('Art. 82')->activo);
        $this->assertSame(1, (int) $fila('Mun. 1')->activo);

        // 201 oficiales (una con la edición del administrador) + la municipal, sin duplicados.
        $this->assertSame(202, $this->db->table('catalogo_infracciones_app')->where('activo', 1)->countAllResults());
        $this->assertSame(203, $this->db->table('catalogo_infracciones_app')->countAllResults());
    }
}
