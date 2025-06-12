<?php
header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

// Validasi apakah path dikirim
if (!isset($_GET["path"])) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'path' diperlukan"]);
    exit;
}

$path = $_GET["path"];
$pathParts = explode("/", $path);
$resource = $pathParts[0] ?? "";
$id = isset($pathParts[1]) ? (int)$pathParts[1] : 0;

if ($resource !== "users") {
    http_response_code(404);
    echo json_encode(["error" => "Resource tidak ditemukan"]);
    exit;
}

switch ($method) {
    case "GET":
        require_once "get_users.php";
        break;
    case "POST":
        require_once "post_user.php";
        break;
    case "PUT":
        require_once "put_user.php";
        break;
    case "DELETE":
        require_once "delete_user.php";
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Metode tidak diizinkan"]);
        break;
}
?>
