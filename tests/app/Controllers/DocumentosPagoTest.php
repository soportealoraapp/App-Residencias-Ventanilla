<?php declare(strict_types=1);

namespace Tests\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * COB-05: quién puede imprimir la hoja de ayuda para el pago.
 *
 * La ruta solo pide sesión; el permiso fino vive en el controlador: el personal
 * de ventanilla imprime cualquier documento, el ciudadano únicamente los de sus
 * propios trámites y las multas no las ve ningún ciudadano.
 */
class DocumentosPagoTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private const FOLIO = 'FOL-DOC-0001';
    private const FOLIO_BOLETA = 'BOLETA-DOC-0001';

    private int $adminId;
    private int $operadorId;
    private int $ciudadanoId;
    private int $ajenoId;
    private int $juezId;
    private int $solicitudId;
    private int $boletaId;

    /** @var array<string, int> */
    private array $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = [];
        foreach (['administrador', 'operador_ventanilla', 'ciudadano', 'juez_calificador'] as $nombre) {
            $this->roles[$nombre] = $this->rol($nombre);
        }

        $this->adminId     = $this->crearUsuario('admin_doc', $this->roles['administrador']);
        $this->operadorId  = $this->crearUsuario('op_doc', $this->roles['operador_ventanilla']);
        $this->ciudadanoId = $this->crearUsuario('laura_doc', $this->roles['ciudadano']);
        $this->ajenoId     = $this->crearUsuario('ajeno_doc', $this->roles['ciudadano']);
        $this->juezId      = $this->crearUsuario('juez_doc', $this->roles['juez_calificador']);

        $this->db->table('solicitudes')->insert([
            'folio'           => self::FOLIO,
            'tramite'         => 'UR-TT-T-05',
            'ciudadano_id'    => $this->ciudadanoId,
            'estatus'         => 'Pago pendiente',
            'monto'           => 287.00,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        $this->solicitudId = (int) $this->db->insertID();

        $this->db->table('boletas_infracciones')->insert([
            'folio'            => self::FOLIO_BOLETA,
            'agente_placa'     => 'AGT-777',
            'fecha_infraccion' => date('Y-m-d'),
            'hora_infraccion'  => '09:15:00',
            'lugar'            => 'Blvd. Uriangato Norte',
            'infractor_nombre' => 'Tomás Vega Luna',
            'vehiculo_placas'  => 'GTO-555-A',
            'estado'           => 'recibida',
        ]);
        $this->boletaId = (int) $this->db->insertID();
    }

    /** Identificador del rol, creándolo solo si la migración no lo sembró. */
    private function rol(string $nombre): int
    {
        $rol = $this->db->table('roles')->where('nombre', $nombre)->get()->getRow();
        if ($rol !== null) {
            return (int) $rol->id;
        }

        $this->db->table('roles')->insert(['nombre' => $nombre, 'descripcion' => $nombre]);

        return (int) $this->db->insertID();
    }

    private function crearUsuario(string $username, int $roleId): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $roleId]);

        return $id;
    }

    private function como(int $id, string $username, string $rol): self
    {
        return $this->withSession([
            'user_id'  => $id,
            'username' => $username,
            'roles'    => [$rol],
        ]);
    }

    private function comoAdmin(): self
    {
        return $this->como($this->adminId, 'admin_doc', 'administrador');
    }

    private function comoOperador(): self
    {
        return $this->como($this->operadorId, 'op_doc', 'operador_ventanilla');
    }

    public function testAdminImprimeLaHojaDeUnaSolicitud(): void
    {
        $respuesta = $this->comoAdmin()->get('/documentos/hoja-pago/solicitud/' . self::FOLIO);
        $respuesta->assertOK();

        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringContainsString('Hoja de ayuda para el pago', $cuerpo);
        $this->assertStringContainsString(self::FOLIO, $cuerpo);
        $this->assertStringContainsString('BanBajío', $cuerpo);
        $this->assertStringContainsString('Total', $cuerpo);
        $this->assertStringContainsString('287.00', $cuerpo);
    }

    public function testOperadorImprimeConElIdNumericoDeLaSolicitud(): void
    {
        $respuesta = $this->comoOperador()->get('/documentos/hoja-pago/solicitud/' . $this->solicitudId);
        $respuesta->assertOK();
        $this->assertStringContainsString(self::FOLIO, $respuesta->response()->getBody());
    }

    public function testElCiudadanoDuenoImprimeSuPropiaHoja(): void
    {
        $this->como($this->ciudadanoId, 'laura_doc', 'ciudadano')
            ->get('/documentos/hoja-pago/solicitud/' . self::FOLIO)
            ->assertOK();
    }

    public function testUnCiudadanoAjenoNoPuedeImprimirLaHoja(): void
    {
        $respuesta = $this->como($this->ajenoId, 'ajeno_doc', 'ciudadano')
            ->get('/documentos/hoja-pago/solicitud/' . self::FOLIO);

        $respuesta->assertRedirect();
        $this->assertStringContainsString('No tienes permiso', (string) session('error'));
    }

    public function testElJuezImprimeLaHojaDeUnaMulta(): void
    {
        $respuesta = $this->como($this->juezId, 'juez_doc', 'juez_calificador')
            ->get('/documentos/hoja-pago/boleta/' . $this->boletaId);

        $respuesta->assertOK();
        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringContainsString('pago de la multa', $cuerpo);
        $this->assertStringContainsString(self::FOLIO_BOLETA, $cuerpo);
        $this->assertStringContainsString('Tomás Vega Luna', $cuerpo);
    }

    public function testUnCiudadanoNoEntraALasHojasDeMulta(): void
    {
        $respuesta = $this->como($this->ciudadanoId, 'laura_doc', 'ciudadano')
            ->get('/documentos/hoja-pago/boleta/' . $this->boletaId);

        $respuesta->assertRedirect();
        $this->assertStringContainsString('No tienes permiso', (string) session('error'));
    }

    public function testSinSesionLaRutaMandaAlLogin(): void
    {
        $this->get('/documentos/hoja-pago/solicitud/' . self::FOLIO)->assertRedirect();
    }

    public function testUnaSolicitudInexistenteRespondeCeroCuatro(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->comoAdmin()->get('/documentos/hoja-pago/solicitud/FOL-NO-EXISTE');
    }
}
