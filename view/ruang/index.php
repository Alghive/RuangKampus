<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $code = $_POST['kode_ruang'] ?? '';
        $name = $_POST['nama_ruang'] ?? '';
        $capacityInput = $_POST['kapasitas'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_ruang'] ?? null, FILTER_VALIDATE_INT) : null;

        if (!is_string($code) || trim($code) === '' || strlen(trim($code)) > 30) {
            $error = 'Kode ruang wajib diisi dan maksimal 30 karakter.';
        } elseif (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 100) {
            $error = 'Nama ruang wajib diisi dan maksimal 100 karakter.';
        } elseif (!is_string($capacityInput) || ($capacityInput !== '' && (!ctype_digit($capacityInput) || (int) $capacityInput < 1 || (int) $capacityInput > 65535))) {
            $error = 'Kapasitas harus berupa angka antara 1 sampai 65535, atau dikosongkan.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data ruang tidak valid.';
        } else {
            $code = strtoupper(trim($code));
            $name = trim($name);
            $capacity = $capacityInput === '' ? null : (int) $capacityInput;
            $saved = $action === 'create'
                ? \App\Models\RuangModel::create($code, $name, $capacity)
                : \App\Models\RuangModel::update((int) $id, $code, $name, $capacity);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Ruang gagal disimpan. Kode ruang mungkin sudah digunakan.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_ruang'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data ruang tidak valid.';
        } elseif (\App\Models\RuangModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Ruang tidak dapat dihapus selama masih dipakai jadwal kuliah.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$rooms = \App\Models\RuangModel::all();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang | RuangKampus</title>
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
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a class="is-current" href="index.php" aria-current="page">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Ruang</h1>
                <p class="page-description">Kelola ruang yang tersedia untuk jadwal perkuliahan.</p>
            </div>
            <span class="total-badge"><?= count($rooms) ?> ruang</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Ruang berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Ruang berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Ruang berhasil dihapus.</p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Tambah ruang</h2>
                <p>Kapasitas opsional; kode ruang harus unik.</p>
            </div>
            <form class="master-create-form room-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Kode ruang
                    <input class="master-input" type="text" name="kode_ruang" maxlength="30" required>
                </label>
                <label class="form-field">Nama ruang
                    <input class="master-input" type="text" name="nama_ruang" maxlength="100" required>
                </label>
                <label class="form-field">Kapasitas
                    <input class="master-input" type="number" name="kapasitas" min="1" max="65535">
                </label>
                <button class="save-button" type="submit">Tambah ruang</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar ruang</h2>
                    <p>Ruang yang masih dipakai jadwal tidak dapat dihapus.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table room-table">
                        <thead>
                            <tr><th scope="col">Kode</th><th scope="col">Nama ruang</th><th scope="col">Kapasitas</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($rooms === []) : ?>
                                <tr><td class="empty-cell" colspan="4">Belum ada data ruang.</td></tr>
                            <?php else : ?>
                                <?php foreach ($rooms as $room) : ?>
                                    <tr>
                                        <td data-label="Kode">
                                            <form class="master-update-form" id="room-<?= (int) $room['id_ruang'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_ruang" value="<?= (int) $room['id_ruang'] ?>">
                                                <input class="master-input" type="text" name="kode_ruang" maxlength="30" value="<?= htmlspecialchars($room['kode_ruang'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Kode ruang <?= htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            </form>
                                        </td>
                                        <td data-label="Nama ruang"><input class="master-input" type="text" name="nama_ruang" maxlength="100" form="room-<?= (int) $room['id_ruang'] ?>" value="<?= htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                                        <td data-label="Kapasitas"><input class="master-input room-capacity-input" type="number" name="kapasitas" min="1" max="65535" form="room-<?= (int) $room['id_ruang'] ?>" value="<?= htmlspecialchars($room['kapasitas'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="room-<?= (int) $room['id_ruang'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_ruang" value="<?= (int) $room['id_ruang'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus ruang <?= htmlspecialchars($room['nama_ruang'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus ruang">
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
                if (!window.confirm(`Hapus ruang ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>