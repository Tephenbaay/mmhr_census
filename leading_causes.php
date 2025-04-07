<?php
include 'config.php';

// Get all distinct sheets for dropdown
$sheet_query = $conn->query("SELECT DISTINCT sheet_name FROM patient_records ORDER BY sheet_name");
$sheets = [];
while ($row = $sheet_query->fetch_assoc()) {
    $sheets[] = $row['sheet_name'];
}

$selected_sheet = $_GET['sheet'] ?? '';

$icd_summary = [];

if ($selected_sheet) {
    $query = "
        SELECT 
            lc.icd_10,
            SUM(CASE WHEN pr.member_category = 'N/A' THEN 1 ELSE 0 END) AS non_nhip_total,
            SUM(CASE WHEN pr.member_category != 'N/A' THEN 1 ELSE 0 END) AS nhip_total
        FROM leading_causes lc
        JOIN patient_records pr 
            ON lc.patient_name = pr.patient_name AND lc.file_id = pr.file_id
        WHERE lc.sheet_name = ?
        GROUP BY lc.icd_10
        ORDER BY nhip_total DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $selected_sheet);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $icd_summary[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Leading Causes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sige/summary.css">
   
</head>
<body class="container mt-4">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <div class="navb">
            <img src="sige/download-removebg-preview.png" alt="icon">
            <div class="nav-text">
            <a class="navbar-brand" href="dashboard.php">BicutanMed</a>
            <p style = "margin-top: 0px;">Caring For Life</p>
            </div>
            <form action="dashboard.php">
            <button class="btn2">↪</button>
        </div>
    </div>
</nav>

<aside>
    <div class="sidebar" id="sidebar">
        <h3>Upload Excel File</h3>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit" class="btn1">Upload</button>
            <button onclick="printTable()" class="btn btn-success mt-3">Print Table</button>
        </form>
        <form action="mmhr_census.php" method="GET">
            <button type="submit" class="btn btn-primary mt-3">View MMHR Census</button>
        </form>
        <form action="display_summary.php" method="GET">
            <button type="submit" class="btn btn-primary mt-3">View MMHR Table</button>
        </form>
    </div>
</aside>
<button class="toggle-btn" id="toggleBtn">Hide</button>

<div class="table-responsive" id="content">
<h2 class="text-center mb-4">Leading Causes Summary</h2>

<form method="GET" class="mb-4">
<div class="sige">
    <label>Select Sheet:</label>
    <select name="sheet" class="form-select w-25 d-inline-block" onchange="this.form.submit()">
        <option value="" disabled selected>Select Month</option>
        <?php foreach ($sheets as $sheet): ?>
            <option value="<?= $sheet ?>" <?= $sheet === $selected_sheet ? 'selected' : '' ?>>
                <?= $sheet ?>
            </option>
        <?php endforeach; ?>
    </select>
    </div>
</form>

<div class="table-responsive1" id="printable">
<?php if ($selected_sheet): ?>
    <table class="table table-bordered">
        <thead class="table-dark text-center">
            <tr class="th1">
                <th rowspan="2" style="background-color: black; color: white;">ICD-10</th>
                <th colspan="2" style="background-color: black; color: white;">TOTAL</th>
            </tr>
            <tr>
                <th style="background-color: black; color: white;">NHIP</th>
                <th style="background-color: black; color: white;">NON-NHIP</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php foreach ($icd_summary as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['icd_10']) ?></td>
                    <td><?= $row['nhip_total'] ?></td>
                    <td><?= $row['non_nhip_total'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-muted">Please select a month to view ICD-10 summary.</p>
<?php endif; ?>
</div>
</div>
<script>

function printTable() {
    var printContents = document.getElementById("printable").innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;

    reinitializeEventListeners();
}

function reinitializeEventListeners() {
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");
    let isSidebarVisible = true;

    toggleBtn.addEventListener("click", () => {
        isSidebarVisible = !isSidebarVisible;
        if (isSidebarVisible) {
            sidebar.classList.remove("hidden");
            toggleBtn.style.left = "260px";
            content.style.marginLeft = "270px";
            content.style.marginRight = "0"; 
            toggleBtn.textContent = "Hide";
        } else {
            sidebar.classList.add("hidden");
            toggleBtn.style.left = "10px";
            content.style.marginLeft = "auto"; 
            content.style.marginRight = "auto"; 
            toggleBtn.textContent = "Show";
        }
    });
}

const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const content = document.getElementById("content"); 
let isSidebarVisible = true;

toggleBtn.addEventListener("click", () => {
    isSidebarVisible = !isSidebarVisible;
    if (isSidebarVisible) {
        sidebar.classList.remove("hidden");
        toggleBtn.style.left = "260px";
        content.style.marginLeft = "270px"; 
        content.style.marginRight = "0"; 
        toggleBtn.textContent = "Hide";
    } else {
        sidebar.classList.add("hidden");
        toggleBtn.style.left = "10px";
        content.style.marginLeft = "auto"; 
        content.style.marginRight = "auto"; 
        toggleBtn.textContent = "Show";
    }
});

</script>

</body>
</html>
