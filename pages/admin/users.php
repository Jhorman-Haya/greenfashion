<?php
// Incluir el archivo de conexión
require_once '../../config/database.php';

// Iniciar la sesión
session_start();

// Verificar si el usuario está autenticado y tiene permisos de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Obtener la conexión
$conn = connection();

// Función para obtener todos los usuarios
function getUsers($conn) {
    $sql = "SELECT id, nombre, email, rol, fecha_registro FROM usuarios WHERE activo = TRUE ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Error en la consulta: " . mysqli_error($conn));
    }
    
    return $result;
}

// Función para sanitizar inputs
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Manejo de mensajes
$mensaje = '';
$tipo_mensaje = '';

// Procesar formularios si se envían
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Validar y sanitizar inputs
                $nombre = sanitizeInput($_POST['nombre']);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $rol = sanitizeInput($_POST['rol']);

                // Insertar nuevo usuario
                $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $nombre, $email, $password, $rol);
                
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
                $rol = sanitizeInput($_POST['rol']);

                $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $nombre, $email, $rol, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario actualizado exitosamente";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error al actualizar usuario: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                }
                break;

            case 'delete':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                
                // Verificar que el ID sea válido
                if (!$id) {
                    $mensaje = "ID de usuario inválido";
                    $tipo_mensaje = "error";
                    break;
                }

                // Prevenir eliminación del usuario actual
                if ($id == $_SESSION['user_id']) {
                    $mensaje = "No puedes eliminar tu propio usuario";
                    $tipo_mensaje = "error";
                    break;
                }

                // Soft delete - actualizar activo
                $sql = "UPDATE usuarios SET activo = FALSE WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                
                if (!$stmt) {
                    $mensaje = "Error al preparar la consulta: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                    break;
                }

                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Verificar si se afectó alguna fila
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $mensaje = "Usuario eliminado exitosamente";
                        $tipo_mensaje = "exito";
                    } else {
                        $mensaje = "No se encontró el usuario o ya estaba eliminado";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Error al eliminar usuario: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                }
                
                mysqli_stmt_close($stmt);
                break;

            case 'restore':
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                
                // Verificar que el ID sea válido
                if (!$id) {
                    $mensaje = "ID de usuario inválido";
                    $tipo_mensaje = "error";
                    break;
                }

                // Restaurar usuario - actualizar activo a TRUE
                $sql = "UPDATE usuarios SET activo = TRUE WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                
                if (!$stmt) {
                    $mensaje = "Error al preparar la consulta: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                    break;
                }

                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Verificar si se afectó alguna fila
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        $mensaje = "Usuario restaurado exitosamente";
                        $tipo_mensaje = "exito";
                    } else {
                        $mensaje = "No se encontró el usuario o ya estaba activo";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Error al restaurar usuario: " . mysqli_error($conn);
                    $tipo_mensaje = "error";
                }
                
                mysqli_stmt_close($stmt);
                break;
        }
    }
}

// Función para obtener todos los usuarios (incluyendo inactivos)
function getAllUsers($conn) {
    $sql = "SELECT id, nombre, email, rol, fecha_registro, activo FROM usuarios ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Error en la consulta: " . mysqli_error($conn));
    }
    
    return $result;
}

// Función para obtener un usuario por ID
function getUserById($conn, $id) {
    $sql = "SELECT id, nombre, email, rol FROM usuarios WHERE id = ? AND activo = TRUE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Si es una petición GET con ID de usuario, devolver los datos
if (isset($_GET['get_user'])) {
    $user_id = filter_var($_GET['get_user'], FILTER_SANITIZE_NUMBER_INT);
    $user_data = getUserById($conn, $user_id);
    header('Content-Type: application/json');
    echo json_encode($user_data);
    exit();
}

// Variable para controlar la vista
$mostrar_inactivos = isset($_GET['mostrar_inactivos']) && $_GET['mostrar_inactivos'] === '1';

// Obtener lista de usuarios según el filtro
$usuarios = $mostrar_inactivos ? getAllUsers($conn) : getUsers($conn);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios - GreenFashion</title>
    <link rel="stylesheet" href="../../assets/css/admin/users.css">
</head>
<body>
    <div class="container">
        <!-- Agregar botón de cerrar sesión -->
        <div class="header-actions">
            <h1 class="text-center">Administración de Usuarios</h1>
            <form method="POST" action="../logout.php" class="logout-form">
                <button type="submit" class="btn-logout">Cerrar Sesión</button>
            </form>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-<?php echo $tipo_mensaje; ?> text-center">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Botón para alternar vista -->
        <div style="margin-bottom: 20px;" class="text-center">
            <?php if ($mostrar_inactivos): ?>
                <a href="?mostrar_inactivos=0" class="btn-action">Mostrar solo activos</a>
            <?php else: ?>
                <a href="?mostrar_inactivos=1" class="btn-action">Mostrar todos los usuarios</a>
            <?php endif; ?>
        </div>

        <!-- Tabla de usuarios -->
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha de Registro</th>
                    <?php if ($mostrar_inactivos): ?>
                        <th>Estado</th>
                    <?php endif; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($usuarios)): ?>
                    <tr <?php echo isset($user['activo']) && !$user['activo'] ? 'style="background-color: #ffebee;"' : ''; ?>>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['rol']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                        <?php if ($mostrar_inactivos): ?>
                            <td><?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if (!isset($user['activo']) || $user['activo']): ?>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" onclick="return confirm('¿Está seguro de eliminar este usuario?')" class="btn-delete">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                    Editar
                                </button>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="usuario-actual">(Usuario actual)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-edit">
                                        Restaurar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal de Edición -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Usuario</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_nombre">Nombre:</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_rol">Rol:</label>
                    <select id="edit_rol" name="rol" required>
                        <option value="admin">Administrador</option>
                        <option value="cliente">Cliente</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-edit">Guardar Cambios</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Obtener el modal
        const modal = document.getElementById('editModal');
        const editForm = document.getElementById('editForm');

        // Función para cerrar el modal
        function closeModal() {
            modal.style.display = "none";
        }

        // Cerrar modal al hacer clic en la X
        document.querySelector('.close').onclick = closeModal;

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Función para editar usuario
        async function editUser(id) {
            try {
                // Obtener datos del usuario
                const response = await fetch(`?get_user=${id}`);
                const userData = await response.json();

                // Llenar el formulario con los datos
                document.getElementById('edit_id').value = userData.id;
                document.getElementById('edit_nombre').value = userData.nombre;
                document.getElementById('edit_email').value = userData.email;
                document.getElementById('edit_rol').value = userData.rol;

                // Mostrar el modal
                modal.style.display = "block";
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los datos del usuario');
            }
        }
    </script>
</body>
</html>