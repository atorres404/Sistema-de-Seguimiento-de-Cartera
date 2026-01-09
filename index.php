<?php

session_start();

if(isset($_SESSION['usuario'])){
    header("location: subir_excel.php");
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Recuperaciones</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: url('assets/images/bg4.jpeg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            height: 100%;
            position: relative;
            overflow-x: hidden;
        }

        /* Overlay oscuro sobre la imagen de fondo */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%);
            z-index: 0;
        }

        .contenedor {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 450px;
            width: 90%;
            backdrop-filter: blur(10px);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container i {
            font-size: 4em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            color: #666;
            font-size: 0.95em;
            text-align: center;
            margin-bottom: 35px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.1em;
            z-index: 1;
        }

        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        input::placeholder {
            color: #9ca3af;
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        button:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
            z-index: 0;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            color: #9ca3af;
            font-size: 0.9em;
            position: relative;
            z-index: 1;
        }

        .footer-text {
            text-align: center;
            color: #666;
            font-size: 0.85em;
            margin-top: 20px;
        }

        .footer-text i {
            color: #667eea;
        }

        /* Decoración de fondo */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }

        .bg-decoration:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }

        .bg-decoration:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
        }

        .bg-decoration:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: -75px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .contenedor {
                padding: 40px 30px;
                width: 95%;
            }

            h2 {
                font-size: 1.6em;
            }

            .logo-container i {
                font-size: 3em;
            }

            input {
                padding: 14px 14px 14px 42px;
                font-size: 0.95em;
            }

            button {
                padding: 14px;
                font-size: 1em;
            }
        }

        @media (max-width: 480px) {
            .contenedor {
                padding: 35px 25px;
            }

            h2 {
                font-size: 1.4em;
            }

            .logo-container i {
                font-size: 2.5em;
            }
        }

        /* Animación del botón de carga */
        button.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        button.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Decoración de fondo -->
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>
    <div class="bg-decoration"></div>

    <div class="contenedor">
        <div class="logo-container">
            <i class="fas fa-chart-line"></i>
        </div>
        
        <h2>¡Bienvenido!</h2>
        <p class="subtitle">Ingresa tus credenciales para continuar</p>
        
        <form action="php/login_usuario_be.php" method="POST" id="loginForm">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="text" placeholder="Correo Electrónico" name="correo" required autocomplete="email">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" placeholder="Contraseña" name="contrasena" id="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" id="submitBtn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="divider">
            <span>Sistema de Recuperaciones</span>
        </div>

        <div class="footer-text">
            <i class="fas fa-shield-alt"></i> Tus datos están protegidos y seguros
        </div>
    </div>

    <script>
        // Agregar efecto de carga al enviar el formulario
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
        });

        // Efecto de focus en los inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#667eea';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('i').style.color = '#667eea';
                }
            });
        });
    </script>
</body>
</html>