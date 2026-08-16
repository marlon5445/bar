<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\VentaService;
use Config\Database;

class TestVentas extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:ventas';
    protected $description = 'Ejecuta las 11 pruebas obligatorias del sistema de ventas contra la base de datos.';

    public function run(array $params)
    {
        $db = Database::connect();
        $service = new VentaService();

        $passed = 0;
        $failed = 0;

        $testResult = function (bool $ok, string $desc) use (&$passed, &$failed) {
            if ($ok) {
                $passed++;
                CLI::write("  ✅ PASÓ: {$desc}", 'green');
            } else {
                $failed++;
                CLI::write("  ❌ FALLÓ: {$desc}", 'red');
            }
        };

        // ── PRUEBA 1: Venta de 1 Cerveza Pilsen ──────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 1: Venta de 1 Cerveza Pilsen", 'yellow');
        CLI::write("============================================================", 'yellow');

        $stockAntes1 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
        CLI::write("  Stock antes: {$stockAntes1}");

        $r1 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 1, 'cantidad' => 1]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r1));
        $testResult($r1['success'] === true, 'Venta exitosa');

        if ($r1['success']) {
            $ventaId1 = $r1['venta_id'];
            $venta1 = $db->table('ventas')->where('id', $ventaId1)->get()->getRowArray();
            $testResult($venta1 !== null, "Registro en ventas (ID: {$ventaId1})");
            $testResult($venta1['total'] == '10.00', "Total = 10.00 (actual: {$venta1['total']})");
            $testResult($venta1['estado'] === 'COMPLETADA', "Estado = COMPLETADA");

            $detalle1 = $db->table('venta_detalle')->where('venta_id', $ventaId1)->get()->getResultArray();
            $testResult(count($detalle1) === 1, "1 registro en venta_detalle");
            $testResult($detalle1[0]['producto_id'] == 1, "producto_id = 1");
            $testResult($detalle1[0]['precio_unitario'] == '10.00', "precio_unitario = 10.00");

            $stockDespues1 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
            CLI::write("  Stock después: {$stockDespues1}");
            $testResult($stockDespues1 === $stockAntes1 - 1, "Stock disminuyó en 1 ({$stockAntes1} → {$stockDespues1})");

            $mov1 = $db->table('movimientos_stock')
                       ->where('referencia_id', $ventaId1)
                       ->where('producto_id', 1)
                       ->where('tipo_movimiento', 'VENTA')
                       ->get()->getRowArray();
            $testResult($mov1 !== null, "Movimiento de stock registrado");
            $testResult($mov1['cantidad'] == 1, "Cantidad movida = 1");
        }

        // ── PRUEBA 2: Venta de 3 Cervezas ────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 2: Venta de 3 Cervezas Pilsen", 'yellow');
        CLI::write("============================================================", 'yellow');

        $stockAntes2 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];

        $r2 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 1, 'cantidad' => 3]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r2));
        $testResult($r2['success'] === true, 'Venta exitosa');

        if ($r2['success']) {
            $stockDespues2 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
            $testResult($stockDespues2 === $stockAntes2 - 3, "Stock disminuyó en 3 ({$stockAntes2} → {$stockDespues2})");
            $testResult($r2['total'] == 30.00, "Total = 30.00");
        }

        // ── PRUEBA 3: Venta atendida por mesero ─────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 3: Venta atendida por mesero", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r3 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 8, 'cantidad' => 2]],
            'mesero_id'  => 1,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r3));
        $testResult($r3['success'] === true, 'Venta exitosa');

        if ($r3['success']) {
            $venta3 = $db->table('ventas')->where('id', $r3['venta_id'])->get()->getRowArray();
            $testResult($venta3['mesero_id'] == 1, "mesero_id = 1 (Carlos)");
        }

        // ── PRUEBA 4: Venta directa en barra ─────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 4: Venta directa en barra (mesero_id = NULL)", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r4 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 8, 'cantidad' => 1]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r4));
        $testResult($r4['success'] === true, 'Venta exitosa');

        if ($r4['success']) {
            $venta4 = $db->table('ventas')->where('id', $r4['venta_id'])->get()->getRowArray();
            $testResult($venta4['mesero_id'] === null, "mesero_id IS NULL");
        }

        // ── PRUEBA 5: Venta en efectivo ─────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 5: Venta en efectivo", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r5 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 7, 'cantidad' => 1]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r5));
        if ($r5['success']) {
            $venta5 = $db->table('ventas')->where('id', $r5['venta_id'])->get()->getRowArray();
            $testResult($venta5['tipo_pago'] === 'EFECTIVO', "tipo_pago = EFECTIVO");
        }

        // ── PRUEBA 6: Venta con Yape ─────────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 6: Venta con Yape", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r6 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 5, 'cantidad' => 1]],
            'mesero_id'  => 2,
            'cliente_id' => null,
            'tipo_pago'  => 'YAPE',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r6));
        if ($r6['success']) {
            $venta6 = $db->table('ventas')->where('id', $r6['venta_id'])->get()->getRowArray();
            $testResult($venta6['tipo_pago'] === 'YAPE', "tipo_pago = YAPE");
        }

        // ── PRUEBA 7: Venta con Plin ─────────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 7: Venta con Plin", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r7 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 5, 'cantidad' => 1]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'PLIN',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r7));
        if ($r7['success']) {
            $venta7 = $db->table('ventas')->where('id', $r7['venta_id'])->get()->getRowArray();
            $testResult($venta7['tipo_pago'] === 'PLIN', "tipo_pago = PLIN");
        }

        // ── PRUEBA 8: Venta fiada ────────────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 8: Venta fiada (cliente_id = 1, Juan Pérez)", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r8 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 5, 'cantidad' => 2]],
            'mesero_id'  => 3,
            'cliente_id' => 1,
            'tipo_pago'  => 'FIADO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r8));
        $testResult($r8['success'] === true, 'Venta fiada exitosa');

        if ($r8['success']) {
            $venta8 = $db->table('ventas')->where('id', $r8['venta_id'])->get()->getRowArray();
            $testResult($venta8['tipo_pago'] === 'FIADO', "tipo_pago = FIADO");
            $testResult($venta8['cliente_id'] == 1, "cliente_id = 1");

            $fiado8 = $db->table('fiados')->where('venta_id', $r8['venta_id'])->get()->getRowArray();
            $testResult($fiado8 !== null, "Registro en fiados creado");
            $testResult((float)$fiado8['monto'] == (float)$r8['total'], "monto fiado = total venta ({$fiado8['monto']})");
            $testResult((float)$fiado8['saldo'] == (float)$r8['total'], "saldo = total ({$fiado8['saldo']})");
            $testResult($fiado8['estado'] === 'PENDIENTE', "estado fiado = PENDIENTE");
        }

        // ── PRUEBA 8b: FIADO sin cliente debe fallar ─────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 8b: FIADO sin cliente (debe fallar)", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r8b = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 5, 'cantidad' => 1]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'FIADO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r8b));
        $testResult($r8b['success'] === false, 'Venta rechazada (sin cliente)');

        // ── PRUEBA 9: Producto sin stock suficiente ──────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 9: Producto sin stock suficiente", 'yellow');
        CLI::write("============================================================", 'yellow');

        $stockActual = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
        $cantExcesiva = (int)$stockActual + 10;

        CLI::write("  Stock actual Pilsen: {$stockActual}, solicitando: {$cantExcesiva}");

        $r9 = $service->procesar([
            'items'      => [['tipo' => 'producto', 'id' => 1, 'cantidad' => $cantExcesiva]],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r9));
        $testResult($r9['success'] === false, 'Venta rechazada por stock insuficiente');

        $stockDespues9 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
        $testResult($stockDespues9 === $stockActual, "Stock no se modificó ({$stockActual} = {$stockDespues9})");

        // ── PRUEBA 10: Venta de Promoción ────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 10: Venta de Promoción '3 Jarras S/20' (ID=1)", 'yellow');
        CLI::write("============================================================", 'yellow');

        $r10 = $service->procesar([
            'items'      => [['tipo' => 'promocion', 'id' => 1, 'cantidad' => 1]],
            'mesero_id'  => 2,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r10));
        $testResult($r10['success'] === true, 'Venta de promo exitosa');

        if ($r10['success']) {
            $detalle10 = $db->table('venta_detalle')->where('venta_id', $r10['venta_id'])->get()->getResultArray();
            $testResult(count($detalle10) === 1, "1 registro en venta_detalle (la promo)");
            $testResult($detalle10[0]['promocion_id'] == 1, "promocion_id = 1");
            $testResult($detalle10[0]['producto_id'] === null, "producto_id = NULL (es promo)");
            $testResult((float)$r10['total'] == 20.00, "Total = S/ 20.00");
        }

        // ── PRUEBA 11: ROLLBACK provocado ─────────────────────────────────────
        CLI::write("\n============================================================", 'yellow');
        CLI::write("  PRUEBA 11: Verificación de ROLLBACK (producto inexistente)", 'yellow');
        CLI::write("============================================================", 'yellow');

        $stockAntes11 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
        $ventasAntes11 = (int) $db->table('ventas')->countAllResults();

        $r11 = $service->procesar([
            'items'      => [
                ['tipo' => 'producto', 'id' => 1, 'cantidad' => 1],
                ['tipo' => 'producto', 'id' => 99999, 'cantidad' => 1],
            ],
            'mesero_id'  => null,
            'cliente_id' => null,
            'tipo_pago'  => 'EFECTIVO',
            'descuento'  => 0,
        ], 2);

        CLI::write("  Resultado: " . json_encode($r11));
        $testResult($r11['success'] === false, 'Venta rechazada (producto inexistente)');

        $stockDespues11 = (float) $db->table('productos')->where('id', 1)->get()->getRowArray()['stock_actual'];
        $ventasDespues11 = (int) $db->table('ventas')->countAllResults();

        $testResult($stockDespues11 === $stockAntes11, "Stock no cambió ({$stockAntes11})");
        $testResult($ventasDespues11 === $ventasAntes11, "No se creó venta ({$ventasAntes11} ventas)");

        // ── RESUMEN FINAL ─────────────────────────────────────────────────────
        CLI::write("\n============================================================", 'cyan');
        CLI::write("  RESUMEN: {$passed} PASARON, {$failed} FALLARON", $failed > 0 ? 'red' : 'green');
        CLI::write("============================================================\n", 'cyan');
    }
}
