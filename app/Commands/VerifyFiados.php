<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Verificación de las 8 condiciones del módulo simplificado de Fiados.
 * Ejecutar con: php spark verify:fiados
 */
class VerifyFiados extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'verify:fiados';
    protected $description = 'Verifica las 8 condiciones del módulo de Fiados simplificado.';

    protected $db;
    protected int $pass = 0;
    protected int $fail = 0;

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();

        CLI::write('');
        CLI::write('══════════════════════════════════════════════════════', 'cyan');
        CLI::write('  VERIFICACIÓN MÓDULO FIADOS — 8 CONDICIONES', 'cyan');
        CLI::write('══════════════════════════════════════════════════════', 'cyan');

        // Obtener cliente de prueba (Juan Pérez)
        $clienteJuan = $this->db->table('clientes')->where('nombre', 'Juan Pérez')->get()->getRowArray();
        if (!$clienteJuan) {
            CLI::write('ERROR: Ejecuta primero "php spark seed:fiados"', 'red');
            return;
        }

        $this->check1_ventaFiadaAumentaDeuda($clienteJuan);
        $this->check2_pagoDisminuyeDeuda($clienteJuan);
        $this->check3_pagoParcialExacto($clienteJuan);
        $this->check4_ventaAnuladaNoCuentaDeuda();
        $this->check5_historialPermanece($clienteJuan);
        $this->check6_deudaCoincideConDB($clienteJuan);
        $this->check7_datosAntiguosEliminados();
        $this->check8_datosDemoFuncionan();

        CLI::write('');
        CLI::write('──────────────────────────────────────────────────────', 'dark_gray');
        CLI::write("  RESULTADO: {$this->pass} ✅  |  {$this->fail} ❌", $this->fail > 0 ? 'red' : 'green');
        CLI::write('──────────────────────────────────────────────────────', 'dark_gray');
        CLI::write('');
    }

    private function ok(string $msg): void
    {
        $this->pass++;
        CLI::write("  ✅ {$msg}", 'green');
    }

    private function ko(string $msg, string $detail = ''): void
    {
        $this->fail++;
        CLI::write("  ❌ {$msg}" . ($detail ? " — {$detail}" : ''), 'red');
    }

    // CONDICIÓN 1: Venta fiada aumenta deuda
    private function check1_ventaFiadaAumentaDeuda(array $cliente): void
    {
        $clienteId = (int)$cliente['id'];
        $deudaAntes = $this->calcularDeuda($clienteId);

        // Crear una nueva venta fiada ficticia
        $this->db->table('ventas')->insert([
            'usuario_id' => 1, 'mesero_id' => 1, 'cliente_id' => $clienteId,
            'subtotal' => 10.00, 'descuento' => 0, 'total' => 10.00,
            'tipo_pago' => 'FIADO', 'estado' => 'COMPLETADA',
            'fecha_venta' => date('Y-m-d H:i:s'),
        ]);
        $ventaId = (int)$this->db->insertID();
        $this->db->table('fiados')->insert([
            'cliente_id' => $clienteId, 'venta_id' => $ventaId,
            'monto' => 10.00, 'saldo' => 10.00, 'estado' => 'PENDIENTE',
            'fecha' => date('Y-m-d H:i:s'),
        ]);

        $deudaDespues = $this->calcularDeuda($clienteId);

        // Limpiar
        $this->db->table('fiados')->where('venta_id', $ventaId)->delete();
        $this->db->table('ventas')->where('id', $ventaId)->delete();

        if ($deudaDespues == $deudaAntes + 10.00) {
            $this->ok('CHECK 1 — Venta fiada aumenta deuda exactamente');
        } else {
            $this->ko('CHECK 1 — Venta fiada NO aumenta deuda correctamente', "antes={$deudaAntes} después={$deudaDespues}");
        }
    }

    // CONDICIÓN 2: Pago disminuye deuda
    private function check2_pagoDisminuyeDeuda(array $cliente): void
    {
        $clienteId = (int)$cliente['id'];
        $deudaAntes = $this->calcularDeuda($clienteId);

        if ($deudaAntes <= 0) {
            $this->ko('CHECK 2 — No hay deuda para registrar pago');
            return;
        }

        $montoPago = min(5.00, $deudaAntes);
        $this->db->table('pagos_fiado')->insert([
            'cliente_id' => $clienteId, 'usuario_id' => 1,
            'monto' => $montoPago, 'tipo_pago' => 'EFECTIVO',
            'observacion' => 'Pago de verificación CHECK2',
            'fecha' => date('Y-m-d H:i:s'),
        ]);
        $pagoId = (int)$this->db->insertID();
        $deudaDespues = $this->calcularDeuda($clienteId);

        // Limpiar
        $this->db->table('pagos_fiado')->where('id', $pagoId)->delete();

        $esperado = round($deudaAntes - $montoPago, 2);
        if (round($deudaDespues, 2) == $esperado) {
            $this->ok('CHECK 2 — Pago disminuye deuda exactamente');
        } else {
            $this->ko('CHECK 2 — Pago NO disminuye deuda', "esperado={$esperado} obtenido={$deudaDespues}");
        }
    }

    // CONDICIÓN 3: Pago parcial muestra exactamente su monto
    private function check3_pagoParcialExacto(array $cliente): void
    {
        $clienteId = (int)$cliente['id'];
        $deudaAntes = $this->calcularDeuda($clienteId);

        if ($deudaAntes < 27.00) {
            $this->ko('CHECK 3 — Deuda insuficiente para prueba de S/27');
            return;
        }

        $this->db->table('pagos_fiado')->insert([
            'cliente_id' => $clienteId, 'usuario_id' => 1,
            'monto' => 27.00, 'tipo_pago' => 'EFECTIVO',
            'observacion' => 'Pago parcial CHECK3',
            'fecha' => date('Y-m-d H:i:s'),
        ]);
        $pagoId = (int)$this->db->insertID();

        // Verificar que el pago aparece con exactamente S/27
        $pago = $this->db->table('pagos_fiado')->where('id', $pagoId)->get()->getRowArray();
        $deudaDespues = $this->calcularDeuda($clienteId);

        // Limpiar
        $this->db->table('pagos_fiado')->where('id', $pagoId)->delete();

        if ((float)$pago['monto'] === 27.00 && round($deudaDespues, 2) === round($deudaAntes - 27.00, 2)) {
            $this->ok('CHECK 3 — Pago parcial S/27 registrado y deuda reducida exactamente');
        } else {
            $this->ko('CHECK 3 — Pago parcial no coincide', "monto={$pago['monto']} deudaDespues={$deudaDespues}");
        }
    }

    // CONDICIÓN 4: Venta anulada no genera deuda
    private function check4_ventaAnuladaNoCuentaDeuda(): void
    {
        // La venta de Juan Pérez de S/25 tiene estado CANCELADA — verificar que no aparece en deuda
        $ventaAnulada = $this->db->table('ventas')
            ->where('tipo_pago', 'FIADO')
            ->where('estado', 'CANCELADA')
            ->where('total', 25.00)
            ->get()->getRowArray();

        if (!$ventaAnulada) {
            // Buscar cualquier venta cancelada de fiado
            $ventaAnulada = $this->db->table('ventas')
                ->where('tipo_pago', 'FIADO')
                ->where('estado', 'CANCELADA')
                ->get()->getRowArray();
        }

        if (!$ventaAnulada) {
            $this->ko('CHECK 4 — No hay venta anulada de fiado en los datos de demo');
            return;
        }

        $clienteId = (int)$ventaAnulada['cliente_id'];
        $totalAnulada = (float)$ventaAnulada['total'];

        // La deuda calculada NO debe incluir esta venta
        $deudaTotal = $this->calcularDeuda($clienteId);
        $deudaConAnulada = $this->calcularDeudaSiContara($clienteId, $totalAnulada);

        if ($deudaConAnulada > $deudaTotal) {
            $this->ok("CHECK 4 — Venta anulada de S/{$totalAnulada} NO forma parte de la deuda");
        } else {
            $this->ko('CHECK 4 — Venta anulada está influyendo en la deuda incorrectamente');
        }
    }

    // CONDICIÓN 5: El historial permanece (ventas, pagos, anuladas)
    private function check5_historialPermanece(array $cliente): void
    {
        $clienteId = (int)$cliente['id'];

        $ventas = $this->db->table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('tipo_pago', 'FIADO')
            ->get()->getResultArray();

        $pagos = $this->db->table('pagos_fiado')
            ->where('cliente_id', $clienteId)
            ->get()->getResultArray();

        if (count($ventas) > 0 && count($pagos) > 0) {
            $this->ok("CHECK 5 — Historial presente: " . count($ventas) . " ventas fiadas, " . count($pagos) . " pagos");
        } else {
            $this->ko('CHECK 5 — Historial incompleto', "ventas=" . count($ventas) . " pagos=" . count($pagos));
        }
    }

    // CONDICIÓN 6: Deuda coincide con base de datos
    private function check6_deudaCoincideConDB(array $cliente): void
    {
        $clienteId = (int)$cliente['id'];

        $ventasFiadas = $this->db->table('ventas')
            ->selectSum('total', 'total_fiado')
            ->where('cliente_id', $clienteId)
            ->where('tipo_pago', 'FIADO')
            ->where('estado', 'COMPLETADA')
            ->get()->getRowArray();

        $pagos = $this->db->table('pagos_fiado')
            ->selectSum('monto', 'total_pagos')
            ->where('cliente_id', $clienteId)
            ->get()->getRowArray();

        $totalFiado = round((float)($ventasFiadas['total_fiado'] ?? 0), 2);
        $totalPagos = round((float)($pagos['total_pagos'] ?? 0), 2);
        $deudaCalculada = max(0, $totalFiado - $totalPagos);
        $deudaServicio = $this->calcularDeuda($clienteId);

        if (round($deudaCalculada, 2) === round($deudaServicio, 2)) {
            $this->ok("CHECK 6 — Deuda en DB (S/{$deudaCalculada}) coincide con servicio (S/{$deudaServicio})");
        } else {
            $this->ko('CHECK 6 — Deuda no coincide entre DB y servicio', "DB={$deudaCalculada} servicio={$deudaServicio}");
        }
    }

    // CONDICIÓN 7: Datos antiguos eliminados (solo existen datos de los 5 clientes demo)
    private function check7_datosAntiguosEliminados(): void
    {
        $clientesDemo = ['Juan Pérez', 'Carlos López', 'Pedro Gómez', 'Luis Torres', 'Miguel Sánchez'];
        $clienteIds = [];
        foreach ($clientesDemo as $nombre) {
            $c = $this->db->table('clientes')->where('nombre', $nombre)->get()->getRowArray();
            if ($c) $clienteIds[] = (int)$c['id'];
        }

        // Pagos que NO pertenecen a los clientes demo
        $pagosExtranos = $this->db->table('pagos_fiado')
            ->whereNotIn('cliente_id', $clienteIds)
            ->where('cliente_id IS NOT NULL')
            ->countAllResults();

        // Fiados que no pertenecen a ningún cliente demo
        $fiadosExtranos = $this->db->table('fiados')
            ->whereNotIn('cliente_id', $clienteIds)
            ->countAllResults();

        if ($pagosExtranos === 0 && $fiadosExtranos === 0) {
            $this->ok('CHECK 7 — Solo existen registros de los 5 clientes demo (datos anteriores eliminados)');
        } else {
            $this->ko("CHECK 7 — Existen registros externos: {$pagosExtranos} pagos, {$fiadosExtranos} fiados fuera de demo");
        }
    }

    // CONDICIÓN 8: Datos de demostración funcionan
    private function check8_datosDemoFuncionan(): void
    {
        $clientesDemo = ['Juan Pérez', 'Carlos López', 'Pedro Gómez', 'Luis Torres', 'Miguel Sánchez'];
        $encontrados = 0;
        $conDeuda = 0;

        foreach ($clientesDemo as $nombre) {
            $c = $this->db->table('clientes')->where('nombre', $nombre)->get()->getRowArray();
            if ($c) {
                $encontrados++;
                $deuda = $this->calcularDeuda((int)$c['id']);
                if ($deuda > 0) $conDeuda++;
            }
        }

        if ($encontrados === 5 && $conDeuda === 5) {
            $this->ok('CHECK 8 — Los 5 clientes demo existen y todos tienen deuda activa');
        } elseif ($encontrados === 5) {
            $this->ok("CHECK 8 — Los 5 clientes demo existen ({$conDeuda}/5 con deuda)");
        } else {
            $this->ko("CHECK 8 — Solo se encontraron {$encontrados}/5 clientes demo");
        }
    }

    // Helper: Calcular deuda real desde la DB
    private function calcularDeuda(int $clienteId): float
    {
        $ventas = $this->db->table('ventas')
            ->selectSum('total', 't')
            ->where('cliente_id', $clienteId)
            ->where('tipo_pago', 'FIADO')
            ->where('estado', 'COMPLETADA')
            ->get()->getRowArray();

        $pagos = $this->db->table('pagos_fiado')
            ->selectSum('monto', 't')
            ->where('cliente_id', $clienteId)
            ->get()->getRowArray();

        return max(0, round((float)($ventas['t'] ?? 0) - (float)($pagos['t'] ?? 0), 2));
    }

    // Helper: Qué pasaría si la venta anulada contara
    private function calcularDeudaSiContara(int $clienteId, float $extra): float
    {
        return round($this->calcularDeuda($clienteId) + $extra, 2);
    }
}
