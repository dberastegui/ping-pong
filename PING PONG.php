<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// --- CONFIGURACIÓN DE SEGURIDAD ---
define('ADMIN_PASSWORD', 'test2024'); 
define('DATA_FILE', 'torneo_data.json');

// --- FUNCIONES ---
function guardarDatos($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function verificarClave() {
    $clave_ingresada = isset($_POST['admin_password']) ? trim($_POST['admin_password']) : '';
    if ($clave_ingresada === ADMIN_PASSWORD) {
        unset($_SESSION['error_message']);
        return true;
    }
    $_SESSION['error_message'] = 'Clave de administrador incorrecta.';
    return false;
}

// --- LECTURA DE DATOS ---
$torneo = null;
if (file_exists(DATA_FILE)) {
    $torneo = json_decode(file_get_contents(DATA_FILE), true);
}


// --- LÓGICA DEL TORNEO ---

// 1. Iniciar un nuevo torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniciar_torneo'])) {
    $equipos = $_POST['equipo'];
    $nombres_equipos = array_filter(array_map('htmlspecialchars', $equipos));

    if (count($nombres_equipos) === 8) {
        $torneo = [
            'equipos' => $nombres_equipos,
            'partidos' => [
                ['eq1' => 0, 'eq2' => 1, 'res1' => null, 'res2' => null, 'nivel' => 'Cuartos'], ['eq1' => 2, 'eq2' => 3, 'res1' => null, 'res2' => null, 'nivel' => 'Cuartos'],
                ['eq1' => 4, 'eq2' => 5, 'res1' => null, 'res2' => null, 'nivel' => 'Cuartos'], ['eq1' => 6, 'eq2' => 7, 'res1' => null, 'res2' => null, 'nivel' => 'Cuartos'],
                ['eq1' => null, 'eq2' => null, 'res1' => null, 'res2' => null, 'nivel' => 'Semifinal'], ['eq1' => null, 'eq2' => null, 'res1' => null, 'res2' => null, 'nivel' => 'Semifinal'],
                ['eq1' => null, 'eq2' => null, 'res1' => null, 'res2' => null, 'nivel' => 'Final']
            ], 'ganador_torneo' => null
        ];
        guardarDatos($torneo);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// 2. Autenticarse para entrar al partido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_partido'])) {
    if (verificarClave()) {
        // Si la clave es correcta, se autentica para el partido específico
        $_SESSION['autenticado_para_partido'] = (int)$_POST['partido_id'];
    }
    // Redirigir a la misma URL (con el parámetro GET) para mostrar la vista correcta
    header("Location: " . $_SERVER['PHP_SELF'] . "?editar=" . (int)$_POST['partido_id']);
    exit();
}

// 3. Lógica de puntos 
$partido_id_actual = isset($_GET['editar']) ? (int)$_GET['editar'] : null;
$admin_autenticado_id = $_SESSION['autenticado_para_partido'] ?? null;

if ($partido_id_actual !== null && $partido_id_actual === $admin_autenticado_id && $torneo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $partido = &$torneo['partidos'][$partido_id_actual];
    $p1 = $partido['res1'] ?? 0; $p2 = $partido['res2'] ?? 0;
    $es_ganador = ($p1 >= 21 && $p1 >= $p2 + 2) || ($p2 >= 21 && $p2 >= $p1 + 2);

    $cambio = false;
    if (isset($_POST['sumar_punto_j1']) && !$es_ganador) { $partido['res1'] = ($partido['res1'] ?? 0) + 1; $cambio = true; }
    if (isset($_POST['sumar_punto_j2']) && !$es_ganador) { $partido['res2'] = ($partido['res2'] ?? 0) + 1; $cambio = true; }
    if (isset($_POST['restar_punto_j1']) && ($partido['res1'] ?? 0) > 0) { $partido['res1']--; $cambio = true; }
    if (isset($_POST['restar_punto_j2']) && ($partido['res2'] ?? 0) > 0) { $partido['res2']--; $cambio = true; }
    
    if ($cambio) {
        guardarDatos($torneo);
        header("Location: " . $_SERVER['PHP_SELF'] . "?editar=" . $partido_id_actual);
        exit();
    }
}

// 4. Volver al fixture (guarda cambios y cierra sesión de admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volver_al_fixture'])) {
    if ($partido_id_actual !== null && $partido_id_actual === $admin_autenticado_id && $torneo) {
        $partido = &$torneo['partidos'][$partido_id_actual];
        $partido['res1'] = $partido['res1'] ?? 0;
        $partido['res2'] = $partido['res2'] ?? 0;
        $p1 = $partido['res1']; $p2 = $partido['res2'];
        $ganador = null;
        if ($p1 >= 21 && $p1 >= $p2 + 2) $ganador = $partido['eq1'];
        elseif ($p2 >= 21 && $p2 >= $p1 + 2) $ganador = $partido['eq2'];

        if ($ganador !== null) {
            if ($partido_id_actual === 0) $torneo['partidos'][4]['eq1'] = $ganador; if ($partido_id_actual === 1) $torneo['partidos'][4]['eq2'] = $ganador;
            if ($partido_id_actual === 2) $torneo['partidos'][5]['eq1'] = $ganador; if ($partido_id_actual === 3) $torneo['partidos'][5]['eq2'] = $ganador;
            if ($partido_id_actual === 4) $torneo['partidos'][6]['eq1'] = $ganador; if ($partido_id_actual === 5) $torneo['partidos'][6]['eq2'] = $ganador;
            if ($partido_id_actual === 6) $torneo['ganador_torneo'] = $ganador;
        }
        guardarDatos($torneo);
    }
    unset($_SESSION['autenticado_para_partido']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


// 5. Reiniciar torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reiniciar_torneo'])) {
    if (file_exists(DATA_FILE)) unlink(DATA_FILE);
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Determinar la vista a mostrar
$vista = 'inicio';
if ($torneo) {
    $partido_id_actual = isset($_GET['editar']) ? (int)$_GET['editar'] : null;
    $admin_autenticado_id = $_SESSION['autenticado_para_partido'] ?? null;

    if ($partido_id_actual !== null) {
        $vista = ($partido_id_actual === $admin_autenticado_id) ? 'partido' : 'login_partido';
    } else {
        $vista = 'fixture';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneo PING PONG</title>
    <style>
        /* ESTILOS */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; color: #333; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; margin: 0; padding: 2em 0; }
        .container { background-color: #ffffff; padding: 2em; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); text-align: center; width: 95%; max-width: 1400px; }
        h1, h2 { color: #981E32; margin-bottom: 1em; }
        button { background-color: #981E32; color: white; border: none; padding: 12px 20px; font-size: 1em; border-radius: 6px; cursor: pointer; transition: background-color 0.3s, transform 0.1s; }
        button:hover { background-color: #7A1828; }
        button:disabled { background-color: #a0a0a0; cursor: not-allowed; }
        .btn-reiniciar { background-color: #c0392b; margin-top: 2em; }
        .btn-cancelar { background-color: #6c757d; }
        input[type="text"], input[type="password"] { padding: 10px; font-size: 1em; border: 1px solid #ccc; border-radius: 4px; }
        .configuracion-partidos { display: flex; flex-direction: column; gap: 1.5em; margin-bottom: 2em; }
        .partido-input-group { display: flex; align-items: center; justify-content: center; gap: 1em; }
        .partido-input-group input { width: 40%; max-width: 300px; }
        .vs-text { font-size: 1.2em; font-weight: bold; color: #981E32; }
        .fixture-container { display: flex; justify-content: space-between; align-items: center; text-align: left; }
        .ronda { display: flex; flex-direction: column; justify-content: space-around; flex-grow: 1; min-height: 500px; }
        .ronda h3 { text-align: center; margin: 0 0 1em 0; }
        .partido { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin: 10px; min-height: 80px; display: flex; flex-direction: column; justify-content: space-around; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .partido .equipo { font-weight: bold; display: flex; justify-content: space-between; padding: 4px 0; }
        .partido .equipo-placeholder { color: #aaa; font-style: italic; font-weight: normal; }
        .partido .resultado { color: #981E32; font-weight: bold; }
        .partido button { padding: 8px 12px; font-size: 0.9em; width: 100%; margin-top: 10px; }
        .campeon { text-align: center; }
        .trofeo { font-size: 3em; margin-bottom: 0.2em; }
        .marcador { display: flex; justify-content: space-around; margin: 2em 0; gap: 1em; }
        .jugador { padding: 1.5em; border: 1px solid #ddd; border-radius: 8px; width: 48%; display: flex; flex-direction: column; align-items: center; }
        .nombre { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; min-height: 4.5rem; }
        .puntos { font-size: 6em; font-weight: bold; color: #981E32; line-height: 1; }
        .controles-jugador { display: flex; justify-content: center; gap: 1em; margin-top: 1em; width: 100%; }
        .controles-jugador button { flex-grow: 0; width: 80px; }
        .btn-restar { background-color: #656669ff; }
        .ganador-mensaje { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 1em; border-radius: 8px; margin: 2em 0; font-size: 1.5em; font-weight: bold; }
        .login-container { max-width: 400px; margin: 4em auto; }
        .login-container .form-group { margin-bottom: 1em; }
        .login-container .form-actions { display: flex; gap: 1em; justify-content: center; }
        .error-mensaje { color: #D8000C; background-color: #FFD2D2; border: 1px solid; padding: 10px; border-radius: 6px; margin-bottom: 1em; }
    </style>
</head>
<body>

<div class="container">
    <h1>Torneo</h1>

    <?php if ($vista === 'inicio'): ?>
        <div class="formulario-inicio"><form action="<?=$_SERVER['PHP_SELF']?>" method="post"><div class="configuracion-partidos"><div class="partido-input-group"><input type="text" name="equipo[]" placeholder="Nombre del Equipo 1" required><span class="vs-text">VS</span><input type="text" name="equipo[]" placeholder="Nombre del Equipo 2" required></div><div class="partido-input-group"><input type="text" name="equipo[]" placeholder="Nombre del Equipo 3" required><span class="vs-text">VS</span><input type="text" name="equipo[]" placeholder="Nombre del Equipo 4" required></div><div class="partido-input-group"><input type="text" name="equipo[]" placeholder="Nombre del Equipo 5" required><span class="vs-text">VS</span><input type="text" name="equipo[]" placeholder="Nombre del Equipo 6" required></div><div class="partido-input-group"><input type="text" name="equipo[]" placeholder="Nombre del Equipo 7" required><span class="vs-text">VS</span><input type="text" name="equipo[]" placeholder="Nombre del Equipo 8" required></div></div><button type="submit" name="iniciar_torneo">¡Empezar!</button></form></div>

    <?php elseif ($vista === 'fixture'):
        $equipos = $torneo['equipos']; $partidos = $torneo['partidos'];
        function mostrarEquipo($id, $e, $p) { return ($id !== null && isset($e[$id])) ? htmlspecialchars($e[$id]) : '<span class="equipo-placeholder">'.$p.'</span>'; }
    ?>
        
        <div class="fixture-container">
            <div class="ronda cuartos"><h3>Cuartos</h3><?php for($i=0;$i<4;$i++):$p=$partidos[$i];?><div class="partido"><div class="equipo"><span><?=mostrarEquipo($p['eq1'],$equipos,'')?></span><span class="resultado"><?=$p['res1']??''?></span></div><hr style="margin:2px 0;"><div class="equipo"><span><?=mostrarEquipo($p['eq2'],$equipos,'')?></span><span class="resultado"><?=$p['res2']??''?></span></div><?php if(isset($p['eq1'])&&isset($p['eq2'])):?><a href="?editar=<?=$i?>"><button>Jugar / Editar</button></a><?php endif;?></div><?php endfor;?></div>
            <div class="ronda semifinal"><h3>Semifinal</h3><?php for($i=4;$i<6;$i++):$p=$partidos[$i];?><div class="partido"><div class="equipo"><span><?=mostrarEquipo($p['eq1'],$equipos,'Por definir')?></span><span class="resultado"><?=$p['res1']??''?></span></div><hr style="margin:2px 0;"><div class="equipo"><span><?=mostrarEquipo($p['eq2'],$equipos,'Por definir')?></span><span class="resultado"><?=$p['res2']??''?></span></div><?php if(isset($p['eq1'])&&isset($p['eq2'])):?><a href="?editar=<?=$i?>"><button>Jugar / Editar</button></a><?php endif;?></div><?php endfor;?></div>
            <div class="ronda final"><h3>Final</h3><?php $p=$partidos[6];?><div class="partido"><div class="equipo"><span><?=mostrarEquipo($p['eq1'],$equipos,'Por definir')?></span><span class="resultado"><?=$p['res1']??''?></span></div><hr style="margin:2px 0;"><div class="equipo"><span><?=mostrarEquipo($p['eq2'],$equipos,'Por definir')?></span><span class="resultado"><?=$p['res2']??''?></span></div><?php if(isset($p['eq1'])&&isset($p['eq2'])):?><a href="?editar=6"><button>Jugar / Editar</button></a><?php endif;?></div></div>
            <div class="ronda campeon"><h3>Campeones</h3><?php if(isset($torneo['ganador_torneo'])):?><div class="campeon"><div class="trofeo">🏆</div><div class="ganador-mensaje" style="font-size:1.2em;padding:.8em;margin:0;"><?=$equipos[$torneo['ganador_torneo']]?></div></div><?php endif;?></div>
        </div>
        <form action="<?=$_SERVER['PHP_SELF']?>" method="post" style="text-align:center;"><button class="btn-reiniciar" type="submit" name="reiniciar_torneo">Reiniciar Torneo</button></form>

    <?php elseif ($vista === 'login_partido'): ?>
        <div class="login-container">
            <p>Ingresa la clave para editar este partido.</p>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-mensaje"><?= $_SESSION['error_message'] ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            <form action="<?= $_SERVER['PHP_SELF'] ?>?editar=<?= $partido_id_actual ?>" method="post">
                <input type="hidden" name="partido_id" value="<?= $partido_id_actual ?>">
                <div class="form-group">
                    <input type="password" name="admin_password" placeholder="Clave de Administrador" required autofocus>
                </div>
                <div class="form-actions">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>"><button type="button" class="btn-cancelar">Cancelar</button></a>
                    <button type="submit" name="entrar_partido">Entrar</button>
                </div>
            </form>
        </div>

    <?php elseif ($vista === 'partido'): 
        $id = $partido_id_actual; $partido = $torneo['partidos'][$id];
        $equipo1_nombre = $torneo['equipos'][$partido['eq1']]; $equipo2_nombre = $torneo['equipos'][$partido['eq2']];
        $puntos1 = $partido['res1'] ?? 0; $puntos2 = $partido['res2'] ?? 0;
        $ganador_partido = null;
        if ($puntos1 >= 21 && $puntos1 >= $puntos2 + 2) $ganador_partido = $equipo1_nombre; 
        elseif ($puntos2 >= 21 && $puntos2 >= $puntos1 + 2) $ganador_partido = $equipo2_nombre;
    ?>
        <h2><?= $torneo['partidos'][$id]['nivel'] ?></h2>
        
        <form action="<?= $_SERVER['PHP_SELF'] ?>?editar=<?= $id ?>" method="post">
            <div class="marcador">
                <div class="jugador"><div class="nombre"><?=$equipo1_nombre?></div><div class="puntos"><?=$puntos1?></div><div class="controles-jugador"><button class="btn-restar" type="submit" name="restar_punto_j1" <?= $puntos1==0?'disabled':''?>>-1</button><button type="submit" name="sumar_punto_j1" <?=$ganador_partido?'disabled':''?>>+1</button></div></div>
                <div class="jugador"><div class="nombre"><?=$equipo2_nombre?></div><div class="puntos"><?=$puntos2?></div><div class="controles-jugador"><button class="btn-restar" type="submit" name="restar_punto_j2" <?= $puntos2==0?'disabled':''?>>-1</button><button type="submit" name="sumar_punto_j2" <?=$ganador_partido?'disabled':''?>>+1</button></div></div>
            </div>
            
            <?php if ($ganador_partido): ?>
                <div class="ganador-mensaje"><strong>Ganador del partido: <?= htmlspecialchars($ganador_partido) ?></strong></div>
            <?php endif; ?>

            <button type="submit" name="volver_al_fixture">
                <?= $ganador_partido ? 'Guardar Ganador y Volver' : 'Guardar y Volver al Fixture' ?>
            </button>
        </form>
    <?php endif; ?>
</div>

</body>

</html>
