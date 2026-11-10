<?php
// servicios/registrar_insumo.php
require_once '../config/database.php';

$mensaje = '';

try {
    // Cargar servicios en proceso o pendientes y artículos disponibles
    $servicios = $pdo->query("SELECT s.id_solicitud, s.tipo_incidente, c.nombre AS cliente 
                              FROM solicitud_servicio s 
                              LEFT JOIN cliente c ON s.id_cliente = c.id_cliente 
                              WHERE s.estado != 'FINALIZADO'")->fetchAll();
                              
    $articulos = $pdo->query("SELECT * FROM articulo WHERE tipo = 'PRODUCTO' ORDER BY nombre")->fetchAll();
    $empleados = $pdo->query("SELECT * FROM empleado WHERE puesto LIKE '%Técnico%' OR puesto LIKE '%tecnico%'")->fetchAll();
} catch (PDOException $e) {
    $mensaje = "Error al cargar datos: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_insumo'])) {
    $id_solicitud = $_POST['id_solicitud'];
    $id_articulo  = $_POST['id_articulo'];
    $id_empleado  = $_POST['id_empleado'];
    $cantidad     = floatval($_POST['cantidad']);

    if ($cantidad <= 0) {
        $mensaje = "La cantidad debe ser mayor a cero.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Obtener la sucursal asignada a la solicitud de servicio
            $stmtServ = $pdo->prepare("SELECT sucursal_asignada FROM solicitud_servicio WHERE id_solicitud = ?");
            $stmtServ->execute([$id_solicitud]);
            $servData = $stmtServ->fetch();

            if (!$servData) {
                throw new Exception("La solicitud de servicio seleccionada no es válida.");
            }
            $id_sucursal = $servData['sucursal_asignada'];

            // 2. Verificar inventario y stock actual con bloqueo
            $stmtInv = $pdo->prepare("SELECT id_inventario, stock_actual FROM inventario WHERE id_sucursal = ? AND id_articulo = ? FOR UPDATE");
            $stmtInv->execute([$id_sucursal, $id_articulo]);
            $inv = $stmtInv->fetch();

            if (!$inv || $inv['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente del insumo en la sucursal asignada.");
            }

            // 3. Descontar stock del inventario
            $stmtUpd = $pdo->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ?");
            $stmtUpd->execute([$cantidad, $inv['id_inventario']]);

            // 4. Registrar el movimiento de inventario (Salida por insumo de servicio)
            $stmtMov = $pdo->prepare("INSERT INTO movimiento_inventario (id_inventario, id_empleado, tipo_movimiento, cantidad, fecha_hora, motivo) VALUES (?, ?, 'SALIDA', ?, NOW(), 'CONSUMO_SERVICIO')");
            $stmtMov->execute([$inv['id_inventario'], $id_empleado, $cantidad]);

            $pdo->commit();
            $mensaje = "¡Insumo registrado y descontado del inventario exitosamente!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error al registrar insumo: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Insumos por Servicio - SIG-LMG</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Registro de Insumos Utilizados en Servicios</h1>
        <p><a href="../index.php" class="btn" style="text-decoration: none; padding: 0.3rem 0.6rem;">← Volver al Inicio</a></p>

        <?php if ($mensaje): ?>
            <p style="font-weight: bold; color: <?= strpos($mensaje, 'exitosamente') !== false ? '#16a34a' : '#dc2626' ?>;"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form action="registrar_insumo.php" method="POST">
            <label>Seleccionar Servicio / Incidente:</label>
            <select name="id_solicitud" class="form-control" required>
                <option value="">-- Seleccionar Servicio --</option>
                <?php foreach ($servicios as $s): ?>
                    <option value="<?= $s['id_solicitud'] ?>">Servicio #<?= $s['id_solicitud'] ?> - <?= htmlspecialchars($s['tipo_incidente']) ?> (Cliente: <?= htmlspecialchars($s['cliente'] ?? 'General') ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Técnico Responsable:</label>
            <select name="id_empleado" class="form-control" required>
                <option value="">-- Seleccionar Técnico --</option>
                <?php foreach ($empleados as $e): ?>
                    <option value="<?= $e['id_empleado'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['puesto']) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Insumo / Refacción (Producto):</label>
            <select name="id_articulo" class="form-control" required>
                <option value="">-- Seleccionar Artículo --</option>
                <?php foreach ($articulos as $a): ?>
                    <option value="<?= $a['id_articulo'] ?>"><?= htmlspecialchars($a['nombre']) ?> ($<?= number_format($a['precio_unitario'], 2) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Cantidad Utilizada:</label>
            <input type="number" name="cantidad" value="1" min="1" step="0.01" class="form-control" required>

            <button type="submit" name="guardar_insumo" class="btn" style="margin-top: 1rem; background-color: #2563eb;">Registrar Consumo de Insumo</button>
        </form>
    </div>
</body>
</html>