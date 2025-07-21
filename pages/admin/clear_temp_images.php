<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Limpiar directorio temporal
$temp_dir = '../../uploads/temp/' . session_id() . '/';
if (file_exists($temp_dir)) {
    array_map('unlink', glob($temp_dir . '*.*'));
    rmdir($temp_dir);
}

// Limpiar sesión
unset($_SESSION['temp_images']);

header('Content-Type: application/json');
echo json_encode(['success' => true]); 