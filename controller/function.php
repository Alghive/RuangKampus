<?php
require_once __DIR__ . '/../model/autoload.php';

function query ($query) {
    $result = \App\Models\Database::connection()->query($query);
    return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getMahasiswaById($id) {
    return \App\Models\MahasiswaModel::findById((int) $id);
}

function ubahMahasiswa($data) {
    return \App\Models\MahasiswaModel::update($data);
}

function hapusMahasiswa($id) {
    return \App\Models\MahasiswaModel::delete((int) $id);
}

function tambahMahasiswaBanyak($rows) {
    return \App\Models\MahasiswaModel::insertMany($rows);
}

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>