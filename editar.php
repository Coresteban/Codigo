
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

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $conexion->prepare(
    "SELECT nombre, categoria, marca, precio, stock, disponible
     FROM productos
     WHERE id_producto = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$stmt->bind_result(
    $nombreActual,
    $categoriaActual,
    $marcaActual,
    $precioActual,
    $stockActual,
    $disponibleActual
);

if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$producto = [
    "nombre" => $nombreActual,
    "categoria" => $categoriaActual,
    "marca" => $marcaActual,
    "precio" => $precioActual,
    "stock" => $stockActual,
    "disponible" => $disponibleActual
];

$stmt->close();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["actualizar"])) {
    $nombre = trim($_POST["nombre"] ?? "");
    $categoria = $_POST["categoria"] ?? "";
    $marca = trim($_POST["marca"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $stock = $_POST["stock"] ?? "";

    $producto = [
        "nombre" => $nombre,
        "categoria" => $categoria,
        "marca" => $marca,
        "precio" => $precio,
        "stock" => $stock,
        "disponible" => is_numeric($stock) && (float)$stock > 0 ? 1 : 0
    ];

    if (
        $nombre === "" ||
        $marca === "" ||
        !in_array($categoria, $categoriasValidas, true) ||
        !is_numeric($precio) ||
        !is_finite((float)$precio) ||
        (float)$precio < 0 ||
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0
    ) {
        $error = "Verifica los datos. El precio y las existencias deben ser válidos.";
    } else {
        $precio = (float)$precio;
        $stock = (int)$stock;
        $disponible = $stock > 0 ? 1 : 0;

        $stmt = $conexion->prepare(
            "UPDATE productos
             SET nombre = ?,
                 categoria = ?,
                 marca = ?,
                 precio = ?,
                 stock = ?,
                 disponible = ?
             WHERE id_producto = ?"
        );

        $stmt->bind_param(
            "sssdiii",
            $nombre,
            $categoria,
            $marca,
            $precio,
            $stock,
            $disponible,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?mensaje=actualizado");
            exit;
        }

        $error = "No se pudieron guardar los cambios.";
        $stmt->close();
    }
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TechStore | Editar producto</title>

    <style>
        :root {
            --fondo: #080e1b;
            --panel: #111b2e;
            --texto: #edf3ff;
            --secundario: #94a7c4;
            --azul: #3984ff;
            --azul-claro: #72aaff;
            --borde: #263751;
            --verde: #36d399;
            --rojo: #fb7185;
            --amarillo: #fbbf24;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at top left, #14284b 0, transparent 42%),
                var(--fondo);
            color: var(--texto);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .contenedor {
            width: min(1180px, 94%);
            margin: auto;
            padding: 30px 0 45px;
        }

        .barra-superior {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 35px;
        }

        .identidad {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .logo {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: linear-gradient(135deg, #3984ff, #6559e8);
            box-shadow: 0 8px 25px #3984ff30;
            font-size: 25px;
            font-weight: bold;
        }

        .nombre-tienda {
            font-size: 21px;
            font-weight: 750;
            letter-spacing: -.5px;
        }

        .nombre-tienda span {
            color: var(--azul-claro);
        }

        .submarca {
            color: var(--secundario);
            font-size: 12px;
            margin-top: 4px;
        }

        .volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
            border: 1px solid var(--borde);
            border-radius: 11px;
            background: #111b2eaa;
            color: var(--texto);
            text-decoration: none;
            font-size: 13px;
            transition: .2s;
        }

        .volver:hover {
            border-color: var(--azul);
            background: #192a44;
        }

        .introduccion {
            margin-bottom: 25px;
        }

        .sobretitulo {
            display: inline-block;
            color: var(--azul-claro);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0;
            font-size: clamp(27px, 4vw, 37px);
            letter-spacing: -1px;
        }

        .introduccion p {
            color: var(--secundario);
            margin: 10px 0 0;
            line-height: 1.6;
        }

        .rejilla {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(270px, .85fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            min-width: 0;
            padding: 25px;
            border: 1px solid var(--borde);
            border-radius: 19px;
            background: linear-gradient(145deg, #121e32, #0f192b);
            box-shadow: 0 18px 50px #00000018;
        }

        .titulo-panel {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .icono-panel {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #1b3558;
            color: var(--azul-claro);
            font-size: 20px;
        }

        h2 {
            margin: 0;
            font-size: 17px;
        }

        .descripcion-panel {
            margin: 5px 0 0;
            color: var(--secundario);
            font-size: 12px;
        }

        .separador {
            height: 1px;
            background: var(--borde);
            margin: 22px 0;
        }

        .grupo {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #c8d5e9;
            font-size: 12px;
            font-weight: 650;
        }

        input,
        select {
            display: block;
            width: 100%;
            min-height: 46px;
            margin: 0;
            padding: 12px 13px;
            border: 1px solid #2b3d58;
            border-radius: 10px;
            background: #0b1425;
            color: var(--texto);
            font: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus,
        select:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 3px #3984ff1b;
        }

        select:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        input::placeholder {
            color: #62748e;
        }

        .ayuda {
            color: var(--secundario);
            font-size: 11px;
            margin: 7px 0 0;
            line-height: 1.5;
        }

        .dos-columnas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .botones {
            display: flex;
            gap: 11px;
            margin-top: 26px;
        }

        .boton {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 17px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s, background .2s, border-color .2s;
        }

        .boton:hover {
            transform: translateY(-1px);
        }

        .guardar {
            flex: 1;
            background: linear-gradient(135deg, #3984ff, #3168e8);
            color: white;
            box-shadow: 0 8px 20px #3984ff20;
        }

        .guardar:hover {
            background: linear-gradient(135deg, #5595ff, #3978f5);
        }

        .cancelar {
            border-color: var(--borde);
            background: #18253a;
            color: #d2def0;
        }

        .cancelar:hover {
            border-color: #526581;
        }

        .alerta {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #843b4b;
            border-radius: 10px;
            background: #421f2b;
            color: #ffb1bd;
            font-size: 13px;
            line-height: 1.5;
        }

        .producto-destacado {
            padding: 20px;
            border: 1px solid #2b4264;
            border-radius: 15px;
            background:
                radial-gradient(circle at top right, #3984ff20, transparent 60%),
                #0d182a;
            margin-bottom: 22px;
        }

        .producto-icono {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: #1a3150;
            font-size: 26px;
            margin-bottom: 16px;
        }

        .producto-etiqueta {
            color: var(--azul-claro);
            font-size: 10px;
            letter-spacing: 1.4px;
            font-weight: 750;
            text-transform: uppercase;
        }

        .producto-nombre {
            margin: 8px 0 6px;
            font-size: 20px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .producto-marca {
            color: var(--secundario);
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .precio-etiqueta {
            color: var(--secundario);
            font-size: 11px;
            margin-top: 22px;
        }

        .precio-grande {
            margin-top: 5px;
            color: var(--verde);
            font-size: 30px;
            font-weight: 750;
            letter-spacing: -.7px;
            overflow-wrap: anywhere;
        }

        .lista-resumen {
            display: grid;
            gap: 0;
        }

        .fila-resumen {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid var(--borde);
            font-size: 12px;
        }

        .fila-resumen:last-child {
            border-bottom: 0;
        }

        .fila-resumen span:first-child {
            color: var(--secundario);
        }

        .fila-resumen strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .stock-indicador {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 8px;
            background: #123c32;
            color: var(--verde);
            font-size: 11px;
        }

        .stock-indicador.bajo {
            background: #46371c;
            color: var(--amarillo);
        }

        .stock-indicador.agotado {
            background: #45202b;
            color: #ff9eae;
        }

        .punto {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .nota-seguridad {
            margin-top: 20px;
            padding: 14px;
            border: 1px solid var(--borde);
            border-radius: 12px;
            color: var(--secundario);
            font-size: 11px;
            line-height: 1.7;
        }

        .nota-seguridad strong {
            display: block;
            color: #cbd8ec;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .pie {
            padding-top: 22px;
            text-align: center;
            color: #71839e;
            font-size: 11px;
        }

        @media (max-width: 850px) {
            .rejilla {
                grid-template-columns: 1fr;
            }

            .resumen {
                order: -1;
            }

            .producto-destacado {
                display: grid;
                grid-template-columns: auto 1fr;
                align-items: center;
                gap: 0 16px;
            }

            .producto-icono {
                grid-row: span 3;
                margin: 0;
            }

            .precio-etiqueta {
                margin-top: 14px;
            }
        }

        @media (max-width: 520px) {
            .contenedor {
                width: 93%;
                padding-top: 20px;
            }

            .barra-superior {
                align-items: flex-start;
            }

            .nombre-tienda {
                font-size: 18px;
            }

            .volver {
                padding: 10px;
                font-size: 12px;
            }

            .panel {
                padding: 18px;
                border-radius: 15px;
            }

            .dos-columnas {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .botones {
                flex-direction: column;
            }

            .producto-nombre {
                font-size: 17px;
            }

            .precio-grande {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="barra-superior">
        <div class="identidad">
            <div class="logo">⌘</div>
            <div>
                <div class="nombre-tienda">Tech<span>Store</span></div>
                <div class="submarca">Administración de inventario</div>
            </div>
        </div>

        <a href="index.php" class="volver">
            <span>←</span> Volver al inventario
        </a>
    </header>

    <section class="introduccion">
        <span class="sobretitulo">Gestión de productos</span>
        <h1>Editar componente</h1>
        <p>
            Actualiza los datos del producto y mantén tu inventario al día.
        </p>
    </section>

    <div class="rejilla">

        <!-- Formulario -->
        <section class="panel">

            <div class="titulo-panel">
                <div class="icono-panel">✎</div>
                <div>
                    <h2>Información del producto</h2>
                    <p class="descripcion-panel">
                        Producto ID #<?= e($id) ?>
                    </p>
                </div>
            </div>

            <?php if ($error !== ""): ?>
                <div class="alerta">
                    ⚠ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar.php?id=<?= e($id) ?>">

                <div class="grupo">
                    <label for="categoria">Categoría del producto</label>

                    <select id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>

                        <?php foreach ($categoriasValidas as $cat): ?>
                            <option
                                value="<?= e($cat) ?>"
                                <?= $producto["categoria"] === $cat ? "selected" : "" ?>
                            >
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grupo">
                    <label for="nombre">Nombre del componente</label>

                    <select id="nombre" name="nombre" required>
                        <option value="">Selecciona un componente</option>
                    </select>

                    <p class="ayuda">
                        Los componentes disponibles dependen de la categoría elegida.
                    </p>
                </div>

                <div class="grupo">
                    <label for="marca">Marca del fabricante</label>

                    <select id="marca" name="marca" required>
                        <option value="">Selecciona una marca</option>
                    </select>
                </div>

                <div class="separador"></div>

                <div class="dos-columnas">
                    <div class="grupo">
                        <label for="precio">Precio de venta (MXN)</label>

                        <input
                            type="number"
                            id="precio"
                            name="precio"
                            min="0"
                            step="0.01"
                            value="<?= e($producto["precio"]) ?>"
                            required
                        >

                        <p class="ayuda">Precio por unidad.</p>
                    </div>

                    <div class="grupo">
                        <label for="stock">Existencias</label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?= e($producto["stock"]) ?>"
                            required
                        >

                        <p class="ayuda">
                            Cantidad de unidades en inventario.
                        </p>
                    </div>
                </div>

                <div class="botones">
                    <button
                        type="submit"
                        name="actualizar"
                        class="boton guardar"
                    >
                        ✓ Guardar cambios
                    </button>

                    <a href="index.php" class="boton cancelar">
                        Cancelar
                    </a>
                </div>

            </form>
        </section>

        <!-- Vista previa -->
        <aside class="panel resumen">

            <div class="titulo-panel">
                <div class="icono-panel">▣</div>
                <div>
                    <h2>Vista previa</h2>
                    <p class="descripcion-panel">
                        Resumen del componente
                    </p>
                </div>
            </div>

            <div class="producto-destacado">
                <div class="producto-icono">▤</div>

                <div class="producto-etiqueta">Producto en edición</div>

                <h3 class="producto-nombre" id="vistaNombre">
                    <?= e($producto["nombre"]) ?>
                </h3>

                <div class="producto-marca" id="vistaMarca">
                    <?= e($producto["marca"]) ?>
                </div>

                <div class="precio-etiqueta">Precio de venta</div>

                <div class="precio-grande" id="vistaPrecio">
                    $<?= number_format((float)$producto["precio"], 2) ?>
                </div>
            </div>

            <div class="lista-resumen">

                <div class="fila-resumen">
                    <span>Categoría</span>
                    <strong id="vistaCategoria">
                        <?= e($producto["categoria"]) ?>
                    </strong>
                </div>

                <div class="fila-resumen">
                    <span>Existencias</span>
                    <strong id="vistaStockNumero">
                        <?= e($producto["stock"]) ?> unidades
                    </strong>
                </div>

                <div class="fila-resumen">
                    <span>Estado del inventario</span>
                    <strong>
                        <span
                            class="stock-indicador <?= (int)$producto["stock"] === 0 ? "agotado" : ((int)$producto["stock"] <= 5 ? "bajo" : "") ?>"
                            id="vistaEstado"
                        >
                            <span class="punto"></span>

                            <span id="vistaEstadoTexto">
                                <?php
                                if ((int)$producto["stock"] === 0) {
                                    echo "Agotado";
                                } elseif ((int)$producto["stock"] <= 5) {
                                    echo "Stock bajo";
                                } else {
                                    echo "Disponible";
                                }
                                ?>
                            </span>
                        </span>
                    </strong>
                </div>

            </div>

            <div class="nota-seguridad">
                <strong>ⓘ Información importante</strong>
                Los cambios se guardarán en la base de datos cuando pulses
                <b>Guardar cambios</b>. Si cancelas, regresarás al inventario
                sin aplicar las modificaciones.
            </div>

        </aside>

    </div>

    <footer class="pie">
        TechStore · Administración de componentes
    </footer>

</div>

<script>
// Catálogo de componentes y sus marcas
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

const campoPrecio = document.getElementById("precio");
const campoStock = document.getElementById("stock");

const nombreActual = <?= json_encode(
    $producto["nombre"],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const marcaActual = <?= json_encode(
    $producto["marca"],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

function llenarSelect(select, opciones, mensaje, seleccionado = "") {
    select.innerHTML = "";

    const inicial = document.createElement("option");
    inicial.value = "";
    inicial.textContent = mensaje;
    select.appendChild(inicial);

    opciones.forEach(opcion => {
        const elemento = document.createElement("option");
        elemento.value = opcion;
        elemento.textContent = opcion;

        if (opcion === seleccionado) {
            elemento.selected = true;
        }

        select.appendChild(elemento);
    });

    select.disabled = opciones.length === 0;
}

function actualizarComponentes(valorSeleccionado = "") {
    const lista = componentes[categoria.value] || {};
    const opciones = Object.keys(lista);

    if (valorSeleccionado && !opciones.includes(valorSeleccionado)) {
        opciones.push(valorSeleccionado);
    }

    llenarSelect(
        nombre,
        opciones,
        "Selecciona un componente",
        valorSeleccionado
    );
}

function actualizarMarcas(valorSeleccionado = "") {
    const lista = componentes[categoria.value] || {};
    const opciones = [...(lista[nombre.value] || [])];

    if (valorSeleccionado && !opciones.includes(valorSeleccionado)) {
        opciones.push(valorSeleccionado);
    }

    llenarSelect(
        marca,
        opciones,
        "Selecciona una marca",
        valorSeleccionado
    );
}

categoria.addEventListener("change", function () {
    actualizarComponentes();
    actualizarMarcas();
    actualizarVistaPrevia();
});

nombre.addEventListener("change", function () {
    actualizarMarcas();
    actualizarVistaPrevia();
});

marca.addEventListener("change", actualizarVistaPrevia);

actualizarComponentes(nombreActual);
actualizarMarcas(marcaActual);

function actualizarVistaPrevia() {
    document.getElementById("vistaNombre").textContent =
        nombre.value || "Sin nombre";

    document.getElementById("vistaMarca").textContent =
        marca.value || "Sin marca";

    document.getElementById("vistaCategoria").textContent =
        categoria.value || "Sin categoría";

    const precio = Number(campoPrecio.value);

    document.getElementById("vistaPrecio").textContent =
        "$" + (
            Number.isFinite(precio) && campoPrecio.value !== ""
                ? precio.toLocaleString("es-MX", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
                : "0.00"
        );

    const stock = Math.max(
        0,
        parseInt(campoStock.value, 10) || 0
    );

    document.getElementById("vistaStockNumero").textContent =
        stock + (stock === 1 ? " unidad" : " unidades");

    const estado = document.getElementById("vistaEstado");
    const textoEstado = document.getElementById("vistaEstadoTexto");

    estado.classList.remove("bajo", "agotado");

    if (stock === 0) {
        estado.classList.add("agotado");
        textoEstado.textContent = "Agotado";
    } else if (stock <= 5) {
        estado.classList.add("bajo");
        textoEstado.textContent = "Stock bajo";
    } else {
        textoEstado.textContent = "Disponible";
    }
}

[campoPrecio, campoStock].forEach(campo => {
    campo.addEventListener("input", actualizarVistaPrevia);
    campo.addEventListener("change", actualizarVistaPrevia);
});

actualizarVistaPrevia();
</script>

</body>
</html>
