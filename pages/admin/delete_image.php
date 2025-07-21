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

// Obtener información de la imagen antes de eliminar
$get_img_sql = "SELECT url, principal FROM imagenes_producto WHERE id = ? AND producto_id = ?";
$get_img_stmt = mysqli_prepare($conn, $get_img_sql);
mysqli_stmt_bind_param($get_img_stmt, "ii", $image_id, $product_id);
mysqli_stmt_execute($get_img_stmt);
$img_result = mysqli_stmt_get_result($get_img_stmt);

if ($img_row = mysqli_fetch_assoc($img_result)) {
    $file_path = '../../' . $img_row['url'];
    $was_principal = $img_row['principal'];

    // Eliminar archivo físico
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Eliminar registro de la base de datos
    $del_sql = "DELETE FROM imagenes_producto WHERE id = ? AND producto_id = ?";
    $del_stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($del_stmt, "ii", $image_id, $product_id);
    
    if (mysqli_stmt_execute($del_stmt)) {
        // Si la imagen era principal, establecer otra imagen como principal
        if ($was_principal) {
            $update_sql = "UPDATE imagenes_producto 
                          SET principal = 1 
                          WHERE producto_id = ? 
                          ORDER BY id ASC 
                          LIMIT 1";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $product_id);
            mysqli_stmt_execute($update_stmt);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la imagen']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Imagen no encontrada']);
}

mysqli_close($conn); 