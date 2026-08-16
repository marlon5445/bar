<?php

namespace App\Models;

use CodeIgniter\Model;

class PermisoModel extends Model
{
    protected $table            = 'permisos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nombre', 'descripcion', 'modulo', 'fecha_creacion'];
}
