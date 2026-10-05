<?php

header("Content-Type: application/json");

$host = "localhost";
$dbname = "medical_kiosk";
$username = "kiosk";
$password = "1234";

try{

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){

    echo json_encode([
        "success" => false,
        "message" => "No JSON received"
    ]);

    exit;
}

if(
    !isset($data["patient_id"]) ||
    !isset($data["face_image"])
){

    echo json_encode([
        "success" => false,
        "message" => "Missing patient_id or face_image"
    ]);

    exit;
}

$patient_id = intval($data["patient_id"]);

$face_image = $data["face_image"];

/* UPLOAD FOLDER */
$upload_dir = "/var/www/html/try/uploads/";

if(!file_exists($upload_dir)){

    mkdir($upload_dir, 0777, true);
}

/* REMOVE BASE64 HEADER */
$face_image = preg_replace(
    '#^data:image/\w+;base64,#i',
    '',
    $face_image
);

/* FIX SPACES */
$face_image = str_replace(' ', '+', $face_image);

/* DECODE */
$image_data = base64_decode($face_image);

if($image_data === false){

    echo json_encode([
        "success" => false,
        "message" => "Base64 decode failed"
    ]);

    exit;
}

/* FILE NAME */
$filename = "face_" . time() . "_" . $patient_id . ".jpg";

$full_path = $upload_dir . $filename;

/* SAVE IMAGE */
$result = file_put_contents($full_path, $image_data);

if($result === false){

    echo json_encode([
        "success" => false,
        "message" => "Failed to save JPG"
    ]);

    exit;
}

/* SAVE PATH TO DATABASE */
$db_path = "uploads/" . $filename;

try{

    $stmt = $pdo->prepare("
        UPDATE patients
        SET face_image = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $db_path,
        $patient_id
    ]);

    echo json_encode([
        "success" => true,
        "path" => $db_path
    ]);

}catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>