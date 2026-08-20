<?php

namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Models\ProductoModel;
use App\Models\PromocionModel;
use App\Models\MeseroModel;
use App\Models\ClienteModel;
use App\Services\VentaService;

class Ventas extends BaseController
{
    protected CategoriaModel $categoriaModel;
    protected ProductoModel  $productoModel;
    protected PromocionModel $promocionModel;
    protected MeseroModel    $meseroModel;
    protected ClienteModel   $clienteModel;
    protected VentaService   $ventaService;

    public function __construct()
    {
        $this->categoriaModel = new CategoriaModel();
        $this->productoModel  = new ProductoModel();
        $this->promocionModel = new PromocionModel();
        $this->meseroModel    = new MeseroModel();
        $this->clienteModel   = new ClienteModel();
        $this->ventaService   = new VentaService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PANTALLA PRINCIPAL DE NUEVA VENTA (Terminal POS Táctil)
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $categorias  = $this->categoriaModel->obtenerActivas();
        $productos   = $this->productoModel->obtenerActivosConCategoria();
        $promociones = $this->promocionModel->obtenerActivasConDetalle();
        $meseros     = $this->meseroModel->obtenerActivos();

        return view('ventas/nueva', [
            'titulo'      => 'Nueva Venta',
            'categorias'  => $categorias,
            'productos'   => $productos,
            'promociones' => $promociones,
            'meseros'     => $meseros,
            'cajero'      => [
                'id'      => session()->get('usuario_id'),
                'nombre'  => session()->get('nombre'),
                'usuario' => session()->get('usuario'),
                'rol'     => session()->get('rol'),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PANTALLA DE HISTORIAL DE VENTAS (Etapa 5)
    // ─────────────────────────────────────────────────────────────────────────
    public function historial()
    {
        $filtros = [
            'fecha'     => $this->request->getGet('fecha') ?? date('Y-m-d'),
            'mesero_id' => $this->request->getGet('mesero_id') ?? '',
            'tipo_pago' => $this->request->getGet('tipo_pago') ?? '',
            'estado'    => $this->request->getGet('estado') ?? '',
        ];

        $ventas  = $this->ventaService->obtenerHistorial($filtros);
        $resumen = $this->ventaService->obtenerResumenFiltros($filtros);
        $meseros = $this->meseroModel->obtenerActivos();

        return view('ventas/historial', [
            'titulo'   => 'Historial de Ventas',
            'ventas'   => $ventas,
            'resumen'  => $resumen,
            'meseros'  => $meseros,
            'filtros'  => $filtros,
            'cajero'   => [
                'id'      => session()->get('usuario_id'),
                'nombre'  => session()->get('nombre'),
                'usuario' => session()->get('usuario'),
                'rol'     => session()->get('rol'),
            ],
        ]);
    }

    public function apertura()
    {
        $productos = $this->productoModel->obtenerActivosConCategoria();
        
        $db = \Config\Database::connect();
        $aperturas = $db->table('movimientos_stock')
            ->select('movimientos_stock.*, productos.nombre as producto_nombre, productos.maneja_unidades')
            ->join('productos', 'productos.id = movimientos_stock.producto_id')
            ->where('tipo_movimiento', 'APERTURA')
            ->where("NOT EXISTS (
                SELECT 1 FROM movimientos_stock as ms2 
                WHERE ms2.tipo_movimiento = 'AJUSTE' 
                AND ms2.referencia_id = movimientos_stock.id
                AND ms2.observacion LIKE 'Reversión de apertura%'
            )")
            ->groupStart()
                ->where('observacion NOT LIKE', '%unidades sueltas%')
                ->orWhere('productos.maneja_unidades', 0)
            ->groupEnd()
            ->orderBy('fecha', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return view('ventas/apertura', [
            'titulo'    => 'Apertura de Productos',
            'productos' => $productos,
            'aperturas' => $aperturas,
        ]);
    }

    public function procesarApertura()
    {
        $productoId = $this->request->getPost('producto_id');
        $cantidad = (int)$this->request->getPost('cantidad');

        if ($cantidad <= 0) {
            return redirect()->back()->with('error', 'La cantidad debe ser mayor a 0.');
        }

        try {
            $resultado = $this->ventaService->realizarApertura($productoId, $cantidad, session()->get('usuario_id'));
            
            if ($resultado['success']) {
                return redirect()->to(site_url('ventas/apertura'))->with('success', $resultado['message']);
            } else {
                return redirect()->back()->with('error', $resultado['message']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function revertirApertura($id)
    {
        try {
            $resultado = $this->ventaService->revertirApertura((int)$id, session()->get('usuario_id'));
            
            if ($resultado['success']) {
                return redirect()->to(site_url('ventas/apertura'))->with('success', $resultado['message']);
            } else {
                return redirect()->back()->with('error', $resultado['message']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: OBTENER DETALLE DE VENTA (GET JSON)
    // Ruta: GET /ventas/detalle/(:num)
    // ─────────────────────────────────────────────────────────────────────────
    public function detalle($id = null)
    {
        $ventaId = (int) $id;
        if ($ventaId <= 0) {
            return $this->jsonError('ID de venta inválido.');
        }

        $detalle = $this->ventaService->obtenerDetalle($ventaId);

        if (!$detalle) {
            return $this->jsonError('La venta solicitada no existe.', 404);
        }

        return $this->response
                    ->setContentType('application/json')
                    ->setJSON(['success' => true, 'venta' => $detalle]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: ANULAR VENTA (POST JSON)
    // Ruta: POST /ventas/anular/(:num)
    // Permisos: auth + VENTAS_ANULAR (o de lo contrario verificar en controlador)
    // ─────────────────────────────────────────────────────────────────────────
    public function anular($id = null)
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->jsonError('Sesión expirada.', 401);
        }

        $ventaId = (int) $id;
        if ($ventaId <= 0) {
            return $this->jsonError('ID de venta inválido.');
        }

        $resultado = $this->ventaService->anular($ventaId, $usuarioId);

        if ($resultado['success']) {
            return $this->response
                        ->setStatusCode(200)
                        ->setContentType('application/json')
                        ->setJSON($resultado);
        }

        return $this->response
                    ->setStatusCode(422)
                    ->setContentType('application/json')
                    ->setJSON($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: PROCESAR VENTA (POST JSON)
    // Ruta: POST /ventas/procesar
    // Permisos: auth + VENTAS_CREAR
    // ─────────────────────────────────────────────────────────────────────────
    public function procesar()
    {
        if (!$this->request->is('post')) {
            return $this->jsonError('Método no permitido.', 405);
        }

        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->jsonError('Sesión expirada. Inicia sesión nuevamente.', 401);
        }

        $payload = $this->request->getJSON(true);

        if (empty($payload)) {
            return $this->jsonError('No se recibieron datos.');
        }

        $resultado = $this->ventaService->procesar($payload, $usuarioId);

        if ($resultado['success']) {
            return $this->response
                        ->setStatusCode(200)
                        ->setContentType('application/json')
                        ->setJSON($resultado);
        }

        return $this->response
                    ->setStatusCode(422)
                    ->setContentType('application/json')
                    ->setJSON($resultado);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: BUSCAR CLIENTES PARA VENTA (GET JSON)
    // Ruta: GET /ventas/clientes?q=termino
    // ─────────────────────────────────────────────────────────────────────────
    public function clientes()
    {
        $q = trim($this->request->getGet('q') ?? '');

        if (strlen($q) < 2) {
            return $this->response
                        ->setContentType('application/json')
                        ->setJSON(['clientes' => []]);
        }

        $clientes = $this->clienteModel->buscar($q);

        return $this->response
                    ->setContentType('application/json')
                    ->setJSON(['clientes' => $clientes]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: CREAR CLIENTE RÁPIDO DESDE EL POS (POST JSON)
    // Ruta: POST /ventas/crear-cliente
    // ─────────────────────────────────────────────────────────────────────────
    public function crearCliente()
    {
        $usuarioId = (int) session()->get('usuario_id');
        if (!$usuarioId) {
            return $this->jsonError('Sesión expirada.', 401);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $nombre   = trim($payload['nombre'] ?? '');
        $telefono = trim($payload['telefono'] ?? '');
        $limite   = (float)($payload['limite_credito'] ?? 200.00);

        if (empty($nombre)) {
            return $this->jsonError('El nombre del cliente es obligatorio.');
        }

        $existente = $this->clienteModel->where('nombre', $nombre)->first();
        if ($existente) {
            return $this->response
                        ->setContentType('application/json')
                        ->setJSON([
                            'success' => true,
                            'mensaje' => 'Cliente seleccionado.',
                            'cliente' => $existente
                        ]);
        }

        $clienteId = $this->clienteModel->insert([
            'nombre'         => $nombre,
            'telefono'       => $telefono,
            'limite_credito' => $limite > 0 ? $limite : 200.00,
            'estado'         => 'ACTIVO',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ]);

        if (!$clienteId) {
            return $this->jsonError('Error al registrar el cliente.');
        }

        $nuevoCliente = $this->clienteModel->find($clienteId);

        return $this->response
                    ->setStatusCode(200)
                    ->setContentType('application/json')
                    ->setJSON([
                        'success' => true,
                        'mensaje' => 'Cliente registrado correctamente.',
                        'cliente' => $nuevoCliente
                    ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER: respuesta JSON de error
    // ─────────────────────────────────────────────────────────────────────────
    private function jsonError(string $mensaje, int $status = 400)
    {
        return $this->response
                    ->setStatusCode($status)
                    ->setContentType('application/json')
                    ->setJSON(['success' => false, 'mensaje' => $mensaje]);
    }
}
