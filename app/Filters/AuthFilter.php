<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UsuarioModel;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', 'Debes iniciar sesión para acceder a esta sección.');
        }

        // Verificar que el usuario continúe existiendo y con estado ACTIVO en BD
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find($session->get('usuario_id'));

        if (!$usuario || $usuario['estado'] !== 'ACTIVO') {
            $session->destroy();
            return redirect()->to(site_url('login'))->with('error', 'Tu cuenta se encuentra inactiva o ha sido dada de baja.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
