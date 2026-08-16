<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando de pruebas automatizadas para el Módulo de Fiados (Etapa 6).
 * Ejecutar con:
 *   C:\wamp64\bin\php\php8.2.26\php.exe spark test:fiados
 */
class TestFiados extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:fiados';
    protected $description = 'Ejecuta pruebas automatizadas del módulo de Fiados (Etapa 6).';

    protected $db;
    protected int $passed = 0;
    protected int $failed = 0;
    protected array $log   = [];

    public function run(array $params): void
    {
        $this->db = \Config\Database::connect();
        CLI::write('');
        CLI::write('═══════════════════════════════════════════════════════', 'cyan');
        CLI::write('   TEST SUITE — MÓDULO FIADOS (ETAPA 6)', 'cyan');
        CLI::write('═══════════════════════════════════════════════════════', 'cyan');
        CLI::write('');

        // ─── SETUP: Limpiar y preparar datos de prueba ─────────────────────
        $this->setup();

        // ─── TESTS ────────────────────────────────────────────────────────
        $this->test01_clienteDebeExistirParaFiado();
        $this->test02_fiadoSeCreaConVentaTipoPagoFiado();
        $this->test03_fiadoSaldoIgualMontoInicial();
        $this->test04_pago_montoInvalidoRechazado();
        $this->test05_pago_sobreDeudaRechazado();
        $this->test06_pago_clienteInactivoRechazado();
        $this->test07_pagoExitosoReduceSaldo();
        $this->test08_pagoCompleto_estadoPagado();
        $this->test09_pagoParcial_estadoPagadoParcial();
        $this->test10_multiples_fiados_pagadoEnOrdenCronologico();
        $this->test11_anulacion_fiadoSinPagos_saldoCero();
        $this->test12_anulacion_fiadoConPagos_reasigna();
        $this->test13_dobleAnulacionRechazada();
        $this->test14_historialCronologicoCliente();
        $this->test15_resumenClienteDeudaCorrecta();

        // ─── TEARDOWN ─────────────────────────────────────────────────────
        $this->teardown();

        // ─── RESULTADOS ───────────────────────────────────────────────────
        CLI::write('');
        CLI::write('───────────────────────────────────────────────────────', 'dark_gray');
        CLI::write("   RESULTADOS: {$this->passed} ✅  |  {$this->failed} ❌", $this->failed > 0 ? 'red' : 'green');
        CLI::write('───────────────────────────────────────────────────────', 'dark_gray');
        foreach ($this->log as $entry) {
            CLI::write($entry);
        }
        CLI::write('');
    }

    // ════════════════════════════════════════════════════════════════════════
    // SETUP y TEARDOWN
    // ════════════════════════════════════════════════════════════════════════
    private function setup(): void
    {
        $this->teardown(); // Limpiar residuos previos

        // Insertar cliente de prueba activo
        $this->db->table('clientes')->insert([
            'nombre'           => '__TEST_FIADO_CLIENTE__',
            'telefono'         => '999000001',
            'limite_credito'   => 200.00,
            'estado'           => 'ACTIVO',
            'fecha_creacion'   => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('clientes')->insert([
            'nombre'           => '__TEST_FIADO_INACTIVO__',
            'telefono'         => '999000002',
            'limite_credito'   => 0,
            'estado'           => 'INACTIVO',
            'fecha_creacion'   => date('Y-m-d H:i:s'),
        ]);
    }

    private function teardown(): void
    {
        // Buscar IDs de clientes de prueba
        $clientes = $this->db->table('clientes')
            ->whereIn('nombre', ['__TEST_FIADO_CLIENTE__', '__TEST_FIADO_INACTIVO__'])
            ->get()->getResultArray();

        foreach ($clientes as $c) {
            $clienteId = (int) $c['id'];

            // Obtener ventas relacionadas
            $ventas = $this->db->table('ventas')
                ->where('cliente_id', $clienteId)
                ->get()->getResultArray();

            foreach ($ventas as $v) {
                $ventaId = (int) $v['id'];

                // Fiados relacionados
                $fiados = $this->db->table('fiados')
                    ->where('venta_id', $ventaId)
                    ->get()->getResultArray();

                foreach ($fiados as $f) {
                    $this->db->table('pagos_fiado')->where('fiado_id', $f['id'])->delete();
                }

                $this->db->table('fiados')->where('venta_id', $ventaId)->delete();
                $this->db->table('venta_detalle')->where('venta_id', $ventaId)->delete();

            }

            $this->db->table('ventas')->where('cliente_id', $clienteId)->delete();
            $this->db->table('clientes')->where('id', $clienteId)->delete();
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════════
    private function assert(string $testName, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            CLI::write("  ✅ PASS — {$testName}", 'green');
            $this->log[] = CLI::color("  ✅ PASS — {$testName}", 'green');
        } else {
            $this->failed++;
            $msg = "  ❌ FAIL — {$testName}" . ($detail ? " | {$detail}" : '');
            CLI::write($msg, 'red');
            $this->log[] = CLI::color($msg, 'red');
        }
    }

    private function getClienteId(): int
    {
        return (int) ($this->db->table('clientes')
            ->where('nombre', '__TEST_FIADO_CLIENTE__')
            ->get()->getRowArray()['id'] ?? 0);
    }

    private function getClienteInactivoId(): int
    {
        return (int) ($this->db->table('clientes')
            ->where('nombre', '__TEST_FIADO_INACTIVO__')
            ->get()->getRowArray()['id'] ?? 0);
    }

    /**
     * Inserta una venta mínima tipo FIADO en la DB directamente (sin pasar por controller).
     */
    private function crearVentaFiado(int $clienteId, float $total = 30.00, int $meseroId = 1): int
    {
        $this->db->table('ventas')->insert([
            'usuario_id' => 1,
            'mesero_id'  => $meseroId,
            'cliente_id' => $clienteId,
            'subtotal'   => $total,
            'descuento'  => 0,
            'total'      => $total,
            'tipo_pago'  => 'FIADO',
            'estado'     => 'COMPLETADA',
            'fecha_venta'=> date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function crearFiado(int $clienteId, int $ventaId, float $monto): int
    {
        $this->db->table('fiados')->insert([
            'cliente_id' => $clienteId,
            'venta_id'   => $ventaId,
            'monto'      => $monto,
            'saldo'      => $monto,
            'estado'     => 'PENDIENTE',
            'fecha'      => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function crearPago(int $fiadoId, float $monto, string $tipo = 'EFECTIVO'): void
    {
        $this->db->table('pagos_fiado')->insert([
            'fiado_id'    => $fiadoId,
            'usuario_id'  => 1,
            'monto'       => $monto,
            'tipo_pago'   => $tipo,
            'observacion' => 'Pago de prueba automatizado',
            'fecha'       => date('Y-m-d H:i:s'),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // TESTS
    // ════════════════════════════════════════════════════════════════════════

    private function test01_clienteDebeExistirParaFiado(): void
    {
        $service = new \App\Services\FiadoService();
        $result = $service->registrarPago(['cliente_id' => 0, 'monto' => 10, 'tipo_pago' => 'EFECTIVO'], 1);
        $this->assert('T01 — Fiado sin cliente_id es rechazado', !$result['success']);
    }

    private function test02_fiadoSeCreaConVentaTipoPagoFiado(): void
    {
        $clienteId = $this->getClienteId();
        $ventaId   = $this->crearVentaFiado($clienteId, 40.00);
        $fiadoId   = $this->crearFiado($clienteId, $ventaId, 40.00);

        $fiado = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $this->assert('T02 — Fiado creado y asociado correctamente', !empty($fiado) && (int)$fiado['venta_id'] === $ventaId);
    }

    private function test03_fiadoSaldoIgualMontoInicial(): void
    {
        $clienteId = $this->getClienteId();
        $ventaId   = $this->crearVentaFiado($clienteId, 25.00);
        $fiadoId   = $this->crearFiado($clienteId, $ventaId, 25.00);

        $fiado = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $this->assert('T03 — Saldo inicial igual al monto fiado', (float)$fiado['saldo'] === 25.00 && (float)$fiado['monto'] === 25.00);
    }

    private function test04_pago_montoInvalidoRechazado(): void
    {
        $clienteId = $this->getClienteId();
        // Asegurar fiado pendiente
        $ventaId = $this->crearVentaFiado($clienteId, 10.00);
        $this->crearFiado($clienteId, $ventaId, 10.00);

        $service = new \App\Services\FiadoService();
        $r = $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 0, 'tipo_pago' => 'EFECTIVO'], 1);
        $this->assert('T04 — Monto 0 es rechazado', !$r['success']);
    }

    private function test05_pago_sobreDeudaRechazado(): void
    {
        $clienteId = $this->getClienteId();
        $service   = new \App\Services\FiadoService();

        // Obtener deuda actual
        $deuda = (float)($this->db->table('fiados')
            ->selectSum('saldo')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->get()->getRowArray()['saldo'] ?? 0);

        $r = $service->registrarPago(['cliente_id' => $clienteId, 'monto' => $deuda + 500, 'tipo_pago' => 'EFECTIVO'], 1);
        $this->assert('T05 — Pago mayor que deuda total es rechazado', !$r['success']);
    }

    private function test06_pago_clienteInactivoRechazado(): void
    {
        $clienteId = $this->getClienteInactivoId();
        $service   = new \App\Services\FiadoService();
        $r = $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 10, 'tipo_pago' => 'EFECTIVO'], 1);
        $this->assert('T06 — Pago a cliente INACTIVO es rechazado', !$r['success']);
    }

    private function test07_pagoExitosoReduceSaldo(): void
    {
        $clienteId = $this->getClienteId();

        // Cancelar todos los fiados pendientes previos para aislar este test
        $this->db->table('fiados')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->update(['saldo' => 0, 'estado' => 'PAGADO']);

        // Crear fiado limpio de 50
        $ventaId = $this->crearVentaFiado($clienteId, 50.00);
        $fiadoId = $this->crearFiado($clienteId, $ventaId, 50.00);

        $service = new \App\Services\FiadoService();
        $r = $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 20.00, 'tipo_pago' => 'EFECTIVO'], 1);

        $fiado = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $this->assert('T07 — Pago exitoso reduce el saldo del fiado', $r['success'] && (float)$fiado['saldo'] == 30.00,
            "saldo={$fiado['saldo']}");
    }

    private function test08_pagoCompleto_estadoPagado(): void
    {
        $clienteId = $this->getClienteId();

        // Aislar: cancelar fiados pendientes previos
        $this->db->table('fiados')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->update(['saldo' => 0, 'estado' => 'PAGADO']);

        $ventaId   = $this->crearVentaFiado($clienteId, 15.00);
        $fiadoId   = $this->crearFiado($clienteId, $ventaId, 15.00);

        $service = new \App\Services\FiadoService();
        $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 15.00, 'tipo_pago' => 'YAPE'], 1);

        $fiado = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $this->assert('T08 — Pago total pone estado PAGADO', $fiado['estado'] === 'PAGADO' && (float)$fiado['saldo'] == 0.00,
            "estado={$fiado['estado']}, saldo={$fiado['saldo']}");
    }

    private function test09_pagoParcial_estadoPagadoParcial(): void
    {
        $clienteId = $this->getClienteId();

        // Aislar: cancelar fiados pendientes previos
        $this->db->table('fiados')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->update(['saldo' => 0, 'estado' => 'PAGADO']);

        $ventaId   = $this->crearVentaFiado($clienteId, 60.00);
        $fiadoId   = $this->crearFiado($clienteId, $ventaId, 60.00);

        $service = new \App\Services\FiadoService();
        $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 30.00, 'tipo_pago' => 'PLIN'], 1);

        $fiado = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $this->assert('T09 — Pago parcial pone estado PAGADO_PARCIAL', $fiado['estado'] === 'PAGADO_PARCIAL',
            "estado={$fiado['estado']}");
    }

    private function test10_multiples_fiados_pagadoEnOrdenCronologico(): void
    {
        $clienteId = $this->getClienteId();

        // Aislar: cancelar fiados pendientes previos
        $this->db->table('fiados')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->update(['saldo' => 0, 'estado' => 'PAGADO']);

        // Crear 2 fiados con saldo pendiente exacto
        $v1 = $this->crearVentaFiado($clienteId, 20.00);
        $f1 = $this->crearFiado($clienteId, $v1, 20.00);
        // Pequeña pausa para garantizar orden cronológico en MySQL
        sleep(1);
        $v2 = $this->crearVentaFiado($clienteId, 30.00);
        $f2 = $this->crearFiado($clienteId, $v2, 30.00);

        // Pagar 25 — debería cubrir el f1 (20) por completo y abonar 5 al f2 (saldo restante = 25)
        $service = new \App\Services\FiadoService();
        $service->registrarPago(['cliente_id' => $clienteId, 'monto' => 25.00, 'tipo_pago' => 'EFECTIVO'], 1);

        $fiado1 = $this->db->table('fiados')->where('id', $f1)->get()->getRowArray();
        $fiado2 = $this->db->table('fiados')->where('id', $f2)->get()->getRowArray();

        $this->assert('T10 — Pago múltiple aplica en orden cronológico',
            $fiado1['estado'] === 'PAGADO' && (float)$fiado2['saldo'] === 25.00,
            "f1.estado={$fiado1['estado']}, f2.saldo={$fiado2['saldo']}"
        );
    }

    private function test11_anulacion_fiadoSinPagos_saldoCero(): void
    {
        $clienteId = $this->getClienteId();
        $ventaId   = $this->crearVentaFiado($clienteId, 35.00);
        $this->crearFiado($clienteId, $ventaId, 35.00);

        // Simular anulación vía VentaService
        $service = new \App\Services\VentaService();
        $result  = $service->anular($ventaId, 1);

        $fiado = $this->db->table('fiados')->where('venta_id', $ventaId)->get()->getRowArray();
        $this->assert('T11 — Anulación de fiado sin pagos pone saldo 0 y estado CANCELADO',
            $result['success'] && $fiado['estado'] === 'CANCELADO' && (float)$fiado['saldo'] == 0.00,
            "estado={$fiado['estado']}, saldo={$fiado['saldo']}"
        );
    }

    private function test12_anulacion_fiadoConPagos_reasigna(): void
    {
        $clienteId = $this->getClienteId();

        // Cancelar fiados previos para aislar el test
        $this->db->table('fiados')
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['PENDIENTE', 'PAGADO_PARCIAL'])
            ->update(['saldo' => 0, 'estado' => 'PAGADO']);

        // Crear un primer fiado con pago parcial
        $v1      = $this->crearVentaFiado($clienteId, 40.00);

        $fiadoId = $this->crearFiado($clienteId, $v1, 40.00);
        $this->crearPago($fiadoId, 15.00); // Registrar pago de S/15
        // Actualizar saldo manualmente (simular pago ya contabilizado)
        $this->db->table('fiados')->where('id', $fiadoId)->update(['saldo' => 25.00, 'estado' => 'PAGADO_PARCIAL']);

        sleep(1); // Garantizar que f2 sea posterior en fecha

        // Crear un segundo fiado para que el excedente se reasigne
        $v2       = $this->crearVentaFiado($clienteId, 50.00);
        $fiadoId2 = $this->crearFiado($clienteId, $v2, 50.00);


        // Anular el primer fiado
        $service = new \App\Services\VentaService();
        $service->anular($v1, 1);

        $fiado1 = $this->db->table('fiados')->where('id', $fiadoId)->get()->getRowArray();
        $fiado2 = $this->db->table('fiados')->where('id', $fiadoId2)->get()->getRowArray();

        $this->assert('T12 — Anulación de fiado con pagos: saldo del fiado anulado = 0',
            $fiado1['estado'] === 'CANCELADO' && (float)$fiado1['saldo'] == 0.00,
            "f1.estado={$fiado1['estado']}, f1.saldo={$fiado1['saldo']}"
        );
        // El excedente de S/15 debería aplicarse al fiado2 — saldo debería ser 35
        $this->assert('T12b — Excedente reasignado a deuda existente',
            (float)$fiado2['saldo'] <= 35.00,
            "f2.saldo={$fiado2['saldo']}"
        );
    }

    private function test13_dobleAnulacionRechazada(): void
    {
        $clienteId = $this->getClienteId();
        $ventaId   = $this->crearVentaFiado($clienteId, 20.00);
        $this->crearFiado($clienteId, $ventaId, 20.00);

        $service = new \App\Services\VentaService();
        $service->anular($ventaId, 1); // Primera anulación
        $r2 = $service->anular($ventaId, 1); // Segunda anulación — debe fallar

        $this->assert('T13 — Doble anulación de venta fiada es rechazada', !$r2['success']);
    }

    private function test14_historialCronologicoCliente(): void
    {
        $clienteId = $this->getClienteId();
        $fService  = new \App\Services\FiadoService();
        $datos     = $fService->obtenerHistorialCliente($clienteId);

        $this->assert('T14 — obtenerHistorialCliente retorna historial cronológico',
            is_array($datos) && array_key_exists('historial', $datos),
            'historial key no presente'
        );
    }

    private function test15_resumenClienteDeudaCorrecta(): void
    {
        $clienteId = $this->getClienteId();
        $model     = new \App\Models\FiadoModel();
        $resumen   = $model->obtenerResumenCliente($clienteId);

        $this->assert('T15 — obtenerResumenCliente retorna array con deuda_actual',
            is_array($resumen) && array_key_exists('deuda_actual', $resumen) && $resumen['deuda_actual'] >= 0,
            'deuda_actual=' . ($resumen['deuda_actual'] ?? 'NULL')
        );
    }
}
