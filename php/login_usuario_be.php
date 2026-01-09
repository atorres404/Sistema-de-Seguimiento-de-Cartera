<?php
    session_start();

    // Incluir la conexión a la base de datos
    include 'conexion_be.php';

    // Capturar y limpiar las entradas del formulario
    $correo = mysqli_real_escape_string($conexion, trim($_POST['correo']));
    $contrasena = mysqli_real_escape_string($conexion, trim($_POST['contrasena']));

    // Hashear la contraseña
    //$contrasena = hash('sha512', $contrasena);

    // Consulta preparada para verificar usuario
    $stmt = $conexion->prepare("SELECT correo, rol FROM usuarios WHERE correo=? AND contrasena=?");
    $stmt->bind_param("ss", $correo, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Obtener los datos del usuario
        $usuario = $resultado->fetch_assoc();

        // Guardar el correo y rol en la sesión
        $_SESSION['usuario'] = $usuario['correo'];
        $_SESSION['rol'] = $usuario['rol'];

        // Redirigir según el rol
        if ($usuario['rol'] === 'admin') {
            header("location: ../subir_excel.php"); // Página del administrador
        } else {
            header("location: ../ver_datos.php"); // Página del usuario normal
        }
        exit;
    } else {
        // Si las credenciales son incorrectas, muestra un mensaje
        echo '
            <script>
                alert("Credenciales inválidas, por favor intente nuevamente");
                window.location = "../index.php";
            </script>
        ';
        exit;
    }
?>
