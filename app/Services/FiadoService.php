<?php

namespace App\Services;

use App\Models\FiadoModel;
use App\Models\PagoFiadoModel;
use CodeIgniter\Database\BaseConnection;

class FiadoService
{
    protected BaseConnection $db;
    protected FiadoModel $fiadoModel;
    protected PagoFiadoModel $pagoModel;

    public function __construct()
    {
        $this->db         = \Config\Database::connect();
        $this->fiadoModel = new FiadoModel();
        $this->pagoModel  = new PagoFiadoModel();
    }

    /**
     * Obtener listado de clientes con resumen de deuda actual simple.
     */
    public function obtenerListaClientes(?string $search = null, ?string $filter = null): array
    {
        return $this->fiadoModel->obtenerClientesConDeuda($search, $filter);
    }

    /**
     * Obtener el historial cronológico de movimientos de un cliente.
     * Muestra ventas fiadas (+), pagos (−) y ventas anuladas (informátivas).
     */
    public function obtenerHistorialCliente(int $clienteId): ?array
    {
        $resumen = $this->fiadoModel->obtenerResumenCliente($clienteId);
        if (!$resumen) {
            return null;
        }

        // 1. Obtener todas las ventas fiadas del cliente con su observación y detalles de productos
        $ventasRaw = $this->db->table('ventas v')
            ->select('v.id as venta_id, v.total, v.estado, v.fecha_venta, f.observacion')
            ->join('fiados f', 'f.venta_id = v.id', 'left')
            ->where('v.cliente_id', $clienteId)
            ->where('v.tipo_pago', 'FIADO')
            ->get()->getResultArray();

        // Obtener detalles de productos para todas las ventas fiadas de este cliente
        $detallesVentas = [];
        if (!empty($ventasRaw)) {
            $ventaIds = array_column($ventasRaw, 'venta_id');
            $rawItems = $this->db->table('venta_detalle vd')
                ->select('vd.venta_id, vd.cantidad, vd.precio_unitario, vd.subtotal, p.nombre as producto_nombre, pr.nombre as promocion_nombre')
                ->join('productos p', 'p.id = vd.producto_id', 'left')
                ->join('promociones pr', 'pr.id = vd.promocion_id', 'left')
                ->whereIn('vd.venta_id', $ventaIds)
                ->get()->getResultArray();
            
            foreach ($rawItems as $item) {
                $nombre = $item['producto_nombre'] ?: ($item['promocion_nombre'] ?: 'Desconocido');
                $detallesVentas[$item['venta_id']][] = [
                    'nombre'   => $nombre,
                    'cantidad' => (int) $item['cantidad'],
                    'precio'   => (float) $item['precio_unitario'],
                    'subtotal' => (float) $item['subtotal']
                ];
            }
        }

        // 2. Obtener todos los pagos realizados por el cliente
        $pagosRaw = $this->db->table('pagos_fiado pf')
            ->select('pf.id as pago_id, pf.monto, pf.tipo_pago, pf.observacion, pf.fecha, u.nombre as cajero_nombre')
            ->join('usuarios u', 'u.id = pf.usuario_id', 'left')
            ->where('pf.cliente_id', $clienteId)
            ->get()->getResultArray();

        // 3. Unificar todos los movimientos en una sola línea de tiempo
        $movimientos = [];

        foreach ($ventasRaw as $v) {
            $esAnulada = in_array(strtoupper($v['estado']), ['CANCELADA', 'ANULADA'], true);

            if ($esAnulada) {
                $movimientos[] = [
                    'timestamp'     => strtotime($v['fecha_venta']),
                    'fecha_formato' => date('d M, h:i A', strtotime($v['fecha_venta'])),
                    'tipo'          => 'VENTA_ANULADA',
                    'referencia'    => "Venta #{$v['venta_id']}",
                    'concepto'      => '🚫 ANULADA',
                    'monto'         => (float) $v['total'],
                    'monto_abs'     => (float) $v['total'],
                    'es_anulada'    => true,
                    'color'         => 'gray',
                    'detalle'       => 'Venta fiada anulada',
                    'observacion'   => $v['observacion'] ?? '',
                    'productos'     => $detallesVentas[$v['venta_id']] ?? [],
                ];
            } else {
                $movimientos[] = [
                    'timestamp'     => strtotime($v['fecha_venta']),
                    'fecha_formato' => date('d M, h:i A', strtotime($v['fecha_venta'])),
                    'tipo'          => 'VENTA_FIADA',
                    'referencia'    => "Venta #{$v['venta_id']}",
                    'concepto'      => '🔴 🍻 FIADO',
                    'monto'         => (float) $v['total'],
                    'monto_abs'     => (float) $v['total'],
                    'es_anulada'    => false,
                    'color'         => 'red',
                    'detalle'       => 'Venta fiada',
                    'observacion'   => $v['observacion'] ?? '',
                    'productos'     => $detallesVentas[$v['venta_id']] ?? [],
                ];
            }
        }

        foreach ($pagosRaw as $p) {
            $movimientos[] = [
                'timestamp'     => strtotime($p['fecha']),
                'fecha_formato' => date('d M, h:i A', strtotime($p['fecha'])),
                'tipo'          => 'PAGO',
                'referencia'    => strtoupper($p['tipo_pago']),
                'concepto'      => '🟢 💵 PAGO',
                'monto'         => (float) $p['monto'],
                'monto_abs'     => (float) $p['monto'],
                'es_anulada'    => false,
                'color'         => 'green',
                'detalle'       => $p['observacion'] ?: "Abono realizado",
            ];
        }

        // Ordenar cronológicamente por timestamp descendente (más reciente a más antiguo)
        usort($movimientos, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $resumen['historial'] = $movimientos;
        return $resumen;
    }

    /**
     * Registrar pago directo para un cliente.
     * Reduce la deuda actual del cliente por el monto exacto abonado.
     */
    public function registrarPago(array $data, int $usuarioId): array
    {
        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $montoPago = round((float) ($data['monto'] ?? 0), 2);
        $tipoPago  = strtoupper(trim($data['tipo_pago'] ?? 'EFECTIVO'));
        $obs       = trim($data['observacion'] ?? '');

        if ($clienteId <= 0) {
            return ['success' => false, 'mensaje' => 'Cliente no válido.'];
        }

        if ($montoPago <= 0) {
            return ['success' => false, 'mensaje' => 'El monto del pago debe ser mayor a 0.'];
        }

        $tiposValidos = ['EFECTIVO', 'YAPE', 'PLIN', 'TARJETA'];
        if (!in_array($tipoPago, $tiposValidos, true)) {
            return ['success' => false, 'mensaje' => 'Método de pago no válido.'];
        }

        $cliente = $this->db->table('clientes')
                            ->where('id', $clienteId)
                            ->where('estado', 'ACTIVO')
                            ->get()->getRowArray();
        if (!$cliente) {
            return ['success' => false, 'mensaje' => 'El cliente no existe o se encuentra inactivo.'];
        }

        // Obtener resumen actual para verificar la deuda
        $resumen = $this->fiadoModel->obtenerResumenCliente($clienteId);
        $deudaActual = $resumen['deuda_actual'] ?? 0;

        if ($deudaActual <= 0) {
            return ['success' => false, 'mensaje' => 'El cliente no posee deuda pendiente.'];
        }

        if ($montoPago > $deudaActual + 0.01) {
            return [
                'success' => false,
                'mensaje' => "El monto ingresado (S/ " . number_format($montoPago, 2) . ") supera la deuda actual (S/ " . number_format($deudaActual, 2) . ")."
            ];
        }

        // Insertar el pago directamente
        $ok = $this->pagoModel->insert([
            'cliente_id'  => $clienteId,
            'usuario_id'  => $usuarioId,
            'monto'       => $montoPago,
            'tipo_pago'   => $tipoPago,
            'observacion' => $obs ?: "Abono a deuda de cliente {$cliente['nombre']}",
            'fecha'       => date('Y-m-d H:i:s'),
        ]);

        if (!$ok) {
            return ['success' => false, 'mensaje' => 'Error al guardar el pago en la base de datos.'];
        }

        return [
            'success' => true,
            'mensaje' => "Pago de S/ " . number_format($montoPago, 2) . " registrado correctamente.",
        ];
    }
}
