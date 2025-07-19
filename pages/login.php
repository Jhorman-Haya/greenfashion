<?php
session_start();
require_once '../config/database.php';

// Si el usuario ya está logueado, redirigir según su rol
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
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validar que los campos no estén vacíos
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        // Consultar usuario
        $sql = "SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND activo = TRUE";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['rol'] = $user['rol'];

                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    header('Location: admin/users.php');
                    exit();
                } else {
                    header('Location: ../index.php');
                    exit();
                }
            } else {
                $error = 'Credenciales incorrectas';
            }
        } else {
            $error = 'Credenciales incorrectas';
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
    <title>Iniciar Sesión - GreenFashion</title>
    <link rel="stylesheet" href="../assets/css/login.css">
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
            <div class="login-container">
                <div class="login-header">
                    <div class="logo-icon">
                        <img src="../assets/images/logo.svg" alt="GreenFashion Icon" class="icon">
                    </div>
                    <span class="brand-name">GreenFashion</span>
                </div>

                <h2>Iniciar Sesión</h2>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST">
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
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
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
                            >
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        Ingresar
                    </button>

                    <div class="login-footer">
                        <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                        <div class="register-link">
                            <span>¿No tienes cuenta?</span>
                            <a href="register.php">Regístrate</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
