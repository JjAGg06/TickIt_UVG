<?php
if(!defined('NOMBRE_SITIO')){
    include_once(__DIR__ . '/config.php');
}

$host = "localhost"; //indico la ip del servidor
    $port = 3306;
    $user = "root";
    $pass = "admin";
    $database = "tickit_UVG";

    $conn = new mysqli($host, $user, $pass, $database, $port);

    if($conn->connect_error){
        die("Conexion fallida" . $conn->connect_error);
    }
    //echo "Conexion Exitosa de la BD";

?>