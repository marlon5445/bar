<?php

namespace App\Controllers;

use App\Models\ProductoModel;
use App\Models\CategoriaModel;

class Productos extends BaseController
{
    protected ProductoModel $productoModel;
    protected CategoriaModel $categoriaModel;

    public function __construct()
    {
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoriaModel();
    }

    /**
     * Listar productos
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $productosRaw = $this->productoModel->obtenerTodosConCategoria();
        
        // Verificar si cada producto tiene ventas
        $productos = array_map(function($p) use ($db) {
            $tieneVentas = $db->table('venta_detalle')
                        ->where('producto_id', $p['id'])
                        ->countAllResults() > 0;
            $p['tiene_ventas'] = $tieneVentas;
            return $p;
        }, $productosRaw);

        return view('productos/index', [
            'productos' => $productos,
            'titulo'    => 'Gestión de Productos',
        ]);
    }

    /**
     * Formulario de creación
     */
    public function crear()
    {
        $categorias = $this->categoriaModel->obtenerActivas();

        return view('productos/crear', [
            'categorias' => $categorias,
            'titulo'     => 'Nuevo Producto',
        ]);
    }

    /**
     * Guardar nuevo producto
     */
    public function guardar()
    {
        $codigo = trim($this->request->getPost('codigo'));
        
        // Validación dinámica de código único si no está vacío
        if (!empty($codigo)) {
            $this->productoModel->setValidationRule('codigo', 'max_length[50]|is_unique[productos.codigo]');
        } else {
            $this->productoModel->setValidationRule('codigo', 'permit_empty|max_length[50]');
        }

        $data = [
            'categoria_id'      => $this->request->getPost('categoria_id'),
            'codigo'            => $codigo ?: null,
            'nombre'            => trim($this->request->getPost('nombre')),
            'descripcion'       => trim($this->request->getPost('descripcion')),
            'precio_venta'      => $this->request->getPost('precio_venta'),
            'precio_unidad'     => ($this->request->getPost('maneja_unidades') == 1) ? ($this->request->getPost('precio_unidad') ?: 0) : 0,
            'costo'             => $this->request->getPost('costo') ?: 0,
            'controla_stock'    => $this->request->getPost('controla_stock') ?? 1,
            'maneja_unidades'   => $this->request->getPost('maneja_unidades') ?? 0,
            'unidades_por_caja' => $this->request->getPost('unidades_por_caja') ?? 0,
            'estado'            => $this->request->getPost('estado') ?? 'ACTIVO',
            'fecha_creacion'    => date('Y-m-d H:i:s'),
        ];

        // Si no controla stock, forzar valores a 0
        if ($data['controla_stock'] == 0) {
            $data['stock_actual'] = 0;
            $data['stock_unidades'] = 0;
            $data['stock_minimo'] = 0;
            $data['maneja_unidades'] = 0;
            $data['unidades_por_caja'] = 0;
        } else {
            $data['stock_actual'] = $this->request->getPost('stock_actual') ?: 0;
            $data['stock_minimo'] = $this->request->getPost('stock_minimo') ?: 0;
            
            if ($data['maneja_unidades'] == 1) {
                $data['stock_unidades'] = $this->request->getPost('stock_unidades') ?: 0;
                $data['unidades_por_caja'] = $this->request->getPost('unidades_por_caja') ?: 0;
            } else {
                $data['stock_unidades'] = 0;
                $data['unidades_por_caja'] = 0;
            }
        }

        if (!$this->productoModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->productoModel->errors());
        }

        return redirect()->to(site_url('productos'))->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Formulario de edición
     */
    public function editar($id = null)
    {
        $producto = $this->productoModel->find($id);

        if (!$producto) {
            return redirect()->to(site_url('productos'))->with('error', 'Producto no encontrado.');
        }

        // Obtener todas las categorías para poder mostrar la actual aunque esté inactiva
        $categorias = $this->categoriaModel->findAll();

        return view('productos/editar', [
            'producto'   => $producto,
            'categorias' => $categorias,
            'titulo'     => 'Editar Producto',
        ]);
    }

    /**
     * Actualizar producto
     */
    public function actualizar($id = null)
    {
        $productoExistente = $this->productoModel->find($id);

        if (!$productoExistente) {
            return redirect()->to(site_url('productos'))->with('error', 'Producto no encontrado.');
        }

        $codigo = trim($this->request->getPost('codigo'));

        // Validación dinámica de código único excluyendo el actual
        if (!empty($codigo)) {
            $this->productoModel->setValidationRule('codigo', "max_length[50]|is_unique[productos.codigo,id,{$id}]");
        } else {
            $this->productoModel->setValidationRule('codigo', 'permit_empty|max_length[50]');
        }

        $data = [
            'categoria_id'   => $this->request->getPost('categoria_id'),
            'codigo'         => $codigo ?: null,
            'nombre'         => trim($this->request->getPost('nombre')),
            'descripcion'    => trim($this->request->getPost('descripcion')),
            'precio_venta'   => $this->request->getPost('precio_venta'),
            'precio_unidad'  => ($productoExistente['maneja_unidades'] == 1) ? ($this->request->getPost('precio_unidad') ?: 0) : 0,
            'costo'          => $this->request->getPost('costo') ?: 0,
            'stock_minimo'   => $this->request->getPost('stock_minimo') ?: 0,
            'estado'         => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        // Permitir actualizar unidades_por_caja solo si maneja unidades
        if ($productoExistente['maneja_unidades'] == 1) {
            $data['unidades_por_caja'] = $this->request->getPost('unidades_por_caja') ?: 0;
        }

        if (!$this->productoModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->productoModel->errors());
        }

        return redirect()->to(site_url('productos'))->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Ajustar stock manualmente (ADMIN)
     */
    public function ajustarStock()
    {
        if (session()->get('rol') !== 'ADMIN') {
            return redirect()->to(site_url('productos'))->with('error', 'No tiene permisos para realizar esta operación.');
        }

        $id = $this->request->getPost('producto_id');
        $nuevoStock = (int) $this->request->getPost('nuevo_stock');
        $tipoStock = $this->request->getPost('tipo_stock') ?: 'cajas';
        $motivo = $this->request->getPost('motivo');
        $observacion = trim($this->request->getPost('observacion'));

        if (empty($motivo) || empty($observacion)) {
            return redirect()->back()->with('error', 'El motivo y la observación son obligatorios para el ajuste de stock.');
        }

        $producto = $this->productoModel->find($id);
        if (!$producto || !$producto['controla_stock']) {
            return redirect()->back()->with('error', 'Producto no válido para ajuste de stock.');
        }

        $campoStock = 'stock_actual';
        if ($producto['maneja_unidades'] == 1 && $tipoStock === 'unidades') {
            $campoStock = 'stock_unidades';
        }

        $stockAnterior = (int) $producto[$campoStock];
        $cantidad = abs($nuevoStock - $stockAnterior);

        // Mapear motivo a tipo_movimiento
        $tipoMovimiento = 'AJUSTE';
        if ($motivo === 'Merma') {
            $tipoMovimiento = 'MERMA';
        }

        // Enriquecer observación con el tipo de stock ajustado
        $suffix = ($campoStock === 'stock_unidades') ? ' (UNIDADES)' : ' (CAJAS)';
        $observacionFinal = $observacion . $suffix;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Actualizar stock en productos
            $this->productoModel->update($id, [
                $campoStock => $nuevoStock,
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ]);

            // 2. Registrar movimiento
            $db->table('movimientos_stock')->insert([
                'producto_id'     => $id,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad'        => $cantidad,
                'stock_anterior'  => $stockAnterior,
                'stock_posterior' => $nuevoStock,
                'usuario_id'      => session()->get('usuario_id'),
                'referencia_id'   => null,
                'observacion'     => $observacionFinal,
                'fecha'           => date('Y-m-d H:i:s')
            ]);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Error al procesar el ajuste de stock.');
            }

            $db->transCommit();
            return redirect()->to(site_url('productos'))->with('success', 'Stock ajustado correctamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar estado (Activar/Desactivar) o Eliminar físicamente (ADMIN)
     */
    public function cambiarEstado($id = null)
    {
        if (session()->get('rol') !== 'ADMIN') {
            return redirect()->to(site_url('productos'))->with('error', 'No tiene permisos para realizar esta operación.');
        }

        $producto = $this->productoModel->find($id);

        if (!$producto) {
            return redirect()->to(site_url('productos'))->with('error', 'Producto no encontrado.');
        }

        // Si se solicita eliminación definitiva (parámetro en POST)
        if ($this->request->getPost('accion') === 'eliminar') {
            $db = \Config\Database::connect();
            $tieneVentas = $db->table('venta_detalle')->where('producto_id', $id)->countAllResults() > 0;

            if ($tieneVentas) {
                return redirect()->to(site_url('productos'))->with('error', 'Este producto tiene ventas asociadas y no puede eliminarse físicamente.');
            }

            $this->productoModel->delete($id);
            return redirect()->to(site_url('productos'))->with('success', 'Producto eliminado definitivamente.');
        }

        $nuevoEstado = ($producto['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->productoModel->update($id, [
            'estado' => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        $mensaje = ($nuevoEstado === 'ACTIVO') ? 'Producto activado.' : 'Producto desactivado.';
        return redirect()->to(site_url('productos'))->with('success', $mensaje);
    }
}
