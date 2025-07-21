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
    // 1. Obtener información de la imagen
    $sql = "SELECT url, principal FROM imagenes_producto WHERE id = ? AND producto_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $image_id, $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($image = mysqli_fetch_assoc($result)) {
        // 2. Eliminar el archivo físico
        $file_path = '../../' . $image['url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // 3. Eliminar de la base de datos
        $delete_sql = "DELETE FROM imagenes_producto WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $image_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // 4. Si era la imagen principal, hacer principal la primera imagen restante
            if ($image['principal']) {
                $update_sql = "UPDATE imagenes_producto SET principal = 1 
                             WHERE producto_id = ? AND id != ? 
                             ORDER BY id ASC LIMIT 1";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ii", $product_id, $image_id);
                mysqli_stmt_execute($update_stmt);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error al eliminar la imagen de la base de datos");
        }
    } else {
        throw new Exception("Imagen no encontrada");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn); 