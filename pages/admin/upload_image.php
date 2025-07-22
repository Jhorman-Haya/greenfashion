<?php
require_once '../../config/database.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que se enviaron archivos
if (!isset($_FILES['images']) || empty($_FILES['images']['name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No se enviaron archivos']);
    exit();
}

$files = $_FILES['images'];
$uploaded_files = [];
$errors = [];

// Crear directorio temporal único por sesión
$temp_dir = '../../uploads/temp/' . session_id() . '/';
if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir, 0777, true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No se pudo crear directorio temporal']);
        exit();
    }
}

// Procesar cada archivo
foreach ($files['name'] as $key => $name) {
    // Verificar errores de subida
    if ($files['error'][$key] !== UPLOAD_ERR_OK) {
        $errors[] = "Error al subir '{$name}'";
        continue;
    }

    $file_type = $files['type'][$key];
    $file_size = $files['size'][$key];
    $tmp_name = $files['tmp_name'][$key];

    // Validaciones básicas
    $allowed_types = ['image/jpeg', 'image/png'];
    $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    if (!in_array($file_type, $allowed_types) || !in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
        $errors[] = "'{$name}': Solo se permiten archivos JPG y PNG";
        continue;
    }

    if ($file_size > 500 * 1024) { // 500KB
        $errors[] = "'{$name}': Archivo muy grande (máximo 500KB)";
        continue;
    }

    if ($file_size < 1024) { // 1KB
        $errors[] = "'{$name}': Archivo muy pequeño";
        continue;
    }

    // Verificar que es una imagen real
    if (!@getimagesize($tmp_name)) {
        $errors[] = "'{$name}': No es una imagen válida";
        continue;
    }

    // Generar nombre único y mover archivo
    $unique_filename = uniqid('img_') . '.' . $file_extension;
    $temp_path = $temp_dir . $unique_filename;

    if (move_uploaded_file($tmp_name, $temp_path)) {
        $uploaded_files[] = [
            'id' => uniqid('temp_'),
            'filename' => $unique_filename,
            'original_name' => $name,
            'temp_path' => $temp_path,
            'size' => $file_size,
            'type' => $file_type,
            'is_principal' => false,
            'is_existing' => false
        ];
    } else {
        $errors[] = "Error al guardar '{$name}'";
    }
}

// Respuesta
header('Content-Type: application/json');

if (!empty($uploaded_files)) {
    echo json_encode([
        'success' => true,
        'files' => $uploaded_files,
        'message' => count($uploaded_files) . ' imagen(es) procesadas correctamente',
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo procesar ningún archivo',
        'details' => $errors
    ]);
}
?> 
