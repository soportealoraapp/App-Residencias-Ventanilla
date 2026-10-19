<?php declare(strict_types=1);

namespace Tests\Controllers\Views;

use Config\Services;
use ErrorException;
use Tests\Support\DatabaseTestCase;
use Throwable;

/**
 * Barre las vistas que declaran sus variables inyectadas con la convencion
 * get_defined_vars() + @var. El objetivo no es el diseno: es comprobar que,
 * cuando el controlador manda los datos, la vista se pinta sin avisos de
 * variable o indice indefinido y el valor del controlador gana sobre el
 * respaldo del encabezado. Sin esta prueba, un respaldo equivocado ("nuevo"
 * donde debia ir "editar", [] donde iba una lista) pasaria desapercibido
 * porque el respaldo nunca se nota con la suite tal como estaba.
 *
 * Se apoya en DatabaseTestCase porque las plantillas del panel consultan
 * parametros_app al pintar el menu.
 */
final class VistasInyectadasRenderTest extends DatabaseTestCase
{
    /** @var callable|null Handler previo, se restaura al terminar. */
    private $manejadorErrores = null;

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('renderer');
        helper(['url', 'form', 'security', 'url_helper_custom']);

        // Cualquier aviso (variable/indice indefinido, propiedad inexistente)
        // debe detener la prueba en seco en lugar de colarse en el HTML.
        $this->manejadorErrores = set_error_handler(
            static function (int $nivel, string $texto, string $archivo = '', int $linea = 0): bool {
                if ((error_reporting() & $nivel) === 0) {
                    return false;
                }
                throw new ErrorException($texto, 0, $nivel, $archivo, $linea);
            }
        );
    }

    protected function tearDown(): void
    {
        if ($this->manejadorErrores !== null) {
            restore_error_handler();
            $this->manejadorErrores = null;
        }
        Services::resetSingle('renderer');
        parent::tearDown();
    }

    private function pintar(string $vista, array $data): string
    {
        Services::resetSingle('renderer');

        return Services::renderer()
            ->setData($data)
            ->render($vista);
    }

    /** @return array<string, array{0:string,1:array<string,mixed>,2:string}> */
    public static function proveedorVistas(): array
    {
        $solicitud = (object) [
            'id'                     => 41,
            'folio'                  => 'UR-01-2026-0041',
            'tramite'                => 'UR-TT-T-01',
            'estatus'                => 'En proceso',
            'fecha_solicitud'        => '2026-03-04',
            'monto'                  => 287.50,
            'fecha_pago'             => '2026-03-05',
            'fecha_vigencia_inicio'  => '2026-03-05',
            'fecha_vigencia_fin'     => '2026-09-05',
        ];

        $documentos = [
            (object) [
                'id'              => 9,
                'tipo_documento'  => 'INE',
                'tamano_bytes'    => 358_400,
                'mime_type'       => 'image/jpeg',
                'nombre_original' => 'ine-frente.jpg',
            ],
        ];

        $datosCierre = [
            'referencia_pago'   => 'REF-2026-5566',
            'nombre_solicitante' => 'Ana Ruiz',
            'domicilio'         => 'Av. Juarez 120',
            'calle_tramo'       => 'Calle Morelos entre 5 de Mayo y Allende',
            'colonia'           => 'Centro',
            'tipo_cierre'       => 'Total',
            'fecha_cierre'      => '2026-05-01',
            'hora_inicio'       => '07:00',
            'hora_fin'          => '14:00',
            'motivo_evento'     => 'Kermada escolar',
        ];

        $usuario = (object) [
            'id'              => 7,
            'nombre'          => 'Bernardo',
            'apellido'        => 'Solis',
            'curp'            => 'SOXB800101HDFLRZ09',
            'rfc'             => 'SOXB800101AX1',
            'telefono'        => '4341112233',
            'email'           => 'bernardo@example.mx',
            'domicilio'       => 'Calle 5 de Mayo 33',
            'ciudad'          => 'Uriangato',
            'estado'          => 'Guanajuato',
            'username'        => 'bernardo',
            'activo'          => 1,
            'ine_frente'      => 'ine-frente.jpg',
            'ine_reverso'     => 'ine-reverso.jpg',
            'nombre_completo' => 'Bernardo Solis',
        ];

        return [
            'resumen carga y descarga' => [
                'portal/tramites/carga_descarga_resumen',
                [
                    'solicitud'   => $solicitud,
                    'datos'       => $datosCierre,
                    'documentos'  => $documentos,
                    'placeholder' => false,
                ],
                'ine-frente.jpg',
            ],
            'resumen cierre de calle' => [
                'portal/tramites/cierre_calle_resumen',
                [
                    'solicitud'  => $solicitud,
                    'datos'      => $datosCierre,
                    'documentos' => $documentos,
                ],
                'UR-01-2026-0041',
            ],
            'resumen permiso eventual' => [
                'portal/tramites/permiso_eventual_resumen',
                [
                    'solicitud'  => $solicitud,
                    'datos'      => $datosCierre,
                    'documentos' => $documentos,
                ],
                'UR-01-2026-0041',
            ],
            'permiso de cierre de calle' => [
                'portal/tramites/cierre_calle_permiso',
                [
                    'solicitud' => $solicitud,
                    'datos'     => $datosCierre,
                ],
                'Kermada escolar',
            ],
            'formulario cierre de calle' => [
                'portal/tramites/cierre_calle_form',
                ['tarifaMonto' => 150.50],
                '150.5',
            ],
            'formulario orden de plaqueo' => [
                'portal/tramites/orden_plaqueo_form',
                ['tarifaMonto' => 150.50],
                '150.5',
            ],
            'formulario permiso eventual' => [
                'portal/tramites/permiso_eventual_form',
                ['tarifaMonto' => 150.50],
                '150.5',
            ],
            'formulario constancia despintado' => [
                'portal/tramites/constancia_despintado_form',
                ['tarifaMonto' => 150.50],
                '150.5',
            ],
            'formulario concesion de transporte' => [
                'portal/tramites/concesion_transporte_form',
                [
                    'convocatoria' => (object) [
                        'id'                      => 3,
                        'bases'                   => 'BASES-CONVOCATORIA-3.pdf',
                        'fecha_publicacion'       => '2026-02-01',
                        'periodo_registro_inicio' => '2026-02-02',
                        'periodo_registro_fin'    => '2026-02-28',
                    ],
                    'tarifaMonto' => 150.50,
                ],
                '150.5',
            ],
            'formulario cita UR-02' => [
                'portal/tramites/ur02_cita_form',
                [
                    'solicitud'    => $solicitud,
                    'verificacion' => (object) ['fecha_cita' => '2026-04-10'],
                ],
                'UR-01-2026-0041',
            ],
            'catalogo de tramites' => [
                'portal/tramites',
                [
                    'formatosMap' => [
                        'UR-TT-T-01' => (object) ['id' => 1],
                        'UR-TT-T-02' => (object) ['id' => 2],
                        'UR-TT-T-03' => (object) ['id' => 3],
                        'UR-TT-T-04' => (object) ['id' => 4],
                        'UR-TT-T-05' => (object) ['id' => 5],
                        'UR-TT-T-06' => (object) ['id' => 6],
                        'UR-TT-T-07' => (object) ['id' => 7],
                    ],
                    'habilitaT06' => true,
                ],
                'UR-TT-T-07',
            ],
            'dashboard ciudadano' => [
                'portal/dashboard',
                [
                    'stats' => [
                        'total'       => 12,
                        'en_proceso'  => 5,
                        'concluidos'  => 6,
                        'pendientes'  => 1,
                    ],
                    'habilitaT06'        => true,
                    'ultimasSolicitudes' => [$solicitud],
                ],
                'UR-01-2026-0041',
            ],
            'mi perfil' => [
                'portal/mi_perfil',
                ['usuario' => $usuario],
                'bernardo@example.mx',
            ],
            'restablecer contrasena' => [
                'auth/reset',
                [
                    'user'  => $usuario,
                    'token' => 'token-demo-8899',
                ],
                'token-demo-8899',
            ],
            'formulario de usuarios' => [
                'admin/usuarios/form',
                [
                    'modo'      => 'editar',
                    'usuario'   => $usuario,
                    'rolActual' => 'ciudadano',
                    'roles'     => ['ciudadano' => 'Ciudadano', 'admin' => 'Administrador'],
                ],
                'Bernardo',
            ],
            'formulario de tarifas' => [
                'admin/catalogos/tarifas_form',
                [
                    'modo'   => 'editar',
                    'tarifa' => (object) [
                        'id'                 => 3,
                        'placeholder_oficial' => 'UR-TT-T-01',
                        'tramite'            => 'UR-TT-T-01',
                        'criterio'           => 'Por solicitud',
                        'monto'              => 287.50,
                        'vigente_desde'      => '2026-01-01',
                        'vigente_hasta'      => null,
                        'descripcion'        => 'Tarifa de prueba',
                    ],
                    'tramites' => ['UR-TT-T-01' => 'Carga y descarga'],
                ],
                'Editar Tarifa #3',
            ],
            'formulario de concesiones' => [
                'admin/catalogos/concesiones_form',
                [
                    'modo'   => 'editar',
                    'concesion' => (object) [
                        'id'               => 8,
                        'numero_titulo'    => 'TIT-0088',
                        'titular_actual'   => 'Ruta Oriente',
                        'vehiculo_placas'  => 'AB-74-239',
                        'vehiculo_num_serie' => 'SERIE-9933',
                        'tipo_persona'     => 'moral',
                        'vigencia_inicio'  => '2026-01-01',
                        'vigencia_fin'     => '2028-12-31',
                        'estatus'          => 'Vigente',
                    ],
                    'estatusOpciones' => ['Vigente' => 'Vigente', 'Cancelada' => 'Cancelada'],
                ],
                'TIT-0088',
            ],
            'formulario de boleta' => [
                'admin/app_transito/boleta_form',
                [
                    'boleta' => (object) [
                        'id'            => 12,
                        'folio'         => 'BOL-2026-0012',
                        'agente_nombre' => 'L. Ramírez',
                        'agente_placa'  => 'P-114',
                    ],
                    'estados' => ['Guanajuato' => 'Guanajuato'],
                    'tipos'   => ['infraccion' => 'Infraccion'],
                ],
                'BOL-2026-0012',
            ],
        ];
    }

    /**
     * @dataProvider proveedorVistas
     */
    public function testLaVistaSePintaConLosDatosDelControlador(string $vista, array $datos, string $aguja): void
    {
        try {
            $html = $this->pintar($vista, $datos);
        } catch (Throwable $e) {
            self::fail($vista . ' no se pudo renderizar: ' . $e->getMessage());
        }

        self::assertStringNotContainsString('Undefined variable', $html, $vista . ' dejo un aviso en el HTML.');
        self::assertStringNotContainsString('Undefined array key', $html, $vista . ' dejo un aviso en el HTML.');
        self::assertStringNotContainsString('Warning:', $html, $vista . ' dejo un aviso en el HTML.');
        self::assertStringContainsString($aguja, $html, $vista . ' ignora el dato enviado por el controlador.');
    }

    /**
     * El respaldo del encabezado no puede cambiar lo que ve el usuario cuando el
     * controlador si manda la variable: se pintan las dos formas reales que usa
     * CatalogoTarifasController (alta y edicion) y se compara el resultado.
     */
    public function testElRespaldoDelEncabezadoNoGanaCuandoLlegaDatoReal(): void
    {
        $tarifaVacia = (object) [
            'id'                  => null,
            'placeholder_oficial' => 1,
            'tramite'             => '',
            'criterio'            => '',
            'monto'               => '',
            'vigente_desde'       => '',
            'vigente_hasta'       => '',
            'descripcion'         => '',
        ];

        $alta = $this->pintar('admin/catalogos/tarifas_form', [
            'modo'     => 'nuevo',
            'tarifa'   => $tarifaVacia,
            'tramites' => ['UR-TT-T-01' => 'Carga y descarga'],
        ]);
        self::assertStringContainsString('Nueva Tarifa', $alta, 'El alta perdio su titulo.');

        $edicion = $this->pintar('admin/catalogos/tarifas_form', [
            'modo'     => 'editar',
            'tarifa'   => (object) [
                'id'                  => 55,
                'placeholder_oficial' => 'UR-TT-T-02',
                'tramite'             => 'UR-TT-T-02',
                'criterio'            => 'Por solicitud',
                'monto'               => 100.00,
                'vigente_desde'       => '2026-01-01',
                'vigente_hasta'       => null,
                'descripcion'         => 'Prueba respaldo',
            ],
            'tramites' => ['UR-TT-T-02' => 'Cierre de calle'],
        ]);
        self::assertStringContainsString('Editar Tarifa #55', $edicion, 'El modo del controlador pierde ante el respaldo.');
        self::assertStringNotContainsString('Nueva Tarifa', $edicion);
        self::assertStringContainsString('Cierre de calle', $edicion, 'La edicion ignora los tramites enviados.');
    }

    /**
     * La plantilla del panel leia la seccion pageTitle dos veces y la primera
     * lectura la consumia: el rotulo del <h1> y el del <title> salian vacios en
     * todas las pantallas de administracion. Se fija el contrato para que no
     * vuelva a romperse.
     */
    public function testElPanelConservaSuTituloEnLaBarraYEnLaPestana(): void
    {
        $html = $this->pintar('admin/catalogos/tarifas_form', [
            'modo'     => 'editar',
            'tarifa'   => (object) [
                'id'                  => 3,
                'placeholder_oficial' => 0,
                'tramite'             => 'UR-TT-T-01',
                'criterio'            => 'Por solicitud',
                'monto'               => 287.50,
                'vigente_desde'       => '2026-01-01',
                'vigente_hasta'       => null,
                'descripcion'         => 'Prueba titulo',
            ],
            'tramites' => ['UR-TT-T-01' => 'Carga y descarga'],
        ]);

        self::assertSame(
            1,
            preg_match('#<h1 class="h5 mb-0 text-truncate">(.*?)</h1>#s', $html, $encabezado),
            'La barra superior perdio su contenedor de titulo.'
        );
        self::assertSame('Editar Tarifa #3', trim($encabezado[1]), 'El panel se pinta sin titulo visible.');

        self::assertSame(
            1,
            preg_match('#<title>(.*?)</title>#s', $html, $titulo),
            'El documento no tiene <title>.'
        );
        self::assertSame('Editar Tarifa #3 - Panel Admin', trim($titulo[1]), 'La pestana del navegador no muestra de que pantalla se trata.');
    }

    public function testPaginadorRenderizaNavegacionAccesible(): void
    {
        // Se usa el servicio real para recorrer la misma cadena que pintan las
        // listas del panel: Pager -> getDetails() -> PagerRenderer -> la vista.
        $paginador = Services::pager();
        $paginador->setPath('/admin/usuarios', 'default');

        try {
            $html = $paginador->makeLinks(3, 10, 37, 'bootstrap');
        } catch (Throwable $e) {
            self::fail('pager_bootstrap_full no se pudo renderizar: ' . $e->getMessage());
        }

        self::assertStringContainsString('aria-label="Pagin', $html, 'El paginador perdio su etiqueta accesible.');
        self::assertStringContainsString('pagination', $html);
        self::assertStringContainsString('page=3', $html, 'El paginador no conserva la pagina actual en los enlaces.');
        self::assertStringContainsString('Anterior', $html, 'La pagina 3 debe permitir retroceder.');
        self::assertStringContainsString('Siguiente', $html, 'Con 37 elementos y 10 por pagina debe quedar avance.');
    }
}
