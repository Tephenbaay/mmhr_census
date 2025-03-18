<?php
require 'vendor/autoload.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sheets = [];
$sheets_2 = [];

if (isset($_GET['file_id'])) {
    $file_id = $_GET['file_id'];

    // Fetch sheets from patient_records
    $query1 = "SELECT DISTINCT sheet_name FROM patient_records WHERE file_id = $file_id";
    $result1 = $conn->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $sheets[] = $row['sheet_name'];
    }

    // Fetch admission sheets from patient_records_2
    $query2 = "SELECT DISTINCT sheet_name_2 FROM patient_records_2 WHERE file_id = $file_id AND sheet_name_2 LIKE 'admission(%)'";
    $result2 = $conn->query($query2);
    while ($row = $result2->fetch_assoc()) {
        $sheets_2[] = $row['sheet_name_2'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Summary</title>
</head>
<body>
    <h2>Select Sheet</h2>
    <form action="display_summary.php" method="GET">
        <input type="hidden" name="file_id" value="<?= $file_id ?>">

        <label for="sheet">Select Main Sheet:</label>
        <select name="sheet_name" id="sheet">
            <?php foreach ($sheets as $sheet): ?>
                <option value="<?= $sheet ?>"><?= $sheet ?></option>
            <?php endforeach; ?>
        </select>

        <label for="sheet_2">Select Admission Sheet:</label>
        <select name="sheet_name_2" id="sheet_2">
            <option value="" disabled selected>Select Admission Sheet</option>
            <?php foreach ($sheets_2 as $sheet): ?>
                <option value="<?= $sheet ?>"><?= $sheet ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Load Summary</button>
    </form>
</body>
</html>
