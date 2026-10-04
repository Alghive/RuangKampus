<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$courses = \App\Models\MataKuliahModel::all();
$lecturers = \App\Models\DosenModel::all();
$academicYears = \App\Models\TahunAkademikModel::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $courseId = filter_var($_POST['id_mata_kuliah'] ?? null, FILTER_VALIDATE_INT);
        $yearId = filter_var($_POST['id_tahun_akademik'] ?? null, FILTER_VALIDATE_INT);
        $lecturerId = filter_var($_POST['id_dosen'] ?? null, FILTER_VALIDATE_INT);
        $sectionCode = $_POST['kode_kelas'] ?? '';
        $quotaInput = $_POST['kuota'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_kelas_kuliah'] ?? null, FILTER_VALIDATE_INT) : null;

        if ($courseId === false || $courseId === null || $courseId <= 0 || \App\Models\MataKuliahModel::findById((int) $courseId) === null) {
            $error = 'Pilih mata kuliah yang valid.';
        } elseif ($yearId === false || $yearId === null || $yearId <= 0 || \App\Models\TahunAkademikModel::findById((int) $yearId) === null) {
            $error = 'Pilih tahun akademik yang valid.';
        } elseif ($lecturerId === false || $lecturerId === null || $lecturerId <= 0 || \App\Models\DosenModel::findById((int) $lecturerId) === null) {
            $error = 'Pilih dosen pengampu yang valid.';
        } elseif (!is_string($sectionCode) || !preg_match('/^[A-Za-z0-9_-]{1,10}$/', $sectionCode)) {
            $error = 'Kode kelas wajib diisi, maksimal 10 karakter, dan hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah.';
        } elseif (!is_string($quotaInput) || ($quotaInput !== '' && (!ctype_digit($quotaInput) || (int) $quotaInput < 1 || (int) $quotaInput > 65535))) {
            $error = 'Kuota harus berupa angka antara 1 sampai 65535, atau dikosongkan.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data kelas kuliah tidak valid.';
        } else {
            $quota = $quotaInput === '' ? null : (int) $quotaInput;
            $sectionCode = strtoupper($sectionCode);
            $saved = $action === 'create'
                ? \App\Models\KelasKuliahModel::create((int) $courseId, (int) $yearId, (int) $lecturerId, $sectionCode, $quota)
                : \App\Models\KelasKuliahModel::update((int) $id, (int) $courseId, (int) $yearId, (int) $lecturerId, $sectionCode, $quota);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Kelas gagal disimpan. Kombinasi mata kuliah, periode, dan kode kelas mungkin sudah digunakan.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_kelas_kuliah'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data kelas kuliah tidak valid.';
        } elseif (\App\Models\KelasKuliahModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Kelas tidak dapat dihapus selama memiliki jadwal atau sudah tercantum di KRS.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$classes = \App\Models\KelasKuliahModel::all();
$status = $_GET['status'] ?? '';
$masterDataReady = $courses !== [] && $lecturers !== [] && $academicYears !== [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelas Kuliah | RuangKampus</title>
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
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / Perkuliahan</p>
        <nav class="master-tabs" aria-label="Data master dan perkuliahan">
            <a href="../program-studi/index.php">Program Studi</a>
            <a href="../dosen/index.php">Dosen</a>
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a class="is-current" href="index.php" aria-current="page">Kelas Kuliah</a>
            <a href="../jadwal-kuliah/index.php">Jadwal Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Kelas Kuliah</h1>
                <p class="page-description">Buka mata kuliah untuk dosen dan periode tertentu.</p>
            </div>
            <span class="total-badge"><?= count($classes) ?> kelas</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Kelas kuliah berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Kelas kuliah berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Kelas kuliah berhasil dihapus.</p>
        <?php endif; ?>

        <?php if (!$masterDataReady) : ?>
            <p class="form-error" role="status">
                Lengkapi data mata kuliah, dosen, dan tahun akademik sebelum membuka kelas.
                <?php if ($academicYears === []) : ?><a href="../tahun-akademik/index.php">Tambah tahun akademik</a><?php endif; ?>
                <?php if ($lecturers === []) : ?><a href="../dosen/index.php">Tambah dosen</a><?php endif; ?>
                <?php if ($courses === []) : ?><a href="../mata-kuliah/index.php">Tambah mata kuliah</a><?php endif; ?>
            </p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Buka kelas</h2>
                <p>Kode kelas harus unik untuk mata kuliah dan tahun akademik yang sama.</p>
            </div>
            <form class="master-create-form class-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Mata kuliah
                    <select class="master-input" name="id_mata_kuliah" required <?= !$masterDataReady ? 'disabled' : '' ?>>
                        <option value="">Pilih mata kuliah</option>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?= (int) $course['id_mata_kuliah'] ?>"><?= htmlspecialchars($course['kode_mk'] . ' - ' . $course['nama_mk'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Tahun akademik
                    <select class="master-input" name="id_tahun_akademik" required <?= !$masterDataReady ? 'disabled' : '' ?>>
                        <option value="">Pilih periode</option>
                        <?php foreach ($academicYears as $year) : ?>
                            <option value="<?= (int) $year['id_tahun_akademik'] ?>"><?= htmlspecialchars($year['tahun_akademik'] . ' - ' . $year['semester'] . ($year['status'] === 'Aktif' ? ' (Aktif)' : ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Dosen pengampu
                    <select class="master-input" name="id_dosen" required <?= !$masterDataReady ? 'disabled' : '' ?>>
                        <option value="">Pilih dosen</option>
                        <?php foreach ($lecturers as $lecturer) : ?>
                            <option value="<?= (int) $lecturer['id_dosen'] ?>"><?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Kode kelas
                    <input class="master-input" type="text" name="kode_kelas" maxlength="10" pattern="[A-Za-z0-9_-]{1,10}" placeholder="A" required <?= !$masterDataReady ? 'disabled' : '' ?>>
                </label>
                <label class="form-field">Kuota
                    <input class="master-input" type="number" name="kuota" min="1" max="65535" placeholder="Opsional" <?= !$masterDataReady ? 'disabled' : '' ?>>
                </label>
                <button class="save-button" type="submit" <?= !$masterDataReady ? 'disabled' : '' ?>>Buka kelas</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar kelas</h2>
                    <p>Kelas yang sudah punya jadwal atau tercantum pada KRS tidak dapat dihapus.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table class-table">
                        <thead>
                            <tr><th scope="col">Mata kuliah</th><th scope="col">Periode</th><th scope="col">Dosen</th><th scope="col">Kode kelas</th><th scope="col">Kuota</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($classes === []) : ?>
                                <tr><td class="empty-cell" colspan="6">Belum ada kelas kuliah.</td></tr>
                            <?php else : ?>
                                <?php foreach ($classes as $class) : ?>
                                    <tr>
                                        <td data-label="Mata kuliah">
                                            <form class="master-update-form" id="class-<?= (int) $class['id_kelas_kuliah'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                <select class="master-input" name="id_mata_kuliah" required>
                                                    <?php foreach ($courses as $course) : ?>
                                                        <option value="<?= (int) $course['id_mata_kuliah'] ?>" <?= (int) $class['id_mata_kuliah'] === (int) $course['id_mata_kuliah'] ? 'selected' : '' ?>><?= htmlspecialchars($course['kode_mk'] . ' - ' . $course['nama_mk'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td data-label="Periode">
                                            <select class="master-input" name="id_tahun_akademik" form="class-<?= (int) $class['id_kelas_kuliah'] ?>" required>
                                                <?php foreach ($academicYears as $year) : ?>
                                                    <option value="<?= (int) $year['id_tahun_akademik'] ?>" <?= (int) $class['id_tahun_akademik'] === (int) $year['id_tahun_akademik'] ? 'selected' : '' ?>><?= htmlspecialchars($year['tahun_akademik'] . ' ' . $year['semester'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Dosen">
                                            <select class="master-input" name="id_dosen" form="class-<?= (int) $class['id_kelas_kuliah'] ?>" required>
                                                <?php foreach ($lecturers as $lecturer) : ?>
                                                    <option value="<?= (int) $lecturer['id_dosen'] ?>" <?= (int) $class['id_dosen'] === (int) $lecturer['id_dosen'] ? 'selected' : '' ?>><?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Kode kelas"><input class="master-input" type="text" name="kode_kelas" maxlength="10" pattern="[A-Za-z0-9_-]{1,10}" form="class-<?= (int) $class['id_kelas_kuliah'] ?>" value="<?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                                        <td data-label="Kuota"><input class="master-input class-quota-input" type="number" name="kuota" min="1" max="65535" form="class-<?= (int) $class['id_kelas_kuliah'] ?>" value="<?= htmlspecialchars($class['kuota'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="class-<?= (int) $class['id_kelas_kuliah'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($class['kode_mk'] . ' kelas ' . $class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus kelas <?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus kelas">
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
                if (!window.confirm(`Hapus ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>