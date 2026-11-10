<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion($rol_requerido = null) {
    if (!isset($_SESSION['id_empleado'])) {
        // Redirigir al inicio o login si no hay sesión activa
        header("Location: /LLANTAS_MR_GARCIA/index.php");
        exit();
    }

    // Opcional: restringir por puesto si se especifica
    if ($rol_requerido && $_SESSION['puesto'] !== $rol_requerido && $_SESSION['puesto'] !== 'Dueño / Administrador') {
        die("Acceso denegado: Tu puesto no cuenta con permisos para este módulo.");
    }
}
?>