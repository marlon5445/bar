<?php

namespace App\Models;

use CodeIgniter\Model;

class ProveedorModel extends Model
{
    protected $table            = 'proveedores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'ruc',
        'telefono',
        'direccion',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $validationRules = [
        'nombre' => 'required|max_length[150]',
        'ruc'    => 'permit_empty|max_length[20]',
        'telefono' => 'permit_empty|max_length[20]',
        'direccion' => 'permit_empty|max_length[255]',
        'estado' => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del proveedor es obligatorio.',
            'max_length' => 'El nombre no debe exceder los 150 caracteres.',
        ],
    ];

    /**
     * Verifica si el proveedor tiene compras asociadas.
     */
    public function tieneCompras($id): bool
    {
        $db = \Config\Database::connect();
        return $db->table('compras')->where('proveedor_id', $id)->countAllResults() > 0;
    }
}
