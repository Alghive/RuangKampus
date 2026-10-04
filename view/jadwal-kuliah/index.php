<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$classes = \App\Models\KelasKuliahModel::all();
$rooms = \App\Models\RuangModel::all();
$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $classId = filter_var($_POST['id_kelas_kuliah'] ?? null, FILTER_VALIDATE_INT);
        $roomId = filter_var($_POST['id_ruang'] ?? null, FILTER_VALIDATE_INT);
        $day = $_POST['hari'] ?? '';
        $start = $_POST['jam_mulai'] ?? '';
        $end = $_POST['jam_selesai'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_jadwal_kuliah'] ?? null, FILTER_VALIDATE_INT) : null;

        if ($classId === false || $classId === null || $classId <= 0 || \App\Models\KelasKuliahModel::findById((int) $classId) === null) {
            $error = 'Pilih kelas yang valid.';
        } elseif ($roomId === false || $roomId === null || $roomId <= 0 || \App\Models\RuangModel::findById((int) $roomId) === null) {
            $error = 'Pilih ruang yang valid.';
        } elseif (!is_string($day) || !in_array($day, $days, true)) {
            $error = 'Pilih hari yang valid.';
        } elseif (!is_string($start) || !preg_match('/^\d{2}:\d{2}$/', $start) || !is_string($end) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $error = 'Waktu mulai dan selesai harus format HH:MM.';
        } elseif ($start >= $end) {
            $error = 'Jam mulai harus lebih awal dari jam selesai.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data jadwal tidak valid.';
        } else {
            $saved = $action === 'create'
                ? \App\Models\JadwalKuliahModel::create((int) $classId, (int) $roomId, $day, $start, $end)
                : \App\Models\JadwalKuliahModel::update((int) $id, (int) $classId, (int) $roomId, $day, $start, $end);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Jadwal gagal disimpan. Terdapat bentrok dengan dosen atau ruang pada waktu yang sama.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_jadwal_kuliah'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data jadwal tidak valid.';
        } elseif (\App\Models\JadwalKuliahModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Jadwal tidak dapat dihapus.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$schedules = \App\Models\JadwalKuliahModel::all();
$status = $_GET['status'] ?? '';
$masterReady = $classes !== [] && $rooms !== [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kuliah | RuangKampus</title>
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
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / Perkuliahan</p>
        <nav class="master-tabs" aria-label="Data master dan perkuliahan">
            <a href="../program-studi/index.php">Program Studi</a>
            <a href="../dosen/index.php">Dosen</a>
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
            <a class="is-current" href="index.php" aria-current="page">Jadwal Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Jadwal Kuliah</h1>
                <p class="page-description">Atur hari, jam, dan ruang per kelas.</p>
            </div>
            <span class="total-badge"><?= count($schedules) ?> jadwal</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Jadwal berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Jadwal berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Jadwal berhasil dihapus.</p>
        <?php endif; ?>

        <?php if (!$masterReady) : ?>
            <p class="form-error" role="status">
                Lengkapi kelas dan ruang terlebih dahulu sebelum membuat jadwal.
                <?php if ($classes === []) : ?><a href="../kelas-kuliah/index.php">Buka kelas</a><?php endif; ?>
                <?php if ($rooms === []) : ?><a href="../ruang/index.php">Tambah ruang</a><?php endif; ?>
            </p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Tambah jadwal</h2>
                <p>Jadwal otomatis memeriksa bentrok dosen dan ruang pada hari yang sama.</p>
            </div>
            <form class="master-create-form schedule-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Kelas
                    <select class="master-input" name="id_kelas_kuliah" required <?= !$masterReady ? 'disabled' : '' ?>>
                        <option value="">Pilih kelas</option>
                        <?php foreach ($classes as $class) : ?>
                            <option value="<?= (int) $class['id_kelas_kuliah'] ?>"><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['kode_kelas'] . ' / ' . $class['tahun_akademik'] . ' ' . $class['semester'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Hari
                    <select class="master-input" name="hari" required <?= !$masterReady ? 'disabled' : '' ?>>
                        <option value="">Pilih hari</option>
                        <?php foreach ($days as $day) : ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Ruang
                    <select class="master-input" name="id_ruang" required <?= !$masterReady ? 'disabled' : '' ?>>
                        <option value="">Pilih ruang</option>
                        <?php foreach ($rooms as $room) : ?>
                            <option value="<?= (int) $room['id_ruang'] ?>"><?= htmlspecialchars($room['kode_ruang'] . ' - ' . $room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Jam mulai
                    <input class="master-input" type="time" name="jam_mulai" required <?= !$masterReady ? 'disabled' : '' ?>>
                </label>
                <label class="form-field">Jam selesai
                    <input class="master-input" type="time" name="jam_selesai" required <?= !$masterReady ? 'disabled' : '' ?>>
                </label>
                <button class="save-button" type="submit" <?= !$masterReady ? 'disabled' : '' ?>>Tambah jadwal</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar jadwal</h2>
                    <p>Jadwal yang bentrok tidak akan tersimpan.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table schedule-table">
                        <thead>
                            <tr><th scope="col">Kelas</th><th scope="col">Hari</th><th scope="col">Ruang</th><th scope="col">Jam</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($schedules === []) : ?>
                                <tr><td class="empty-cell" colspan="5">Belum ada jadwal.</td></tr>
                            <?php else : ?>
                                <?php foreach ($schedules as $schedule) : ?>
                                    <tr>
                                        <td data-label="Kelas">
                                            <form class="master-update-form" id="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_jadwal_kuliah" value="<?= (int) $schedule['id_jadwal_kuliah'] ?>">
                                                <select class="master-input" name="id_kelas_kuliah" required>
                                                    <?php foreach ($classes as $class) : ?>
                                                        <option value="<?= (int) $class['id_kelas_kuliah'] ?>" <?= (int) $schedule['id_kelas_kuliah'] === (int) $class['id_kelas_kuliah'] ? 'selected' : '' ?>><?= htmlspecialchars($class['kode_mk'] . ' - ' . $class['kode_kelas'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td data-label="Hari">
                                            <select class="master-input" name="hari" form="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>" required>
                                                <?php foreach ($days as $day) : ?>
                                                    <option value="<?= $day ?>" <?= $schedule['hari'] === $day ? 'selected' : '' ?>><?= $day ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Ruang">
                                            <select class="master-input" name="id_ruang" form="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>" required>
                                                <?php foreach ($rooms as $room) : ?>
                                                    <option value="<?= (int) $room['id_ruang'] ?>" <?= (int) $schedule['id_ruang'] === (int) $room['id_ruang'] ? 'selected' : '' ?>><?= htmlspecialchars($room['kode_ruang'] . ' - ' . $room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td data-label="Jam">
                                            <div class="schedule-times">
                                                <input class="master-input" type="time" name="jam_mulai" form="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>" value="<?= htmlspecialchars(substr((string) $schedule['jam_mulai'], 0, 5), ENT_QUOTES, 'UTF-8') ?>" required>
                                                <input class="master-input" type="time" name="jam_selesai" form="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>" value="<?= htmlspecialchars(substr((string) $schedule['jam_selesai'], 0, 5), ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="schedule-<?= (int) $schedule['id_jadwal_kuliah'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($schedule['kode_mk'] . ' ' . $schedule['kode_kelas'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_jadwal_kuliah" value="<?= (int) $schedule['id_jadwal_kuliah'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus jadwal <?= htmlspecialchars($schedule['kode_mk'] . ' ' . $schedule['kode_kelas'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus jadwal">
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
                if (!window.confirm(`Hapus jadwal ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>
