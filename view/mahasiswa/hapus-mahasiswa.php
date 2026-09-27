<?php
require __DIR__ . '/../../controller/function.php';
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view-mahasiswa.php');
    exit;
}

requireCsrfToken();
$id = filter_input(INPUT_POST, 'id_mahasiswa', FILTER_VALIDATE_INT);
if ($id !== false && $id !== null && hapusMahasiswa($id)) {
    header('Location: view-mahasiswa.php?status=hapus');
    exit;
}

header('Location: view-mahasiswa.php?status=gagal-hapus');
exit;