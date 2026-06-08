<?php
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function require_login() {
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }
}

function require_admin() {
    require_login();

    if (($_SESSION['rol'] ?? '') !== 'Administrador') {
        header("Location: dashboard.php");
        exit();
    }
}

function has_role($role) {
    return ($_SESSION['rol'] ?? '') === $role;
}

function has_any_role($roles) {
    return in_array($_SESSION['rol'] ?? '', $roles, true);
}

function require_any_role($roles) {
    require_login();

    if (!has_any_role($roles)) {
        header("Location: dashboard.php");
        exit();
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}
?>
