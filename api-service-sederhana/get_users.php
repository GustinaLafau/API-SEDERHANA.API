<?php
require_once "config.php";

// Cek apakah ada parameter ID dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    // Ambil satu user berdasarkan ID
    $query = "SELECT id, name, email, role_id, profile_image, created_at, updated_at FROM tb_users WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "User tidak ditemukan"]);
    }
} else {
    // Ambil semua user
    $query = "SELECT id, name, email, role_id, profile_image, created_at, updated_at FROM tb_users";
    $result = mysqli_query($conn, $query);
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    echo json_encode($users);
}

mysqli_close($conn);
?>
