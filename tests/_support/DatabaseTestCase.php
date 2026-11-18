<?php declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';

    protected $migrate = true;

    protected $migrateOnce = false;

    protected $refresh = true;

    protected $namespace = null;

    protected $seed = '';

    protected $seedOnce = false;

    protected $basePath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Permite reutilizar un esquema PostgreSQL aislado ya preparado sin pasar
     * por el corredor histórico de migraciones. Cada prueba recibe tablas
     * vacías y secuencias reiniciadas; la conexión predeterminada no se usa.
     */
    protected function setUp(): void
    {
        $esPostgresPreparado = getenv('TEST_POSTGRES_SCHEMA_PREPARED') === '1';
        $seederDeLaPrueba    = '';
        if ($esPostgresPreparado) {
            $this->migrate = false;
            $this->refresh = false;
            // El seeder de la prueba corre después del TRUNCATE; si no, el
            // TRUNCATE borraría lo que acaba de sembrar.
            $seederDeLaPrueba = $this->seed;
            $this->seed       = '';
        }

        parent::setUp();

        if (! $esPostgresPreparado) {
            return;
        }

        $tablas = $this->db->query(
            "SELECT tablename FROM pg_tables WHERE schemaname = current_schema() AND tablename <> 'migrations' ORDER BY tablename"
        )->getResultArray();
        if ($tablas === []) {
            throw new \RuntimeException('El esquema PostgreSQL aislado no está preparado.');
        }

        $nombres = array_map(
            fn (array $fila): string => $this->db->escapeIdentifiers($fila['tablename']),
            $tablas
        );
        $this->db->query('TRUNCATE TABLE ' . implode(', ', $nombres) . ' RESTART IDENTITY CASCADE');

        if ($seederDeLaPrueba !== '') {
            $this->seed = $seederDeLaPrueba;
            $this->seed($seederDeLaPrueba);
        }
    }

    /**
     * En el modo PostgreSQL preparado el TRUNCATE también borra lo que siembran
     * las migraciones (tarifas base, rol de juez). Las pruebas que dependen de
     * esos datos se omiten ahí; lo cubre el ensayo de instalación limpia
     * (GUIA_MIGRACION.md, sección 5).
     */
    protected function omitirEnPostgresPreparado(string $motivo): void
    {
        if (getenv('TEST_POSTGRES_SCHEMA_PREPARED') === '1') {
            $this->markTestSkipped('Depende de datos que siembran las migraciones: ' . $motivo);
        }
    }
}
