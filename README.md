# mmhr_census

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
            $colPatient_Name = "A";
            $colAdmission_Date = "K";
            $colDischarge_Date = "M";
            $colMember_Category = "T";
            $tableName = "patient_records_3";
        } else {
            continue; 
        }

        for ($rowIndex = $startRow; $rowIndex <= $highestRow; $rowIndex++) { 
            $admissionDate = $sheet->getCell("{$colAdmissionDate}$rowIndex")->getValue();
            $patientName = $sheet->getCell("{$colPatientName}$rowIndex")->getValue();
            $admission_Date = isset($colAdmission_Date) ? $sheet->getCell("{$colAdmission_Date}$rowIndex")->getValue() : null;
            $discharge_Date = isset($colDischarge_Date) ? $sheet->getCell("{$colDischarge_Date}$rowIndex")->getValue() : null;
            $member_Category = isset($colMember_Category) ? trim($sheet->getCell("{$colMember_Category}$rowIndex")->getValue()) : null;

            if (is_numeric($admissionDate)) {
                $admissionDate = Date::excelToDateTimeObject($admissionDate)->format('Y-m-d');
            }

            if (is_numeric($admission_Date)) {
                $admission_Date = Date::excelToDateTimeObject($admission_Date)->format('Y-m-d');
            }

            if ($discharge_Date != null && is_numeric($discharge_Date)) {
                $discharge_Date = Date::excelToDateTimeObject($discharge_Date)->format('Y-m-d');
            }

            if (empty($patientName) || empty($admissionDate)) {
                continue;
            }

            if ($tableName === "patient_records_3") {
                $patient_Name = $sheet->getCell("{$colPatient_Name}$rowIndex")->getValue();
                $batchData[] = "($fileId, '$sheetName', '$admission_Date', " . 
                    (!empty($discharge_Date) ? "'$discharge_Date'" : "NULL") . ", " .
                    (!empty($member_Category) ? "'$member_Category'" : "NULL") . ", '$patient_Name')";

            } elseif ($tableName === "patient_records_2") {
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
                
                } elseif ($tableName === "patient_records_2") {
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
            } elseif ($tableName === "patient_records_2") {
                $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2) VALUES " . implode(',', $batchData);
            } else {
                $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
            }
            $conn->query($query);
        }
    }

    echo "File uploaded and processed successfully!";
} else {
    echo "No file uploaded.";
}

$conn->close();
?>



