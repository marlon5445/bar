<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraModel extends Model
{
    protected $table            = 'compras';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'proveedor_id',
        'usuario_id',
        'subtotal',
        'total',
        'observacion',
        'estado',
        'fecha',
    ];

    protected $validationRules = [
        'proveedor_id' => 'required|is_not_unique[proveedores.id]',
        'usuario_id'   => 'required|is_not_unique[usuarios.id]',
        'subtotal'     => 'required|decimal',
        'total'        => 'required|decimal',
        'estado'       => 'required|in_list[COMPLETADA,ANULADA]',
    ];

    public function obtenerTodasConRelaciones(): array
    {
        return $this->select('compras.*, proveedores.nombre as proveedor_nombre, usuarios.nombre as usuario_nombre')
                    ->join('proveedores', 'proveedores.id = compras.proveedor_id')
                    ->join('usuarios', 'usuarios.id = compras.usuario_id')
                    ->orderBy('compras.id', 'DESC')
                    ->findAll();
    }

    public function obtenerPorIdConRelaciones($id)
    {
        return $this->select('compras.*, proveedores.nombre as proveedor_nombre, usuarios.nombre as usuario_nombre')
                    ->join('proveedores', 'proveedores.id = compras.proveedor_id')
                    ->join('usuarios', 'usuarios.id = compras.usuario_id')
                    ->where('compras.id', $id)
                    ->first();
    }
}
