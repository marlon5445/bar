<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\VentaService;

class TestAnulacion extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:anulacion';
    protected $description = 'Prueba automatizada de Etapa 5: Historial y Anulacion de Ventas con devolucion de stock';

    public function run(array $params)
    {
        CLI::write("============================================================", 'yellow');
        CLI::write("  INICIANDO SUITE DE PRUEBAS — ETAPA 5: HISTORIAL Y ANULACIÓN", 'yellow');
        CLI::write("============================================================", 'yellow');

        $ventaService = new VentaService();
        $db = \Config\Database::connect();

        $passCount = 0;
        $failCount = 0;

        $check = function($cond, $label) use (&$passCount, &$failCount) {
            if ($cond) {
                CLI::write("  ✅ PASÓ: {$label}", 'green');
                $passCount++;
            } else {
                CLI::write("  ❌ FALLÓ: {$label}", 'red');
                $failCount++;
            }
        };

        // 1. Probar Historial y Resumen
        CLI::write("\n--- 1. CONSULTA DE HISTORIAL Y RESUMEN KPI ---", 'white');
        $ventas = $ventaService->obtenerHistorial(['fecha' => date('Y-m-d')]);
        $resumen = $ventaService->obtenerResumenFiltros(['fecha' => date('Y-m-d')]);
        $check(is_array($ventas), 'obtenerHistorial retorne array');
        $check(is_array($resumen), 'obtenerResumen retorne array');

        $r = $resumen;
        CLI::write("  Resumen Hoy: {$r['total_ventas']} ventas | Total S/ {$r['total_monto']} | Efec: S/ {$r['total_efectivo']} | Yape: S/ {$r['total_yape']} | Plin: S/ {$r['total_plin']} | Fiado: S/ {$r['total_fiado']}");

        // 2. Crear una Venta Normal para Anular
        CLI::write("\n--- 2. CREACIÓN DE VENTA DE PRUEBA PARA ANULAR ---", 'white');
        // Producto 1 stock actual
        $prodPilsen = $db->table('productos')->where('id', 1)->get()->getRowArray();
        $stockInicial = (int)($prodPilsen['stock_actual'] ?? 0);

        $resVenta = $ventaService->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 1, 'cantidad' => 2]],
            'usuario_id' => 1,
            'mesero_id'  => 1,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0
        ], 1);

        $check($resVenta['success'] === true, 'Venta de prueba creada (2 Pilsen)');
        $ventaId = $resVenta['venta_id'];

        $prodDespues = $db->table('productos')->where('id', 1)->get()->getRowArray();
        $stockVendido = (int)($prodDespues['stock_actual'] ?? 0);
        $check($stockVendido === ($stockInicial - 2), "Stock disminuyó en 2 ($stockInicial -> $stockVendido)");

        // 3. Probar Detalle Completo
        CLI::write("\n--- 3. OBTENER DETALLE COMPLETO DE VENTA #{$ventaId} ---", 'white');
        $detalle = $ventaService->obtenerDetalle($ventaId);
        $check($detalle !== null, 'obtenerDetalle retorne datos');
        $check(count($detalle['items'] ?? []) === 1, '1 item en detalle');
        $check(($detalle['items'][0]['nombre'] ?? '') !== '', 'Nombre de producto visible en detalle');

        // 4. ANULAR LA VENTA Y VERIFICAR RESTAURACIÓN DE STOCK
        CLI::write("\n--- 4. ANULAR VENTA #{$ventaId} Y VERIFICAR DEVOLUCIÓN DE STOCK ---", 'white');
        $resAnulacion = $ventaService->anular($ventaId, 1);
        $check($resAnulacion['success'] === true, 'Anulación retornó success = true');

        // Verificar estado de venta en BD
        $ventaBD = $db->table('ventas')->where('id', $ventaId)->get()->getRowArray();
        $check(in_array(strtoupper(trim($ventaBD['estado'] ?? '')), ['CANCELADA', 'ANULADA']), 'Estado en BD cambió a CANCELADA/ANULADA');

        // Verificar stock restaurado
        $prodRestaurado = $db->table('productos')->where('id', 1)->get()->getRowArray();
        $stockFinal = (int)($prodRestaurado['stock_actual'] ?? 0);
        $check($stockFinal === $stockInicial, "Stock se restauró exactamente ($stockVendido -> $stockFinal == $stockInicial)");

        // Verificar movimiento de stock de anulación
        $mov = $db->table('movimientos_stock')
                  ->where('producto_id', 1)
                  ->whereIn('tipo_movimiento', ['AJUSTE', 'ANULACION_VENTA', 'ENTRADA'])
                  ->where('referencia_id', $ventaId)
                  ->orderBy('id', 'DESC')
                  ->get()->getRowArray();
        $check($mov !== null, 'Movimiento de anulación/devolución registrado en movimientos_stock');
        $check((int)($mov['cantidad'] ?? 0) === 2, 'Cantidad devuelta en movimiento = 2');

        // 5. Intentar Anular por Segunda Vez (Debe Rechazar)
        CLI::write("\n--- 5. RECHAZAR SEGUNDA ANULACIÓN ---", 'white');
        $resAnulacion2 = $ventaService->anular($ventaId, 1);
        $check($resAnulacion2['success'] === false, 'Rechaza re-anulación');

        // 6. Probar Anulación de Venta Fiada
        CLI::write("\n--- 6. ANULACIÓN DE VENTA FIADA ---", 'white');
        $resFiado = $ventaService->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 1, 'cantidad' => 1]],
            'usuario_id' => 1,
            'mesero_id'  => 1,
            'cliente_id' => 1,
            'tipo_pago'  => 'FIADO',
            'descuento'  => 0
        ], 1);
        $ventaFiadaId = $resFiado['venta_id'];
        
        $fiadoReg = $db->table('fiados')->where('venta_id', $ventaFiadaId)->get()->getRowArray();
        $check($fiadoReg !== null, 'Fiado creado');

        $resAnulFiado = $ventaService->anular($ventaFiadaId, 1);
        $check($resAnulFiado['success'] === true, 'Venta fiada anulada');

        $fiadoAnul = $db->table('fiados')->where('venta_id', $ventaFiadaId)->get()->getRowArray();
        $check(in_array(strtoupper($fiadoAnul['estado'] ?? ''), ['CANCELADO', 'ANULADO']), 'Fiado cambió a estado CANCELADO/ANULADO');

        CLI::write("\n============================================================", 'yellow');
        CLI::write("  RESUMEN ETAPA 5: {$passCount} PASARON, {$failCount} FALLARON", $failCount === 0 ? 'green' : 'red');
        CLI::write("============================================================", 'yellow');
    }
}
