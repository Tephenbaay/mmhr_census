<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sheets_query = "SELECT DISTINCT sheet_name FROM patient_records";
$sheets_result = $conn->query($sheets_query);
$sheets = [];
while ($row = $sheets_result->fetch_assoc()) {
    $sheets[] = $row['sheet_name'];
}

$sheets_query_2 = "SELECT DISTINCT sheet_name_2 FROM patient_records_2";
$sheets_result_2 = $conn->query($sheets_query_2);
$sheets_2 = [];
while ($row = $sheets_result_2->fetch_assoc()) {
    $sheets_2[] = $row['sheet_name_2'];
}

$sheets_query_3 = "SELECT DISTINCT sheet_name_3 FROM patient_records_3";
$sheets_result_3 = $conn->query($sheets_query_3);
$sheets_3 = [];
while ($row = $sheets_result_3->fetch_assoc()) {
    $sheets_3[] = $row['sheet_name_3'];
}

$selected_sheet_1 = isset($_GET['sheet_1']) ? $_GET['sheet_1'] : '';
$selected_sheet_2 = isset($_GET['sheet_2']) ? $_GET['sheet_2'] : '';
$selected_sheet_3 = isset($_GET['sheet_3']) ? $_GET['sheet_3'] : '';

$query = "SELECT admission_date, discharge_date, member_category FROM patient_records 
          WHERE LOWER(sheet_name) = LOWER('$selected_sheet_1')";

$result = $conn->query($query);

$summary = array_fill(1, 31, [
    'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
    'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0,
    'nhip' => 0, 'non_nhip' => 0, 'total_admissions' => 0, 'total_discharges_nhip' => 0,
    'total_discharges_non_nhip' => 0,'lohs_nhip' => 0, 'lohs_non_nhip' => 0
]);

while ($row = $result->fetch_assoc()) {
    $admit = new DateTime($row['admission_date']);
    $discharge = new DateTime($row['discharge_date']);
    $category = strtolower($row['member_category']);

    $startDay = max(1, ($admit->format('m') == '12') ? 1 : (int) $admit->format('d'));
    $endDay = min(31, (int) $discharge->format('d') - 1);

    if ($startDay <= 31 && $endDay >= 1) {
        for ($day = $startDay; $day <= $endDay; $day++) {
            if (strpos($category, 'formal-government') !== false || strpos($category, 'sponsored- local govt unit') !== false) {
                $summary[$day]['govt'] += 1;
            } elseif (strpos($category, 'formal-private') !== false) {
                $summary[$day]['private'] += 1;
            } elseif (strpos($category, 'self earning individual') !== false || strpos($category, 'indirect contributor') !== false
              || strpos($category, 'informal economy- informal sector') !== false) {
                $summary[$day]['self_employed'] += 1;
            } elseif (strpos($category, 'ofw') !== false) {
                $summary[$day]['ofw'] += 1;
            } elseif (strpos($category, 'migrant worker') !== false) {
                $summary[$day]['owwa'] += 1;
            } elseif (strpos($category, 'senior citizen') !== false) {
                $summary[$day]['sc'] += 1;
            } elseif (strpos($category, 'pwd') !== false) {
                $summary[$day]['pwd'] += 1;
            } elseif (strpos($category, 'indigent') !== false || strpos($category, 'sponsored- pos financially incapable') !== false
              || strpos($category, '4ps/mcct') !== false) {
                $summary[$day]['indigent'] += 1;
            } elseif (strpos($category, 'lifetime member') !== false) {
                $summary[$day]['pensioners'] += 1;
            }
        }
    }
    
    foreach ($summary as $day => $row) {
        $summary[$day]['nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }    

    foreach ($summary as $day => $row) {
        $summary[$day]['lohs_nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }  
}
    $admission_query = "SELECT admission_date_2 FROM patient_records_2 WHERE sheet_name_2 = '$selected_sheet_2'";
    $admission_result = $conn->query($admission_query);

    while ($row = $admission_result->fetch_assoc()) {
        $admit_day = (int)date('d', strtotime($row['admission_date_2']));

        if ($admit_day >= 1 && $admit_day <= 31) {
            $summary[$admit_day]['total_admissions'] += 1;
        }
    }

    $discharge_query = "SELECT date_discharge, category FROM patient_records_3 WHERE sheet_name_3 = '$selected_sheet_3'";
    $discharge_result = $conn->query($discharge_query);

    while ($row = $discharge_result->fetch_assoc()) {
        $discharge_day = (int)date('d', strtotime($row['date_discharge'])); 
        $category = strtolower($row['category']);

        if ($discharge_day >= 1 && $discharge_day <= 31) {
            if (!isset($summary[$discharge_day])) {
                $summary[$discharge_day] = [
                    'total_discharges_non_nhip' => 0,
                    'total_discharges_nhip' => 0
                ];
            }
            if (strpos($category, 'n/a') !== false || strpos($category, 'non phic') !== false || strpos($category, '#n/a') !== false) {
                $summary[$discharge_day]['total_discharges_non_nhip'] += 1;
            } else {
                $summary[$discharge_day]['total_discharges_nhip'] += 1;
            }
        }
    }

    $non_nhip_query = "SELECT date_admitted, date_discharge, category FROM patient_records_3 WHERE sheet_name_3 = '$selected_sheet_3'";
    $non_nhip_result = $conn->query($non_nhip_query);
    while ($row = $non_nhip_result->fetch_assoc()) {
        $admit = new DateTime($row['date_admitted']);
        $discharge = new DateTime($row['date_discharge']);
        $category = strtolower($row['category']);

        if (strpos($category, 'n/a') !== false || strpos($category, 'non phic') !== false || strpos($category, '#n/a') !== false) {
            $startDay = max(1, (int) $admit->format('d'));
            $endDay = min(31, (int) $discharge->format('d') - 1);

            if ($startDay <= 31 && $endDay >= 1) {
                for ($day = $startDay; $day <= $endDay; $day++) {
                    $summary[$day]['non_nhip'] += 1;
                }
            }
        }
    }
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sige\mmhr.css">
    <style>
    </style>
</head>
<body class="container mt-4">

<nav class="navbar" style="background-color: #6ba172;">
    <div class="container-fluid">
        <div class="navb">
            <img src="sige/download-removebg-preview.png" alt="icon">
            <a class="navbar-brand" href="dashboard.php">BicutanMed</a>
        </div>
    </div>
</nav>

<aside>
    <div class="sidebar" id="sidebar" style="background-color: #6ba172;">
        <h2>Upload Excel File</h2>
        <form action="display_summary.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit">Upload</button>

            <button type="button" onclick="printTable()" class="btn btn-success">Print Table</button>

            <form action="display_summary.php" method="GET">
            <button type="submit" class="btn btn-primary mt-3">View MMHR Table</button>
        </form>
        </form>
    </div>
</aside>
<button class="toggle-btn" id="toggleBtn" style="background-color: #6ba172;">Hide</button>

<div class="main-content" id="main-content">
            <div class="header-text">
            <form method="GET" class="mb-3">
            <div class="sige">
                <label class="col2-5"></label>
                <select name="sheet_1" class="form-select mb-2" onchange="this.form.submit()">
                    <option value="" disabled <?php echo empty($selected_sheet_1) ? 'selected' : ''; ?>>Select Sheet</option>
                    <?php foreach ($sheets as $sheet) { ?>
                        <option value="<?php echo htmlspecialchars($sheet); ?>" <?php echo ($sheet == $selected_sheet_1) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sheet); ?>
                        </option>
                    <?php } ?>
                </select>

                <label class="col7"></label>
                <select name="sheet_2" class="form-select mb-2" onchange="this.form.submit()">
                    <option value="" disabled <?php echo empty($selected_sheet_2) ? 'selected' : ''; ?>>Select Admission Sheet</option>
                    <?php foreach ($sheets_2 as $sheet) { ?>
                        <option value="<?php echo htmlspecialchars($sheet); ?>" <?php echo ($sheet == $selected_sheet_2) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sheet); ?>
                        </option>
                    <?php } ?>
                </select>

                <label class="col8"></label>
                <select name="sheet_3" class="form-select mb-2" onchange="this.form.submit()">
                    <option value="" disabled <?php echo empty($selected_sheet_3) ? 'selected' : ''; ?>>Select Discharge Sheet</option>
                    <?php foreach ($sheets_3 as $sheet) { ?>
                        <option value="<?php echo htmlspecialchars($sheet); ?>" <?php echo ($sheet == $selected_sheet_3) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sheet); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </form>

                <h2 class="text-center">MMHR Census</h2>
                <div class="table-container">
                    <div class="col-md-6">
                    <p>A. DAILY CENSUS OF NHIP PATIENTS</p>
                    <p class="text-center"><b>(EVERY 12:00MN.)</b></p>
                        <table class="table table-bordered text-center" style="font-size: 10px;">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2">DATE</th>
                                    <th colspan="3">CENSUS</th> 
                                </tr>
                                <tr>
                                    <th>NHIP</th>
                                    <th>NON-NHIP</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totals = ['nhip' => 0, 'non_nhip' => 0, 'total' => 0];
                                for ($i = 1; $i <= 31; $i++) { 
                                    $nhip = $summary[$i]['nhip'] ?? 0;
                                    $non_nhip = $summary[$i]['non_nhip'] ?? 0;
                                    $total = $nhip + $non_nhip;
            
                                    $totals['nhip']  += $nhip;
                                    $totals['non_nhip'] += $non_nhip;
                                    $totals['total'] += $total;
                                ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo $nhip; ?></td>
                                        <td><?php echo $non_nhip; ?></td>
                                        <td><?php echo $total; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr class="fw-bold text-center">
                                    <td colspan="4">*** NOTHING FOLLOWS ***</td>
                                </tr>
                                <tr class="table-dark fw-bold">
                                    <td>Total</td>
                                    <td><?php echo $totals['nhip']; ?></td>
                                    <td><?php echo $totals['non_nhip']; ?></td>
                                    <td><?php echo $totals['total']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
            
                    <div class="col-md-6">
                        <p>CENSUS FOR THE DAY=CENSUS OF THE PREVIOUS DAY PLUS THE ADMISSION OF THE DAY</p>
                        <p class="text-center"><b>minus DISCHARGES of the day.</b></p>
                        <table class="table table-bordered text-center" style="font-size: 10px;">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2">DATE</th>
                                    <th colspan="3">DISCHARGES</th>
                                </tr>
                                <tr>
                                    <th>NHIP</th>
                                    <th>NON-NHIP</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totals_discharge = ['nhip' => 0, 'non_nhip' => 0, 'total' => 0];
                                for ($i = 1; $i <= 31; $i++) { 
                                    $nhip = $summary[$i]['total_discharges_nhip'] ?? 0;
                                    $non_nhip = $summary[$i]['total_discharges_non_nhip'] ?? 0;
                                    $total = $nhip + $non_nhip;
            
                                    $totals_discharge['nhip'] += $nhip;
                                    $totals_discharge['non_nhip'] += $non_nhip;
                                    $totals_discharge['total'] += $total;
                                ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo $nhip; ?></td>
                                        <td><?php echo $non_nhip; ?></td>
                                        <td><?php echo $total; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr class="fw-bold text-center">
                                    <td colspan="4">*** NOTHING FOLLOWS ***</td>
                                </tr>
                                <tr class="table-dark fw-bold">
                                    <td>Total</td>
                                    <td><?php echo $totals_discharge['nhip']; ?></td>
                                    <td><?php echo $totals_discharge['non_nhip']; ?></td>
                                    <td><?php echo $totals_discharge['total']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            
        </div>
    </div>

<script>

function printTable() {
    const printContents = document.getElementById("main-content").innerHTML; 
    const originalContents = document.body.innerHTML;

    const printStyles = `
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid black;
                padding: 8px;
                text-align: center;
            }
            .header-text {
                text-align: center;
                margin-bottom: 20px;
            }
        </style>
    `;

    document.body.innerHTML = `
        ${printStyles}
        <div class="header-text">
            <h2>Mandatory Monthly Hospital Report</h2>
        </div>
        ${printContents}
    `;

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
