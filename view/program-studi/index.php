<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$roles = ['admin', 'operator', 'viewer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $code = $_POST['kode_prodi'] ?? '';
        $name = $_POST['nama_prodi'] ?? '';
        $id = $action === 'update' ? filter_var($_POST['id_program_studi'] ?? null, FILTER_VALIDATE_INT) : null;

        if (!is_string($code) || strlen(trim($code)) > 20) {
            $error = 'Kode prodi maksimal 20 karakter.';
        } elseif (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 100) {
            $error = 'Nama program studi wajib diisi dan maksimal 100 karakter.';
        } elseif ($action === 'update' && ($id === false || $id === null || $id <= 0)) {
            $error = 'Data program studi tidak valid.';
        } else {
            $code = trim($code) === '' ? null : strtoupper(trim($code));
            $name = trim($name);
            $saved = $action === 'create'
                ? \App\Models\ProgramStudiModel::create($code, $name)
                : \App\Models\ProgramStudiModel::update((int) $id, $code, $name);

            if ($saved) {
                header('Location: index.php?status=' . ($action === 'create' ? 'created' : 'updated'));
                exit;
            }
            $error = 'Data gagal disimpan. Kode atau nama prodi mungkin sudah digunakan.';
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id_program_studi'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $error = 'Data program studi tidak valid.';
        } elseif (\App\Models\ProgramStudiModel::delete((int) $id)) {
            header('Location: index.php?status=deleted');
            exit;
        } else {
            $error = 'Prodi tidak dapat dihapus selama masih digunakan mahasiswa atau mata kuliah.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$programStudi = \App\Models\ProgramStudiModel::all();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Studi | RuangKampus</title>
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

    <main class="page-shell program-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / Data Master</p>
        <nav class="master-tabs" aria-label="Data master">
            <a class="is-current" href="index.php" aria-current="page">Program Studi</a>
            <a href="../dosen/index.php">Dosen</a>
            <a href="../mata-kuliah/index.php">Mata Kuliah</a>
            <a href="../tahun-akademik/index.php">Tahun Akademik</a>
            <a href="../ruang/index.php">Ruang</a>
            <a href="../kelas-kuliah/index.php">Kelas Kuliah</a>
            <a href="../jadwal-kuliah/index.php">Jadwal Kuliah</a>
        </nav>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Program Studi</h1>
                <p class="page-description">Kelola program studi yang tersedia untuk mahasiswa.</p>
            </div>
            <span class="total-badge"><?= count($programStudi) ?> prodi</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Program studi berhasil ditambahkan.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Program studi berhasil diperbarui.</p>
        <?php elseif ($status === 'deleted') : ?>
            <p class="notice" role="status">Program studi berhasil dihapus.</p>
        <?php endif; ?>

        <section class="program-create-section" aria-labelledby="create-program-title">
            <div class="program-section-heading">
                <div>
                    <h2 id="create-program-title">Tambah program studi</h2>
                    <p>Kode bersifat opsional dan harus unik jika diisi.</p>
                </div>
            </div>
            <form class="program-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Kode prodi
                    <input class="program-input" type="text" name="kode_prodi" maxlength="20" autocomplete="off" placeholder="Contoh: IF">
                </label>
                <label class="form-field">Nama program studi
                    <input class="program-input" type="text" name="nama_prodi" maxlength="100" required>
                </label>
                <button class="save-button" type="submit">Tambah prodi</button>
            </form>
        </section>

        <section class="program-list-section" aria-labelledby="program-list-title">
            <div class="program-section-heading">
                <div>
                    <h2 id="program-list-title">Daftar program studi</h2>
                    <p>Mengubah nama prodi juga memperbarui nama tampilan mahasiswa terkait.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="program-table">
                        <thead>
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Nama program studi</th>
                                <th scope="col">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($programStudi === []) : ?>
                                <tr><td class="empty-cell" colspan="3">Belum ada program studi.</td></tr>
                            <?php else : ?>
                                <?php foreach ($programStudi as $program) : ?>
                                    <tr>
                                        <td data-label="Kode">
                                            <form class="program-update-form" id="program-<?= (int) $program['id_program_studi'] ?>" method="post">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id_program_studi" value="<?= (int) $program['id_program_studi'] ?>">
                                                <input class="program-input" type="text" name="kode_prodi" maxlength="20" value="<?= htmlspecialchars($program['kode_prodi'] ?? '', ENT_QUOTES, 'UTF-8') ?>" aria-label="Kode prodi <?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?>">
                                            </form>
                                        </td>
                                        <td data-label="Nama program studi">
                                            <input class="program-input" type="text" name="nama_prodi" maxlength="100" form="program-<?= (int) $program['id_program_studi'] ?>" value="<?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Nama prodi <?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </td>
                                        <td data-label="Perubahan">
                                            <div class="program-actions">
                                                <button class="secondary-button" type="submit" form="program-<?= (int) $program['id_program_studi'] ?>">Simpan</button>
                                                <form class="program-delete-form" method="post" data-name="<?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_program_studi" value="<?= (int) $program['id_program_studi'] ?>">
                                                    <button class="program-delete-button" type="submit" aria-label="Hapus <?= htmlspecialchars($program['nama_prodi'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus prodi">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M3 6h18" />
                                                            <path d="M8 6V4h8v2" />
                                                            <path d="m19 6-1 14H6L5 6" />
                                                            <path d="M10 11v6M14 11v6" />
                                                        </svg>
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
        document.querySelectorAll('.program-delete-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(`Hapus program studi ${form.dataset.name}? Prodi yang sedang digunakan tidak dapat dihapus.`)) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>

</html>