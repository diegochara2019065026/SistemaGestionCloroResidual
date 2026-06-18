<?php
session_start();
require 'conexion.php';
require 'includes/helpers.php';

require_admin();

$error = '';
$success = '';

$columnaMunicipalidad = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'municipalidad_id'");
if ($columnaMunicipalidad && $columnaMunicipalidad->num_rows === 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN municipalidad_id INT NULL AFTER rol_id");
    $conn->query("ALTER TABLE usuarios ADD INDEX idx_usuarios_municipalidad_id (municipalidad_id)");
}

$conn->query("
    CREATE TABLE IF NOT EXISTS usuario_centros_poblados (
        usuario_id INT NOT NULL,
        centro_poblado_id INT NOT NULL,
        PRIMARY KEY (usuario_id, centro_poblado_id),
        KEY idx_usuario_centros_centro (centro_poblado_id),
        CONSTRAINT fk_usuario_centros_usuario
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_usuario_centros_centro
            FOREIGN KEY (centro_poblado_id) REFERENCES centros_poblados (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

function sync_user_centros(mysqli $conn, int $usuario_id, array $centros_asignados): void
{
    $stmtEliminar = $conn->prepare("DELETE FROM usuario_centros_poblados WHERE usuario_id = ?");
    $stmtEliminar->bind_param("i", $usuario_id);
    $stmtEliminar->execute();

    if (!$centros_asignados) {
        return;
    }

    $stmtCentroExiste = $conn->prepare("SELECT id FROM centros_poblados WHERE id = ?");
    $stmtCentroUsuario = $conn->prepare("
        INSERT INTO usuario_centros_poblados (usuario_id, centro_poblado_id)
        VALUES (?, ?)
    ");

    foreach ($centros_asignados as $centro_id) {
        $stmtCentroExiste->bind_param("i", $centro_id);
        $stmtCentroExiste->execute();

        if ($stmtCentroExiste->get_result()->num_rows === 0) {
            throw new RuntimeException('Centro poblado invalido.');
        }

        $stmtCentroUsuario->bind_param("ii", $usuario_id, $centro_id);
        $stmtCentroUsuario->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "La solicitud no es valida. Recarga la pagina e intenta nuevamente.";
    }

    $action = $_POST['action'] ?? 'create';
    $usuario_id = (int) ($_POST['usuario_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $rol_id = (int) ($_POST['rol'] ?? 0);
    $municipalidad_id = ($_POST['municipalidad_id'] ?? '') === '' ? null : (int) $_POST['municipalidad_id'];
    $centros_asignados = array_map('intval', $_POST['centros_poblados'] ?? []);
    $centros_asignados = array_values(array_unique(array_filter($centros_asignados)));

    if (!$error && $action === 'toggle_status') {
        if ($usuario_id <= 0) {
            $error = "Selecciona un usuario valido.";
        } elseif ($usuario_id === (int) ($_SESSION['id'] ?? 0)) {
            $error = "No puedes desactivar tu propio usuario.";
        } else {
            $stmtUsuario = $conn->prepare("SELECT estado FROM usuarios WHERE id = ?");
            $stmtUsuario->bind_param("i", $usuario_id);
            $stmtUsuario->execute();
            $usuarioEstado = $stmtUsuario->get_result()->fetch_assoc();

            if (!$usuarioEstado) {
                $error = "El usuario seleccionado no existe.";
            } else {
                $nuevoEstado = ((int) $usuarioEstado['estado'] === 1) ? 0 : 1;
                $stmtEstado = $conn->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
                $stmtEstado->bind_param("ii", $nuevoEstado, $usuario_id);

                if ($stmtEstado->execute()) {
                    $success = $nuevoEstado === 1 ? "Usuario activado correctamente." : "Usuario desactivado correctamente.";
                } else {
                    $error = "No se pudo cambiar el estado del usuario.";
                }
            }
        }
    }

    if (!$error && in_array($action, ['create', 'update'], true)) {
        $esEdicion = $action === 'update';

        if ($esEdicion && $usuario_id <= 0) {
            $error = "Selecciona un usuario valido para editar.";
        } elseif ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $rol_id <= 0) {
            $error = "Completa nombre, correo y rol.";
        } elseif (!$esEdicion && strlen($contrasena) < 6) {
            $error = "La contrasena debe tener al menos 6 caracteres.";
        } elseif ($esEdicion && $contrasena !== '' && strlen($contrasena) < 6) {
            $error = "La nueva contrasena debe tener al menos 6 caracteres.";
        }
    }

    if (!$error && in_array($action, ['create', 'update'], true)) {
        $esEdicion = $action === 'update';
        $stmtRol = $conn->prepare("SELECT id, nombre_rol FROM roles WHERE id = ?");
        $stmtRol->bind_param("i", $rol_id);
        $stmtRol->execute();
        $rol = $stmtRol->get_result()->fetch_assoc();
        $esMunicipalidad = ($rol['nombre_rol'] ?? '') === 'Municipalidad';

        if (!$rol) {
            $error = "Selecciona un rol valido.";
        } elseif ($esMunicipalidad && (!$municipalidad_id || !$centros_asignados)) {
            $error = "Para un usuario Municipalidad selecciona su municipalidad y al menos un centro poblado.";
        } elseif ($esMunicipalidad) {
            $stmtMunicipalidad = $conn->prepare("SELECT id FROM municipalidades WHERE id = ?");
            $stmtMunicipalidad->bind_param("i", $municipalidad_id);
            $stmtMunicipalidad->execute();

            if ($stmtMunicipalidad->get_result()->num_rows === 0) {
                $error = "Selecciona una municipalidad valida.";
            }
        } else {
            $municipalidad_id = null;
            $centros_asignados = [];
        }

        if (!$error) {
            $stmtCorreo = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id <> ?");
            $stmtCorreo->bind_param("si", $correo, $usuario_id);
            $stmtCorreo->execute();

            if ($stmtCorreo->get_result()->num_rows > 0) {
                $error = "El correo ya esta registrado.";
            } else {
                $conn->begin_transaction();

                try {
                    if ($esEdicion) {
                        if ($contrasena !== '') {
                            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, rol_id = ?, municipalidad_id = ? WHERE id = ?");
                            $stmt->bind_param("sssiii", $nombre, $correo, $hash, $rol_id, $municipalidad_id, $usuario_id);
                        } else {
                            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, correo = ?, rol_id = ?, municipalidad_id = ? WHERE id = ?");
                            $stmt->bind_param("ssiii", $nombre, $correo, $rol_id, $municipalidad_id, $usuario_id);
                        }
                        $stmt->execute();
                    } else {
                        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol_id, municipalidad_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssii", $nombre, $correo, $hash, $rol_id, $municipalidad_id);
                        $stmt->execute();
                        $usuario_id = $conn->insert_id;
                    }

                    sync_user_centros($conn, $usuario_id, $centros_asignados);

                    $conn->commit();
                    $success = $esEdicion ? "Usuario actualizado correctamente." : "Usuario creado correctamente.";
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = $esEdicion ? "No se pudo actualizar el usuario. Intenta nuevamente." : "No se pudo crear el usuario. Intenta nuevamente.";
                }
            }
        }
    }
}

$rolesData = $conn->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol")->fetch_all(MYSQLI_ASSOC);
$municipalidadesData = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM municipalidades ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$centrosPobladosData = $conn->query("SELECT id, nombre, distrito, provincia, departamento FROM centros_poblados ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$editando = null;
$editCentros = [];
$editarId = (int) ($_GET['editar'] ?? 0);

if ($editarId > 0) {
    $stmtEditar = $conn->prepare("
        SELECT id, nombre, correo, rol_id, municipalidad_id, estado
        FROM usuarios
        WHERE id = ?
    ");
    $stmtEditar->bind_param("i", $editarId);
    $stmtEditar->execute();
    $editando = $stmtEditar->get_result()->fetch_assoc();

    if ($editando) {
        $stmtCentros = $conn->prepare("SELECT centro_poblado_id FROM usuario_centros_poblados WHERE usuario_id = ?");
        $stmtCentros->bind_param("i", $editarId);
        $stmtCentros->execute();
        $resultadoCentros = $stmtCentros->get_result();

        while ($centro = $resultadoCentros->fetch_assoc()) {
            $editCentros[] = (int) $centro['centro_poblado_id'];
        }
    }
}

$usuarios = $conn->query("
    SELECT u.id, u.nombre, u.correo, u.estado, u.fecha_creacion, r.nombre_rol,
           m.nombre AS municipalidad,
           GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') AS centros_asignados
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    LEFT JOIN municipalidades m ON u.municipalidad_id = m.id
    LEFT JOIN usuario_centros_poblados ucp ON ucp.usuario_id = u.id
    LEFT JOIN centros_poblados c ON c.id = ucp.centro_poblado_id
    GROUP BY u.id, u.nombre, u.correo, u.estado, u.fecha_creacion, r.nombre_rol, m.nombre
    ORDER BY u.fecha_creacion DESC, u.id DESC
");

$esFormularioEdicion = (bool) $editando;
$formAction = $esFormularioEdicion ? 'update' : 'create';
$formTitulo = $esFormularioEdicion ? 'Editar Usuario' : 'Crear Usuario';
$formDescripcion = $esFormularioEdicion ? 'Actualiza los datos del usuario seleccionado.' : 'Registra usuarios y asigna roles para el acceso al sistema.';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Usuarios</title>
<link rel="stylesheet" href="dashboard.css">
<style>
.page-title { margin-bottom: 18px; }
.page-title h2 { margin: 0 0 6px; font-size: 26px; color: #2f343b; }
.page-title p { margin: 0; color: #777; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 14px; margin-bottom: 16px; }
.form-grid label { display: flex; flex-direction: column; gap: 6px; color: #333; font-size: 14px; font-weight: bold; }
.form-grid input, .form-grid select { padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
.form-grid .full { grid-column: 1 / -1; }
.municipal-fields { display: none !important; }
.municipal-fields.visible { display: flex !important; }
.hint { color: #666; font-size: 12px; font-weight: normal; }
.form-actions, .row-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.button, button { padding: 10px 15px; border: none; border-radius: 6px; background: #c62828; color: #fff; cursor: pointer; text-decoration: none; font-size: 14px; }
.button.light, button.light { background: #eee; color: #333; }
.button.warning, button.warning { background: #5f6368; color: #fff; }
.button.small, button.small { padding: 7px 10px; font-size: 13px; }
.alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 14px; }
.alert.error { background: #fdecea; color: #b71c1c; }
.alert.success { background: #e8f5e9; color: #1b5e20; }
.section-title { margin: 24px 0 12px; color: #333; }
table { width: 100%; border-collapse: collapse; background: #fff; }
th, td { padding: 10px; border-bottom: 1px solid #e7e7e7; text-align: left; vertical-align: top; }
th { background: #f7f8fa; color: #666; font-size: 13px; text-transform: uppercase; }
tr:hover td { background: #fafafa; }
.status { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
.status.active { background: #e8f5e9; color: #1b5e20; }
.status.inactive { background: #f1f3f4; color: #5f6368; }
.inline-form { display: inline; margin: 0; }
@media (max-width: 800px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="sidebar">
    <div class="logo"><h2>DATASS</h2></div>
    <ul class="menu">
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="crear_usuario.php">Crear Usuario</a></li>
        <?php if (has_any_role(['Administrador', 'Municipalidad'])): ?>
            <li><a href="solicitar_asistencia.php">Solicitar Asistencia</a></li>
        <?php endif; ?>
        <?php if (has_any_role(['Administrador', 'Gobierno Regional'])): ?>
            <li><a href="gestionar_asistencia.php">Gestionar Asistencia</a></li>
        <?php endif; ?>
        <li>
            <a href="#" class="submenu-toggle">Mantenimiento &#9662;</a>
            <ul class="submenu">
                <li><a href="mantenimiento_centros.php">Centros Poblados</a></li>
                <li><a href="mantenimiento_municipalidades.php">Municipalidades</a></li>
                <li><a href="mantenimiento_jass.php">JASS</a></li>
                <li><a href="mantenimiento_sistemas_agua.php">Sistemas de Agua</a></li>
                <li><a href="mantenimiento_ficha.php">Ficha Cloracion</a></li>
            </ul>
        </li>
        <li><a href="logout.php">Cerrar sesion</a></li>
    </ul>
</div>

<div class="main">
    <header class="datass-header">
        <div class="datass-brand">
            <div class="brand-mark">PERU</div>
            <div class="brand-ministry">Ministerio de Vivienda, Construccion y Saneamiento</div>
            <strong>DATASS</strong>
        </div>
        <div class="datass-clock">
            <span class="clock-icon"></span>
            <div>
                <strong id="currentDateTime"></strong>
                <span>Tacna, Tacna, Peru (PE)</span>
            </div>
        </div>
        <div class="datass-user">
            <span class="bell-icon">!</span>
            <div class="user-avatar"><?php echo e(strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1))); ?></div>
            <div>
                <strong><?php echo e($_SESSION['nombre']); ?></strong>
                <span><?php echo e($_SESSION['rol']); ?></span>
            </div>
        </div>
    </header>

    <section class="content">
        <div class="page-title">
            <h2><?php echo e($formTitulo); ?></h2>
            <p><?php echo e($formDescripcion); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="action" value="<?php echo e($formAction); ?>">
            <input type="hidden" name="usuario_id" value="<?php echo e($editando['id'] ?? 0); ?>">
            <div class="form-grid">
                <label>
                    Nombre completo
                    <input type="text" name="nombre" value="<?php echo e($editando['nombre'] ?? ''); ?>" required>
                </label>
                <label>
                    Correo electronico
                    <input type="email" name="correo" value="<?php echo e($editando['correo'] ?? ''); ?>" required>
                </label>
                <label>
                    <?php echo $esFormularioEdicion ? 'Nueva contrasena' : 'Contrasena'; ?>
                    <input type="password" name="contrasena" minlength="6" <?php echo $esFormularioEdicion ? '' : 'required'; ?>>
                    <?php if ($esFormularioEdicion): ?>
                        <span class="hint">Dejala vacia para mantener la contrasena actual.</span>
                    <?php endif; ?>
                </label>
                <label>
                    Rol
                    <select name="rol" id="rol" required>
                        <option value="">Seleccionar rol</option>
                        <?php foreach ($rolesData as $r): ?>
                            <option value="<?php echo e($r['id']); ?>" data-rol="<?php echo e($r['nombre_rol']); ?>" <?php echo ((int) ($editando['rol_id'] ?? 0) === (int) $r['id']) ? 'selected' : ''; ?>>
                                <?php echo e($r['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="municipal-fields">
                    Municipalidad
                    <select name="municipalidad_id" id="municipalidad_id">
                        <option value="">Seleccionar municipalidad</option>
                        <?php foreach ($municipalidadesData as $m): ?>
                            <option value="<?php echo e($m['id']); ?>" <?php echo ((int) ($editando['municipalidad_id'] ?? 0) === (int) $m['id']) ? 'selected' : ''; ?>>
                                <?php echo e($m['nombre'] . ' - ' . $m['distrito'] . ', ' . $m['provincia']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="municipal-fields full">
                    Centros poblados que administra
                    <select name="centros_poblados[]" id="centros_poblados" multiple size="6">
                        <?php foreach ($centrosPobladosData as $c): ?>
                            <option value="<?php echo e($c['id']); ?>" <?php echo in_array((int) $c['id'], $editCentros, true) ? 'selected' : ''; ?>>
                                <?php echo e($c['nombre'] . ' - ' . $c['distrito'] . ', ' . $c['provincia']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Manten presionada la tecla Ctrl para seleccionar varios centros.</span>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit"><?php echo $esFormularioEdicion ? 'Guardar cambios' : 'Crear usuario'; ?></button>
                <?php if ($esFormularioEdicion): ?>
                    <a class="button light" href="crear_usuario.php">Cancelar edicion</a>
                <?php endif; ?>
                <a class="button light" href="dashboard.php">Volver</a>
            </div>
        </form>

        <h3 class="section-title">Usuarios registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Municipalidad</th>
                    <th>Centros asignados</th>
                    <th>Estado</th>
                    <th>Fecha creacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios->num_rows === 0): ?>
                    <tr><td colspan="9">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <?php $usuarioActivo = (int) $usuario['estado'] === 1; ?>
                    <tr>
                        <td><?php echo e($usuario['id']); ?></td>
                        <td><?php echo e($usuario['nombre']); ?></td>
                        <td><?php echo e($usuario['correo']); ?></td>
                        <td><?php echo e($usuario['nombre_rol']); ?></td>
                        <td><?php echo e($usuario['municipalidad'] ?? ''); ?></td>
                        <td><?php echo e($usuario['centros_asignados'] ?? ''); ?></td>
                        <td>
                            <span class="status <?php echo $usuarioActivo ? 'active' : 'inactive'; ?>">
                                <?php echo $usuarioActivo ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td><?php echo e($usuario['fecha_creacion']); ?></td>
                        <td>
                            <div class="row-actions">
                                <a class="button small light" href="crear_usuario.php?editar=<?php echo e($usuario['id']); ?>">Editar</a>
                                <form class="inline-form" method="post" onsubmit="return confirm('<?php echo $usuarioActivo ? 'Desactivar este usuario?' : 'Activar este usuario?'; ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="usuario_id" value="<?php echo e($usuario['id']); ?>">
                                    <button class="small warning" type="submit" <?php echo ((int) $usuario['id'] === (int) ($_SESSION['id'] ?? 0)) ? 'disabled' : ''; ?>>
                                        <?php echo $usuarioActivo ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
const toggles = document.querySelectorAll('.submenu-toggle');
toggles.forEach(t => {
    t.addEventListener('click', function(e) {
        e.preventDefault();
        this.nextElementSibling.classList.toggle('open');
    });
});

const rolSelect = document.getElementById('rol');
const municipalFields = document.querySelectorAll('.municipal-fields');
const municipalidadSelect = document.getElementById('municipalidad_id');
const centrosSelect = document.getElementById('centros_poblados');

function toggleMunicipalFields() {
    const selected = rolSelect.options[rolSelect.selectedIndex];
    const isMunicipalidad = selected && selected.dataset.rol === 'Municipalidad';

    municipalFields.forEach(field => field.classList.toggle('visible', isMunicipalidad));
    municipalidadSelect.required = isMunicipalidad;
    centrosSelect.required = isMunicipalidad;

    if (!isMunicipalidad) {
        municipalidadSelect.value = '';
        Array.from(centrosSelect.options).forEach(option => option.selected = false);
    }
}

rolSelect.addEventListener('change', toggleMunicipalFields);
toggleMunicipalFields();

function updateDateTime() {
    const target = document.getElementById('currentDateTime');
    const now = new Date();
    const date = now.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const time = now.toLocaleTimeString('es-PE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    target.textContent = `${date}, ${time}`;
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>
</body>
</html>
