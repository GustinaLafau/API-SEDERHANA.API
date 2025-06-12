<?php
require_once "config.php";

// Fungsi untuk membersihkan input
function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Validasi ID dari path
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID diperlukan"]);
    exit;
}

// Direktori untuk menyimpan file upload
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Inisialisasi variabel
$name = "";
$email = "";
$role_id = null;
$profile_image = null;

// Cek method request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'PUT') {
    // Untuk PUT request, baca raw input
    $putData = file_get_contents("php://input");
    
    // Cek apakah data adalah multipart/form-data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Parse multipart data untuk PUT request
        $boundary = substr($contentType, strpos($contentType, 'boundary=') + 9);
        $parts = explode('--' . $boundary, $putData);
        
        foreach ($parts as $part) {
            if (strpos($part, 'Content-Disposition: form-data') !== false) {
                // Parse field name
                preg_match('/name="([^"]*)"/', $part, $matches);
                if (isset($matches[1])) {
                    $fieldName = $matches[1];
                    
                    // Get field value
                    $fieldValue = trim(substr($part, strpos($part, "\r\n\r\n") + 4));
                    $fieldValue = rtrim($fieldValue, "\r\n");
                    
                    // Assign values
                    switch ($fieldName) {
                        case 'name':
                            $name = cleanInput($fieldValue);
                            break;
                        case 'email':
                            $email = cleanInput($fieldValue);
                            break;
                        case 'role_id':
                            $role_id = (int)$fieldValue;
                            break;
                        case 'profile_image':
                            // Handle file upload for PUT request
                            if (strpos($part, 'filename=') !== false) {
                                preg_match('/filename="([^"]*)"/', $part, $fileMatches);
                                if (isset($fileMatches[1]) && !empty($fileMatches[1])) {
                                    $fileName = $fileMatches[1];
                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    $allowedExts = ["jpg", "jpeg", "png"];
                                    
                                    if (in_array($fileExt, $allowedExts)) {
                                        $newFileName = uniqid() . "." . $fileExt;
                                        $destPath = $uploadDir . $newFileName;
                                        
                                        // Extract file content
                                        $fileContent = substr($part, strpos($part, "\r\n\r\n") + 4);
                                        $fileContent = rtrim($fileContent, "\r\n");
                                        
                                        if (file_put_contents($destPath, $fileContent)) {
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
                            }
                            break;
                    }
                }
            }
        }
    } else {
        // Jika bukan multipart, parse sebagai JSON atau form-urlencoded
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($putData, true);
            if ($data) {
                $name = isset($data["name"]) ? cleanInput($data["name"]) : "";
                $email = isset($data["email"]) ? cleanInput($data["email"]) : "";
                $role_id = isset($data["role_id"]) ? (int)$data["role_id"] : null;
            }
        } else {
            // Parse form-urlencoded data
            parse_str($putData, $data);
            $name = isset($data["name"]) ? cleanInput($data["name"]) : "";
            $email = isset($data["email"]) ? cleanInput($data["email"]) : "";
            $role_id = isset($data["role_id"]) ? (int)$data["role_id"] : null;
        }
    }
} else {
    // Untuk POST request (fallback)
    $name = isset($_POST["name"]) ? cleanInput($_POST["name"]) : "";
    $email = isset($_POST["email"]) ? cleanInput($_POST["email"]) : "";
    $role_id = isset($_POST["role_id"]) ? (int)$_POST["role_id"] : null;
    
    // Tangani upload file profile_image (jika ada)
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["profile_image"]["tmp_name"];
        $fileName = $_FILES["profile_image"]["name"];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ["jpg", "jpeg", "png"];

        // Validasi ekstensi file
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
}

// Validasi dasar
if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Name dan email wajib diisi",
        "debug" => [
            "method" => $method,
            "name" => $name,
            "email" => $email,
            "content_type" => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ]
    ]);
    exit;
}

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare("UPDATE tb_users SET name = ?, email = ?, role_id = ?, profile_image = ? WHERE id = ?");

// Bind parameters
$stmt->bind_param("ssisi", $name, $email, $role_id, $profile_image, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["message" => "User berhasil diperbarui"]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "User tidak ditemukan atau gagal diperbarui"]);
}

$stmt->close();
mysqli_close($conn);
?>