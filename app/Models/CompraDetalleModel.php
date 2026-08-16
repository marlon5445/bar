<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraDetalleModel extends Model
{
    protected $table            = 'compra_detalle';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'compra_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'subtotal',
    ];

    public function obtenerPorCompra($compra_id): array
    {
        return $this->select('compra_detalle.*, productos.nombre as producto_nombre')
                    ->join('productos', 'productos.id = compra_detalle.producto_id')
                    ->where('compra_id', $compra_id)
                    ->findAll();
    }
}
