<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UsuarioModel;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', 'Debes iniciar sesión.');
        }

        $rol = $session->get('rol');

        // El ADMIN posee acceso irrestricto a todas las funciones
        if ($rol === 'ADMIN') {
            return;
        }

        // Si se especificó un permiso o rol requerido como argumento del filtro
        if (!empty($arguments)) {
            $permisoRequerido = $arguments[0];

            $usuarioModel = new UsuarioModel();
            
            // Si el argumento coincide con un rol (ej. ADMIN) o con un permiso específico (ej. USUARIOS_VER)
            $tienePermiso = $usuarioModel->rolTienePermiso($rol, $permisoRequerido);

            if (!$tienePermiso) {
                return redirect()->to(site_url('acceso-denegado'));
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
