<?php

namespace App\Services;

use Config\Database;
use App\Models\VentaModel;

/**
 * VentaService — Etapa 4.2 & Etapa 5
 *
 * Lógica de negocio para:
 *  - Registrar ventas atómicas (transacción)
 *  - Consultar historial y KPIs de ventas filtradas
 *  - Consultar detalle completo de una venta
 *  - Anular ventas de forma atómica con devolución de stock y trazabilidad
 */
class VentaService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRAR VENTA
    // ─────────────────────────────────────────────────────────────────────────

    public function procesar(array $data, int $usuarioId): array
    {
        $validacion = $this->validarInput($data);
        if (!$validacion['ok']) {
            return ['success' => false, 'mensaje' => $validacion['mensaje']];
        }

        $resultado = $this->expandirItems($data['items']);
        if (!$resultado['ok']) {
            return ['success' => false, 'mensaje' => $resultado['mensaje']];
        }

        $itemsExpandidos = $resultado['items'];
        $subtotal        = $resultado['subtotal'];
        $descuento       = 0.00;
        $total           = round($subtotal - $descuento, 2);

        $meseroId = null;
        if (!empty($data['mesero_id']) && $data['mesero_id'] !== 'null' && $data['mesero_id'] !== 'barra') {
            $meseroId = (int) $data['mesero_id'];
            $mesero   = $this->db->table('meseros')
                                 ->where('id', $meseroId)
                                 ->where('estado', 'ACTIVO')
                                 ->get()->getRowArray();
            if (!$mesero) {
                return ['success' => false, 'mensaje' => 'El mesero seleccionado no existe o no está activo.'];
            }
        }

        $stockCheck = $this->validarStock($itemsExpandidos);
        if (!$stockCheck['ok']) {
            return ['success' => false, 'mensaje' => $stockCheck['mensaje']];
        }

        $clienteId = null;
        $tipoPago  = strtoupper(trim($data['tipo_pago']));

        if (!empty($data['cliente_id'])) {
            $cId = (int) $data['cliente_id'];
            $cliente = $this->db->table('clientes')
                                ->where('id', $cId)
                                ->where('estado', 'ACTIVO')
                                ->get()->getRowArray();
            if (!$cliente) {
                return ['success' => false, 'mensaje' => 'El cliente seleccionado no existe o no está activo.'];
            }
            $clienteId = $cId;
        }

        if ($tipoPago === 'FIADO' && empty($clienteId)) {
            return ['success' => false, 'mensaje' => 'Para ventas fiadas debe seleccionar un cliente.'];
        }

        $this->db->transBegin();

        try {
            $ventaId = $this->insertarVenta([
                'usuario_id'  => $usuarioId,
                'mesero_id'   => $meseroId,
                'cliente_id'  => $clienteId,
                'subtotal'    => $subtotal,
                'descuento'   => $descuento,
                'total'       => $total,
                'tipo_pago'   => $tipoPago,
                'estado'      => 'COMPLETADA',
                'fecha_venta' => date('Y-m-d H:i:s'),
            ]);

            foreach ($itemsExpandidos as $item) {
                $this->insertarDetalleVenta($ventaId, $item);

                // La línea promoción no controla stock por sí misma, pero su
                // contenido ya fue expandido en items_stock.
                $this->descontarStock($item, $ventaId, $usuarioId);
            }

            if ($tipoPago === 'FIADO') {
                $this->insertarFiado($ventaId, $clienteId, $total, trim($data['observacion'] ?? ''));
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return ['success' => false, 'mensaje' => 'Error interno al guardar la venta. Intente de nuevo.'];
            }

            $this->db->transCommit();

            return [
                'success'  => true,
                'venta_id' => $ventaId,
                'total'    => $total,
                'mensaje'  => 'Venta registrada correctamente.',
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[VentaService] ROLLBACK — ' . $e->getMessage());
            return ['success' => false, 'mensaje' => 'Error al procesar la venta. Se revirtieron los cambios.'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONSULTAS: HISTORIAL Y DETALLE
    // ─────────────────────────────────────────────────────────────────────────

    public function obtenerHistorial(array $filtros = []): array
    {
        $ventaModel = new VentaModel();
        return $ventaModel->obtenerHistorial($filtros);
    }

    public function obtenerResumenFiltros(array $filtros = []): array
    {
        $ventaModel = new VentaModel();
        return $ventaModel->obtenerResumenFiltros($filtros);
    }

    public function obtenerDetalle(int $ventaId): ?array
    {
        $venta = $this->db->table('ventas v')
            ->select('v.*, u.nombre as cajero_nombre, m.nombre as mesero_nombre, c.nombre as cliente_nombre, c.telefono as cliente_telefono')
            ->join('usuarios u', 'u.id = v.usuario_id', 'left')
            ->join('meseros m', 'm.id = v.mesero_id', 'left')
            ->join('clientes c', 'c.id = v.cliente_id', 'left')
            ->where('v.id', $ventaId)
            ->get()->getRowArray();

        if (!$venta) {
            return null;
        }

        $detallesRaw = $this->db->table('venta_detalle vd')
            ->select('vd.*, p.nombre as producto_nombre, p.codigo as producto_codigo, pr.nombre as promocion_nombre')
            ->join('productos p', 'p.id = vd.producto_id', 'left')
            ->join('promociones pr', 'pr.id = vd.promocion_id', 'left')
            ->where('vd.venta_id', $ventaId)
            ->get()->getResultArray();

        $items = [];
        foreach ($detallesRaw as $d) {
            $esPromo = !empty($d['promocion_id']);
            $items[] = [
                'id'              => (int) $d['id'],
                'tipo'            => $esPromo ? 'promocion' : 'producto',
                'nombre'          => $esPromo ? ($d['promocion_nombre'] ?? 'Promoción') : ($d['producto_nombre'] ?? 'Producto'),
                'codigo'          => $esPromo ? 'PROMO' : ($d['producto_codigo'] ?? ''),
                'cantidad'        => (int) $d['cantidad'],
                'precio_unitario' => (float) $d['precio_unitario'],
                'subtotal'        => (float) $d['subtotal'],
            ];
        }

        $venta['items'] = $items;
        return $venta;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ANULAR VENTA (TRANSACCIÓN ATÓMICA Y DEVOLUCIÓN DE STOCK)
    // ─────────────────────────────────────────────────────────────────────────

    public function anular(int $ventaId, int $usuarioId): array
    {
        $this->db->transBegin();

        try {
            // Bloquear la venta durante toda la transacción evita dos
            // anulaciones concurrentes.
            $venta = $this->db->query(
                'SELECT * FROM ventas WHERE id = ? FOR UPDATE',
                [$ventaId]
            )->getRowArray();

            if (!$venta) {
                throw new \RuntimeException('La venta no existe.');
            }

            $estadoActual = strtoupper(trim($venta['estado']));
            if ($estadoActual === 'ANULADA' || $estadoActual === 'CANCELADA') {
                throw new \RuntimeException('Esta venta ya se encuentra anulada y no se puede volver a anular.');
            }

            if ($estadoActual !== 'COMPLETADA') {
                throw new \RuntimeException('Solo se pueden anular ventas en estado COMPLETADA.');
            }

            // La fuente de verdad es lo que efectivamente se descontó.
            $movimientos = $this->db->table('movimientos_stock')
                                    ->where('referencia_id', $ventaId)
                                    ->where('tipo_movimiento', 'VENTA')
                                    ->orderBy('id', 'ASC')
                                    ->get()->getResultArray();

            foreach ($movimientos as $movimiento) {
                $this->devolverStockMovimiento($movimiento, $ventaId, $usuarioId);
            }

            // Si fue FIADO, actualizar la tabla fiados a CANCELADO.
            if (strtoupper($venta['tipo_pago']) === 'FIADO') {
                $this->db->table('fiados')
                         ->where('venta_id', $ventaId)
                         ->update([
                             'saldo'  => 0.00,
                             'estado' => 'CANCELADO',
                             'observacion' => 'Observación / Garantía: Venta anulada'
                         ]);
            }

            // Cambiar el estado al final deja toda la operación protegida por
            // la misma transacción que la devolución del inventario.
            $this->db->table('ventas')
                     ->where('id', $ventaId)
                     ->update(['estado' => 'CANCELADA']);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return ['success' => false, 'mensaje' => 'Error al anular la venta. Se revirtieron los cambios.'];
            }

            $this->db->transCommit();

            return [
                'success'  => true,
                'venta_id' => $ventaId,
                'mensaje'  => "Venta #{$ventaId} anulada correctamente. Stock devuelto al inventario.",
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "[VentaService] ROLLBACK en anulación #{$ventaId} — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Devuelve exactamente un movimiento VENTA ya registrado y crea su AJUSTE.
     */
    private function devolverStockMovimiento(array $movimiento, int $ventaId, int $usuarioId): void
    {
        $producto = $this->db->table('productos')
                             ->select('id, nombre, stock_actual, controla_stock')
                             ->where('id', (int) $movimiento['producto_id'])
                             ->get()->getRowArray();

        if (!$producto) {
            throw new \RuntimeException("Producto ID {$movimiento['producto_id']} no encontrado al devolver stock.");
        }

        $cantidad = (int) $movimiento['cantidad'];
        if ($cantidad <= 0) {
            throw new \RuntimeException('El movimiento de venta tiene una cantidad inválida.');
        }

        $stockAnterior  = (float) $producto['stock_actual'];
        $stockPosterior = $stockAnterior + $cantidad;

        // Actualizar stock del producto
        $this->db->table('productos')
                 ->where('id', (int) $movimiento['producto_id'])
                 ->update(['stock_actual' => $stockPosterior]);

        if ($this->db->affectedRows() === 0) {
            throw new \RuntimeException("No se pudo devolver stock del producto '{$producto['nombre']}'.");
        }

        // Registrar movimiento de stock
        $this->db->table('movimientos_stock')->insert([
            'producto_id'     => (int) $movimiento['producto_id'],
            'tipo_movimiento' => 'AJUSTE',
            'cantidad'        => $cantidad,
            'stock_anterior'  => $stockAnterior,
            'stock_posterior' => $stockPosterior,
            'usuario_id'      => $usuarioId,
            'referencia_id'   => $ventaId,
            'observacion'     => "Reversión de venta #{$ventaId} por anulación",
            'fecha'           => date('Y-m-d H:i:s'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODOS PRIVADOS SECUNDARIOS
    // ─────────────────────────────────────────────────────────────────────────

    private function validarInput(array $data): array
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            return ['ok' => false, 'mensaje' => 'La venta debe contener al menos un producto.'];
        }

        $tiposValidos = ['EFECTIVO', 'YAPE', 'PLIN', 'FIADO'];
        $tipoPago     = strtoupper(trim($data['tipo_pago'] ?? ''));
        if (!in_array($tipoPago, $tiposValidos, true)) {
            return ['ok' => false, 'mensaje' => 'Tipo de pago inválido. Debe ser EFECTIVO, YAPE, PLIN o FIADO.'];
        }

        foreach ($data['items'] as $idx => $item) {
            if (empty($item['tipo']) || !in_array($item['tipo'], ['producto', 'promocion'], true)) {
                return ['ok' => false, 'mensaje' => "Ítem #{$idx}: tipo inválido."];
            }
            if (empty($item['id']) || (int)$item['id'] <= 0) {
                return ['ok' => false, 'mensaje' => "Ítem #{$idx}: ID inválido."];
            }
            if (empty($item['cantidad']) || (int)$item['cantidad'] <= 0) {
                return ['ok' => false, 'mensaje' => "Ítem #{$idx}: la cantidad debe ser mayor a 0."];
            }
        }

        return ['ok' => true, 'mensaje' => ''];
    }

    private function expandirItems(array $itemsFrontend): array
    {
        $itemsExpandidos = [];
        $subtotal        = 0.00;

        foreach ($itemsFrontend as $item) {
            $cantidad = (int) $item['cantidad'];
            $tipo     = $item['tipo'];
            $id       = (int) $item['id'];

            if ($tipo === 'producto') {
                $producto = $this->db->table('productos')
                                     ->where('id', $id)
                                     ->where('estado', 'ACTIVO')
                                     ->get()->getRowArray();

                if (!$producto) {
                    return ['ok' => false, 'mensaje' => "Producto ID {$id} no encontrado o inactivo.", 'items' => [], 'subtotal' => 0];
                }

                $precioUnit = (float) $producto['precio_venta'];
                $subItem    = round($precioUnit * $cantidad, 2);
                $subtotal  += $subItem;

                $itemsExpandidos[] = [
                    'tipo_linea'      => 'producto',
                    'referencia_id'   => $id,
                    'producto_id'     => $id,
                    'nombre'          => $producto['nombre'],
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnit,
                    'subtotal_linea'  => $subItem,
                    'controla_stock'  => (bool) $producto['controla_stock'],
                    'stock_actual'    => (float) $producto['stock_actual'],
                    'items_stock'     => [['producto_id' => $id, 'cantidad' => $cantidad, 'nombre' => $producto['nombre'], 'controla_stock' => (bool) $producto['controla_stock'], 'stock_actual' => (float) $producto['stock_actual']]],
                ];

            } elseif ($tipo === 'promocion') {
                $promo = $this->db->table('promociones')
                                  ->where('id', $id)
                                  ->where('estado', 'ACTIVO')
                                  ->get()->getRowArray();

                if (!$promo) {
                    return ['ok' => false, 'mensaje' => "Promoción ID {$id} no encontrada o inactiva.", 'items' => [], 'subtotal' => 0];
                }

                $detalles = $this->db->table('promocion_detalle pd')
                                     ->select('pd.producto_id, pd.cantidad as cant_promo, p.nombre, p.precio_venta, p.controla_stock, p.stock_actual, p.estado')
                                     ->join('productos p', 'p.id = pd.producto_id')
                                     ->where('pd.promocion_id', $id)
                                     ->get()->getResultArray();

                if (empty($detalles)) {
                    return ['ok' => false, 'mensaje' => "La promoción '{$promo['nombre']}' no tiene productos configurados.", 'items' => [], 'subtotal' => 0];
                }

                foreach ($detalles as $det) {
                    if ($det['estado'] !== 'ACTIVO') {
                        return ['ok' => false, 'mensaje' => "El producto '{$det['nombre']}' de la promoción '{$promo['nombre']}' no está activo.", 'items' => [], 'subtotal' => 0];
                    }
                }

                $precioPromo = (float) $promo['precio'];
                $subItem     = round($precioPromo * $cantidad, 2);
                $subtotal   += $subItem;

                $itemsStock = [];
                foreach ($detalles as $det) {
                    $cantReal = (int)$det['cant_promo'] * $cantidad;
                    $itemsStock[] = [
                        'producto_id'    => $det['producto_id'],
                        'cantidad'       => $cantReal,
                        'nombre'         => $det['nombre'],
                        'controla_stock' => (bool) $det['controla_stock'],
                        'stock_actual'   => (float) $det['stock_actual'],
                    ];
                }

                $itemsExpandidos[] = [
                    'tipo_linea'      => 'promocion',
                    'referencia_id'   => $id,
                    'producto_id'     => null,
                    'nombre'          => $promo['nombre'],
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioPromo,
                    'subtotal_linea'  => $subItem,
                    'controla_stock'  => false,
                    'stock_actual'    => 0,
                    'items_stock'     => $itemsStock,
                ];
            }
        }

        return ['ok' => true, 'mensaje' => '', 'items' => $itemsExpandidos, 'subtotal' => $subtotal];
    }

    private function validarStock(array $items): array
    {
        $requerido = [];
        foreach ($items as $item) {
            foreach ($item['items_stock'] as $s) {
                if ($s['controla_stock']) {
                    $requerido[$s['producto_id']] = ($requerido[$s['producto_id']] ?? 0) + $s['cantidad'];
                }
            }
        }

        foreach ($requerido as $productoId => $cantNeeded) {
            $prod = $this->db->table('productos')
                             ->select('nombre, stock_actual')
                             ->where('id', $productoId)
                             ->get()->getRowArray();

            $stockDisp = $prod ? (float) $prod['stock_actual'] : 0;

            if ($stockDisp < $cantNeeded) {
                $nombre = $prod['nombre'] ?? "ID {$productoId}";
                return [
                    'ok'      => false,
                    'mensaje' => "Stock insuficiente para '{$nombre}'. Disponible: {$stockDisp} | Solicitado: {$cantNeeded}.",
                ];
            }
        }

        return ['ok' => true, 'mensaje' => ''];
    }

    private function insertarVenta(array $datos): int
    {
        $this->db->table('ventas')->insert($datos);
        return (int) $this->db->insertID();
    }

    private function insertarDetalleVenta(int $ventaId, array $item): void
    {
        $fila = [
            'venta_id'        => $ventaId,
            'cantidad'        => $item['cantidad'],
            'precio_unitario' => $item['precio_unitario'],
            'subtotal'        => $item['subtotal_linea'],
        ];

        if ($item['tipo_linea'] === 'producto') {
            $fila['producto_id']  = $item['referencia_id'];
            $fila['promocion_id'] = null;
        } else {
            $fila['producto_id']  = null;
            $fila['promocion_id'] = $item['referencia_id'];
        }

        $this->db->table('venta_detalle')->insert($fila);
    }

    private function descontarStock(array $item, int $ventaId, int $usuarioId): void
    {
        foreach ($item['items_stock'] as $s) {
            if (!$s['controla_stock']) {
                continue;
            }

            $productoId = $s['producto_id'];
            $cantidad   = $s['cantidad'];

            $prod = $this->db->table('productos')
                             ->select('stock_actual, nombre')
                             ->where('id', $productoId)
                             ->get()->getRowArray();

            if (!$prod) {
                throw new \RuntimeException("Producto ID {$productoId} no encontrado al descontar stock.");
            }

            $stockAnterior  = (float) $prod['stock_actual'];
            $stockPosterior = $stockAnterior - $cantidad;

            if ($stockPosterior < 0) {
                throw new \RuntimeException("Stock insuficiente para '{$prod['nombre']}' durante la transacción.");
            }

            $this->db->table('productos')
                     ->where('id', $productoId)
                     ->where('stock_actual >=', $cantidad)
                     ->update(['stock_actual' => $stockPosterior]);

            if ($this->db->affectedRows() === 0) {
                throw new \RuntimeException("No se pudo descontar stock del producto '{$prod['nombre']}'.");
            }

            $this->db->table('movimientos_stock')->insert([
                'producto_id'     => $productoId,
                'tipo_movimiento' => 'VENTA',
                'cantidad'        => $cantidad,
                'stock_anterior'  => $stockAnterior,
                'stock_posterior' => $stockPosterior,
                'usuario_id'      => $usuarioId,
                'referencia_id'   => $ventaId,
                'observacion'     => $item['tipo_linea'] === 'promocion'
                    ? "Venta #{$ventaId} - Producto incluido en promoción \"{$item['nombre']}\""
                    : "Venta #{$ventaId} - Producto directo",
                'fecha'           => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function insertarFiado(int $ventaId, int $clienteId, float $total, string $observacion = ''): void
    {
        $this->db->table('fiados')->insert([
            'cliente_id'  => $clienteId,
            'venta_id'    => $ventaId,
            'monto'       => $total,
            'saldo'       => $total,
            'observacion' => $observacion ?: null,
            'estado'      => 'PENDIENTE',
            'fecha'       => date('Y-m-d H:i:s'),
        ]);
    }
}
