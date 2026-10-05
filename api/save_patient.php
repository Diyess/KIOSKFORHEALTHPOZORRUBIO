<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// DATABASE
$host = "localhost";
$db   = "medical_kiosk";
$user = "kiosk";
$pass = "1234";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

} catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

    exit;
}

// RECEIVE JSON
$data = json_decode(file_get_contents("php://input"), true);

if(!$data){

    echo json_encode([
        "success" => false,
        "error" => "No JSON received"
    ]);

    exit;
}

// VALUES
$first_name    = trim($data["first_name"] ?? "");
$last_name     = trim($data["last_name"] ?? "");
$date_of_birth = $data["date_of_birth"] ?? "";
$age           = intval($data["age"] ?? 0);
$gender        = $data["gender"] ?? "";
$phone         = $data["phone"] ?? "";
$barangay      = $data["barangay"] ?? "";
$municipality  = "Pozorrubio";
$province      = "Pangasinan";
$face_image    = $data["face_image"] ?? null;

try {

    // INSERT PATIENT
    $stmt = $pdo->prepare("
        INSERT INTO patients
        (
            first_name,
            last_name,
            date_of_birth,
            age,
            gender,
            phone,
            barangay,
            municipality,
            province,
            face_image
        )
        VALUES
        (
            :first_name,
            :last_name,
            :date_of_birth,
            :age,
            :gender,
            :phone,
            :barangay,
            :municipality,
            :province,
            :face_image
        )
    ");

    $stmt->execute([
        ':first_name'    => $first_name,
        ':last_name'     => $last_name,
        ':date_of_birth' => $date_of_birth,
        ':age'           => $age,
        ':gender'        => $gender,
        ':phone'         => $phone,
        ':barangay'      => $barangay,
        ':municipality'  => $municipality,
        ':province'      => $province,
        ':face_image'    => $face_image
    ]);

    $patient_id = $pdo->lastInsertId();

    // CREATE HEALTH RECORD
    $stmt2 = $pdo->prepare("
        INSERT INTO health_records (patient_id)
        VALUES (:patient_id)
    ");

    $stmt2->execute([
        ':patient_id' => $patient_id
    ]);

    $record_id = $pdo->lastInsertId();

    echo json_encode([
        "success"   => true,
        "patient_id"=> $patient_id,
        "record_id" => $record_id
    ]);

} catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>