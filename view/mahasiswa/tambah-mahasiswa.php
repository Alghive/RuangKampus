<?php
require __DIR__ . '/../../controller/function.php';

$jenisKelaminOptions = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$agamaOptions = ['Islam', 'Protestan', 'Kristen', 'Budha', 'Hindu', 'Konghucu', 'Aliran Lain'];
$emptyRow = [
    'npm' => '',
    'nama_mahasiswa' => '',
    'jenis_kelamin' => '',
    'program_studi' => '',
    'angkatan' => '',
    'agama' => '',
];
$rows = [$emptyRow];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedRows = $_POST['mahasiswa'] ?? null;

    if (!is_array($submittedRows) || count($submittedRows) === 0) {
        $error = 'Tambahkan setidaknya satu data mahasiswa.';
    } else {
        $submittedRows = array_values($submittedRows);
        $tooManyRows = count($submittedRows) > 50;
        $rows = [];

        foreach (array_slice($submittedRows, 0, 50) as $submittedRow) {
            $submittedRow = is_array($submittedRow) ? $submittedRow : [];
            $row = [];
            foreach ($emptyRow as $field => $defaultValue) {
                $value = $submittedRow[$field] ?? '';
                $row[$field] = is_string($value) || is_numeric($value) ? trim((string) $value) : '';
            }
            $rows[] = $row;
        }

        if ($tooManyRows) {
            $error = 'Maksimal 50 mahasiswa dapat ditambahkan sekaligus.';
        } else {
            $seenNpm = [];
            foreach ($rows as $index => $row) {
                if (in_array('', $row, true)) {
                    $error = 'Lengkapi semua kolom pada baris ' . ($index + 1) . '.';
                    break;
                }
                if (strlen($row['npm']) > 20 || strlen($row['nama_mahasiswa']) > 100 || strlen($row['program_studi']) > 100) {
                    $error = 'NPM, nama, atau program studi pada baris ' . ($index + 1) . ' melebihi batas karakter.';
                    break;
                }
                if (!ctype_digit($row['angkatan']) || (int) $row['angkatan'] > 9999) {
                    $error = 'Angkatan pada baris ' . ($index + 1) . ' harus berupa angka yang valid.';
                    break;
                }
                if (!array_key_exists($row['jenis_kelamin'], $jenisKelaminOptions)) {
                    $error = 'Pilih jenis kelamin yang valid pada baris ' . ($index + 1) . '.';
                    break;
                }
                if (!in_array($row['agama'], $agamaOptions, true)) {
                    $error = 'Pilih agama yang valid pada baris ' . ($index + 1) . '.';
                    break;
                }
                if (isset($seenNpm[$row['npm']])) {
                    $error = 'NPM ' . $row['npm'] . ' dimasukkan lebih dari satu kali.';
                    break;
                }
                $seenNpm[$row['npm']] = true;
            }

            if ($error === '') {
                $jumlahDitambah = tambahMahasiswaBanyak($rows);
                if ($jumlahDitambah !== false) {
                    header('Location: view-mahasiswa.php?status=tambah&jumlah=' . $jumlahDitambah);
                    exit;
                }
                $error = 'Data gagal disimpan. Pastikan NPM belum terdaftar, lalu coba lagi.';
            }
        }
    }

    if (count($rows) === 0) {
        $rows = [$emptyRow];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mahasiswa | RuangKampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/mahasiswa.css?v=3">
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand-mark" aria-hidden="true">RK</div>
            <span class="brand-name">RuangKampus</span>
            <span class="brand-caption">Administrasi Akademik</span>
        </div>
    </header>

    <main class="page-shell batch-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="view-mahasiswa.php">Direktori / Akademik</a> / Tambah</p>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Tambah mahasiswa</h1>
                <p class="page-description">Masukkan data mahasiswa baru.</p>
            </div>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= escapeHtml($error) ?></p>
        <?php endif; ?>

        <form class="batch-panel" method="post">
            <div class="batch-toolbar">
                <div>
                    <h2 class="table-title">Data mahasiswa</h2>
                    <p class="batch-summary" id="batchSummary" role="status" aria-live="polite">1 baris data</p>
                </div>
                <button class="secondary-button" id="addStudentRow" type="button">+ Tambah baris</button>
            </div>

            <div class="batch-rows" id="batchRows">
                <?php foreach ($rows as $index => $row) : ?>
                    <?php $row = is_array($row) ? array_merge($emptyRow, $row) : $emptyRow; ?>
                    <section class="batch-row" aria-label="Data mahasiswa <?= $index + 1 ?>">
                        <div class="batch-row-header">
                            <h3 class="batch-row-title">Mahasiswa <span class="row-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span></h3>
                            <button class="remove-row" type="button" aria-label="Hapus baris <?= $index + 1 ?>" <?= count($rows) === 1 ? 'hidden' : '' ?>>Hapus baris</button>
                        </div>
                        <div class="batch-row-fields">
                            <label class="form-field">NPM
                                <input type="text" name="mahasiswa[<?= $index ?>][npm]" maxlength="20" required value="<?= escapeHtml($row['npm']) ?>">
                            </label>
                            <label class="form-field">Nama Mahasiswa
                                <input type="text" name="mahasiswa[<?= $index ?>][nama_mahasiswa]" maxlength="100" required value="<?= escapeHtml($row['nama_mahasiswa']) ?>">
                            </label>
                            <label class="form-field">Jenis Kelamin
                                <select name="mahasiswa[<?= $index ?>][jenis_kelamin]" required>
                                    <option value="">Pilih jenis kelamin</option>
                                    <?php foreach ($jenisKelaminOptions as $value => $label) : ?>
                                        <option value="<?= escapeHtml($value) ?>" <?= $row['jenis_kelamin'] === $value ? 'selected' : '' ?>><?= escapeHtml($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="form-field">Program Studi
                                <input type="text" name="mahasiswa[<?= $index ?>][program_studi]" maxlength="100" required value="<?= escapeHtml($row['program_studi']) ?>">
                            </label>
                            <label class="form-field">Angkatan
                                <input type="number" name="mahasiswa[<?= $index ?>][angkatan]" min="1" max="9999" required value="<?= escapeHtml($row['angkatan']) ?>">
                            </label>
                            <label class="form-field">Agama
                                <select name="mahasiswa[<?= $index ?>][agama]" required>
                                    <option value="">Pilih agama</option>
                                    <?php foreach ($agamaOptions as $agama) : ?>
                                        <option value="<?= escapeHtml($agama) ?>" <?= $row['agama'] === $agama ? 'selected' : '' ?>><?= escapeHtml($agama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <template id="studentRowTemplate">
                <section class="batch-row" aria-label="Data mahasiswa">
                    <div class="batch-row-header">
                        <h3 class="batch-row-title">Mahasiswa <span class="row-index"></span></h3>
                        <button class="remove-row" type="button">Hapus baris</button>
                    </div>
                    <div class="batch-row-fields">
                        <label class="form-field">NPM
                            <input type="text" name="mahasiswa[__INDEX__][npm]" maxlength="20" required>
                        </label>
                        <label class="form-field">Nama Mahasiswa
                            <input type="text" name="mahasiswa[__INDEX__][nama_mahasiswa]" maxlength="100" required>
                        </label>
                        <label class="form-field">Jenis Kelamin
                            <select name="mahasiswa[__INDEX__][jenis_kelamin]" required>
                                <option value="">Pilih jenis kelamin</option>
                                <?php foreach ($jenisKelaminOptions as $value => $label) : ?>
                                    <option value="<?= escapeHtml($value) ?>"><?= escapeHtml($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="form-field">Program Studi
                            <input type="text" name="mahasiswa[__INDEX__][program_studi]" maxlength="100" required>
                        </label>
                        <label class="form-field">Angkatan
                            <input type="number" name="mahasiswa[__INDEX__][angkatan]" min="1" max="9999" required>
                        </label>
                        <label class="form-field">Agama
                            <select name="mahasiswa[__INDEX__][agama]" required>
                                <option value="">Pilih agama</option>
                                <?php foreach ($agamaOptions as $agama) : ?>
                                    <option value="<?= escapeHtml($agama) ?>"><?= escapeHtml($agama) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </section>
            </template>

            <div class="batch-actions">
                <a class="cancel-link" href="view-mahasiswa.php">Batal</a>
                <button class="save-button" type="submit">Simpan semua data</button>
            </div>
        </form>
    </main>

    <script>
        const batchRows = document.getElementById('batchRows');
        const addStudentRow = document.getElementById('addStudentRow');
        const studentRowTemplate = document.getElementById('studentRowTemplate');
        const batchSummary = document.getElementById('batchSummary');
        let nextStudentIndex = <?= count($rows) ?>;

        function refreshBatchRows() {
            const rows = Array.from(batchRows.querySelectorAll('.batch-row'));
            rows.forEach((row, index) => {
                row.querySelector('.row-index').textContent = String(index + 1).padStart(2, '0');
                row.setAttribute('aria-label', `Data mahasiswa ${index + 1}`);
                const removeButton = row.querySelector('.remove-row');
                removeButton.hidden = rows.length === 1;
                removeButton.setAttribute('aria-label', `Hapus baris ${index + 1}`);
            });

            batchSummary.textContent = `${rows.length} baris data`;
            addStudentRow.disabled = rows.length >= 50;
        }

        addStudentRow.addEventListener('click', () => {
            const newRow = studentRowTemplate.content.cloneNode(true);
            newRow.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace('__INDEX__', String(nextStudentIndex));
            });
            nextStudentIndex += 1;
            batchRows.append(newRow);
            refreshBatchRows();
        });

        batchRows.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-row');
            if (!removeButton || batchRows.querySelectorAll('.batch-row').length <= 1) return;

            removeButton.closest('.batch-row').remove();
            refreshBatchRows();
        });

        refreshBatchRows();
    </script>
</body>

</html>