<?php 
// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "perkuliahan");

function query ($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getMahasiswaById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM mahasiswa WHERE id_mahasiswa = ?");
    if (!$stmt) {
        return null;
    }

    $id = (int) $id;
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $mahasiswa = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $mahasiswa;
}

function ubahMahasiswa($data) {
    global $conn;

    $id = (int) $data['id_mahasiswa'];
    $npm = trim($data['npm']);
    $nama = trim($data['nama_mahasiswa']);
    $jenisKelamin = $data['jenis_kelamin'];
    $programStudi = trim($data['program_studi']);
    $angkatan = (int) $data['angkatan'];
    $agama = $data['agama'];

    try {
        $stmt = mysqli_prepare($conn, "UPDATE mahasiswa SET npm = ?, nama_mahasiswa = ?, jenis_kelamin = ?, program_studi = ?, angkatan = ?, agama = ? WHERE id_mahasiswa = ?");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssssisi", $npm, $nama, $jenisKelamin, $programStudi, $angkatan, $agama, $id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $success;
    } catch (mysqli_sql_exception $exception) {
        return false;
    }
}

function tambahMahasiswaBanyak($rows) {
    global $conn;

    if (count($rows) === 0) {
        return false;
    }

    $lockAcquired = false;
    $transactionStarted = false;
    $stmt = null;

    try {
        $lockResult = mysqli_query($conn, "SELECT GET_LOCK('ruangkampus_mahasiswa_insert', 10) AS lock_acquired");
        $lock = $lockResult ? mysqli_fetch_assoc($lockResult) : null;
        if ((int) ($lock['lock_acquired'] ?? 0) !== 1) {
            return false;
        }
        $lockAcquired = true;

        if (!mysqli_begin_transaction($conn)) {
            return false;
        }
        $transactionStarted = true;

        $idResult = mysqli_query($conn, "SELECT id_mahasiswa FROM mahasiswa ORDER BY id_mahasiswa DESC LIMIT 1 FOR UPDATE");
        if (!$idResult) {
            throw new RuntimeException('Tidak dapat menentukan ID mahasiswa berikutnya.');
        }
        $lastMahasiswa = mysqli_fetch_assoc($idResult);
        $nextId = $lastMahasiswa ? (int) $lastMahasiswa['id_mahasiswa'] + 1 : 1;

        $stmt = mysqli_prepare($conn, "INSERT INTO mahasiswa (id_mahasiswa, npm, nama_mahasiswa, jenis_kelamin, program_studi, angkatan, agama) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new RuntimeException('Tidak dapat menyiapkan query insert mahasiswa.');
        }

        foreach ($rows as $row) {
            $id = $nextId++;
            $npm = $row['npm'];
            $nama = $row['nama_mahasiswa'];
            $jenisKelamin = $row['jenis_kelamin'];
            $programStudi = $row['program_studi'];
            $angkatan = (int) $row['angkatan'];
            $agama = $row['agama'];

            mysqli_stmt_bind_param($stmt, "issssis", $id, $npm, $nama, $jenisKelamin, $programStudi, $angkatan, $agama);
            mysqli_stmt_execute($stmt);
        }

        mysqli_commit($conn);
        $transactionStarted = false;

        return count($rows);
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            mysqli_rollback($conn);
        }
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
        if ($lockAcquired) {
            mysqli_query($conn, "SELECT RELEASE_LOCK('ruangkampus_mahasiswa_insert')");
        }
    }
}

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>