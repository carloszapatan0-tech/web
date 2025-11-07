<?php
/*
 * Archivo: includes/db.php
 * credenciales para Miarroba/Webcindario
 */

// --- CREDENCIALES DE LA BASE DE DATOS ---
$host = "mysql.webcindario.com";
$user = "dentalcarepro";
$password = "MZ220703";
$database = "dentalcarepro";

// --- CREACIÓN DE LA CONEXIÓN ---
$conn = new mysqli($host, $user, $password, $database);

// --- VERIFICACIÓN DE LA CONEXIÓN ---
if ($conn->connect_error) {
    // Si hay un error, detiene la página y muestra el error.
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// --- CONFIGURACIÓN DE CARACTERES ---
$conn->set_charset("utf8");

?>