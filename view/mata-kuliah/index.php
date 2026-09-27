<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$programStudi = \App\Models\ProgramStudiModel::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $code = $_POST['kode_mk'] ?? '';
        $name = $_POST['nama_mk'] ?? '';
        $creditsInput = $_POST['sks'] ?? '';
        $programInput = $_POST['id_program_studi'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_mata_kuliah'] ?? null, FILTER_VALIDATE_INT) : null;

        if (!is_string($code) || trim($code) === '' || strlen(trim($code)) > 20) {
            $error = 'Kode mata kuliah wajib diisi dan maksimal 20 karakter.';
        } elseif (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 120) {
            $error = 'Nama mata kuliah wajib diisi dan maksimal 120 karakter.';
        } elseif (!is_string($creditsInput) || !ctype_digit($creditsInput) || (int) $creditsInput < 1 || (int) $creditsInput > 30) {
            $error = 'SKS harus berupa angka antara 1 sampai 30.';
        } elseif (!is_string($programInput)) {
            $error = 'Pilihan program studi tidak valid.';
        } elseif ($programInput !== '' && (!ctype_digit($programInput) || \App\Models\ProgramStudiModel::findById((int) $programInput) === null)) {
            $error = 'Program studi yang dipilih tidak ditemukan.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data mata kuliah tidak valid.';
        } else {
            $code = strtoupper(trim($code));
            $name = trim($name);
            $credits = (int) $creditsInput;
            $programId = $programInput === '' ? null : (int) $programInput;
            $saved = $action === 'create'
                ? \App\Models\MataKuliahModel::create($code, $name, $credits, $programId)
                : \App\Models\MataKuliahModel::update((int) $id, $code, $name, $credits, $programId);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Mata kuliah gagal disimpan. Kode mungkin sudah digunakan.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_mata_kuliah'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data mata kuliah tidak valid.';
        } elseif (\App\Models\MataKuliahModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Mata kuliah tidak dapat dihapus selama masih dipakai kelas kuliah.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$mataKuliah = \App\Models\MataKuliahModel::all();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mata Kuliah | RuangKampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/mahasiswa.css?v=9">
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

    <main class="page-shell master-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / Data Master</p>
        <nav class="master-tabs" aria-label="Data master">
            <a href="../program-studi/index.php">Program Studi</a>
            <a href="../dosen/index.php">Dosen</a>
            <a class="is-current" href="index.php" aria-current="page">Mata Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Mata Kuliah</h1>
                <p class="page-description">Kelola katalog mata kuliah dan bobot SKS.</p>
            </div>
            <span class="total-badge"><?= count($mataKuliah) ?> mata kuliah</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Mata kuliah berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Mata kuliah berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Mata kuliah berhasil dihapus.</p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Tambah mata kuliah</h2>
                <p>SKS antara 1 sampai 30. Program studi boleh dikosongkan untuk mata kuliah lintas prodi.</p>
            </div>
            <form class="master-create-form course-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Kode mata kuliah
                    <input class="master-input" type="text" name="kode_mk" maxlength="20" required>
                </label>
                <label class="form-field">Nama mata kuliah
                    <input class="master-input" type="text" name="nama_mk" maxlength="120" required>
                </label>
                <label class="form-field">SKS
                    <input class="master-input" type="number" name="sks" min="1" max="30" required>
                </label>
                <label class="form-field">Program studi
                    <select class="master-input" name="id_program_studi">
                        <option value="">Lintas prodi / belum ditentukan</option>
                        <?php foreach ($programStudi as $program) : ?>
                            <option value="<?= (int) $program['id_program_studi'] ?>"><?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="save-button" type="submit">Tambah mata kuliah</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar mata kuliah</h2>
                    <p>Mata kuliah yang sudah dipakai kelas tidak dapat dihapus.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table course-table">
                        <thead>
                            <tr><th scope="col">Kode</th><th scope="col">Nama mata kuliah</th><th scope="col">SKS</th><th scope="col">Program studi</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($mataKuliah === []) : ?>
                                <tr><td class="empty-cell" colspan="5">Belum ada mata kuliah.</td></tr>
                            <?php else : ?>
                                <?php foreach ($mataKuliah as $course) : ?>
                                    <tr>
                                        <td data-label="Kode">
                                            <form class="master-update-form" id="course-<?= (int) $course['id_mata_kuliah'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_mata_kuliah" value="<?= (int) $course['id_mata_kuliah'] ?>">
                                                <input class="master-input" type="text" name="kode_mk" maxlength="20" value="<?= htmlspecialchars($course['kode_mk'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Kode mata kuliah <?= htmlspecialchars($course['nama_mk'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            </form>
                                        </td>
                                        <td data-label="Nama mata kuliah"><input class="master-input" type="text" name="nama_mk" maxlength="120" form="course-<?= (int) $course['id_mata_kuliah'] ?>" value="<?= htmlspecialchars($course['nama_mk'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                                        <td data-label="SKS"><input class="master-input course-sks-input" type="number" name="sks" min="1" max="30" form="course-<?= (int) $course['id_mata_kuliah'] ?>" value="<?= (int) $course['sks'] ?>" required></td>
                                        <td data-label="Program studi">
                                            <select class="master-input" name="id_program_studi" form="course-<?= (int) $course['id_mata_kuliah'] ?>">
                                                <option value="">Lintas prodi / belum ditentukan</option>
                                                <?php foreach ($programStudi as $program) : ?>
                                                    <option value="<?= (int) $program['id_program_studi'] ?>" <?= (string) ($course['id_program_studi'] ?? '') === (string) $program['id_program_studi'] ? 'selected' : '' ?>><?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="course-<?= (int) $course['id_mata_kuliah'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($course['nama_mk'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_mata_kuliah" value="<?= (int) $course['id_mata_kuliah'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus mata kuliah <?= htmlspecialchars($course['nama_mk'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus mata kuliah">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('.master-delete-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(`Hapus mata kuliah ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>