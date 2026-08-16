<?php

namespace App\Models;

use CodeIgniter\Model;

class FiadoModel extends Model
{
    protected $table            = 'fiados';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cliente_id',
        'venta_id',
        'monto',
        'saldo',
        'observacion',
        'estado',
        'fecha',
    ];

    /**
     * Obtener listado de clientes con su deuda actual simple.
     * DEUDA ACTUAL = SUM(Ventas Fiadas Válidas) - SUM(Pagos Realizados)
     */
    public function obtenerClientesConDeuda(?string $search = null, ?string $filter = null): array
    {
        $builder = $this->db->table('clientes c')
            ->select('
                c.id as cliente_id,
                c.nombre as cliente_nombre,
                c.telefono as cliente_telefono,
                c.limite_credito,
                COALESCE(v.total_fiado, 0) as total_fiado,
                COALESCE(p.total_pagos, 0) as total_pagos,
                (COALESCE(v.total_fiado, 0) - COALESCE(p.total_pagos, 0)) as saldo_pendiente
            ')
            ->join('(
                SELECT cliente_id, SUM(total) as total_fiado 
                FROM ventas 
                WHERE tipo_pago = \'FIADO\' AND estado = \'COMPLETADA\'
                GROUP BY cliente_id
            ) v', 'v.cliente_id = c.id', 'left')
            ->join('(
                SELECT cliente_id, SUM(monto) as total_pagos 
                FROM pagos_fiado 
                GROUP BY cliente_id
            ) p', 'p.cliente_id = c.id', 'left')
            ->where('c.estado', 'ACTIVO');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('c.nombre', $search)
                ->orLike('c.telefono', $search)
                ->groupEnd();
        }

        $result = $builder->orderBy('saldo_pendiente', 'DESC')
                          ->orderBy('c.nombre', 'ASC')
                          ->get()
                          ->getResultArray();

        $filteredResult = [];
        foreach ($result as &$row) {
            $row['total_fiado']     = round((float) $row['total_fiado'], 2);
            $row['total_pagos']     = round((float) $row['total_pagos'], 2);
            $row['saldo_pendiente'] = max(0.00, round((float) $row['saldo_pendiente'], 2));

            if ($row['saldo_pendiente'] == 0) {
                $row['estado_deuda'] = 'SIN_DEUDA';
            } elseif ($row['saldo_pendiente'] >= 50.00) {
                $row['estado_deuda'] = 'ALTA';
            } else {
                $row['estado_deuda'] = 'REGULAR';
            }

            // Aplicar filtro si existe
            if ($filter) {
                if ($filter === 'CON_DEUDA' && $row['saldo_pendiente'] > 0) {
                    $filteredResult[] = $row;
                } elseif ($filter === 'ALTA' && $row['estado_deuda'] === 'ALTA') {
                    $filteredResult[] = $row;
                } elseif ($filter === 'SIN_DEUDA' && $row['estado_deuda'] === 'SIN_DEUDA') {
                    $filteredResult[] = $row;
                }
            } else {
                $filteredResult[] = $row;
            }
        }

        return $filteredResult;
    }

    /**
     * Obtener el resumen simple de deuda para un cliente específico.
     */
    public function obtenerResumenCliente(int $clienteId): ?array
    {
        $cliente = $this->db->table('clientes')
                            ->where('id', $clienteId)
                            ->get()->getRowArray();

        if (!$cliente) {
            return null;
        }

        $ventasFiadas = $this->db->table('ventas')
            ->selectSum('total', 'total_fiado')
            ->where('cliente_id', $clienteId)
            ->where('tipo_pago', 'FIADO')
            ->whereIn('estado', ['COMPLETADA', 'PENDIENTE_PAGO'])
            ->get()->getRowArray();

        $pagos = $this->db->table('pagos_fiado')
            ->selectSum('monto', 'total_pagos')
            ->where('cliente_id', $clienteId)
            ->get()->getRowArray();

        $totalFiado  = round((float)($ventasFiadas['total_fiado'] ?? 0), 2);
        $totalPagos  = round((float)($pagos['total_pagos'] ?? 0), 2);
        $deudaActual = max(0.00, round($totalFiado - $totalPagos, 2));

        return [
            'cliente_id'     => (int) $cliente['id'],
            'nombre'         => $cliente['nombre'],
            'telefono'       => $cliente['telefono'],
            'limite_credito' => (float) $cliente['limite_credito'],
            'deuda_actual'   => $deudaActual,
            'total_fiado'    => $totalFiado,
            'total_pagos'    => $totalPagos,
        ];
    }
}
