<?php
require __DIR__ . '/../../controller/auth.php';
requireRole(['admin']);

$error = '';
$roles = ['admin', 'operator', 'viewer', 'student'];
$currentUser = currentUser();
$students = \App\Models\MahasiswaModel::all();
$studentsWithoutAccount = [];
foreach ($students as $student) {
    $npm = trim((string) ($student['npm'] ?? ''));
    if ($npm !== '' && \App\Models\UserModel::findActiveByUsername($npm) === null) {
        $studentsWithoutAccount[] = $student;
    }
}
$users = \App\Models\UserModel::allForManagement();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = $_POST['username'] ?? '';
        $fullName = $_POST['nama_lengkap'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!is_string($username) || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $error = 'Username harus 3-50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
        } elseif (!is_string($fullName) || trim($fullName) === '' || strlen($fullName) > 100) {
            $error = 'Nama lengkap wajib diisi dan maksimal 100 karakter.';
        } elseif (!is_string($password) || strlen($password) < 12) {
            $error = 'Password harus minimal 12 karakter.';
        } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
            $error = 'Konfirmasi password tidak sama.';
        } elseif (!is_string($role) || !in_array($role, $roles, true)) {
            $error = 'Pilih role yang valid.';
        } elseif (\App\Models\UserModel::create($username, trim($fullName), password_hash($password, PASSWORD_DEFAULT), $role)) {
            header('Location: index.php?status=created');
            exit;
        } else {
            $error = 'Akun gagal dibuat. Username mungkin sudah digunakan.';
        }
    } elseif ($action === 'link-student') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $studentId = filter_var($_POST['id_mahasiswa'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null || $userId <= 0) {
            $error = 'Pilih akun yang valid.';
        } elseif ($studentId === false || $studentId === null || $studentId <= 0) {
            $error = 'Pilih mahasiswa yang valid.';
        } elseif (\App\Models\UserModel::linkStudent((int) $userId, (int) $studentId)) {
            header('Location: index.php?status=linked');
            exit;
        } else {
            $error = 'Penghubung akun mahasiswa gagal dibuat. Pastikan akun dan mahasiswa valid serta belum terhubung.';
        }
    } elseif ($action === 'create-from-mahasiswa') {
        $studentId = filter_var($_POST['id_mahasiswa'] ?? null, FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if ($studentId === false || $studentId === null || $studentId <= 0) {
            $error = 'Pilih mahasiswa yang valid.';
        } else {
            $student = \App\Models\MahasiswaModel::findById((int) $studentId);
            if ($student === null) {
                $error = 'Data mahasiswa tidak ditemukan.';
            } else {
                $username = trim((string) ($student['npm'] ?? ''));
                if ($username === '') {
                    $error = 'NPM mahasiswa belum tersedia.';
                } elseif (!is_string($password) || strlen($password) < 12) {
                    $error = 'Password minimal 12 karakter.';
                } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
                    $error = 'Konfirmasi password tidak sama.';
                } elseif (\App\Models\UserModel::findActiveByUsername($username) !== null) {
                    $error = 'Akun mahasiswa dengan NPM ini sudah ada.';
                } elseif (\App\Models\UserModel::create($username, trim((string) $student['nama_mahasiswa']), password_hash($password, PASSWORD_DEFAULT), 'student')) {
                    header('Location: index.php?status=created-student');
                    exit;
                } else {
                    $error = 'Akun mahasiswa gagal dibuat.';
                }
            }
        }
    } elseif ($action === 'update-access') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $role = $_POST['role'] ?? '';
        $isActive = ($_POST['is_active'] ?? '') === '1';

        if ($userId === false || $userId === null || !is_string($role) || !in_array($role, $roles, true)) {
            $error = 'Perubahan akun tidak valid.';
        } elseif ((int) $userId === (int) $currentUser['id_user'] && ($role !== 'admin' || !$isActive)) {
            $error = 'Anda tidak dapat menonaktifkan atau mengubah role akun sendiri.';
        } elseif (\App\Models\UserModel::updateAccess((int) $userId, $role, $isActive)) {
            header('Location: index.php?status=updated');
            exit;
        } else {
            $error = 'Perubahan gagal. Pastikan masih ada minimal satu admin aktif.';
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$users = \App\Models\UserModel::allForManagement();
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun | RuangKampus</title>
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

    <main class="page-shell accounts-shell">
        <p class="eyebrow"><a class="breadcrumb-link" href="../mahasiswa/view-mahasiswa.php">Administrasi Akademik</a> / Pengguna</p>
        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <h1 id="page-title">Kelola akun</h1>
                <p class="page-description">Atur pengguna dan hak akses sistem.</p>
            </div>
            <span class="total-badge"><?= count($users) ?> akun</span>
        </section>

        <?php if ($error !== '') : ?>
            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($status === 'created') : ?>
            <p class="notice" role="status">Akun berhasil dibuat.</p>
        <?php elseif ($status === 'created-student') : ?>
            <p class="notice" role="status">Akun mahasiswa berhasil dibuat dari data mahasiswa.</p>
        <?php elseif ($status === 'linked') : ?>
            <p class="notice" role="status">Akun mahasiswa berhasil dihubungkan ke data mahasiswa.</p>
        <?php elseif ($status === 'updated') : ?>
            <p class="notice" role="status">Hak akses berhasil diperbarui.</p>
        <?php endif; ?>

        <section class="account-create-section" aria-labelledby="create-title">
            <div class="account-section-heading">
                <div>
                    <h2 id="create-title">Buat akun</h2>
                    <p>Setiap akun memiliki role untuk mengatur izin.</p>
                </div>
            </div>
            <form class="account-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <label class="form-field">Username
                    <input class="account-input" type="text" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="off" required>
                </label>
                <label class="form-field">Nama lengkap
                    <input class="account-input" type="text" name="nama_lengkap" maxlength="100" required>
                </label>
                <label class="form-field">Password
                    <input class="account-input" type="password" name="password" minlength="12" autocomplete="new-password" required>
                </label>
                <label class="form-field">Ulangi password
                    <input class="account-input" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </label>
                <label class="form-field">Role
                    <select class="account-input" name="role" required>
                        <option value="viewer">Viewer</option>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                        <option value="student">Mahasiswa</option>
                    </select>
                </label>
                <div class="account-form-actions">
                    <span>Password minimal 12 karakter.</span>
                    <button class="save-button" type="submit">Buat akun</button>
                </div>
            </form>
        </section>

        <section class="account-create-section" aria-labelledby="create-student-title">
            <div class="account-section-heading">
                <div>
                    <h2 id="create-student-title">Buat akun mahasiswa dari data mahasiswa</h2>
                    <p>Gunakan data mahasiswa yang sudah ada. Username otomatis mengikuti NPM.</p>
                </div>
            </div>
            <form class="account-create-form" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create-from-mahasiswa">
                <label class="form-field">Mahasiswa
                    <select class="account-input" name="id_mahasiswa" required>
                        <option value="">Pilih mahasiswa</option>
                        <?php foreach ($studentsWithoutAccount as $student) : ?>
                            <option value="<?= (int) $student['id_mahasiswa'] ?>"><?= htmlspecialchars($student['nama_mahasiswa'] . ' (' . $student['npm'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-field">Password akun
                    <input class="account-input" type="password" name="password" minlength="12" autocomplete="new-password" required>
                </label>
                <label class="form-field">Ulangi password
                    <input class="account-input" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </label>
                <div class="account-form-actions">
                    <span>Username akan otomatis dibuat dari NPM mahasiswa.</span>
                    <button class="save-button" type="submit">Buat akun mahasiswa</button>
                </div>
            </form>
        </section>

       

        <section class="account-list-section" aria-labelledby="account-list-title">
            <div class="account-section-heading">
                <div>
                    <h2 id="account-list-title">Daftar akun</h2>
                    <p>Perubahan role dan status berlaku setelah disimpan.</p>
                </div>
            </div>
            <div class="table-frame">
                <div class="table-scroll">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th scope="col">Pengguna</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col">Login terakhir</th>
                                <th scope="col">Pengaturan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <?php $isCurrentUser = (int) $user['id_user'] === (int) $currentUser['id_user']; ?>
                                <tr>
                                    <td data-label="Pengguna">
                                        <span class="account-name"><?= htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="account-username"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?><?= $isCurrentUser ? ' (Anda)' : '' ?></span>
                                    </td>
                                    <td data-label="Role">
                                        <span class="role-badge role-badge--<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="account-status <?= (int) $user['is_active'] === 1 ? 'is-active' : 'is-inactive' ?>"><?= (int) $user['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                                    </td>
                                    <td data-label="Login terakhir"><?= $user['last_login_at'] ? htmlspecialchars(date('d M Y, H:i', strtotime($user['last_login_at'])), ENT_QUOTES, 'UTF-8') : 'Belum pernah' ?></td>
                                    <td data-label="Pengaturan">
                                        <form class="account-access-form" method="post">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="update-access">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id_user'] ?>">
                                            <select class="access-role-select" name="role" aria-label="Role <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" <?= $isCurrentUser ? 'disabled' : '' ?>>
                                                <?php foreach ($roles as $role) : ?>
                                                    <option value="<?= $role ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select class="access-status-select" name="is_active" aria-label="Status <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" <?= $isCurrentUser ? 'disabled' : '' ?>>
                                                <option value="1" <?= (int) $user['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                <option value="0" <?= (int) $user['is_active'] !== 1 ? 'selected' : '' ?>>Nonaktif</option>
                                            </select>
                                            <?php if ($isCurrentUser) : ?>
                                                <input type="hidden" name="role" value="admin">
                                                <input type="hidden" name="is_active" value="1">
                                                <span class="self-account-note">Akun sendiri</span>
                                            <?php else : ?>
                                                <button class="secondary-button" type="submit">Simpan</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>

</html>