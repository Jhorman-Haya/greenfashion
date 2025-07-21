<?php
require_once '../../config/database.php';
session_start();

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que se hayan enviado imágenes
if (!isset($_FILES['images'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No se recibieron imágenes']);
    exit();
}

$files = $_FILES['images'];
$uploaded_files = [];
$errors = [];

// Crear directorio temporal para la sesión si no existe
$temp_upload_dir = '../../uploads/temp/' . session_id() . '/';
if (!file_exists($temp_upload_dir)) {
    mkdir($temp_upload_dir, 0777, true);
} else {
    // Limpiar directorio temporal
    array_map('unlink', glob($temp_upload_dir . '*.*'));
}

// Validar y procesar cada imagen
foreach ($files['name'] as $key => $name) {
    if ($files['error'][$key] === 0) {
        $file_type = $files['type'][$key];
        $file_size = $files['size'][$key];
        $tmp_name = $files['tmp_name'][$key];

        // Validar tipo de archivo (según documentación técnica - extensiones permitidas)
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "El archivo $name no es válido. Solo se permiten imágenes JPG y PNG";
            continue;
        }

        // Validar tamaño (500KB máximo según documentación técnica)
        if ($file_size > 500 * 1024) {
            $errors[] = "El archivo $name excede el límite de 500KB";
            continue;
        }

        // Generar nombre único
        $file_extension = pathinfo($name, PATHINFO_EXTENSION);
        $unique_filename = uniqid('product_') . '.' . $file_extension;
        $temp_path = $temp_upload_dir . $unique_filename;

        // Mover archivo
        if (move_uploaded_file($tmp_name, $temp_path)) {
            $uploaded_files[] = [
                'temp_path' => $temp_path,
                'filename' => $unique_filename,
                'original_name' => $name,
                'is_principal' => $key === 0
            ];
        } else {
            $errors[] = "Error al guardar el archivo $name";
        }
    }
}

// Agregar a las imágenes temporales existentes (no reemplazar)
if (!isset($_SESSION['temp_images'])) {
    $_SESSION['temp_images'] = [];
}

// Verificar si ya hay alguna imagen (principal o no) en la sesión
$has_any_images = !empty($_SESSION['temp_images']);
$has_principal = false;

// Verificar si ya hay una imagen principal
foreach ($_SESSION['temp_images'] as $existing_image) {
    if ($existing_image['is_principal']) {
        $has_principal = true;
        break;
    }
}

// Lógica de imagen principal:
// SOLO la primera imagen subida en toda la sesión será principal
// Las demás imágenes subidas posteriormente NO serán principales automáticamente
if (!$has_any_images && !empty($uploaded_files)) {
    // Si es la primera vez que se suben imágenes, la primera será principal
    $uploaded_files[0]['is_principal'] = true;
    // Las demás imágenes de este upload no son principales
    for ($i = 1; $i < count($uploaded_files); $i++) {
        $uploaded_files[$i]['is_principal'] = false;
    }
} else {
    // Si ya hay imágenes (principal o no), TODAS las nuevas imágenes serán NO principales
    foreach ($uploaded_files as &$file) {
        $file['is_principal'] = false;
    }
}

// Agregar las nuevas imágenes a las existentes
$_SESSION['temp_images'] = array_merge($_SESSION['temp_images'], $uploaded_files);

// Log de debug
$debug_info = [
    'session_id' => session_id(),
    'temp_dir' => $temp_upload_dir,
    'temp_dir_exists' => file_exists($temp_upload_dir),
    'uploaded_count' => count($uploaded_files),
    'session_temp_images_count' => count($_SESSION['temp_images']),
    'has_principal' => $has_principal,
    'total_files_now' => count($_SESSION['temp_images'])
];

// Devolver respuesta
header('Content-Type: application/json');
if (!empty($uploaded_files)) {
    echo json_encode([
        'success' => true,
        'files' => $uploaded_files,
        'errors' => $errors,
        'debug' => $debug_info
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo subir ninguna imagen',
        'errors' => $errors,
        'debug' => $debug_info
    ]);
} 