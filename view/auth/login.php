<?php
require __DIR__ . '/../../controller/auth.php';

if (currentUser() !== null) {
    header('Location: ../mahasiswa/view-mahasiswa.php');
    exit;
}

$error = '';
$usernameInput = $_POST['username'] ?? '';
$passwordInput = $_POST['password'] ?? '';
if (!is_string($usernameInput)) {
    $usernameInput = '';
}
if (!is_string($passwordInput)) {
    $passwordInput = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir berakhir. Muat ulang halaman dan coba lagi.';
    } elseif (strlen($usernameInput) > 50) {
        $error = 'Username atau password tidak sesuai.';
    } else {
        if (loginUser(trim($usernameInput), $passwordInput)) {
            header('Location: ../mahasiswa/view-mahasiswa.php');
            exit;
        }
        $error = 'Username atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | RuangKampus</title>
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
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-panel" aria-labelledby="login-title">
            <p class="eyebrow">Administrasi Akademik</p>
            <h1 id="login-title">Masuk</h1>
            <p class="auth-description">Masuk dengan akun kampus Anda.</p>

            <?php if ($error !== '') : ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form class="auth-form" method="post">
                <?= csrfField() ?>
                <label class="form-field">Username
                    <input class="auth-input" type="text" name="username" autocomplete="username" maxlength="50" required autofocus value="<?= htmlspecialchars($usernameInput, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="form-field">Password
                    <input class="auth-input" type="password" name="password" autocomplete="current-password" required>
                </label>
                <button class="save-button auth-submit" type="submit">Masuk</button>
            </form>
        </section>
    </main>
</body>

</html>