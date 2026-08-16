<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use CodeIgniter\HTTP\ResponseInterface;

class Clientes extends BaseController
{
    protected ClienteModel $clienteModel;

    public function __construct()
    {
        $this->clienteModel = new ClienteModel();
    }

    /**
     * Listar clientes
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $clientesRaw = $this->clienteModel->orderBy('nombre', 'ASC')->findAll();

        $clientes = array_map(function($c) use ($db) {
            // Verificar si tiene fiados activos para prevenir eliminación física
            $c['tiene_fiados'] = $db->table('fiados')->where('cliente_id', $c['id'])->countAllResults() > 0;
            // También verificar en ventas
            $c['tiene_ventas'] = $db->table('ventas')->where('cliente_id', $c['id'])->countAllResults() > 0;
            $c['tiene_relaciones'] = $c['tiene_fiados'] || $c['tiene_ventas'];
            return $c;
        }, $clientesRaw);

        return view('clientes/index', [
            'clientes' => $clientes,
            'titulo'   => 'Gestión de Clientes',
        ]);
    }

    /**
     * Formulario de creación
     */
    public function crear()
    {
        return view('clientes/crear', [
            'titulo' => 'Nuevo Cliente',
        ]);
    }

    /**
     * Guardar nuevo cliente
     */
    public function guardar()
    {
        $data = [
            'nombre'         => trim($this->request->getPost('nombre')),
            'telefono'       => trim($this->request->getPost('telefono')),
            'limite_credito' => $this->request->getPost('limite_credito'),
            'estado'         => $this->request->getPost('estado') ?? 'ACTIVO',
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->clienteModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->clienteModel->errors());
        }

        return redirect()->to(site_url('clientes'))->with('success', 'Cliente registrado exitosamente.');
    }

    /**
     * Formulario de edición
     */
    public function editar($id = null)
    {
        $cliente = $this->clienteModel->find($id);

        if (!$cliente) {
            return redirect()->to(site_url('clientes'))->with('error', 'Cliente no encontrado.');
        }

        return view('clientes/editar', [
            'cliente' => $cliente,
            'titulo'  => 'Editar Cliente',
        ]);
    }

    /**
     * Actualizar cliente
     */
    public function actualizar($id = null)
    {
        $cliente = $this->clienteModel->find($id);

        if (!$cliente) {
            return redirect()->to(site_url('clientes'))->with('error', 'Cliente no encontrado.');
        }

        $data = [
            'nombre'         => trim($this->request->getPost('nombre')),
            'telefono'       => trim($this->request->getPost('telefono')),
            'limite_credito' => $this->request->getPost('limite_credito'),
            'estado'         => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        if (!$this->clienteModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $this->clienteModel->errors());
        }

        return redirect()->to(site_url('clientes'))->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Cambiar estado (Activar/Desactivar) o Eliminar si no tiene relaciones
     */
    public function cambiarEstado($id = null)
    {
        $cliente = $this->clienteModel->find($id);

        if (!$cliente) {
            return redirect()->to(site_url('clientes'))->with('error', 'Cliente no encontrado.');
        }

        $db = \Config\Database::connect();
        $tieneFiados = $db->table('fiados')->where('cliente_id', $id)->countAllResults() > 0;
        $tieneVentas = $db->table('ventas')->where('cliente_id', $id)->countAllResults() > 0;
        $tieneRelaciones = $tieneFiados || $tieneVentas;

        // Si se solicita eliminación definitiva
        if ($this->request->getPost('accion') === 'eliminar') {
            if ($tieneRelaciones) {
                return redirect()->to(site_url('clientes'))->with('error', 'Este cliente tiene historial de ventas o fiados y no puede eliminarse físicamente.');
            }

            $this->clienteModel->delete($id);
            return redirect()->to(site_url('clientes'))->with('success', 'Cliente eliminado definitivamente.');
        }

        $nuevoEstado = ($cliente['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->clienteModel->update($id, [
            'estado' => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        $mensaje = ($nuevoEstado === 'ACTIVO') ? 'Cliente activado exitosamente.' : 'Cliente desactivado exitosamente.';
        return redirect()->to(site_url('clientes'))->with('success', $mensaje);
    }
}
