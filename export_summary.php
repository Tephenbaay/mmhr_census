<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = [
    'Date', 'Govt', 'Private', 'Self-Employed', 'OFW', 'OWWA', 'SC', 'PWD',
    'Indigent', 'Pensioners', 'NHIP', 'NON-NHIP', 'Total Admissions', 'Total Discharges'
];
$sheet->fromArray($headers, null, 'A1');

// Fetch data from database
$query = "SELECT * FROM mmhr_summary"; // Assuming a table exists
$result = $conn->query($query);

$row = 2; // Start from row 2
while ($data = $result->fetch_assoc()) {
    $sheet->fromArray(array_values($data), null, 'A' . $row);
    $row++;
}

// Generate file
$filename = "mmhr_summary.xlsx";
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$writer->save("php://output");
exit;
?>
