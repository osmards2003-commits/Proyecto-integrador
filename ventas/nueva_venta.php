<?php
// ventas/nueva_venta.php
require_once '../config/database.php';

$mensaje_error = '';

// Cargar sucursales, empleados y artículos para las selecciones del formulario
try {
    $sucursales = $pdo->query("SELECT * FROM sucursal ORDER BY nombre")->fetchAll();
    $empleados  = $pdo->query("SELECT * FROM empleado WHERE activo = 1 ORDER BY nombre")->fetchAll();
    $articulos  = $pdo->query("SELECT * FROM articulo ORDER BY nombre")->fetchAll();
} catch (PDOException $e) {
    $mensaje_error = "Error al cargar datos base: " . $e->getMessage();
}

// Procesar la venta cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_venta'])) {
    $id_sucursal  = $_POST['id_sucursal'];
    $id_empleado  = $_POST['id_empleado'];
    $id_cliente   = !empty($_POST['id_cliente']) ? $_POST['id_cliente'] : null;
    $articulos_id = $_POST['articulo_id'] ?? [];
    $cantidades   = $_POST['cantidad'] ?? [];

    if (empty($articulos_id)) {
        $mensaje_error = "Debes agregar al menos un artículo o servicio a la venta.";
    } else {
        try {
            // Iniciar la transacción para proteger la atomicidad de la venta
            $pdo->beginTransaction();

            // 1. Obtener precios unitarios actuales y calcular el total acumulado
            $total = 0;
            $items_procesar = [];

            foreach ($articulos_id as $index => $id_art) {
                $cant = floatval($cantidades[$index]);
                if ($cant <= 0) continue;

                $stmtArt = $pdo->prepare("SELECT * FROM articulo WHERE id_articulo = ?");
                $stmtArt->execute([$id_art]);
                $art = $stmtArt->fetch();

                if ($art) {
                    $subtotal = $art['precio_unitario'] * $cant;
                    $total += $subtotal;

                    $items_procesar[] = [
                        'id_articulo' => $id_art,
                        'tipo' => $art['tipo'],
                        'cantidad' => $cant,
                        'precio_unitario' => $art['precio_unitario'],
                        'subtotal' => $subtotal
                    ];
                }
            }

            // 2. Registrar el encabezado de la venta
            $stmtVenta = $pdo->prepare("INSERT INTO venta (id_sucursal, id_empleado, id_cliente, fecha_hora, total) VALUES (?, ?, ?, NOW(), ?)");
            $stmtVenta->execute([$id_sucursal, $id_empleado, $id_cliente, $total]);
            $id_venta = $pdo->lastInsertId();

            // 3. Procesar los renglones y la deducción de inventario
            foreach ($items_procesar as $item) {
                // Registrar detalle de venta
                $stmtDet = $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_articulo, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmtDet->execute([$id_venta, $item['id_articulo'], $item['cantidad'], $item['precio_unitario'], $item['subtotal']]);

                // Si es un producto físico, bloqueamos el registro e intentamos descontar el stock
                if ($item['tipo'] === 'PRODUCTO') {
                    $stmtInv = $pdo->prepare("SELECT id_inventario, stock_actual FROM inventario WHERE id_sucursal = ? AND id_articulo = ? FOR UPDATE");
                    $stmtInv->execute([$id_sucursal, $item['id_articulo']]);
                    $inv = $stmtInv->fetch();

                    if (!$inv || $inv['stock_actual'] < $item['cantidad']) {
                        throw new Exception("Stock insuficiente para el producto ID: " . $item['id_articulo']);
                    }

                    // Actualizar el stock actual
                    $stmtUpd = $pdo->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ?");
                    $stmtUpd->execute([$item['cantidad'], $inv['id_inventario']]);

                    // Registrar movimiento de salida
                    $stmtMov = $pdo->prepare("INSERT INTO movimiento_inventario (id_inventario, id_empleado, tipo_movimiento, cantidad, fecha_hora, motivo) VALUES (?, ?, 'SALIDA', ?, NOW(), 'VENTA')");
                    $stmtMov->execute([$inv['id_inventario'], $id_empleado, $item['cantidad']]);
                }
            }

            // Confirmar transacción
            $pdo->commit();

            // Redirigir a la emisión del comprobante
            header("Location: comprobante.php?id=" . $id_venta);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje_error = "Falló el registro de la venta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Venta - SIG-LMG</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function agregarRenglon() {
            const container = document.getElementById('detalles_venta');
            const selectArticulo = document.getElementById('select_articulo');
            const cantidadInput = document.getElementById('input_cantidad');

            const idArticulo = selectArticulo.value;
            const nombreArticulo = selectArticulo.options[selectArticulo.selectedIndex].text;
            const cantidad = cantidadInput.value;

            if (!idArticulo || cantidad <= 0) {
                alert('Selecciona un artículo y una cantidad válida.');
                return;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${nombreArticulo}<input type="hidden" name="articulo_id[]" value="${idArticulo}"></td>
                <td>${cantidad}<input type="hidden" name="cantidad[]" value="${cantidad}"></td>
                <td><button type="button" class="btn" style="background-color: #ef4444;" onclick="this.closest('tr').remove()">Quitar</button></td>
            `;
            container.appendChild(tr);

            cantidadInput.value = 1;
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Registrar Nueva Venta</h1>
        <p><a href="../index.php" class="btn" style="text-decoration: none; padding: 0.3rem 0.6rem;">← Volver al Inicio</a></p>

        <?php if ($mensaje_error): ?>
            <p style="color: #dc2626; font-weight: bold;"><?= htmlspecialchars($mensaje_error) ?></p>
        <?php endif; ?>

        <form action="nueva_venta.php" method="POST">
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="flex: 1;">
                    <label>Sucursal:</label>
                    <select name="id_sucursal" class="form-control" required>
                        <?php foreach ($sucursales as $s): ?>
                            <option value="<?= $s['id_sucursal'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Atendido por (Empleado):</label>
                    <select name="id_empleado" class="form-control" required>
                        <?php foreach ($empleados as $e): ?>
                            <option value="<?= $e['id_empleado'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['puesto']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3>Agregar Conceptos</h3>
            <div style="display: flex; gap: 1rem; align-items: flex-end; margin-bottom: 1rem;">
                <div style="flex: 2;">
                    <label>Artículo o Servicio:</label>
                    <select id="select_articulo" class="form-control" style="margin-bottom:0;">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($articulos as $a): ?>
                            <option value="<?= $a['id_articulo'] ?>"><?= htmlspecialchars($a['nombre']) ?> - $<?= number_format($a['precio_unitario'], 2) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Cantidad:</label>
                    <input type="number" id="input_cantidad" value="1" min="1" step="1" class="form-control" style="margin-bottom:0;">
                </div>
                <div>
                    <button type="button" class="btn" onclick="agregarRenglon()">+ Agregar Renglón</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Artículo / Servicio</th>
                        <th>Cantidad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="detalles_venta">
                    <!-- Los elementos añadidos dinámicamente aparecerán aquí -->
                </tbody>
            </table>

            <div style="margin-top: 1.5rem; text-align: right;">
                <button type="submit" name="confirmar_venta" class="btn" style="background-color: #16a34a; font-size: 1.1rem; padding: 0.7rem 1.5rem;">Confirmar Venta y Cobrar</button>
            </div>
        </form>
    </div>
</body>
</html>