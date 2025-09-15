<?php
if (!defined('NOMBRE_SITIO')) {
    include_once(__DIR__ . '/../config/config.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Guatemala');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TickIt UVG</title>
    <link rel="stylesheet" href="<?php echo URL_BASE ?>/assets/css/header.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            
            <!-- Lado izquierdo: Logo + Links -->
            <div class="navbar-left">
                <a href="<?php echo URL_BASE ?>" class="navbar-brand">
                    <i class="fa-solid fa-clone"></i>
                    <span>TickIt UVG</span>
                </a>
                <nav class="nav-links">
                    <?php if(isset($_SESSION['usuario_id'])) { ?>
                        <a href="<?php echo URL_BASE?>/pages/tareas.php" class="nav-item">Tareas</a>
                        <a href="<?php echo URL_BASE?>/pages/etiquetas.php" class="nav-item">Etiquetas</a>
                    <?php } else { ?>
                        <a href="#" class="nav-item" onclick="alert('Debes iniciar sesión para acceder a Tareas'); return false;">Tareas</a>
                        <a href="#" class="nav-item" onclick="alert('Debes iniciar sesión para acceder a Etiquetas'); return false;">Etiquetas</a>
                    <?php } ?>
                </nav>
            </div>

            <!-- Lado derecho: barra de búsqueda -->
            <form class="search-bar" method="GET" action="<?php echo URL_BASE?>/pages/busqueda.php">
                <input type="text" name="q" placeholder="Buscar tareas...">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </header>

    <br>
    <div class="container">
        <div class="row">