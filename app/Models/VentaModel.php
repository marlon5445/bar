<?php

namespace App\Models;

use CodeIgniter\Model;

class VentaModel extends Model
{
    protected $table            = 'ventas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id',
        'mesero_id',
        'cliente_id',
        'subtotal',
        'descuento',
        'total',
        'tipo_pago',
        'estado',
        'fecha_venta',
    ];

    /**
     * Obtener listado de ventas filtrado con joins a usuarios, meseros y clientes.
     */
    public function obtenerHistorial(array $filtros = []): array
    {
        $builder = $this->db->table('ventas v')
            ->select('v.*, u.nombre as cajero_nombre, m.nombre as mesero_nombre, c.nombre as cliente_nombre')
            ->join('usuarios u', 'u.id = v.usuario_id', 'left')
            ->join('meseros m', 'm.id = v.mesero_id', 'left')
            ->join('clientes c', 'c.id = v.cliente_id', 'left');

        // Filtro por fecha (YYYY-MM-DD)
        if (!empty($filtros['fecha'])) {
            $builder->where('DATE(v.fecha_venta)', $filtros['fecha']);
        }

        // Filtro por mesero: 'barra' => NULL, o ID entero
        if (isset($filtros['mesero_id']) && $filtros['mesero_id'] !== '') {
            if ($filtros['mesero_id'] === 'barra' || $filtros['mesero_id'] === 'null') {
                $builder->where('v.mesero_id IS NULL');
            } else {
                $builder->where('v.mesero_id', (int) $filtros['mesero_id']);
            }
        }

        // Filtro por método de pago
        if (!empty($filtros['tipo_pago'])) {
            $builder->where('v.tipo_pago', strtoupper($filtros['tipo_pago']));
        }

        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $est = strtoupper($filtros['estado']);
            if ($est === 'ANULADA') {
                $est = 'CANCELADA';
            }
            $builder->where('v.estado', $est);
        }

        return $builder->orderBy('v.fecha_venta', 'DESC')
                       ->orderBy('v.id', 'DESC')
                       ->get()
                       ->getResultArray();
    }

    /**
     * Obtener resumen de KPIs para las ventas VÁLIDAS (excluyendo ANULADA/CANCELADA).
     */
    public function obtenerResumenFiltros(array $filtros = []): array
    {
        $builder = $this->db->table('ventas v');

        if (!empty($filtros['fecha'])) {
            $builder->where('DATE(v.fecha_venta)', $filtros['fecha']);
        }

        if (isset($filtros['mesero_id']) && $filtros['mesero_id'] !== '') {
            if ($filtros['mesero_id'] === 'barra' || $filtros['mesero_id'] === 'null') {
                $builder->where('v.mesero_id IS NULL');
            } else {
                $builder->where('v.mesero_id', (int) $filtros['mesero_id']);
            }
        }

        if (!empty($filtros['tipo_pago'])) {
            $builder->where('v.tipo_pago', strtoupper($filtros['tipo_pago']));
        }

        if (!empty($filtros['estado'])) {
            $builder->where('v.estado', strtoupper($filtros['estado']));
        } else {
            // Por defecto en resumen, solo considerar ventas COMPLETADA (excluir ANULADA/CANCELADA)
            $builder->whereIn('v.estado', ['COMPLETADA']);
        }

        $ventas = $builder->get()->getResultArray();

        $resumen = [
            'total_ventas'   => count($ventas),
            'total_monto'    => 0.00,
            'total_efectivo' => 0.00,
            'total_yape'     => 0.00,
            'total_plin'     => 0.00,
            'total_fiado'    => 0.00,
        ];

        foreach ($ventas as $v) {
            $monto = (float) $v['total'];
            $resumen['total_monto'] += $monto;

            switch (strtoupper($v['tipo_pago'])) {
                case 'EFECTIVO':
                    $resumen['total_efectivo'] += $monto;
                    break;
                case 'YAPE':
                    $resumen['total_yape'] += $monto;
                    break;
                case 'PLIN':
                    $resumen['total_plin'] += $monto;
                    break;
                case 'FIADO':
                    $resumen['total_fiado'] += $monto;
                    break;
            }
        }

        return $resumen;
    }
}
