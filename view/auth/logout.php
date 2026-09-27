<?php
require __DIR__ . '/../../controller/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

requireCsrfToken();
logoutUser();
header('Location: login.php');
exit;