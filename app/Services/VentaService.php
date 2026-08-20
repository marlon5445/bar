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
            $productosStockAfectado = [];

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
                $this->descontarStock($item, $ventaId, $usuarioId, $productosStockAfectado);
            }

            if ($tipoPago === 'FIADO') {
                $this->insertarFiado($ventaId, $clienteId, $total, trim($data['observacion'] ?? ''));
            }

            $stockActualizado = [];
            foreach (array_keys($productosStockAfectado) as $productoId) {
                $producto = $this->db->table('productos')
                                     ->select('id, stock_actual, stock_unidades, controla_stock, maneja_unidades')
                                     ->where('id', (int) $productoId)
                                     ->where('controla_stock', 1)
                                     ->get()->getRowArray();

                if ($producto) {
                    $stockActualizado[] = [
                        'producto_id'     => (int) $producto['id'],
                        'stock_actual'    => (int) $producto['stock_actual'],
                        'stock_unidades'  => (int) $producto['stock_unidades'],
                        'maneja_unidades' => (bool) $producto['maneja_unidades'],
                    ];
                }
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
                'stock_actualizado' => $stockActualizado,
                'mensaje'  => 'Venta registrada correctamente.',
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[VentaService] ROLLBACK — ' . $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage() ?: 'Error al procesar la venta. Se revirtieron los cambios.'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // APERTURA DE PRODUCTOS
    // ─────────────────────────────────────────────────────────────────────────

    public function realizarApertura(int $productoId, int $cantidad, int $usuarioId, bool $transaccionAutomatica = false): array
    {
        if (!$transaccionAutomatica) {
            $this->db->transBegin();
        }

        try {
            $producto = $this->db->table('productos')
                ->where('id', $productoId)
                ->where('controla_stock', 1)
                ->get()->getRowArray();

            if (!$producto) {
                throw new \RuntimeException('El producto no existe o no controla inventario.');
            }

            if ($producto['stock_actual'] < $cantidad) {
                throw new \RuntimeException("Stock insuficiente. Disponible: {$producto['stock_actual']} | Requerido: {$cantidad}");
            }

            $stockAnteriorActual = (int)$producto['stock_actual'];
            $stockPosteriorActual = $stockAnteriorActual - $cantidad;

            // 1. Descontar del stock_actual
            $this->db->table('productos')
                ->where('id', $productoId)
                ->update(['stock_actual' => $stockPosteriorActual]);

            // 2. Registrar movimiento de salida (Apertura)
            $obsSalida = $producto['maneja_unidades'] == 1 
                ? "Apertura de {$cantidad} cajetilla(s) " . $producto['nombre'] . " para venta por unidades"
                : "Apertura de " . ($cantidad > 1 ? "{$cantidad} unidades" : "una unidad") . " de " . $producto['nombre'];
            
            $this->db->table('movimientos_stock')->insert([
                'producto_id'     => $productoId,
                'tipo_movimiento' => 'APERTURA',
                'cantidad'        => $cantidad,
                'stock_anterior'  => $stockAnteriorActual,
                'stock_posterior' => $stockPosteriorActual,
                'usuario_id'      => $usuarioId,
                'observacion'     => $obsSalida,
                'fecha'           => date('Y-m-d H:i:s')
            ]);

            // 3. Si maneja unidades, sumar al stock_unidades
            if ($producto['maneja_unidades'] == 1) {
                $unidadesPorCaja = (int)$producto['unidades_por_caja'];
                $totalUnidadesNuevas = $cantidad * $unidadesPorCaja;
                $stockAnteriorUnidades = (int)$producto['stock_unidades'];
                $stockPosteriorUnidades = $stockAnteriorUnidades + $totalUnidadesNuevas;

                $this->db->table('productos')
                    ->where('id', $productoId)
                    ->update(['stock_unidades' => $stockPosteriorUnidades]);

                // Registrar movimiento de ingreso de unidades
                $this->db->table('movimientos_stock')->insert([
                    'producto_id'     => $productoId,
                    'tipo_movimiento' => 'APERTURA',
                    'cantidad'        => $totalUnidadesNuevas,
                    'stock_anterior'  => $stockAnteriorUnidades,
                    'stock_posterior' => $stockPosteriorUnidades,
                    'usuario_id'      => $usuarioId,
                    'observacion'     => "Ingreso de {$totalUnidadesNuevas} unidades sueltas por apertura de {$cantidad} cajetilla(s) " . $producto['nombre'],
                    'fecha'           => date('Y-m-d H:i:s')
                ]);
            }

            if (!$transaccionAutomatica) {
                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    return ['success' => false, 'message' => 'Error al procesar la apertura.'];
                }
                $this->db->transCommit();
            }

            return ['success' => true, 'message' => 'Apertura realizada con éxito.'];

        } catch (\Exception $e) {
            if (!$transaccionAutomatica) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    public function revertirApertura(int $movimientoId, int $usuarioId): array
    {
        $this->db->transBegin();

        try {
            $movimiento = $this->db->table('movimientos_stock')
                ->where('id', $movimientoId)
                ->where('tipo_movimiento', 'APERTURA')
                ->get()->getRowArray();

            if (!$movimiento) {
                throw new \RuntimeException('El movimiento de apertura no existe.');
            }

            $producto = $this->db->table('productos')
                ->where('id', $movimiento['producto_id'])
                ->get()->getRowArray();
            
            if (!$producto) {
                throw new \RuntimeException('El producto asociado al movimiento ya no existe.');
            }

            // Identificar si es el movimiento de salida (cajas) o de entrada (unidades)
            // Normalmente se revierte el que se ve en la lista (que debería ser el de cajas)
            // Pero por seguridad, manejamos ambos casos si vienen relacionados.
            
            $cantidad = (int)$movimiento['cantidad'];
            $productoId = (int)$movimiento['producto_id'];

            // Lógica de reversión
            if (strpos($movimiento['observacion'], 'unidades sueltas') !== false) {
                // Es reversión de unidades
                $stockAnterior = (int)$producto['stock_unidades'];
                $stockPosterior = $stockAnterior - $cantidad;
                
                if ($stockPosterior < 0) {
                    throw new \RuntimeException('No se puede revertir: el stock de unidades quedaría en negativo.');
                }

                $this->db->table('productos')->where('id', $productoId)->update(['stock_unidades' => $stockPosterior]);
                
                $ajusteId = $this->db->table('movimientos_stock')->insert([
                    'producto_id'     => $productoId,
                    'tipo_movimiento' => 'AJUSTE',
                    'cantidad'        => $cantidad,
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'usuario_id'      => $usuarioId,
                    'referencia_id'   => $movimientoId, // Relacionar con el movimiento original
                    'observacion'     => "Reversión de {$cantidad} unidades sueltas generadas por apertura de " . $producto['nombre'],
                    'fecha'           => date('Y-m-d H:i:s')
                ]);

            } else {
                // Es reversión de cajas/botellas
                $stockAnterior = (int)$producto['stock_actual'];
                $stockPosterior = $stockAnterior + $cantidad;

                $this->db->table('productos')->where('id', $productoId)->update(['stock_actual' => $stockPosterior]);

                $this->db->table('movimientos_stock')->insert([
                    'producto_id'     => $productoId,
                    'tipo_movimiento' => 'AJUSTE',
                    'cantidad'        => $cantidad,
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'usuario_id'      => $usuarioId,
                    'referencia_id'   => $movimientoId, // Relacionar con el movimiento original
                    'observacion'     => "Reversión de apertura de " . $producto['nombre'],
                    'fecha'           => date('Y-m-d H:i:s')
                ]);

                // Si maneja unidades, debemos buscar y revertir también el movimiento de unidades asociado (si existe)
                if ($producto['maneja_unidades'] == 1) {
                    $unidadesARevertir = $cantidad * (int)$producto['unidades_por_caja'];
                    
                    $stockAnteriorU = (int)$producto['stock_unidades'];
                    $stockPosteriorU = $stockAnteriorU - $unidadesARevertir;
                    
                    if ($stockPosteriorU < 0) {
                         throw new \RuntimeException('No se puede revertir la apertura de cajas porque ya se consumieron las unidades sueltas.');
                    }

                    $this->db->table('productos')->where('id', $productoId)->update(['stock_unidades' => $stockPosteriorU]);

                    $this->db->table('movimientos_stock')->insert([
                        'producto_id'     => $productoId,
                        'tipo_movimiento' => 'AJUSTE',
                        'cantidad'        => $unidadesARevertir,
                        'stock_anterior'  => $stockAnteriorU,
                        'stock_posterior' => $stockPosteriorU,
                        'usuario_id'      => $usuarioId,
                        'referencia_id'   => $movimientoId, // Relacionar con el movimiento principal
                        'observacion'     => "Reversión de {$unidadesARevertir} unidades sueltas generadas por apertura de " . $producto['nombre'],
                        'fecha'           => date('Y-m-d H:i:s')
                    ]);
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Error al revertir la apertura.'];
            }

            $this->db->transCommit();
            return ['success' => true, 'message' => 'Apertura revertida con éxito.'];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['success' => false, 'message' => $e->getMessage()];
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
                             ->where('id', (int) $movimiento['producto_id'])
                             ->get()->getRowArray();

        if (!$producto) {
            throw new \RuntimeException("Producto ID {$movimiento['producto_id']} no encontrado al devolver stock.");
        }

        $cantidad = (int) $movimiento['cantidad'];
        if ($cantidad <= 0) {
            throw new \RuntimeException('El movimiento de venta tiene una cantidad inválida.');
        }

        $venderPorUnidad = strpos($movimiento['observacion'], 'unidad suelta') !== false || strpos($movimiento['observacion'], 'unidades sueltas') !== false;

        if ($venderPorUnidad) {
            $stockAnterior  = (float) $producto['stock_unidades'];
            $stockPosterior = $stockAnterior + $cantidad;
            $campoStock = 'stock_unidades';
        } else {
            $stockAnterior  = (float) $producto['stock_actual'];
            $stockPosterior = $stockAnterior + $cantidad;
            $campoStock = 'stock_actual';
        }

        // Actualizar stock del producto
        $this->db->table('productos')
                 ->where('id', (int) $movimiento['producto_id'])
                 ->update([$campoStock => $stockPosterior]);

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
            'observacion'     => "Reversión de venta #{$ventaId} por anulación - " . ($venderPorUnidad ? "{$cantidad} unidad(es)" : "{$cantidad} presentación(es)") . " de " . $producto['nombre'],
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
            if (isset($item['vender_por_unidad']) && $item['vender_por_unidad'] && (!isset($item['precio_unitario']) || (float)$item['precio_unitario'] <= 0)) {
                // Si viene del frontend pero el backend lo validará igual, esto es solo preventivo.
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

                $ventaPorUnidad = isset($item['vender_por_unidad']) && $item['vender_por_unidad'] == true;
                
                if ($ventaPorUnidad) {
                    $precioUnit = (float) ($producto['precio_unitario'] ?? $producto['precio_unidad'] ?? 0);
                    if ($precioUnit <= 0) {
                        return [
                            'ok'       => false,
                            'mensaje'  => "El producto '{$producto['nombre']}' no tiene configurado precio por unidad.",
                            'items'    => [],
                            'subtotal' => 0,
                        ];
                    }
                } else {
                    $precioUnit = (float) $producto['precio_venta'];
                }

                $subItem    = round($precioUnit * $cantidad, 2);
                $subtotal  += $subItem;

                $itemsExpandidos[] = [
                    'tipo_linea'      => 'producto',
                    'referencia_id'   => $id,
                    'producto_id'     => $id,
                    'nombre'          => $producto['nombre'] . ($ventaPorUnidad ? ' (UNIDAD)' : ''),
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precioUnit,
                    'subtotal_linea'  => $subItem,
                    'controla_stock'  => (bool) $producto['controla_stock'],
                    'maneja_unidades' => (bool) $producto['maneja_unidades'],
                    'vender_por_unidad' => $ventaPorUnidad,
                    'stock_actual'    => (float) $producto['stock_actual'],
                    'stock_unidades'  => (float) $producto['stock_unidades'],
                    'unidades_por_caja' => (int) $producto['unidades_por_caja'],
                    'items_stock'     => [[
                        'producto_id' => $id, 
                        'cantidad' => $cantidad, 
                        'nombre' => $producto['nombre'], 
                        'controla_stock' => (bool) $producto['controla_stock'],
                        'maneja_unidades' => (bool) $producto['maneja_unidades'],
                        'vender_por_unidad' => $ventaPorUnidad,
                        'unidades_por_caja' => (int) $producto['unidades_por_caja']
                    ]],
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
        $requeridoActual = [];
        $requeridoUnidades = [];

        foreach ($items as $item) {
            foreach ($item['items_stock'] as $s) {
                if ($s['controla_stock']) {
                    $pid = $s['producto_id'];
                    $venderPorUnidad = isset($s['vender_por_unidad']) && $s['vender_por_unidad'];
                    
                    if ($venderPorUnidad) {
                        $requeridoUnidades[$pid] = ($requeridoUnidades[$pid] ?? 0) + $s['cantidad'];
                    } else {
                        $requeridoActual[$pid] = ($requeridoActual[$pid] ?? 0) + $s['cantidad'];
                    }
                }
            }
        }

        // Validar stock_actual
        foreach ($requeridoActual as $productoId => $cantNeeded) {
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

        // Validar stock_unidades (considerando apertura automática si es necesario)
        foreach ($requeridoUnidades as $productoId => $cantNeeded) {
            $prod = $this->db->table('productos')
                             ->select('nombre, stock_actual, stock_unidades, maneja_unidades, unidades_por_caja')
                             ->where('id', $productoId)
                             ->get()->getRowArray();

            if (!$prod) continue;

            $stockDisp = (float) $prod['stock_unidades'];
            
            if ($stockDisp < $cantNeeded) {
                // Si no hay suficientes unidades, verificar si se puede abrir una presentación
                // Esto es solo una validación previa. La apertura real se hace en descontarStock.
                // Pero aquí debemos avisar si ni siquiera abriendo hay stock.
                
                $faltante = $cantNeeded - $stockDisp;
                $unidadesPorCaja = (int)$prod['unidades_por_caja'];
                
                if ($unidadesPorCaja <= 0) {
                     return ['ok' => false, 'mensaje' => "El producto '{$prod['nombre']}' no tiene configuradas unidades por caja."];
                }

                $cajasNecesarias = ceil($faltante / $unidadesPorCaja);
                
                if ($prod['stock_actual'] < $cajasNecesarias) {
                     return [
                        'ok'      => false,
                        'mensaje' => "Stock insuficiente para unidades sueltas de '{$prod['nombre']}'. Se requiere abrir {$cajasNecesarias} cajetilla(s) pero solo hay {$prod['stock_actual']} disponible(s).",
                    ];
                }
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

    private function descontarStock(array $item, int $ventaId, int $usuarioId, array &$productosStockAfectado): void
    {
        foreach ($item['items_stock'] as $s) {
            if (!$s['controla_stock']) {
                continue;
            }

            $productoId = $s['producto_id'];
            $cantidad   = $s['cantidad'];
            $venderPorUnidad = isset($s['vender_por_unidad']) && $s['vender_por_unidad'];

            $prod = $this->db->table('productos')
                             ->where('id', $productoId)
                             ->get()->getRowArray();

            if (!$prod) {
                throw new \RuntimeException("Producto ID {$productoId} no encontrado al descontar stock.");
            }

            if ($venderPorUnidad) {
                // Venta por unidad
                $stockAnterior = (int)$prod['stock_unidades'];
                
                // Si no hay stock suelto, realizar apertura automática
                if ($stockAnterior < $cantidad) {
                    $faltante = $cantidad - $stockAnterior;
                    $unidadesPorCaja = (int)$prod['unidades_por_caja'];
                    $cajasAAbrir = ceil($faltante / $unidadesPorCaja);
                    
                    // Llamar a realizarApertura dentro de la misma transacción
                    $this->realizarApertura($productoId, (int)$cajasAAbrir, $usuarioId, true);
                    
                    // Recargar datos del producto después de la apertura
                    $prod = $this->db->table('productos')->where('id', $productoId)->get()->getRowArray();
                    $stockAnterior = (int)$prod['stock_unidades'];
                }

                $stockPosterior = $stockAnterior - $cantidad;

                $this->db->table('productos')
                     ->where('id', $productoId)
                     ->update(['stock_unidades' => $stockPosterior]);
                
                $observacion = "Venta #{$ventaId} - " . ($cantidad > 1 ? "{$cantidad} unidades sueltas" : "1 unidad suelta") . " de " . $prod['nombre'];

            } else {
                // Venta normal (caja/botella)
                $stockAnterior  = (float) $prod['stock_actual'];
                $stockPosterior = $stockAnterior - $cantidad;

                if ($stockPosterior < 0) {
                    throw new \RuntimeException("Stock insuficiente para '{$prod['nombre']}'. Disponible: {$stockAnterior} | Solicitado: {$cantidad}.");
                }

                $this->db->table('productos')
                         ->where('id', $productoId)
                         ->where('stock_actual >=', $cantidad)
                         ->update(['stock_actual' => $stockPosterior]);
                
                $observacion = $item['tipo_linea'] === 'promocion'
                    ? "Venta #{$ventaId} - Producto incluido en promoción \"{$item['nombre']}\""
                    : "Venta #{$ventaId} - " . ($cantidad > 1 ? "{$cantidad} unidades" : "1 unidad") . " de " . $prod['nombre'];
            }

            if ($this->db->affectedRows() === 0) {
                throw new \RuntimeException("No se pudo descontar stock del producto '{$prod['nombre']}'.");
            }

            $productosStockAfectado[$productoId] = true;

            $this->db->table('movimientos_stock')->insert([
                'producto_id'     => $productoId,
                'tipo_movimiento' => 'VENTA',
                'cantidad'        => $cantidad,
                'stock_anterior'  => $stockAnterior,
                'stock_posterior' => $stockPosterior,
                'usuario_id'      => $usuarioId,
                'referencia_id'   => $ventaId,
                'observacion'     => $observacion,
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
