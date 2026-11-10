<?php
require_once 'config/database.php';
session_start();

// Simulación rápida de sesión para pruebas locales (puedes ajustar el id y puesto según tus empleados en BD)
if (!isset($_SESSION['id_empleado'])) {
    $_SESSION['id_empleado'] = 1;
    $_SESSION['puesto'] = 'Dueño / Administrador';
}

try {
    $stmt = $pdo->query("SELECT * FROM sucursal");
    $sucursales = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIG-LMG - Llantas Mr. García</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Sistema Integral de Gestión - Llantas Mr. García</h1>
        <p>Estado del sistema: <strong>Conectado a MySQL Workbench (Puerto 3307)</strong>[cite: 19, 22]</p>
        <p>Sesión activa: <strong><?= htmlspecialchars($_SESSION['puesto']) ?></strong></p>

        <div style="display: flex; gap: 1rem; margin: 1.5rem 0; flex-wrap: wrap;">
            <a href="inventario/index.php" class="btn" style="text-decoration: none;">📦 Módulo de Inventario</a>
            <a href="ventas/nueva_venta.php" class="btn" style="text-decoration: none; background-color: #16a34a;">🛒 Módulo de Ventas / Caja</a>
            <a href="ventas/corte_diario.php" class="btn" style="text-decoration: none; background-color: #0284c7;">📊 Corte Diario</a>
            <a href="servicios/registrar_insumo.php" class="btn" style="text-decoration: none; background-color: #ca8a04;">🔧 Insumos por Servicio</a>
        </div>
        
        <h2>Sucursales Registradas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sucursales)): ?>
                    <?php foreach ($sucursales as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id_sucursal']) ?></td>
                        <td><?= htmlspecialchars($s['nombre']) ?></td>
                        <td><?= htmlspecialchars($s['direccion']) ?></td>
                        <td><?= htmlspecialchars($s['telefono']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b;">No hay sucursales registradas aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>