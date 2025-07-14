<?php
function connection() {
    $server = "localhost";
    $user = "root";
    $password = "";
    $database = "greenfashion";

    $connect = mysqli_connect($server, $user, $database, $password);

    return $connect;
}
?>