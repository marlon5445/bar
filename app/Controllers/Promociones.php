<?php

namespace App\Controllers;

use App\Models\PromocionModel;
use App\Models\ProductoModel;

class Promociones extends BaseController
{
    protected PromocionModel $promocionModel;
    protected ProductoModel $productoModel;

    public function __construct()
    {
        $this->promocionModel = new PromocionModel();
        $this->productoModel = new ProductoModel();
    }

    /**
     * Listar promociones
     */
    public function index()
    {
        $promociones = $this->promocionModel->obtenerTodasConDetalle();

        return view('promociones/index', [
            'promociones' => $promociones,
            'titulo'      => 'Promociones',
        ]);
    }

    /**
     * Formulario de creación
     */
    public function crear()
    {
        $productos = $this->productoModel->where('estado', 'ACTIVO')->orderBy('nombre', 'ASC')->findAll();

        return view('promociones/crear', [
            'titulo'    => 'Nueva Promoción',
            'productos' => $productos,
        ]);
    }

    /**
     * Guardar nueva promoción
     */
    public function guardar()
    {
        $productosIds = $this->request->getPost('productos');
        $cantidades = $this->request->getPost('cantidades');

        if (empty($productosIds)) {
            return redirect()->back()->withInput()->with('error', 'La promoción debe incluir al menos un producto.');
        }

        // Validar cantidades
        foreach ($cantidades as $cant) {
            if (!is_numeric($cant) || $cant < 1 || floor($cant) != $cant) {
                return redirect()->back()->withInput()->with('error', 'Las cantidades deben ser números enteros mayores a 0.');
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $dataPromo = [
            'nombre'      => trim($this->request->getPost('nombre')),
            'descripcion' => trim($this->request->getPost('descripcion')),
            'precio'      => $this->request->getPost('precio'),
            'estado'      => $this->request->getPost('estado') ?? 'ACTIVO',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->promocionModel->insert($dataPromo)) {
            return redirect()->back()->withInput()->with('errors', $this->promocionModel->errors());
        }

        $promocionId = $this->promocionModel->getInsertID();

        foreach ($productosIds as $index => $prodId) {
            $db->table('promocion_detalle')->insert([
                'promocion_id' => $promocionId,
                'producto_id'  => $prodId,
                'cantidad'     => $cantidades[$index]
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Error al guardar la promoción.');
        }

        return redirect()->to(site_url('promociones'))->with('success', 'Promoción creada exitosamente.');
    }

    /**
     * Formulario de edición
     */
    public function editar($id = null)
    {
        $promocion = $this->promocionModel->obtenerConDetalle($id);

        if (!$promocion) {
            return redirect()->to(site_url('promociones'))->with('error', 'Promoción no encontrada.');
        }

        // Productos activos para agregar nuevos
        $productosActivos = $this->productoModel->where('estado', 'ACTIVO')->orderBy('nombre', 'ASC')->findAll();

        return view('promociones/editar', [
            'promocion' => $promocion,
            'productos' => $productosActivos,
            'titulo'    => 'Editar Promoción',
        ]);
    }

    /**
     * Actualizar promoción
     */
    public function actualizar($id = null)
    {
        $promocion = $this->promocionModel->find($id);

        if (!$promocion) {
            return redirect()->to(site_url('promociones'))->with('error', 'Promoción no encontrada.');
        }

        $productosIds = $this->request->getPost('productos');
        $cantidades = $this->request->getPost('cantidades');

        if (empty($productosIds)) {
            return redirect()->back()->withInput()->with('error', 'La promoción debe incluir al menos un producto.');
        }

        // Validar cantidades
        foreach ($cantidades as $cant) {
            if (!is_numeric($cant) || $cant < 1 || floor($cant) != $cant) {
                return redirect()->back()->withInput()->with('error', 'Las cantidades deben ser números enteros mayores a 0.');
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $dataPromo = [
            'nombre'      => trim($this->request->getPost('nombre')),
            'descripcion' => trim($this->request->getPost('descripcion')),
            'precio'      => $this->request->getPost('precio'),
            'estado'      => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->promocionModel->update($id, $dataPromo)) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('errors', $this->promocionModel->errors());
        }

        // Actualizar detalle: eliminar y volver a insertar
        $db->table('promocion_detalle')->where('promocion_id', $id)->delete();

        foreach ($productosIds as $index => $prodId) {
            $db->table('promocion_detalle')->insert([
                'promocion_id' => $id,
                'producto_id'  => $prodId,
                'cantidad'     => $cantidades[$index]
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la promoción.');
        }

        return redirect()->to(site_url('promociones'))->with('success', 'Promoción actualizada exitosamente.');
    }

    /**
     * Cambiar estado o eliminar
     */
    public function cambiarEstado($id = null)
    {
        $promocion = $this->promocionModel->find($id);

        if (!$promocion) {
            return redirect()->to(site_url('promociones'))->with('error', 'Promoción no encontrada.');
        }

        $db = \Config\Database::connect();
        
        if ($this->request->getPost('accion') === 'eliminar') {
            // Verificar ventas
            $tieneVentas = $db->table('venta_detalle')->where('promocion_id', $id)->countAllResults() > 0;

            if ($tieneVentas) {
                return redirect()->to(site_url('promociones'))->with('error', 'Esta promoción tiene ventas asociadas y no puede eliminarse físicamente.');
            }

            $db->transStart();
            $db->table('promocion_detalle')->where('promocion_id', $id)->delete();
            $this->promocionModel->delete($id);
            $db->transComplete();

            return redirect()->to(site_url('promociones'))->with('success', 'Promoción eliminada definitivamente.');
        }

        $nuevoEstado = ($promocion['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->promocionModel->update($id, [
            'estado' => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        $mensaje = ($nuevoEstado === 'ACTIVO') ? 'Promoción activada.' : 'Promoción desactivada.';
        return redirect()->to(site_url('promociones'))->with('success', $mensaje);
    }
}
