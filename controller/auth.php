<?php
require_once __DIR__ . '/../model/autoload.php';

function startAuthSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('RUANGKAMPUS_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();
}

startAuthSession();

function currentUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function loginUser(string $username, string $password): bool
{
    $user = \App\Models\UserModel::findActiveByUsername($username);
    if ($user === null) {
        return false;
    }
    if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        \App\Models\UserModel::recordFailedLogin((int) $user['id_user']);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id_user' => (int) $user['id_user'],
        'username' => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role' => $user['role'],
    ];
    $_SESSION['last_activity'] = time();

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        \App\Models\UserModel::updatePasswordHash((int) $user['id_user'], password_hash($password, PASSWORD_DEFAULT));
    }
    \App\Models\UserModel::recordSuccessfulLogin((int) $user['id_user']);

    return true;
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function hasRole(array $roles): bool
{
    $user = currentUser();
    return $user !== null && in_array($user['role'], $roles, true);
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!hasRole($roles)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken($token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrfToken(): void
{
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function authUserMenu(): string
{
    $user = currentUser();
    if ($user === null) {
        return '';
    }

    $name = htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8');
    $role = htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8');
    $programLink = $user['role'] === 'admin'
        ? '<a class="program-nav-link" href="../program-studi/index.php" aria-label="Program Studi" title="Program Studi">' .
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
            '<path d="M3 21h18" /><path d="M5 21V7l8-4v18" /><path d="M19 21V11l-6-4" />' .
            '<path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01" />' .
            '</svg><span class="program-nav-label">Program Studi</span></a>'
        : '';
    $classLink = $user['role'] === 'admin'
        ? '<a class="class-nav-link" href="../kelas-kuliah/index.php" aria-label="Kelas Kuliah" title="Kelas Kuliah">' .
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
            '<rect x="3" y="4" width="18" height="17" rx="2" /><path d="M16 2v4M8 2v4M3 10h18M8 14h3M8 17h7" />' .
            '</svg><span class="class-nav-label">Kelas Kuliah</span></a>'
        : '';
    $accountLink = $user['role'] === 'admin'
        ? '<a class="account-nav-link" href="../akun/index.php" aria-label="Kelola akun" title="Kelola akun">' .
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
            '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />' .
            '<circle cx="10" cy="7" r="4" />' .
            '<path d="M20 8v6M17 11h6" />' .
            '</svg><span class="account-nav-label">Kelola akun</span></a>'
        : '';

    return '<div class="topbar-user">' . $programLink . $classLink . $accountLink . '<span class="topbar-user-name">' . $name .
        '<small>' . $role . '</small></span><form action="../auth/logout.php" method="post">' .
        csrfField() . '<button class="logout-button" type="submit">Keluar</button></form></div>';
}

function logoutUser(): void
{
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
    session_destroy();
}