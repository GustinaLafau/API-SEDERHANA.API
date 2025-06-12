<?php
require_once "config.php";

// Ambil ID dari body request DELETE
$input = file_get_contents("php://input");
parse_str($input, $data); // jika kamu kirim dalam format x-www-form-urlencoded

$id = isset($data["id"]) ? (int)$data["id"] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "ID diperlukan dan harus valid"]);
    exit;
}

// Hapus dari database
$query = "DELETE FROM tb_users WHERE id = $id";
if (mysqli_query($conn, $query) && mysqli_affected_rows($conn) > 0) {
    echo json_encode(["message" => "User berhasil dihapus"]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "User tidak ditemukan"]);
}

mysqli_close($conn);
