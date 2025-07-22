<?php
require_once '../../config/database.php';
session_start();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Obtener datos del body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image_id']) || !isset($data['product_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$image_id = filter_var($data['image_id'], FILTER_SANITIZE_NUMBER_INT);
$product_id = filter_var($data['product_id'], FILTER_SANITIZE_NUMBER_INT);

$conn = connection();

try {
    // 1. Quitar el estado principal de todas las imágenes del producto
    $remove_principal_sql = "UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?";
    $remove_stmt = mysqli_prepare($conn, $remove_principal_sql);
    mysqli_stmt_bind_param($remove_stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($remove_stmt)) {
        // 2. Establecer la nueva imagen como principal
        $set_principal_sql = "UPDATE imagenes_producto SET principal = 1 WHERE id = ? AND producto_id = ?";
        $set_stmt = mysqli_prepare($conn, $set_principal_sql);
        mysqli_stmt_bind_param($set_stmt, "ii", $image_id, $product_id);
        
        if (mysqli_stmt_execute($set_stmt)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error al establecer la nueva imagen principal");
        }
    } else {
        throw new Exception("Error al quitar el estado principal de las imágenes existentes");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);