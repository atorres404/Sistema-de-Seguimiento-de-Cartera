<?php
date_default_timezone_set('America/Mexico_City');
session_start();

// Verifica si el usuario es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    echo '
        <script>
            alert("Acceso denegado. Debes iniciar sesión como administrador.");
            window.location = "index.php";
        </script>
    ';
    session_destroy();
    die();
}

// Incluye las dependencias necesarias
require 'vendor/autoload.php';
include 'php/conexion_be.php';

// Verifica que se hayan subido los archivos
if (!isset($_FILES['excelFile1']) || !isset($_FILES['excelFile2'])) {
    echo '
        <script>
            alert("No se han cargado los archivos requeridos.");
            window.location = "subir_excel.php";
        </script>
    ';
    exit();
}

// Función para procesar cada archivo
function procesarArchivo($fileTmpPath, $empresa, $conexion) {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    $encabezadosEsperados = ['Razón Social', 'Número de Contrato', 'Importe Ministrado', 'Saldo', 'Intereses', 'Vencimiento'];
    $encabezadosArchivo = $sheetData[1]; // La primera fila

    // Validar encabezados
    if ($encabezadosArchivo['A'] !== $encabezadosEsperados[0] ||
        $encabezadosArchivo['B'] !== $encabezadosEsperados[1] ||
        $encabezadosArchivo['C'] !== $encabezadosEsperados[2] ||
        $encabezadosArchivo['D'] !== $encabezadosEsperados[3] ||
        $encabezadosArchivo['E'] !== $encabezadosEsperados[4] ||
        $encabezadosArchivo['F'] !== $encabezadosEsperados[5]) {
        echo '
            <script>
                alert("El archivo de ' . $empresa . ' no tiene el formato esperado.");
                window.location = "subir_excel.php";
            </script>
        ';
        exit();
    }

    // Limpia los datos previos de la empresa
    $queryDelete = "DELETE FROM contratos WHERE empresa = '$empresa'";
    mysqli_query($conexion, $queryDelete);

    // Procesar datos en lotes
    $batchSize = 500; // Número de filas por lote
    $filasInsertadas = 0;
    $loteActual = [];

    for ($i = 2; $i <= count($sheetData); $i++) {
        // Validar y limpiar datos de cada fila
        $razonSocial = mysqli_real_escape_string($conexion, trim($sheetData[$i]['A'] ?? ''));
        $numeroContrato = mysqli_real_escape_string($conexion, trim($sheetData[$i]['B'] ?? ''));
        $importeMinistrado = floatval(str_replace(['$', ',', ' '], '', $sheetData[$i]['C'] ?? '0'));
        $saldo = floatval(str_replace(['$', ',', ' '], '', $sheetData[$i]['D'] ?? '0'));
        $intereses = floatval(str_replace(['$', ',', ' '], '', $sheetData[$i]['E'] ?? '0'));
        $vencimiento = !empty($sheetData[$i]['F']) ? date('Y-m-d', strtotime($sheetData[$i]['F'])) : null;

        // Verifica que los campos no estén vacíos y sean válidos
        if (!empty($razonSocial) && !empty($numeroContrato) && is_numeric($importeMinistrado) && is_numeric($saldo) && is_numeric($intereses) && $vencimiento) {
            $loteActual[] = "('$razonSocial', '$numeroContrato', $importeMinistrado, $saldo, $intereses, '$vencimiento', '$empresa', NOW())";

            // Si el lote alcanza el tamaño definido, inserta en la base de datos
            if (count($loteActual) >= $batchSize) {
                insertarLote($loteActual, $conexion, $filasInsertadas);
                $loteActual = []; // Reinicia el lote
            }
        } else {
            // Registra un error si una fila tiene datos inválidos
            error_log("Fila inválida en la línea $i: " . json_encode($sheetData[$i]));
        }
    }

    // Inserta las filas restantes del último lote
    if (!empty($loteActual)) {
        insertarLote($loteActual, $conexion, $filasInsertadas);
    }

    echo '
        <script>
            alert("Archivo de ' . $empresa . ' procesado correctamente. Filas insertadas: ' . $filasInsertadas . '.");
        </script>
    ';
}

function insertarLote($lote, $conexion, &$filasInsertadas) {
    $queryInsert = "INSERT INTO contratos (razon_social, numero_contrato, importe_ministrado, saldo, intereses, vencimiento, empresa, ultima_actualizacion) VALUES " . implode(',', $lote);

    // Depuración: registra la consulta SQL generada
    error_log("Query: $queryInsert");

    if (mysqli_query($conexion, $queryInsert)) {
        $filasInsertadas += count($lote); // Incrementa el contador de filas exitosas
    } else {
        // Registra el error en el log
        error_log("Error al insertar lote: " . mysqli_error($conexion));
    }
}


// Procesar el archivo 1
if ($_FILES['excelFile1']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath1 = $_FILES['excelFile1']['tmp_name'];
    procesarArchivo($fileTmpPath1, 'DISPERSORA_CREDITO', $conexion);
} else {
    echo '
        <script>
            alert("Error al procesar el archivo de Dispersora de Crédito.");
            window.location = "subir_excel.php";
        </script>
    ';
    exit();
}

// Procesar el archivo 2
if ($_FILES['excelFile2']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath2 = $_FILES['excelFile2']['tmp_name'];
    procesarArchivo($fileTmpPath2, 'FINANCIERA_SOFOM', $conexion);
} else {
    echo '
        <script>
            alert("Error al procesar el archivo de Financiera SOFOM.");
            window.location = "subir_excel.php";
        </script>
    ';
    exit();
}

// Redirige a la página principal
echo '
    <script>
        window.location = "ver_datos.php";
    </script>
';
?>







