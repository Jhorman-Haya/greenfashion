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

// Manejar mensajes de éxito desde redirecciones
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

// Procesar formularios
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

                // Insertar producto
                $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, materiales, impacto_ambiental, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssdissi", $nombre, $descripcion, $precio, $stock, $materiales, $impacto_ambiental, $activo);
                
                if (mysqli_stmt_execute($stmt)) {
                    $producto_id = mysqli_insert_id($conn);

                    // Procesar imágenes temporales
                    if (isset($_SESSION['temp_images']) && !empty($_SESSION['temp_images'])) {
                        $upload_dir = '../../uploads/products/' . $producto_id . '/';
                        
                        // Crear directorio si no existe
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        // Verificar si ya existe una imagen principal
                        $check_principal_sql = "SELECT COUNT(*) as count FROM imagenes_producto WHERE producto_id = ? AND principal = 1";
                        $check_stmt = mysqli_prepare($conn, $check_principal_sql);
                        mysqli_stmt_bind_param($check_stmt, "i", $producto_id);
                        mysqli_stmt_execute($check_stmt);
                        $check_result = mysqli_stmt_get_result($check_stmt);
                        $has_principal = mysqli_fetch_assoc($check_result)['count'] > 0;

                        // Si no hay principal, la primera imagen será principal
                        $first_image = true;

                        foreach ($_SESSION['temp_images'] as $image) {
                            $temp_path = $image['temp_path'];
                            $filename = $image['filename'];
                            
                            if (file_exists($temp_path)) {
                                $final_path = $upload_dir . $filename;
                                
                                if (rename($temp_path, $final_path)) {
                                    // Determinar si esta imagen será principal
                                    $is_principal = (!$has_principal && $first_image) ? 1 : 0;
                                    
                                    // Guardar referencia en la base de datos
                                    $img_sql = "INSERT INTO imagenes_producto (producto_id, url, principal) VALUES (?, ?, ?)";
                                    $img_stmt = mysqli_prepare($conn, $img_sql);
                                    $relative_path = 'uploads/products/' . $producto_id . '/' . $filename;
                                    mysqli_stmt_bind_param($img_stmt, "isi", $producto_id, $relative_path, $is_principal);
                                    
                                    if (mysqli_stmt_execute($img_stmt)) {
                                        if ($is_principal) {
                                            $has_principal = true; // Ya tenemos una principal
                                        }
                                    } else {
                                        $mensaje = "Error al guardar la referencia de la imagen en la base de datos";
                                        $tipo_mensaje = "error";
                                    }
                                } else {
                                    $mensaje = "Error al mover la imagen a su ubicación final";
                                    $tipo_mensaje = "error";
                                }
                            }
                            
                            $first_image = false; // Ya no es la primera imagen
                        }

                        // Limpiar directorio temporal y sesión
                        array_map('unlink', glob('../../uploads/temp/' . session_id() . '/*.*'));
                        rmdir('../../uploads/temp/' . session_id());
                        unset($_SESSION['temp_images']);
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
                    if (isset($_SESSION['temp_images']) && !empty($_SESSION['temp_images'])) {
                        $upload_dir = '../../uploads/products/' . $id . '/';
                        
                        // Crear directorio si no existe
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        foreach ($_SESSION['temp_images'] as $image) {
                            $temp_path = $image['temp_path'];
                            $filename = $image['filename'];
                            $is_principal = $image['is_principal'];
                            
                            if (file_exists($temp_path)) {
                                $final_path = $upload_dir . $filename;
                                
                                // Si es la imagen principal, quitar la marca de principal de las otras
                                if ($is_principal) {
                                    $update_principal_sql = "UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?";
                                    $update_principal_stmt = mysqli_prepare($conn, $update_principal_sql);
                                    mysqli_stmt_bind_param($update_principal_stmt, "i", $id);
                                    mysqli_stmt_execute($update_principal_stmt);
                                }
                                
                                if (rename($temp_path, $final_path)) {
                                    // Guardar referencia en la base de datos
                                    $img_sql = "INSERT INTO imagenes_producto (producto_id, url, principal) VALUES (?, ?, ?)";
                                    $img_stmt = mysqli_prepare($conn, $img_sql);
                                    $relative_path = 'uploads/products/' . $id . '/' . $filename;
                                    mysqli_stmt_bind_param($img_stmt, "isi", $id, $relative_path, $is_principal);
                                    
                                    if (!mysqli_stmt_execute($img_stmt)) {
                                        $mensaje = "Error al guardar la referencia de la imagen en la base de datos";
                                        $tipo_mensaje = "error";
                                    }
                                } else {
                                    $mensaje = "Error al mover la imagen a su ubicación final";
                                    $tipo_mensaje = "error";
                                }
                            }
                        }

                        // Limpiar directorio temporal y sesión
                        $temp_dir = '../../uploads/temp/' . session_id() . '/';
                        if (file_exists($temp_dir)) {
                            array_map('unlink', glob($temp_dir . '*.*'));
                            rmdir($temp_dir);
                        }
                        unset($_SESSION['temp_images']);
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
    
    // Obtener datos básicos del producto y categorías
    $sql = "SELECT p.*, GROUP_CONCAT(c.nombre) as categorias FROM productos p 
            LEFT JOIN productos_categorias pc ON p.id = pc.producto_id
            LEFT JOIN categorias c ON pc.categoria_id = c.id
            WHERE p.id = ? AND p.activo = TRUE
            GROUP BY p.id";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product_data = mysqli_fetch_assoc($result);

    // Obtener imágenes del producto
    if ($product_data) {
        $img_sql = "SELECT id, url, principal FROM imagenes_producto WHERE producto_id = ? ORDER BY principal DESC, id ASC";
        $img_stmt = mysqli_prepare($conn, $img_sql);
        mysqli_stmt_bind_param($img_stmt, "i", $product_id);
        mysqli_stmt_execute($img_stmt);
        $img_result = mysqli_stmt_get_result($img_stmt);
        
        $product_data['imagenes'] = [];
        while ($img = mysqli_fetch_assoc($img_result)) {
            $product_data['imagenes'][] = $img;
        }
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
                                    <tr>
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
                                            <div class="action-buttons">
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
                            <small class="form-hint">Formatos permitidos: JPG, PNG, JPEG. Máximo 5 imágenes</small>
                            
                            <div class="product-images-container">
                                <!-- Área de drop -->
                                <div class="drop-zone" id="uploadArea">
                                    <span class="material-icons">cloud_upload</span>
                                    <p>Arrastra y suelta imágenes aquí o</p>
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('imageFiles').click()">
                                        Haz clic para seleccionar archivos
                                    </button>
                                    <input type="file" id="imageFiles" multiple accept="image/*" style="display: none;">
                                    <div class="upload-requirements">
                                        <small class="form-hint">
                                            <span class="material-icons">info</span>
                                            Máximo 5 imágenes por producto • 500KB máximo por imagen • JPG/PNG • La primera imagen será la portada
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Lista de imágenes -->
                                <div class="images-list" id="imagesList"></div>
                                
                                <!-- Contador de imágenes -->
                                <div class="images-counter" id="imagesCounter"></div>
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
        const modal = document.getElementById('productModal');
        const productForm = document.getElementById('productForm');
        const uploadStatus = document.getElementById('uploadStatus');
        
        function closeModal() {
            modal.style.display = 'none';
            productForm.reset();
            
            // Limpiar URLs de preview para liberar memoria
            currentImages.forEach(img => {
                if (img.preview_url) {
                    URL.revokeObjectURL(img.preview_url);
                }
            });
            
            // Limpiar estado de imágenes
            currentImages = [];
            
            // Limpiar contenedores de imágenes
            const imagesList = document.getElementById('imagesList');
            const imagesCounter = document.getElementById('imagesCounter');
            if (imagesList) imagesList.innerHTML = '';
            if (imagesCounter) imagesCounter.innerHTML = '';
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<span class="material-icons">add_circle</span> Insertar Nuevo Producto';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<span class="material-icons">save</span> Guardar Producto';
            document.getElementById('productId').value = '';
            
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
            const imagesCounter = document.getElementById('imagesCounter');
            if (imagesList) imagesList.innerHTML = '';
            if (imagesCounter) imagesCounter.innerHTML = '';
            
            // Limpiar imágenes temporales del servidor
            clearTempImages();
            
            modal.style.display = 'block';
        }

        async function editProduct(id) {
            try {
                const response = await fetch(`?get_product=${id}`);
                const productData = await response.json();

                document.getElementById('modalTitle').innerHTML = '<span class="material-icons">edit</span> Editar Producto';
                document.getElementById('formAction').value = 'update';
                document.getElementById('submitBtn').innerHTML = '<span class="material-icons">save</span> Actualizar Producto';
                
                document.getElementById('productId').value = productData.id;
                document.getElementById('nombre').value = productData.nombre;
                document.getElementById('descripcion').value = productData.descripcion;
                document.getElementById('precio').value = productData.precio;
                document.getElementById('stock').value = productData.stock;
                document.getElementById('materiales').value = productData.materiales || '';
                document.getElementById('impacto_ambiental').value = productData.impacto_ambiental || '';
                document.getElementById('activo').checked = productData.activo == 1;

                // Marcar categorías
                const categorias = productData.categorias ? productData.categorias.split(',') : [];
                document.querySelectorAll('input[name="categorias[]"]').forEach(checkbox => {
                    checkbox.checked = categorias.includes(checkbox.value);
                });

                // Cargar imágenes existentes del producto
                currentImages = [];
                window.imagesToDelete = []; // Resetear lista de eliminación
                
                if (productData.imagenes && productData.imagenes.length > 0) {
                    currentImages = productData.imagenes.map((imagen, index) => {
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

                // Actualizar display de imágenes
                updateImageDisplay();

                modal.style.display = 'block';
            } catch (error) {
                alert('Error al cargar los datos del producto');
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
        const uploadArea = document.getElementById('uploadArea');
        const imageFiles = document.getElementById('imageFiles');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        imageFiles.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        // Array para mantener el estado de las imágenes subidas
        let currentImages = [];

        function handleFiles(files) {
            // Verificar límite total de imágenes (máximo 5 por producto)
            const totalAfterUpload = currentImages.length + files.length;
            if (totalAfterUpload > 5) {
                showUploadStatus(`Error: Máximo 5 imágenes por producto. Actualmente tienes ${currentImages.length}, intentas subir ${files.length}`, 'error');
                return;
            }

            // Validar archivos antes de subir
            let validFiles = [];
            Array.from(files).forEach((file, index) => {
                if (!file.type.startsWith('image/')) {
                    showUploadStatus('Error: Solo se permiten imágenes (JPG, PNG)', 'error');
                    return;
                }

                // Validar tamaño según documentación técnica (500KB máximo)
                if (file.size > 500 * 1024) {
                    showUploadStatus(`Error: "${file.name}" excede el límite de 500KB`, 'error');
                    return;
                }

                validFiles.push(file);
            });

            if (validFiles.length === 0) {
                return;
            }

            // Crear previews inmediatas de las imágenes
            let previewImages = [];
            validFiles.forEach((file, index) => {
                const preview = {
                    id: 'temp_' + Date.now() + '_' + index,
                    filename: file.name,
                    original_name: file.name,
                    is_principal: (currentImages.length === 0 && index === 0), // Solo la primera si no hay otras
                    is_existing: false,
                    preview_url: URL.createObjectURL(file), // URL temporal para preview
                    file: file // Mantener referencia al archivo
                };
                previewImages.push(preview);
            });

            // Agregar las previews al estado actual inmediatamente
            currentImages = [...currentImages, ...previewImages];
            updateImageDisplay();

            // Subir silenciosamente en segundo plano
            const formData = new FormData();
            validFiles.forEach(file => {
                formData.append('images[]', file);
            });

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar silenciosamente los datos del servidor
                    data.files.forEach((serverFile, index) => {
                        const previewIndex = currentImages.findIndex(img => 
                            img.id === previewImages[index].id
                        );
                        if (previewIndex !== -1) {
                            // Mantener la URL de preview pero actualizar los demás datos
                            currentImages[previewIndex] = {
                                ...serverFile,
                                preview_url: currentImages[previewIndex].preview_url,
                                is_principal: currentImages[previewIndex].is_principal,
                                is_existing: false
                            };
                        }
                    });
                } else {
                    // Si falla, mostrar error pero mantener las imágenes
                    showUploadStatus('Error al procesar las imágenes en el servidor', 'error');
                }
            })
            .catch(error => {
                showUploadStatus('Error al procesar las imágenes', 'error');
                console.error('Error:', error);
            });
        }

        function updateImageDisplay() {
            const imagesList = document.getElementById('imagesList');
            const imagesCounter = document.getElementById('imagesCounter');
            
            // Limpiar contenedores
            imagesList.innerHTML = '';
            imagesCounter.innerHTML = '';
            
            if (currentImages.length > 0) {
                // Actualizar contador
                const existingCount = currentImages.filter(img => img.is_existing).length;
                const newCount = currentImages.filter(img => !img.is_existing).length;
                imagesCounter.textContent = `Total: ${currentImages.length} imagen(es)${existingCount > 0 ? ` (${existingCount} existente${existingCount > 1 ? 's' : ''})` : ''}${newCount > 0 ? ` (${newCount} nueva${newCount > 1 ? 's' : ''})` : ''}`;
                
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
                    img.src = imageUrl || ''; // Si no hay URL, se mostrará el error
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
                
                if (type === 'success' || type === 'error') {
                    setTimeout(() => {
                        uploadStatus.textContent = '';
                        uploadStatus.className = 'upload-status';
                    }, 3000);
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
            
            // Limpiar campos previos
            const prevFields = form.querySelectorAll('.dynamic-image-field');
            prevFields.forEach(field => field.remove());
            
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
            
            // Agregar información de imagen principal de las existentes
            const existingPrincipal = currentImages.find(img => img.is_existing && img.is_principal);
            if (existingPrincipal) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'existing_principal_id';
                input.value = existingPrincipal.id;
                input.className = 'dynamic-image-field';
                form.appendChild(input);
            }
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
    </script>
</body>
</html> 