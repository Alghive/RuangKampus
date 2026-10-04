<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$status = $_GET['status'] ?? '';

$students = \App\Models\MahasiswaModel::all();
$activeYear = \App\Models\KrsModel::findActiveYear();
$selectedStudentId = filter_var($_GET['id_mahasiswa'] ?? 0, FILTER_VALIDATE_INT);
$selectedStudentId = $selectedStudentId !== false && $selectedStudentId !== null ? (int) $selectedStudentId : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';
    $studentId = filter_var($_POST['id_mahasiswa'] ?? null, FILTER_VALIDATE_INT);
    $classId = filter_var($_POST['id_kelas_kuliah'] ?? null, FILTER_VALIDATE_INT);
    $yearId = filter_var($_POST['id_tahun_akademik'] ?? null, FILTER_VALIDATE_INT);
    $krsId = filter_var($_POST['id_krs'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'add') {
        if ($studentId === false || $studentId === null || $studentId <= 0 || \App\Models\MahasiswaModel::findById((int) $studentId) === null) {
            $error = 'Pilih mahasiswa yang valid.';
        } elseif ($yearId === false || $yearId === null || $yearId <= 0 || \App\Models\TahunAkademikModel::findById((int) $yearId) === null) {
            $error = 'Pilih tahun akademik yang valid.';
        } elseif ($classId === false || $classId === null || $classId <= 0 || \App\Models\KelasKuliahModel::findById((int) $classId) === null) {
            $error = 'Pilih kelas yang valid.';
        } elseif (\App\Models\KrsModel::addClass((int) $studentId, (int) $yearId, (int) $classId)) {
            header('Location: index.php?id_mahasiswa=' . (int) $studentId . '&status=added');
            exit;
        } else {
            $error = 'Kelas gagal ditambahkan. Bisa jadi sudah dipilih, kuota penuh, atau jadwal bentrok.';
        }
    } elseif ($action === 'remove') {
        if ($krsId === false || $krsId === null || $krsId <= 0 || $classId === false || $classId === null || $classId <= 0) {
            $error = 'Data KRS tidak valid.';
        } elseif (\App\Models\KrsModel::removeClass((int) $krsId, (int) $classId)) {
            header('Location: index.php?id_mahasiswa=' . (int) $studentId . '&status=removed');
            exit;
        } else {
            $error = 'Kelas tidak dapat dihapus.';
        }
    } elseif ($action === 'approve') {
        if ($krsId === false || $krsId === null || $krsId <= 0) {
            $error = 'Data KRS tidak valid.';
        } elseif (\App\Models\KrsModel::updateStatus((int) $krsId, 'Disetujui')) {
            header('Location: index.php?status=approved');
            exit;
        } else {
            $error = 'KRS tidak dapat disetujui.';
        }
    } elseif ($action === 'reject') {
        if ($krsId === false || $krsId === null || $krsId <= 0) {
            $error = 'Data KRS tidak valid.';
        } elseif (\App\Models\KrsModel::updateStatus((int) $krsId, 'Draft')) {
            header('Location: index.php?status=rejected');
            exit;
        } else {
            $error = 'KRS tidak dapat dikembalikan ke Draft.';
        }
    }
}

$selectedStudent = $selectedStudentId > 0 ? \App\Models\MahasiswaModel::findById($selectedStudentId) : null;
$yearId = $activeYear !== null ? (int) $activeYear['id_tahun_akademik'] : 0;
$selectedClasses = $selectedStudent !== null && $yearId > 0 ? \App\Models\KrsModel::getStudentClasses($selectedStudentId, $yearId) : [];
$availableClasses = $selectedStudent !== null && $yearId > 0 ? \App\Models\KrsModel::getAvailableClasses($selectedStudentId, $yearId) : [];
$pendingKrs = \App\Models\KrsModel::allForReview();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRS | RuangKampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/mahasiswa.css?v=13">
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
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / KRS</p>
        <nav class="master-tabs" aria-label="Perkuliahan">
            <a href="../program-studi/index.php">Program Studi</a>
            <a href="../dosen/index.php">Dosen</a>
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
            <a href="../jadwal-kuliah/index.php">Jadwal Kuliah</a>
            <a class="is-current" href="index.php" aria-current="page">KRS</a>
        </nav>

        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">KRS Mahasiswa</h1>
                <p class="page-description">Kelola pengambilan kelas untuk periode aktif.</p>
            </div>
            <span class="total-badge"><?= $activeYear !== null ? htmlspecialchars($activeYear['tahun_akademik'] . ' ' . $activeYear['semester'], ENT_QUOTES, 'UTF-8') : 'Belum ada' ?></span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'added') : ?>
            <p class="notice" role="status">Kelas berhasil ditambahkan ke KRS.</p>
        <?php elseif ($status === 'removed') : ?>
            <p class="notice" role="status">Kelas berhasil dihapus dari KRS.</p>
        <?php elseif ($status === 'approved') : ?>
            <p class="notice" role="status">KRS berhasil disetujui.</p>
        <?php elseif ($status === 'rejected') : ?>
            <p class="notice" role="status">KRS dikembalikan ke Draft.</p>
        <?php endif; ?>

        <?php if ($activeYear === null) : ?>
            <p class="form-error" role="status">Belum ada tahun akademik aktif. Tambahkan periode aktif terlebih dahulu agar KRS bisa dibuat.</p>
        <?php endif; ?>

        <section class="master-create-section">
            <div class="master-section-heading">
                <h2>Pilih mahasiswa</h2>
            </div>
            <form class="master-create-form" method="get" style="grid-template-columns: minmax(200px, 1.2fr) auto;">
                <label class="form-field">Mahasiswa
                    <select class="master-input" name="id_mahasiswa" required>
                        <option value="">Pilih mahasiswa</option>
                        <?php foreach ($students as $student) : ?>
                            <option value="<?= (int) $student['id_mahasiswa'] ?>" <?= (int) $selectedStudentId === (int) $student['id_mahasiswa'] ? 'selected' : '' ?>><?= htmlspecialchars($student['nama_mahasiswa'] . ' (' . $student['npm'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="save-button" type="submit">Tampilkan KRS</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="review-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="review-title">Persetujuan KRS</h2>
                    <p>Daftar KRS yang sudah diajukan mahasiswa akan muncul di sini.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table schedule-table">
                        <thead>
                            <tr><th scope="col">Mahasiswa</th><th scope="col">Periode</th><th scope="col">Status</th><th scope="col">Jumlah kelas</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($pendingKrs === []) : ?>
                                <tr><td class="empty-cell" colspan="5">Belum ada KRS yang diajukan atau diproses.</td></tr>
                            <?php else : ?>
                                <?php foreach ($pendingKrs as $krsRow) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($krsRow['nama_mahasiswa'] . ' (' . $krsRow['npm'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($krsRow['tahun_akademik'] . ' ' . $krsRow['semester'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($krsRow['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $krsRow['jumlah_kelas'] ?></td>
                                        <td>
                                            <?php if ($krsRow['status'] === 'Diajukan') : ?>
                                                <div class="master-actions">
                                                    <form method="post" class="master-update-form">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="id_krs" value="<?= (int) $krsRow['id_krs'] ?>">
                                                        <button class="secondary-button" type="submit">Setujui</button>
                                                    </form>
                                                    <form method="post" class="master-delete-form">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="id_krs" value="<?= (int) $krsRow['id_krs'] ?>">
                                                        <button class="program-delete-button" type="submit" title="Kembalikan ke Draft">Tolak</button>
                                                    </form>
                                                </div>
                                            <?php else : ?>
                                                <span class="muted-text">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <?php if ($selectedStudent !== null && $activeYear !== null) : ?>
            <section class="master-list-section" aria-labelledby="selected-title">
                <div class="master-section-heading">
                    <div>
                        <h2 id="selected-title">KRS <?= htmlspecialchars($selectedStudent['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>Periode aktif: <?= htmlspecialchars($activeYear['tahun_akademik'] . ' - ' . $activeYear['semester'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <div class="table-frame">
                    <div class="table-scroll">
                        <table class="master-table schedule-table">
                            <thead>
                                <tr><th scope="col">Kelas</th><th scope="col">Mata Kuliah</th><th scope="col">Dosen</th><th scope="col">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($selectedClasses === []) : ?>
                                    <tr><td class="empty-cell" colspan="4">Mahasiswa belum memilih kelas untuk periode ini.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($selectedClasses as $class) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['nama_mk'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <form method="post" class="master-delete-form">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="id_mahasiswa" value="<?= (int) $selectedStudentId ?>">
                                                    <input type="hidden" name="id_krs" value="<?= (int) \App\Models\KrsModel::findByStudentAndYear((int) $selectedStudentId, (int) $yearId)['id_krs'] ?? 0 ?>">
                                                    <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                    <button class="program-delete-button" type="submit" title="Hapus dari KRS">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="master-list-section" aria-labelledby="available-title">
                <div class="master-section-heading">
                    <div>
                        <h2 id="available-title">Kelas tersedia</h2>
                        <p>Setiap kelas dipastikan tidak bentrok dengan jadwal yang sudah dipilih.</p>
                    </div>
                </div>

                <div class="table-frame">
                    <div class="table-scroll">
                        <table class="master-table schedule-table">
                            <thead>
                                <tr><th scope="col">Kelas</th><th scope="col">Mata Kuliah</th><th scope="col">Dosen</th><th scope="col">Kuota</th><th scope="col">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($availableClasses === []) : ?>
                                    <tr><td class="empty-cell" colspan="5">Tidak ada kelas yang tersedia untuk mahasiswa ini.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($availableClasses as $class) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['nama_mk'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($class['kuota'] ?? 'Tanpa batas'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <form method="post">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="add">
                                                    <input type="hidden" name="id_mahasiswa" value="<?= (int) $selectedStudentId ?>">
                                                    <input type="hidden" name="id_tahun_akademik" value="<?= (int) $yearId ?>">
                                                    <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                    <button class="secondary-button" type="submit">Tambah</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>
