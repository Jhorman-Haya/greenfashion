<?php
session_start();
require_once '../config/database.php';

// Si el usuario ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: admin/users.php');
    } else {
        header('Location: ../index.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connection();
    
    // Obtener y sanitizar datos
    $nombre = trim($_POST['nombre']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, complete todos los campos obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un correo electrónico válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Verificar si el email ya existe
        $sql_check = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $error = 'Este correo electrónico ya está registrado';
        } else {
            // Crear nuevo usuario
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $rol = 'cliente'; // Registro como cliente por defecto
            $activo = true;

            $sql = "INSERT INTO usuarios (nombre, email, telefono, password, rol, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssi", $nombre, $email, $telefono, $password_hashed, $rol, $activo);

            if (mysqli_stmt_execute($stmt)) {
                // Obtener el ID del usuario recién creado
                $user_id = mysqli_insert_id($conn);
                
                // Iniciar sesión automáticamente
                $_SESSION['user_id'] = $user_id;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                $_SESSION['rol'] = 'cliente';
                
                // Redirigir a la página principal para clientes
                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Error al crear la cuenta. Por favor, inténtelo de nuevo.';
            }
        }
    }   
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - GreenFashion</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <!-- Círculos decorativos -->
    <div class="decoration-circle decoration-circle-1"></div>
    <div class="decoration-circle decoration-circle-2"></div>
    <div class="decoration-circle decoration-circle-3"></div>

    <div class="main-container">
        <!-- Sección izquierda con logo grande -->
        <div class="left-section">
            <div class="brand-logo">
                <div class="tree-illustration">
                    <!-- Tronco -->
                    <div class="trunk"></div>
                    <!-- Hojas -->
                    <div class="leaves leaf-1"></div>
                    <div class="leaves leaf-2"></div>
                    <div class="leaves leaf-3"></div>
                    <!-- Cuadrado flotante -->
                    <div class="floating-square"></div>
                </div>
                <h1>GreenFashion</h1>
                <p>Moda Sostenible</p>
            </div>
        </div>

        <!-- Sección derecha con formulario -->
        <div class="right-section">
            <div class="register-container">
                <div class="register-header">
                    <div class="logo-icon">
                        <img src="../assets/images/logo.svg" alt="GreenFashion Icon" class="icon">
                    </div>
                    <span class="brand-name">GreenFashion</span>
                </div>

                <h2>Crear Cuenta</h2>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>



                <form class="register-form" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <div class="input-container">
                            <span class="material-icons">person</span>
                            <input 
                                type="text" 
                                id="nombre" 
                                name="nombre" 
                                required 
                                placeholder="Tu nombre completo"
                                value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <div class="input-container">
                            <span class="material-icons">mail</span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                placeholder="ejemplo@correo.com"
                                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono (opcional)</label>
                        <div class="input-container">
                            <span class="material-icons">phone</span>
                            <input 
                                type="tel" 
                                id="telefono" 
                                name="telefono" 
                                placeholder="Tu número de teléfono"
                                value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-container">
                            <span class="material-icons">lock</span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                placeholder="Contraseña"
                                minlength="6"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar contraseña</label>
                        <div class="input-container">
                            <span class="material-icons">lock</span>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                placeholder="Confirmar contraseña"
                                minlength="6"
                            >
                        </div>
                    </div>

                    <button type="submit" class="register-button">
                        Registrarse
                    </button>

                    <div class="register-footer">
                        <div class="login-link">
                            <span>¿Ya tienes cuenta?</span>
                            <a href="login.php">Inicia sesión</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 