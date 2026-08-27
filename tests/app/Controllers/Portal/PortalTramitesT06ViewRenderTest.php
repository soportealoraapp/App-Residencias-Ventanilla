<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class PortalTramitesT06ViewRenderTest extends CIUnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        putenv('APP_ENABLE_UR_TT_T_06=true');
        $_ENV['APP_ENABLE_UR_TT_T_06'] = 'true';
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('APP_ENABLE_UR_TT_T_06=true');
        $_ENV['APP_ENABLE_UR_TT_T_06'] = 'true';
        helper(['url', 'form', 'url_helper_custom']);
    }

    public function testVistaPortalTramitesMuestraT06ConLinkActivoCuandoHabilitaT06(): void
    {
        $renderer = Services::renderer();
        $html = $renderer->setData(['habilitaT06' => true])->render('portal/tramites');

        $t06Pos = strpos($html, 'UR-TT-T-06');
        $this->assertNotFalse($t06Pos, "La vista NO contiene la tarjeta UR-TT-T-06 aunque habilitaT06=true");

        $t06Card = substr($html, $t06Pos, 3500);

        $this->assertStringNotContainsString('En desarrollo', $t06Card, "❌ Tarjeta T-06 sigue mostrando 'En desarrollo'");
        $this->assertStringNotContainsString('Próximamente', $t06Card, "❌ Tarjeta T-06 sigue con botón 'Próximamente'");
        $this->assertStringNotContainsString('disabled>', $t06Card, "❌ Tarjeta T-06 tiene botón disabled");
        $this->assertStringNotContainsString('btn-outline-secondary', $t06Card, "❌ Tarjeta T-06 tiene estilo gris outline deshabilitado");

        $this->assertStringContainsString('/portal/tramites/cesion-concesion', $t06Card, "❌ Falta link real site_url(/portal/tramites/cesion-concesion) en tarjeta T-06");
        $this->assertStringContainsString('btn btn-success btn-lg w-100 shadow-sm', $t06Card, "❌ Tarjeta T-06 no tiene botón success verde (tiene que ser igual estilo success que UR-01)");
        $this->assertStringContainsString('Iniciar trámite', $t06Card, "❌ Tarjeta T-06 no tiene el texto 'Iniciar trámite'");
        $this->assertStringContainsString('Disponible para solicitud', $t06Card, "❌ Tarjeta T-06 no tiene badge 'Disponible para solicitud'");
        $this->assertStringContainsString('bg-success-subtle text-success', $t06Card, "❌ Tarjeta T-06 no tiene badge success verde");
        $this->assertStringContainsString('bi bi-play-circle', $t06Card, "❌ Tarjeta T-06 no tiene icono bi-play-circle (ícono de reproducir/iniciar)");
    }

    public function testVistaPortalTramitesOcultaT06CuandoDeshabilitado(): void
    {
        putenv('APP_ENABLE_UR_TT_T_06=false');
        $_ENV['APP_ENABLE_UR_TT_T_06'] = 'false';

        $renderer = Services::renderer();
        $html = $renderer->setData(['habilitaT06' => false])->render('portal/tramites');

        $this->assertStringNotContainsString('UR-TT-T-06', $html, "UR-TT-T-06 debe estar OCULTO cuando habilitaT06=false");
    }
}
