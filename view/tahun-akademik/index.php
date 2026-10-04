<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$semesters = ['Ganjil', 'Genap', 'Pendek'];
$statuses = ['Aktif', 'Nonaktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $year = $_POST['tahun_akademik'] ?? '';
        $semester = $_POST['semester'] ?? '';
        $status = $_POST['status'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_tahun_akademik'] ?? null, FILTER_VALIDATE_INT) : null;

        $yearParts = is_string($year) ? [] : null;
        $validYear = is_string($year) && preg_match('/^(\d{4})\/(\d{4})$/', $year, $yearParts) === 1 && (int) $yearParts[2] === (int) $yearParts[1] + 1;
        if (!$validYear) {
            $error = 'Tahun akademik harus berurutan dengan format YYYY/YYYY, misalnya 2026/2027.';
        } elseif (!is_string($semester) || !in_array($semester, $semesters, true)) {
            $error = 'Pilih semester yang valid.';
        } elseif (!is_string($status) || !in_array($status, $statuses, true)) {
            $error = 'Pilih status yang valid.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data tahun akademik tidak valid.';
        } else {
            $saved = $action === 'create'
                ? \App\Models\TahunAkademikModel::create($year, $semester, $status)
                : \App\Models\TahunAkademikModel::update((int) $id, $year, $semester, $status);
            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Tahun akademik gagal disimpan. Kombinasi tahun dan semester mungkin sudah ada.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_tahun_akademik'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data tahun akademik tidak valid.';
        } elseif (\App\Models\TahunAkademikModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Periode aktif atau periode yang sudah memiliki kelas tidak dapat dihapus.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$years = \App\Models\TahunAkademikModel::all();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahun Akademik | RuangKampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/mahasiswa.css?v=12">
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
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a class="is-current" href="index.php" aria-current="page">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
            <a href="../jadwal-kuliah/index.php">Jadwal Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Tahun Akademik</h1>
                <p class="page-description">Atur periode dan semester yang digunakan untuk membuka kelas.</p>
            </div>
            <span class="total-badge"><?= count($years) ?> periode</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Tahun akademik berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Tahun akademik berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Tahun akademik berhasil dihapus.</p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Tambah periode</h2>
                <p>Gunakan format tahun berurutan, misalnya 2026/2027. Maksimal satu periode berstatus aktif.</p>
            </div>
            <form class="master-create-form year-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Tahun akademik
                    <input class="master-input" type="text" name="tahun_akademik" pattern="[0-9]{4}/[0-9]{4}" maxlength="9" placeholder="2026/2027" required>
                </label>
                <label class="form-field">Semester
                    <select class="master-input" name="semester" required>
                        <?php foreach ($semesters as $semester) : ?>
                            <option value="<?= $semester ?>"><?= $semester ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Status
                    <select class="master-input" name="status" required>
                        <?php foreach ($statuses as $yearStatus) : ?>
                            <option value="<?= $yearStatus ?>" <?= $yearStatus === 'Nonaktif' ? 'selected' : '' ?>><?= $yearStatus ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="save-button" type="submit">Tambah periode</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar periode</h2>
                    <p>Mengaktifkan periode akan menonaktifkan periode aktif sebelumnya.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table year-table">
                        <thead>
                            <tr><th scope="col">Tahun</th><th scope="col">Semester</th><th scope="col">Status</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($years === []) : ?>
                                <tr><td class="empty-cell" colspan="4">Belum ada tahun akademik.</td></tr>
                            <?php else : ?>
                                <?php foreach ($years as $year) : ?>
                                    <tr>
                                        <td data-label="Tahun">
                                            <form class="master-update-form" id="year-<?= (int) $year['id_tahun_akademik'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_tahun_akademik" value="<?= (int) $year['id_tahun_akademik'] ?>">
                                                <input class="master-input" type="text" name="tahun_akademik" pattern="[0-9]{4}/[0-9]{4}" maxlength="9" value="<?= htmlspecialchars($year['tahun_akademik'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            </form>
                                        </td>
                                        <td data-label="Semester">
                                            <select class="master-input" name="semester" form="year-<?= (int) $year['id_tahun_akademik'] ?>">
                                                <?php foreach ($semesters as $semester) : ?>
                                                    <option value="<?= $semester ?>" <?= $year['semester'] === $semester ? 'selected' : '' ?>><?= $semester ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Status">
                                            <select class="master-input" name="status" form="year-<?= (int) $year['id_tahun_akademik'] ?>">
                                                <?php foreach ($statuses as $yearStatus) : ?>
                                                    <option value="<?= $yearStatus ?>" <?= $year['status'] === $yearStatus ? 'selected' : '' ?>><?= $yearStatus ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="year-<?= (int) $year['id_tahun_akademik'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($year['tahun_akademik'] . ' ' . $year['semester'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_tahun_akademik" value="<?= (int) $year['id_tahun_akademik'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus periode <?= htmlspecialchars($year['tahun_akademik'] . ' ' . $year['semester'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus periode">
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
                if (!window.confirm(`Hapus periode ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>