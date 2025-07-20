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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $nombre = sanitizeInput($_POST['nombre']);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $telefono = sanitizeInput($_POST['telefono']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $rol = sanitizeInput($_POST['rol']);

                $sql = "INSERT INTO usuarios (nombre, email, telefono, password, rol, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $nombre, $email, $telefono, $password, $rol);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario creado exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al crear usuario: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                }
                break;

            case 'update':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $nombre = sanitizeInput($_POST['nombre']);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $telefono = sanitizeInput($_POST['telefono']);
                $rol = sanitizeInput($_POST['rol']);

                $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, rol = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $email, $telefono, $rol, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario actualizado exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al actualizar usuario";
                    $tipo_mensaje = "error";
                }
                break;

            case 'delete':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                
                if ($id == $_SESSION['user_id']) {
                    $mensaje = "No puedes eliminar tu propio usuario";
                    $tipo_mensaje = "error";
                    break;
                }

                $sql = "UPDATE usuarios SET activo = FALSE WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario eliminado exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al eliminar usuario";
                    $tipo_mensaje = "error";
                }
                break;
        }
    }
}

// Obtener usuarios
$search = isset($_GET['search']) ? trim(sanitizeInput($_GET['search'])) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir consulta con parámetros preparados para evitar inyección SQL
$where_conditions = ["activo = TRUE"];
$params = [];
$param_types = "";

if ($search && strlen($search) >= 2) {
    $where_conditions[] = "(nombre LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT id, nombre, email, telefono, rol, fecha_registro, activo 
        FROM usuarios 
        $where_clause 
        ORDER BY fecha_registro DESC 
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$usuarios = mysqli_stmt_get_result($stmt);

// Contar total de usuarios
$count_sql = "SELECT COUNT(*) as total FROM usuarios $where_clause";
if ($search && strlen($search) >= 2) {
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_sql);
}
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $limit);

// Obtener usuario por ID para edición
if (isset($_GET['get_user'])) {
    $user_id = filter_var($_GET['get_user'], FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT id, nombre, email, telefono, rol FROM usuarios WHERE id = ? AND activo = TRUE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($user_data);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - GreenFashion</title>
    <link rel="stylesheet" href="../../assets/css/admin/users.css">
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
                            <a href="users.php" class="nav-link active">
                                <span class="material-icons">people</span>
                                Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
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
                <h1 class="page-title">Gestión de Usuarios</h1>
                <div class="header-actions">
                    <form class="search-container" method="GET">
                        <span class="material-icons search-icon">search</span>
                        <input type="text" class="search-input" placeholder="Buscar usuarios..." 
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
                        Añadir Usuario
                    </button>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nombre'], 0, 1)); ?>
                        </div>
                        <span class="material-icons">keyboard_arrow_down</span>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje === 'exito' ? 'success' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Panel de Contenido -->
            <div class="content-panel">
                <div class="panel-body">
                    <!-- Tabla de Usuarios -->
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha de Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($usuarios) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($usuarios)): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar-table">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['nombre']); ?>&background=2E7D32&color=fff" 
                                                         alt="<?php echo htmlspecialchars($user['nombre']); ?>">
                                                </div>
                                                <div class="user-details">
                                                    <h4><?php echo htmlspecialchars($user['nombre']); ?></h4>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['rol'] === 'admin' ? 'status-pendiente' : 'status-activo'; ?>">
                                                <?php echo $user['rol'] === 'admin' ? 'Administrador' : 'Cliente'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-activo">Activo</span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Editar">
                                                    <span class="material-icons">edit</span>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Eliminar">
                                                        <span class="material-icons">delete</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-results">
                                        <div class="no-results-content">
                                            <span class="material-icons">search_off</span>
                                            <h3>No se encontraron usuarios</h3>
                                            <p>
                                                <?php if ($search): ?>
                                                    No hay usuarios que coincidan con la búsqueda "<?php echo htmlspecialchars($search); ?>"
                                                    <br>
                                                    <a href="?" class="btn btn-secondary">Limpiar búsqueda</a>
                                                <?php else: ?>
                                                    No hay usuarios registrados en el sistema
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
                            Mostrando <?php echo ($page - 1) * $limit + 1; ?> a <?php echo min($page * $limit, $total_users); ?> de <?php echo $total_users; ?> resultados
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

    <!-- Modal para Crear/Editar Usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Añadir Usuario</h2>
                <button class="close" onclick="closeModal()" type="button">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <form id="userForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="form-group full-width">
                        <label class="form-label" for="nombre">Nombre Completo</label>
                        <input type="text" class="form-input" id="nombre" name="nombre" 
                               placeholder="Ingresa el nombre completo" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">Correo Electrónico</label>
                            <input type="email" class="form-input" id="email" name="email" 
                                   placeholder="usuario@email.com" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono</label>
                            <input type="tel" class="form-input" id="telefono" name="telefono" 
                                   placeholder="+34 123 456 789">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" id="passwordGroup">
                            <label class="form-label" for="password">Contraseña</label>
                            <input type="password" class="form-input" id="password" name="password" 
                                   placeholder="Mínimo 6 caracteres">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="rol">Rol del Usuario</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccionar rol</option>
                                <option value="cliente">Cliente</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="material-icons">person_add</span>
                            Crear Usuario
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const userForm = document.getElementById('userForm');
        
        function closeModal() {
            modal.style.display = 'none';
            userForm.reset();
        }

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Añadir Usuario';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<span class="material-icons">person_add</span> Crear Usuario';
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('password').required = true;
            modal.style.display = 'block';
        }

        async function editUser(id) {
            try {
                const response = await fetch(`?get_user=${id}`);
                const userData = await response.json();

                document.getElementById('modalTitle').textContent = 'Editar Usuario';
                document.getElementById('formAction').value = 'update';
                document.getElementById('submitBtn').innerHTML = '<span class="material-icons">save</span> Actualizar Usuario';
                document.getElementById('passwordGroup').style.display = 'none';
                document.getElementById('password').required = false;
                
                document.getElementById('userId').value = userData.id;
                document.getElementById('nombre').value = userData.nombre;
                document.getElementById('email').value = userData.email;
                document.getElementById('telefono').value = userData.telefono || '';
                document.getElementById('rol').value = userData.rol;

                modal.style.display = 'block';
            } catch (error) {
                alert('Error al cargar los datos del usuario');
            }
        }

        function deleteUser(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
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

        // Implementar búsqueda con debounce (opcional)
        let searchTimeout;
        
        function debounceSearch(query, delay = 500) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) {
                    searchUsers(query);
                }
            }, delay);
        }
        
        function searchUsers(query) {
            const url = new URL(window.location);
            if (query && query.trim()) {
                url.searchParams.set('search', query.trim());
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');
            window.location = url;
        }
        
        // Agregar funcionalidad opcional de búsqueda en tiempo real (deshabilitada por defecto)
        document.getElementById('searchInput').addEventListener('input', function(e) {
            // Descomenta la línea siguiente si quieres búsqueda en tiempo real con debounce
            // debounceSearch(e.target.value);
        });
        
        // Búsqueda al presionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUsers(this.value);
            }
        });
        
        // Limpiar búsqueda
        function clearSearch() {
            const url = new URL(window.location);
            url.searchParams.delete('search');
            url.searchParams.delete('page');
            window.location = url;
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }


    </script>
</body>
</html>