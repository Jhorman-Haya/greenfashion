<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de producto no proporcionado']);
    exit();
}

$conn = connection();
$product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Obtener datos básicos del producto y categorías
$sql = "SELECT p.*, GROUP_CONCAT(c.nombre) as categorias 
        FROM productos p 
        LEFT JOIN productos_categorias pc ON p.id = pc.producto_id
        LEFT JOIN categorias c ON pc.categoria_id = c.id
        WHERE p.id = ? AND p.activo = TRUE
        GROUP BY p.id";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product_data = mysqli_fetch_assoc($result);

if ($product_data) {
    // Obtener imágenes del producto
    $img_sql = "SELECT id, url, principal FROM imagenes_producto 
                WHERE producto_id = ? 
                ORDER BY principal DESC, id ASC";
    $img_stmt = mysqli_prepare($conn, $img_sql);
    mysqli_stmt_bind_param($img_stmt, "i", $product_id);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);
    
    $product_data['imagenes'] = [];
    while ($img = mysqli_fetch_assoc($img_result)) {
        $product_data['imagenes'][] = $img;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $product_data
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Producto no encontrado'
    ]);
} 