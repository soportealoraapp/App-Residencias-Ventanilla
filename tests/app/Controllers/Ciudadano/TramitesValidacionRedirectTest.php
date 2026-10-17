<?php declare(strict_types=1);

namespace Tests\Controllers\Ciudadano;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Al fallar la validación de un trámite, el ciudadano debe regresar al mismo
 * formulario con lo capturado, no al catálogo de trámites. No se usa
 * redirect()->back() porque la "página anterior" de la sesión puede haber
 * cambiado por otras peticiones (p. ej. el script de la barra de depuración).
 */
class TramitesValidacionRedirectTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insert(['nombre' => 'ciudadano', 'descripcion' => 'ciudadano']);
        $rolId = (int) $this->db->insertID();
        $this->db->table('users')->insert([
            'username'      => 'ciudadano1',
            'email'         => 'ciudadano@example.com',
            'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
            'activo'        => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->ciudadanoId, 'role_id' => $rolId]);
    }

    /** @return array<string, array{string, string}> */
    public static function formularios(): array
    {
        return [
            'permiso eventual'      => ['portal/tramites/permiso-eventual/guardar', 'portal/tramites/permiso-eventual'],
            'cierre de calle'       => ['portal/tramites/cierre-calle/guardar', 'portal/tramites/cierre-calle'],
            'orden de plaqueo'      => ['portal/tramites/orden-plaqueo/guardar', 'portal/tramites/orden-plaqueo'],
            'alias UR-03'           => ['portal/tramites/ur-03/guardar', 'portal/tramites/orden-plaqueo'],
            'constancia despintado' => ['portal/tramites/constancia-despintado/guardar', 'portal/tramites/constancia-despintado'],
            'carga y descarga'      => ['portal/tramites/carga-descarga/guardar', 'portal/tramites/carga-descarga/formulario'],
            'concesión transporte'  => ['portal/tramites/concesion-transporte/guardar', 'portal/tramites/concesion-transporte'],
            'mi perfil'             => ['portal/mi-perfil', 'portal/mi-perfil'],
        ];
    }

    /**
     * @dataProvider formularios
     */
    public function testErrorDeValidacionRegresaAlMismoFormulario(string $accion, string $formulario): void
    {
        $result = $this->withSession([
            'user_id'  => $this->ciudadanoId,
            'username' => 'ciudadano1',
            'roles'    => ['ciudadano'],
            // Simula que la "página anterior" quedó apuntando al catálogo.
            '_ci_previous_url' => site_url('portal/tramites'),
        ])->post($accion, ['nombre_razon_social' => 'Juan Prueba', 'domicilio' => 'Calle 1']);

        $result->assertRedirectTo(site_url($formulario));
        $this->assertSame('Juan Prueba', old('nombre_razon_social'));
    }
}
