<?php

namespace App\Controllers;

use App\Models\ProveedorModel;
use App\Models\UsuarioModel;

class Proveedores extends BaseController
{
    protected $proveedorModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->proveedorModel = new ProveedorModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'PROVEEDORES_VER')) {
            return redirect()->to('dashboard')->with('error', 'No tienes permisos para acceder a este módulo.');
        }

        $proveedores = $this->proveedorModel->orderBy('id', 'DESC')->findAll();
        
        // Agregar flag de si tiene compras para la lógica de eliminación en la vista
        foreach ($proveedores as &$p) {
            $p['tiene_compras'] = $this->proveedorModel->tieneCompras($p['id']);
        }

        $data = [
            'titulo' => 'Proveedores',
            'proveedores' => $proveedores
        ];

        return view('proveedores/index', $data);
    }

    public function guardar()
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'PROVEEDORES_CREAR')) {
            return redirect()->to('proveedores')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $data = [
            'nombre'    => $this->request->getPost('nombre'),
            'ruc'       => $this->request->getPost('ruc'),
            'telefono'  => $this->request->getPost('telefono'),
            'direccion' => $this->request->getPost('direccion'),
            'estado'    => 'ACTIVO'
        ];

        if ($this->proveedorModel->save($data)) {
            return redirect()->to('proveedores')->with('success', 'Proveedor guardado correctamente.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->proveedorModel->errors());
        }
    }

    public function actualizar($id)
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'PROVEEDORES_EDITAR')) {
            return redirect()->to('proveedores')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $data = [
            'id'        => $id,
            'nombre'    => $this->request->getPost('nombre'),
            'ruc'       => $this->request->getPost('ruc'),
            'telefono'  => $this->request->getPost('telefono'),
            'direccion' => $this->request->getPost('direccion')
        ];

        if ($this->proveedorModel->save($data)) {
            return redirect()->to('proveedores')->with('success', 'Proveedor actualizado correctamente.');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->proveedorModel->errors());
        }
    }

    public function eliminar($id)
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'PROVEEDORES_EDITAR')) {
            return redirect()->to('proveedores')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        if ($this->proveedorModel->tieneCompras($id)) {
            // Si tiene compras, solo desactivar
            $this->proveedorModel->update($id, ['estado' => 'INACTIVO']);
            return redirect()->to('proveedores')->with('info', 'El proveedor tiene compras asociadas. Se ha desactivado en lugar de eliminar.');
        }

        // Si no tiene compras, eliminación física
        $this->proveedorModel->delete($id);
        return redirect()->to('proveedores')->with('success', 'Proveedor eliminado correctamente.');
    }

    public function cambiarEstado($id)
    {
        if (!$this->usuarioModel->rolTienePermiso(session()->get('rol'), 'PROVEEDORES_EDITAR')) {
            return redirect()->to('proveedores')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $proveedor = $this->proveedorModel->find($id);
        $nuevoEstado = ($proveedor['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->proveedorModel->update($id, ['estado' => $nuevoEstado]);

        return redirect()->to('proveedores')->with('success', 'Estado del proveedor actualizado.');
    }
}
