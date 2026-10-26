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
        if ($esPostgresPreparado) {
            $this->migrate = false;
            $this->refresh = false;
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
    }
}
