
<?php
session_start();

// ========================================
// CONFIGURACIÓN Y SEGURIDAD
// ========================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conexion = require __DIR__ . '/../app/db.php';

$estadosValidos = [
    'Pendiente',
    'En proceso',
    'Completada'
];

// Evitar mostrar código HTML ingresado por el usuario.
function mostrar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Obtener un texto seguro de los formularios.
function textoFormulario($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

// Contar caracteres UTF-8 sin depender de la extensión mbstring.
function longitudTexto(string $texto): int
{
    $cantidad = preg_match_all('/./us', $texto);
    return $cantidad === false ? PHP_INT_MAX : $cantidad;
}

// Redirigir después de registrar, editar o eliminar.
function redirigir(string $mensaje, string $destino = 'index.php'): void
{
    $_SESSION['mensaje_flash'] = $mensaje;
    header('Location: ' . $destino, true, 303);
    exit;
}

// ========================================
// MENSAJES DEL SISTEMA
// ========================================

$mensajes = [
    'registrada' => 'Tarea registrada correctamente.',
    'actualizada' => 'Tarea actualizada correctamente.',
    'eliminada' => 'Tarea eliminada correctamente.',
    'no_existe' => 'La tarea seleccionada ya no existe.',
    'id_invalido' => 'El identificador de la tarea no es válido.',
    'datos_invalidos' => 'Revisa los campos señalados e inténtalo nuevamente.',
    'accion_invalida' => 'La operación solicitada no es válida.'
];

$mensajeClave = $_SESSION['mensaje_flash'] ?? '';
unset($_SESSION['mensaje_flash']);

$mensaje = is_string($mensajeClave)
    ? ($mensajes[$mensajeClave] ?? '')
    : '';

$mensajeExito = in_array(
    $mensajeClave,
    ['registrada', 'actualizada', 'eliminada'],
    true
);

// Recuperar los errores y los datos anteriores.
$errores = $_SESSION['errores_formulario'] ?? [];
$datosFormulario = $_SESSION['datos_formulario'] ?? [];

unset(
    $_SESSION['errores_formulario'],
    $_SESSION['datos_formulario']
);

if (!is_array($errores)) {
    $errores = [];
}

if (!is_array($datosFormulario)) {
    $datosFormulario = [];
}

// ========================================
// PROCESAR FORMULARIOS POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar protección CSRF.
    $tokenEnviado = $_POST['csrf_token'] ?? '';

    if (
        !is_string($tokenEnviado) ||
        !hash_equals($_SESSION['csrf_token'], $tokenEnviado)
    ) {
        http_response_code(403);
        exit(
            'Solicitud no autorizada. Recarga la página e inténtalo nuevamente.'
        );
    }

    $accion = textoFormulario($_POST['accion'] ?? '');

    // ====================================
    // ELIMINAR TAREA
    // ====================================

    if ($accion === 'eliminar') {

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            redirigir('id_invalido');
        }

        $consulta = $conexion->prepare(
            'DELETE FROM tareas WHERE id = :id'
        );

        $consulta->execute([
            ':id' => $id
        ]);

        if ($consulta->rowCount() > 0) {
            redirigir('eliminada');
        }

        redirigir('no_existe');
    }

    // Comprobar que la acción sea válida.
    if ($accion !== 'registrar' && $accion !== 'editar') {
        redirigir('accion_invalida');
    }

    // ====================================
    // RECIBIR DATOS DEL FORMULARIO
    // ====================================

    $titulo = textoFormulario($_POST['titulo'] ?? '');
    $materia = textoFormulario($_POST['materia'] ?? '');
    $descripcion = textoFormulario($_POST['descripcion'] ?? '');
    $fecha = textoFormulario($_POST['fecha_entrega'] ?? '');
    $estado = textoFormulario($_POST['estado'] ?? '');

    $id = null;

    // Comprobar identificador al editar.
    if ($accion === 'editar') {

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            redirigir('id_invalido');
        }

        // Verificar que la tarea exista.
        $consultaExiste = $conexion->prepare(
            'SELECT id FROM tareas WHERE id = :id'
        );

        $consultaExiste->execute([
            ':id' => $id
        ]);

        if (!$consultaExiste->fetch()) {
            redirigir('no_existe');
        }
    }

    // ====================================
    // VALIDACIONES EN PHP
    // ====================================

    $erroresValidacion = [];

    // Validar título.
    if (!preg_match('//u', $titulo) || !preg_match('//u', $materia) || !preg_match('//u', $descripcion)) {
        $erroresValidacion['titulo'] = 'El texto contiene caracteres no válidos.';
    }
    if ($titulo === '') {
        $erroresValidacion['titulo'] =
            'Debes escribir el título de la tarea.';
    } elseif (longitudTexto($titulo) > 120) {
        $erroresValidacion['titulo'] =
            'El título no puede superar los 120 caracteres.';
    }

    // Validar materia.
    if ($materia === '') {
        $erroresValidacion['materia'] =
            'Debes escribir el nombre de la materia.';
    } elseif (longitudTexto($materia) > 100) {
        $erroresValidacion['materia'] =
            'La materia no puede superar los 100 caracteres.';
    }

    // Evitar búsquedas y textos excesivamente largos en la base de datos.
    if (strlen($descripcion) > 65535) {
        $erroresValidacion['descripcion'] = 'La descripción es demasiado larga.';
    }

    // Validar fecha.
    if ($fecha === '') {

        $erroresValidacion['fecha_entrega'] =
            'Debes seleccionar una fecha de entrega.';

    } else {

        $fechaValida = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fecha
        );

        if (
            !$fechaValida ||
            $fechaValida->format('Y-m-d') !== $fecha
        ) {
            $erroresValidacion['fecha_entrega'] =
                'La fecha de entrega no es válida.';
        }
    }

    // Validar estado.
    if (!in_array($estado, $estadosValidos, true)) {
        $erroresValidacion['estado'] =
            'Selecciona un estado permitido.';
    }

    // Si hay errores, conservar la información.
    if (!empty($erroresValidacion)) {

        $_SESSION['errores_formulario'] = $erroresValidacion;

        $_SESSION['datos_formulario'] = [
            'accion' => $accion,
            'id' => $id,
            'titulo' => $titulo,
            'materia' => $materia,
            'descripcion' => $descripcion,
            'fecha_entrega' => $fecha,
            'estado' => $estado
        ];

        $destino = 'index.php';

        if ($accion === 'editar') {
            $destino .= '?editar=' . $id;
        }

        redirigir(
            'datos_invalidos',
            $destino . '#formulario-tarea'
        );
    }

    // ====================================
    // PREPARAR PARÁMETROS
    // ====================================

    $parametros = [
        ':titulo' => $titulo,
        ':materia' => $materia,
        ':descripcion' => $descripcion,
        ':fecha' => $fecha,
        ':estado' => $estado
    ];

    // ====================================
    // REGISTRAR NUEVA TAREA
    // ====================================

    if ($accion === 'registrar') {

        $consulta = $conexion->prepare(
            'INSERT INTO tareas
            (titulo, materia, descripcion, fecha_entrega, estado)
            VALUES
            (:titulo, :materia, :descripcion, :fecha, :estado)'
        );

        $consulta->execute($parametros);

        redirigir('registrada');
    }

    // ====================================
    // ACTUALIZAR TAREA
    // ====================================

    $parametros[':id'] = $id;

    $consulta = $conexion->prepare(
        'UPDATE tareas
         SET titulo = :titulo,
             materia = :materia,
             descripcion = :descripcion,
             fecha_entrega = :fecha,
             estado = :estado
         WHERE id = :id'
    );

    $consulta->execute($parametros);

    // Una actualización sin cambios también es válida si el registro existe.
    redirigir('actualizada');
}

// ========================================
// CARGAR TAREA PARA EDITAR
// ========================================

$tareaEditar = null;

$idEditar = filter_var(
    $_GET['editar'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $idEditar !== false &&
    $idEditar !== null &&
    $idEditar > 0
) {

    $consulta = $conexion->prepare(
        'SELECT * FROM tareas WHERE id = :id'
    );

    $consulta->execute([
        ':id' => $idEditar
    ]);

    $tareaEditar = $consulta->fetch();

    if (!$tareaEditar) {
        $mensaje = 'La tarea seleccionada no existe.';
        $mensajeExito = false;
    }
}

// ========================================
// RECUPERAR DATOS DEL FORMULARIO
// ========================================

$datos = $tareaEditar ?: [
    'titulo' => '',
    'materia' => '',
    'descripcion' => '',
    'fecha_entrega' => '',
    'estado' => 'Pendiente'
];

$recuperarFormulario = false;

if (!empty($datosFormulario)) {

    $accionAnterior = $datosFormulario['accion'] ?? '';

    if ($accionAnterior === 'registrar' && !$tareaEditar) {
        $recuperarFormulario = true;
    }

    if (
        $accionAnterior === 'editar' &&
        $tareaEditar &&
        (int) ($datosFormulario['id'] ?? 0) ===
        (int) $tareaEditar['id']
    ) {
        $recuperarFormulario = true;
    }

    if ($recuperarFormulario) {
        $datos = array_merge($datos, $datosFormulario);
    }
}

// ========================================
// ESTADÍSTICAS GENERALES
// ========================================

$estadisticas = $conexion->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(
            SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END),
            0
        ) AS pendientes,
        COALESCE(
            SUM(CASE WHEN estado = 'En proceso' THEN 1 ELSE 0 END),
            0
        ) AS en_proceso,
        COALESCE(
            SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END),
            0
        ) AS completadas
     FROM tareas"
)->fetch();

// ========================================
// BUSCAR Y FILTRAR TAREAS
// ========================================

$buscar = textoFormulario($_GET['buscar'] ?? '');
if (strlen($buscar) > 200) {
    $buscar = substr($buscar, 0, 200);
}
$filtroEstado = textoFormulario($_GET['filtro_estado'] ?? '');

if (
    $filtroEstado !== '' &&
    !in_array($filtroEstado, $estadosValidos, true)
) {
    $filtroEstado = '';
}

$sql = 'SELECT * FROM tareas WHERE 1 = 1';

$parametros = [];

if ($buscar !== '') {

    $sql .= ' AND (
        titulo LIKE :titulo
        OR materia LIKE :materia
    )';

    $parametros[':titulo'] = '%' . $buscar . '%';
    $parametros[':materia'] = '%' . $buscar . '%';
}

if ($filtroEstado !== '') {

    $sql .= ' AND estado = :estado';

    $parametros[':estado'] = $filtroEstado;
}

$sql .= ' ORDER BY id ASC';

$consulta = $conexion->prepare($sql);
$consulta->execute($parametros);

$tareas = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gestor de Tareas Académicas</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<!-- =====================================
     ENCABEZADO
===================================== -->

<header>
    <h1>Gestor de Tareas Académicas</h1>
    <p>Organiza y administra tus actividades universitarias</p>
</header>

<main>

    <!-- BIENVENIDA -->

    <section class="tarjeta">
        <h2>Bienvenido al sistema</h2>

        <p>
            Esta aplicación permite registrar, consultar,
            modificar y eliminar tareas académicas.
        </p>
    </section>

    <!-- ESTADÍSTICAS -->

    <section class="tarjeta">

        <h2>Estadísticas de tareas</h2>

        <div class="estadisticas">

            <div class="estadistica">
                <h3>Total de tareas</h3>
                <p><?= (int) $estadisticas['total'] ?></p>
            </div>

            <div class="estadistica">
                <h3>Pendientes</h3>
                <p><?= (int) $estadisticas['pendientes'] ?></p>
            </div>

            <div class="estadistica">
                <h3>En proceso</h3>
                <p><?= (int) $estadisticas['en_proceso'] ?></p>
            </div>

            <div class="estadistica">
                <h3>Completadas</h3>
                <p><?= (int) $estadisticas['completadas'] ?></p>
            </div>

        </div>
    </section>

    <!-- MENSAJES -->

    <?php if ($mensaje !== ''): ?>

        <div
            id="mensaje-temporal"
            class="mensaje-temporal <?= $mensajeExito ? 'mensaje-exito' : 'mensaje-error' ?>"
            role="status"
            aria-live="polite"
        >

            <span><?= mostrar($mensaje) ?></span>

            <button
                type="button"
                class="cerrar-mensaje"
                aria-label="Cerrar mensaje"
            >
                &times;
            </button>

        </div>

    <?php endif; ?>

    <!-- =================================
         REGISTRAR / EDITAR TAREA
    ================================= -->

    <section class="tarjeta" id="formulario-tarea">

        <h2>
            <?= $tareaEditar
                ? 'Editar tarea académica'
                : 'Registrar nueva tarea'
            ?>
        </h2>

        <form action="index.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= mostrar($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="accion"
                value="<?= $tareaEditar ? 'editar' : 'registrar' ?>"
            >

            <?php if ($tareaEditar): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $tareaEditar['id'] ?>"
                >

            <?php endif; ?>

            <!-- TÍTULO -->

            <label for="titulo">
                Título de la tarea
            </label>

            <input
                type="text"
                id="titulo"
                name="titulo"
                maxlength="120"
                value="<?= mostrar($datos['titulo'] ?? '') ?>"
                aria-invalid="<?= isset($errores['titulo']) ? 'true' : 'false' ?>"
                required
            >

            <?php if (isset($errores['titulo'])): ?>
                <p class="error-campo">
                    <?= mostrar($errores['titulo']) ?>
                </p>
            <?php endif; ?>

            <!-- MATERIA -->

            <label for="materia">
                Materia
            </label>

            <input
                type="text"
                id="materia"
                name="materia"
                maxlength="100"
                value="<?= mostrar($datos['materia'] ?? '') ?>"
                aria-invalid="<?= isset($errores['materia']) ? 'true' : 'false' ?>"
                required
            >

            <?php if (isset($errores['materia'])): ?>
                <p class="error-campo">
                    <?= mostrar($errores['materia']) ?>
                </p>
            <?php endif; ?>

            <!-- DESCRIPCIÓN -->

            <label for="descripcion">
                Descripción
            </label>

            <textarea
                id="descripcion"
                name="descripcion"
            ><?= mostrar($datos['descripcion'] ?? '') ?></textarea>

            <?php if (isset($errores['descripcion'])): ?>
                <p class="error-campo">
                    <?= mostrar($errores['descripcion']) ?>
                </p>
            <?php endif; ?>

            <!-- FECHA -->

            <label for="fecha_entrega">
                Fecha de entrega
            </label>

            <input
                type="date"
                id="fecha_entrega"
                name="fecha_entrega"
                value="<?= mostrar($datos['fecha_entrega'] ?? '') ?>"
                aria-invalid="<?= isset($errores['fecha_entrega']) ? 'true' : 'false' ?>"
                required
            >

            <?php if (isset($errores['fecha_entrega'])): ?>
                <p class="error-campo">
                    <?= mostrar($errores['fecha_entrega']) ?>
                </p>
            <?php endif; ?>

            <!-- ESTADO -->

            <label for="estado">
                Estado
            </label>

            <select
                id="estado"
                name="estado"
                aria-invalid="<?= isset($errores['estado']) ? 'true' : 'false' ?>"
                required
            >

                <?php foreach ($estadosValidos as $opcion): ?>

                    <option
                        value="<?= mostrar($opcion) ?>"
                        <?= ($datos['estado'] ?? 'Pendiente') === $opcion
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= mostrar($opcion) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <?php if (isset($errores['estado'])): ?>
                <p class="error-campo">
                    <?= mostrar($errores['estado']) ?>
                </p>
            <?php endif; ?>

            <!-- GUARDAR -->

            <button type="submit">

                <?= $tareaEditar
                    ? 'Guardar cambios'
                    : 'Guardar tarea'
                ?>

            </button>

            <?php if ($tareaEditar): ?>

                <a href="index.php">
                    Cancelar edición
                </a>

            <?php endif; ?>

        </form>

    </section>

    <!-- =================================
         LISTADO DE TAREAS
    ================================= -->

    <section class="tarjeta" id="listado-tareas">

        <h2>Mis tareas académicas</h2>

        <!-- BÚSQUEDA Y FILTROS -->

        <form
            method="GET"
            action="index.php"
            class="formulario-filtros"
        >

            <label for="buscar">
                Buscar tarea
            </label>

            <input
                type="text"
                id="buscar"
                name="buscar"
                placeholder="Buscar por título o materia"
                value="<?= mostrar($buscar) ?>"
            >

            <label for="filtro_estado">
                Filtrar por estado
            </label>

            <select
                id="filtro_estado"
                name="filtro_estado"
            >

                <option value="">
                    Todos los estados
                </option>

                <?php foreach ($estadosValidos as $opcion): ?>

                    <option
                        value="<?= mostrar($opcion) ?>"
                        <?= $filtroEstado === $opcion
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= mostrar($opcion) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                Buscar y filtrar
            </button>

            <a href="index.php">
                Limpiar filtros
            </a>

        </form>

        <!-- TABLA -->

        <div class="tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Título</th>
                        <th>Materia</th>
                        <th>Descripción</th>
                        <th>Fecha de entrega</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($tareas)): ?>

                        <?php foreach ($tareas as $indice => $tarea): ?>

                            <tr>

                                <td>
                                    <?= $indice + 1 ?>
                                </td>

                                <td>
                                    <?= mostrar($tarea['titulo']) ?>
                                </td>

                                <td>
                                    <?= mostrar($tarea['materia']) ?>
                                </td>

                                <td>
                                    <?= mostrar($tarea['descripcion'] ?? '') ?>
                                </td>

                                <td>
                                    <?= mostrar($tarea['fecha_entrega']) ?>
                                </td>

                                <td>
                                    <?= mostrar($tarea['estado']) ?>
                                </td>

                                <td class="celda-acciones">

                                    <div class="acciones-tarea">

                                        <!-- EDITAR -->

                                        <a
                                            class="boton-editar"
                                            href="index.php?editar=<?= (int) $tarea['id'] ?>#formulario-tarea"
                                        >
                                            Editar
                                        </a>

                                        <!-- ELIMINAR -->

                                        <form
                                            action="index.php"
                                            method="POST"
                                            class="formulario-eliminar"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= mostrar($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="eliminar"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $tarea['id'] ?>"
                                            >

                                            <button
                                                type="button"
                                                class="boton-eliminar abrir-modal-eliminar"
                                                data-titulo="<?= mostrar($tarea['titulo']) ?>"
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
                            <td colspan="7">
                                No se encontraron tareas registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<!-- =====================================
     PIE DE PÁGINA
===================================== -->

<footer>

    <p>Gestor de Tareas Académicas</p>

    <p>
        Desarrollo de Aplicaciones Web - Sexto Ciclo
    </p>

</footer>

<!-- =====================================
     MODAL PARA CONFIRMAR ELIMINACIÓN
===================================== -->

<dialog
    id="modal-eliminar"
    class="modal-eliminar"
    aria-labelledby="modal-titulo"
    aria-describedby="modal-descripcion"
>

    <div class="modal-icono" aria-hidden="true">
        !
    </div>

    <h2 id="modal-titulo">
        ¿Eliminar tarea?
    </h2>

    <p id="modal-descripcion">
        ¿Estás seguro de que deseas eliminar
        <strong id="nombre-tarea-eliminar"></strong>?
    </p>

    <p class="modal-advertencia">
        Esta acción no se puede deshacer.
    </p>

    <div class="modal-acciones">

        <button
            type="button"
            id="cancelar-eliminar"
            class="modal-cancelar"
        >
            Cancelar
        </button>

        <button
            type="button"
            id="confirmar-eliminar"
            class="modal-confirmar"
        >
            Sí, eliminar
        </button>

    </div>

</dialog>

<!-- =====================================
     ESTILOS COMPLEMENTARIOS
===================================== -->

<style>

.mensaje-temporal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 15px 20px;
    margin-bottom: 24px;
    border-radius: 10px;
    border: 1px solid;
    transition: opacity .25s ease;
}

.mensaje-exito {
    background: #ecfdf5;
    border-color: #86efac;
    color: #166534;
}

.mensaje-error {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #991b1b;
}

.cerrar-mensaje {
    background: transparent;
    color: inherit;
    border: 0;
    padding: 0 4px;
    margin: 0;
    font-size: 26px;
    cursor: pointer;
}

.cerrar-mensaje:hover {
    background: transparent;
    opacity: .7;
}

.error-campo {
    color: #b91c1c;
    font-size: 14px;
    margin: 6px 0 10px;
    font-weight: bold;
}

input[aria-invalid="true"],
select[aria-invalid="true"] {
    border: 2px solid #dc2626;
}

.modal-eliminar {
    width: min(92vw, 440px);
    max-width: 440px;
    border: 0;
    border-radius: 16px;
    padding: 28px;
    color: #1e293b;
    box-shadow: 0 22px 70px rgba(15,23,42,.25);
}

.modal-eliminar::backdrop {
    background: rgba(15,23,42,.62);
}

.modal-eliminar h2 {
    margin: 0 0 12px;
    font-size: 24px;
}

.modal-eliminar p {
    line-height: 1.55;
    overflow-wrap: anywhere;
}

.modal-icono {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    margin-bottom: 16px;
    border-radius: 50%;
    background: #fee2e2;
    color: #b91c1c;
    font-weight: 700;
    font-size: 26px;
}

.modal-advertencia {
    color: #64748b;
    font-size: 14px;
}

.modal-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.modal-acciones button {
    margin: 0;
    min-height: 44px;
    padding: 10px 18px;
}

.modal-cancelar {
    background: #e2e8f0;
    color: #0f172a;
}

.modal-cancelar:hover {
    background: #cbd5e1;
}

.modal-confirmar {
    background: #dc2626;
    color: #fff;
}

.modal-confirmar:hover {
    background: #b91c1c;
}

@media (max-width: 420px) {
    .modal-acciones {
        flex-direction: column-reverse;
    }

    .modal-acciones button {
        width: 100%;
    }
}

</style>

<!-- =====================================
     JAVASCRIPT
===================================== -->

<script>

(() => {

    // Cerrar mensajes temporales.
    const aviso = document.getElementById('mensaje-temporal');

    if (aviso) {

        const cerrarAviso = () => {
            aviso.style.opacity = '0';

            window.setTimeout(() => {
                aviso.remove();
            }, 250);
        };

        const botonCerrar = aviso.querySelector('.cerrar-mensaje');

        if (botonCerrar) {
            botonCerrar.addEventListener('click', cerrarAviso);
        }

        // Mantener visibles los errores para poder leerlos.
        if (aviso.classList.contains('mensaje-exito')) {
            window.setTimeout(cerrarAviso, 4500);
        }
    }

    // Elementos del modal de eliminación.
    const modal = document.getElementById('modal-eliminar');
    const nombre = document.getElementById('nombre-tarea-eliminar');
    const cancelar = document.getElementById('cancelar-eliminar');
    const confirmar = document.getElementById('confirmar-eliminar');

    let formularioPendiente = null;

    // Abrir ventana de confirmación.
    document.querySelectorAll('.abrir-modal-eliminar').forEach(boton => {

        boton.addEventListener('click', () => {

            formularioPendiente = boton.closest('form');

            nombre.textContent = '“' + boton.dataset.titulo + '”';

            confirmar.disabled = false;
            confirmar.textContent = 'Sí, eliminar';

            modal.showModal();
        });

    });

    // Cancelar eliminación.
    cancelar.addEventListener('click', () => {
        modal.close();
    });

    // Cerrar haciendo clic fuera del contenido.
    modal.addEventListener('click', evento => {
        if (evento.target === modal) {
            modal.close();
        }
    });

    // Limpiar selección al cerrar.
    modal.addEventListener('close', () => {
        formularioPendiente = null;
    });

    // Confirmar eliminación.
    confirmar.addEventListener('click', () => {

        if (!formularioPendiente) {
            return;
        }

        confirmar.disabled = true;
        confirmar.textContent = 'Eliminando...';

        formularioPendiente.submit();
    });

})();

</script>

</body>
</html>
