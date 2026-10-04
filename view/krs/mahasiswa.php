<?php
require __DIR__ . '/../../controller/auth.php';
requireLogin();

$error = '';
$status = $_GET['status'] ?? '';

$currentUser = currentUser();
$username = strtolower(trim((string) ($currentUser['username'] ?? '')));
$studentId = null;

if (isset($_GET['id_mahasiswa'])) {
    $requestedId = filter_var($_GET['id_mahasiswa'], FILTER_VALIDATE_INT);
    if ($requestedId !== false && $requestedId !== null && $requestedId > 0) {
        $studentId = (int) $requestedId;
    }
}

if ($studentId === null) {
    $sessionUser = currentUser();
    $linkedStudentId = $sessionUser !== null && isset($sessionUser['id_mahasiswa']) ? filter_var($sessionUser['id_mahasiswa'], FILTER_VALIDATE_INT) : null;
    if ($linkedStudentId !== false && $linkedStudentId !== null && $linkedStudentId > 0) {
        $studentId = (int) $linkedStudentId;
    }
}

if ($studentId === null) {
    $studentCandidate = \App\Models\MahasiswaModel::all();
    foreach ($studentCandidate as $candidate) {
        $npm = strtolower(trim((string) ($candidate['npm'] ?? '')));
        $name = strtolower(trim((string) ($candidate['nama_mahasiswa'] ?? '')));
        if ($username === $npm || $username === $name || $username === str_replace(' ', '', $name) || $username === str_replace([' ', '-'], '', $name)) {
            $studentId = (int) $candidate['id_mahasiswa'];
            break;
        }
    }
}

if ($studentId === null || \App\Models\MahasiswaModel::findById($studentId) === null) {
    $error = 'Mahasiswa tidak ditemukan untuk akun saat ini. Hubungi admin untuk menghubungkan akun Anda dengan data mahasiswa.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';
    $studentIdFromPost = filter_var($_POST['id_mahasiswa'] ?? null, FILTER_VALIDATE_INT);
    if ($studentIdFromPost === false || $studentIdFromPost === null || $studentIdFromPost <= 0) {
        $error = 'Data mahasiswa tidak valid.';
    } elseif ((int) $studentIdFromPost !== $studentId) {
        $error = 'Anda hanya dapat mengelola KRS milik akun sendiri.';
    } else {
        $classId = filter_var($_POST['id_kelas_kuliah'] ?? null, FILTER_VALIDATE_INT);
        $yearId = filter_var($_POST['id_tahun_akademik'] ?? null, FILTER_VALIDATE_INT);

        if ($action === 'add') {
            if ($yearId === false || $yearId === null || $yearId <= 0 || \App\Models\TahunAkademikModel::findById((int) $yearId) === null) {
                $error = 'Tahun akademik tidak valid.';
            } elseif ($classId === false || $classId === null || $classId <= 0 || \App\Models\KelasKuliahModel::findById((int) $classId) === null) {
                $error = 'Kelas tidak valid.';
            } elseif (\App\Models\KrsModel::addClass((int) $studentIdFromPost, (int) $yearId, (int) $classId)) {
                header('Location: mahasiswa.php?id_mahasiswa=' . (int) $studentIdFromPost . '&status=added');
                exit;
            } else {
                $error = 'Kelas gagal ditambahkan. Kemungkinan sudah dipilih, kuota penuh, atau jadwal bentrok.';
            }
        } elseif ($action === 'remove') {
            $krsId = filter_var($_POST['id_krs'] ?? null, FILTER_VALIDATE_INT);
            if ($classId === false || $classId === null || $classId <= 0 || $krsId === false || $krsId === null || $krsId <= 0) {
                $error = 'Data KRS tidak valid.';
            } elseif (\App\Models\KrsModel::removeClass((int) $krsId, (int) $classId)) {
                header('Location: mahasiswa.php?id_mahasiswa=' . (int) $studentIdFromPost . '&status=removed');
                exit;
            } else {
                $error = 'Kelas tidak dapat dihapus.';
            }
        } elseif ($action === 'submit') {
            $krsId = filter_var($_POST['id_krs'] ?? null, FILTER_VALIDATE_INT);
            if ($krsId === false || $krsId === null || $krsId <= 0) {
                $error = 'Data KRS tidak valid.';
            } elseif (\App\Models\KrsModel::submitForApproval((int) $studentIdFromPost, (int) $yearId)) {
                header('Location: mahasiswa.php?id_mahasiswa=' . (int) $studentIdFromPost . '&status=submitted');
                exit;
            } else {
                $error = 'KRS tidak dapat diajukan. Pastikan Anda memiliki kelas terpilih dan status belum disetujui.';
            }
        }
    }
}

$student = $studentId !== null ? \App\Models\MahasiswaModel::findById($studentId) : null;
$activeYear = \App\Models\KrsModel::findActiveYear();
$yearId = $activeYear !== null ? (int) $activeYear['id_tahun_akademik'] : 0;
$selectedClasses = $student !== null && $yearId > 0 ? \App\Models\KrsModel::getStudentClasses($studentId, $yearId) : [];
$availableClasses = $student !== null && $yearId > 0 ? \App\Models\KrsModel::getAvailableClasses($studentId, $yearId) : [];
$krsRecord = $student !== null && $yearId > 0 ? \App\Models\KrsModel::findByStudentAndYear((int) $student['id_mahasiswa'], (int) $yearId) : null;
$krsStatus = $krsRecord['status'] ?? 'Draft';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRS Saya | RuangKampus</title>
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
            <span class="brand-caption">Mahasiswa</span>
            <?= authUserMenu() ?>
        </div>
    </header>

    <main class="page-shell master-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Akademik</a> / KRS Saya</p>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">KRS Saya</h1>
                <p class="page-description">Pilih kelas untuk semester aktif dan pastikan tidak bentrok jadwal.</p>
            </div>
            <span class="total-badge"><?= $activeYear !== null ? htmlspecialchars($activeYear['tahun_akademik'] . ' ' . $activeYear['semester'], ENT_QUOTES, 'UTF-8') : 'Belum ada' ?></span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'added') : ?>
            <p class="notice" role="status">Kelas berhasil ditambahkan ke KRS Anda.</p>
        <?php elseif ($status === 'removed') : ?>
            <p class="notice" role="status">Kelas berhasil dihapus dari KRS Anda.</p>
        <?php elseif ($status === 'submitted') : ?>
            <p class="notice" role="status">KRS Anda berhasil diajukan dan menunggu persetujuan admin.</p>
        <?php endif; ?>

        <?php if ($student !== null && $activeYear !== null) : ?>
            <section class="master-list-section" aria-labelledby="selected-title">
                <div class="master-section-heading">
                    <div>
                        <h2 id="selected-title">Mahasiswa: <?= htmlspecialchars($student['nama_mahasiswa'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>NPM: <?= htmlspecialchars($student['npm'], ENT_QUOTES, 'UTF-8') ?> | Program: <?= htmlspecialchars($student['program_studi'] ?? $student['program_studi'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="total-badge">Status: <?= htmlspecialchars($krsStatus, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="table-frame">
                    <div class="table-scroll">
                        <table class="master-table schedule-table">
                            <thead>
                                <tr><th scope="col">Kelas</th><th scope="col">Mata Kuliah</th><th scope="col">Dosen</th><th scope="col">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($selectedClasses === []) : ?>
                                    <tr><td class="empty-cell" colspan="4">Anda belum memilih kelas untuk periode ini.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($selectedClasses as $class) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['nama_mk'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if ($krsStatus === 'Draft') : ?>
                                                    <form class="master-delete-form" method="post">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="id_mahasiswa" value="<?= (int) $student['id_mahasiswa'] ?>">
                                                        <input type="hidden" name="id_krs" value="<?= (int) ($krsRecord['id_krs'] ?? 0) ?>">
                                                        <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                        <button class="program-delete-button" type="submit" title="Hapus dari KRS">Hapus</button>
                                                    </form>
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

            <?php if ($krsStatus === 'Draft' && $selectedClasses !== []) : ?>
                <section class="master-create-section" aria-labelledby="submit-title">
                    <div class="master-section-heading">
                        <h2 id="submit-title">Ajukan KRS</h2>
                    </div>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="id_mahasiswa" value="<?= (int) $student['id_mahasiswa'] ?>">
                        <input type="hidden" name="id_tahun_akademik" value="<?= (int) $yearId ?>">
                        <input type="hidden" name="id_krs" value="<?= (int) ($krsRecord['id_krs'] ?? 0) ?>">
                        <button class="save-button" type="submit">Ajukan KRS</button>
                    </form>
                </section>
            <?php elseif ($krsStatus === 'Diajukan') : ?>
                <section class="master-create-section" aria-labelledby="submit-title">
                    <div class="master-section-heading">
                        <h2 id="submit-title">Status KRS</h2>
                    </div>
                    <p class="notice" role="status">KRS Anda telah diajukan dan menunggu persetujuan admin.</p>
                </section>
            <?php elseif ($krsStatus === 'Disetujui') : ?>
                <section class="master-create-section" aria-labelledby="submit-title">
                    <div class="master-section-heading">
                        <h2 id="submit-title">Status KRS</h2>
                    </div>
                    <p class="notice" role="status">KRS Anda sudah disetujui. Perubahan kelas tidak dapat dilakukan lagi.</p>
                </section>
            <?php endif; ?>

            <section class="master-list-section" aria-labelledby="available-title">
                <div class="master-section-heading">
                    <div>
                        <h2 id="available-title">Kelas tersedia</h2>
                        <p>Pastikan kelas yang dipilih tidak bentrok dengan jadwal lain yang sudah Anda ambil.</p>
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
                                    <tr><td class="empty-cell" colspan="5">Tidak ada kelas yang tersedia untuk Anda saat ini.</td></tr>
                                <?php else : ?>
                                    <?php foreach ($availableClasses as $class) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['nama_mk'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($class['nama_dosen'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($class['kuota'] ?? 'Tanpa batas'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if ($krsStatus === 'Draft') : ?>
                                                    <form method="post">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="add">
                                                        <input type="hidden" name="id_mahasiswa" value="<?= (int) $student['id_mahasiswa'] ?>">
                                                        <input type="hidden" name="id_tahun_akademik" value="<?= (int) $yearId ?>">
                                                        <input type="hidden" name="id_kelas_kuliah" value="<?= (int) $class['id_kelas_kuliah'] ?>">
                                                        <button class="secondary-button" type="submit">Tambah</button>
                                                    </form>
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
        <?php elseif ($activeYear === null) : ?>
            <p class="form-error" role="status">Belum ada tahun akademik aktif untuk KRS.</p>
        <?php endif; ?>
    </main>
</body>
</html>
