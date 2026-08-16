<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table            = 'productos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'precio_venta',
        'costo',
        'stock_actual',
        'stock_minimo',
        'controla_stock',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $validationRules = [
        'nombre'         => 'required|max_length[100]',
        'categoria_id'   => 'required|is_not_unique[categorias.id]',
        'precio_venta'   => 'required|decimal|greater_than[0]',
        'costo'          => 'required|decimal|greater_than_equal_to[0]',
        'stock_actual'   => 'permit_empty|integer|greater_than_equal_to[0]',
        'stock_minimo'   => 'permit_empty|integer|greater_than_equal_to[0]',
        'controla_stock' => 'required|in_list[0,1]',
        'estado'         => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre del producto es obligatorio.',
            'max_length' => 'El nombre no debe exceder los 100 caracteres.',
        ],
        'codigo' => [
            'max_length' => 'El código no debe exceder los 50 caracteres.',
            'is_unique'  => 'Ya existe un producto con ese código.',
        ],
        'categoria_id' => [
            'required'      => 'La categoría es obligatoria.',
            'is_not_unique' => 'La categoría seleccionada no es válida.',
        ],
        'precio_venta' => [
            'required'     => 'El precio de venta es obligatorio.',
            'decimal'      => 'El precio debe ser un número decimal.',
            'greater_than' => 'El precio debe ser mayor a 0.',
        ],
        'costo' => [
            'required'              => 'El costo es obligatorio.',
            'decimal'               => 'El costo debe ser un número decimal.',
            'greater_than_equal_to' => 'El costo no puede ser negativo.',
        ],
        'stock_actual' => [
            'integer'               => 'El stock debe ser un número entero.',
            'greater_than_equal_to' => 'El stock no puede ser negativo.',
        ],
        'stock_minimo' => [
            'integer'               => 'El stock mínimo debe ser un número entero.',
            'greater_than_equal_to' => 'El stock mínimo no puede ser negativo.',
        ],
    ];

    /**
     * Obtener todos los productos con el nombre de su categoría
     */
    public function obtenerTodosConCategoria(): array
    {
        return $this->select('productos.*, categorias.nombre as categoria_nombre')
                    ->join('categorias', 'categorias.id = productos.categoria_id')
                    ->orderBy('productos.id', 'ASC')
                    ->findAll();
    }

    /**
     * Obtener todos los productos activos con el nombre de su categoría
     */
    public function obtenerActivosConCategoria(): array
    {
        return $this->select('productos.*, categorias.nombre as categoria_nombre')
                    ->join('categorias', 'categorias.id = productos.categoria_id')
                    ->where('productos.estado', 'ACTIVO')
                    ->where('categorias.estado', 'ACTIVO')
                    ->orderBy('productos.nombre', 'ASC')
                    ->findAll();
    }
}
