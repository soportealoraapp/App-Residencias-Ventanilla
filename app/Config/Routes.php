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
$routes->get('auth/terminos', 'AuthController::terminos');
$routes->get('auth/privacidad', 'AuthController::privacidad');

$routes->group('admin', ['filter' => 'auth'], function($routes) {
    $routes->group('', ['filter' => 'role:administrador,operador_ventanilla'], function($routes) {
        $routes->get('dashboard', 'Admin\\AdminController::dashboard', ['as' => 'admin.dashboard']);
        $routes->get('solicitudes', 'Admin\\AdminController::listaSolicitudes', ['as' => 'admin.solicitudes']);
        $routes->get('solicitudes/(:any)', 'Admin\\AdminController::verSolicitud/$1', ['as' => 'admin.solicitud.ver']);
        $routes->post('solicitudes/cambiar-estatus/(:num)', 'Admin\\AdminController::cambiarEstatus/$1');
        $routes->post('solicitudes/ur04/evaluacion/(:num)', 'Admin\\AdminController::guardarEvaluacionUr04/$1');
        $routes->post('solicitudes/ur05/validacion/(:num)', 'Admin\\AdminController::validarCierreCalleUr05/$1');
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

        $routes->get('formatos', 'Admin\\AdminController::formatos');
        $routes->post('formatos/subir', 'Admin\\AdminController::subirFormato');
        $routes->post('formatos/eliminar', 'Admin\\AdminController::eliminarFormato');
    });
});

$routes->get('/', 'Home::index');
$routes->get('login', 'AuthController::login');
$routes->get('register', 'AuthController::register');
$routes->get('logout', 'AuthController::logout');


$routes->group('portal', ['filter' => 'auth,adminRedirect'], static function ($routes) {
    $routes->get('', 'Portal\PortalController::dashboard');       // /portal → dashboard
    $routes->get('dashboard', 'Portal\PortalController::dashboard', ['as' => 'portal.dashboard']);
    $routes->get('tramites', 'Portal\PortalController::tramites', ['as' => 'portal.tramites']);
    $routes->get('mis-solicitudes', 'Portal\PortalController::misSolicitudes', ['as' => 'portal.mis_solicitudes']);
    $routes->get('solicitud/(:any)', 'Portal\PortalController::verSolicitud/$1', ['as' => 'portal.ver_solicitud']);
    $routes->get('solicitud/(:any)/descargar/(:num)', 'Portal\PortalController::descargarDocumento/$1/$2', ['as' => 'portal.solicitud.descargar']);

    $routes->get('mi-perfil', 'Portal\PortalController::miPerfil', ['as' => 'portal.mi_perfil']);
    $routes->post('mi-perfil', 'Portal\PortalController::guardarPerfil');

    $routes->get('formato/(:segment)', 'Portal\PortalController::descargarFormato/$1');

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

        $routes->get('permiso-eventual', 'Portal\TramitePermisoEventualController::formulario');
        $routes->post('permiso-eventual/guardar', 'Portal\TramitePermisoEventualController::guardar');
        $routes->get('permiso-eventual/resumen/(:any)', 'Portal\TramitePermisoEventualController::resumen/$1');
        $routes->post('permiso-eventual/pagar/(:num)', 'Portal\TramitePermisoEventualController::pagar/$1');

        $routes->get('cierre-calle', 'Portal\\TramiteCierreCalleController::formulario');
        $routes->post('cierre-calle/guardar', 'Portal\\TramiteCierreCalleController::guardar');
        $routes->get('cierre-calle/resumen/(:any)', 'Portal\\TramiteCierreCalleController::resumen/$1');
        $routes->post('cierre-calle/pagar/(:num)', 'Portal\\TramiteCierreCalleController::pagar/$1');
        $routes->get('cierre-calle/permiso/(:any)', 'Portal\\TramiteCierreCalleController::permiso/$1');
    });
});

return $routes;
