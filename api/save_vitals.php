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

if(!isset($data["patient_id"])){

    echo json_encode([
        "success" => false,
        "message" => "Missing patient_id"
    ]);

    exit;
}

$patient_id = intval($data["patient_id"]);

try{

/*
=========================================
BMI
=========================================
*/
if(isset($data["bmi"])){

    $bmi = floatval($data["bmi"]);
    $bmi_status = $data["bmi_status"] ?? null;

    $stmt = $pdo->prepare("

        UPDATE health_records

        SET
            bmi = ?,
            bmi_status = ?

        WHERE id = (

            SELECT id FROM (

                SELECT id
                FROM health_records
                WHERE patient_id = ?
                ORDER BY id DESC
                LIMIT 1

            ) AS latest_record

        )

    ");

    $stmt->execute([

        $bmi,
        $bmi_status,
        $patient_id

    ]);

    echo json_encode([
        "success" => true,
        "type" => "bmi"
    ]);

    exit;
}

    /*
    /*
=========================================
HEIGHT
=========================================
*/
if(isset($data["height_cm"])){

    $height = floatval($data["height_cm"]);

    $stmt = $pdo->prepare("
        UPDATE health_records
        SET height_cm = ?
        WHERE patient_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $height,
        $patient_id
    ]);

    echo json_encode([
        "success" => true,
        "type" => "height"
    ]);

    exit;
}
    /*
    =========================================
    WEIGHT
    =========================================
    */
    if(isset($data["weight_kg"])){

        $weight = floatval($data["weight_kg"]);

        $stmt = $pdo->prepare("
            UPDATE health_records
            SET weight_kg = ?
            WHERE patient_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $weight,
            $patient_id
        ]);

        echo json_encode([
            "success" => true,
            "type" => "weight"
        ]);

        exit;
    }

   /*
=========================================
TEMPERATURE
=========================================
*/
if(isset($data["body_temp_c"])){

    $temp = floatval($data["body_temp_c"]);

    $stmt = $pdo->prepare("
        UPDATE health_records
        SET temperature_c = ?
        WHERE patient_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $temp,
        $patient_id
    ]);

    echo json_encode([
        "success" => true,
        "type" => "temperature"
    ]);

    exit;
}

    /*
    =========================================
    OXIMETER
    =========================================
    */
    if(isset($data["spo2"])){

        $spo2 = floatval($data["spo2"]);

        $stmt = $pdo->prepare("
            UPDATE health_records
            SET spo2_percent = ?
            WHERE patient_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $spo2,
            $patient_id
        ]);

        echo json_encode([
            "success" => true,
            "type" => "spo2"
        ]);

        exit;
    }

    /*
    =========================================
    BLOOD PRESSURE
    =========================================
    */
    if(
        isset($data["systolic_bp"]) &&
        isset($data["diastolic_bp"]) &&
        isset($data["pulse_bpm"])
    ){

        $systolic  = intval($data["systolic_bp"]);
        $diastolic = intval($data["diastolic_bp"]);
        $pulse     = intval($data["pulse_bpm"]);

        $stmt = $pdo->prepare("
            UPDATE health_records
            SET
                systolic_bp = ?,
                diastolic_bp = ?,
                pulse_bpm = ?
            WHERE patient_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $systolic,
            $diastolic,
            $pulse,
            $patient_id
        ]);

        echo json_encode([
            "success" => true,
            "type" => "blood_pressure"
        ]);

        exit;
    }

    /*
    =========================================
    UNKNOWN TYPE
    =========================================
    */
    echo json_encode([
        "success" => false,
        "message" => "Unknown measurement type"
    ]);

}catch(PDOException $e){

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>

