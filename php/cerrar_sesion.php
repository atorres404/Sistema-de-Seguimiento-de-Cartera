<?php
    // Inicia la sesión existente, si la hay
    session_start();

    // Verifica si hay una sesión activa antes de intentar destruirla
    if (isset($_SESSION['usuario'])) {
        // Limpia todas las variables de la sesión
        $_SESSION = [];

        // Si las cookies de sesión están habilitadas, las elimina
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),    // Nombre de la cookie de sesión
                '',                // Valor vacío para eliminarla
                time() - 42000,    // Tiempo en el pasado para forzar su eliminación
                $params["path"],   // Ruta válida
                $params["domain"], // Dominio válido
                $params["secure"], // Solo para conexiones seguras si aplica
                $params["httponly"]// Solo accesible por HTTP
            );
        }

        // Finalmente destruye la sesión en el servidor
        session_destroy();
    }

    // Redirige al usuario a la página de inicio o login
    header("location: ../index.php");
    exit(); // Detiene la ejecución del script después de la redirección
?>