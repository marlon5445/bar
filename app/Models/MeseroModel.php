<?php

namespace App\Models;

use CodeIgniter\Model;

class MeseroModel extends Model
{
    protected $table            = 'meseros';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'telefono',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $validationRules = [
        'nombre' => 'required|max_length[100]',
        'estado' => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del mesero es obligatorio.',
            'max_length' => 'El nombre no debe exceder los 100 caracteres.',
        ],
    ];

    /**
     * Obtener todos los meseros activos
     */
    public function obtenerActivos(): array
    {
        return $this->where('estado', 'ACTIVO')
                    ->orderBy('nombre', 'ASC')
                    ->findAll();
    }
}
