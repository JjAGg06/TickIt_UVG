<?php
session_start();
session_unset(); // Limpia variables de sesion
session_destroy(); // Destruye la sesion actual

// Redirigir al index, sin usuario activo
header("Location: index.php");
exit;
