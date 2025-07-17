<?php
function connection() {
    $server = "localhost";
    $user = "root";
    $password = "";
    $database = "greenfashion";

    $connect = mysqli_connect($server, $user, $password, $database);

    return $connect;
}
?>