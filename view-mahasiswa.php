<?php
// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "perkuliahan");

// ambil data dari tabel mahasiswa
$result = mysqli_query($conn, "SELECT * FROM mahasiswa");

// ambil data (fetch) mahasiswa dari object result
// mysqli_fetch_row() // mengembalikan array numerik
// mysqli_fetch_assoc() // mengembalikan array associative
// mysqli_fetch_array() // mengembalikan keduanya
// mysqli_fetch_object() // mengembalikan object
// var_dump($mhs->npm);

// while ($mhs = mysqli_fetch_assoc($result)) {
// }

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }

    </style>
</head>

<body>
    <h1> Selamat Datang, Mahasiswa</h1>

    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>No.</th>
            <th>Aksi</th>
            <th>NPM</th>
            <th>Nama Mahasiswa</th>
            <th>Jenis Kelamin</th>
            <th>Program Studi</th>
            <th>Angkatan</th>
            <th>Agama</th>
        </tr>
        <?php while ($mhs = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $mhs['id_mahasiswa'] ?></td>
            <td>
                <a href="">ubah</a> | <a href="">hapus</a>
            </td>
            <td><?= $mhs['npm'] ?></td>
            <td><?= $mhs['nama_mahasiswa'] ?></td>
            <td><?= $mhs['jenis_kelamin'] ?></td>
            <td><?= $mhs['program_studi'] ?></td>
            <td><?= $mhs['angkatan'] ?></td>
            <td><?= $mhs['agama'] ?></td>
        </tr>
        <?php endwhile; ?>
</body>

</html>