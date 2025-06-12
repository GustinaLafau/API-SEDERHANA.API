<?php
require_once "config.php";

// Fungsi untuk membersihkan input
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Direktori untuk menyimpan file upload
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Ambil data dari form-data
$name = isset($_POST["name"]) ? cleanInput($_POST["name"]) : "";
$email = isset($_POST["email"]) ? cleanInput($_POST["email"]) : "";
$passwordRaw = isset($_POST["password"]) ? $_POST["password"] : "";
$role_id = isset($_POST["role_id"]) ? (int)$_POST["role_id"] : null;
$profile_image = null;

// Validasi wajib
if (empty($name) || empty($email) || empty($passwordRaw)) {
    http_response_code(400);
    echo json_encode(["error" => "Name, email, dan password wajib diisi"]);
    exit;
}

// Enkripsi password
$password = password_hash($passwordRaw, PASSWORD_DEFAULT);

// Tangani upload gambar jika ada
if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES["profile_image"]["tmp_name"];
    $fileName = $_FILES["profile_image"]["name"];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ["jpg", "jpeg", "png"];

    if (in_array($fileExt, $allowedExts)) {
        $newFileName = uniqid() . "." . $fileExt;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $profile_image = $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Gagal mengunggah file"]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Ekstensi file tidak diizinkan. Gunakan jpg, jpeg, atau png"]);
        exit;
    }
}

// Buat query INSERT
$query = "INSERT INTO tb_users (name, email, password, role_id, profile_image) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssis", $name, $email, $password, $role_id, $profile_image);

// Eksekusi query
if (mysqli_stmt_execute($stmt)) {
    $id = mysqli_insert_id($conn);
    http_response_code(201);
    echo json_encode([
        "message" => "User berhasil ditambahkan",
        "id" => $id,
        "name" => $name,
        "email" => $email,
        "role_id" => $role_id,
        "profile_image" => $profile_image
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Gagal menambah user: " . mysqli_error($conn)]);
}

// Tutup koneksi
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
