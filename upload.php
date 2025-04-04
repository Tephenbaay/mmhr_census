<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

ini_set('max_execution_time', 300);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function convertExcelDate($value) {
    if (is_numeric($value)) {
        return date('Y-m-d', Date::excelToTimestamp($value));
    } else {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            $date = DateTime::createFromFormat('d/m/Y', "$day/$month/$year");
            return $date ? $date->format('Y-m-d') : null;
        }
    }
    return null;
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
            $colDischargeDate = "D";
            $colMemberCategory = "L";
            $tableName = "patient_records";
        } elseif (stripos($sheetName, 'admission') !== false) {
            $startRow = 11;
            $colPatientName = "D"; 
            $colAdmissionDate = "H"; 
            $colMemberCategory = "K";
            $tableName = "patient_records_2";
        } elseif (stripos($sheetName, 'discharge') !== false) {
            $startRow = 3;
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
            $admissionDate = convertExcelDate(trim($sheet->getCell("{$colAdmissionDate}$rowIndex")->getValue()));
            $dischargeDate = convertExcelDate(trim($sheet->getCell("{$colDischargeDate}$rowIndex")->getValue()));
            
            if (empty($patientName) || empty($admissionDate)) {
                continue;
            }

            if ($tableName === "patient_records_3") {
                $category = trim($sheet->getCell("{$colCategory}$rowIndex")->getValue());
                
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', " . 
                    (!empty($dischargeDate) ? "'$dischargeDate'" : "NULL") . ", " . 
                    (!empty($category) ? "'$category'" : "NULL") . ", '$patientName')";

            } elseif ($tableName === "patient_records_2") {
                $cell = $sheet->getCell("{$colMemberCategory}$rowIndex");
                $memberCategory = $cell->getCalculatedValue(); 
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$patientName', '$memberCategory')";
            } else {
                $memberCategory = trim($sheet->getCell("{$colMemberCategory}$rowIndex")->getValue());
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$dischargeDate', '$memberCategory', '$patientName')";
            }

            if (count($batchData) >= 500) {
                if ($tableName === "patient_records_3") {
                    $query = "INSERT INTO patient_records_3 (file_id, sheet_name_3, date_admitted, date_discharge, category, patient_name_3) VALUES " . implode(',', $batchData);
                } else if ($tableName === "patient_records_2") {
                    $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2, category_2) VALUES " . implode(',', $batchData);
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
                $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2, category_2) VALUES " . implode(',', $batchData);
            } else {
                $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
            }
            $conn->query($query);
        }
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['file_uploaded'] = true;    

    if (!isset($_SESSION['file_uploaded']) || !empty($sheets)) {
        echo "<script>alert('File uploaded and processed successfully!');</script>";
    }

    echo "<script>alert('File uploaded and processed successfully!'); window.location.href='dashboard.php';</script>";
    exit();
} else {
    echo "No file uploaded.";
}

$conn->close();
?>
