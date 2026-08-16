<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriaModel extends Model
{
    protected $table            = 'categorias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'descripcion',
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
            'required' => 'El nombre de la categoría es obligatorio.',
            'max_length' => 'El nombre no debe exceder los 100 caracteres.',
            'is_unique' => 'Ya existe una categoría con ese nombre.',
        ],
    ];

    /**
     * Obtener todas las categorías activas
     */
    public function obtenerActivas(): array
    {
        return $this->where('estado', 'ACTIVO')
                    ->orderBy('nombre', 'ASC')
                    ->findAll();
    }
}
