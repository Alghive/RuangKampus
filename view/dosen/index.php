<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $nidn = $_POST['nidn'] ?? '';
        $name = $_POST['nama_dosen'] ?? '';
        $email = $_POST['email'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_dosen'] ?? null, FILTER_VALIDATE_INT) : null;

        if (!is_string($nidn) || strlen(trim($nidn)) > 20) {
            $error = 'NIDN maksimal 20 karakter.';
        } elseif (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 100) {
            $error = 'Nama dosen wajib diisi dan maksimal 100 karakter.';
        } elseif (!is_string($email) || strlen(trim($email)) > 150 || (trim($email) !== '' && !filter_var(trim($email), FILTER_VALIDATE_EMAIL))) {
            $error = 'Format email tidak valid atau melebihi 150 karakter.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data dosen tidak valid.';
        } else {
            $nidn = trim($nidn) === '' ? null : trim($nidn);
            $name = trim($name);
            $email = trim($email) === '' ? null : trim($email);
            $saved = $action === 'create'
                ? \App\Models\DosenModel::create($nidn, $name, $email)
                : \App\Models\DosenModel::update((int) $id, $nidn, $name, $email);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Data dosen gagal disimpan. NIDN mungkin sudah digunakan.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_dosen'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data dosen tidak valid.';
        } elseif (\App\Models\DosenModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Dosen tidak dapat dihapus selama masih mengampu kelas kuliah.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$dosen = \App\Models\DosenModel::all();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosen | RuangKampus</title>
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
            <a class="is-current" href="index.php" aria-current="page">Dosen</a>
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
            <a href="../jadwal-kuliah/index.php">Jadwal Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Dosen</h1>
                <p class="page-description">Kelola data dosen dan kontak akademik.</p>
            </div>
            <span class="total-badge"><?= count($dosen) ?> dosen</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Dosen berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Data dosen berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Dosen berhasil dihapus.</p>
        <?php endif; ?>

        <section class="master-create-section" aria-labelledby="create-title">
            <div class="master-section-heading">
                <h2 id="create-title">Tambah dosen</h2>
                <p>NIDN dan email opsional; NIDN harus unik jika diisi.</p>
            </div>
            <form class="master-create-form lecturer-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">NIDN
                    <input class="master-input" type="text" name="nidn" maxlength="20">
                </label>
                <label class="form-field">Nama dosen
                    <input class="master-input" type="text" name="nama_dosen" maxlength="100" required>
                </label>
                <label class="form-field">Email
                    <input class="master-input" type="email" name="email" maxlength="150">
                </label>
                <button class="save-button" type="submit">Tambah dosen</button>
            </form>
        </section>

        <section class="master-list-section" aria-labelledby="list-title">
            <div class="master-section-heading">
                <div>
                    <h2 id="list-title">Daftar dosen</h2>
                    <p>Dosen yang masih mengampu kelas tidak dapat dihapus.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="master-table lecturer-table">
                        <thead>
                            <tr><th scope="col">NIDN</th><th scope="col">Nama dosen</th><th scope="col">Email</th><th scope="col">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($dosen === []) : ?>
                                <tr><td class="empty-cell" colspan="4">Belum ada data dosen.</td></tr>
                            <?php else : ?>
                                <?php foreach ($dosen as $lecturer) : ?>
                                    <tr>
                                        <td data-label="NIDN">
                                            <form class="master-update-form" id="lecturer-<?= (int) $lecturer['id_dosen'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_dosen" value="<?= (int) $lecturer['id_dosen'] ?>">
                                                <input class="master-input" type="text" name="nidn" maxlength="20" value="<?= htmlspecialchars($lecturer['nidn'] ?? '', ENT_QUOTES, 'UTF-8') ?>" aria-label="NIDN <?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?>">
                                            </form>
                                        </td>
                                        <td data-label="Nama dosen"><input class="master-input" type="text" name="nama_dosen" maxlength="100" form="lecturer-<?= (int) $lecturer['id_dosen'] ?>" value="<?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                                        <td data-label="Email"><input class="master-input" type="email" name="email" maxlength="150" form="lecturer-<?= (int) $lecturer['id_dosen'] ?>" value="<?= htmlspecialchars($lecturer['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                                        <td data-label="Aksi">
                                            <div class="master-actions">
                                                <button class="secondary-button" type="submit" form="lecturer-<?= (int) $lecturer['id_dosen'] ?>">Simpan</button>
                                                <form class="master-delete-form" method="post" data-name="<?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_dosen" value="<?= (int) $lecturer['id_dosen'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus dosen <?= htmlspecialchars($lecturer['nama_dosen'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus dosen">
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
                if (!window.confirm(`Hapus data dosen ${form.dataset.name}?`)) event.preventDefault();
            });
        });
    </script>
</body>

</html>