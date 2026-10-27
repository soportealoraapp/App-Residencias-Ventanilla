<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\ParametroAppModel;
use App\Models\AgenteTransitoModel;
use App\Models\CatalogoInfraccionAppModel;
use App\Models\BoletaInfraccionModel;
use App\Models\AuditoriaModel;
use App\Libraries\BoletaAccionesService;
use Throwable;

class AppTransitoController extends Controller
{
    private const ESTADOS_BOLETA = [
        'recibida'  => 'Recibida',
        'procesada' => 'Procesada',
        'calificada' => 'Calificada',
        'pagada'    => 'Pagada',
        'cancelada' => 'Cancelada',
        'archivada' => 'Archivada',
    ];

    private const TIPOS_VEHICULO = [
        'particular'         => 'Particular',
        'transporte_publico' => 'Transporte público',
        'carga'              => 'Carga',
        'motocicleta'        => 'Motocicleta',
        'otro'               => 'Otro',
    ];

    protected ParametroAppModel $paramModel;
    protected AgenteTransitoModel $agenteModel;
    protected CatalogoInfraccionAppModel $catalogoModel;
    protected BoletaInfraccionModel $boletaModel;
    protected AuditoriaModel $auditoriaModel;

    public function __construct()
    {
        $this->paramModel     = new ParametroAppModel();
        $this->agenteModel    = new AgenteTransitoModel();
        $this->catalogoModel  = new CatalogoInfraccionAppModel();
        $this->boletaModel    = new BoletaInfraccionModel();
        $this->auditoriaModel = new AuditoriaModel();
    }

    /**
     * Dashboard y Configuración Principal de la App de Agentes.
     */
    public function index()
    {
        $parametros = $this->paramModel->findAll();
        $mapParametros = [];
        foreach ($parametros as $p) {
            $mapParametros[$p->clave] = $p->valor;
        }

        $agentes = $this->agenteModel->orderBy('placa', 'ASC')->findAll();
        $totalBoletas = $this->boletaModel->countAllResults();
        $ultimasBoletas = $this->boletaModel->orderBy('id', 'DESC')->findAll(6);
        $totalCatalogo = $this->catalogoModel->where('activo', 1)->countAllResults();

        $valorUma = (float)($mapParametros['valor_uma_vigente'] ?? '117.31');

        return view('admin/app_transito/index', [
            'parametros'     => $mapParametros,
            'agentes'        => $agentes,
            'totalBoletas'   => $totalBoletas,
            'ultimasBoletas' => $ultimasBoletas,
            'totalCatalogo'  => $totalCatalogo,
            'valorUma'       => $valorUma,
        ]);
    }

    /**
     * Guardar actualización de parámetros generales (UMA, fotos, versión).
     */
    public function guardarParametros()
    {
        $rules = [
            'valor_uma_vigente' => [
                'rules' => 'required|decimal|greater_than[0]',
                'label' => 'Valor de la UMA vigente',
            ],
            'fotos_obligatorias' => [
                'rules' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[5]',
                'label' => 'Fotografías obligatorias',
            ],
            'app_version' => [
                'rules' => 'required|max_length[20]',
                'label' => 'Versión de la app',
            ],
            'sync_intervalo_segundos' => [
                'rules' => 'required|integer|greater_than_equal_to[30]',
                'label' => 'Intervalo de sincronización',
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $campos = [
            'valor_uma_vigente'       => $this->request->getPost('valor_uma_vigente'),
            'fotos_obligatorias'      => $this->request->getPost('fotos_obligatorias'),
            'app_version'             => $this->request->getPost('app_version'),
            'sync_intervalo_segundos' => $this->request->getPost('sync_intervalo_segundos'),
            'municipio'               => $this->request->getPost('municipio') ?? 'Uriangato, Guanajuato',
            'modo_estricto_fotos'     => $this->request->getPost('modo_estricto_fotos') === 'true' ? 'true' : 'false',
        ];

        foreach ($campos as $clave => $valor) {
            $this->paramModel->setValor($clave, (string)$valor);
        }

        $userId = session('user_id');
        $this->auditoriaModel->registrar('parametros_app', 1, 'actualizar', $userId, $campos);

        return redirect()->to(site_url('admin/app-transito'))
            ->with('message', 'Parámetros de la App Móvil (incluido Valor UMA: $' . $campos['valor_uma_vigente'] . ' MXN) actualizados con éxito.');
    }

    /**
     * Guardar o actualizar un Agente de Tránsito.
     */
    public function guardarAgente()
    {
        $id = $this->request->getPost('id');
        $esNuevo = empty($id);
        // La placa se guarda en mayúsculas: se normaliza antes de validar que sea
        // única, para que "agt-305" no pase si ya existe "AGT-305".
        normalizar_campos_mayusculas($this->request, ['placa']);

        $rules = [
            'placa' => [
                'rules' => $esNuevo
                    ? 'required|max_length[20]|is_unique[agentes_transito.placa]'
                    : "required|max_length[20]|is_unique[agentes_transito.placa,id,{$id}]",
                'label' => 'Número de Placa / Credencial',
            ],
            'nombre_completo' => [
                'rules' => 'required|min_length[3]|max_length[180]',
                'label' => 'Nombre Completo del Agente',
            ],
            'sector' => [
                'rules' => 'permit_empty|max_length[100]',
                'label' => 'Sector o Cuadrante Asignado',
            ],
            'rol' => [
                'rules' => 'required|in_list[operativo,coordinador,supervisor]',
                'label' => 'Rol Operativo',
            ],
        ];

        if ($esNuevo || !empty($this->request->getPost('password'))) {
            $rules['password'] = [
                'rules' => 'required|min_length[8]|max_length[50]',
                'label' => 'Contraseña de Acceso Móvil',
            ];
        }

        if (!$this->validate($rules)) {
            // Se vuelve a abrir el modal con lo capturado para corregirlo.
            return redirect()->to(site_url('admin/app-transito'))->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('reabrir_modal_agente', true);
        }

        $data = [
            'placa'           => strtoupper(trim((string)$this->request->getPost('placa'))),
            'nombre_completo' => trim((string)$this->request->getPost('nombre_completo')),
            'rol'             => $this->request->getPost('rol'),
            'sector'          => trim((string)$this->request->getPost('sector')) ?: 'Sector Centro',
        ];
        // Al editar se conserva el estado actual salvo que el formulario lo indique explícitamente.
        $activo = $this->request->getPost('activo');
        if ($esNuevo || $activo !== null) {
            $data['activo'] = $activo === '0' ? 0 : 1;
        }

        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        try {
            if ($esNuevo) {
                if ($this->agenteModel->insert($data) === false) {
                    throw new \RuntimeException(implode(' ', $this->agenteModel->errors()));
                }
                $nuevoId = $this->agenteModel->getInsertID();
                unset($data['password_hash']);
                $this->auditoriaModel->registrar('agentes_transito', $nuevoId, 'crear', session('user_id'), $data);
                $msg = "Agente {$data['placa']} registrado exitosamente.";
            } else {
                $this->agenteModel->update($id, $data);
                unset($data['password_hash']);
                $this->auditoriaModel->registrar('agentes_transito', (int)$id, 'editar', session('user_id'), $data);
                $msg = "Datos del Agente {$data['placa']} actualizados.";
            }
        } catch (\Throwable $e) {
            log_message('error', 'Alta/edición de agente de tránsito: ' . $e->getMessage());
            return redirect()->to(site_url('admin/app-transito'))->withInput()
                ->with('errors', ['agente' => 'No fue posible guardar el agente en la base de datos. Si el problema continúa, avisa al equipo técnico (puede requerir resincronizar las secuencias de la base).'])
                ->with('reabrir_modal_agente', true);
        }

        return redirect()->to(site_url('admin/app-transito'))->with('message', $msg);
    }

    /**
     * Alternar estado activo de un agente.
     */
    public function toggleEstadoAgente(int $id)
    {
        $agente = $this->agenteModel->find($id);
        if (!$agente) {
            return redirect()->to(site_url('admin/app-transito'))->with('errors', ['Agente no encontrado.']);
        }

        $nuevoEstado = $agente->activo == 1 ? 0 : 1;
        $this->agenteModel->update($id, ['activo' => $nuevoEstado]);

        $this->auditoriaModel->registrar('agentes_transito', $id, 'toggle_estado', session('user_id'), [
            'activo_anterior' => $agente->activo,
            'activo_nuevo'    => $nuevoEstado,
        ]);

        return redirect()->to(site_url('admin/app-transito'))
            ->with('message', "El agente {$agente->placa} ha sido " . ($nuevoEstado == 1 ? 'activado' : 'desactivado') . '.');
    }

    /**
     * Eliminar un agente. Si ya emitió boletas se conserva para no perder la
     * trazabilidad y se sugiere desactivarlo.
     */
    public function eliminarAgente(int $id)
    {
        $agente = $this->agenteModel->find($id);
        if (!$agente) {
            return redirect()->to(site_url('admin/app-transito'))->with('errors', ['Agente no encontrado.']);
        }

        $boletas = $this->boletaModel->where('agente_placa', $agente->placa)->countAllResults();
        if ($boletas > 0) {
            return redirect()->to(site_url('admin/app-transito'))->with('errors', [
                "El agente {$agente->placa} tiene {$boletas} boleta(s) registradas y no puede eliminarse; desactívalo para impedir su acceso.",
            ]);
        }

        $this->agenteModel->delete($id);
        $registro = (array) $agente;
        unset($registro['password_hash']);
        $this->auditoriaModel->registrar('agentes_transito', $id, 'eliminar', session('user_id'), ['placa' => $agente->placa], $registro);

        return redirect()->to(site_url('admin/app-transito'))->with('message', "Agente {$agente->placa} eliminado.");
    }

    /**
     * Listado completo de Boletas de Infracción sincronizadas desde la app móvil.
     */
    public function boletas()
    {
        // Solo se aceptan textos: un parámetro repetido como arreglo (?estado[]=x) se ignora.
        $param  = fn (string $clave): string => is_string($valor = $this->request->getGet($clave)) ? trim($valor) : '';
        $placa  = $param('placa');
        $fecha  = $param('fecha');
        $q      = $param('q');
        // Estado validado contra el catálogo interno: lo que no existe se descarta, de modo que
        // la pantalla marque exactamente el filtro que se aplica a la consulta.
        $estado = $param('estado');
        $estado = array_key_exists($estado, self::ESTADOS_BOLETA) ? $estado : '';
        // Fundamento exacto: permite que el catálogo enlace las boletas de una falta.
        $falta  = $param('falta');

        $model = new BoletaInfraccionModel();

        if ($placa !== '') {
            $model->where('agente_placa', $placa);
        }
        if ($falta !== '') {
            $model->where('falta_fundamento_legal', $falta);
        }
        if ($fecha !== '') {
            $model->where('fecha_infraccion', $fecha);
        }
        if ($estado !== '') {
            $model->where('estado', $estado);
        }
        if ($q !== '') {
            $model->groupStart()
                ->like('folio', $q)
                ->orLike('infractor_nombre', $q)
                ->orLike('vehiculo_placas', $q)
                ->orLike('falta_fundamento_legal', $q)
                ->groupEnd();
        }

        $boletas = $model->orderBy('id', 'DESC')->findAll(100);
        $agentes = $this->agenteModel->findAll();
        $valorUma = (float)$this->paramModel->getValor('valor_uma_vigente', '117.31');

        return view('admin/app_transito/boletas', [
            'boletas'  => $boletas,
            'acciones' => (new BoletaAccionesService())->paraLista($boletas, (array) (session('roles') ?? [])),
            'agentes'  => $agentes,
            'filtros'  => ['placa' => $placa, 'fecha' => $fecha, 'q' => $q, 'estado' => $estado, 'falta' => $falta],
            'valorUma' => $valorUma,
            'estados'  => self::ESTADOS_BOLETA,
            'puedeConfig' => $this->puedeUsar('app_transito_config'),
        ]);
    }

    /** Administrador siempre; operador según su configuración efectiva; otros roles no pasan por /admin. */
    private function puedeUsar(string $permiso): bool
    {
        $roles = (array) (session('roles') ?? []);

        return in_array('administrador', $roles, true)
            || (in_array('operador_ventanilla', $roles, true) && \App\Libraries\PermisosOperador::activo($permiso));
    }

    /**
     * Formulario de corrección de una boleta sincronizada.
     */
    public function editarBoleta(int $id)
    {
        $boleta = $this->boletaModel->find($id);
        if (!$boleta) {
            return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', ['Boleta no encontrada.']);
        }

        $motivo = (new BoletaAccionesService())->motivoNoEditable($boleta);
        if ($motivo !== null) {
            return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', [$motivo]);
        }

        return view('admin/app_transito/boleta_form', [
            'boleta'      => $boleta,
            'estadoLabel' => self::ESTADOS_BOLETA[(string) $boleta->estado] ?? ucfirst((string) $boleta->estado),
            'tipos'       => self::TIPOS_VEHICULO,
        ]);
    }

    /**
     * Guardar correcciones administrativas de una boleta.
     */
    public function actualizarBoleta(int $id)
    {
        $boleta = $this->boletaModel->find($id);
        if (!$boleta) {
            return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', ['Boleta no encontrada.']);
        }

        // El estado solo cambia por los flujos de calificación (COB-10) y cobro.
        // Cualquier intento de enviarlo en este formulario se rechaza completo.
        foreach (array_keys((array) $this->request->getPost()) as $clave) {
            if (in_array(strtolower((string) $clave), ['estado', 'estatus', 'status', 'state', 'estado_boleta'], true)) {
                return redirect()->to(site_url('admin/app-transito/boletas/editar/' . $id))
                    ->with('errors', ['El estado de la boleta no se puede cambiar desde este formulario. No se guardó ningún cambio.']);
            }
        }

        $motivo = (new BoletaAccionesService())->motivoNoEditable($boleta);
        if ($motivo !== null) {
            return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', [$motivo]);
        }

        $rules = [
            'fecha_infraccion'    => ['label' => 'Fecha', 'rules' => 'required|valid_date[Y-m-d]'],
            'hora_infraccion'     => ['label' => 'Hora', 'rules' => 'required|regex_match[/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/]'],
            'lugar'               => ['label' => 'Lugar', 'rules' => 'required|max_length[255]'],
            'infractor_nombre'    => ['label' => 'Nombre del infractor', 'rules' => 'permit_empty|max_length[180]'],
            'infractor_domicilio' => ['label' => 'Domicilio del infractor', 'rules' => 'permit_empty|max_length[255]'],
            'infractor_licencia'  => ['label' => 'Licencia', 'rules' => 'permit_empty|max_length[50]'],
            'vehiculo_placas'     => ['label' => 'Placas', 'rules' => 'permit_empty|max_length[20]'],
            'vehiculo_marca'      => ['label' => 'Marca', 'rules' => 'permit_empty|max_length[60]'],
            'vehiculo_linea'      => ['label' => 'Línea / modelo', 'rules' => 'permit_empty|max_length[80]'],
            'vehiculo_color'      => ['label' => 'Color', 'rules' => 'permit_empty|max_length[40]'],
            'vehiculo_tipo'       => ['label' => 'Tipo de vehículo', 'rules' => 'required|in_list[' . implode(',', array_keys(self::TIPOS_VEHICULO)) . ']'],
            'hechos'              => ['label' => 'Hechos', 'rules' => 'permit_empty|max_length[2000]'],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $texto = fn (string $campo): ?string => ($v = trim((string) $this->request->getPost($campo))) === '' ? null : $v;
        $placas = $texto('vehiculo_placas');
        $data = [
            'fecha_infraccion'    => (string) $this->request->getPost('fecha_infraccion'),
            'hora_infraccion'     => (string) $this->request->getPost('hora_infraccion'),
            'lugar'               => (string) $texto('lugar'),
            'conductor_ausente'   => $this->request->getPost('conductor_ausente') === '1' ? 1 : 0,
            'infractor_nombre'    => $texto('infractor_nombre'),
            'infractor_domicilio' => $texto('infractor_domicilio'),
            'infractor_licencia'  => $texto('infractor_licencia'),
            'sin_placas'          => $this->request->getPost('sin_placas') === '1' ? 1 : 0,
            'vehiculo_placas'     => $placas !== null ? strtoupper($placas) : null,
            'vehiculo_marca'      => $texto('vehiculo_marca'),
            'vehiculo_linea'      => $texto('vehiculo_linea'),
            'vehiculo_color'      => $texto('vehiculo_color'),
            'vehiculo_tipo'       => (string) $this->request->getPost('vehiculo_tipo'),
            'hechos'              => $texto('hechos'),
        ];

        // La comprobación previa pudo quedar obsoleta (formulario abierto antes de
        // una calificación): se repite con la fila bloqueada dentro de la transacción.
        $servicio = new BoletaAccionesService();
        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            $actual = $servicio->bloquear($id, $db);
            if ($actual === null) {
                $db->transRollback();
                return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', ['Boleta no encontrada.']);
            }
            $motivo = $servicio->motivoNoEditable($actual);
            if ($motivo !== null) {
                $db->transRollback();
                return redirect()->to(site_url('admin/app-transito/boletas'))
                    ->with('errors', [$motivo . ' No se guardó ningún cambio.']);
            }

            $antes = array_intersect_key((array) $actual, $data);
            if (! $this->boletaModel->update($id, $data)) {
                throw new \RuntimeException('No se pudo actualizar la boleta.');
            }
            $this->auditoriaModel->registrar('boletas_infracciones', $id, 'editar', session('user_id'), [
                'folio'   => $actual->folio,
                'antes'   => $antes,
                'despues' => $data,
            ]);
            if (! $db->transStatus() || ! $db->transCommit()) {
                throw new \RuntimeException('No se pudo completar la actualización.');
            }
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error al actualizar boleta {id}: {mensaje}', ['id' => $id, 'mensaje' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('errors', ['No se pudo guardar la boleta. Intenta de nuevo.']);
        }

        return redirect()->to(site_url('admin/app-transito/boletas'))->with('message', "Boleta {$boleta->folio} actualizada.");
    }

    /**
     * Eliminar una boleta (queda copia íntegra en auditoría).
     */
    public function eliminarBoleta(int $id)
    {
        $boleta = $this->boletaModel->find($id);
        if (!$boleta) {
            return redirect()->to(site_url('admin/app-transito/boletas'))->with('errors', ['Boleta no encontrada.']);
        }

        $this->boletaModel->delete($id);
        $this->auditoriaModel->registrar('boletas_infracciones', $id, 'eliminar', session('user_id'), ['folio' => $boleta->folio], (array) $boleta);

        return redirect()->to(site_url('admin/app-transito/boletas'))->with('message', "Boleta {$boleta->folio} eliminada.");
    }

    /**
     * Catálogo de Infracciones del Reglamento administrable.
     */
    public function catalogo()
    {
        $q         = (string) ($this->request->getGet('q') ?? '');
        $categoria = (string) ($this->request->getGet('categoria') ?? '');
        $estado    = (string) ($this->request->getGet('estado') ?? '');

        $model = new CatalogoInfraccionAppModel();

        if ($q !== '') {
            $model->groupStart()
                ->like('fundamento_legal', $q)
                ->orLike('descripcion', $q)
                ->groupEnd();
        }
        if ($categoria !== '') {
            $model->where('categoria', $categoria);
        }
        if ($estado === 'activa') {
            $model->where('activo', 1);
        } elseif ($estado === 'inactiva') {
            $model->where('activo', 0);
        } else {
            $estado = '';
        }

        $items = $model->orderBy('orden', 'ASC')->findAll();
        $valorUma = (float)$this->paramModel->getValor('valor_uma_vigente', '117.31');
        $conteos  = $this->conteosCatalogo();

        // Categorías con su cantidad real, ordenadas para el filtro rápido.
        $categorias = $conteos['por_categoria'];
        ksort($categorias);

        return view('admin/app_transito/catalogo', [
            'items'           => $items,
            'valorUma'        => $valorUma,
            'filtros'         => ['q' => $q, 'categoria' => $categoria, 'estado' => $estado],
            'categorias'      => $categorias,
            'conteos'         => $conteos,
            'boletasPorFalta' => $this->boletasPorFalta(),
        ]);
    }

    /**
     * Totales del catálogo: sirven para que cada filtro rápido muestre su
     * cantidad real y para detectar faltas capturadas pero inactivas.
     *
     * @return array{total: int, activas: int, inactivas: int, por_categoria: array<string, int>}
     */
    private function conteosCatalogo(): array
    {
        $db = \Config\Database::connect();

        $porCategoria = [];
        $filas = $db->table('catalogo_infracciones_app')
            ->select('categoria, COUNT(*) AS total')
            ->groupBy('categoria')
            ->get()
            ->getResultArray();
        foreach ($filas as $fila) {
            $porCategoria[(string) $fila['categoria']] = (int) $fila['total'];
        }

        return [
            'total'         => $db->table('catalogo_infracciones_app')->countAllResults(),
            'activas'       => $db->table('catalogo_infracciones_app')->where('activo', 1)->countAllResults(),
            'inactivas'     => $db->table('catalogo_infracciones_app')->where('activo', 0)->countAllResults(),
            'por_categoria' => $porCategoria,
        ];
    }

    /**
     * Cuántas boletas se emitieron con cada fundamento legal. Así el catálogo
     * no es solo un formulario: revela qué faltas se están usando en el campo.
     *
     * @return array<string, int>
     */
    private function boletasPorFalta(): array
    {
        $conteo = [];
        $filas = \Config\Database::connect()->table('boletas_infracciones')
            ->select('falta_fundamento_legal, COUNT(*) AS total')
            ->where('falta_fundamento_legal IS NOT NULL', null, false)
            ->groupBy('falta_fundamento_legal')
            ->get()
            ->getResultArray();
        foreach ($filas as $fila) {
            $conteo[(string) $fila['falta_fundamento_legal']] = (int) $fila['total'];
        }

        return $conteo;
    }

    /**
     * Guardar o actualizar infracción en catálogo.
     */
    public function guardarCatalogo()
    {
        $id = $this->request->getPost('id');
        $esNuevo = empty($id);

        $rules = [
            'fundamento_legal' => [
                'rules' => $esNuevo
                    ? 'required|max_length[100]|is_unique[catalogo_infracciones_app.fundamento_legal]'
                    : "required|max_length[100]|is_unique[catalogo_infracciones_app.fundamento_legal,id,{$id}]",
                'label' => 'Fundamento Legal (Artículo)',
            ],
            'descripcion' => [
                'rules' => 'required|min_length[5]',
                'label' => 'Descripción de la falta',
            ],
            'categoria' => [
                'rules' => 'required|max_length[100]',
                'label' => 'Categoría',
            ],
            'monto_min_uma' => [
                'rules' => 'required|decimal|greater_than_equal_to[0]',
                'label' => 'Monto Mínimo UMA',
            ],
            'monto_max_uma' => [
                'rules' => 'required|decimal|greater_than_equal_to[0]',
                'label' => 'Monto Máximo UMA',
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'fundamento_legal' => trim((string)$this->request->getPost('fundamento_legal')),
            'descripcion'      => trim((string)$this->request->getPost('descripcion')),
            'categoria'        => strtoupper(trim((string)$this->request->getPost('categoria'))),
            'monto_min_uma'    => (float)$this->request->getPost('monto_min_uma'),
            'monto_max_uma'    => (float)$this->request->getPost('monto_max_uma'),
            // Checkbox: si no viene en el POST significa que se desmarcó.
            'activo'           => $this->request->getPost('activo') === '1' ? 1 : 0,
            'orden'            => (int)($this->request->getPost('orden') ?? 0),
        ];

        if ($esNuevo) {
            $this->catalogoModel->insert($data);
            $msg = 'Infracción agregada al catálogo de la app móvil.';
        } else {
            $this->catalogoModel->update($id, $data);
            $msg = 'Infracción actualizada correctamente.';
        }

        return redirect()->to(site_url('admin/app-transito/catalogo'))->with('message', $msg);
    }

    /**
     * Eliminar una falta del catálogo. Las boletas ya emitidas conservan su
     * copia de la falta en faltas_json, por lo que no se ven afectadas.
     */
    public function eliminarCatalogo(int $id)
    {
        $item = $this->catalogoModel->find($id);
        if (!$item) {
            return redirect()->to(site_url('admin/app-transito/catalogo'))->with('errors', ['Falta no encontrada.']);
        }

        $this->catalogoModel->delete($id);
        $this->auditoriaModel->registrar('catalogo_infracciones_app', $id, 'eliminar', session('user_id'), [
            'fundamento_legal' => $item->fundamento_legal,
        ], (array) $item);

        return redirect()->to(site_url('admin/app-transito/catalogo'))->with('message', "Falta {$item->fundamento_legal} eliminada del catálogo.");
    }
}
