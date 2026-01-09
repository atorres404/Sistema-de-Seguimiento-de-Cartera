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

// Consultar estadísticas generales
$queryEstadisticas = "SELECT 
    COUNT(*) AS total_contratos,
    SUM(importe_ministrado) AS total_importe,
    SUM(saldo) AS total_saldo,
    SUM(intereses) AS total_intereses,
    MAX(ultima_actualizacion) AS ultima_actualizacion
FROM contratos";
$resultEstadisticas = mysqli_query($conexion, $queryEstadisticas);
$estadisticas = mysqli_fetch_assoc($resultEstadisticas);

// Formatear datos
$totalContratos = $estadisticas['total_contratos'] ?? 0;
$totalImporte = number_format($estadisticas['total_importe'] ?? 0, 2);
$totalSaldo = number_format($estadisticas['total_saldo'] ?? 0, 2);
$totalIntereses = number_format($estadisticas['total_intereses'] ?? 0, 2);
$ultimaActualizacion = $estadisticas['ultima_actualizacion'] 
    ? date('d-m-Y H:i:s', strtotime($estadisticas['ultima_actualizacion']))
    : 'No disponible';

// Consultar datos por empresa
   $queryEstadisticasEmpresas = "
    SELECT 
        empresa,
        COUNT(*) AS total_creditos_colocados,
        SUM(CASE WHEN saldo > 0 THEN 1 ELSE 0 END) AS total_creditos_vigentes,
        SUM(importe_ministrado) AS total_importe_ministrado,
        SUM(saldo) AS total_saldo_por_recuperar,
        SUM(CASE WHEN vencimiento < NOW() THEN saldo ELSE 0 END) AS total_saldo_vencido,
        SUM(intereses) AS total_intereses_acumulados
    FROM contratos
    GROUP BY empresa";
$resultadoEmpresas = mysqli_query($conexion, $queryEstadisticasEmpresas);

$estadisticasEmpresas = [];
$totalsaldoporrecuperar = 0;

while ($fila = mysqli_fetch_assoc($resultadoEmpresas)) {
    $empresa = $fila['empresa'];
    $totalSaldo = $fila['total_saldo_por_recuperar'];
    
    $totalVencido = $fila['total_saldo_vencido'];
    $porcentajeVencido = $totalSaldo > 0 ? ($totalVencido / $totalSaldo) * 100 : 0;

    $estadisticasEmpresas[$empresa] = [
        'total_creditos_colocados' => $fila['total_creditos_colocados'],
        'total_creditos_vigentes' => $fila['total_creditos_vigentes'],
        'total_importe_ministrado' => $fila['total_importe_ministrado'],
        'total_saldo_por_recuperar' => $fila['total_saldo_por_recuperar'],
        'total_saldo_vencido' => $totalVencido,
        'porcentaje_vencido' => $porcentajeVencido,
        'total_intereses_acumulados' => $fila['total_intereses_acumulados']
    ];
    
    // Sumar al total
    $totalsaldoporrecuperar += $totalSaldo;
}


// Consultar datos para las gráficas
$queryGraficas = "SELECT 
    empresa,
    YEAR(vencimiento - INTERVAL 2 MONTH) AS anio_fiscal,
    SUM(importe_ministrado) AS importe_ministrado,
    SUM(saldo) AS saldo_vigente
FROM contratos
GROUP BY empresa, YEAR(vencimiento - INTERVAL 2 MONTH)
ORDER BY empresa, anio_fiscal";
$resultGraficas = mysqli_query($conexion, $queryGraficas);

// Organizar datos por empresa y año fiscal
$datosGrafica = [];
while ($row = mysqli_fetch_assoc($resultGraficas)) {
    $empresa = $row['empresa'];
    $anioFiscal = $row['anio_fiscal'];
    $importeMinistrado = $row['importe_ministrado'];
    $saldoVigente = $row['saldo_vigente'];
    $porcentaje = $saldoVigente / $importeMinistrado * 100; // Calcular el porcentaje
    $datosGrafica[$empresa][$anioFiscal] = [
        'saldo_vigente' => $saldoVigente,
        'porcentaje' => $porcentaje
    ];
}

// Convertir datos a JSON para las gráficas
$graficaDispersora = json_encode($datosGrafica['DISPERSORA_CREDITO'] ?? []);
$graficaFinancieraSofom = json_encode($datosGrafica['FINANCIERA_SOFOM'] ?? []);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Recuperaciones - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
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
            backdrop-filter: blur(10px);
        }

        header .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        header .logo-section a {
            display: flex;
            align-items: center;
        }

        header .logo-section img {
            height: 80px;
            transition: transform 0.3s ease;
        }

        header .logo-section img:hover {
            transform: scale(1.05);
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #2596be, #91bf80);
        }

        .stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
        }

        .stat h2 {
            font-size: 1.1em;
            color: #333;
            font-weight: 600;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: 700;
            color: #2596be;
            margin: 10px 0 15px;
        }

        .stat-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 0.95em;
        }

        .stat-detail:first-of-type {
            border-top: none;
        }

        .stat-detail .label {
            font-weight: 500;
        }

        .stat-detail .value {
            font-weight: 600;
            color: #374151;
        }

        .company-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-right: 8px;
        }

        .badge-sofom {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-spr {
            background: #dbeafe;
            color: #1e40af;
        }

        .chart-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .chart-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-section h3 i {
            color: #2596be;
        }

        .chart-container {
            position: relative;
            height: 500px;
            width: 100%;
        }

        .update-info {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
            margin-top: 30px;
        }

        .update-info h2 {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .update-info p {
            color: #666;
            font-size: 1.1em;
            font-weight: 600;
        }

        @media (max-width: 1024px) {
            header {
                flex-direction: column;
                gap: 20px;
                padding: 20px 30px;
            }

            header h1 {
                font-size: 1.5em;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .chart-container {
                height: 400px;
            }
        }

        @media (max-width: 640px) {
            header {
                padding: 15px 20px;
            }

            header h1 {
                font-size: 1.2em;
            }

            .menu {
                flex-direction: column;
                width: 100%;
            }

            .menu a {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.5em;
            }

            .chart-section {
                padding: 20px 15px;
            }

            .chart-container {
                height: 350px;
            }
        }

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

        .stat {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat:nth-child(1) { animation-delay: 0.1s; }
        .stat:nth-child(2) { animation-delay: 0.2s; }
        .stat:nth-child(3) { animation-delay: 0.3s; }
        .stat:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <a href="inicio.php">
                <img src="assets/images/logo.png" alt="Logo">
            </a>
            <h1><i class="fas fa-chart-line"></i> Seguimiento de Cartera</h1>
        </div>
        <nav class="menu">
            <a href="inicio.php">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="ver_datos.php">
                <i class="fas fa-file-alt"></i> Reportes
            </a>
            <a href="php/cerrar_sesion.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h2>Total de Créditos Colocados</h2>
                </div>
                <div class="stat-value"><?php echo $totalContratos; ?> Créditos</div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-sofom">SOFOM</span>
                    </span>
                    <span class="value"><?php echo $estadisticasEmpresas['FINANCIERA_SOFOM']['total_creditos_colocados'] ?? 'N/A'; ?></span>
                </div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-spr">Dispersora</span>
                    </span>
                    <span class="value"><?php echo $estadisticasEmpresas['DISPERSORA_CREDITO']['total_creditos_colocados'] ?? 'N/A'; ?></span>
                </div>
            </div>

            <div class="stat">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h2>Total Importe Ministrado</h2>
                </div>
                <div class="stat-value">$<?php echo $totalImporte; ?></div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-sofom">SOFOM</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['FINANCIERA_SOFOM']['total_importe_ministrado'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-spr">Dispersora</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['DISPERSORA_CREDITO']['total_importe_ministrado'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="stat">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h2>Total Saldo por Recuperar</h2>
                </div>
                <div class="stat-value">$<?php echo number_format($totalsaldoporrecuperar ?? 0, 2); ?></div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-sofom">SOFOM</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['FINANCIERA_SOFOM']['total_saldo_por_recuperar'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-spr">Dispersora</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['DISPERSORA_CREDITO']['total_saldo_por_recuperar'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="stat">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h2>Total Intereses Acumulados</h2>
                </div>
                <div class="stat-value">$<?php echo $totalIntereses; ?></div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-sofom">SOFOM</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['FINANCIERA_SOFOM']['total_intereses_acumulados'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-detail">
                    <span class="label">
                        <span class="company-badge badge-spr">Dispersora</span>
                    </span>
                    <span class="value">$<?php echo number_format($estadisticasEmpresas['DISPERSORA_CREDITO']['total_intereses_acumulados'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="chart-section">
            <h3><i class="fas fa-chart-bar"></i> Saldo Vigente - Dispersora de Crédito</h3>
            <div class="chart-container">
                <canvas id="graficaDispersora"></canvas>
            </div>
        </div>

        <div class="chart-section">
            <h3><i class="fas fa-chart-bar"></i> Saldo Vigente - Financiera SOFOM</h3>
            <div class="chart-container">
                <canvas id="graficaFinancieraSofom"></canvas>
            </div>
        </div>

        <div class="update-info">
            <h2><i class="fas fa-clock"></i> Última Actualización</h2>
            <p><?php echo $ultimaActualizacion; ?></p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataDispersora = <?php echo $graficaDispersora; ?>;
        const dataFinancieraSofom = <?php echo $graficaFinancieraSofom; ?>;

        const labelsDispersora = Object.keys(dataDispersora);
        const valuesDispersora = Object.values(dataDispersora).map(function(item) { return item.saldo_vigente; });
        const percentagesDispersora = Object.values(dataDispersora).map(function(item) { return item.porcentaje; });

        const labelsFinancieraSofom = Object.keys(dataFinancieraSofom);
        const valuesFinancieraSofom = Object.values(dataFinancieraSofom).map(function(item) { return item.saldo_vigente; });
        const percentagesFinancieraSofom = Object.values(dataFinancieraSofom).map(function(item) { return item.porcentaje; });

        const maxValueDispersora = Math.max.apply(null, valuesDispersora);
        const maxValueSofom = Math.max.apply(null, valuesFinancieraSofom);
        const maxValue = Math.max(maxValueDispersora, maxValueSofom);
        const suggestedMax = maxValue * 1.15;

        new Chart(document.getElementById('graficaDispersora').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labelsDispersora,
                datasets: [{
                    label: 'Saldo Vigente - Dispersora de Crédito',
                    data: valuesDispersora,
                    backgroundColor: '#2596be',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    datalabels: {
                        formatter: function(value, context) {
                            return percentagesDispersora[context.dataIndex].toFixed(2) + '%';
                        },
                        anchor: 'end',
                        align: 'top',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        color: '#374151'
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        suggestedMax: suggestedMax,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-MX');
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        new Chart(document.getElementById('graficaFinancieraSofom').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labelsFinancieraSofom,
                datasets: [{
                    label: 'Saldo Vigente - Financiera SOFOM',
                    data: valuesFinancieraSofom,
                    backgroundColor: '#91bf80',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    datalabels: {
                        formatter: function(value, context) {
                            return percentagesFinancieraSofom[context.dataIndex].toFixed(2) + '%';
                        },
                        anchor: 'end',
                        align: 'top',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        color: '#374151'
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        suggestedMax: suggestedMax,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-MX');
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    });
    </script>
</body>
</html>