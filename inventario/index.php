<?php
// inventario/index.php
require_once '../config/database.php';

try {
    // Consulta para obtener el inventario detallado por sucursal
    $sql = "SELECT i.id_inventario, s.nombre AS sucursal, a.nombre AS articulo, 
                   a.tipo, a.precio_unitario, i.stock_actual, i.stock_minimo 
            FROM inventario i
            INNER JOIN sucursal s ON i.id_sucursal = s.id_sucursal
            INNER JOIN articulo a ON i.id_articulo = a.id_articulo
            ORDER BY s.nombre, a.nombre";
    
    $stmt = $pdo->query($sql);
    $inventarios = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Error al consultar la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Inventario - SIG-LMG</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Control de Inventario por Sucursal</h1>
        <p><a href="../index.php" class="btn" style="text-decoration: none; padding: 0.3rem 0.6rem; font-size: 0.9rem;">← Volver al Inicio</a></p>

        <?php if (isset($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Artículo</th>
                        <th>Tipo</th>
                        <th>Precio Unitario</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($inventarios) > 0): ?>
                        <?php foreach ($inventarios as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['sucursal']) ?></td>
                            <td><?= htmlspecialchars($row['articulo']) ?></td>
                            <td><?= htmlspecialchars($row['tipo']) ?></td>
                            <td>$<?= number_format($row['precio_unitario'], 2) ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?= ($row['stock_actual'] <= $row['stock_minimo']) ? '#dc2626' : '#16a34a'; ?>">
                                    <?= htmlspecialchars($row['stock_actual']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['stock_minimo']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b;">No hay registros de inventario en la base de datos todavía.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>