<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\SupabaseStorage;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Storage acepta las llaves nuevas de Supabase (`sb_secret_…`) además de las
 * heredadas (JWT), para poder revocar el secreto JWT heredado.
 */
class SupabaseStorageLlavesTest extends CIUnitTestCase
{
    private array $envOriginal = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['SUPABASE_URL', 'SUPABASE_ANON_KEY', 'SUPABASE_SERVICE_ROLE_KEY'] as $k) {
            $this->envOriginal[$k] = $_ENV[$k] ?? null;
        }
        $_ENV['SUPABASE_URL'] = 'https://proyecto.supabase.co';
    }

    protected function tearDown(): void
    {
        foreach ($this->envOriginal as $k => $v) {
            if ($v === null) {
                unset($_ENV[$k]);
            } else {
                $_ENV[$k] = $v;
            }
        }
        parent::tearDown();
    }

    private function encabezados(): array
    {
        $storage = new class () extends SupabaseStorage {
            public function exponer(): array
            {
                return $this->encabezadosAuth();
            }
        };

        return $storage->exponer();
    }

    public function testConLlaveSecretaNuevaVaSolaEnApikey(): void
    {
        $_ENV['SUPABASE_ANON_KEY'] = 'sb_publishable_xyz';
        $_ENV['SUPABASE_SERVICE_ROLE_KEY'] = 'sb_secret_abc';

        $this->assertSame(['apikey: sb_secret_abc'], $this->encabezados());
    }

    public function testConLlavesHeredadasUsaAnonYBearer(): void
    {
        $_ENV['SUPABASE_ANON_KEY'] = 'eyJ.anon.jwt';
        $_ENV['SUPABASE_SERVICE_ROLE_KEY'] = 'eyJ.service.jwt';

        $this->assertSame(['apikey: eyJ.anon.jwt', 'Authorization: Bearer eyJ.service.jwt'], $this->encabezados());
    }
}
