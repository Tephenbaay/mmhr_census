<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

ini_set('max_execution_time', 300);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";
$port = 3308;

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] != 0) {
    die("Error: No file uploaded or upload failed.");
}

if (isset($_FILES['excelFile'])) {
    $fileName = $_FILES['excelFile']['name'];
    $fileTmp = $_FILES['excelFile']['tmp_name'];

    if (!file_exists($fileTmp)) {
        die("Error: Uploaded file does not exist.");
    }
}

if (isset($_FILES['excelFile'])) {
    $fileName = $_FILES['excelFile']['name'];
    $fileTmp = $_FILES['excelFile']['tmp_name'];

    $stmt = $conn->prepare("INSERT INTO uploaded_files (file_name) VALUES (?)");
    $stmt->bind_param("s", $fileName);
    $stmt->execute();
    $fileId = $stmt->insert_id;
    $stmt->close();

    $spreadsheet = IOFactory::load($fileTmp);

    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $highestRow = $sheet->getHighestRow(); 
        
        $batchData = [];

        $normalizedSheetName = strtoupper(trim($sheetName));

        if (preg_match('/^(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)$/', $normalizedSheetName)) {
            $startRow = 3;
            $colPatientName = "F"; 
            $colAdmissionDate = "C"; 
            $tableName = "patient_records";
        } elseif (stripos($sheetName, 'admission') !== false) {
            $startRow = 6;
            $colPatientName = "D"; 
            $colAdmissionDate = "H"; 
            $tableName = "patient_records_2";
        } elseif (stripos($sheetName, 'discharge(billing)') !== false) {
            $startRow = 4;
            $colPatientName = "A";
            $colAdmissionDate = "K";
            $colDischargeDate = "M";
            $colCategory = "T";
            $tableName = "patient_records_3";
        } else {
            continue;
        }

        for ($rowIndex = $startRow; $rowIndex <= $highestRow; $rowIndex++) {
            $patientName = trim($sheet->getCell("{$colPatientName}$rowIndex")->getValue());
            $admissionDate = $sheet->getCell("{$colAdmissionDate}$rowIndex")->getValue();
            $dischargeDate = isset($colDischargeDate) ? $sheet->getCell("{$colDischargeDate}$rowIndex")->getValue() : null;
            $category = isset($colCategory) ? trim($sheet->getCell("{$colCategory}$rowIndex")->getValue()) : null;

            if (is_numeric($admissionDate)) {
                $admissionDate = Date::excelToDateTimeObject($admissionDate)->format('Y-m-d');
            }

            if ($dischargeDate !== null && is_numeric($dischargeDate)) {
                $dischargeDate = Date::excelToDateTimeObject($dischargeDate)->format('Y-m-d');
            }

            if (empty($patientName) || empty($admissionDate)) {
                continue;
            }

            if ($tableName === "patient_records_3") {
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', " . 
                    (!empty($dischargeDate) ? "'$dischargeDate'" : "NULL") . ", " . 
                    (!empty($category) ? "'$category'" : "NULL") . ", '$patientName')";
            } else if ($tableName === "patient_records_2") {
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$patientName')";
            } else {
                $dischargeDate = $sheet->getCell("D$rowIndex")->getValue();
                $memberCategory = $sheet->getCell("M$rowIndex")->getValue();

                if (is_numeric($dischargeDate)) {
                    $dischargeDate = Date::excelToDateTimeObject($dischargeDate)->format('Y-m-d');
                }

                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$dischargeDate', '$memberCategory', '$patientName')";
            }

            if (count($batchData) >= 500) {
                if ($tableName === "patient_records_3") {
                    $query = "INSERT INTO patient_records_3 (file_id, sheet_name_3, date_admitted, date_discharge, category, patient_name_3) VALUES " . implode(',', $batchData);
                } else if ($tableName === "patient_records_2") {
                    $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2) VALUES " . implode(',', $batchData);
                } else {
                    $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
                }
                $conn->query($query);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            if ($tableName === "patient_records_3") {
                $query = "INSERT INTO patient_records_3 (file_id, sheet_name_3, date_admitted, date_discharge, category, patient_name_3) VALUES " . implode(',', $batchData);
            } else if ($tableName === "patient_records_2") {
                $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2) VALUES " . implode(',', $batchData);
            } else {
                $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
            }
            $conn->query($query);
        }
    }

        session_start();
    $_SESSION['file_uploaded'] = true;

        session_start();
    if (!isset($_SESSION['file_uploaded']) || empty($sheets)) {
        echo "<script>alert('No file uploaded. Please upload a file first.');</script>";
    }

    echo "File uploaded and processed successfully!";
} else {
    echo "No file uploaded.";
}

$conn->close();
?>
