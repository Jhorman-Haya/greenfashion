<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$conn = connection();

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $mensaje = "Producto creado exitosamente";
            $tipo_mensaje = "exito";
            break;
        case 'updated':
            $mensaje = "Producto actualizado exitosamente";
            $tipo_mensaje = "exito";
            break;
        case 'deleted':
            $mensaje = "Producto eliminado exitosamente";
            $tipo_mensaje = "exito";
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $nombre = sanitizeInput($_POST['nombre']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $precio = floatval($_POST['precio']);
                $stock = intval($_POST['stock']);
                $materiales = sanitizeInput($_POST['materiales']);
                $impacto_ambiental = sanitizeInput($_POST['impacto_ambiental']);
                $activo = isset($_POST['activo']) ? 1 : 0;
                $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];

                $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, materiales, impacto_ambiental, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssdissi", $nombre, $descripcion, $precio, $stock, $materiales, $impacto_ambiental, $activo);
                
                if (mysqli_stmt_execute($stmt)) {
                    $producto_id = mysqli_insert_id($conn);

                    // Procesar imágenes temporales si existen
                    if (isset($_POST['temp_images']) && !empty($_POST['temp_images'])) {
                        // Crear directorio del producto
                        $upload_dir = '../../uploads/products/' . $producto_id . '/';
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0777, true)) {
                                $mensaje = "Error al crear directorio de imágenes";
                                $tipo_mensaje = "error";
                                break;
                            }
                        }

                        foreach ($_POST['temp_images'] as $image) {
                            // Verificar datos requeridos
                            if (empty($image['temp_path']) || empty($image['filename'])) {
                                continue;
                            }

                            $temp_path = $image['temp_path'];
                            $filename = $image['filename'];
                            $is_principal = isset($image['is_principal']) && $image['is_principal'] === '1';
                            
                            // Verificar que el archivo temporal existe
                            if (!file_exists($temp_path)) {
                                $mensaje = "Archivo temporal no encontrado: " . $filename;
                                $tipo_mensaje = "error";
                                continue;
                            }
                            
                            // Mover imagen a ubicación final
                            $final_path = $upload_dir . $filename;
                            if (!rename($temp_path, $final_path)) {
                                $mensaje = "Error al mover imagen: " . $filename;
                                $tipo_mensaje = "error";
                                continue;
                            }

                            // Guardar en base de datos
                            $relative_path = 'uploads/products/' . $producto_id . '/' . $filename;
                            $img_sql = "INSERT INTO imagenes_producto (producto_id, url, principal) VALUES (?, ?, ?)";
                            $img_stmt = mysqli_prepare($conn, $img_sql);
                            
                            if ($img_stmt) {
                                $principal_value = $is_principal ? 1 : 0;
                                mysqli_stmt_bind_param($img_stmt, "isi", $producto_id, $relative_path, $principal_value);
                                
                                if (!mysqli_stmt_execute($img_stmt)) {
                                    $mensaje = "Error al guardar imagen en BD: " . mysqli_error($conn);
                                    $tipo_mensaje = "error";
                                }
                            }
                        }

                        // Limpiar directorio temporal
                        $temp_dir = '../../uploads/temp/' . session_id() . '/';
                        if (file_exists($temp_dir)) {
                            array_map('unlink', glob($temp_dir . '*.*'));
                            rmdir($temp_dir);
                        }
                    }
                    
                    // Insertar categorías del producto
                    if (!empty($categorias)) {
                        foreach ($categorias as $categoria_nombre) {
                            $categoria_nombre = sanitizeInput($categoria_nombre);
                            
                            // Buscar o crear categoría
                            $cat_sql = "SELECT id FROM categorias WHERE nombre = ?";
                            $cat_stmt = mysqli_prepare($conn, $cat_sql);
                            mysqli_stmt_bind_param($cat_stmt, "s", $categoria_nombre);
                            mysqli_stmt_execute($cat_stmt);
                            $cat_result = mysqli_stmt_get_result($cat_stmt);
                            
                            if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                $categoria_id = $cat_row['id'];
                            } else {
                                // Crear nueva categoría
                                $new_cat_sql = "INSERT INTO categorias (nombre, activo) VALUES (?, 1)";
                                $new_cat_stmt = mysqli_prepare($conn, $new_cat_sql);
                                mysqli_stmt_bind_param($new_cat_stmt, "s", $categoria_nombre);
                                mysqli_stmt_execute($new_cat_stmt);
                                $categoria_id = mysqli_insert_id($conn);
                            }
                            
                            // Relacionar producto con categoría
                            $rel_sql = "INSERT INTO productos_categorias (producto_id, categoria_id) VALUES (?, ?)";
                            $rel_stmt = mysqli_prepare($conn, $rel_sql);
                            mysqli_stmt_bind_param($rel_stmt, "ii", $producto_id, $categoria_id);
                            mysqli_stmt_execute($rel_stmt);
                        }
                    }
                    
                    // Redirigir para evitar reenvío del formulario
                    header('Location: products.php?success=created');
                    exit();
                } else {
                    $mensaje = "Error al crear producto: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                }
                break;

            case 'update':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $nombre = sanitizeInput($_POST['nombre']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $precio = floatval($_POST['precio']);
                $stock = intval($_POST['stock']);
                $materiales = sanitizeInput($_POST['materiales']);
                $impacto_ambiental = sanitizeInput($_POST['impacto_ambiental']);
                $activo = isset($_POST['activo']) ? 1 : 0;
                $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];

                // Actualizar información básica del producto
                $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, materiales = ?, impacto_ambiental = ?, activo = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssdissii", $nombre, $descripcion, $precio, $stock, $materiales, $impacto_ambiental, $activo, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Eliminar imágenes marcadas para eliminación
                    if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
                        foreach ($_POST['delete_images'] as $image_id) {
                            $image_id = intval($image_id);
                            
                            // Obtener la URL de la imagen antes de eliminarla
                            $get_img_sql = "SELECT url FROM imagenes_producto WHERE id = ?";
                            $get_img_stmt = mysqli_prepare($conn, $get_img_sql);
                            mysqli_stmt_bind_param($get_img_stmt, "i", $image_id);
                            mysqli_stmt_execute($get_img_stmt);
                            $img_result = mysqli_stmt_get_result($get_img_stmt);
                            
                            if ($img_row = mysqli_fetch_assoc($img_result)) {
                                $file_path = '../../' . $img_row['url'];
                                if (file_exists($file_path)) {
                                    unlink($file_path); // Eliminar archivo físico
                                }
                            }
                            
                            // Eliminar de la base de datos
                            $del_img_sql = "DELETE FROM imagenes_producto WHERE id = ?";
                            $del_img_stmt = mysqli_prepare($conn, $del_img_sql);
                            mysqli_stmt_bind_param($del_img_stmt, "i", $image_id);
                            mysqli_stmt_execute($del_img_stmt);
                        }
                    }

                    // Actualizar imagen principal de las existentes
                    if (isset($_POST['existing_principal_id'])) {
                        $principal_id = intval($_POST['existing_principal_id']);
                        
                        // Quitar principal de todas las imágenes del producto
                        $update_all_sql = "UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?";
                        $update_all_stmt = mysqli_prepare($conn, $update_all_sql);
                        mysqli_stmt_bind_param($update_all_stmt, "i", $id);
                        mysqli_stmt_execute($update_all_stmt);
                        
                        // Marcar la nueva como principal
                        $update_principal_sql = "UPDATE imagenes_producto SET principal = 1 WHERE id = ? AND producto_id = ?";
                        $update_principal_stmt = mysqli_prepare($conn, $update_principal_sql);
                        mysqli_stmt_bind_param($update_principal_stmt, "ii", $principal_id, $id);
                        mysqli_stmt_execute($update_principal_stmt);
                    }

                    // Procesar nuevas imágenes temporales si existen
                    if (isset($_POST['temp_images']) && !empty($_POST['temp_images'])) {
                        $upload_dir = '../../uploads/products/' . $id . '/';
                        
                        // Crear directorio si no existe
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0777, true)) {
                                $mensaje = "Error al crear el directorio para las imágenes";
                                $tipo_mensaje = "error";
                                break;
                            }
                        }

                        // Verificar si ya existe una imagen principal
                        $check_principal_sql = "SELECT COUNT(*) as count FROM imagenes_producto WHERE producto_id = ? AND principal = 1";
                        $check_stmt = mysqli_prepare($conn, $check_principal_sql);
                        mysqli_stmt_bind_param($check_stmt, "i", $id);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        $has_principal = mysqli_fetch_assoc($check_result)['count'] > 0;

                        foreach ($_POST['temp_images'] as $image) {
                            // Verificar que tenemos los datos necesarios
                            if (!isset($image['temp_path']) || !isset($image['filename'])) {
                                continue;
                            }

                            $temp_path = $image['temp_path'];
                            $filename = $image['filename'];
                            $is_principal = isset($image['is_principal']) && $image['is_principal'] == '1';
                            
                            // Verificar que el archivo temporal existe
                            if (!file_exists($temp_path)) {
                                $mensaje = "Error: Archivo temporal no encontrado - " . $filename;
                                $tipo_mensaje = "error";
                                continue;
                            }

                            // Si es la imagen principal, quitar la marca de principal de las otras
                            if ($is_principal && !$has_principal) {
                                $update_principal_sql = "UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?";
                                $update_principal_stmt = mysqli_prepare($conn, $update_principal_sql);
                                mysqli_stmt_bind_param($update_principal_stmt, "i", $id);
                                mysqli_stmt_execute($update_principal_stmt);
                                $has_principal = true;
                            }
                            
                            // Mover la imagen a su ubicación final
                            $final_path = $upload_dir . $filename;
                            if (!rename($temp_path, $final_path)) {
                                $mensaje = "Error al mover la imagen: " . $filename;
                                $tipo_mensaje = "error";
                                continue;
                            }

                            // Guardar referencia en la base de datos
                            $relative_path = 'uploads/products/' . $id . '/' . $filename;
                            $img_sql = "INSERT INTO imagenes_producto (producto_id, url, principal) VALUES (?, ?, ?)";
                            $img_stmt = mysqli_prepare($conn, $img_sql);
                            
                            if (!$img_stmt) {
                                $mensaje = "Error al preparar la consulta de imagen";
                                $tipo_mensaje = "error";
                                continue;
                            }

                            $principal_value = $is_principal ? 1 : 0;
                            mysqli_stmt_bind_param($img_stmt, "isi", $id, $relative_path, $principal_value);
                            
                            if (!mysqli_stmt_execute($img_stmt)) {
                                $mensaje = "Error al guardar la imagen en la base de datos: " . mysqli_error($conn);
                                $tipo_mensaje = "error";
                                continue;
                            }
                        }

                        // Limpiar archivos temporales
                        $temp_dir = '../../uploads/temp/' . session_id() . '/';
                        if (file_exists($temp_dir)) {
                            array_map('unlink', glob($temp_dir . '*.*'));
                            rmdir($temp_dir);
                        }
                    }

                    // Actualizar categorías del producto
                    // Primero eliminar las categorías existentes
                    $delete_cat_sql = "DELETE FROM productos_categorias WHERE producto_id = ?";
                    $delete_cat_stmt = mysqli_prepare($conn, $delete_cat_sql);
                    mysqli_stmt_bind_param($delete_cat_stmt, "i", $id);
                    mysqli_stmt_execute($delete_cat_stmt);

                    // Insertar las nuevas categorías
                    if (!empty($categorias)) {
                        foreach ($categorias as $categoria_nombre) {
                            $categoria_nombre = sanitizeInput($categoria_nombre);
                            
                            // Buscar o crear categoría
                            $cat_sql = "SELECT id FROM categorias WHERE nombre = ?";
                            $cat_stmt = mysqli_prepare($conn, $cat_sql);
                            mysqli_stmt_bind_param($cat_stmt, "s", $categoria_nombre);
                            mysqli_stmt_execute($cat_stmt);
                            $cat_result = mysqli_stmt_get_result($cat_stmt);
                            
                            if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                $categoria_id = $cat_row['id'];
                            } else {
                                // Crear nueva categoría
                                $new_cat_sql = "INSERT INTO categorias (nombre, activo) VALUES (?, 1)";
                                $new_cat_stmt = mysqli_prepare($conn, $new_cat_sql);
                                mysqli_stmt_bind_param($new_cat_stmt, "s", $categoria_nombre);
                                mysqli_stmt_execute($new_cat_stmt);
                                $categoria_id = mysqli_insert_id($conn);
                            }
                            
                            // Relacionar producto con categoría
                            $rel_sql = "INSERT INTO productos_categorias (producto_id, categoria_id) VALUES (?, ?)";
                            $rel_stmt = mysqli_prepare($conn, $rel_sql);
                            mysqli_stmt_bind_param($rel_stmt, "ii", $id, $categoria_id);
                            mysqli_stmt_execute($rel_stmt);
                        }
                    }

                    // Redirigir para evitar reenvío del formulario
                    header('Location: products.php?success=updated');
                    exit();
                } else {
                    $mensaje = "Error al actualizar producto";
                    $tipo_mensaje = "error";
                }
                break;

            case 'delete':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

                $sql = "UPDATE productos SET activo = FALSE WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Redirigir para evitar reenvío del formulario
                    header('Location: products.php?success=deleted');
                    exit();
                } else {
                    $mensaje = "Error al eliminar producto";
                    $tipo_mensaje = "error";
                }
                break;
        }
    }
}

// Obtener productos
$search = isset($_GET['search']) ? trim(sanitizeInput($_GET['search'])) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir consulta con parámetros preparados
$where_conditions = ["p.activo = TRUE"];
$params = [];
$param_types = "";

if ($search && strlen($search) >= 2) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.fecha_creacion, p.activo,
               GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias,
               (SELECT url FROM imagenes_producto WHERE producto_id = p.id AND principal = 1 LIMIT 1) as imagen_principal
        FROM productos p 
        LEFT JOIN productos_categorias pc ON p.id = pc.producto_id
        LEFT JOIN categorias c ON pc.categoria_id = c.id
        $where_clause 
        GROUP BY p.id
        ORDER BY p.fecha_creacion DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$productos = mysqli_stmt_get_result($stmt);

// Contar total de productos
$count_sql = "SELECT COUNT(*) as total FROM productos p $where_clause";
if ($search && strlen($search) >= 2) {
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_sql);
}
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $limit);

// Obtener producto por ID para edición
if (isset($_GET['get_product'])) {
    $product_id = filter_var($_GET['get_product'], FILTER_SANITIZE_NUMBER_INT);
    
    // Validar que el ID es válido
    if (!$product_id || $product_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'ID de producto inválido']);
        exit();
    }
    
    // Obtener datos básicos del producto y categorías (sin filtro de activo para edición)
    $sql = "SELECT p.*, GROUP_CONCAT(c.nombre) as categorias FROM productos p 
            LEFT JOIN productos_categorias pc ON p.id = pc.producto_id
            LEFT JOIN categorias c ON pc.categoria_id = c.id
            WHERE p.id = ?
            GROUP BY p.id";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error en la preparación de consulta: ' . mysqli_error($conn)]);
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product_data = mysqli_fetch_assoc($result);

    if (!$product_data) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Producto no encontrado']);
        exit();
    }

    // Obtener imágenes del producto
    $img_sql = "SELECT id, url, principal FROM imagenes_producto WHERE producto_id = ? ORDER BY principal DESC, id ASC";
    $img_stmt = mysqli_prepare($conn, $img_sql);
    
    if ($img_stmt) {
        mysqli_stmt_bind_param($img_stmt, "i", $product_id);
        mysqli_stmt_execute($img_stmt);
        $img_result = mysqli_stmt_get_result($img_stmt);
        
        $product_data['imagenes'] = [];
        while ($img = mysqli_fetch_assoc($img_result)) {
            $product_data['imagenes'][] = $img;
        }
    } else {
        // Si falla la consulta de imágenes, al menos devolver el producto sin imágenes
        $product_data['imagenes'] = [];
    }

    header('Content-Type: application/json');
    echo json_encode($product_data);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Productos | GreenFashion</title>
    <link rel="stylesheet" href="../../assets/css/admin/products.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../../index.php" class="sidebar-brand">
                    <img src="../../assets/images/logo.svg" alt="GreenFashion" class="logo">
                    <div class="brand-info">
                        <h1>GreenFashion</h1>
                        <p>Panel Administrativo</p>
                    </div>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">General</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="material-icons">dashboard</span>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <span class="material-icons">people</span>
                                Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="products.php" class="nav-link active">
                                <span class="material-icons">inventory_2</span>
                                Productos
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Ventas</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="material-icons">receipt_long</span>
                                Pedidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="material-icons">bar_chart</span>
                                Informes
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Configuración</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <span class="material-icons">settings</span>
                                Ajustes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../logout.php" class="nav-link">
                                <span class="material-icons">logout</span>
                                Cerrar sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Header del Panel -->
            <div class="panel-header">
                <h1 class="page-title">Gestión de Productos</h1>
                <div class="header-actions">
                    <form class="search-container" method="GET">
                        <span class="material-icons search-icon">search</span>
                        <input type="text" class="search-input" placeholder="Buscar productos..." 
                               name="search" id="searchInput"
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search" title="Buscar">
                            <span class="material-icons">search</span>
                        </button>
                        <?php if ($search): ?>
                            <a href="?" class="btn-clear-search" title="Limpiar búsqueda">
                                <span class="material-icons">close</span>
                            </a>
                        <?php endif; ?>
                    </form>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <span class="material-icons">add</span>
                        Añadir Producto
                    </button>
                    <div class="user-menu" onclick="toggleUserDropdown()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                        </div>
                        <div class="user-info-dropdown">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                            <span class="user-role"><?php echo ucfirst($_SESSION['rol']); ?></span>
                        </div>
                        <span class="material-icons dropdown-arrow">keyboard_arrow_down</span>
                        
                        <div id="userDropdown" class="user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-avatar-large">
                                    <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                                </div>
                                <div class="user-details-dropdown">
                                    <h4><?php echo htmlspecialchars($_SESSION['nombre']); ?></h4>
                                    <p><?php echo htmlspecialchars($_SESSION['email'] ?? 'No email'); ?></p>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#" class="dropdown-item" onclick="openProfileModal()">
                                        <span class="material-icons">person</span>
                                        Mi Perfil
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="dropdown-item" onclick="openPasswordModal()">
                                        <span class="material-icons">lock</span>
                                        Cambiar Contraseña
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="dropdown-item">
                                        <span class="material-icons">settings</span>
                                        Configuración
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <a href="../logout.php" class="dropdown-item logout">
                                        <span class="material-icons">logout</span>
                                        Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje === 'exito' ? 'success' : 'error'; ?>" id="alertMessage">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Panel de Contenido -->
            <div class="content-panel">
                <div class="panel-body">
                    <!-- Tabla de Productos -->
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categorías</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($productos) > 0): ?>
                                <?php while ($product = mysqli_fetch_assoc($productos)): ?>
                                    <tr class="product-row" onclick="showProductDetail(<?php echo $product['id']; ?>)" style="cursor: pointer;">
                                        <td>
                                            <div class="product-info">
                                                <div class="product-image">
                                                    <?php if ($product['imagen_principal']): ?>
                                                        <img src="../../<?php echo htmlspecialchars($product['imagen_principal']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['nombre']); ?>">
                                                    <?php else: ?>
                                                        <img src="https://via.placeholder.com/50x50/2E7D32/FFFFFF?text=<?php echo urlencode(substr($product['nombre'], 0, 1)); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['nombre']); ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="product-details">
                                                    <h4><?php echo htmlspecialchars($product['nombre']); ?></h4>
                                                    <p><?php echo htmlspecialchars(substr($product['descripcion'], 0, 50)) . '...'; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($product['categorias']): ?>
                                                <?php 
                                                $categorias = explode(', ', $product['categorias']);
                                                foreach ($categorias as $categoria): ?>
                                                    <span class="category-badge"><?php echo htmlspecialchars($categoria); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sin categoría</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="price">$<?php echo number_format($product['precio'], 2); ?></td>
                                        <td>
                                            <span class="stock-badge <?php echo $product['stock'] > 0 ? 'stock-available' : 'stock-empty'; ?>">
                                                <?php echo $product['stock']; ?> unidades
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-activo">Activo</span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($product['fecha_creacion'])); ?></td>
                                        <td>
                                            <div class="action-buttons" onclick="event.stopPropagation();">
                                                <button class="btn-action btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)" title="Editar">
                                                    <span class="material-icons">edit</span>
                                                </button>
                                                <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Eliminar">
                                                    <span class="material-icons">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-results">
                                        <div class="no-results-content">
                                            <span class="material-icons">inventory_2</span>
                                            <h3>No se encontraron productos</h3>
                                            <p>
                                                <?php if ($search): ?>
                                                    No hay productos que coincidan con la búsqueda "<?php echo htmlspecialchars($search); ?>"
                                                    <br>
                                                    <a href="?" class="btn btn-secondary">Limpiar búsqueda</a>
                                                <?php else: ?>
                                                    No hay productos registrados en el sistema
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div class="pagination">
                        <span class="pagination-info">
                            Mostrando <?php echo ($page - 1) * $limit + 1; ?> a <?php echo min($page * $limit, $total_products); ?> de <?php echo $total_products; ?> resultados
                        </span>
                        
                        <div class="pagination-nav">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-btn">
                                    <span class="material-icons">chevron_left</span>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-btn">
                                    <span class="material-icons">chevron_right</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para Detalle del Producto -->
    <div id="productDetailModal" class="modal">
        <div class="modal-content product-detail-modal">
            <div class="modal-header">
                <h2 class="modal-title">
                    <span class="material-icons">visibility</span>
                    Detalle del Producto
                </h2>
                <button class="close" onclick="closeDetailModal()" type="button">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="productDetailContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Vista Ampliada de Imagen -->
    <div id="imageViewerModal" class="modal image-viewer-modal">
        <div class="modal-content image-viewer-content">
            <div class="image-viewer-header">
                <div class="image-viewer-title">
                    <span class="material-icons">zoom_in</span>
                    Vista Ampliada
                </div>
                <button class="close" onclick="closeImageViewer()" type="button">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <div class="image-viewer-body">
                <div class="image-viewer-container">
                    <div class="image-viewer-slider" id="imageViewerSlider">
                        <!-- Las imágenes se cargarán dinámicamente -->
                    </div>
                    
                    <!-- Controles del viewer -->
                    <div class="viewer-controls">
                        <button class="viewer-btn" id="viewerPrevBtn" onclick="changeViewerSlide(-1)">
                            <span class="material-icons">chevron_left</span>
                        </button>
                        <button class="viewer-btn" id="viewerNextBtn" onclick="changeViewerSlide(1)">
                            <span class="material-icons">chevron_right</span>
                        </button>
                    </div>
                    
                    <!-- Indicadores del viewer -->
                    <div class="viewer-indicators" id="viewerIndicators">
                        <!-- Los indicadores se cargarán dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">
                    <span class="material-icons">add_circle</span>
                    Insertar Nuevo Producto
                </h2>
                <button class="close" onclick="closeModal()" type="button">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <form id="productForm" method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="temp_session" value="<?php echo session_id(); ?>">
                    
                    <!-- Información Básica del Producto -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-icons">info</span>
                            <h3>Información Básica del Producto</h3>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label" for="nombre">
                                Nombre del producto <span class="required">*</span>
                            </label>
                            <input type="text" class="form-input" id="nombre" name="nombre" 
                                   placeholder="Ingresa el nombre del producto" maxlength="90" required>
                            <small class="form-hint">Máximo 90 caracteres</small>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="descripcion">
                                Descripción <span class="required">*</span>
                            </label>
                            <textarea class="form-textarea" id="descripcion" name="descripcion" 
                                      placeholder="Descripción detallada del producto" rows="4" required></textarea>
                            <small class="form-hint">Descripción detallada del producto</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="precio">
                                    Precio <span class="required">*</span>
                                </label>
                                <input type="number" class="form-input" id="precio" name="precio" 
                                       placeholder="0.00" step="0.01" min="0" required>
                                <small class="form-hint">Formato: 00.00</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="stock">
                                    Stock <span class="required">*</span>
                                </label>
                                <input type="number" class="form-input" id="stock" name="stock" 
                                       placeholder="0" min="0" required>
                                <small class="form-hint">Cantidad disponible</small>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Sostenibilidad -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-icons">eco</span>
                            <h3>Información de Sostenibilidad</h3>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="materiales">
                                Materiales <span class="required">*</span>
                            </label>
                            <textarea class="form-textarea" id="materiales" name="materiales" 
                                      placeholder="Descripción de materiales ecológicos utilizados" rows="3" required></textarea>
                            <small class="form-hint">Descripción de materiales ecológicos utilizados</small>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="impacto_ambiental">
                                Impacto Ambiental <span class="required">*</span>
                            </label>
                            <textarea class="form-textarea" id="impacto_ambiental" name="impacto_ambiental" 
                                      placeholder="Información sobre el impacto ambiental del producto" rows="3" required></textarea>
                            <small class="form-hint">Información sobre el impacto ambiental del producto</small>
                        </div>
                    </div>

                    <!-- Categorización -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-icons">category</span>
                            <h3>Categorización</h3>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">
                                Categorías <span class="required">*</span>
                            </label>
                            <small class="form-hint">Selecciona al menos una categoría</small>
                            
                            <div class="categories-grid">
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Camisetas">
                                    <span class="checkmark"></span>
                                    Camisetas
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Pantalones">
                                    <span class="checkmark"></span>
                                    Pantalones
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Vestidos">
                                    <span class="checkmark"></span>
                                    Vestidos
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Accesorios">
                                    <span class="checkmark"></span>
                                    Accesorios
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Calzado">
                                    <span class="checkmark"></span>
                                    Calzado
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Abrigos">
                                    <span class="checkmark"></span>
                                    Abrigos
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Ropa Interior">
                                    <span class="checkmark"></span>
                                    Ropa Interior
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categorias[]" value="Ropa Deportiva">
                                    <span class="checkmark"></span>
                                    Ropa Deportiva
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Imágenes -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-icons">image</span>
                            <h3>Gestión de Imágenes</h3>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">
                                Imágenes del Producto <span class="required">*</span>
                            </label>
                            
                            <!-- Estado de imágenes -->
                            <div class="images-status" id="imageStatus">
                                <span class="material-icons">photo_library</span>
                                <span id="statusText">Sin imágenes seleccionadas</span>
                            </div>
                            
                            <!-- Mensajes de validación -->
                            <div id="validationErrors" class="validation-errors"></div>
                            
                            <div class="product-images-container">
                                <!-- Lista de imágenes -->
                                <div id="imagesList" class="images-list"></div>
                                
                                <!-- Zona de subida -->
                                <div class="drop-zone" id="dropZone">
                                    <div class="drop-zone-content">
                                        <span class="material-icons">cloud_upload</span>
                                        <p>Arrastra imágenes aquí o haz clic para seleccionar</p>
                                        <p class="upload-info">JPG, PNG • Máximo 500KB • Hasta 5 imágenes</p>
                                        <input type="file" id="fileInput" name="images[]" multiple accept="image/jpeg,image/png" style="display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Producto -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-icons">toggle_on</span>
                            <h3>Estado del Producto</h3>
                        </div>

                        <div class="form-group full-width">
                            <div class="toggle-container">
                                <label class="toggle-label">
                                    <input type="checkbox" name="activo" id="activo" checked>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-text">Estado Activo</span>
                                </label>
                                <small class="form-hint">Los productos activos se muestran en la tienda</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="material-icons">save</span>
                            Guardar Producto
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Verificar que los elementos principales existen al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const requiredElements = ['productModal', 'productForm'];
            const missing = requiredElements.filter(id => !document.getElementById(id));
            
            if (missing.length > 0) {
                console.error('Elementos principales faltantes:', missing);
                alert(`Error crítico: Elementos faltantes: ${missing.join(', ')}`);
                return;
            }
            
            console.log('Todos los elementos principales cargados correctamente');
        });

        const modal = document.getElementById('productModal');
        const productForm = document.getElementById('productForm');
        const uploadStatus = document.getElementById('uploadStatus');
        
        function closeModal() {
            if (!modal) {
                console.error('Modal no encontrado');
                return;
            }
            
            modal.style.display = 'none';
            
            if (productForm) {
                productForm.reset();
            }
            
            // Limpiar URLs de preview para liberar memoria
            if (currentImages && Array.isArray(currentImages)) {
                currentImages.forEach(img => {
                    if (img.preview_url) {
                        URL.revokeObjectURL(img.preview_url);
                    }
                });
            }
            
            // Limpiar estado de imágenes
            currentImages = [];
            
            // Limpiar contenedores de imágenes
            const imagesList = document.getElementById('imagesList');
            if (imagesList) imagesList.innerHTML = '';
        }

        function openCreateModal() {
            // Verificar que los elementos básicos existen
            const basicElements = {
                modalTitle: document.getElementById('modalTitle'),
                formAction: document.getElementById('formAction'),
                submitBtn: document.getElementById('submitBtn'),
                productId: document.getElementById('productId')
            };

            const missingBasicElements = Object.keys(basicElements).filter(key => !basicElements[key]);
            if (missingBasicElements.length > 0) {
                console.error(`Elementos faltantes en el DOM: ${missingBasicElements.join(', ')}`);
                alert(`Error: Elementos faltantes en el modal: ${missingBasicElements.join(', ')}`);
                return;
            }

            basicElements.modalTitle.innerHTML = '<span class="material-icons">add_circle</span> Insertar Nuevo Producto';
            basicElements.formAction.value = 'create';
            basicElements.submitBtn.innerHTML = '<span class="material-icons">save</span> Guardar Producto';
            basicElements.productId.value = '';
            
            // Limpiar URLs de preview para liberar memoria
            currentImages.forEach(img => {
                if (img.preview_url) {
                    URL.revokeObjectURL(img.preview_url);
                }
            });
            
            // Limpiar estado de imágenes
            currentImages = [];
            window.imagesToDelete = []; // Resetear lista de eliminación
            
            // Limpiar contenedores de imágenes
            const imagesList = document.getElementById('imagesList');
            if (imagesList) imagesList.innerHTML = '';
            
            // Limpiar imágenes temporales del servidor
            clearTempImages();
            
            // Actualizar estado inicial
            updateImageStatus();
            
            modal.style.display = 'block';
        }

        async function editProduct(id) {
            try {
                console.log('Cargando producto ID:', id);
                const response = await fetch(`?get_product=${id}`);
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let productData;
                try {
                    productData = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('Error parsing JSON:', jsonError);
                    throw new Error('Respuesta del servidor no es válida');
                }

                console.log('Product data:', productData);

                // Verificar si hay error en la respuesta del servidor
                if (productData.error) {
                    throw new Error(`Error del servidor: ${productData.error}`);
                }

                if (!productData || !productData.id) {
                    throw new Error('Producto no encontrado o datos incompletos');
                }

                // Verificar que todos los elementos existen antes de modificarlos
                const elements = {
                    modalTitle: document.getElementById('modalTitle'),
                    formAction: document.getElementById('formAction'),
                    submitBtn: document.getElementById('submitBtn'),
                    productId: document.getElementById('productId'),
                    nombre: document.getElementById('nombre'),
                    descripcion: document.getElementById('descripcion'),
                    precio: document.getElementById('precio'),
                    stock: document.getElementById('stock'),
                    materiales: document.getElementById('materiales'),
                    impacto_ambiental: document.getElementById('impacto_ambiental'),
                    activo: document.getElementById('activo')
                };

                // Verificar que todos los elementos existen
                const missingElements = Object.keys(elements).filter(key => !elements[key]);
                if (missingElements.length > 0) {
                    throw new Error(`Elementos faltantes en el DOM: ${missingElements.join(', ')}`);
                }

                elements.modalTitle.innerHTML = '<span class="material-icons">edit</span> Editar Producto';
                elements.formAction.value = 'update';
                elements.submitBtn.innerHTML = '<span class="material-icons">save</span> Actualizar Producto';
                
                elements.productId.value = productData.id;
                elements.nombre.value = productData.nombre || '';
                elements.descripcion.value = productData.descripcion || '';
                elements.precio.value = productData.precio || '';
                elements.stock.value = productData.stock || '';
                elements.materiales.value = productData.materiales || '';
                elements.impacto_ambiental.value = productData.impacto_ambiental || '';
                elements.activo.checked = productData.activo == 1;

                // Marcar categorías
                const categorias = productData.categorias ? productData.categorias.split(',') : [];
                document.querySelectorAll('input[name="categorias[]"]').forEach(checkbox => {
                    checkbox.checked = categorias.includes(checkbox.value);
                });

                // Cargar imágenes existentes del producto
                currentImages = [];
                window.imagesToDelete = [];
                
                if (productData.imagenes && Array.isArray(productData.imagenes) && productData.imagenes.length > 0) {
                    currentImages = productData.imagenes.map((imagen) => {
                        return {
                            id: imagen.id,
                            filename: imagen.url.split('/').pop(),
                            original_name: imagen.url.split('/').pop(),
                            is_principal: imagen.principal == 1,
                            is_existing: true,
                            url: imagen.url
                        };
                    });
                }

                console.log('Current images:', currentImages);

                // Actualizar display de imágenes
                updateImageDisplay();
                updateImageStatus();

                modal.style.display = 'block';
            } catch (error) {
                console.error('Error completo:', error);
                alert(`Error al cargar los datos del producto: ${error.message}`);
            }
        }

        function deleteProduct(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Funcionalidad del dropdown de usuario
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            const arrow = document.querySelector('.dropdown-arrow');
            
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            } else {
                dropdown.style.display = 'block';
                arrow.style.transform = 'rotate(180deg)';
            }
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (!userMenu.contains(event.target)) {
                dropdown.style.display = 'none';
                document.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
            }
        });

        // Funciones del perfil
        function openProfileModal() {
            alert('Funcionalidad de perfil - Por implementar');
            document.getElementById('userDropdown').style.display = 'none';
            document.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
        }

        function openPasswordModal() {
            alert('Funcionalidad para cambiar contraseña - Por implementar');
            document.getElementById('userDropdown').style.display = 'none';
            document.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
        }

        // Gestión de imágenes y validación de formulario

        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        dropZone.addEventListener('click', (e) => {
            if (!dropZone.classList.contains('hidden')) {
                fileInput.click();
            }
        });

        // Array para mantener el estado de las imágenes subidas
        let currentImages = [];

        // Función para actualizar el estado de las imágenes
        function updateImageStatus() {
            const statusElement = document.getElementById('statusText');
            const dropZone = document.getElementById('dropZone');
            const count = currentImages.length;
            
            if (count === 0) {
                statusElement.textContent = 'Sin imágenes seleccionadas';
                dropZone.classList.remove('hidden');
            } else if (count === 5) {
                statusElement.textContent = `${count} imágenes (límite alcanzado)`;
                dropZone.classList.add('hidden');
            } else {
                const principal = currentImages.find(img => img.is_principal);
                const principalText = principal ? ' • 1 principal' : '';
                statusElement.textContent = `${count} imagen${count !== 1 ? 'es' : ''}${principalText} • ${5 - count} restante${5 - count !== 1 ? 's' : ''}`;
                dropZone.classList.remove('hidden');
            }
        }

        // Función para mostrar errores de validación
        function showValidationError(message, isSuccess = false) {
            const errorsDiv = document.getElementById('validationErrors');
            const dropZone = document.getElementById('dropZone');
            
            // Limpiar clases anteriores
            errorsDiv.className = 'validation-errors';
            dropZone.classList.remove('error', 'success');
            
            // Agregar nuevas clases
            errorsDiv.classList.add('show', isSuccess ? 'success' : 'error');
            dropZone.classList.add(isSuccess ? 'success' : 'error');
            
            // Actualizar mensaje
            errorsDiv.textContent = message;
            
            setTimeout(() => {
                errorsDiv.classList.remove('show');
                dropZone.classList.remove('error', 'success');
            }, 3000);
        }

        function handleFiles(files) {
            if (!files || files.length === 0) {
                showValidationError('❌ Por favor, selecciona al menos una imagen', false);
                return;
            }

            // Validar límite de imágenes
            const totalAfterUpload = currentImages.length + files.length;
            if (totalAfterUpload > 5) {
                showValidationError(`❌ Límite excedido: intentas subir ${files.length} imagen(es) pero solo puedes tener ${5 - currentImages.length} más`, false);
                return;
            }

            let validFiles = [];
            let errors = [];

            // Validar cada archivo
            Array.from(files).forEach((file) => {
                const allowedTypes = ['image/jpeg', 'image/png'];
                const extension = file.name.toLowerCase().split('.').pop();
                const isTypeValid = allowedTypes.includes(file.type) && ['jpg', 'jpeg', 'png'].includes(extension);
                const isSizeValid = file.size <= 500 * 1024 && file.size >= 1024;

                if (!isTypeValid) {
                    errors.push(`"${file.name}": Solo se permiten archivos JPG y PNG`);
                } else if (!isSizeValid) {
                    const sizeKB = Math.round(file.size / 1024);
                    errors.push(`"${file.name}": ${sizeKB}KB (máximo 500KB)`);
                } else {
                    validFiles.push(file);
                }
            });

            if (errors.length > 0) {
                showValidationError(`❌ ${errors.join(', ')}`, false);
                return;
            }

            if (validFiles.length === 0) {
                showValidationError('❌ No hay archivos válidos para subir', false);
                return;
            }

            // Crear previews inmediatas
            validFiles.forEach((file, index) => {
                const imageData = {
                    id: 'temp_' + Date.now() + '_' + index,
                    filename: file.name,
                    original_name: file.name,
                    is_principal: currentImages.length === 0 && index === 0, // Solo la primera imagen si no hay otras
                    is_existing: false,
                    preview_url: URL.createObjectURL(file),
                    temp_path: null, // Se llenará cuando se suba al servidor
                    file: file
                };
                currentImages.push(imageData);
            });

            updateImageDisplay();
            updateImageStatus();
            fileInput.value = '';

            // Subir archivos al servidor
            const formData = new FormData();
            validFiles.forEach(file => formData.append('images[]', file));

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar datos del servidor
                    data.files.forEach((serverFile, index) => {
                        const localIndex = currentImages.length - validFiles.length + index;
                        if (currentImages[localIndex]) {
                            currentImages[localIndex].temp_path = serverFile.temp_path;
                            currentImages[localIndex].id = serverFile.id;
                        }
                    });
                    
                    showValidationError(`✅ ${data.files.length} imagen(es) subidas correctamente`, true);
                } else {
                    // Remover imágenes que fallaron
                    currentImages = currentImages.slice(0, -validFiles.length);
                    updateImageDisplay();
                    updateImageStatus();
                    showValidationError(`❌ Error: ${data.error}`, false);
                }
            })
            .catch(error => {
                currentImages = currentImages.slice(0, -validFiles.length);
                updateImageDisplay();
                updateImageStatus();
                showValidationError('❌ Error de conexión', false);
            });
        }

        function updateImageDisplay() {
            const imagesList = document.getElementById('imagesList');
            
            // Verificar que el elemento existe
            if (!imagesList) {
                console.error('Element imagesList not found');
                return;
            }
            
            // Limpiar contenedor de imágenes
            imagesList.innerHTML = '';
            
            if (currentImages.length > 0) {
                // Crear items de imagen
                currentImages.forEach((file, index) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = `image-item ${file.is_principal ? 'is-principal' : ''}`;
                    itemDiv.setAttribute('data-index', index);
                    
                    // Determinar la URL de la imagen
                    let imageUrl = '';
                    
                    if (file.is_existing) {
                        // Imagen existente - construir URL normal
                        imageUrl = file.url.startsWith('uploads/') ? 
                            '../../' + file.url : 
                            '../../uploads/products/' + (document.getElementById('productId').value || 'unknown') + '/' + file.url;
                    } else if (file.preview_url) {
                        // Imagen nueva con preview - usar URL temporal
                        imageUrl = file.preview_url;
                    }
                    
                    // Crear y agregar la imagen
                    const img = document.createElement('img');
                    // Si es una imagen existente o tiene URL del servidor, usar esa URL
                    if (file.is_existing && file.url) {
                        img.src = file.url.startsWith('uploads/') ? 
                            '../../' + file.url : 
                            '../../uploads/products/' + (document.getElementById('productId').value || 'temp') + '/' + file.url;
                    } 
                    // Si es una preview temporal, usar la URL de preview
                    else if (file.preview_url) {
                        img.src = file.preview_url;
                    }
                    // Si tiene URL temporal del servidor
                    else if (file.temp_path) {
                        img.src = '../../' + file.temp_path.replace('../../', '');
                    }
                    
                    img.alt = 'Vista previa';
                    img.onerror = function() {
                        this.style.display = 'none';
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'image-error';
                        errorDiv.innerHTML = '<span class="material-icons">broken_image</span>';
                        this.parentNode.appendChild(errorDiv);
                    };
                    itemDiv.appendChild(img);
                    
                    // Inputs hidden solo para imágenes existentes
                    if (file.is_existing) {
                        itemDiv.appendChild(createHiddenInput('existing_images[]', file.id));
                        itemDiv.appendChild(createHiddenInput('existing_principal[]', file.is_principal ? 1 : 0));
                    }
                    
                    // Crear acciones (solo si no está subiendo)
                    if (!file.is_uploading) {
                        const actionsDiv = document.createElement('div');
                        actionsDiv.className = 'image-actions';
                        
                        // Botón principal
                        if (!file.is_principal) {
                            actionsDiv.appendChild(createActionButton(
                                'make-principal',
                                'star_outline',
                                'Hacer Principal',
                                () => makePrincipalTemp(index)
                            ));
                        }
                        
                        // Botón eliminar
                        actionsDiv.appendChild(createActionButton(
                            'delete',
                            'delete',
                            'Eliminar',
                            () => removeTempImage(index)
                        ));
                        
                        itemDiv.appendChild(actionsDiv);
                    }
                    
                    imagesList.appendChild(itemDiv);
                });
            }
        }
        
        // Función auxiliar para crear inputs hidden
        function createHiddenInput(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
        }
        
        // Función auxiliar para crear botones de acción
        function createActionButton(className, icon, title, onClick) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `image-action-btn ${className}`;
            button.title = title;
            button.onclick = onClick;
            button.innerHTML = `<span class="material-icons">${icon}</span>`;
            return button;
        }

                async function removeTempImage(index) {
            const image = currentImages[index];
            const imageType = image.is_existing ? 'existente' : 'nueva';
            
            if (confirm(`¿Eliminar esta imagen ${imageType}?`)) {
                if (image.is_existing) {
                    try {
                        // Hacer la petición para eliminar la imagen
                        const response = await fetch('delete_image.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                image_id: image.id,
                                product_id: document.getElementById('productId').value
                            })
                        });

                        const result = await response.json();
                        
                        if (!result.success) {
                            alert('Error al eliminar la imagen: ' + result.error);
                            return;
                        }
                    } catch (error) {
                        alert('Error al eliminar la imagen');
                        return;
                    }
                } else if (image.preview_url) {
                    // Liberar la URL del objeto para imágenes en preview
                    URL.revokeObjectURL(image.preview_url);
                }

                // Eliminar del array local
                currentImages.splice(index, 1);

                // Reordenar is_principal si eliminamos la principal
                if (currentImages.length > 0) {
                    let hasPrincipal = currentImages.some(img => img.is_principal);
                    if (!hasPrincipal) {
                        currentImages[0].is_principal = true;
                    }
                }
                
                updateImageDisplay();
                updateImageStatus();
            }
        }

        async function makePrincipalTemp(index) {
            const image = currentImages[index];
            
            if (image.is_existing && document.getElementById('productId').value) {
                try {
                    // Hacer la petición para cambiar la imagen principal
                    const response = await fetch('set_principal_image.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            image_id: image.id,
                            product_id: document.getElementById('productId').value
                        })
                    });

                    const result = await response.json();
                    
                    if (!result.success) {
                        alert('Error al cambiar la imagen principal: ' + result.error);
                        return;
                    }
                } catch (error) {
                    alert('Error al cambiar la imagen principal');
                    return;
                }
            }
            
            // Actualizar el estado local
            currentImages.forEach(img => img.is_principal = false);
            currentImages[index].is_principal = true;
            updateImageDisplay();
        }

        function showUploadStatus(message, type) {
            if (uploadStatus) {
                uploadStatus.textContent = message;
                uploadStatus.className = 'upload-status ' + type;
                
                // Auto-ocultar después de un tiempo (más tiempo para warnings debido a más texto)
                if (type === 'success') {
                    setTimeout(() => {
                        uploadStatus.textContent = '';
                        uploadStatus.className = 'upload-status';
                    }, 3000);
                } else if (type === 'warning') {
                    setTimeout(() => {
                        uploadStatus.textContent = '';
                        uploadStatus.className = 'upload-status';
                    }, 6000); // Más tiempo para leer warnings
                } else if (type === 'error') {
                    setTimeout(() => {
                        uploadStatus.textContent = '';
                        uploadStatus.className = 'upload-status';
                    }, 5000); // Más tiempo para leer errores
                }
            }
        }

        // Validación del formulario
        function validateProductForm() {
            const nombre = document.getElementById('nombre').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const precio = document.getElementById('precio').value;
            const stock = document.getElementById('stock').value;
            const materiales = document.getElementById('materiales').value.trim();
            const impacto_ambiental = document.getElementById('impacto_ambiental').value.trim();
            
            // Validar campos requeridos
            if (!nombre || !descripcion || !precio || !stock || !materiales || !impacto_ambiental) {
                alert('Por favor, completa todos los campos requeridos');
                return false;
            }

            // Validar precio y stock
            if (parseFloat(precio) <= 0) {
                alert('El precio debe ser mayor a 0');
                return false;
            }

            if (parseInt(stock) < 0) {
                alert('El stock no puede ser negativo');
                return false;
            }

            // Validar que al menos una categoría esté seleccionada
            const categorias = document.querySelectorAll('input[name="categorias[]"]:checked');
            if (categorias.length === 0) {
                alert('Selecciona al menos una categoría');
                return false;
            }

            return true;
        }

        // Manejar envío del formulario
        document.getElementById('productForm').addEventListener('submit', function(e) {
            if (!validateProductForm()) {
                e.preventDefault();
                return false;
            }
            
            // Agregar información de imágenes al formulario
            addImageDataToForm();
            
            // Si la validación es exitosa, el formulario se envía normalmente
        });

        function addImageDataToForm() {
            const form = document.getElementById('productForm');
            
            // Limpiar campos de imágenes previos
            form.querySelectorAll('.dynamic-image-field').forEach(field => field.remove());
            
            // Agregar imágenes a eliminar
            if (window.imagesToDelete && window.imagesToDelete.length > 0) {
                window.imagesToDelete.forEach(imageId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_images[]';
                    input.value = imageId;
                    input.className = 'dynamic-image-field';
                    form.appendChild(input);
                });
            }
            
            // Agregar imagen principal existente
            const existingPrincipal = currentImages.find(img => img.is_existing && img.is_principal);
            if (existingPrincipal) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'existing_principal_id';
                input.value = existingPrincipal.id;
                input.className = 'dynamic-image-field';
                form.appendChild(input);
            }

            // Agregar imágenes temporales (nuevas)
            const tempImages = currentImages.filter(img => !img.is_existing && img.temp_path);
            tempImages.forEach((img, index) => {
                // temp_path
                const pathInput = document.createElement('input');
                pathInput.type = 'hidden';
                pathInput.name = `temp_images[${index}][temp_path]`;
                pathInput.value = img.temp_path;
                pathInput.className = 'dynamic-image-field';
                form.appendChild(pathInput);

                // filename
                const nameInput = document.createElement('input');
                nameInput.type = 'hidden';
                nameInput.name = `temp_images[${index}][filename]`;
                nameInput.value = img.filename;
                nameInput.className = 'dynamic-image-field';
                form.appendChild(nameInput);

                // is_principal
                const principalInput = document.createElement('input');
                principalInput.type = 'hidden';
                principalInput.name = `temp_images[${index}][is_principal]`;
                principalInput.value = img.is_principal ? '1' : '0';
                principalInput.className = 'dynamic-image-field';
                form.appendChild(principalInput);
            });

            console.log('Imágenes temporales a enviar:', tempImages.length);
        }

        function removeImage(index) {
            // Esta función se puede implementar más tarde para remover imágenes específicas
            alert('Funcionalidad de remover imagen - Por implementar');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Búsqueda
        function searchProducts(query) {
            const url = new URL(window.location);
            if (query && query.trim()) {
                url.searchParams.set('search', query.trim());
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location = url;
        }
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts(this.value);
            }
        });

        // Manejar mensajes de éxito (auto-ocultar después de 5 segundos)
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alertMessage');
            if (alertMessage && alertMessage.classList.contains('alert-success')) {
                setTimeout(function() {
                    alertMessage.style.opacity = '0';
                    setTimeout(function() {
                        alertMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        });

        // Limpiar imágenes temporales al abrir modal de crear
        function clearTempImages() {
            // Hacer petición para limpiar imágenes temporales
            fetch('clear_temp_images.php', {
                method: 'POST'
            }).catch(error => {
                console.log('Error limpiando imágenes temporales:', error);
            });
        }

        // Funciones para el modal de detalle del producto
        const detailModal = document.getElementById('productDetailModal');
        const detailContent = document.getElementById('productDetailContent');

        function showProductDetail(productId) {
            // Mostrar loading
            detailContent.innerHTML = `
                <div class="loading-container">
                    <span class="material-icons loading-icon">hourglass_empty</span>
                    <p>Cargando detalles del producto...</p>
                </div>
            `;
            detailModal.style.display = 'block';

            // Hacer petición AJAX para obtener los datos del producto
            fetch(`get_product_detail.php?id=${productId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `Error ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayProductDetail(data.producto);
                    } else {
                        throw new Error(data.error || 'Error al cargar el producto');
                    }
                })
                .catch(error => {
                    console.error('Error en showProductDetail:', error);
                    detailContent.innerHTML = `
                        <div class="error-container">
                            <span class="material-icons error-icon">error</span>
                            <p>Error al cargar los detalles del producto</p>
                            <small>${error.message}</small>
                            <br><br>
                            <button onclick="showProductDetail(${productId})" class="btn btn-secondary">
                                <span class="material-icons">refresh</span>
                                Reintentar
                            </button>
                        </div>
                    `;
                });
        }

        // Función para formatear el precio en formato $45,000.00
        function formatPrecio(precio) {
            return '$' + Number(precio).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function displayProductDetail(producto) {
            const categoriasHTML = producto.categorias.length > 0 
                ? producto.categorias.map(cat => `<span class="category-badge">${cat}</span>`).join('')
                : '<span class="text-muted">Sin categoría</span>';

            // Generar HTML del slider
            let sliderHTML = '';
            if (producto.imagenes.length > 0) {
                const slidesHTML = producto.imagenes.map((img, index) => `
                    <div class="slide" data-index="${index}" onclick="openImageViewer(${index})">
                        <img src="${img.url}" alt="${producto.nombre}" onerror="this.style.display='none'">
                        ${img.principal ? '<div class="principal-star"><span class="material-icons">star</span></div>' : ''}
                    </div>
                `).join('');

                const indicatorsHTML = producto.imagenes.map((_, index) => `
                    <div class="indicator ${index === 0 ? 'active' : ''}" data-index="${index}"></div>
                `).join('');

                sliderHTML = `
                    <div class="image-slider-container">
                        <div class="image-slider" id="imageSlider">
                            ${slidesHTML}
                        </div>
                        
                        <!-- Controles del slider -->
                        <div class="slider-controls">
                            <button class="slider-btn" id="prevBtn" onclick="changeSlide(-1)">
                                <span class="material-icons">chevron_left</span>
                            </button>
                            <button class="slider-btn" id="nextBtn" onclick="changeSlide(1)">
                                <span class="material-icons">chevron_right</span>
                            </button>
                        </div>
                        
                        <!-- Indicadores -->
                        <div class="slider-indicators">
                            ${indicatorsHTML}
                        </div>
                    </div>
                `;
            } else {
                sliderHTML = '<div class="no-images"><span class="material-icons">image_not_supported</span><p>Sin imágenes</p></div>';
            }

            detailContent.innerHTML = `
                <div class="product-detail-container">
                    <!-- Header compacto -->
                    <div class="product-detail-header">
                        <h3 class="product-title">${producto.nombre}</h3>
                        <div class="product-status">
                            <span class="status-badge">
                                ${producto.activo ? 'Activo' : 'Inactivo'}
                            </span>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="product-detail-content">
                        <!-- Galería con slider -->
                        <div class="product-images-gallery">
                            <div class="gallery-title">
                                <span class="material-icons">photo_library</span>
                                Imágenes
                            </div>
                            ${sliderHTML}
                        </div>

                        <!-- Información compacta -->
                        <div class="product-detail-info">
                            <!-- Metadatos compactos -->
                            <div class="product-meta-section">
                                <div class="meta-item">
                                    <span class="meta-label">Precio</span>
                                    <span class="meta-value price">${formatPrecio(producto.precio)}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Stock</span>
                                    <span class="meta-value stock-badge ${producto.stock > 0 ? 'stock-available' : 'stock-empty'}">
                                        ${producto.stock}
                                    </span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Fecha</span>
                                    <span class="meta-value">${new Date(producto.fecha_creacion).toLocaleDateString('es-ES')}</span>
                                </div>
                            </div>

                            <!-- Categorías compactas -->
                            <div class="product-categories">
                                <div class="categories-title">
                                    <span class="material-icons">category</span>
                                    Categorías
                                </div>
                                <div class="categories-container">
                                    ${categoriasHTML}
                                </div>
                            </div>

                            <!-- Descripción compacta -->
                            <div class="product-description">
                                <div class="description-title">
                                    <span class="material-icons">description</span>
                                    Descripción
                                </div>
                                <p class="description-text">${producto.descripcion}</p>
                            </div>

                            <!-- Sostenibilidad compacta -->
                            <div class="product-sustainability">
                                <div class="sustainability-section">
                                    <div class="sustainability-section-title">Materiales</div>
                                    <p class="sustainability-text">${producto.materiales}</p>
                                </div>
                                <div class="sustainability-section">
                                    <div class="sustainability-section-title">Impacto Ambiental</div>
                                    <p class="sustainability-text">${producto.impacto_ambiental}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Inicializar el slider si hay imágenes
            if (producto.imagenes.length > 1) {
                initializeSlider();
            }
        }

        function closeDetailModal() {
            detailModal.style.display = 'none';
            detailContent.innerHTML = '';
        }

        // Cerrar modal de detalle al hacer clic fuera
        window.addEventListener('click', function(event) {
            if (event.target === detailModal) {
                closeDetailModal();
            }
        });

        // Variables globales para el slider
        let currentSlide = 0;
        let slideInterval;
        let totalSlides = 0;

        // Función para inicializar el slider
        function initializeSlider() {
            const slider = document.getElementById('imageSlider');
            const indicators = document.querySelectorAll('.indicator');
            
            if (!slider || indicators.length === 0) return;
            
            totalSlides = indicators.length;
            currentSlide = 0;
            
            // Configurar indicadores clickeables
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    goToSlide(index);
                });
            });
            
            // Iniciar auto-slide
            startAutoSlide();
            
            // Pausar auto-slide al hacer hover
            const sliderContainer = document.querySelector('.image-slider-container');
            if (sliderContainer) {
                sliderContainer.addEventListener('mouseenter', pauseAutoSlide);
                sliderContainer.addEventListener('mouseleave', startAutoSlide);
            }
        }

        // Función para cambiar slide
        function changeSlide(direction) {
            const newSlide = currentSlide + direction;
            
            if (newSlide < 0) {
                goToSlide(totalSlides - 1);
            } else if (newSlide >= totalSlides) {
                goToSlide(0);
            } else {
                goToSlide(newSlide);
            }
        }

        // Función para ir a un slide específico
        function goToSlide(index) {
            const slider = document.getElementById('imageSlider');
            const indicators = document.querySelectorAll('.indicator');
            
            if (!slider || index < 0 || index >= totalSlides) return;
            
            currentSlide = index;
            
            // Actualizar posición del slider
            slider.style.transform = `translateX(-${index * 100}%)`;
            
            // Actualizar indicadores
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
            
            // Actualizar estado de los botones
            updateSliderButtons();
            
            // Reiniciar auto-slide
            restartAutoSlide();
        }

        // Función para actualizar estado de los botones
        function updateSliderButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (prevBtn) prevBtn.disabled = false;
            if (nextBtn) nextBtn.disabled = false;
        }

        // Función para iniciar auto-slide
        function startAutoSlide() {
            if (totalSlides <= 1) return;
            
            slideInterval = setInterval(() => {
                changeSlide(1);
            }, 3000); // Cambiar cada 3 segundos
        }

        // Función para pausar auto-slide
        function pauseAutoSlide() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
            }
        }

        // Función para reiniciar auto-slide
        function restartAutoSlide() {
            pauseAutoSlide();
            startAutoSlide();
        }

        // Limpiar intervalos al cerrar el modal
        function closeDetailModal() {
            pauseAutoSlide();
            detailModal.style.display = 'none';
            detailContent.innerHTML = '';
            currentSlide = 0;
            totalSlides = 0;
        }

        // Variables globales para el viewer de imágenes
        let currentViewerSlide = 0;
        let totalViewerSlides = 0;
        let currentProductImages = [];

        // Función para abrir el viewer de imágenes
        function openImageViewer(startIndex = 0) {
            const imageViewerModal = document.getElementById('imageViewerModal');
            const imageViewerSlider = document.getElementById('imageViewerSlider');
            const viewerIndicators = document.getElementById('viewerIndicators');
            
            if (!imageViewerModal || !imageViewerSlider) return;
            
            // Obtener las imágenes del producto actual
            currentProductImages = Array.from(document.querySelectorAll('.slide img')).map(img => ({
                src: img.src,
                alt: img.alt,
                isPrincipal: img.parentElement.querySelector('.principal-star') !== null
            }));
            
            if (currentProductImages.length === 0) return;
            
            totalViewerSlides = currentProductImages.length;
            currentViewerSlide = startIndex;
            
            // Generar HTML del slider del viewer
            const viewerSlidesHTML = currentProductImages.map((img, index) => `
                <div class="viewer-slide" data-index="${index}">
                    <img src="${img.src}" alt="${img.alt}">
                    ${img.isPrincipal ? '<div class="viewer-principal-star"><span class="material-icons">star</span></div>' : ''}
                </div>
            `).join('');
            
            // Generar indicadores del viewer
            const viewerIndicatorsHTML = currentProductImages.map((_, index) => `
                <div class="viewer-indicator ${index === startIndex ? 'active' : ''}" data-index="${index}"></div>
            `).join('');
            
            imageViewerSlider.innerHTML = viewerSlidesHTML;
            viewerIndicators.innerHTML = viewerIndicatorsHTML;
            
            // Configurar indicadores clickeables
            const indicators = viewerIndicators.querySelectorAll('.viewer-indicator');
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    goToViewerSlide(index);
                });
            });
            
            // Mostrar el modal y ir al slide inicial
            imageViewerModal.classList.add('active');
            goToViewerSlide(startIndex);
            
            // Configurar navegación con teclado
            document.addEventListener('keydown', handleViewerKeydown);
        }

        // Función para cerrar el viewer
        function closeImageViewer() {
            const imageViewerModal = document.getElementById('imageViewerModal');
            if (imageViewerModal) {
                imageViewerModal.classList.remove('active');
            }
            
            // Remover event listener del teclado
            document.removeEventListener('keydown', handleViewerKeydown);
        }

        // Función para cambiar slide en el viewer
        function changeViewerSlide(direction) {
            const newSlide = currentViewerSlide + direction;
            
            if (newSlide < 0) {
                goToViewerSlide(totalViewerSlides - 1);
            } else if (newSlide >= totalViewerSlides) {
                goToViewerSlide(0);
            } else {
                goToViewerSlide(newSlide);
            }
        }

        // Función para ir a un slide específico en el viewer
        function goToViewerSlide(index) {
            const imageViewerSlider = document.getElementById('imageViewerSlider');
            const indicators = document.querySelectorAll('.viewer-indicator');
            
            if (!imageViewerSlider || index < 0 || index >= totalViewerSlides) return;
            
            currentViewerSlide = index;
            
            // Actualizar posición del slider
            imageViewerSlider.style.transform = `translateX(-${index * 100}%)`;
            
            // Actualizar indicadores
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
            
            // Actualizar estado de los botones
            updateViewerButtons();
        }

        // Función para actualizar estado de los botones del viewer
        function updateViewerButtons() {
            const prevBtn = document.getElementById('viewerPrevBtn');
            const nextBtn = document.getElementById('viewerNextBtn');
            
            if (prevBtn) prevBtn.disabled = false;
            if (nextBtn) nextBtn.disabled = false;
        }

        // Función para manejar navegación con teclado
        function handleViewerKeydown(event) {
            switch (event.key) {
                case 'ArrowLeft':
                    event.preventDefault();
                    changeViewerSlide(-1);
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    changeViewerSlide(1);
                    break;
                case 'Escape':
                    event.preventDefault();
                    closeImageViewer();
                    break;
            }
        }

        // Cerrar viewer al hacer clic fuera
        window.addEventListener('click', function(event) {
            const imageViewerModal = document.getElementById('imageViewerModal');
            if (event.target === imageViewerModal) {
                closeImageViewer();
            }
        });
    </script>
</body>
</html> 