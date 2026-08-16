<?php

namespace App\Models;

use CodeIgniter\Model;

class PagoFiadoModel extends Model
{
    protected $table            = 'pagos_fiado';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cliente_id',
        'fiado_id',
        'usuario_id',
        'monto',
        'tipo_pago',
        'observacion',
        'fecha',
    ];

    /**
     * Obtener historial de pagos abonados por un cliente.
     */
    public function obtenerPagosPorCliente(int $clienteId): array
    {
        return $this->db->table('pagos_fiado pf')
            ->select('pf.*, u.nombre as cajero_nombre')
            ->join('usuarios u', 'u.id = pf.usuario_id', 'left')
            ->where('pf.cliente_id', $clienteId)
            ->orderBy('pf.fecha', 'ASC')
            ->get()
            ->getResultArray();
    }
}
