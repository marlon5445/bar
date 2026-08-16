<?php

namespace App\Controllers;

use App\Models\MeseroModel;
use CodeIgniter\HTTP\ResponseInterface;

class Meseros extends BaseController
{
    protected MeseroModel $meseroModel;

    public function __construct()
    {
        $this->meseroModel = new MeseroModel();
    }

    /**
     * Listar meseros
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $meserosRaw = $this->meseroModel->orderBy('nombre', 'ASC')->findAll();

        $meseros = array_map(function($m) use ($db) {
            // Verificar si tiene ventas asociadas para prevenir eliminación física
            $m['tiene_ventas'] = $db->table('ventas')->where('mesero_id', $m['id'])->countAllResults() > 0;
            return $m;
        }, $meserosRaw);

        return view('meseros/index', [
            'meseros' => $meseros,
            'titulo'  => 'Gestión de Meseros',
        ]);
    }

    /**
     * Formulario de creación
     */
    public function crear()
    {
        return view('meseros/crear', [
            'titulo' => 'Nuevo Mesero',
        ]);
    }

    /**
     * Guardar nuevo mesero
     */
    public function guardar()
    {
        // Validación de duplicados para nombre
        $this->meseroModel->setValidationRule('nombre', 'required|max_length[100]|is_unique[meseros.nombre]');

        $data = [
            'nombre'         => trim($this->request->getPost('nombre')),
            'telefono'       => trim($this->request->getPost('telefono')),
            'estado'         => $this->request->getPost('estado') ?? 'ACTIVO',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->meseroModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->meseroModel->errors());
        }

        return redirect()->to(site_url('meseros'))->with('success', 'Mesero registrado exitosamente.');
    }

    /**
     * Formulario de edición
     */
    public function editar($id = null)
    {
        $mesero = $this->meseroModel->find($id);

        if (!$mesero) {
            return redirect()->to(site_url('meseros'))->with('error', 'Mesero no encontrado.');
        }

        return view('meseros/editar', [
            'mesero' => $mesero,
            'titulo' => 'Editar Mesero',
        ]);
    }

    /**
     * Actualizar mesero
     */
    public function actualizar($id = null)
    {
        $mesero = $this->meseroModel->find($id);

        if (!$mesero) {
            return redirect()->to(site_url('meseros'))->with('error', 'Mesero no encontrado.');
        }

        // Validación de duplicados excluyendo el ID actual
        $this->meseroModel->setValidationRule('nombre', "required|max_length[100]|is_unique[meseros.nombre,id,{$id}]");

        $data = [
            'nombre'         => trim($this->request->getPost('nombre')),
            'telefono'       => trim($this->request->getPost('telefono')),
            'estado'         => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->meseroModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->meseroModel->errors());
        }

        return redirect()->to(site_url('meseros'))->with('success', 'Mesero actualizado exitosamente.');
    }

    /**
     * Cambiar estado (Activar/Desactivar) o Eliminar si no tiene ventas
     */
    public function cambiarEstado($id = null)
    {
        $mesero = $this->meseroModel->find($id);

        if (!$mesero) {
            return redirect()->to(site_url('meseros'))->with('error', 'Mesero no encontrado.');
        }

        $db = \Config\Database::connect();
        $tieneVentas = $db->table('ventas')->where('mesero_id', $id)->countAllResults() > 0;

        // Si se solicita eliminación definitiva
        if ($this->request->getPost('accion') === 'eliminar') {
            if ($tieneVentas) {
                return redirect()->to(site_url('meseros'))->with('error', 'Este mesero tiene ventas asociadas y no puede eliminarse físicamente.');
            }

            $this->meseroModel->delete($id);
            return redirect()->to(site_url('meseros'))->with('success', 'Mesero eliminado definitivamente.');
        }

        $nuevoEstado = ($mesero['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->meseroModel->update($id, [
            'estado' => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        $mensaje = ($nuevoEstado === 'ACTIVO') ? 'Mesero activado exitosamente.' : 'Mesero desactivado exitosamente.';
        return redirect()->to(site_url('meseros'))->with('success', $mensaje);
    }
}
