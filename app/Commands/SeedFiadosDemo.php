<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SeedFiadosDemo extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'seed:fiados';
    protected $description = 'Prepara el esquema simplificado y los 5 clientes de demostración para el Módulo de Fiados.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write('Preparando esquema de base de datos para Fiados simplificado...', 'yellow');

        // 1. Asegurar columna cliente_id en pagos_fiado y fiado_id NULLable
        $fields = $db->getFieldNames('pagos_fiado');
        if (!in_array('cliente_id', $fields, true)) {
            $db->query("ALTER TABLE pagos_fiado ADD COLUMN cliente_id INT NULL AFTER id");
            CLI::write('Columna cliente_id agregada a pagos_fiado.', 'green');
        }

        // Hacer fiado_id NULLable en pagos_fiado si es necesario
        $db->query("ALTER TABLE pagos_fiado MODIFY COLUMN fiado_id INT NULL");

        // 2. Limpiar datos antiguos de fiados y pagos_fiado
        CLI::write('Limpiando datos antiguos de fiados y pagos_fiado...', 'yellow');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('pagos_fiado')->truncate();
        $db->table('fiados')->truncate();
        $db->table('ventas')->where('tipo_pago', 'FIADO')->delete();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');


        // 3. Definir los 5 clientes de demostración
        $clientesDemo = [
            [
                'nombre' => 'Juan Pérez',
                'telefono' => '912345678',
                'limite_credito' => 300.00,
                'target_deuda' => 100.00,
                'ventas' => [
                    ['monto' => 50.00, 'estado' => 'COMPLETADA', 'mins_ago' => 120],
                    ['monto' => 30.00, 'estado' => 'COMPLETADA', 'mins_ago' => 90],
                    ['monto' => 25.00, 'estado' => 'CANCELADA',  'mins_ago' => 60], // Venta anulada (no cuenta)
                    ['monto' => 40.00, 'estado' => 'COMPLETADA', 'mins_ago' => 45],
                ],
                'pagos' => [
                    ['monto' => 20.00, 'tipo' => 'EFECTIVO', 'obs' => 'Pago parcial en efectivo', 'mins_ago' => 30],
                ]
            ],
            [
                'nombre' => 'Carlos López',
                'telefono' => '923456789',
                'limite_credito' => 200.00,
                'target_deuda' => 45.00,
                'ventas' => [
                    ['monto' => 60.00, 'estado' => 'COMPLETADA', 'mins_ago' => 100],
                ],
                'pagos' => [
                    ['monto' => 15.00, 'tipo' => 'YAPE', 'obs' => 'Abono Yape', 'mins_ago' => 40],
                ]
            ],
            [
                'nombre' => 'Pedro Gómez',
                'telefono' => '934567890',
                'limite_credito' => 250.00,
                'target_deuda' => 80.00,
                'ventas' => [
                    ['monto' => 100.00, 'estado' => 'COMPLETADA', 'mins_ago' => 110],
                ],
                'pagos' => [
                    ['monto' => 20.00, 'tipo' => 'PLIN', 'obs' => 'Abono Plin', 'mins_ago' => 50],
                ]
            ],
            [
                'nombre' => 'Luis Torres',
                'telefono' => '945678901',
                'limite_credito' => 150.00,
                'target_deuda' => 30.00,
                'ventas' => [
                    ['monto' => 30.00, 'estado' => 'COMPLETADA', 'mins_ago' => 80],
                ],
                'pagos' => []
            ],
            [
                'nombre' => 'Miguel Sánchez',
                'telefono' => '956789012',
                'limite_credito' => 200.00,
                'target_deuda' => 60.00,
                'ventas' => [
                    ['monto' => 80.00, 'estado' => 'COMPLETADA', 'mins_ago' => 95],
                ],
                'pagos' => [
                    ['monto' => 20.00, 'tipo' => 'TARJETA', 'obs' => 'Abono con tarjeta', 'mins_ago' => 35],
                ]
            ]
        ];

        CLI::write('Creando los 5 clientes y sus datos de prueba...', 'yellow');

        foreach ($clientesDemo as $cData) {
            // Verificar si el cliente existe o crearlo
            $existing = $db->table('clientes')->where('nombre', $cData['nombre'])->get()->getRowArray();
            if ($existing) {
                $clienteId = (int)$existing['id'];
                $db->table('clientes')->where('id', $clienteId)->update([
                    'telefono' => $cData['telefono'],
                    'limite_credito' => $cData['limite_credito'],
                    'estado' => 'ACTIVO'
                ]);
            } else {
                $db->table('clientes')->insert([
                    'nombre' => $cData['nombre'],
                    'telefono' => $cData['telefono'],
                    'limite_credito' => $cData['limite_credito'],
                    'estado' => 'ACTIVO',
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                ]);
                $clienteId = (int)$db->insertID();
            }

            // Insertar Ventas
            foreach ($cData['ventas'] as $v) {
                $fechaVenta = date('Y-m-d H:i:s', time() - ($v['mins_ago'] * 60));
                $db->table('ventas')->insert([
                    'usuario_id' => 1,
                    'mesero_id' => 1,
                    'cliente_id' => $clienteId,
                    'subtotal' => $v['monto'],
                    'descuento' => 0.00,
                    'total' => $v['monto'],
                    'tipo_pago' => 'FIADO',
                    'estado' => $v['estado'],
                    'fecha_venta' => $fechaVenta,
                ]);
                $ventaId = (int)$db->insertID();

                // Registro simple en fiados
                $db->table('fiados')->insert([
                    'cliente_id' => $clienteId,
                    'venta_id' => $ventaId,
                    'monto' => $v['monto'],
                    'saldo' => ($v['estado'] === 'CANCELADA') ? 0.00 : $v['monto'],
                    'estado' => ($v['estado'] === 'CANCELADA') ? 'CANCELADO' : 'PENDIENTE',
                    'fecha' => $fechaVenta,
                ]);
            }

            // Insertar Pagos
            foreach ($cData['pagos'] as $p) {
                $fechaPago = date('Y-m-d H:i:s', time() - ($p['mins_ago'] * 60));
                $db->table('pagos_fiado')->insert([
                    'cliente_id' => $clienteId,
                    'usuario_id' => 1,
                    'monto' => $p['monto'],
                    'tipo_pago' => $p['tipo'],
                    'observacion' => $p['obs'],
                    'fecha' => $fechaPago,
                ]);
            }

            CLI::write("  ✓ Cliente '{$cData['nombre']}' configurado. Target Deuda: S/ {$cData['target_deuda']}", 'green');
        }

        CLI::write('');
        CLI::write('✅ Seeder completado con éxito. 5 clientes cargados con simulación limpia.', 'green');
    }
}
