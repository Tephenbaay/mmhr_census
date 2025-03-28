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
    <link rel="stylesheet" href="sige\style.css">
    <title>MMHR Census</title>
</head>
<body style="background-image: url('sige/bgg.png'); background-size: cover; background-repeat: no-repeat;">
    
    <nav class="navbar">
        <div class="navb">
        <img src="sige/download-removebg-preview.png" alt="icon">
        <h1>BicutanMed</h1>
        </div>
    </nav>

        <div class="main">
            <div class="container">
                    <h2>Upload Excel File</h2>
                 <form action="upload.php" method="POST" enctype="multipart/form-data">
                     <input type="file" name="excelFile" accept=".xlsx, .xls">
                     <button type="submit" class="btn1">Upload</button>
                 </form>
            </div>

            <div class="content">
            <h2>Select File & Sheet</h2>
                <form action="display_summary.php" method="GET">
                    <label for="file">Select File:</label>
                    <select name="file_id" id="file">
                        <?php while ($file = $files->fetch_assoc()): ?>
                            <option value="<?= $file['id'] ?>"><?= $file['file_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="submit">Load Sheets</button>
                </form>
            </div>
        </div>
        
</body>
</html>