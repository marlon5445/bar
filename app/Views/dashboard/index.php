<?= $this->extend('layout/app_layout'); ?>

<?= $this->section('content'); ?>

<!-- Encabezado con Bienvenida Contextual -->
<div style="margin-bottom: 1.75rem;">
    <?php
        $hora = (int)date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos días' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
    ?>
    <h2 style="font-size: 1.6rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
        <?= $saludo; ?>, <span style="color: var(--accent);"><?= esc(session()->get('nombre')); ?></span> 👋
    </h2>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Resumen operativo y métricas de rendimiento en tiempo real para <strong>BAR MANAGER</strong>.
    </p>
</div>

<!-- 4 TARJETAS PRINCIPALES DE MÉTRICAS -->
<div class="grid-4">
    <!-- Ventas de Hoy -->
    <div class="card">
        <div class="metric-header">
            <span class="metric-label">Ventas de Hoy</span>
            <div class="metric-icon">💰</div>
        </div>
        <div class="metric-value">S/ <?= number_format($totalVentasHoy, 2); ?></div>
        <div class="metric-subtitle">
            <strong><?= $cantVentasHoy; ?></strong> operaciones completadas hoy
        </div>
    </div>

    <!-- Clientes / Ventas -->
    <div class="card">
        <div class="metric-header">
            <span class="metric-label">Transacciones</span>
            <div class="metric-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">🛍️</div>
        </div>
        <div class="metric-value"><?= $cantVentasHoy; ?></div>
        <div class="metric-subtitle">
            Atenciones registradas en caja
        </div>
    </div>

    <!-- Ticket Promedio -->
    <div class="card">
        <div class="metric-header">
            <span class="metric-label">Ticket Promedio</span>
            <div class="metric-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">📈</div>
        </div>
        <div class="metric-value">S/ <?= number_format($ticketPromedio, 2); ?></div>
        <div class="metric-subtitle">
            Promedio de consumo por venta
        </div>
    </div>

    <!-- Fiado Pendiente -->
    <div class="card">
        <div class="metric-header">
            <span class="metric-label">Fiado Pendiente</span>
            <div class="metric-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">💳</div>
        </div>
        <div class="metric-value" style="color: var(--danger);">S/ <?= number_format($totalFiado, 2); ?></div>
        <div class="metric-subtitle">
            <strong><?= $clientesDeuda; ?></strong> cliente(s) con saldo pendiente
        </div>
    </div>
</div>

<!-- SECCIÓN INTERMEDIA: GRÁFICO + PRODUCTOS MÁS VENDIDOS -->
<div class="grid-dashboard-mid">
    <!-- Gráfico de Ventas de 7 Días -->
    <div class="card">
        <div class="card-title">
            <span>📊 Ventas de los Últimos 7 Días</span>
            <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">Soles (S/)</span>
        </div>
        <div style="position: relative; height: 280px; width: 100%;">
            <canvas id="chartVentas7Dias"></canvas>
        </div>
    </div>

    <!-- Productos Más Vendidos 🔥 -->
    <div class="card card-ranking">
        <div class="card-title">
            <span>🔥 Productos Más Vendidos</span>
        </div>
        <ul class="ranking-list">
            <?php if (!empty($topProductos)): ?>
                <?php foreach ($topProductos as $idx => $prod): ?>
                    <li class="ranking-item">
                        <div class="ranking-info">
                            <span class="ranking-num"><?= $idx + 1; ?></span>
                            <div class="ranking-text">
                                <div class="ranking-name" title="<?= esc($prod['nombre']); ?>"><?= esc($prod['nombre']); ?></div>
                                <div class="ranking-meta"><?= $prod['total_vendido']; ?> un. vendidas</div>
                            </div>
                        </div>
                        <div class="ranking-amount">S/ <?= number_format($prod['total_monto'], 2); ?></div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="color: var(--text-muted); font-size: 0.85rem; padding: 1rem 0; text-align: center;">Sin ventas aún</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- SECCIÓN INFERIOR: VENTAS POR MESERO, STOCK CRÍTICO Y ESTIMACIÓN -->
<div class="grid-3">
    <!-- Ventas por Mesero 👨‍🍳 -->
    <div class="card">
        <div class="card-title">
            <span>👨‍🍳 Ventas por Mesero</span>
        </div>
        <ul class="ranking-list">
            <?php if (!empty($ventasMeseros)): ?>
                <?php foreach ($ventasMeseros as $vm): ?>
                    <li class="ranking-item">
                        <div class="ranking-info">
                            <span style="font-size: 1.1rem; flex-shrink: 0;">
                                <?= ($vm['mesero_nombre'] === '🏪 Venta en barra') ? '🏪' : '👤'; ?>
                            </span>
                            <div class="ranking-text">
                                <div class="ranking-name" title="<?= esc($vm['mesero_nombre']); ?>" style="<?= ($vm['mesero_nombre'] === '🏪 Venta en barra') ? 'color: var(--accent); font-weight:700;' : ''; ?>">
                                    <?= esc($vm['mesero_nombre']); ?>
                                </div>
                                <div class="ranking-meta"><?= $vm['num_ventas']; ?> venta(s)</div>
                            </div>
                        </div>
                        <div class="ranking-amount">S/ <?= number_format($vm['total_monto'], 2); ?></div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="color: var(--text-muted); font-size: 0.85rem; padding: 1rem 0; text-align: center;">Sin registro de atenciones</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Stock Crítico 📦 -->
    <div class="card">
        <div class="card-title">
            <span>📦 Stock Crítico</span>
        </div>
        <?php if (!empty($stockCritico)): ?>
            <ul class="ranking-list">
                <?php foreach ($stockCritico as $sc): ?>
                    <li class="ranking-item">
                        <div class="ranking-text">
                            <div class="ranking-name" title="<?= esc($sc['nombre']); ?>"><?= esc($sc['nombre']); ?></div>
                            <div class="ranking-meta">Stock: <strong><?= $sc['stock_actual']; ?></strong> / Mínimo: <?= $sc['stock_minimo']; ?></div>
                        </div>
                        <div style="flex-shrink: 0;">
                            <span class="badge-stock <?= $sc['estado_stock'] === 'CRITICO' ? 'badge-critico' : 'badge-bajo'; ?>">
                                <?= $sc['estado_stock'] === 'CRITICO' ? '🔴' : '🟡'; ?> <?= esc($sc['estado_stock'] === 'CRITICO' ? 'Crítico' : 'Bajo'); ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div style="text-align: center; padding: 1.5rem 0; color: var(--success);">
                <div style="font-size: 1.75rem; margin-bottom: 0.3rem;">🟢</div>
                <strong style="font-size: 0.9rem;">Inventario Saludable</strong>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Todos los productos cuentan con stock suficiente.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tarjeta Demostrativa: Próximo Sábado 🔥 -->
    <div class="card" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(234, 88, 12, 0.15) 100%); border: 1px solid rgba(245, 158, 11, 0.3);">
        <div class="card-title">
            <span style="color: var(--accent);">🔥 PRÓXIMO SÁBADO</span>
            <span style="font-size: 0.7rem; font-weight: 700; background: var(--accent); color: #000; padding: 0.2rem 0.5rem; border-radius: 10px;">DEMO</span>
        </div>
        <div style="margin-bottom: 1rem;">
            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Proyección Estimada</div>
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin-top: 0.2rem;">⚡ Alta Demanda Esperada</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem;">
            <div style="background: var(--bg-card); padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Ventas estimadas</div>
                <div style="font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--accent);">S/ 2,100 - 2,600</div>
            </div>
            <div style="background: var(--bg-card); padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Clientes estimados</div>
                <div style="font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--info);">110 - 140</div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>


<?= $this->section('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chartVentas7Dias');
    if (!ctx) return;

    const labels = <?= $graficoLabels; ?>;
    const dataValues = <?= $graficoValores; ?>;

    let chartInstance = null;

    function renderChart() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        
        const textColor = isLight ? '#475569' : '#94a3b8';
        const gridColor = isLight ? '#e2e8f0' : 'rgba(255, 255, 255, 0.08)';
        const accentColor = isLight ? '#d97706' : '#f59e0b';
        const gradientBg = isLight ? 'rgba(217, 119, 6, 0.15)' : 'rgba(245, 158, 11, 0.2)';

        if (chartInstance) {
            chartInstance.destroy();
        }

        const chartCtx = ctx.getContext('2d');
        const gradient = chartCtx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, gradientBg);
        gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas (S/)',
                    data: dataValues,
                    borderColor: accentColor,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: accentColor,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return ' Ventas: S/ ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'Outfit' } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { 
                            color: textColor, 
                            font: { family: 'Outfit' },
                            callback: function(value) { return 'S/ ' + value; }
                        }
                    }
                }
            }
        });
    }

    renderChart();

    // Actualizar gráfico en tiempo real al alternar entre Tema Claro ☀️ y Tema Oscuro 🌙
    window.addEventListener('themeChanged', function () {
        renderChart();
    });
});
</script>
<?= $this->endSection(); ?>
