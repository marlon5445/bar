<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Auth extends BaseController
{
    /**
     * Muestra la pantalla de inicio de sesión
     */
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login');
    }

    /**
     * Procesa la autenticación del usuario
     */
    public function procesarLogin()
    {
        $usuarioStr = trim($this->request->getPost('usuario') ?? '');
        $passwordStr = trim($this->request->getPost('password') ?? '');

        if (empty($usuarioStr) || empty($passwordStr)) {
            return redirect()->to(site_url('login'))
                ->withInput()
                ->with('error', 'Por favor ingresa tu usuario y contraseña.');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->obtenerPorUsuario($usuarioStr);

        // 1. Verificar si existe el usuario
        if (!$usuario) {
            return redirect()->to(site_url('login'))
                ->withInput()
                ->with('error', 'Credenciales de acceso incorrectas.');
        }

        // 2. Verificar estado del usuario (ACTIVO / INACTIVO)
        if ($usuario['estado'] !== 'ACTIVO') {
            return redirect()->to(site_url('login'))
                ->withInput()
                ->with('error', 'Tu usuario se encuentra INACTIVO. Consulta con el administrador.');
        }

        // 3. Verificar contraseña mediante password_verify()
        if (!password_verify($passwordStr, $usuario['password'])) {
            return redirect()->to(site_url('login'))
                ->withInput()
                ->with('error', 'Credenciales de acceso incorrectas.');
        }

        // 4. Regenerar ID de sesión por seguridad
        session()->regenerate();

        // 5. Cargar datos de sesión
        session()->set([
            'usuario_id' => $usuario['id'],
            'nombre'     => $usuario['nombre'],
            'usuario'    => $usuario['usuario'],
            'rol'        => $usuario['rol'],
            'isLoggedIn' => true,
        ]);

        // Redirigir al Dashboard principal
        return redirect()->to(site_url('dashboard'));
    }

    /**
     * Cierra la sesión activa del usuario
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'Has cerrado sesión exitosamente.');
    }

    /**
     * Muestra la vista de Acceso Denegado (403)
     */
    public function accesoDenegado()
    {
        return view('errors/acceso_denegado');
    }
}
