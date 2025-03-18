<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch uploaded files
$files = $conn->query("SELECT * FROM uploaded_files");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
</head>
<body>
    <h2>Upload Excel File</h2>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="excelFile" accept=".xlsx, .xls">
        <button type="submit">Upload</button>
    </form>

    <h2>Select File & Sheet</h2>
    <form action="summary.php" method="GET">
        <label for="file">Select File:</label>
        <select name="file_id" id="file">
            <?php while ($file = $files->fetch_assoc()): ?>
                <option value="<?= $file['id'] ?>"><?= $file['file_name'] ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Load Sheets</button>
    </form>
</body>
</html>
