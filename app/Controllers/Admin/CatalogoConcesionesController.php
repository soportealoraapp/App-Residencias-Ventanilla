<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\ConcesionModel;
use App\Models\AuditoriaModel;
use Config\Services;

class CatalogoConcesionesController extends Controller
{
    public function index()
    {
        $concesionModel = new ConcesionModel();

        $q = $this->request->getGet('q') ?? '';
        $estatus = $this->request->getGet('estatus') ?? '';

        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $concesionModel
                ->groupStart()
                    ->like('numero_titulo', $q)
                    ->orLike('titular_actual', $q)
                    ->orLike('vehiculo_placas', $q)
                ->groupEnd();
        }

        if ($estatus !== '') {
            $concesionModel->where('estatus', $estatus);
        }

        $concesionModel->orderBy('id', 'DESC');

        $concesiones = $concesionModel->paginate(20);
        $pager = $concesionModel->pager;

        $filtros = [
            'q'       => $q,
            'estatus' => $estatus,
        ];

        $estatusOpciones = [
            ''               => 'Todos',
            'vigente'        => 'Vigente',
            'vencida'        => 'Vencida',
            'en_transmision' => 'En trámite de transmisión',
        ];

        return view('admin/catalogos/concesiones_index', [
            'concesiones'    => $concesiones,
            'pager'          => $pager,
            'filtros'        => $filtros,
            'estatusOpciones' => $estatusOpciones,
        ]);
    }

    public function formNuevo()
    {
        $concesion = (object)[
            'id'                => null,
            'numero_titulo'     => '',
            'titular_actual'    => '',
            'vehiculo_placas'   => '',
            'vehiculo_num_serie' => '',
            'tipo_persona'      => 'fisica',
            'vigencia_inicio'   => '',
            'vigencia_fin'      => '',
            'estatus'           => 'vigente',
        ];

        $estatusOpciones = [
            'vigente'        => 'Vigente',
            'vencida'        => 'Vencida',
            'en_transmision' => 'En trámite de transmisión',
        ];

        return view('admin/catalogos/concesiones_form', [
            'concesion'       => $concesion,
            'estatusOpciones' => $estatusOpciones,
            'modo'            => 'nuevo',
        ]);
    }

    public function guardar()
    {
        $concesionModel = new ConcesionModel();
        $auditoriaModel = new AuditoriaModel();

        $reglas = [
            'numero_titulo'      => 'required|is_unique[concesiones.numero_titulo]|max_length[50]',
            'titular_actual'     => 'required|max_length[180]',
            'vehiculo_placas'    => 'permit_empty|max_length[10]',
            'vehiculo_num_serie'  => 'permit_empty|max_length[20]',
            'tipo_persona'       => 'permit_empty|in_list[fisica,moral]',
            'vigencia_inicio'    => 'required|valid_date',
            'vigencia_fin'       => 'required|valid_date',
            'estatus'            => 'required|in_list[vigente,vencida,en_transmision]',
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'numero_titulo'      => $this->request->getPost('numero_titulo'),
            'titular_actual'     => $this->request->getPost('titular_actual'),
            'vehiculo_placas'    => $this->request->getPost('vehiculo_placas') ?: null,
            'vehiculo_num_serie'  => $this->request->getPost('vehiculo_num_serie') ?: null,
            'tipo_persona'       => $this->request->getPost('tipo_persona') ?: null,
            'vigencia_inicio'    => $this->request->getPost('vigencia_inicio'),
            'vigencia_fin'       => $this->request->getPost('vigencia_fin'),
            'estatus'            => $this->request->getPost('estatus'),
        ];

        $concesionModel->insert($data);
        $nuevoId = $concesionModel->getInsertID();

        $userId = session('user_id');
        $auditoriaModel->registrar('concesiones', $nuevoId, 'crear', $userId, $data);

        return redirect()->to(site_url('admin/concesiones'))->with('message', 'Concesión creada en catálogo provisional. El padrón real se sincronizará con el módulo de Concesiones.');
    }

    public function formEditar(int $id)
    {
        $concesionModel = new ConcesionModel();

        $concesion = $concesionModel->find($id);
        if (!$concesion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $estatusOpciones = [
            'vigente'        => 'Vigente',
            'vencida'        => 'Vencida',
            'en_transmision' => 'En trámite de transmisión',
        ];

        return view('admin/catalogos/concesiones_form', [
            'concesion'       => $concesion,
            'estatusOpciones' => $estatusOpciones,
            'modo'            => 'editar',
        ]);
    }

    public function actualizar(int $id)
    {
        $concesionModel = new ConcesionModel();
        $auditoriaModel = new AuditoriaModel();

        $concesion = $concesionModel->find($id);
        if (!$concesion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $reglas = [
            'numero_titulo'      => "required|is_unique[concesiones.numero_titulo,id,{$id}]|max_length[50]",
            'titular_actual'     => 'required|max_length[180]',
            'vehiculo_placas'    => 'permit_empty|max_length[10]',
            'vehiculo_num_serie'  => 'permit_empty|max_length[20]',
            'tipo_persona'       => 'permit_empty|in_list[fisica,moral]',
            'vigencia_inicio'    => 'required|valid_date',
            'vigencia_fin'       => 'required|valid_date',
            'estatus'            => 'required|in_list[vigente,vencida,en_transmision]',
        ];

        if (!$this->validate($reglas)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'numero_titulo'      => $this->request->getPost('numero_titulo'),
            'titular_actual'     => $this->request->getPost('titular_actual'),
            'vehiculo_placas'    => $this->request->getPost('vehiculo_placas') ?: null,
            'vehiculo_num_serie'  => $this->request->getPost('vehiculo_num_serie') ?: null,
            'tipo_persona'       => $this->request->getPost('tipo_persona') ?: null,
            'vigencia_inicio'    => $this->request->getPost('vigencia_inicio'),
            'vigencia_fin'       => $this->request->getPost('vigencia_fin'),
            'estatus'            => $this->request->getPost('estatus'),
        ];

        $concesionModel->update($id, $data);

        $userId = session('user_id');
        $auditoriaModel->registrar('concesiones', $id, 'editar', $userId, $data);

        return redirect()->to(site_url('admin/concesiones'))->with('message', 'Concesión actualizada en catálogo provisional.');
    }

    public function eliminar(int $id)
    {
        $concesionModel = new ConcesionModel();
        $auditoriaModel = new AuditoriaModel();

        $concesion = $concesionModel->find($id);
        if (!$concesion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $concesionModel->delete($id);

        $userId = session('user_id');
        $auditoriaModel->registrar('concesiones', $id, 'eliminar', $userId, (array)$concesion);

        return redirect()->to(site_url('admin/concesiones'))->with('message', 'Registro eliminado del catálogo stub. No afecta solicitudes ya creadas.');
    }
}
