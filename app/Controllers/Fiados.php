<?php

namespace App\Controllers;

use App\Services\FiadoService;
use App\Models\FiadoModel;

class Fiados extends BaseController
{
    protected FiadoService $fiadoService;
    protected FiadoModel   $fiadoModel;

    public function __construct()
    {
        $this->fiadoService = new FiadoService();
        $this->fiadoModel   = new FiadoModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Listado de clientes con deuda
    // Ruta: GET /fiados
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $search   = trim($this->request->getGet('search') ?? '');
        $filter   = trim($this->request->getGet('filter') ?? '');
        $clientes = $this->fiadoService->obtenerListaClientes($search ?: null, $filter ?: null);

        // Para las tarjetas KPI necesitamos el conteo total sin filtros
        $todosLosClientes = $this->fiadoService->obtenerListaClientes(null, null);

        $totalDeuda    = 0.00;
        $totalConDeuda = 0;
        $totalSinDeuda = 0;
        $totalAlta     = 0;

        foreach ($todosLosClientes as $c) {
            $totalDeuda += $c['saldo_pendiente'];
            if ($c['saldo_pendiente'] > 0) {
                $totalConDeuda++;
            } else {
                $totalSinDeuda++;
            }
            if ($c['estado_deuda'] === 'ALTA') {
                $totalAlta++;
            }
        }

        return view('fiados/index', [
            'titulo'         => 'Fiados',
            'clientes'       => $clientes,
            'search'         => $search,
            'filter'         => $filter,
            'total_deuda'    => round($totalDeuda, 2),
            'total_clientes' => count($clientes),
            'con_deuda'      => $totalConDeuda,
            'sin_deuda'      => $totalSinDeuda,
            'alta_deuda'     => $totalAlta,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Detalle de un cliente: historial + pagar
    // Ruta: GET /fiados/cliente/(:num)
    // ─────────────────────────────────────────────────────────────────────────
    public function cliente($id = null)
    {
        $clienteId = (int) $id;
        if ($clienteId <= 0) {
            return redirect()->to(site_url('fiados'))->with('error', 'Cliente no válido.');
        }

        $datos = $this->fiadoService->obtenerHistorialCliente($clienteId);

        if (!$datos) {
            return redirect()->to(site_url('fiados'))->with('error', 'El cliente no existe.');
        }

        return view('fiados/cliente', [
            'titulo' => '💳 ' . $datos['nombre'],
            'datos'  => $datos,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: Guardar Garantía / Observación
    // Ruta: POST /fiados/guardar-garantia
    // ─────────────────────────────────────────────────────────────────────────
    public function guardarGarantia()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $garantia  = trim($data['garantia'] ?? '');

        if ($clienteId <= 0) {
            return $this->jsonError('Cliente no válido.');
        }

        // Buscamos el fiado más reciente de este cliente para asociar la garantía
        // Si no hay fiados, podríamos crear una entrada ficticia o simplemente guardarlo en el último.
        // Las reglas dicen: "debe guardarse directamente en fiados.observacion"
        $ultimoFiado = $this->fiadoModel->where('cliente_id', $clienteId)
                                        ->orderBy('id', 'DESC')
                                        ->first();

        if ($ultimoFiado) {
            $this->fiadoModel->update($ultimoFiado['id'], ['observacion' => $garantia]);
        } else {
            // Si el cliente no tiene fiados aún, no podemos guardar la garantía en fiados.observacion 
            // a menos que creemos un registro. Pero el usuario dice "No crear ninguna tabla nueva".
            // Podríamos crear un registro de fiado con monto 0 para guardar la observación si es necesario,
            // pero normalmente se guarda cuando se hace un fiado.
            return $this->jsonError('El cliente no tiene fiados registrados para asignar una garantía.');
        }

        return $this->response->setJSON(['success' => true, 'mensaje' => 'Garantía guardada correctamente.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: Registrar pago (POST JSON)
    // Ruta: POST /fiados/pagar
    // ─────────────────────────────────────────────────────────────────────────
    public function pagar()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->jsonError('Sesión expirada.', 401);
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $resultado = $this->fiadoService->registrarPago($data, $usuarioId);

        $status = $resultado['success'] ? 200 : 422;

        return $this->response
                    ->setStatusCode($status)
                    ->setContentType('application/json')
                    ->setJSON($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: Resumen de deuda de un cliente (para modal de pago)
    // Ruta: GET /fiados/resumen/(:num)
    // ─────────────────────────────────────────────────────────────────────────
    public function resumen($id = null)
    {
        $clienteId = (int) $id;
        if ($clienteId <= 0) {
            return $this->jsonError('ID de cliente inválido.');
        }

        $resumen = $this->fiadoModel->obtenerResumenCliente($clienteId);
        if (!$resumen) {
            return $this->jsonError('Cliente no encontrado.', 404);
        }

        return $this->response
                    ->setContentType('application/json')
                    ->setJSON(['success' => true, 'resumen' => $resumen]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Respuesta JSON de error
    // ─────────────────────────────────────────────────────────────────────────
    private function jsonError(string $mensaje, int $statusCode = 422): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
                    ->setStatusCode($statusCode)
                    ->setContentType('application/json')
                    ->setJSON(['success' => false, 'mensaje' => $mensaje]);
    }
}
