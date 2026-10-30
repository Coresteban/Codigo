
<?php
require_once "conexion.php";

$categoriasValidas = [
    "Tarjeta gráfica",
    "Procesador",
    "Tarjeta madre",
    "Memoria RAM",
    "Almacenamiento",
    "Fuente de poder",
    "Gabinete",
    "Refrigeración",
    "Monitor",
    "Teclado",
    "Ratón",
    "Periférico",
    "Otro"
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["registrar"])) {
    $nombre = trim($_POST["nombre"] ?? "");
    $categoria = $_POST["categoria"] ?? "";
    $marca = trim($_POST["marca"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $stock = $_POST["stock"] ?? "";

    if (
        $nombre !== "" &&
        $marca !== "" &&
        in_array($categoria, $categoriasValidas, true) &&
        is_numeric($precio) &&
        (float)$precio >= 0 &&
        filter_var($stock, FILTER_VALIDATE_INT) !== false &&
        (int)$stock >= 0
    ) {
        $precio = (float)$precio;
        $stock = (int)$stock;
        $disponible = $stock > 0 ? 1 : 0;

        $stmt = $conexion->prepare(
            "INSERT INTO productos
            (nombre, categoria, marca, precio, stock, disponible)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssdii",
            $nombre,
            $categoria,
            $marca,
            $precio,
            $stock,
            $disponible
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?mensaje=registrado");
            exit;
        }

        $error = "No se pudo registrar el producto.";
        $stmt->close();
    } else {
        $error = "Revisa los datos ingresados. Todos los campos son obligatorios.";
    }
}

$busqueda = trim($_GET["buscar"] ?? "");
$filtro = $_GET["categoria"] ?? "";

$sql = "SELECT id_producto, nombre, categoria, marca, precio, stock, disponible
        FROM productos
        WHERE 1=1";

$parametros = [];
$tipos = "";

if ($busqueda !== "") {
    $sql .= " AND (nombre LIKE ? OR marca LIKE ?)";
    $termino = "%" . $busqueda . "%";

    $parametros[] = $termino;
    $parametros[] = $termino;
    $tipos .= "ss";
}

if (in_array($filtro, $categoriasValidas, true)) {
    $sql .= " AND categoria = ?";
    $parametros[] = $filtro;
    $tipos .= "s";
}

$sql .= " ORDER BY id_producto DESC";

$stmt = $conexion->prepare($sql);

if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();

$stmt->bind_result(
    $id,
    $nombreProducto,
    $categoriaProducto,
    $marcaProducto,
    $precioProducto,
    $stockProducto,
    $disponibleProducto
);

$productos = [];

while ($stmt->fetch()) {
    $productos[] = [
        "id" => $id,
        "nombre" => $nombreProducto,
        "categoria" => $categoriaProducto,
        "marca" => $marcaProducto,
        "precio" => $precioProducto,
        "stock" => $stockProducto,
        "disponible" => $disponibleProducto
    ];
}

$stmt->close();

$resultado = $conexion->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(stock), 0) AS unidades,
        COALESCE(SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END), 0) AS bajo_stock,
        COALESCE(SUM(CASE WHEN disponible = 1 THEN 1 ELSE 0 END), 0) AS disponibles
     FROM productos"
);

$estadisticas = $resultado->fetch_assoc();

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TechStore | Inventario</title>

    <style>
        :root {
            --fondo: #0b1120;
            --panel: #111b2e;
            --panel2: #17243a;
            --texto: #edf3ff;
            --secundario: #94a7c4;
            --azul: #3984ff;
            --borde: #253650;
            --verde: #36d399;
            --rojo: #fb7185;
            --amarillo: #fbbf24;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--fondo);
            color: var(--texto);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .contenedor {
            width: min(1450px, 94%);
            margin: auto;
            padding: 28px 0 45px;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            background: var(--azul);
            display: grid;
            place-items: center;
            font-size: 27px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
        }

        h2 {
            font-size: 19px;
            margin: 0 0 20px;
        }

        p {
            color: var(--secundario);
        }

        .subtitulo {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .etiqueta {
            padding: 9px 14px;
            border: 1px solid var(--borde);
            border-radius: 20px;
            color: var(--verde);
            font-size: 13px;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .tarjeta,
        .panel {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 17px;
        }

        .tarjeta {
            padding: 20px;
        }

        .tarjeta small {
            color: var(--secundario);
            font-size: 13px;
        }

        .numero {
            font-size: 29px;
            font-weight: 700;
            margin-top: 10px;
        }

        .azul {
            color: #73a9ff;
        }

        .verde {
            color: var(--verde);
        }

        .amarillo {
            color: var(--amarillo);
        }

        .rojo {
            color: var(--rojo);
        }

        .columnas {
            display: grid;
            grid-template-columns: 330px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .panel {
            padding: 23px;
            min-width: 0;
        }

        label {
            display: block;
            color: #bdcbe0;
            font-size: 13px;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            background: var(--fondo);
            border: 1px solid var(--borde);
            color: var(--texto);
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 15px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--azul);
        }

        select:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        button,
        .boton {
            border: 0;
            cursor: pointer;
            border-radius: 9px;
            padding: 11px 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 13px;
        }

        .principal {
            background: var(--azul);
            color: white;
        }

        .secundario {
            background: var(--panel2);
            color: var(--texto);
        }

        .peligro {
            background: #482333;
            color: #ff9eae;
        }

        .formulario button {
            width: 100%;
        }

        .barra-busqueda {
            display: grid;
            grid-template-columns: minmax(130px, 1fr) 190px auto auto;
            gap: 9px;
            margin-bottom: 18px;
        }

        .barra-busqueda input,
        .barra-busqueda select {
            margin: 0;
            min-width: 0;
        }

        .tabla-contenedor {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--borde);
            text-align: left;
            font-size: 13px;
        }

        th {
            color: var(--secundario);
            font-weight: 500;
        }

        tbody tr:hover {
            background: #152239;
        }

        .categoria {
            background: #1b3150;
            color: #9dc4ff;
            padding: 6px 9px;
            border-radius: 7px;
            font-size: 12px;
        }

        .estado {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 12px;
        }

        .estado.si {
            background: #123c32;
            color: var(--verde);
        }

        .estado.no {
            background: #45202b;
            color: #ff9eae;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .acciones form {
            margin: 0;
        }

        .acciones button,
        .acciones .boton {
            padding: 9px 10px;
        }

        .alerta {
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            background: #123c32;
            color: var(--verde);
        }

        .alerta.error {
            background: #45202b;
            color: #ff9eae;
        }

        .vacio {
            text-align: center;
            padding: 30px;
            color: var(--secundario);
        }

        @media (max-width: 1050px) {
            .columnas {
                grid-template-columns: 1fr;
            }

            .estadisticas {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .barra-busqueda {
                grid-template-columns: 1fr 1fr;
            }

            .barra-busqueda input {
                grid-column: 1 / -1;
            }

            .contenedor {
                width: 95%;
                padding-top: 18px;
            }

            .panel {
                padding: 15px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div class="marca">
            <div class="logo">⌘</div>
            <div>
                <h1>TechStore</h1>
                <p class="subtitulo">
                    Administración de componentes e inventario
                </p>
            </div>
        </div>

        <span class="etiqueta">● Sistema de inventario</span>
    </header>

    <?php if (isset($_GET["mensaje"])): ?>
        <div class="alerta">
            <?php
            if ($_GET["mensaje"] === "registrado") {
                echo "Producto registrado correctamente.";
            } elseif ($_GET["mensaje"] === "actualizado") {
                echo "Producto actualizado correctamente.";
            } elseif ($_GET["mensaje"] === "eliminado") {
                echo "Producto eliminado correctamente.";
            } else {
                echo "Operación completada.";
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alerta error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <section class="estadisticas">

        <div class="tarjeta">
            <small>Tipos de productos</small>
            <div class="numero azul">
                <?= e($estadisticas["total"]) ?>
            </div>
        </div>

        <div class="tarjeta">
            <small>Unidades en inventario</small>
            <div class="numero verde">
                <?= e($estadisticas["unidades"]) ?>
            </div>
        </div>

        <div class="tarjeta">
            <small>Productos con poco stock</small>
            <div class="numero amarillo">
                <?= e($estadisticas["bajo_stock"]) ?>
            </div>
            <small>5 unidades o menos</small>
        </div>

        <div class="tarjeta">
            <small>Productos disponibles</small>
            <div class="numero">
                <?= e($estadisticas["disponibles"]) ?>
            </div>
        </div>

    </section>

    <div class="columnas">

        <section class="panel">
            <h2>＋ Registrar componente</h2>

            <form class="formulario" method="POST" action="index.php">

                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categoriasValidas as $cat): ?>
                        <option value="<?= e($cat) ?>">
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="nombre">Nombre del componente</label>
                <select id="nombre" name="nombre" required disabled>
                    <option value="">
                        Primero selecciona una categoría
                    </option>
                </select>

                <label for="marca">Marca</label>
                <select id="marca" name="marca" required disabled>
                    <option value="">
                        Primero selecciona un componente
                    </option>
                </select>

                <label for="precio">Precio de venta (MXN)</label>
                <input
                    id="precio"
                    type="number"
                    name="precio"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    required
                >

                <label for="stock">Cantidad en inventario</label>
                <input
                    id="stock"
                    type="number"
                    name="stock"
                    min="0"
                    step="1"
                    placeholder="0"
                    required
                >

                <button
                    class="principal"
                    type="submit"
                    name="registrar"
                >
                    Registrar producto
                </button>

            </form>
        </section>


        <section class="panel">
            <h2>Inventario de componentes</h2>

            <form class="barra-busqueda" method="GET" action="index.php">

                <input
                    type="search"
                    name="buscar"
                    placeholder="Buscar por nombre o marca..."
                    value="<?= e($busqueda) ?>"
                >

                <select name="categoria">
                    <option value="">Todas las categorías</option>

                    <?php foreach ($categoriasValidas as $cat): ?>
                        <option
                            value="<?= e($cat) ?>"
                            <?= $filtro === $cat ? "selected" : "" ?>
                        >
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="principal" type="submit">
                    Buscar
                </button>

                <a class="boton secundario" href="index.php">
                    Limpiar
                </a>

            </form>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($productos) > 0): ?>

                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= e($p["nombre"]) ?></td>

                                <td>
                                    <span class="categoria">
                                        <?= e($p["categoria"]) ?>
                                    </span>
                                </td>

                                <td><?= e($p["marca"]) ?></td>

                                <td>
                                    $<?= number_format((float)$p["precio"], 2) ?>
                                </td>

                                <td>
                                    <?php if ($p["stock"] <= 5): ?>
                                        <span class="amarillo">
                                            <?= e($p["stock"]) ?>
                                        </span>
                                    <?php else: ?>
                                        <?= e($p["stock"]) ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($p["disponible"]): ?>
                                        <span class="estado si">
                                            Disponible
                                        </span>
                                    <?php else: ?>
                                        <span class="estado no">
                                            Agotado
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="acciones">

                                        <a
                                            class="boton secundario"
                                            href="editar.php?id=<?= e($p["id"]) ?>"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="eliminar.php"
                                            method="POST"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= e($p["id"]) ?>"
                                            >

                                            <button
                                                class="peligro"
                                                type="submit"
                                            >
                                                Eliminar
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="vacio">
                                No se encontraron productos.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>

<script>

const componentes = {
    "Tarjeta gráfica": {
        "GeForce RTX 4060": ["ASUS", "MSI", "Gigabyte", "Zotac"],
        "GeForce RTX 4070": ["ASUS", "MSI", "Gigabyte", "Zotac"],
        "GeForce RTX 5060": ["ASUS", "MSI", "Gigabyte", "Zotac"],
        "Radeon RX 7600": ["ASUS", "MSI", "Gigabyte", "Sapphire", "PowerColor"]
    },

    "Procesador": {
        "Ryzen 5 5600": ["AMD"],
        "Ryzen 7 5700X": ["AMD"],
        "Ryzen 5 7600": ["AMD"],
        "Intel Core i5-12400F": ["Intel"],
        "Intel Core i5-14400F": ["Intel"],
        "Intel Core i7-14700K": ["Intel"]
    },

    "Tarjeta madre": {
        "ROG Strix B550-F": ["ASUS"],
        "TUF Gaming B650-Plus": ["ASUS"],
        "MSI B650 Gaming Plus": ["MSI"],
        "B650 AORUS Elite": ["Gigabyte"],
        "B550M Pro4": ["ASRock"]
    },

    "Memoria RAM": {
        "Memoria RAM DDR4 16GB": ["Kingston", "Corsair", "Crucial", "G.Skill"],
        "Memoria RAM DDR5 32GB": ["Kingston", "Corsair", "Crucial", "G.Skill"]
    },

    "Almacenamiento": {
        "SSD NVMe 1TB": ["Samsung", "Kingston", "Western Digital", "Crucial"],
        "SSD NVMe 2TB": ["Samsung", "Kingston", "Western Digital", "Crucial"],
        "Disco duro HDD 2TB": ["Seagate", "Western Digital"]
    },

    "Fuente de poder": {
        "Fuente de poder 650W": ["Corsair", "Cooler Master", "Gigabyte", "MSI"],
        "Fuente de poder 750W": ["Corsair", "Cooler Master", "Gigabyte", "MSI"]
    },

    "Gabinete": {
        "Gabinete ATX": ["Corsair", "Cooler Master", "NZXT", "MSI", "DeepCool"]
    },

    "Refrigeración": {
        "Disipador para procesador": ["Cooler Master", "DeepCool", "Noctua", "Thermalright"],
        "Refrigeración líquida": ["Corsair", "NZXT", "Cooler Master", "DeepCool"]
    },

    "Monitor": {
        "Monitor 24 pulgadas": ["ASUS", "LG", "AOC", "Samsung", "Acer", "MSI"]
    },

    "Teclado": {
        "Teclado mecánico": ["Logitech", "Razer", "Redragon", "Corsair", "HyperX"]
    },

    "Ratón": {
        "Mouse gamer": ["Logitech", "Razer", "Redragon", "Corsair", "Attack Shark"]
    },

    "Periférico": {
        "Audífonos": ["Logitech", "Razer", "HyperX", "Corsair"],
        "Webcam": ["Logitech", "Razer", "ASUS"],
        "Bocinas": ["Logitech", "Creative"]
    },

    "Otro": {
        "Cable o adaptador": ["UGREEN", "Anker", "StarTech"],
        "Accesorio para computadora": ["ASUS", "MSI", "Genérico"]
    }
};

const categoria = document.getElementById("categoria");
const nombre = document.getElementById("nombre");
const marca = document.getElementById("marca");

function llenarSelect(select, opciones, mensaje) {
    select.innerHTML = "";

    const inicial = document.createElement("option");
    inicial.value = "";
    inicial.textContent = mensaje;
    select.appendChild(inicial);

    opciones.forEach(opcion => {
        const elemento = document.createElement("option");
        elemento.value = opcion;
        elemento.textContent = opcion;
        select.appendChild(elemento);
    });

    select.disabled = opciones.length === 0;
}

categoria.addEventListener("change", function () {
    const lista = componentes[categoria.value] || {};

    llenarSelect(
        nombre,
        Object.keys(lista),
        "Selecciona un componente"
    );

    llenarSelect(
        marca,
        [],
        "Primero selecciona un componente"
    );
});

nombre.addEventListener("change", function () {
    const lista = componentes[categoria.value] || {};
    const marcas = lista[nombre.value] || [];

    llenarSelect(
        marca,
        marcas,
        "Selecciona una marca"
    );
});
</script>

</body>
</html>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    