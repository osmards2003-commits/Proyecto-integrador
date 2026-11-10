<?php
// ventas/corte_diario.php
require_once '../config/database.php';
require_once '../config/auth.php';
verificarSesion(); // Asegura que el usuario esté autenticado

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$id_sucursal = $_GET['id_sucursal'] ?? '';

try {
    // Cargar sucursales para el filtro
    $sucursales = $pdo->query("SELECT * FROM sucursal ORDER BY nombre")->fetchAll();

    // Consultar ventas del día seleccionado
    $sql = "SELECT v.*, s.nombre AS sucursal, e.nombre AS empleado 
            FROM venta v
            INNER JOIN sucursal s ON v.id_sucursal = s.id_sucursal
            INNER JOIN empleado e ON v.id_empleado = e.id_empleado
            WHERE DATE(v.fecha_hora) = ?";
    
    $params = [$fecha];
    if (!empty($id_sucursal)) {
        $sql .= " AND v.id_sucursal = ?";
        $params[] = $id_sucursal;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventas_dia = $stmt->fetchAll();

    // Calcular suma total recaudada
    $total_recaudado = 0;
    foreach ($ventas_dia as $v) {
        $total_recaudado += $v['total'];
    }
} catch (PDOException $e) {
    $error = "Error al calcular el corte diario: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte Diario - SIG-LMG</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Corte Diario de Caja</h1>
        <p><a href="../index.php" class="btn" style="text-decoration: none; padding: 0.3rem 0.6rem;">← Volver al Inicio</a></p>

        <?php if (isset($error)): ?>
            <p style="color: #dc2626; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: flex-end;">
            <div>
                <label>Fecha:</label>
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control" style="margin-bottom:0;">
            </div>
            <div>
                <label>Sucursal:</label>
                <select name="id_sucursal" class="form-control" style="margin-bottom:0;">
                    <option value="">Todas las sucursales</option>
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id_sucursal'] ?>" <?= $id_sucursal == $s['id_sucursal'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn">Filtrar</button>
            </div>
        </form>

        <div style="background: #f1f5f9; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; display: flex; justify-content: space-around;">
            <div><strong>Operaciones Totales:</strong> <?= count($ventas_dia) ?></div>
            <div><strong>Total Recaudado:</strong> $<?= number_format($total_recaudado, 2) ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Sucursal</th>
                    <th>Empleado</th>
                    <th>Fecha y Hora</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ventas_dia)): ?>
                    <?php foreach ($ventas_dia as $v): ?>
                    <tr>
                        <td>#<?= str_pad($v['id_venta'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($v['sucursal']) ?></td>
                        <td><?= htmlspecialchars($v['empleado']) ?></td>
                        <td><?= htmlspecialchars($v['fecha_hora']) ?></td>
                        <td>$<?= number_format($v['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b;">No hay operaciones registradas para esta fecha.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>