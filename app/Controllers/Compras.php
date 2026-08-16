<?php

namespace App\Controllers;

use App\Models\CompraModel;
use App\Models\CompraDetalleModel;
use App\Models\ProveedorModel;
use App\Models\ProductoModel;
use App\Models\UsuarioModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Compras extends BaseController
{
    protected $compraModel;
    protected $compraDetalleModel;
    protected $proveedorModel;
    protected $productoModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->compraModel = new CompraModel();
        $this->compraDetalleModel = new CompraDetalleModel();
        $this->proveedorModel = new ProveedorModel();
        $this->productoModel = new ProductoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'COMPRAS_VER')) {
            return redirect()->to('dashboard')->with('error', 'No tienes permisos para acceder a este módulo.');
        }

        $data = [
            'titulo' => 'Compras',
            'compras' => $this->compraModel->obtenerTodasConRelaciones()
        ];

        return view('compras/index', $data);
    }

    public function nuevo()
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'COMPRAS_CREAR')) {
            return redirect()->to('compras')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $data = [
            'titulo' => 'Nueva Compra',
            'proveedores' => $this->proveedorModel->where('estado', 'ACTIVO')->findAll(),
            'productos' => $this->productoModel->where('estado', 'ACTIVO')->findAll()
        ];

        return view('compras/nuevo', $data);
    }

    public function guardar()
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'COMPRAS_CREAR')) {
            return $this->response->setJSON(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
        }

        $usuarioId = session()->get('usuario_id');
        $proveedorId = $this->request->getPost('proveedor_id');
        $observacion = $this->request->getPost('observacion');
        $items = $this->request->getPost('items');

        if (empty($items)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Debe agregar al menos un producto.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['cantidad'] * $item['costo_unitario'];
            }
            $total = $subtotal;

            $compraId = $this->compraModel->insert([
                'proveedor_id' => $proveedorId,
                'usuario_id'   => $usuarioId,
                'subtotal'     => $subtotal,
                'total'        => $total,
                'observacion'  => $observacion,
                'estado'       => 'COMPLETADA',
                'fecha'        => date('Y-m-d H:i:s')
            ]);

            $proveedor = $this->proveedorModel->find($proveedorId);

            foreach ($items as $item) {
                $producto = $this->productoModel->find($item['producto_id']);
                $stockAnterior = $producto['stock_actual'];
                $stockPosterior = $stockAnterior + $item['cantidad'];

                // 1. Guardar detalle
                $this->compraDetalleModel->insert([
                    'compra_id'      => $compraId,
                    'producto_id'    => $item['producto_id'],
                    'cantidad'       => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'subtotal'       => $item['cantidad'] * $item['costo_unitario']
                ]);

                // 2. Actualizar stock y costo producto
                $this->productoModel->update($item['producto_id'], [
                    'stock_actual' => $stockPosterior,
                    'costo'        => $item['costo_unitario']
                ]);

                // 3. Registrar movimiento
                $db->table('movimientos_stock')->insert([
                    'producto_id'     => $item['producto_id'],
                    'tipo_movimiento' => 'COMPRA',
                    'cantidad'        => $item['cantidad'],
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'usuario_id'      => $usuarioId,
                    'referencia_id'   => $compraId,
                    'observacion'     => "Compra #$compraId - " . $proveedor['nombre'],
                    'fecha'           => date('Y-m-d H:i:s')
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Error al procesar la compra.']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Compra registrada correctamente.', 'redirect' => site_url('compras')]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function detalle($id)
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'COMPRAS_VER')) {
            return redirect()->to('compras')->with('error', 'No tienes permisos.');
        }

        $compra = $this->compraModel->obtenerPorIdConRelaciones($id);
        if (!$compra) {
            return redirect()->to('compras')->with('error', 'Compra no encontrada.');
        }

        $data = [
            'titulo' => 'Detalle de Compra #' . $id,
            'compra' => $compra,
            'detalles' => $this->compraDetalleModel->obtenerPorCompra($id)
        ];

        return view('compras/detalle', $data);
    }

    public function anular($id)
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'COMPRAS_ANULAR')) {
            return redirect()->to('compras')->with('error', 'No tienes permisos para anular.');
        }

        $compra = $this->compraModel->find($id);
        if (!$compra || $compra['estado'] === 'ANULADA') {
            return redirect()->to('compras')->with('error', 'La compra no existe o ya está anulada.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $detalles = $this->compraDetalleModel->where('compra_id', $id)->findAll();
            $usuarioId = session()->get('usuario_id');

            foreach ($detalles as $det) {
                $producto = $this->productoModel->find($det['producto_id']);
                $stockAnterior = $producto['stock_actual'];
                $stockPosterior = $stockAnterior - $det['cantidad'];

                // 1. Restaurar stock
                $this->productoModel->update($det['producto_id'], [
                    'stock_actual' => $stockPosterior
                ]);

                // 2. Registrar movimiento de AJUSTE por anulación
                $db->table('movimientos_stock')->insert([
                    'producto_id'     => $det['producto_id'],
                    'tipo_movimiento' => 'AJUSTE',
                    'cantidad'        => $det['cantidad'],
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'usuario_id'      => $usuarioId,
                    'referencia_id'   => $id,
                    'observacion'     => "Anulación de compra #$id",
                    'fecha'           => date('Y-m-d H:i:s')
                ]);
            }

            // 3. Cambiar estado de la compra
            $this->compraModel->update($id, ['estado' => 'ANULADA']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->to('compras/detalle/' . $id)->with('error', 'Error al anular la compra.');
            }

            return redirect()->to('compras/detalle/' . $id)->with('success', 'Compra anulada correctamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('compras/detalle/' . $id)->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
