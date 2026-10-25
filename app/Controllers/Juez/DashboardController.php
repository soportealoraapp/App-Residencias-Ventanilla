<?php declare(strict_types=1);

namespace App\Controllers\Juez;

use CodeIgniter\Controller;
use App\Libraries\CalificacionBoletaException;
use App\Libraries\CalificarBoletaService;
use App\Models\BoletaInfraccionModel;
use App\Models\CobroModel;
use CodeIgniter\HTTP\RedirectResponse;

class DashboardController extends Controller
{
    public function index()
    {
        // COB-05: cobros de multa pendientes de pago, con el folio de su boleta,
        // para imprimir la hoja de ayuda sin salir del panel del juez.
        $cobrosPendientes = (new CobroModel())
            ->select('cobros.id, cobros.folio, cobros.boleta_id, cobros.nombre_pagador, cobros.total, cobros.created_at, boletas_infracciones.folio AS boleta_folio', false)
            ->join('boletas_infracciones', 'boletas_infracciones.id = cobros.boleta_id', 'left')
            ->where('cobros.origen', 'boleta')
            ->where('cobros.estatus', 'pendiente')
            ->orderBy('cobros.id', 'DESC')
            ->limit(10)
            ->findAll();

        return view('juez/dashboard', [
            'pendientes'       => (new BoletaInfraccionModel())->where('estado', 'recibida')->countAllResults(),
            'cobrosPendientes' => $cobrosPendientes,
        ]);
    }

    public function boletas()
    {
        $servicio = new CalificarBoletaService();
        $boletas = (new BoletaInfraccionModel())
            ->where('estado', 'recibida')
            ->orderBy('fecha_infraccion', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($boletas as $boleta) {
            try {
                $boleta->numero_faltas = count($servicio->faltasDeBoleta($boleta));
                $boleta->faltas_validas = true;
            } catch (CalificacionBoletaException) {
                $boleta->numero_faltas = 0;
                $boleta->faltas_validas = false;
            }
        }
        return view('juez/boletas', ['boletas' => $boletas]);
    }

    public function detalle(int $id)
    {
        return $this->mostrarDetalle($id);
    }

    public function vistaPrevia(int $id)
    {
        $nombre = trim((string) $this->request->getPost('nombre_infractor'));
        $selecciones = $this->seleccionesPost();
        try {
            $boleta = $this->boletaRecibida($id);
            $previa = (new CalificarBoletaService())->vistaPrevia($boleta, $selecciones);
            return $this->mostrarDetalle($id, $nombre, $selecciones, $previa);
        } catch (CalificacionBoletaException $e) {
            return $this->mostrarDetalle($id, $nombre, $selecciones, null, $e->getMessage());
        }
    }

    public function calificar(int $id): RedirectResponse
    {
        try {
            $resultado = (new CalificarBoletaService())->calificar(
                $id,
                (string) $this->request->getPost('nombre_infractor'),
                $this->seleccionesPost(),
                (int) session('user_id')
            );
            return redirect()->to('/juez/boletas')->with(
                'message',
                'Boleta calificada. Se generó el cobro pendiente #' . $resultado['cobro_id'] . '.'
            );
        } catch (CalificacionBoletaException $e) {
            return redirect()->to('/juez/boletas/' . $id)
                ->withInput()
                ->with('errors', [$e->getMessage()]);
        }
    }

    private function mostrarDetalle(
        int $id,
        ?string $nombre = null,
        ?array $selecciones = null,
        ?array $previa = null,
        ?string $error = null
    ) {
        try {
            $boleta = $this->boletaRecibida($id);
            $faltas = (new CalificarBoletaService())->faltasDeBoleta($boleta);
        } catch (CalificacionBoletaException $e) {
            return redirect()->to('/juez/boletas')->with('errors', [$e->getMessage()]);
        }

        return view('juez/boleta_detalle', [
            'boleta' => $boleta,
            'faltas' => $faltas,
            'nombre' => $nombre ?? (string) old('nombre_infractor', $boleta->infractor_nombre ?? ''),
            'selecciones' => $selecciones ?? (array) old('uma', []),
            'previa' => $previa,
            'errors' => $error !== null ? [$error] : (array) session('errors'),
        ]);
    }

    private function boletaRecibida(int $id): object
    {
        $boleta = (new BoletaInfraccionModel())->find($id);
        if ($boleta === null) {
            throw new CalificacionBoletaException('La boleta no existe.');
        }
        if ((string) $boleta->estado !== 'recibida') {
            throw new CalificacionBoletaException('La boleta ya no está pendiente de calificación.');
        }
        return $boleta;
    }

    private function seleccionesPost(): array
    {
        $selecciones = $this->request->getPost('uma');
        return is_array($selecciones) ? $selecciones : [];
    }
}
