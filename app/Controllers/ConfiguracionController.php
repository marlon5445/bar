<?php

namespace App\Controllers;

use App\Models\ConfiguracionModel;

class ConfiguracionController extends BaseController
{
    protected $configModel;

    public function __construct()
    {
        $this->configModel = new ConfiguracionModel();
    }

    public function index()
    {
        if (session()->get('rol') !== 'ADMIN') {
            return redirect()->to(site_url('dashboard'))->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        $data = [
            'titulo' => 'Configuración del Sistema',
            'config' => $this->configModel->getConfig()
        ];

        return view('configuracion/index', $data);
    }

    public function guardar()
    {
        if (session()->get('rol') !== 'ADMIN') {
            return redirect()->to(site_url('dashboard'))->with('error', 'No tienes permisos.');
        }

        $nombre = $this->request->getPost('nombre_negocio');
        $logoFile = $this->request->getFile('logo');

        $data = [
            'nombre_negocio' => $nombre
        ];

        if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
            $newName = $logoFile->getRandomName();
            $logoFile->move(FCPATH . 'uploads', $newName);
            $data['logo'] = 'uploads/' . $newName;

            // Opcional: eliminar logo anterior
            $oldConfig = $this->configModel->getConfig();
            if ($oldConfig['logo'] && file_exists(FCPATH . $oldConfig['logo'])) {
                @unlink(FCPATH . $oldConfig['logo']);
            }
        }

        if ($this->configModel->update(1, $data)) {
            return redirect()->to(site_url('configuracion'))->with('success', 'Configuración actualizada correctamente.');
        } else {
            return redirect()->back()->with('error', 'Error al actualizar la configuración.')->withInput();
        }
    }
}
