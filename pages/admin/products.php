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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

                // Marcar producto como inactivo (soft delete)
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

// Obtener productos con paginación y búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir consulta
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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <main class="main-content">
            <!-- Header con búsqueda -->
            <div class="panel-header">
                <h1>Gestión de Productos</h1>
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
                </div>
            </div>

            <!-- Tabla de Productos -->
            <div class="content-panel">
                <div class="panel-body">
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
                                            <div class="product-info" onclick="viewProduct(<?php echo $product['id']; ?>)" style="cursor: pointer;">
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
                                                <button class="btn-action btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)" title="Ver Detalle">
                                                    <span class="material-icons">visibility</span>
                                                </button>
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

            <!-- Modal de Detalle -->
            <div id="viewModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="viewModalTitle">
                            <span class="material-icons">visibility</span>
                            Detalle del Producto
                        </h2>
                        <button class="close" onclick="closeViewModal()">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="product-detail">
                            <!-- Galería de imágenes -->
                            <div class="product-gallery">
                                <div id="mainImage" class="main-image"></div>
                                <div id="thumbnails" class="thumbnails"></div>
                            </div>

                            <!-- Información del producto -->
                            <div class="product-info-detail">
                                <h3 id="productName"></h3>
                                <div class="price-stock">
                                    <div class="detail-price">
                                        <span class="label">Precio:</span>
                                        <span id="productPrice" class="price"></span>
                                    </div>
                                    <div class="detail-stock">
                                        <span class="label">Stock:</span>
                                        <span id="productStock" class="stock-badge"></span>
                                    </div>
                                </div>

                                <div class="detail-section">
                                    <h4>Descripción</h4>
                                    <p id="productDescription"></p>
                                </div>

                                <div class="detail-section">
                                    <h4>Categorías</h4>
                                    <div id="productCategories" class="categories-list"></div>
                                </div>

                                <div class="detail-section">
                                    <h4>Materiales</h4>
                                    <p id="productMaterials"></p>
                                </div>

                                <div class="detail-section">
                                    <h4>Impacto Ambiental</h4>
                                    <p id="productImpact"></p>
                                </div>

                                <div class="detail-section">
                                    <h4>Información Adicional</h4>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <span class="label">Estado:</span>
                                            <span id="productStatus" class="status-badge"></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Fecha de Creación:</span>
                                            <span id="productDate"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

        // Modal de detalle
        const viewModal = document.getElementById('viewModal');
        
        function closeViewModal() {
            viewModal.style.display = 'none';
        }

        async function viewProduct(id) {
            try {
                const response = await fetch(`get_product.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const product = result.data;
                    
                    // Actualizar información básica
                    document.getElementById('productName').textContent = product.nombre;
                    document.getElementById('productPrice').textContent = `$${parseFloat(product.precio).toFixed(2)}`;
                    document.getElementById('productStock').textContent = `${product.stock} unidades`;
                    document.getElementById('productStock').className = `stock-badge ${product.stock > 0 ? 'stock-available' : 'stock-empty'}`;
                    document.getElementById('productDescription').textContent = product.descripcion;
                    document.getElementById('productMaterials').textContent = product.materiales;
                    document.getElementById('productImpact').textContent = product.impacto_ambiental;
                    document.getElementById('productStatus').textContent = product.activo ? 'Activo' : 'Inactivo';
                    document.getElementById('productStatus').className = `status-badge status-${product.activo ? 'activo' : 'inactivo'}`;
                    document.getElementById('productDate').textContent = new Date(product.fecha_creacion).toLocaleDateString();
                    
                    // Actualizar categorías
                    const categoriesContainer = document.getElementById('productCategories');
                    categoriesContainer.innerHTML = '';
                    if (product.categorias) {
                        product.categorias.split(',').forEach(categoria => {
                            const badge = document.createElement('span');
                            badge.className = 'category-badge';
                            badge.textContent = categoria.trim();
                            categoriesContainer.appendChild(badge);
                        });
                    } else {
                        categoriesContainer.innerHTML = '<span class="text-muted">Sin categoría</span>';
                    }
                    
                    // Actualizar galería de imágenes
                    const mainImage = document.getElementById('mainImage');
                    const thumbnails = document.getElementById('thumbnails');
                    mainImage.innerHTML = '';
                    thumbnails.innerHTML = '';
                    
                    if (product.imagenes && product.imagenes.length > 0) {
                        // Imagen principal
                        const principalImage = product.imagenes.find(img => img.principal == 1) || product.imagenes[0];
                        const mainImg = document.createElement('img');
                        mainImg.src = `../../${principalImage.url}`;
                        mainImg.alt = product.nombre;
                        mainImage.appendChild(mainImg);
                        
                        // Miniaturas
                        product.imagenes.forEach(img => {
                            const thumb = document.createElement('div');
                            thumb.className = `thumbnail ${img.principal == 1 ? 'active' : ''}`;
                            thumb.innerHTML = `<img src="../../${img.url}" alt="${product.nombre}">`;
                            thumb.onclick = () => {
                                mainImg.src = `../../${img.url}`;
                                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                                thumb.classList.add('active');
                            };
                            thumbnails.appendChild(thumb);
                        });
                    } else {
                        mainImage.innerHTML = `
                            <div class="no-image">
                                <span class="material-icons">image_not_available</span>
                                <p>No hay imágenes disponibles</p>
                            </div>
                        `;
                    }
                    
                    viewModal.style.display = 'block';
                } else {
                    alert('Error al cargar el producto');
                }
            } catch (error) {
                alert('Error al cargar el producto');
                console.error('Error:', error);
            }
        }

        async function editProduct(id) {
            try {
                const response = await fetch(`get_product.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const product = result.data;
                    
                    // Actualizar título y acción del modal
                    document.getElementById('modalTitle').innerHTML = '<span class="material-icons">edit</span> Editar Producto';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('submitBtn').innerHTML = '<span class="material-icons">save</span> Actualizar Producto';
                    
                    // Llenar campos del formulario
                    document.getElementById('productId').value = product.id;
                    document.getElementById('nombre').value = product.nombre;
                    document.getElementById('descripcion').value = product.descripcion;
                    document.getElementById('precio').value = product.precio;
                    document.getElementById('stock').value = product.stock;
                    document.getElementById('materiales').value = product.materiales || '';
                    document.getElementById('impacto_ambiental').value = product.impacto_ambiental || '';
                    document.getElementById('activo').checked = product.activo == 1;

                    // Marcar categorías
                    const categorias = product.categorias ? product.categorias.split(',') : [];
                    document.querySelectorAll('input[name="categorias[]"]').forEach(checkbox => {
                        checkbox.checked = categorias.includes(checkbox.value.trim());
                    });

                    // Cargar imágenes existentes
                    currentImages = [];
                    if (product.imagenes && product.imagenes.length > 0) {
                        currentImages = product.imagenes.map(imagen => ({
                            id: imagen.id,
                            filename: imagen.url.split('/').pop(),
                            original_name: imagen.url.split('/').pop(),
                            is_principal: imagen.principal == 1,
                            is_existing: true,
                            url: imagen.url
                        }));
                    }
                    updateImageDisplay();

                    // Mostrar modal
                    document.getElementById('productModal').style.display = 'block';
                } else {
                    alert('Error al cargar los datos del producto');
                }
            } catch (error) {
                alert('Error al cargar los datos del producto');
                console.error('Error:', error);
            }
        }

        // Función para eliminar producto
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

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target === viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html> 