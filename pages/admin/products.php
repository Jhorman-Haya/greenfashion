<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$conn = connection();

// Obtener productos con paginación y búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
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

    <script>
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