<?php

namespace App\Models;

use CodeIgniter\Model;

class PromocionModel extends Model
{
    protected $table            = 'promociones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'descripcion',
        'precio',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    // Validation
    protected $validationRules      = [
        'nombre' => 'required|max_length[100]',
        'precio' => 'required|decimal|greater_than[0]',
        'estado' => 'required|in_list[ACTIVO,INACTIVO]',
    ];

    /**
     * Obtener todas las promociones activas con detalles
     */
    public function obtenerActivasConDetalle(): array
    {
        return $this->obtenerTodasConDetalle(true);
    }

    /**
     * Obtener todas las promociones con resumen de productos
     */
    public function obtenerTodasConDetalle($soloActivas = false): array
    {
        $builder = $this->orderBy('id', 'DESC');
        if ($soloActivas) {
            $builder->where('estado', 'ACTIVO');
        }
        $promociones = $builder->findAll();

        if (empty($promociones)) {
            return [];
        }

        $db = \Config\Database::connect();

        foreach ($promociones as &$promo) {
            $builderDet = $db->table('promocion_detalle pd');
            $builderDet->select('pd.cantidad, p.nombre as producto_nombre, p.estado as producto_estado');
            $builderDet->join('productos p', 'p.id = pd.producto_id');
            $builderDet->where('pd.promocion_id', $promo['id']);
            $query = $builderDet->get();
            $promo['detalles'] = $query->getResultArray();
            
            // Generar resumen amigable
            $resumen = [];
            foreach ($promo['detalles'] as $det) {
                $nombreProd = $det['producto_nombre'];
                if ($det['producto_estado'] !== 'ACTIVO') {
                    $nombreProd .= ' (INACTIVO)';
                }
                $resumen[] = "{$det['cantidad']} × {$nombreProd}";
            }
            $promo['productos_resumen'] = implode('<br>', $resumen);
            
            // Verificar si tiene ventas asociadas
            $promo['tiene_ventas'] = $db->table('venta_detalle')->where('promocion_id', $promo['id'])->countAllResults() > 0;
        }

        return $promociones;
    }

    /**
     * Obtener una promoción específica con sus detalles
     */
    public function obtenerConDetalle($id): ?array
    {
        $promo = $this->find($id);
        if (!$promo) return null;

        $db = \Config\Database::connect();
        $builderDet = $db->table('promocion_detalle pd');
        $builderDet->select('pd.*, p.nombre as producto_nombre, p.precio_venta as producto_precio, p.estado as producto_estado');
        $builderDet->join('productos p', 'p.id = pd.producto_id');
        $builderDet->where('pd.promocion_id', $id);
        $promo['detalles'] = $builderDet->get()->getResultArray();

        return $promo;
    }
}
