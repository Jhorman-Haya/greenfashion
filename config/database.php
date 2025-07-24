<?php
function connection() {
    $server = "localhost";
    $user = "root";
    $password = "";
    $database = "greenfashion";

    try {
        $connect = mysqli_connect($server, $user, $password, $database);
    } catch (mysqli_sql_exception) {
        die("No se pudo conectar a la base de datos. Revisa las variables de conexión.");
    }
    return $connect;
}
?>