<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\ParametroAppModel;
use App\Models\AgenteTransitoModel;
use App\Models\CatalogoInfraccionAppModel;
use App\Models\BoletaInfraccionModel;
use App\Models\AuditoriaModel;

class AppTransitoController extends Controller
{
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
                'rules' => 'required|min_length[4]|max_length[50]',
                'label' => 'Contraseña de Acceso Móvil',
            ];
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'placa'           => strtoupper(trim((string)$this->request->getPost('placa'))),
            'nombre_completo' => trim((string)$this->request->getPost('nombre_completo')),
            'rol'             => $this->request->getPost('rol'),
            'sector'          => trim((string)$this->request->getPost('sector')) ?: 'Sector Centro',
            'activo'          => (int)($this->request->getPost('activo') ?? 1),
        ];

        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($esNuevo) {
            $this->agenteModel->insert($data);
            $nuevoId = $this->agenteModel->getInsertID();
            $this->auditoriaModel->registrar('agentes_transito', $nuevoId, 'crear', session('user_id'), $data);
            $msg = "Agente {$data['placa']} registrado exitosamente.";
        } else {
            $this->agenteModel->update($id, $data);
            $this->auditoriaModel->registrar('agentes_transito', (int)$id, 'editar', session('user_id'), $data);
            $msg = "Datos del Agente {$data['placa']} actualizados.";
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
     * Listado completo de Boletas de Infracción sincronizadas desde la app móvil.
     */
    public function boletas()
    {
        $placa = $this->request->getGet('placa') ?? '';
        $fecha = $this->request->getGet('fecha') ?? '';
        $q     = $this->request->getGet('q') ?? '';

        $model = new BoletaInfraccionModel();

        if ($placa !== '') {
            $model->where('agente_placa', $placa);
        }
        if ($fecha !== '') {
            $model->where('fecha_infraccion', $fecha);
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
            'agentes'  => $agentes,
            'filtros'  => ['placa' => $placa, 'fecha' => $fecha, 'q' => $q],
            'valorUma' => $valorUma,
        ]);
    }

    /**
     * Catálogo de Infracciones del Reglamento administrable.
     */
    public function catalogo()
    {
        $items = $this->catalogoModel->orderBy('orden', 'ASC')->findAll();
        $valorUma = (float)$this->paramModel->getValor('valor_uma_vigente', '117.31');

        return view('admin/app_transito/catalogo', [
            'items'    => $items,
            'valorUma' => $valorUma,
        ]);
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
            'activo'           => (int)($this->request->getPost('activo') ?? 1),
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
}
