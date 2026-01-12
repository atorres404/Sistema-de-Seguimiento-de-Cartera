<?php
session_start();

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    echo '
        <script>
            alert("Acceso denegado. Debes iniciar sesión.");
            window.location = "index.php";
        </script>
    ';
    exit();
}

include 'php/conexion_be.php';

// ==================== CONSULTAS AVANZADAS ====================

// 1. Estadísticas Generales
$queryEstadisticas = "SELECT 
    COUNT(*) AS total_contratos,
    SUM(importe_ministrado) AS total_importe,
    SUM(saldo) AS total_saldo,
    SUM(intereses) AS total_intereses,
    MAX(ultima_actualizacion) AS ultima_actualizacion,
    SUM(CASE WHEN saldo > 0 THEN 1 ELSE 0 END) AS creditos_vigentes,
    SUM(CASE WHEN saldo = 0 THEN 1 ELSE 0 END) AS creditos_liquidados
FROM contratos";
$resultEstadisticas = mysqli_query($conexion, $queryEstadisticas);
$estadisticas = mysqli_fetch_assoc($resultEstadisticas);

// 2. Cartera Vencida (más de 90 días)
$queryCarteraVencida = "SELECT 
    empresa,
    COUNT(*) AS total_vencidos,
    SUM(saldo) AS monto_vencido,
    SUM(intereses) AS intereses_vencidos
FROM contratos
WHERE vencimiento < DATE_SUB(NOW(), INTERVAL 90 DAY) AND saldo > 0
GROUP BY empresa";
$resultCarteraVencida = mysqli_query($conexion, $queryCarteraVencida);
$carteraVencida = [];
while ($row = mysqli_fetch_assoc($resultCarteraVencida)) {
    $carteraVencida[$row['empresa']] = $row;
}

// 3. Análisis de Morosidad por Rangos
$queryMorosidad = "SELECT 
    CASE 
        WHEN DATEDIFF(NOW(), vencimiento) <= 30 THEN '0-30 días'
        WHEN DATEDIFF(NOW(), vencimiento) <= 60 THEN '31-60 días'
        WHEN DATEDIFF(NOW(), vencimiento) <= 90 THEN '61-90 días'
        WHEN DATEDIFF(NOW(), vencimiento) <= 180 THEN '91-180 días'
        ELSE 'Más de 180 días'
    END AS rango_morosidad,
    COUNT(*) AS cantidad,
    SUM(saldo) AS monto
FROM contratos
WHERE vencimiento < NOW() AND saldo > 0
GROUP BY rango_morosidad
ORDER BY FIELD(rango_morosidad, '0-30 días', '31-60 días', '61-90 días', '91-180 días', 'Más de 180 días')";
$resultMorosidad = mysqli_query($conexion, $queryMorosidad);
$datosMorosidad = [];
while ($row = mysqli_fetch_assoc($resultMorosidad)) {
    $datosMorosidad[] = $row;
}

// 4. Top 10 Clientes con Mayor Saldo
$queryTopClientes = "SELECT 
    razon_social,
    empresa,
    SUM(saldo) AS saldo_total,
    SUM(intereses) AS intereses_total,
    COUNT(*) AS num_contratos
FROM contratos
WHERE saldo > 0
GROUP BY razon_social, empresa
ORDER BY saldo_total DESC
LIMIT 10";
$resultTopClientes = mysqli_query($conexion, $queryTopClientes);
$topClientes = [];
while ($row = mysqli_fetch_assoc($resultTopClientes)) {
    $topClientes[] = $row;
}

// 5. Proyección de Vencimientos (próximos 6 meses)
$queryProyeccion = "SELECT 
    DATE_FORMAT(vencimiento, '%Y-%m') AS mes,
    COUNT(*) AS cantidad_contratos,
    SUM(saldo) AS monto_vencer
FROM contratos
WHERE vencimiento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 6 MONTH)
    AND saldo > 0
GROUP BY mes
ORDER BY mes";
$resultProyeccion = mysqli_query($conexion, $queryProyeccion);
$proyeccionVencimientos = [];
while ($row = mysqli_fetch_assoc($resultProyeccion)) {
    $proyeccionVencimientos[] = $row;
}

// 6. Comparativa por Año Fiscal y Empresa - CORREGIDA
$queryComparativa = "SELECT 
    empresa,
    CASE 
        WHEN MONTH(vencimiento) >= 4 THEN YEAR(vencimiento)
        ELSE YEAR(vencimiento) - 1
    END AS anio_fiscal,
    SUM(importe_ministrado) AS importe_ministrado,
    SUM(saldo) AS saldo_vigente,
    COUNT(*) AS num_contratos
FROM contratos
WHERE vencimiento IS NOT NULL
GROUP BY empresa, anio_fiscal
HAVING anio_fiscal IS NOT NULL
ORDER BY empresa, anio_fiscal";
$resultComparativa = mysqli_query($conexion, $queryComparativa);
$datosComparativa = [];
while ($row = mysqli_fetch_assoc($resultComparativa)) {
    $empresa = $row['empresa'];
    $anio = $row['anio_fiscal'];
    $datosComparativa[$empresa][$anio] = $row;
}

// 7. Estadísticas por Empresa
$queryEstadisticasEmpresas = "SELECT 
    empresa,
    COUNT(*) AS total_creditos,
    SUM(CASE WHEN saldo > 0 THEN 1 ELSE 0 END) AS creditos_vigentes,
    SUM(importe_ministrado) AS importe_ministrado,
    SUM(saldo) AS saldo_por_recuperar,
    SUM(CASE WHEN vencimiento < NOW() AND saldo > 0 THEN saldo ELSE 0 END) AS saldo_vencido,
    SUM(intereses) AS intereses_acumulados,
    AVG(saldo) AS promedio_saldo,
    MAX(saldo) AS saldo_maximo,
    MIN(CASE WHEN saldo > 0 THEN saldo END) AS saldo_minimo
FROM contratos
GROUP BY empresa";
$resultEstadisticasEmpresas = mysqli_query($conexion, $queryEstadisticasEmpresas);
$estadisticasEmpresas = [];
while ($row = mysqli_fetch_assoc($resultEstadisticasEmpresas)) {
    $empresa = $row['empresa'];
    $importe = $row['importe_ministrado'];
    $saldo = $row['saldo_por_recuperar'];
    $saldoVencido = $row['saldo_vencido'];
    
    // Calcular KPIs
    $tasaRecuperacion = $importe > 0 ? (($importe - $saldo) / $importe) * 100 : 0;
    $indiceMorosidad = $saldo > 0 ? ($saldoVencido / $saldo) * 100 : 0;
    
    $estadisticasEmpresas[$empresa] = array_merge($row, [
        'tasa_recuperacion' => $tasaRecuperacion,
        'indice_morosidad' => $indiceMorosidad,
        'monto_recuperado' => $importe - $saldo
    ]);
}

// Calcular Totales Globales
$totalImporteMinistrado = $estadisticas['total_importe'] ?? 0;
$totalSaldoPorRecuperar = $estadisticas['total_saldo'] ?? 0;
$totalRecuperado = $totalImporteMinistrado - $totalSaldoPorRecuperar;
$tasaRecuperacionGlobal = $totalImporteMinistrado > 0 ? ($totalRecuperado / $totalImporteMinistrado) * 100 : 0;

// Formatear datos
$ultimaActualizacion = $estadisticas['ultima_actualizacion'] 
    ? date('d-m-Y H:i:s', strtotime($estadisticas['ultima_actualizacion']))
    : 'No disponible';

// Convertir datos a JSON para JavaScript (con opciones para evitar problemas)
$jsonMorosidad = json_encode($datosMorosidad, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$jsonProyeccion = json_encode($proyeccionVencimientos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$jsonComparativa = json_encode($datosComparativa, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$jsonEstadisticasEmpresas = json_encode($estadisticasEmpresas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financiero - Sistema de Recuperaciones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js - Versión estable -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 40px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #2596be 0%, #1a7a9c 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        header .logo-section img {
            height: 80px;
        }

        header h1 {
            color: #fff;
            font-size: 1.8em;
            font-weight: 600;
        }

        .menu {
            display: flex;
            gap: 15px;
        }

        .menu a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* KPI Cards - Mejoradas */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #2596be, #667eea);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }

        .kpi-icon.blue { background: linear-gradient(135deg, #2596be, #1a7a9c); }
        .kpi-icon.purple { background: linear-gradient(135deg, #667eea, #764ba2); }
        .kpi-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .kpi-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .kpi-icon.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .kpi-icon.indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }

        .kpi-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85em;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .kpi-trend.up {
            background: #dcfce7;
            color: #166534;
        }

        .kpi-trend.down {
            background: #fee2e2;
            color: #991b1b;
        }

        .kpi-title {
            font-size: 0.95em;
            color: #666;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .kpi-value {
            font-size: 2em;
            font-weight: 700;
            color: #2596be;
            margin-bottom: 10px;
        }

        .kpi-subtitle {
            font-size: 0.85em;
            color: #999;
        }

        .kpi-progress {
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .kpi-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2596be, #667eea);
            border-radius: 10px;
            transition: width 1s ease;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .chart-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Full Width Charts */
        .chart-card.full-width {
            grid-column: 1 / -1;
        }

        /* Table Styles */
        .table-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .table-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #2596be 0%, #1a7a9c 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        tbody tr:hover {
            background-color: #f3f4f6;
        }

        /* Company Badge */
        .company-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-dispersora {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-sofom {
            background: #d1fae5;
            color: #065f46;
        }

        /* Alert Box */
        .alert-box {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .alert-box h4 {
            color: #92400e;
            font-size: 1.1em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-box p {
            color: #78350f;
            font-size: 0.95em;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 300px;
            }

            table {
                font-size: 0.85em;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .kpi-card, .chart-card, .table-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* No Data Message */
        .no-data {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 40px;
        }

        .no-data i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <a href="inicio.php">
                <img src="assets/images/logo.png" alt="Logo">
            </a>
            <h1><i class="fas fa-chart-line"></i> Dashboard Financiero</h1>
        </div>
        <nav class="menu">
            <a href="inicio.php">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="ver_datos.php">
                <i class="fas fa-file-alt"></i> Reportes
            </a>
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <a href="subir_excel.php">
                <i class="fas fa-upload"></i> Subir Datos
            </a>
            <?php endif; ?>
            <a href="php/cerrar_sesion.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </header>

    <div class="container">
        <!-- Alert de Cartera Vencida -->
        <?php 
        $totalVencido = array_sum(array_column($carteraVencida, 'monto_vencido'));
        if ($totalVencido > 0): 
        ?>
        <div class="alert-box">
            <h4><i class="fas fa-exclamation-triangle"></i> Alerta de Cartera Vencida</h4>
            <p>Hay <strong>$<?php echo number_format($totalVencido, 2); ?></strong> en cartera vencida (más de 90 días). Se requiere acción inmediata.</p>
        </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <!-- Total Cartera -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon blue">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="kpi-trend up">
                        <i class="fas fa-arrow-up"></i> Activo
                    </div>
                </div>
                <div class="kpi-title">Cartera Total</div>
                <div class="kpi-value">$<?php echo number_format($totalImporteMinistrado, 0); ?></div>
                <div class="kpi-subtitle"><?php echo $estadisticas['total_contratos']; ?> contratos colocados</div>
                <div class="kpi-progress">
                    <div class="kpi-progress-bar" style="width: 100%"></div>
                </div>
            </div>

            <!-- Saldo por Recuperar -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon purple">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="kpi-trend down">
                        <i class="fas fa-arrow-down"></i> <?php echo number_format(100 - $tasaRecuperacionGlobal, 1); ?>%
                    </div>
                </div>
                <div class="kpi-title">Por Recuperar</div>
                <div class="kpi-value">$<?php echo number_format($totalSaldoPorRecuperar, 0); ?></div>
                <div class="kpi-subtitle"><?php echo $estadisticas['creditos_vigentes']; ?> créditos vigentes</div>
                <div class="kpi-progress">
                    <div class="kpi-progress-bar" style="width: <?php echo 100 - $tasaRecuperacionGlobal; ?>%"></div>
                </div>
            </div>

            <!-- Tasa de Recuperación -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon green">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="kpi-trend up">
                        <i class="fas fa-check"></i> Saludable
                    </div>
                </div>
                <div class="kpi-title">Tasa de Recuperación</div>
                <div class="kpi-value"><?php echo number_format($tasaRecuperacionGlobal, 1); ?>%</div>
                <div class="kpi-subtitle">$<?php echo number_format($totalRecuperado, 0); ?> recuperado</div>
                <div class="kpi-progress">
                    <div class="kpi-progress-bar" style="width: <?php echo $tasaRecuperacionGlobal; ?>%"></div>
                </div>
            </div>

            <!-- Cartera Vencida -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon red">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <?php 
                    $indiceMorosidadGlobal = $totalSaldoPorRecuperar > 0 ? ($totalVencido / $totalSaldoPorRecuperar) * 100 : 0;
                    ?>
                    <div class="kpi-trend <?php echo $indiceMorosidadGlobal > 10 ? 'down' : 'up'; ?>">
                        <i class="fas fa-<?php echo $indiceMorosidadGlobal > 10 ? 'exclamation' : 'check'; ?>"></i> 
                        <?php echo number_format($indiceMorosidadGlobal, 1); ?>%
                    </div>
                </div>
                <div class="kpi-title">Cartera Vencida (+90 días)</div>
                <div class="kpi-value">$<?php echo number_format($totalVencido, 0); ?></div>
                <div class="kpi-subtitle">Índice de morosidad</div>
                <div class="kpi-progress">
                    <div class="kpi-progress-bar" style="width: <?php echo min($indiceMorosidadGlobal, 100); ?>%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div>
                </div>
            </div>

            <!-- Intereses -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon yellow">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="kpi-title">Intereses Acumulados</div>
                <div class="kpi-value">$<?php echo number_format($estadisticas['total_intereses'], 0); ?></div>
                <div class="kpi-subtitle">Pendientes de cobro</div>
            </div>

            <!-- Créditos Liquidados -->
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon indigo">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
                <div class="kpi-title">Créditos Liquidados</div>
                <div class="kpi-value"><?php echo $estadisticas['creditos_liquidados']; ?></div>
                <div class="kpi-subtitle">
                    <?php 
                    $porcentajeLiquidados = $estadisticas['total_contratos'] > 0 
                        ? ($estadisticas['creditos_liquidados'] / $estadisticas['total_contratos']) * 100 
                        : 0;
                    echo number_format($porcentajeLiquidados, 1); 
                    ?>% del total
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Análisis de Morosidad -->
            <div class="chart-card" 
                 data-chart="morosidad"
                 data-json='<?php echo htmlspecialchars($jsonMorosidad, ENT_QUOTES, 'UTF-8'); ?>'>
                <h3><i class="fas fa-chart-bar"></i> Análisis de Morosidad por Rangos</h3>
                <div class="chart-container">
                    <canvas id="chartMorosidad"></canvas>
                </div>
            </div>

            <!-- Comparativa por Empresa -->
            <div class="chart-card"
                 data-chart="empresas"
                 data-labels='<?php echo htmlspecialchars(json_encode(array_keys($estadisticasEmpresas)), ENT_QUOTES, 'UTF-8'); ?>'
                 data-saldos='<?php echo htmlspecialchars(json_encode(array_column($estadisticasEmpresas, 'saldo_por_recuperar')), ENT_QUOTES, 'UTF-8'); ?>'>
                <h3><i class="fas fa-building"></i> Saldo por Empresa</h3>
                <div class="chart-container">
                    <canvas id="chartEmpresas"></canvas>
                </div>
            </div>
        </div>

        <!-- Proyección de Vencimientos -->
        <div class="chart-card full-width"
             data-chart="proyeccion"
             data-json='<?php echo htmlspecialchars($jsonProyeccion, ENT_QUOTES, 'UTF-8'); ?>'>
            <h3><i class="fas fa-calendar-alt"></i> Proyección de Vencimientos (Próximos 6 Meses)</h3>
            <div class="chart-container">
                <canvas id="chartProyeccion"></canvas>
            </div>
        </div>

        <!-- Comparativa Histórica por Año Fiscal -->
        <div class="chart-card full-width"
             data-chart="historico"
             data-json='<?php echo htmlspecialchars($jsonComparativa, ENT_QUOTES, 'UTF-8'); ?>'>
            <h3><i class="fas fa-chart-line"></i> Evolución Histórica por Año Fiscal</h3>
            <div class="chart-container">
                <canvas id="chartHistorico"></canvas>
            </div>
        </div>

        <!-- Top 10 Clientes -->
        <div class="table-card">
            <h3><i class="fas fa-trophy"></i> Top 10 Clientes con Mayor Saldo</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Razón Social</th>
                        <th>Empresa</th>
                        <th>Contratos</th>
                        <th>Saldo Total</th>
                        <th>Intereses</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topClientes as $index => $cliente): ?>
                    <tr>
                        <td><strong><?php echo $index + 1; ?></strong></td>
                        <td><?php echo $cliente['razon_social']; ?></td>
                        <td>
                            <span class="company-badge badge-<?php echo $cliente['empresa'] == 'DISPERSORA_CREDITO' ? 'dispersora' : 'sofom'; ?>">
                                <?php echo $cliente['empresa'] == 'DISPERSORA_CREDITO' ? 'Dispersora' : 'SOFOM'; ?>
                            </span>
                        </td>
                        <td><?php echo $cliente['num_contratos']; ?></td>
                        <td>$<?php echo number_format($cliente['saldo_total'], 2); ?></td>
                        <td>$<?php echo number_format($cliente['intereses_total'], 2); ?></td>
                        <td><strong>$<?php echo number_format($cliente['saldo_total'] + $cliente['intereses_total'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Estadísticas por Empresa -->
        <div class="charts-grid">
            <?php foreach ($estadisticasEmpresas as $empresa => $stats): 
                $empresaId = str_replace('_', '', $empresa);
                $empresaNombre = $empresa == 'DISPERSORA_CREDITO' ? 'Dispersora de Crédito' : 'Financiera SOFOM';
            ?>
            <div class="chart-card" 
                 data-chart="empresa_<?php echo $empresaId; ?>"
                 data-json='<?php echo htmlspecialchars(json_encode($stats), ENT_QUOTES, 'UTF-8'); ?>'>
                <h3>
                    <i class="fas fa-chart-pie"></i> 
                    <?php echo $empresaNombre; ?>
                </h3>
                <div style="padding: 20px; background: #f9fafb; border-radius: 12px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Tasa de Recuperación</div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #10b981;">
                                <?php echo number_format($stats['tasa_recuperacion'], 1); ?>%
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Índice de Morosidad</div>
                            <div style="font-size: 1.5em; font-weight: 700; color: <?php echo $stats['indice_morosidad'] > 15 ? '#ef4444' : '#f59e0b'; ?>;">
                                <?php echo number_format($stats['indice_morosidad'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="chart<?php echo $empresaId; ?>"></canvas>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Última Actualización -->
        <div style="background: rgba(255, 255, 255, 0.98); padding: 20px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); text-align: center; margin-top: 30px;">
            <h3 style="color: #333; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="fas fa-clock"></i> Última Actualización
            </h3>
            <p style="color: #666; font-size: 1.1em; font-weight: 600;"><?php echo $ultimaActualizacion; ?></p>
        </div>
    </div>

    <!-- JavaScript para gráficas -->
    <script>
    console.log('═══════════════════════════════════════');
    console.log('🚀 INICIO DEL SCRIPT - VERSIÓN CORREGIDA');
    console.log('═══════════════════════════════════════');

    // Esperar a que Chart.js esté disponible
    function waitForChartJS() {
        return new Promise((resolve) => {
            if (typeof Chart !== 'undefined') {
                resolve();
            } else {
                const checkInterval = setInterval(() => {
                    if (typeof Chart !== 'undefined') {
                        clearInterval(checkInterval);
                        resolve();
                    }
                }, 100);
            }
        });
    }

    // Función para obtener datos de manera segura desde atributos data-*
    function getDatosDesdeElemento(selector, atributo) {
        const elemento = document.querySelector(selector);
        if (!elemento) {
            console.error('❌ Elemento no encontrado:', selector);
            return null;
        }
        
        const jsonString = elemento.getAttribute(atributo);
        if (!jsonString) {
            console.warn('⚠️ No hay datos en', atributo);
            return null;
        }
        
        try {
            return JSON.parse(jsonString);
        } catch (error) {
            console.error('❌ Error al parsear JSON:', error);
            return null;
        }
    }

    async function crearGraficas() {
        console.log('\n═══════════════════════════════════════');
        console.log('🎬 INICIANDO CREACIÓN DE GRÁFICAS');
        console.log('═══════════════════════════════════════\n');
        
        // Esperar a que Chart.js esté cargado
        await waitForChartJS();
        
        // Configuración global de Chart.js
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.plugins.legend.display = true;
        
        // ==================== GRÁFICA 1: MOROSIDAD ====================
        console.log('📊 [1/5] Creando gráfica de MOROSIDAD...');
        
        try {
            const cardMorosidad = document.querySelector('[data-chart="morosidad"]');
            if (!cardMorosidad) {
                console.error('❌ No se encontró el contenedor de morosidad');
                return;
            }
            
            const jsonData = cardMorosidad.getAttribute('data-json');
            const dataMorosidad = JSON.parse(jsonData);
            
            console.log('   → Datos morosidad:', dataMorosidad);
            
            const canvasMorosidad = document.getElementById('chartMorosidad');
            
            if (!canvasMorosidad) {
                console.error('   ❌ Canvas no encontrado');
                return;
            }
            
            if (!dataMorosidad || dataMorosidad.length === 0) {
                console.warn('   ⚠️ No hay datos de morosidad');
                canvasMorosidad.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-bar"></i><p>No hay datos de morosidad disponibles</p></div>';
                return;
            }
            
            new Chart(canvasMorosidad, {
                type: 'bar',
                data: {
                    labels: dataMorosidad.map(d => d.rango_morosidad),
                    datasets: [{
                        label: 'Monto Vencido',
                        data: dataMorosidad.map(d => parseFloat(d.monto) || 0),
                        backgroundColor: ['#FBB024', '#F59E0B', '#EA580C', '#EF4444', '#DC2626'],
                        borderWidth: 0,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Distribución por Rango de Días',
                            font: { size: 14 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-MX', {maximumFractionDigits: 0});
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('   ✅ Gráfica de morosidad creada exitosamente');
            
        } catch (error) {
            console.error('   ❌ ERROR en gráfica de morosidad:', error);
        }
        
        // ==================== GRÁFICA 2: EMPRESAS ====================
        console.log('\n📊 [2/5] Procesando gráfica de EMPRESAS...');
        
        try {
            const cardEmpresas = document.querySelector('[data-chart="empresas"]');
            if (!cardEmpresas) {
                console.error('❌ No se encontró el contenedor de empresas');
                return;
            }
            
            const labelsData = cardEmpresas.getAttribute('data-labels');
            const saldosData = cardEmpresas.getAttribute('data-saldos');
            
            const EMPRESAS_LABELS = JSON.parse(labelsData);
            const EMPRESAS_SALDOS = JSON.parse(saldosData);
            
            console.log('   → Labels:', EMPRESAS_LABELS);
            console.log('   → Saldos:', EMPRESAS_SALDOS);
            
            const canvasEmpresas = document.getElementById('chartEmpresas');
            console.log('   → Canvas encontrado:', canvasEmpresas !== null);
            
            if (!canvasEmpresas) {
                console.error('   ❌ Canvas chartEmpresas NO encontrado');
                return;
            }
            
            if (!EMPRESAS_LABELS || EMPRESAS_LABELS.length === 0) {
                console.warn('   ⚠️  No hay datos de empresas');
                canvasEmpresas.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-building"></i><p>No hay datos de empresas disponibles</p></div>';
                return;
            }
            
            const chartEmpresas = new Chart(canvasEmpresas, {
                type: 'doughnut',
                data: {
                    labels: EMPRESAS_LABELS.map(e => e === 'DISPERSORA_CREDITO' ? 'Dispersora' : 'SOFOM'),
                    datasets: [{
                        data: EMPRESAS_SALDOS.map(s => parseFloat(s) || 0),
                        backgroundColor: [
                            'rgba(37, 150, 190, 0.9)',
                            'rgba(145, 191, 128, 0.9)'
                        ],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: { padding: 20, font: { size: 14, weight: 'bold' } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: $${value.toLocaleString('es-MX')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('   ✅ Gráfica de empresas creada exitosamente');
            
        } catch (error) {
            console.error('   ❌ ERROR en gráfica de empresas:', error);
        }
        
        // ==================== GRÁFICA 3: PROYECCIÓN ====================
        console.log('\n📊 [3/5] Procesando gráfica de PROYECCIÓN...');
        
        try {
            const cardProyeccion = document.querySelector('[data-chart="proyeccion"]');
            if (!cardProyeccion) {
                console.error('❌ No se encontró el contenedor de proyección');
                return;
            }
            
            const jsonProyeccion = cardProyeccion.getAttribute('data-json');
            const DATOS_PROYECCION = JSON.parse(jsonProyeccion);
            
            console.log('   → Datos proyección:', DATOS_PROYECCION);
            
            const canvasProyeccion = document.getElementById('chartProyeccion');
            console.log('   → Canvas encontrado:', canvasProyeccion !== null);
            
            if (!canvasProyeccion) {
                console.error('   ❌ Canvas chartProyeccion NO encontrado');
                return;
            }
            
            if (!DATOS_PROYECCION || DATOS_PROYECCION.length === 0) {
                console.warn('   ⚠️  No hay datos de proyección');
                canvasProyeccion.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-calendar-alt"></i><p>No hay vencimientos en los próximos 6 meses</p></div>';
                return;
            }
            
            const meses = DATOS_PROYECCION.map(d => {
                const [year, month] = d.mes.split('-');
                const mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                return mesesNombres[parseInt(month) - 1] + ' ' + year;
            });
            
            const chartProyeccion = new Chart(canvasProyeccion, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Monto a Vencer',
                        data: DATOS_PROYECCION.map(d => parseFloat(d.monto_vencer) || 0),
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        borderColor: 'rgb(102, 126, 234)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-MX', {maximumFractionDigits: 0});
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('   ✅ Gráfica de proyección creada exitosamente');
            
        } catch (error) {
            console.error('   ❌ ERROR en gráfica de proyección:', error);
        }
        
        // ==================== GRÁFICA 4: HISTÓRICO ====================
        console.log('\n📊 [4/5] Procesando gráfica HISTÓRICA...');
        
        try {
            const cardHistorico = document.querySelector('[data-chart="historico"]');
            if (!cardHistorico) {
                console.error('❌ No se encontró el contenedor histórico');
                return;
            }
            
            const jsonHistorico = cardHistorico.getAttribute('data-json');
            const DATOS_COMPARATIVA = JSON.parse(jsonHistorico);
            
            console.log('   → Datos históricos RAW:', DATOS_COMPARATIVA);
            
            const canvasHistorico = document.getElementById('chartHistorico');
            console.log('   → Canvas encontrado:', canvasHistorico !== null);
            
            if (!canvasHistorico) {
                console.error('   ❌ Canvas chartHistorico NO encontrado');
                return;
            }
            
            // Verificar si hay datos
            if (!DATOS_COMPARATIVA || Object.keys(DATOS_COMPARATIVA).length === 0) {
                console.warn('   ⚠️  No hay datos históricos');
                canvasHistorico.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-line"></i><p>No hay datos históricos disponibles</p></div>';
                return;
            }
            
            console.log('   → Empresas en datos:', Object.keys(DATOS_COMPARATIVA));
            
            // Obtener todos los años únicos de todas las empresas
            const allYears = new Set();
            Object.values(DATOS_COMPARATIVA).forEach(empresa => {
                if (empresa) {
                    Object.keys(empresa).forEach(year => {
                        if (year && year !== 'undefined') {
                            allYears.add(year.toString());
                        }
                    });
                }
            });
            
            const sortedYears = Array.from(allYears).sort();
            console.log('   → Años encontrados:', sortedYears);
            
            if (sortedYears.length === 0) {
                console.warn('   ⚠️  No se encontraron años válidos');
                canvasHistorico.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-line"></i><p>No hay datos por año fiscal disponibles</p></div>';
                return;
            }
            
            const datasets = [];
            const colores = {
                'DISPERSORA_CREDITO': { 
                    bg: 'rgba(37, 150, 190, 0.6)', 
                    border: 'rgb(37, 150, 190)',
                    nombre: 'Dispersora de Crédito'
                },
                'FINANCIERA_SOFOM': { 
                    bg: 'rgba(145, 191, 128, 0.6)', 
                    border: 'rgb(145, 191, 128)',
                    nombre: 'Financiera SOFOM'
                }
            };
            
            // Crear datasets para cada empresa
            Object.keys(colores).forEach(empresaKey => {
                const empresaData = DATOS_COMPARATIVA[empresaKey];
                const colorConfig = colores[empresaKey];
                
                if (empresaData) {
                    console.log(`   → Procesando datos para ${colorConfig.nombre}:`, empresaData);
                    
                    // Dataset para Importe Ministrado
                    datasets.push({
                        label: colorConfig.nombre + ' - Colocado',
                        data: sortedYears.map(year => {
                            const yearData = empresaData[year];
                            return yearData ? parseFloat(yearData.importe_ministrado || 0) : 0;
                        }),
                        backgroundColor: colorConfig.bg,
                        borderColor: colorConfig.border,
                        borderWidth: 2,
                        borderRadius: 6,
                        type: 'bar'
                    });
                    
                    // Dataset para Saldo Vigente
                    datasets.push({
                        label: colorConfig.nombre + ' - Saldo Vigente',
                        data: sortedYears.map(year => {
                            const yearData = empresaData[year];
                            return yearData ? parseFloat(yearData.saldo_vigente || 0) : 0;
                        }),
                        backgroundColor: colorConfig.bg.replace('0.6', '0.3'),
                        borderColor: colorConfig.border,
                        borderWidth: 2,
                        borderRadius: 6,
                        type: 'bar'
                    });
                } else {
                    console.warn(`   ⚠️  No hay datos para ${colorConfig.nombre}`);
                }
            });
            
            console.log('   → Total datasets creados:', datasets.length);
            
            if (datasets.length === 0) {
                console.warn('   ⚠️  No se pudieron crear datasets');
                canvasHistorico.parentElement.innerHTML = '<div class="no-data"><i class="fas fa-chart-line"></i><p>No hay suficientes datos para mostrar la evolución histórica</p></div>';
                return;
            }
            
            // Crear el gráfico
            new Chart(canvasHistorico, {
                type: 'bar',
                data: {
                    labels: sortedYears,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true, 
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: $${value.toLocaleString('es-MX', {maximumFractionDigits: 0})}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return '$' + (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return '$' + (value / 1000).toFixed(0) + 'K';
                                    }
                                    return '$' + value.toLocaleString('es-MX', {maximumFractionDigits: 0});
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            console.log('   ✅ Gráfica histórica creada exitosamente');
            
        } catch (error) {
            console.error('   ❌ ERROR en gráfica histórica:', error);
            console.error('   Stack:', error.stack);
        }
        
        // ==================== GRÁFICAS POR EMPRESA ====================
        console.log('\n📊 [5/5] Creando gráficas por EMPRESA...');
        
        try {
            // Obtener las empresas desde las estadísticas por empresa
            const empresasCards = document.querySelectorAll('.chart-card[data-chart^="empresa_"]');
            
            console.log(`   → Encontradas ${empresasCards.length} tarjetas de empresa`);
            
            empresasCards.forEach((card, index) => {
                const empresaId = card.getAttribute('data-chart').replace('empresa_', '');
                const canvas = card.querySelector('canvas');
                
                if (canvas && canvas.id.startsWith('chart')) {
                    console.log(`   → Procesando gráfica para: ${empresaId}`);
                    
                    try {
                        const empresaData = JSON.parse(card.getAttribute('data-json'));
                        
                        new Chart(canvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Recuperado', 'Por Recuperar', 'Vencido'],
                                datasets: [{
                                    data: [
                                        empresaData.monto_recuperado || 0,
                                        (empresaData.saldo_por_recuperar || 0) - (empresaData.saldo_vencido || 0),
                                        empresaData.saldo_vencido || 0
                                    ],
                                    backgroundColor: [
                                        'rgba(16, 185, 129, 0.8)',
                                        'rgba(59, 130, 246, 0.8)',
                                        'rgba(239, 68, 68, 0.8)'
                                    ],
                                    borderColor: [
                                        'rgb(16, 185, 129)',
                                        'rgb(59, 130, 246)',
                                        'rgb(239, 68, 68)'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { 
                                        position: 'bottom' 
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.raw || 0;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                return `${label}: $${value.toLocaleString('es-MX')} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        
                        console.log(`   ✅ Gráfica para ${empresaId} creada`);
                    } catch (error) {
                        console.error(`   ❌ Error al crear gráfica para ${empresaId}:`, error);
                    }
                }
            });
            
            console.log(`   ✅ ${empresasCards.length} gráficas por empresa procesadas`);
            
        } catch (error) {
            console.error('   ❌ ERROR en gráficas por empresa:', error);
        }
        
        console.log('\n═══════════════════════════════════════');
        console.log('🎉 TODAS LAS GRÁFICAS CREADAS EXITOSAMENTE');
        console.log('═══════════════════════════════════════\n');
    }

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOM cargado, iniciando creación de gráficas...');
        
        // Pequeño delay para asegurar que todos los elementos estén listos
        setTimeout(() => {
            crearGraficas();
        }, 500);
    });

    // Manejar errores globales
    window.addEventListener('error', function(e) {
        console.error('⚠️ ERROR GLOBAL DETECTADO:', e.error);
        console.error('En archivo:', e.filename);
        console.error('Línea:', e.lineno, 'Columna:', e.colno);
    });
    </script>
</body>
</html>