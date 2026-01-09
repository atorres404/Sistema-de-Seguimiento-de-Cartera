<?php
session_start();

// Verifica que el usuario esté autenticado y sea administrador
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Archivos Excel - Sistema de Recuperaciones</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            color: #666;
            font-size: 0.95em;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .file-input-wrapper {
            background: #f8f9fa;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .file-input-wrapper.active {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .file-input-wrapper label {
            display: block;
            color: #374151;
            font-weight: 500;
            margin-bottom: 12px;
            font-size: 0.95em;
        }

        .file-input-container {
            position: relative;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        input[type="file"]:hover {
            border-color: #667eea;
        }

        input[type="file"]::file-selector-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 12px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        input[type="file"]::file-selector-button:hover {
            background: #5568d3;
        }

        .submit-button {
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.05em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 10px;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .logout-link {
            display: inline-block;
            margin-top: 25px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            width: 100%;
            padding: 12px;
            border: 2px solid #ef4444;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .logout-link:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .icon {
            display: inline-block;
            margin-right: 8px;
        }

        @media (max-width: 640px) {
            .container {
                padding: 25px;
            }

            .header h1 {
                font-size: 1.6em;
            }

            .submit-button {
                padding: 12px 24px;
                font-size: 1em;
            }
        }

        /* Animación de carga */
        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
            color: #667eea;
            font-weight: 500;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Subir Archivos Excel</h1>
            <p>Carga los archivos correspondientes a cada entidad</p>
        </div>

        <form id="uploadForm" class="upload-form" action="procesar_excel.php" method="POST" enctype="multipart/form-data">
            <div class="file-input-wrapper">
                <label for="excelFile1">
                    <span class="icon">📄</span>
                    Archivo para "Dispersora de Crédito":
                </label>
                <div class="file-input-container">
                    <input type="file" id="excelFile1" name="excelFile1" accept=".xlsx, .xls" required>
                </div>
            </div>

            <div class="file-input-wrapper">
                <label for="excelFile2">
                    <span class="icon">📄</span>
                    Archivo para "Financiera SOFOM":
                </label>
                <div class="file-input-container">
                    <input type="file" id="excelFile2" name="excelFile2" accept=".xlsx, .xls" required>
                </div>
            </div>

            <button type="submit" class="submit-button">
                <span class="icon">⬆️</span>
                Cargar y Procesar
            </button>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Procesando archivos...</p>
            </div>
        </form>

        <a href="php/cerrar_sesion.php" class="logout-link">
            <span class="icon">🚪</span>
            Cerrar Sesión
        </a>
    </div>

    <script>
        // Efecto visual al seleccionar archivos
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const wrapper = this.closest('.file-input-wrapper');
                if (this.files.length > 0) {
                    wrapper.classList.add('active');
                } else {
                    wrapper.classList.remove('active');
                }
            });
        });

        // Mostrar indicador de carga al enviar
        const form = document.getElementById('uploadForm');
        const loading = document.getElementById('loading');
        
        form.addEventListener('submit', function() {
            loading.classList.add('active');
        });
    </script>
</body>
</html>
