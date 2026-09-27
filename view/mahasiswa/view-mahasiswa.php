<?php
require __DIR__ . '/../../controller/function.php';

$mahasiswa = query("SELECT * FROM mahasiswa");
$berhasilUbah = ($_GET['status'] ?? '') === 'ubah';
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
    <link rel="stylesheet" href="../css/mahasiswa.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand-mark" aria-hidden="true">RK</div>
            <span class="brand-name">RuangKampus</span>
            <span class="brand-caption">Administrasi Akademik</span>
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
        <?php elseif (($_GET['status'] ?? '') === 'tambah' && $jumlahDitambah > 0) : ?>
            <p class="notice" role="status"><?= $jumlahDitambah ?> data mahasiswa berhasil ditambahkan.</p>
        <?php endif; ?>

        <section aria-labelledby="table-title">
            <div class="table-toolbar">
                <h2 class="table-title" id="table-title">Data mahasiswa</h2>
                <div class="table-actions">
                    <input class="search-input" id="searchMahasiswa" type="search" placeholder="Cari nama, NPM, atau program studi" aria-label="Cari mahasiswa">
                    <a class="primary-link" href="tambah-mahasiswa.php">+ Tambah mahasiswa</a>
                </div>
            </div>

            <div class="table-frame">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">No.</th>
                                <th scope="col">Aksi</th>
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
                                <tr><td class="empty-cell" colspan="8">Belum ada data mahasiswa.</td></tr>
                            <?php else : ?>
                                <?php $i = 1; ?>
                                <?php foreach ($mahasiswa as $mhs) : ?>
                                    <tr data-search="<?= escapeHtml(implode(' ', [$mhs['npm'], $mhs['nama_mahasiswa'], $mhs['program_studi'], $mhs['angkatan'], $mhs['agama']])) ?>">
                                        <td class="number-cell"><?= $i++ ?></td>
                                        <td><a href="ubah-mahasiswa.php?id=<?= (int) $mhs['id_mahasiswa'] ?>" class="edit-link">Ubah</a></td>
                                        <td class="npm-cell"><?= escapeHtml($mhs['npm']) ?></td>
                                        <td class="name-cell"><?= escapeHtml($mhs['nama_mahasiswa']) ?></td>
                                        <td><?= ($mhs['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : (($mhs['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : '-') ?></td>
                                        <td><?= escapeHtml($mhs['program_studi']) ?></td>
                                        <td><?= escapeHtml($mhs['angkatan']) ?></td>
                                        <td><?= escapeHtml($mhs['agama']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="emptyState" hidden><td class="empty-cell" colspan="8">Tidak ada mahasiswa yang cocok dengan pencarian.</td></tr>
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
</body>

</html>