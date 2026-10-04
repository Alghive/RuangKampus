<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../model/autoload.php';

use App\Models\UserModel;

$username = getenv('RUANGKAMPUS_NEW_USERNAME') ?: '';
$fullName = getenv('RUANGKAMPUS_NEW_NAME') ?: '';
$password = getenv('RUANGKAMPUS_NEW_PASSWORD') ?: '';
$role = getenv('RUANGKAMPUS_NEW_ROLE') ?: '';

if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
    fwrite(STDERR, "Username harus 3-50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.\n");
    exit(1);
}
if (trim($fullName) === '' || strlen($fullName) > 100) {
    fwrite(STDERR, "Nama lengkap wajib diisi dan maksimal 100 karakter.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Password minimal 12 karakter.\n");
    exit(1);
}
if (!in_array($role, ['admin', 'operator', 'viewer', 'student'], true)) {
    fwrite(STDERR, "Role harus admin, operator, viewer, atau student.\n");
    exit(1);
}

if (!UserModel::create($username, $fullName, password_hash($password, PASSWORD_DEFAULT), $role)) {
    fwrite(STDERR, "Akun gagal dibuat. Pastikan username belum digunakan.\n");
    exit(1);
}

echo "Akun {$username} berhasil dibuat dengan role {$role}.\n";