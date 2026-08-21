<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfiguracionModel extends Model
{
    protected $table            = 'configuracion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nombre_negocio', 'logo'];

    public function getConfig()
    {
        return $this->find(1) ?: [
            'nombre_negocio' => 'BAR MANAGER',
            'logo' => null
        ];
    }
}
