<?php
session_start();
require_once '../config/database.php';

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: admin/users.php');
    } else {
        header('Location: public/index.php');
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
                    header('Location: public/index.php');
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
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>GreenFashion</h1>
            <p>Iniciar Sesión</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="login-button">
                Iniciar Sesión
            </button>
        </form>
    </div>
</body>
</html>
