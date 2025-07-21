<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Obtener datos del request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image_id']) || !isset($data['product_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$conn = connection();
$image_id = filter_var($data['image_id'], FILTER_SANITIZE_NUMBER_INT);
$product_id = filter_var($data['product_id'], FILTER_SANITIZE_NUMBER_INT);

// Verificar que la imagen pertenezca al producto
$check_sql = "SELECT COUNT(*) as count FROM imagenes_producto WHERE id = ? AND producto_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $image_id, $product_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_fetch_assoc($check_result)['count'] === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'La imagen no pertenece al producto']);
    exit();
}

// Quitar principal de todas las imágenes del producto
$update_all_sql = "UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?";
$update_all_stmt = mysqli_prepare($conn, $update_all_sql);
mysqli_stmt_bind_param($update_all_stmt, "i", $product_id);
mysqli_stmt_execute($update_all_stmt);

// Establecer la nueva imagen principal
$update_sql = "UPDATE imagenes_producto SET principal = 1 WHERE id = ? AND producto_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "ii", $image_id, $product_id);

if (mysqli_stmt_execute($update_stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al actualizar la imagen principal']);
}