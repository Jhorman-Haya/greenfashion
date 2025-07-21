<?php
session_start();

// Limpiar imágenes temporales de la sesión
if (isset($_SESSION['temp_images'])) {
    // Eliminar archivos físicos
    foreach ($_SESSION['temp_images'] as $image) {
        if (file_exists($image['temp_path'])) {
            unlink($image['temp_path']);
        }
    }
    
    // Eliminar directorio temporal si está vacío
    $temp_dir = '../../uploads/temp/' . session_id() . '/';
    if (file_exists($temp_dir)) {
        @rmdir($temp_dir);
    }
    
    // Limpiar de la sesión
    unset($_SESSION['temp_images']);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>