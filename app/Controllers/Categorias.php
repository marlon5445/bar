<?php

namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Models\ProductoModel;

class Categorias extends BaseController
{
    protected CategoriaModel $categoriaModel;

    public function __construct()
    {
        $this->categoriaModel = new CategoriaModel();
    }

    /**
     * Listar categorías
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $categoriasRaw = $this->categoriaModel->orderBy('id', 'ASC')->findAll();

        $categorias = array_map(function($c) use ($db) {
            $c['tiene_productos'] = $db->table('productos')->where('categoria_id', $c['id'])->countAllResults() > 0;
            return $c;
        }, $categoriasRaw);

        return view('categorias/index', [
            'categorias' => $categorias,
            'titulo'     => 'Gestión de Categorías',
        ]);
    }

    /**
     * Formulario de creación
     */
    public function crear()
    {
        return view('categorias/crear', [
            'titulo' => 'Nueva Categoría',
        ]);
    }

    /**
     * Guardar nueva categoría
     */
    public function guardar()
    {
        $this->categoriaModel->setValidationRule('nombre', 'required|max_length[100]|is_unique[categorias.nombre]');

        $data = [
            'nombre'      => trim($this->request->getPost('nombre')),
            'descripcion' => trim($this->request->getPost('descripcion')),
            'estado'      => $this->request->getPost('estado') ?? 'ACTIVO',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->categoriaModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->categoriaModel->errors());
        }

        return redirect()->to(site_url('categorias'))->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Formulario de edición
     */
    public function editar($id = null)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to(site_url('categorias'))->with('error', 'Categoría no encontrada.');
        }

        return view('categorias/editar', [
            'categoria' => $categoria,
            'titulo'    => 'Editar Categoría',
        ]);
    }

    /**
     * Actualizar categoría
     */
    public function actualizar($id = null)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to(site_url('categorias'))->with('error', 'Categoría no encontrada.');
        }

        // Definir regla de validación para excluir el ID actual
        $this->categoriaModel->setValidationRule('nombre', "required|max_length[100]|is_unique[categorias.nombre,id,{$id}]");

        $data = [
            'nombre'      => trim($this->request->getPost('nombre')),
            'descripcion' => trim($this->request->getPost('descripcion')),
            'estado'      => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->categoriaModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->categoriaModel->errors());
        }

        return redirect()->to(site_url('categorias'))->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Cambiar estado (Activar/Desactivar) o Eliminar físicamente
     */
    public function cambiarEstado($id = null)
    {
        $categoria = $this->categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to(site_url('categorias'))->with('error', 'Categoría no encontrada.');
        }

        // Si se solicita eliminación definitiva (parámetro en POST)
        if ($this->request->getPost('accion') === 'eliminar') {
            // Verificar si tiene productos
            $db = \Config\Database::connect();
            $tieneProductos = $db->table('productos')->where('categoria_id', $id)->countAllResults() > 0;

            if ($tieneProductos) {
                return redirect()->to(site_url('categorias'))->with('error', 'Esta categoría tiene productos asociados y no puede eliminarse físicamente.');
            }

            $this->categoriaModel->delete($id);
            return redirect()->to(site_url('categorias'))->with('success', 'Categoría eliminada definitivamente.');
        }

        $nuevoEstado = ($categoria['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->categoriaModel->update($id, [
            'estado' => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        $mensaje = ($nuevoEstado === 'ACTIVO') ? 'Categoría activada.' : 'Categoría desactivada.';
        return redirect()->to(site_url('categorias'))->with('success', $mensaje);
    }
}
