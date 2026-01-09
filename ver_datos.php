<?php
session_start();
include 'php/conexion_be.php';

date_default_timezone_set('America/Mexico_City');

// Determinar empresa seleccionada
$empresa = isset($_GET['empresa']) ? $_GET['empresa'] : 'DISPERSORA_CREDITO';

// Obtener la última actualización
$queryFecha = "SELECT MAX(ultima_actualizacion) AS ultima_actualizacion FROM contratos WHERE empresa = '$empresa'";
$resultadoFecha = mysqli_query($conexion, $queryFecha);
$filaFecha = mysqli_fetch_assoc($resultadoFecha);
$ultima_actualizacion = $filaFecha['ultima_actualizacion'] ? date('d-m-Y H:i:s', strtotime($filaFecha['ultima_actualizacion'])) : 'No disponible';

// Aplicar filtros
$filtros = "WHERE empresa = '$empresa'";

// Filtro de saldo vigente
if (isset($_GET['saldo_vigente']) && $_GET['saldo_vigente'] !== "") {
    if ($_GET['saldo_vigente'] == "1") {
        $filtros .= " AND saldo > 0"; // Mostrar solo los que tienen saldo
    } elseif ($_GET['saldo_vigente'] == "0") {
        $filtros .= " AND saldo = 0"; // Mostrar solo los que no tienen saldo
    }
}

// Filtro de rango de fechas
if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_final'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_final = $_GET['fecha_final'];
    $filtros .= " AND vencimiento BETWEEN '$fecha_inicio' AND '$fecha_final'";
}

// Consulta final con filtros
$query = "SELECT razon_social, numero_contrato, importe_ministrado, saldo, intereses, vencimiento 
          FROM contratos 
          $filtros";
$result = mysqli_query($conexion, $query);

// Inicializar totales
$totalExigible = 0;
$totalSaldo = 0;
$totalIntereses = 0;
$dataRows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $totalExigible += $row['importe_ministrado'];
    $totalSaldo += $row['saldo'];
    $totalIntereses += $row['intereses'];
    $dataRows[] = $row; // Guardar datos para generar la tabla
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
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

        /* Header */
        .header {
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

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-left a {
            display: flex;
            align-items: center;
        }

        .header-left img {
            height: 80px;
            transition: transform 0.3s ease;
        }

        .header-left img:hover {
            transform: scale(1.05);
        }

        .header h1 {
            color: #fff;
            font-size: 1.8em;
            font-weight: 600;
        }

        .last-update {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            color: #fff;
            font-size: 0.9em;
        }

        .last-update strong {
            font-weight: 700;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Selector de Empresa */
        .empresa-selector {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .empresa-label {
            text-align: center;
            font-size: 1.3em;
            font-weight: 700;
            color: #2596be;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .menu a {
            padding: 15px 20px;
            background: linear-gradient(135deg, #2596be 0%, #1a7a9c 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 150, 190, 0.3);
        }

        .menu a:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(37, 150, 190, 0.5);
        }

        /* Filtros */
        .filters-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .filters-title {
            font-size: 1.2em;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filters label {
            font-size: 0.9em;
            font-weight: 600;
            color: #555;
        }

        .filters input,
        .filters select {
            padding: 12px;
            font-size: 0.95em;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #2596be;
            box-shadow: 0 0 0 3px rgba(37, 150, 190, 0.1);
        }

        .filters button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .filters button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Tabla */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #2596be 0%, #1a7a9c 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f3f4f6;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:nth-child(even):hover {
            background-color: #f3f4f6;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #ef4444;
            font-weight: 600;
            font-size: 1.1em;
        }

        /* Totales */
        .totals-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .totals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .total-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
            border-left: 4px solid #2596be;
        }

        .total-item:nth-child(2) {
            border-left-color: #667eea;
        }

        .total-item:nth-child(3) {
            border-left-color: #91bf80;
        }

        .total-label {
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .total-value {
            font-size: 1.8em;
            font-weight: 700;
            color: #2596be;
        }

        /* Gráfica */
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .chart-title {
            text-align: center;
            font-size: 1.4em;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .chart-wrapper {
            max-width: 600px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 20px 30px;
            }

            .header h1 {
                font-size: 1.5em;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .totals-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.2em;
            }

            .header-left img {
                height: 60px;
            }

            .empresa-label {
                font-size: 1.1em;
            }

            table {
                font-size: 0.85em;
            }

            th, td {
                padding: 10px 8px;
            }

            .total-value {
                font-size: 1.4em;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empresa-selector,
        .filters-container,
        .table-container,
        .totals-container,
        .chart-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="inicio.php">
                <img src="assets/images/logo.png" alt="Logo">
            </a>
            <h1><i class="fas fa-file-alt"></i> Reportes</h1>
        </div>
        <div class="last-update">
            <i class="fas fa-clock"></i> Última actualización: <strong><?php echo $ultima_actualizacion; ?></strong>
        </div>
    </div>

    <div class="container">
        <!-- Selector de Empresa -->
        <div class="empresa-selector">
            <div class="empresa-label">
                <i class="fas fa-building"></i>
                <?php
                $nombreEmpresa = [
                    'DISPERSORA_CREDITO' => 'Dispersora de Crédito',
                    'FINANCIERA_SOFOM' => 'Financiera SOFOM'
                ];
                echo $nombreEmpresa[$empresa] ?? 'Empresa desconocida';
                ?>
            </div>
            <div class="menu">
                <a href="ver_datos.php?empresa=DISPERSORA_CREDITO">
                    <i class="fas fa-chart-line"></i> Dispersora de Crédito
                </a>
                <a href="ver_datos.php?empresa=FINANCIERA_SOFOM">
                    <i class="fas fa-university"></i> Financiera SOFOM
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-container">
            <div class="filters-title">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </div>
            <form method="GET" class="filters">
                <input type="hidden" name="empresa" value="<?php echo $empresa; ?>">
                
                <div class="filter-group">
                    <label for="saldo_vigente">
                        <i class="fas fa-money-check-alt"></i> Saldo Vigente
                    </label>
                    <select name="saldo_vigente" id="saldo_vigente">
                        <option value="">Todos</option>
                        <option value="1" <?php echo isset($_GET['saldo_vigente']) && $_GET['saldo_vigente'] == "1" ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo isset($_GET['saldo_vigente']) && $_GET['saldo_vigente'] == "0" ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="fecha_inicio">
                        <i class="fas fa-calendar-alt"></i> Fecha Inicio
                    </label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo $_GET['fecha_inicio'] ?? ''; ?>">
                </div>

                <div class="filter-group">
                    <label for="fecha_final">
                        <i class="fas fa-calendar-check"></i> Fecha Final
                    </label>
                    <input type="date" name="fecha_final" id="fecha_final" value="<?php echo $_GET['fecha_final'] ?? ''; ?>">
                </div>

                <button type="submit">
                    <i class="fas fa-search"></i> Aplicar Filtros
                </button>
            </form>
        </div>

        <!-- Tabla de Datos -->
        <div class="table-container">
            <?php if (count($dataRows) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Razón Social</th>
                        <th><i class="fas fa-file-contract"></i> Número de Contrato</th>
                        <th><i class="fas fa-dollar-sign"></i> Importe Ministrado</th>
                        <th><i class="fas fa-wallet"></i> Saldo</th>
                        <th><i class="fas fa-percentage"></i> Intereses</th>
                        <th><i class="fas fa-calendar"></i> Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataRows as $row): ?>
                        <tr>
                            <td><?php echo $row['razon_social']; ?></td>
                            <td><?php echo $row['numero_contrato']; ?></td>
                            <td>$<?php echo number_format($row['importe_ministrado'], 2); ?></td>
                            <td>$<?php echo number_format($row['saldo'], 2); ?></td>
                            <td>$<?php echo number_format($row['intereses'], 2); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['vencimiento'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i> No hay datos para mostrar con los filtros seleccionados.
                </div>
            <?php endif; ?>
        </div>

        <!-- Totales -->
        <div class="totals-container">
            <div class="totals-grid">
                <div class="total-item">
                    <div class="total-label">
                        <i class="fas fa-money-bill-wave"></i> Total Exigible
                    </div>
                    <div class="total-value">$<?php echo number_format($totalExigible, 2); ?></div>
                    <div style="font-size: 0.85em; color: #999; margin-top: 5px;">M.N.</div>
                </div>
                <div class="total-item">
                    <div class="total-label">
                        <i class="fas fa-hand-holding-usd"></i> Por Recuperar
                    </div>
                    <div class="total-value">$<?php echo number_format($totalSaldo, 2); ?></div>
                    <div style="font-size: 0.85em; color: #999; margin-top: 5px;">M.N.</div>
                </div>
                <div class="total-item">
                    <div class="total-label">
                        <i class="fas fa-chart-line"></i> Intereses por Recuperar
                    </div>
                    <div class="total-value">$<?php echo number_format($totalIntereses, 2); ?></div>
                    <div style="font-size: 0.85em; color: #999; margin-top: 5px;">M.N.</div>
                </div>
            </div>
        </div>

        <!-- Gráfica -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fas fa-chart-pie"></i> Distribución de Recuperación
            </div>
            <div class="chart-wrapper">
                <canvas id="grafica"></canvas>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('grafica');
        const totalExigible = <?php echo $totalExigible; ?>;
        const totalSaldo = <?php echo $totalSaldo; ?>;
        const totalRecuperado = totalExigible - totalSaldo;

        if (ctx && totalExigible > 0) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Por recuperar', 'Recuperado'],
                    datasets: [{
                        data: [totalSaldo, totalRecuperado],
                        backgroundColor: ['#2596be', '#91bf80'],
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        datalabels: {
                            formatter: function(value, context) {
                                const total = context.chart.data.datasets[0].data.reduce(function(a, b) { return a + b; }, 0);
                                const percentage = ((value / total) * 100).toFixed(2);
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 16,
                            },
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
    });
    </script>
</body>
</html>















                           




