<?php
require __DIR__ . '/../../controller/function.php';
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin', 'operator']);

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: view-mahasiswa.php');
    exit;
}

$id = (int) $_GET['id'];
$mahasiswa = getMahasiswaById($id);
$programStudiOptions = \App\Models\ProgramStudiModel::all();

if (!$mahasiswa) {
    http_response_code(404);
    exit('Data mahasiswa tidak ditemukan.');
}

$form = $mahasiswa;
if (empty($form['id_program_studi'])) {
    $existingProgram = \App\Models\ProgramStudiModel::findByName($form['program_studi'] ?? '');
    $form['id_program_studi'] = $existingProgram['id_program_studi'] ?? '';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $form = array_merge($form, $_POST);
    $selectedProgramId = $_POST['id_program_studi'] ?? '';
    $program = is_string($selectedProgramId) && ctype_digit($selectedProgramId)
        ? \App\Models\ProgramStudiModel::findById((int) $selectedProgramId)
        : null;
    $_POST['program_studi'] = $program['nama_prodi'] ?? '';
    $form['id_program_studi'] = $program['id_program_studi'] ?? '';
    $_POST['id_mahasiswa'] = $id;

    if (trim($_POST['npm'] ?? '') === '' || trim($_POST['nama_mahasiswa'] ?? '') === '' || trim($_POST['program_studi'] ?? '') === '' || trim($_POST['angkatan'] ?? '') === '') {
        $error = 'NPM, nama, program studi, dan angkatan wajib diisi.';
    } elseif (!ctype_digit((string) $_POST['angkatan'])) {
        $error = 'Angkatan harus berupa angka.';
    } elseif (!in_array($_POST['jenis_kelamin'] ?? '', ['L', 'P'], true)) {
        $error = 'Pilih jenis kelamin yang valid.';
    } elseif (!in_array($_POST['agama'] ?? '', ['Islam', 'Protestan', 'Kristen', 'Budha', 'Hindu', 'Konghucu', 'Aliran Lain'], true)) {
        $error = 'Pilih agama yang valid.';
    } elseif (ubahMahasiswa($_POST)) {
        header('Location: view-mahasiswa.php?status=ubah');
        exit;
    } else {
        $error = 'Data gagal disimpan. Pastikan NPM belum digunakan mahasiswa lain.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa | RuangKampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/mahasiswa.css?v=8">
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand-mark" aria-hidden="true">RK</div>
            <span class="brand-name">RuangKampus</span>
            <span class="brand-caption">Administrasi Akademik</span>
            <?= authUserMenu() ?>
        </div>
    </header>

    <main class="page-shell edit-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="view-mahasiswa.php">Direktori / Akademik</a> / Edit</p>
        <section class="page-heading edit-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Edit mahasiswa</h1>
                <p class="page-description">Perbarui informasi akademik mahasiswa.</p>
            </div>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= escapeHtml($error) ?></p>
        <?php endif; ?>

        <form class="edit-panel edit-form" method="post">
            <?= csrfField() ?>
            <div class="edit-fields">
                <label class="form-field">NPM
                    <input type="text" name="npm" maxlength="20" required value="<?= escapeHtml($form['npm'] ?? '') ?>">
                </label>
                <label class="form-field">Nama Mahasiswa
                    <input type="text" name="nama_mahasiswa" maxlength="100" required value="<?= escapeHtml($form['nama_mahasiswa'] ?? '') ?>">
                </label>
                <label class="form-field">Jenis Kelamin
                    <select name="jenis_kelamin" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="L" <?= ($form['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($form['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </label>
                <label class="form-field">Program Studi
                    <select name="id_program_studi" required>
                        <option value="">Pilih program studi</option>
                        <?php foreach ($programStudiOptions as $program) : ?>
                            <option value="<?= (int) $program['id_program_studi'] ?>" <?= (string) ($form['id_program_studi'] ?? '') === (string) $program['id_program_studi'] ? 'selected' : '' ?>><?= escapeHtml($program['nama_prodi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Angkatan
                    <input type="number" name="angkatan" required value="<?= escapeHtml($form['angkatan'] ?? '') ?>">
                </label>
                <label class="form-field">Agama
                    <select name="agama" required>
                        <option value="">Pilih agama</option>
                        <?php foreach (['Islam', 'Protestan', 'Kristen', 'Budha', 'Hindu', 'Konghucu', 'Aliran Lain'] as $agama) : ?>
                            <option value="<?= escapeHtml($agama) ?>" <?= ($form['agama'] ?? '') === $agama ? 'selected' : '' ?>><?= escapeHtml($agama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="edit-actions">
                <button class="save-button" type="submit">Simpan Perubahan</button>
                <a class="cancel-link" href="view-mahasiswa.php">Batal</a>
            </div>
        </form>
    </main>
</body>

</html>