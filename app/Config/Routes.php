<?php declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setPrioritize(true);
$routes->setAutoRoute(true);

$routes->get('auth/login', 'AuthController::login', ['as' => 'login']);
$routes->post('auth/attempt-login', 'AuthController::attemptLogin');
$routes->get('auth/register', 'AuthController::register', ['as' => 'register']);
$routes->post('auth/attempt-register', 'AuthController::attemptRegister');
$routes->get('auth/logout', 'AuthController::logout', ['as' => 'logout']);
$routes->get('auth/forgot', 'AuthController::forgot');
$routes->post('auth/attempt-forgot', 'AuthController::attemptForgot');
$routes->get('auth/reset/(:any)', 'AuthController::reset/$1');
$routes->post('auth/reset/(:any)', 'AuthController::reset/$1');

$routes->group('admin', ['filter' => 'auth'], function($routes) {
    $routes->group('', ['filter' => 'role:administrador,operador_ventanilla'], function($routes) {
        $routes->get('dashboard', 'Admin\\AdminController::dashboard', ['as' => 'admin.dashboard']);
        $routes->get('solicitudes', 'Admin\\AdminController::listaSolicitudes', ['as' => 'admin.solicitudes']);
        $routes->get('solicitudes/(:any)', 'Admin\\AdminController::verSolicitud/$1', ['as' => 'admin.solicitud.ver']);
        $routes->post('solicitudes/cambiar-estatus/(:num)', 'Admin\\AdminController::cambiarEstatus/$1');
        $routes->post('solicitudes/dictamen-ur02/(:num)', 'Admin\\AdminController::registrarDictamenUr02/$1');
        $routes->get('solicitudes/descargar-documento/(:num)', 'Admin\\AdminController::descargarDocumento/$1');
        $routes->get('tarifas', 'Admin\\CatalogoTarifasController::index');
        $routes->get('tarifas/nuevo', 'Admin\\CatalogoTarifasController::formNuevo');
        $routes->post('tarifas/guardar', 'Admin\\CatalogoTarifasController::guardar');
        $routes->get('tarifas/editar/(:num)', 'Admin\\CatalogoTarifasController::formEditar/$1');
        $routes->post('tarifas/actualizar/(:num)', 'Admin\\CatalogoTarifasController::actualizar/$1');
        $routes->post('tarifas/eliminar/(:num)', 'Admin\\CatalogoTarifasController::eliminar/$1');
        $routes->get('concesiones', 'Admin\\CatalogoConcesionesController::index');
        $routes->get('concesiones/nuevo', 'Admin\\CatalogoConcesionesController::formNuevo');
        $routes->post('concesiones/guardar', 'Admin\\CatalogoConcesionesController::guardar');
        $routes->get('concesiones/editar/(:num)', 'Admin\\CatalogoConcesionesController::formEditar/$1');
        $routes->post('concesiones/actualizar/(:num)', 'Admin\\CatalogoConcesionesController::actualizar/$1');
        $routes->post('concesiones/eliminar/(:num)', 'Admin\\CatalogoConcesionesController::eliminar/$1');
        $routes->get('convocatorias/(:num)/evaluacion', 'Admin\\AdminController::evaluacionConvocatoria/$1');
        $routes->post('convocatorias/(:num)/seleccionar', 'Admin\\AdminController::seleccionarGanadorConvocatoria/$1');
    });
});

$routes->get('/', 'Home::index');
$routes->get('login', 'AuthController::login');
$routes->get('register', 'AuthController::register');
$routes->get('logout', 'AuthController::logout');


$routes->group('portal', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'Portal\PortalController::dashboard');       // /portal → dashboard
    $routes->get('dashboard', 'Portal\PortalController::dashboard', ['as' => 'portal.dashboard']);
    $routes->get('tramites', 'Portal\PortalController::tramites', ['as' => 'portal.tramites']);
    $routes->get('mis-solicitudes', 'Portal\PortalController::misSolicitudes', ['as' => 'portal.mis_solicitudes']);
    $routes->get('solicitud/(:any)', 'Portal\PortalController::verSolicitud/$1', ['as' => 'portal.ver_solicitud']);

    $routes->group('tramites', static function ($routes) {
        $routes->post('solicitudes', 'Portal\TramitesController::crear', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->get('solicitudes/(:segment)', 'Portal\TramitesController::consultar/$1', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->post('ur-02/solicitudes/(:num)/cita', 'Portal\TramitesController::agendarVerificacion/$1', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->post('ur-02/solicitudes/(:num)/resultado', 'Portal\TramitesController::registrarResultado/$1', ['filter' => 'role:administrador,operador_ventanilla']);
        $routes->get('ur-01/convocatorias/(:num)/solicitudes', 'Portal\TramitesController::listarConvocatoria/$1', ['filter' => 'role:administrador,operador_ventanilla']);
        $routes->post('ur-01/convocatorias/(:num)/seleccionar', 'Portal\TramitesController::seleccionar/$1', ['filter' => 'role:administrador,operador_ventanilla']);

        $routes->get('orden-plaqueo', 'Portal\TramiteOrdenPlaqueoController::formulario');
        $routes->get('ur-03', 'Portal\TramiteOrdenPlaqueoController::formulario');
        $routes->post('orden-plaqueo/guardar', 'Portal\TramiteOrdenPlaqueoController::guardar');
        $routes->post('ur-03/guardar', 'Portal\TramiteOrdenPlaqueoController::guardar');

        $routes->get('constancia-despintado', 'Portal\TramiteDespintadoController::formulario');
        $routes->get('ur-02', 'Portal\TramiteDespintadoController::formulario');
        $routes->post('constancia-despintado/guardar', 'Portal\TramiteDespintadoController::guardar');
        $routes->post('ur-02/guardar', 'Portal\TramiteDespintadoController::guardar');
        $routes->get('ur-02/solicitud/(:any)/cita', 'Portal\TramiteDespintadoController::agendarCitaForm/$1');
        $routes->post('ur-02/solicitud/(:any)/cita/guardar', 'Portal\TramiteDespintadoController::guardarCita/$1');

        $routes->get('concesion-transporte', 'Portal\TramiteConcesionTransporteController::formulario');
        $routes->get('ur-01', 'Portal\TramiteConcesionTransporteController::formulario');
        $routes->post('concesion-transporte/guardar', 'Portal\TramiteConcesionTransporteController::guardar');
        $routes->post('ur-01/guardar', 'Portal\TramiteConcesionTransporteController::guardar');

        $routes->get('cesion-concesion', 'Portal\TramiteCesionConcesionController::formulario');
        $routes->post('cesion-concesion/guardar', 'Portal\TramiteCesionConcesionController::guardar');
        $routes->get('cesion-concesion/validar-concesion/(:any)', 'Portal\TramiteCesionConcesionController::validarConcesionAjax/$1');

        $routes->get('carga-descarga/formulario', 'Portal\TramiteCargaDescargaController::formulario');
        $routes->get('carga-descarga', 'Portal\TramiteCargaDescargaController::formulario');
        $routes->post('carga-descarga/guardar', 'Portal\TramiteCargaDescargaController::guardar');
        $routes->post('carga-descarga/calcular-monto', 'Portal\TramiteCargaDescargaController::calcularMontoAjax');
        $routes->get('carga-descarga/calcular-monto', 'Portal\TramiteCargaDescargaController::calcularMontoAjax');
        $routes->get('carga-descarga/resumen/(:any)', 'Portal\TramiteCargaDescargaController::resumen/$1');
        $routes->post('carga-descarga/pagar/(:num)', 'Portal\TramiteCargaDescargaController::pagar/$1');
        $routes->get('carga-descarga/(:any)/descargar/(:num)', 'Portal\TramiteCargaDescargaController::descargarDocumento/$1/$2');
    });
});

return $routes;
