<?php
// ventas/comprobante.php
require_once '../config/database.php';

$id_venta = $_GET['id'] ?? null;

if (!$id_venta) {
    header("Location: nueva_venta.php");
    exit();
}

try {
    // Consultar datos principales de la venta
    $stmtVenta = $pdo->prepare("
        SELECT v.*, s.nombre AS sucursal, e.nombre AS empleado 
        FROM venta v
        INNER JOIN sucursal s ON v.id_sucursal = s.id_sucursal
        INNER JOIN empleado e ON v.id_empleado = e.id_empleado
        WHERE v.id_venta = ?
    ");
    $stmtVenta->execute([$id_venta]);
    $venta = $stmtVenta->fetch();

    if (!$venta) {
        die("Comprobante no encontrado.");
    }

    // Consultar los detalles de los productos/servicios vendidos
    $stmtDetalles = $pdo->prepare("
        SELECT d.*, a.nombre AS articulo 
        FROM detalle_venta d
        INNER JOIN articulo a ON d.id_articulo = a.id_articulo
        WHERE d.id_venta = ?
    ");
    $stmtDetalles->execute([$id_venta]);
    $detalles = $stmtDetalles->fetchAll();

} catch (PDOException $e) {
    die("Error al consultar comprobante: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Venta #<?= htmlspecialchars($venta['id_venta']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @media print {
            .no-print { display: none; }
            .container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <div class="no-print" style="margin-bottom: 1rem;">
            <a href="nueva_venta.php" class="btn">← Nueva Venta</a>
            <button onclick="window.print()" class="btn" style="background-color: #475569; float: right;">Imprimir Comprobante</button>
        </div>

        <div style="text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 1rem;">
            <h2>Llantas Mr. García</h2>
            <p><strong>Sucursal:</strong> <?= htmlspecialchars($venta['sucursal']) ?></p>
            <p><strong>Folio de Venta:</strong> #<?= str_pad($venta['id_venta'], 6, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Fecha:</strong> <?= htmlspecialchars($venta['fecha_hora']) ?></p>
            <p><strong>Atendido por:</strong> <?= htmlspecialchars($venta['empleado']) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Cant.</th>
                    <th>P.U.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['articulo']) ?></td>
                    <td><?= htmlspecialchars($d['cantidad']) ?></td>
                    <td>$<?= number_format($d['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format($d['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align: right; margin-top: 1.5rem; font-size: 1.2rem; font-weight: bold;">
            Total Pagado: $<?= number_format($venta['total'], 2) ?>
        </div>
    </div>
</body>
</html>