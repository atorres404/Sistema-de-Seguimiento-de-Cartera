<?php
// Establece la conexión con la base de datos
$conexion = mysqli_connect("localhost", "root", "", "sistema_recuperaciones_db");

// Verifica si la conexión fue exitosa
if (!$conexion) {
    // Termina el script y muestra un mensaje de error
    die("Error en la conexión: " . mysqli_connect_error());
}

// Mensaje para desarrollo (comentar en producción)
// echo 'Conectado exitosamente a la Base de Datos';
?>