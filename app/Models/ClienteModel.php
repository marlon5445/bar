<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table            = 'clientes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'telefono',
        'limite_credito',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $validationRules = [
        'nombre'         => 'required|max_length[100]',
        'limite_credito' => 'required|decimal|greater_than_equal_to[0]',
        'estado'         => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del cliente es obligatorio.',
            'max_length' => 'El nombre no debe exceder los 100 caracteres.',
        ],
        'limite_credito' => [
            'required'              => 'El límite de crédito es obligatorio.',
            'decimal'               => 'El límite de crédito debe ser un número decimal.',
            'greater_than_equal_to' => 'El límite de crédito no puede ser negativo.',
        ],
    ];

    /**
     * Obtener clientes activos para selector de fiado
     */
    public function obtenerActivos(): array
    {
        return $this->where('estado', 'ACTIVO')
                    ->orderBy('nombre', 'ASC')
                    ->findAll();
    }

    /**
     * Buscar clientes activos por nombre o teléfono
     */
    public function buscar(string $termino): array
    {
        return $this->where('estado', 'ACTIVO')
                    ->groupStart()
                        ->like('nombre', $termino)
                        ->orLike('telefono', $termino)
                    ->groupEnd()
                    ->orderBy('nombre', 'ASC')
                    ->findAll();
    }
}
