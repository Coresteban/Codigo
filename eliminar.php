<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $conexion->prepare(
    "SELECT id_producto FROM productos WHERE id_producto = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$stmt->close();

$stmt = $conexion->prepare(
    "DELETE FROM productos WHERE id_producto = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();
    header("Location: index.php?mensaje=eliminado");
    exit;
}

$stmt->close();

header("Location: index.php");
exit;
?>
