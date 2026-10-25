<?php declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setPrioritize(true);
$routes->setAutoRoute(false);

$routes->get('auth/login', 'AuthController::login', ['as' => 'login']);
$routes->post('auth/attempt-login', 'AuthController::attemptLogin');
// Registro público SOLO de ciudadanos. Los agentes de tránsito los crea el admin.
$routes->get('auth/register', 'AuthController::register', ['as' => 'register']);
$routes->post('auth/attempt-register', 'AuthController::attemptRegister');
$routes->get('auth/logout', 'AuthController::logout', ['as' => 'logout']);
$routes->get('auth/forgot', 'AuthController::forgot');
$routes->post('auth/attempt-forgot', 'AuthController::attemptForgot');
$routes->get('auth/reset/(:any)', 'AuthController::reset/$1');
$routes->post('auth/reset/(:any)', 'AuthController::reset/$1');
$routes->get('auth/terminos', 'AuthController::terminos');
$routes->get('auth/privacidad', 'AuthController::privacidad');

// API Móvil - Infracciones, Agentes y Checklist
$routes->get('api/checklist-infracciones', 'Api\InfraccionesApiController::checklist');

$routes->options('api/agentes/login', 'Api\AgentesTransitoApiController::login');
$routes->post('api/agentes/login', 'Api\AgentesTransitoApiController::login');

$routes->get('api/parametros', 'Api\InfraccionesApiController::parametros');
$routes->options('api/parametros', 'Api\InfraccionesApiController::parametros');

$routes->options('api/infracciones/sincronizar', 'Api\InfraccionesApiController::sincronizar');
$routes->post('api/infracciones/sincronizar', 'Api\InfraccionesApiController::sincronizar', ['filter' => 'agenteToken']);
$routes->options('api/boletas/sincronizar', 'Api\InfraccionesApiController::sincronizar');
$routes->post('api/boletas/sincronizar', 'Api\InfraccionesApiController::sincronizar', ['filter' => 'agenteToken']);

$routes->options('api/infracciones/evidencias', 'Api\InfraccionesApiController::subirEvidencia');
$routes->post('api/infracciones/evidencias', 'Api\InfraccionesApiController::subirEvidencia', ['filter' => 'agenteToken']);

// Juez calificador: navegación propia y exclusivamente de lectura en COB-09.
$routes->group('juez', ['filter' => 'auth'], static function ($routes) {
    $routes->group('', ['filter' => 'role:juez_calificador'], static function ($routes) {
        $routes->get('', 'Juez\DashboardController::index', ['as' => 'juez.inicio']);
        $routes->get('boletas', 'Juez\DashboardController::boletas', ['as' => 'juez.boletas']);
        $routes->get('boletas/(:num)', 'Juez\DashboardController::detalle/$1', ['as' => 'juez.boleta']);
        $routes->post('boletas/(:num)/vista-previa', 'Juez\DashboardController::vistaPrevia/$1', ['as' => 'juez.boleta.previa']);
        $routes->post('boletas/(:num)/calificar', 'Juez\DashboardController::calificar/$1', ['as' => 'juez.boleta.calificar']);
    });
});

// Documentos de apoyo al pago (COB-05). La hoja se imprime desde el panel, el
// portal y el panel del juez; la ruta solo exige sesión y el permiso fino lo
// resuelve el controlador (cada ciudadano ve únicamente sus trámites).
$routes->group('documentos', ['filter' => 'auth'], static function ($routes) {
    $routes->get('hoja-pago/solicitud/(:segment)', 'DocumentosPagoController::hojaPagoSolicitud/$1', ['as' => 'documentos.hoja_pago_solicitud']);
    $routes->get('hoja-pago/boleta/(:num)', 'DocumentosPagoController::hojaPagoBoleta/$1', ['as' => 'documentos.hoja_pago_boleta']);
});

$routes->group('admin', ['filter' => 'auth'], function($routes) {
    $routes->group('', ['filter' => 'role:administrador,operador_ventanilla'], function($routes) {
        $routes->get('dashboard', 'Admin\\AdminController::dashboard', ['as' => 'admin.dashboard']);
        $routes->get('auditoria', 'Admin\\AdminController::auditoria', ['as' => 'admin.auditoria']);
        $routes->get('documentos', 'Admin\\DocumentosController::index', ['as' => 'admin.documentos']);
        $routes->get('verificaciones', 'Admin\\VerificacionesController::index', ['as' => 'admin.verificaciones']);
        $routes->get('sesiones-app', 'Admin\\SesionesAppController::index', ['as' => 'admin.sesiones_app']);
        $routes->get('solicitudes', 'Admin\\AdminController::listaSolicitudes', ['as' => 'admin.solicitudes']);
        $routes->get('solicitudes/nueva', 'Admin\\SolicitudPresencialController::index');
        $routes->post('solicitudes/nueva/buscar-ciudadano', 'Admin\\SolicitudPresencialController::buscarCiudadano');
        $routes->post('solicitudes/nueva/registrar-ciudadano', 'Admin\\SolicitudPresencialController::registrarCiudadano');
        $routes->post('solicitudes/nueva/guardar', 'Admin\\SolicitudPresencialController::guardar');
        $routes->get('solicitudes/nueva/(:segment)', 'Admin\\SolicitudPresencialController::formulario/$1');
        $routes->get('solicitudes/(:segment)', 'Admin\\AdminController::verSolicitud/$1', ['as' => 'admin.solicitud.ver']);
        $routes->post('solicitudes/cambiar-estatus/(:num)', 'Admin\\AdminController::cambiarEstatus/$1');
        $routes->post('solicitudes/ur04/evaluacion/(:num)', 'Admin\\AdminController::guardarEvaluacionUr04/$1');
        $routes->post('solicitudes/ur05/validacion/(:num)', 'Admin\\AdminController::validarCierreCalleUr05/$1');
        $routes->post('solicitudes/dictamen-ur02/(:num)', 'Admin\\AdminController::registrarDictamenUr02/$1');
        $routes->get('solicitudes/descargar-documento/(:num)', 'Admin\\AdminController::descargarDocumento/$1');
        $routes->post('solicitudes/validar-documento/(:num)', 'Admin\\AdminController::validarDocumento/$1');
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
        $routes->get('convocatorias', 'Admin\\ConvocatoriasController::index', ['as' => 'admin.convocatorias']);
        $routes->get('convocatorias/nueva', 'Admin\\ConvocatoriasController::formNuevo');
        $routes->post('convocatorias/guardar', 'Admin\\ConvocatoriasController::guardar');
        $routes->get('convocatorias/editar/(:num)', 'Admin\\ConvocatoriasController::formEditar/$1');
        $routes->post('convocatorias/actualizar/(:num)', 'Admin\\ConvocatoriasController::actualizar/$1');
        $routes->get('convocatorias/(:num)/evaluacion', 'Admin\\ConvocatoriasController::evaluacion/$1', ['as' => 'admin.convocatoria.evaluacion']);
        $routes->post('convocatorias/(:num)/seleccionar', 'Admin\\ConvocatoriasController::seleccionar/$1');
        $routes->get('convocatorias/(:num)/bases', 'Admin\\ConvocatoriasController::bases/$1');

        $routes->get('formatos', 'Admin\\AdminController::formatos');
        $routes->get('formatos/ver/(:segment)', 'Admin\\AdminController::verFormato/$1');
        $routes->post('formatos/subir', 'Admin\\AdminController::subirFormato');
        $routes->post('formatos/eliminar', 'Admin\\AdminController::eliminarFormato');

        // App Agentes de Tránsito (Configuración, UMA, Agentes, Catálogo, Boletas)
        $routes->get('app-transito', 'Admin\\AppTransitoController::index');
        $routes->post('app-transito/guardar-parametros', 'Admin\\AppTransitoController::guardarParametros');
        $routes->post('app-transito/agentes/guardar', 'Admin\\AppTransitoController::guardarAgente');
        $routes->post('app-transito/agentes/toggle/(:num)', 'Admin\\AppTransitoController::toggleEstadoAgente/$1');
        $routes->get('app-transito/boletas', 'Admin\\AppTransitoController::boletas');
        $routes->get('app-transito/catalogo', 'Admin\\AppTransitoController::catalogo');
        $routes->post('app-transito/catalogo/guardar', 'Admin\\AppTransitoController::guardarCatalogo');
        $routes->get('app-transito/boletas/editar/(:num)', 'Admin\\AppTransitoController::editarBoleta/$1');
        $routes->post('app-transito/boletas/actualizar/(:num)', 'Admin\\AppTransitoController::actualizarBoleta/$1');

        // Cuentas del portal: el ciudadano puede autorregistrarse; la ventanilla también las gestiona aquí.
        $routes->get('usuarios', 'Admin\\UsuariosController::index');
        $routes->get('usuarios/nuevo', 'Admin\\UsuariosController::formNuevo');
        $routes->post('usuarios/guardar', 'Admin\\UsuariosController::guardar');
        $routes->get('usuarios/editar/(:num)', 'Admin\\UsuariosController::formEditar/$1');
        $routes->post('usuarios/actualizar/(:num)', 'Admin\\UsuariosController::actualizar/$1');
        $routes->post('usuarios/toggle/(:num)', 'Admin\\UsuariosController::toggleEstado/$1');
    });

    // Bajas definitivas y permisos del operador: solo administrador.
    $routes->group('', ['filter' => 'role:administrador'], function($routes) {
        $routes->get('permisos-operador', 'Admin\\PermisosController::index');
        $routes->post('permisos-operador/guardar', 'Admin\\PermisosController::guardar');
        // Desglose global: todas las metricas del sistema con su desglose navegable.
        $routes->get('desglose', 'Admin\\DesgloseController::index', ['as' => 'admin.desglose']);
        $routes->post('usuarios/eliminar/(:num)', 'Admin\\UsuariosController::eliminar/$1');
        $routes->post('app-transito/agentes/eliminar/(:num)', 'Admin\\AppTransitoController::eliminarAgente/$1');
        $routes->post('app-transito/boletas/eliminar/(:num)', 'Admin\\AppTransitoController::eliminarBoleta/$1');
        $routes->post('app-transito/catalogo/eliminar/(:num)', 'Admin\\AppTransitoController::eliminarCatalogo/$1');
        $routes->post('convocatorias/eliminar/(:num)', 'Admin\\ConvocatoriasController::eliminar/$1');
    });
});

// Archivos del almacenamiento local (sin Supabase), servidos con URL firmada.
$routes->get('archivos/(:segment)', 'ArchivosLocalesController::servir/$1');

$routes->get('/', 'Home::index');

// Sondeo de conectividad de la app de agentes. NetInfo (@react-native-community)
// pregunta HEAD / cada pocos segundos y solo considera que hay internet si
// responde 200; CI4 no resuelve HEAD con las rutas GET, así que el sondeo caía en
// el 404, la app se declaraba sin conexión y el auto-sincronizado al recuperar la
// red no se disparaba. Se contesta vacío y sin pasar por un controlador para no
// abrir sesión en cada sondeo. El cuerpo se deja vacío de forma explícita: una
// respuesta HEAD no debe llevar HTML, y `response()` es un servicio compartido.
$routes->head('/', static fn() => response()->setStatusCode(200)->setBody(''));

// Atajos de acceso. Necesitan su propio nombre ('as') porque las rutas cortas
// coinciden con los nombres ya asignados arriba (login/register/logout en
// auth/login, auth/register y auth/logout); sin 'as', CI4 descarta la definición
// en silencio y el atajo responde 404.
$routes->get('login', 'AuthController::login', ['as' => 'acceso']);
$routes->get('register', 'AuthController::register', ['as' => 'registro']);
$routes->get('logout', 'AuthController::logout', ['as' => 'salida']);

// Fallback del enrutador de la app de agentes: /agentes/login y cualquier ruta
// hija, para que un enlace directo o un F5 no caigan en el 404 de la plataforma
// (ver AgentesWebController y EnrutadoServerlessTest). Las dos rutas apuntan al
// mismo método, así que cada una necesita su propio nombre: si la segunda hereda
// el de la primera, CI4 la descarta en silencio.
$routes->get('agentes', 'AgentesWebController::shell', ['as' => 'agentes.inicio']);
$routes->get('agentes/(:any)', 'AgentesWebController::shell', ['as' => 'agentes.ruta']);


$routes->group('portal', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'Ciudadano\PortalController::dashboard');       // /portal → dashboard
    $routes->get('dashboard', 'Ciudadano\PortalController::dashboard', ['as' => 'portal.dashboard']);
    $routes->get('tramites', 'Ciudadano\PortalController::tramites', ['as' => 'portal.tramites']);
    $routes->get('mis-solicitudes', 'Ciudadano\PortalController::misSolicitudes', ['as' => 'portal.mis_solicitudes']);
    $routes->get('solicitud/(:segment)', 'Ciudadano\PortalController::verSolicitud/$1', ['as' => 'portal.ver_solicitud']);
    $routes->get('solicitud/(:segment)/descargar/(:num)', 'Ciudadano\PortalController::descargarDocumento/$1/$2', ['as' => 'portal.solicitud.descargar']);

    $routes->get('mi-perfil', 'Ciudadano\PortalController::miPerfil', ['as' => 'portal.mi_perfil']);
    $routes->post('mi-perfil', 'Ciudadano\PortalController::guardarPerfil');

    $routes->get('formato/(:segment)', 'Ciudadano\PortalController::descargarFormato/$1');

    $routes->group('tramites', static function ($routes) {
        $routes->post('solicitudes', 'Ciudadano\TramitesController::crear', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->get('solicitudes/(:segment)', 'Ciudadano\TramitesController::consultar/$1', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->post('ur-02/solicitudes/(:num)/cita', 'Ciudadano\TramitesController::agendarVerificacion/$1', ['filter' => 'role:administrador,operador_ventanilla,ciudadano']);
        $routes->post('ur-02/solicitudes/(:num)/resultado', 'Ciudadano\TramitesController::registrarResultado/$1', ['filter' => 'role:administrador,operador_ventanilla']);
        $routes->get('ur-01/convocatorias/(:num)/solicitudes', 'Ciudadano\TramitesController::listarConvocatoria/$1', ['filter' => 'role:administrador,operador_ventanilla']);
        $routes->post('ur-01/convocatorias/(:num)/seleccionar', 'Ciudadano\TramitesController::seleccionar/$1', ['filter' => 'role:administrador,operador_ventanilla']);

        $routes->get('orden-plaqueo', 'Ciudadano\TramiteOrdenPlaqueoController::formulario');
        $routes->get('ur-03', 'Ciudadano\TramiteOrdenPlaqueoController::formulario');
        $routes->post('orden-plaqueo/guardar', 'Ciudadano\TramiteOrdenPlaqueoController::guardar');
        $routes->post('ur-03/guardar', 'Ciudadano\TramiteOrdenPlaqueoController::guardar');

        $routes->get('constancia-despintado', 'Ciudadano\TramiteDespintadoController::formulario');
        $routes->get('ur-02', 'Ciudadano\TramiteDespintadoController::formulario');
        $routes->post('constancia-despintado/guardar', 'Ciudadano\TramiteDespintadoController::guardar');
        $routes->post('ur-02/guardar', 'Ciudadano\TramiteDespintadoController::guardar');
        $routes->get('ur-02/solicitud/(:any)/cita', 'Ciudadano\TramiteDespintadoController::agendarCitaForm/$1');
        $routes->post('ur-02/solicitud/(:any)/cita/guardar', 'Ciudadano\TramiteDespintadoController::guardarCita/$1');

        $routes->get('concesion-transporte', 'Ciudadano\TramiteConcesionTransporteController::formulario');
        $routes->get('ur-01', 'Ciudadano\TramiteConcesionTransporteController::formulario');
        $routes->post('concesion-transporte/guardar', 'Ciudadano\TramiteConcesionTransporteController::guardar');
        $routes->post('ur-01/guardar', 'Ciudadano\TramiteConcesionTransporteController::guardar');

        $routes->get('cesion-concesion', 'Ciudadano\TramiteCesionConcesionController::formulario');
        $routes->post('cesion-concesion/guardar', 'Ciudadano\TramiteCesionConcesionController::guardar');
        $routes->get('cesion-concesion/validar-concesion/(:any)', 'Ciudadano\TramiteCesionConcesionController::validarConcesionAjax/$1');

        $routes->get('carga-descarga/formulario', 'Ciudadano\TramiteCargaDescargaController::formulario');
        $routes->get('carga-descarga', 'Ciudadano\TramiteCargaDescargaController::formulario');
        $routes->post('carga-descarga/guardar', 'Ciudadano\TramiteCargaDescargaController::guardar');
        $routes->post('carga-descarga/calcular-monto', 'Ciudadano\TramiteCargaDescargaController::calcularMontoAjax');
        $routes->get('carga-descarga/calcular-monto', 'Ciudadano\TramiteCargaDescargaController::calcularMontoAjax');
        $routes->get('carga-descarga/resumen/(:any)', 'Ciudadano\TramiteCargaDescargaController::resumen/$1');
        $routes->post('carga-descarga/pagar/(:num)', 'Ciudadano\TramiteCargaDescargaController::pagar/$1');
        $routes->get('carga-descarga/(:any)/descargar/(:num)', 'Ciudadano\TramiteCargaDescargaController::descargarDocumento/$1/$2');

        $routes->get('permiso-eventual', 'Ciudadano\TramitePermisoEventualController::formulario');
        $routes->post('permiso-eventual/guardar', 'Ciudadano\TramitePermisoEventualController::guardar');
        $routes->get('permiso-eventual/resumen/(:any)', 'Ciudadano\TramitePermisoEventualController::resumen/$1');
        $routes->post('permiso-eventual/pagar/(:num)', 'Ciudadano\TramitePermisoEventualController::pagar/$1');

        $routes->get('cierre-calle', 'Ciudadano\TramiteCierreCalleController::formulario');
        $routes->post('cierre-calle/guardar', 'Ciudadano\TramiteCierreCalleController::guardar');
        $routes->get('cierre-calle/resumen/(:any)', 'Ciudadano\TramiteCierreCalleController::resumen/$1');
        $routes->post('cierre-calle/pagar/(:num)', 'Ciudadano\TramiteCierreCalleController::pagar/$1');
        $routes->get('cierre-calle/permiso/(:any)', 'Ciudadano\TramiteCierreCalleController::permiso/$1');
    });
});

return $routes;
