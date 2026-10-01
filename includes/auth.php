<?php
// ============================================================
// TOKONESIA — Auth Functions
// ============================================================

require_once __DIR__ . '/config.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(BASE_URL . '/index.php');
    }
}

function loginUser(string $email, string $password): bool {
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function registerUser(string $name, string $email, string $password): bool|string {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    if ($stmt->fetch()) {
        return 'Email sudah terdaftar.';
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "customer")');
    $ins->execute([trim($name), trim($email), $hash]);
    return true;
}

function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
    redirect(BASE_URL . '/index.php');
}

function require_once_auth(): void {
    require_once __DIR__ . '/auth.php';
}
