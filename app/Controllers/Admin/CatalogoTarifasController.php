<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\TarifaModel;
use App\Models\AuditoriaModel;
use App\Libraries\FeatureFlags;
use Config\Services;

class CatalogoTarifasController extends Controller
{
    public function index()
    {
        $tarifaModel = new TarifaModel();

        $tramite    = (string) ($this->request->getGet('tramite') ?? '');
        // 'no_oficial' aísla los montos que aún son referencia y no tarifario oficial.
        $referencia = (string) ($this->request->getGet('referencia') ?? '');

        $tramites = $this->tramitesHabilitados();

        if ($tramite !== '') {
            $tarifaModel->where('tramite', $tramite);
        }

        if ($referencia === 'no_oficial') {
            $tarifaModel->where('placeholder_oficial', 1);
        } elseif ($referencia === 'oficial') {
            $tarifaModel->where('placeholder_oficial', 0);
        }

        $tarifaModel->orderBy('tramite', 'ASC')
            ->orderBy('criterio', 'ASC')
            ->orderBy('vigente_desde', 'DESC');

        $tarifas = $tarifaModel->findAll();

        $filtros = [
            'tramite'    => $tramite,
            'referencia' => $referencia,
        ];

        return view('admin/catalogos/tarifas_index', [
            'tarifas'  => $tarifas,
            'filtros'  => $filtros,
            'tramites' => $tramites,
            'conteos'  => $this->conteosTarifas(),
        ]);
    }

    /**
     * Códigos de trámite habilitados según los feature flags.
     *
     * @return list<string>
     */
    private function tramitesHabilitados(): array
    {
        $tramites = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05'];
        if (FeatureFlags::habilitarUrTtT06()) {
            $tramites[] = 'UR-TT-T-06';
        }
        $tramites[] = 'UR-TT-T-07';

        return $tramites;
    }

    /**
     * Cuántas tarifas hay por trámite, para que cada filtro rápido muestre su
     * cantidad real en lugar de un número genérico.
     *
     * @return array{total: int, por_tramite: array<string, int>, no_oficiales: int}
     */
    private function conteosTarifas(): array
    {
        $db = \Config\Database::connect();

        $porTramite = [];
        $filas = $db->table('tarifas')
            ->select('tramite, COUNT(*) AS total')
            ->groupBy('tramite')
            ->get()
            ->getResultArray();
        foreach ($filas as $fila) {
            $porTramite[(string) $fila['tramite']] = (int) $fila['total'];
        }

        return [
            'total'        => $db->table('tarifas')->countAllResults(),
            'por_tramite'  => $porTramite,
            'no_oficiales' => $db->table('tarifas')->where('placeholder_oficial', 1)->countAllResults(),
        ];
    }

    public function formNuevo()
    {
        $tramites = $this->tramitesHabilitados();

        $hoy = date('Y-m-d');

        $tarifa = (object)[
            'id'                 => null,
            'tramite'            => '',
            'criterio'           => '',
            'monto'              => '',
            'vigente_desde'      => $hoy,
            'vigente_hasta'      => '',
            'descripcion'        => '',
            'placeholder_oficial' => 1,
        ];

        return view('admin/catalogos/tarifas_form', [
            'tarifa'   => $tarifa,
            'tramites' => $tramites,
            'modo'     => 'nuevo',
        ]);
    }

    public function guardar()
    {
        $tarifaModel = new TarifaModel();
        $auditoriaModel = new AuditoriaModel();

        $reglas = [
            'tramite' => [
                'rules' => 'required|max_length[20]',
                'label' => 'Trámite',
            ],
            'criterio' => [
                'rules' => 'required|max_length[50]',
                'label' => 'Criterio',
            ],
            'monto' => [
                'rules' => 'required|decimal|greater_than_equal_to[0]',
                'label' => 'Monto',
            ],
            'vigente_desde' => [
                'rules' => 'required|valid_date',
                'label' => 'Vigente desde',
            ],
            'vigente_hasta' => [
                'rules' => 'permit_empty|valid_date',
                'label' => 'Vigente hasta',
            ],
            'descripcion' => [
                'rules' => 'permit_empty|max_length[250]',
                'label' => 'Descripción',
            ],
            'placeholder_oficial' => [
                'rules' => 'permit_empty|in_list[0,1]',
                'label' => 'Tarifa de referencia',
            ],
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'tramite'            => $this->request->getPost('tramite'),
            'criterio'           => $this->request->getPost('criterio'),
            'monto'              => (float)$this->request->getPost('monto'),
            'vigente_desde'      => $this->request->getPost('vigente_desde'),
            'vigente_hasta'      => $this->request->getPost('vigente_hasta') ?: null,
            'descripcion'        => $this->request->getPost('descripcion') ?: null,
            'placeholder_oficial' => (int)($this->request->getPost('placeholder_oficial') ?? 0),
        ];

        $tarifaModel->insert($data);
        $nuevoId = $tarifaModel->getInsertID();

        $userId = session('user_id');
        $auditoriaModel->registrar('tarifas', $nuevoId, 'crear', $userId, $data);

        return redirect()->to(site_url('admin/tarifas'))->with('message', 'Tarifa creada correctamente.');
    }

    public function formEditar(int $id)
    {
        $tarifaModel = new TarifaModel();

        $tarifa = $tarifaModel->find($id);
        if (!$tarifa) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tramites = $this->tramitesHabilitados();

        return view('admin/catalogos/tarifas_form', [
            'tarifa'   => $tarifa,
            'tramites' => $tramites,
            'modo'     => 'editar',
        ]);
    }

    public function actualizar(int $id)
    {
        $tarifaModel = new TarifaModel();
        $auditoriaModel = new AuditoriaModel();

        $tarifa = $tarifaModel->find($id);
        if (!$tarifa) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $reglas = [
            'tramite' => [
                'rules' => 'required|max_length[20]',
                'label' => 'Trámite',
            ],
            'criterio' => [
                'rules' => 'required|max_length[50]',
                'label' => 'Criterio',
            ],
            'monto' => [
                'rules' => 'required|decimal|greater_than_equal_to[0]',
                'label' => 'Monto',
            ],
            'vigente_desde' => [
                'rules' => 'required|valid_date',
                'label' => 'Vigente desde',
            ],
            'vigente_hasta' => [
                'rules' => 'permit_empty|valid_date',
                'label' => 'Vigente hasta',
            ],
            'descripcion' => [
                'rules' => 'permit_empty|max_length[250]',
                'label' => 'Descripción',
            ],
            'placeholder_oficial' => [
                'rules' => 'permit_empty|in_list[0,1]',
                'label' => 'Tarifa de referencia',
            ],
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'tramite'            => $this->request->getPost('tramite'),
            'criterio'           => $this->request->getPost('criterio'),
            'monto'              => (float)$this->request->getPost('monto'),
            'vigente_desde'      => $this->request->getPost('vigente_desde'),
            'vigente_hasta'      => $this->request->getPost('vigente_hasta') ?: null,
            'descripcion'        => $this->request->getPost('descripcion') ?: null,
            'placeholder_oficial' => (int)($this->request->getPost('placeholder_oficial') ?? 0),
        ];

        $tarifaModel->update($id, $data);

        $userId = session('user_id');
        $auditoriaModel->registrar('tarifas', $id, 'editar', $userId, $data);

        return redirect()->to(site_url('admin/tarifas'))->with('message', 'Tarifa actualizada');
    }

    public function eliminar(int $id)
    {
        $tarifaModel = new TarifaModel();
        $auditoriaModel = new AuditoriaModel();

        $tarifa = $tarifaModel->find($id);
        if (!$tarifa) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tarifaModel->delete($id);

        $userId = session('user_id');
        $auditoriaModel->registrar('tarifas', $id, 'eliminar', $userId, (array)$tarifa);

        return redirect()->to(site_url('admin/tarifas'))->with('message', 'Tarifa eliminada (no afecta solicitudes ya procesadas)');
    }
}
