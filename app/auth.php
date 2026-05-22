<?php
require_once __DIR__ . '/db.php';

// --- Autenticación existente ---
function findUserByEmail(string $email) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function registerUser(string $nombre, string $email, string $password, string $rol = 'user') {

    // Si NO es admin, exigir dominio institucional
    if ($rol !== 'admin') {
        if (!preg_match('/^[A-Za-z0-9._%+\-]+@docentes\.educacion\.edu\.ec$/', $email)) {
            return false;
        }
    }

    $pdo = getPDO();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nombre, email, password, rol)
         VALUES (?, ?, ?, ?)"
    );
    return $stmt->execute([$nombre, $email, $hash, $rol]);
}


function attemptLogin(string $email, string $password): bool {
    $user = findUserByEmail($email);
    if ($user && password_verify($password, $user['password'])) {
        // Establecer sesión (evitar fijación de sesión regenerando id)
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ];
        return true;
    }
    return false;
}

function logout() {
    // Limpiar sesión de forma segura
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// --- Helpers de sesión / usuario ---
function isAuthenticated(): bool {
    return !empty($_SESSION['user']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

// --- Flash messages (notificaciones en sesión) ---
function set_flash(string $message) {
    $_SESSION['flash'] = $message;
}

function get_flash(): ?string {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}

// --- CSRF token helpers ---
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $max_age = 60 * 60 * 2;
    if (!empty($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > $max_age) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input_html(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// --- Autorización helpers ---
function is_admin(): bool {
    $u = currentUser();
    return !empty($u['rol']) && strtolower($u['rol']) === 'admin';
}

function require_login(string $redirect = 'login.php') {
    if (!isAuthenticated()) {
        set_flash('Debes iniciar sesión para acceder a esa página.');
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(string $redirect = 'access_denied.php') {
    require_login();
    if (!is_admin()) {
        // Usuario autenticado pero sin permisos -> mostrar página de acceso denegado
        set_flash('No tienes permisos para realizar esta acción.');
        header('Location: ' . $redirect);
        exit;
    }
}