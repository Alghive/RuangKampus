<?php
require __DIR__ . '/../../controller/function.php';
require __DIR__ . '/../../controller/auth.php';
requireLogin();

$mahasiswa = query("SELECT m.*, p.nama_prodi AS nama_prodi FROM mahasiswa AS m LEFT JOIN program_studi AS p ON p.id_program_studi = m.id_program_studi");
$canManageStudents = hasRole(['admin', 'operator']);
$berhasilUbah = ($_GET['status'] ?? '') === 'ubah';
$berhasilHapus = ($_GET['status'] ?? '') === 'hapus';
$gagalHapus = ($_GET['status'] ?? '') === 'gagal-hapus';
$jumlahDitambah = max(0, (int) ($_GET['jumlah'] ?? 0));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa | RuangKampus</title>
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

    <main class="page-shell">
        <p class="eyebrow">Direktori / Akademik</p>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Mahasiswa</h1>
                <p class="page-description">Daftar mahasiswa terdaftar dalam sistem akademik.</p>
            </div>
            <span class="total-badge"><?= count($mahasiswa) ?> mahasiswa</span>
        </section>

        <?php if ($berhasilUbah) : ?>
            <p class="notice" role="status">Data mahasiswa berhasil diperbarui.</p>
        <?php elseif ($berhasilHapus) : ?>
            <p class="notice" role="status">Data mahasiswa berhasil dihapus.</p>
        <?php elseif ($gagalHapus) : ?>
            <p class="form-error" role="alert">Data mahasiswa gagal dihapus atau sudah tidak ditemukan.</p>
        <?php elseif (($_GET['status'] ?? '') === 'tambah' && $jumlahDitambah > 0) : ?>
            <p class="notice" role="status"><?= $jumlahDitambah ?> data mahasiswa berhasil ditambahkan.</p>
        <?php endif; ?>

        <section aria-labelledby="table-title">
            <div class="table-toolbar">
                <h2 class="table-title" id="table-title">Data mahasiswa</h2>
                <div class="table-actions">
                    <input class="search-input" id="searchMahasiswa" type="search" placeholder="Cari nama, NPM, atau program studi" aria-label="Cari mahasiswa">
                    <?php if ($canManageStudents) : ?>
                        <a class="primary-link" href="tambah-mahasiswa.php">+ Tambah mahasiswa</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-frame">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">No.</th>
                                <?php if ($canManageStudents) : ?>
                                    <th scope="col">Aksi</th>
                                <?php endif; ?>
                                <th scope="col">NPM</th>
                                <th scope="col">Nama Mahasiswa</th>
                                <th scope="col">Jenis Kelamin</th>
                                <th scope="col">Program Studi</th>
                                <th scope="col">Angkatan</th>
                                <th scope="col">Agama</th>
                            </tr>
                        </thead>
                        <tbody id="mahasiswaRows">
                            <?php if (count($mahasiswa) === 0) : ?>
                                <tr><td class="empty-cell" colspan="<?= $canManageStudents ? 8 : 7 ?>">Belum ada data mahasiswa.</td></tr>
                            <?php else : ?>
                                <?php $i = 1; ?>
                                <?php foreach ($mahasiswa as $mhs) : ?>
                                    <tr data-search="<?= escapeHtml(implode(' ', [$mhs['npm'], $mhs['nama_mahasiswa'], $mhs['nama_prodi'] ?? $mhs['program_studi'], $mhs['angkatan'], $mhs['agama']])) ?>">
                                        <td class="number-cell"><?= $i++ ?></td>
                                            <?php if ($canManageStudents) : ?>
                                            <td class="action-cell">
                                                <div class="row-actions">
                                                    <a href="ubah-mahasiswa.php?id=<?= (int) $mhs['id_mahasiswa'] ?>" class="action-icon" aria-label="Ubah data <?= escapeHtml($mhs['nama_mahasiswa']) ?>" title="Ubah">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M12 20h9" />
                                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                                        </svg>
                                                    </a>
                                                    <form class="delete-form" action="hapus-mahasiswa.php" method="post" data-name="<?= escapeHtml($mhs['nama_mahasiswa']) ?>">
                                                        <input type="hidden" name="id_mahasiswa" value="<?= (int) $mhs['id_mahasiswa'] ?>">
                                                        <?= csrfField() ?>
                                                        <button class="action-icon action-icon--delete" type="submit" aria-label="Hapus data <?= escapeHtml($mhs['nama_mahasiswa']) ?>" title="Hapus">
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
                                            <?php endif; ?>
                                        <td class="npm-cell" data-label="NPM"><?= escapeHtml($mhs['npm']) ?></td>
                                        <td class="name-cell" data-label="Nama"><?= escapeHtml($mhs['nama_mahasiswa']) ?></td>
                                        <td data-label="Jenis Kelamin"><?= ($mhs['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : (($mhs['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : '-') ?></td>
                                        <td data-label="Program Studi"><?= escapeHtml($mhs['nama_prodi'] ?? $mhs['program_studi']) ?></td>
                                        <td data-label="Angkatan"><?= escapeHtml($mhs['angkatan']) ?></td>
                                        <td data-label="Agama"><?= escapeHtml($mhs['agama']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="emptyState" hidden><td class="empty-cell" colspan="<?= $canManageStudents ? 8 : 7 ?>">Tidak ada mahasiswa yang cocok dengan pencarian.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="result-count" id="resultCount" role="status" aria-live="polite">Menampilkan <?= count($mahasiswa) ?> dari <?= count($mahasiswa) ?> mahasiswa</p>
        </section>
    </main>

    <script>
        const searchInput = document.getElementById('searchMahasiswa');
        const studentRows = Array.from(document.querySelectorAll('#mahasiswaRows tr[data-search]'));
        const emptyState = document.getElementById('emptyState');
        const resultCount = document.getElementById('resultCount');
        const totalStudents = studentRows.length;

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim().toLocaleLowerCase('id');
            let visibleCount = 0;

            studentRows.forEach((row) => {
                const matches = row.dataset.search.toLocaleLowerCase('id').includes(searchTerm);
                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });

            if (emptyState) emptyState.hidden = visibleCount !== 0;
            resultCount.textContent = `Menampilkan ${visibleCount} dari ${totalStudents} mahasiswa`;
        });
    </script>
    <script>
        document.querySelectorAll('.delete-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const studentName = form.dataset.name;
                if (!window.confirm(`Hapus data mahasiswa ${studentName}?`)) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>

</html>