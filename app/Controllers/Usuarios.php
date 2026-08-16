<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\RolModel;

class Usuarios extends BaseController
{
    protected UsuarioModel $usuarioModel;
    protected RolModel $rolModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->rolModel     = new RolModel();
    }

    /**
     * Listar todos los usuarios del sistema.
     * IMPORTANTE: No incluye el campo password en la vista.
     */
    public function index()
    {
        $usuarios = $this->usuarioModel
            ->select('id, nombre, usuario, rol, estado, fecha_creacion, fecha_actualizacion')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('usuarios/index', [
            'usuarios' => $usuarios,
            'titulo'   => 'Gestión de Usuarios',
        ]);
    }

    /**
     * Formulario de creación de usuario
     */
    public function crear()
    {
        $roles = $this->rolModel->findAll();

        return view('usuarios/crear', [
            'roles'  => $roles,
            'titulo' => 'Nuevo Usuario',
        ]);
    }

    /**
     * Procesar guardado de nuevo usuario
     */
    public function guardar()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'nombre'   => 'required|min_length[3]|max_length[100]',
            'usuario'  => 'required|min_length[3]|max_length[50]|is_unique[usuarios.usuario]',
            'password' => 'required|min_length[6]',
            'rol'      => 'required|in_list[ADMIN,CAJERO]',
            'estado'   => 'required|in_list[ACTIVO,INACTIVO]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $passwordHash = password_hash(trim($this->request->getPost('password')), PASSWORD_DEFAULT);

        $this->usuarioModel->insert([
            'nombre'         => trim($this->request->getPost('nombre')),
            'usuario'        => trim($this->request->getPost('usuario')),
            'password'       => $passwordHash,
            'rol'            => $this->request->getPost('rol'),
            'estado'         => $this->request->getPost('estado'),
            'fecha_creacion' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('usuarios'))->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Formulario de edición de usuario
     */
    public function editar($id = null)
    {
        $usuario = $this->usuarioModel->select('id, nombre, usuario, rol, estado')->find($id);

        if (!$usuario) {
            return redirect()->to(site_url('usuarios'))->with('error', 'Usuario no encontrado.');
        }

        $roles = $this->rolModel->findAll();

        return view('usuarios/editar', [
            'usuario' => $usuario,
            'roles'   => $roles,
            'titulo'  => 'Editar Usuario',
        ]);
    }

    /**
     * Procesar actualización de datos del usuario
     */
    public function actualizar($id = null)
    {
        $usuarioExistente = $this->usuarioModel->find($id);

        if (!$usuarioExistente) {
            return redirect()->to(site_url('usuarios'))->with('error', 'Usuario no encontrado.');
        }

        $usuarioStr = trim($this->request->getPost('usuario'));

        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'usuario' => "required|min_length[3]|max_length[50]|is_unique[usuarios.usuario,id,{$id}]",
            'rol'    => 'required|in_list[ADMIN,CAJERO]',
            'estado' => 'required|in_list[ACTIVO,INACTIVO]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dataUpdate = [
            'nombre'              => trim($this->request->getPost('nombre')),
            'usuario'             => $usuarioStr,
            'rol'                 => $this->request->getPost('rol'),
            'estado'              => $this->request->getPost('estado'),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        $this->usuarioModel->update($id, $dataUpdate);

        return redirect()->to(site_url('usuarios'))->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Activar o desactivar estado del usuario
     */
    public function cambiarEstado($id = null)
    {
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to(site_url('usuarios'))->with('error', 'Usuario no encontrado.');
        }

        // Evitar que el usuario actual se desactive a sí mismo
        if ($usuario['id'] == session()->get('usuario_id')) {
            return redirect()->to(site_url('usuarios'))->with('error', 'No puedes desactivar tu propia cuenta activa.');
        }

        $nuevoEstado = ($usuario['estado'] === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';

        $this->usuarioModel->update($id, [
            'estado'              => $nuevoEstado,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('usuarios'))
            ->with('success', "Estado del usuario {$usuario['usuario']} cambiado a {$nuevoEstado}.");
    }

    /**
     * Formulario de cambio de contraseña
     */
    public function cambiarPassword($id = null)
    {
        $usuario = $this->usuarioModel->select('id, nombre, usuario')->find($id);

        if (!$usuario) {
            return redirect()->to(site_url('usuarios'))->with('error', 'Usuario no encontrado.');
        }

        return view('usuarios/cambiar_password', [
            'usuario' => $usuario,
            'titulo'  => 'Cambiar Contraseña',
        ]);
    }

    /**
     * Procesar el cambio de contraseña con hashing seguro
     */
    public function guardarPassword()
    {
        $id = $this->request->getPost('id');
        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return redirect()->to(site_url('usuarios'))->with('error', 'Usuario no encontrado.');
        }

        $esMiPropioPerfil = ($id == session()->get('usuario_id'));

        $rules = [
            'password_nueva'        => 'required|min_length[6]',
            'password_confirmacion' => 'required|matches[password_nueva]',
        ];

        if ($esMiPropioPerfil) {
            $rules['password_actual'] = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Si es el usuario mismo, validar la contraseña actual
        if ($esMiPropioPerfil) {
            $passwordActual = $this->request->getPost('password_actual');
            if (!password_verify($passwordActual, $usuario['password'])) {
                return redirect()->back()->withInput()->with('error', 'La contraseña actual ingresada es incorrecta.');
            }
        }

        $nuevaPasswordHash = password_hash(trim($this->request->getPost('password_nueva')), PASSWORD_DEFAULT);

        $this->usuarioModel->update($id, [
            'password'            => $nuevaPasswordHash,
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('usuarios'))->with('success', 'Contraseña actualizada con éxito.');
    }
}
