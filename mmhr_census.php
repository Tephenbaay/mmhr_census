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

$selected_sheet_1 = $_GET['sheet_1'] ?? ($sheets[0] ?? '');
$selected_sheet_2 = $_GET['sheet_2'] ?? ($sheets[0] ?? '');
$selected_sheet_3 = $_GET['sheet_3'] ?? ($sheets[0] ?? '');

$query = "SELECT admission_date, discharge_date, member_category FROM patient_records 
          WHERE sheet_name = '$selected_sheet_1'
          AND MONTH(admission_date) != 2
          AND MONTH(discharge_date) != 2
          AND (MONTH(admission_date) = 1 OR MONTH(admission_date) = 12)";

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
        $discharge_day = (int)date('d', strtotime($row['date_discharge'])) - 1; 
        $category = strtolower($row['category']);

        if ($discharge_day >= 1 && $discharge_day <= 31) {
            if (strpos($category, 'non phic') !== false) {
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

        if (strpos($category, 'non phic') !== false) {
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

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">BMCI</a>
    </div>
</nav>

<aside>
    <div class="sidebar">
        <h2>Upload Excel File</h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit">Upload</button>

            <button onclick="printTable()" class="btn btn-success">Print Table</button>
        </form>
    </div>
</aside>

<div class="main-content" id="main-content">
            <div class="header-text">
                <div class="container">
                    <p>REPUBLIC OF THE PHILIPPINES</p>
                    <p>PHILIPPINE HEALTH INSURANCE CORPORATION</p>
                    <p>MANDATORY MONTHLY HOSPITAL REPORT</p>
                    <p>12/F City State Centre, 709 Shaw Blvd., Brgy. Oranbo, Pasig City</p>
                    <p>For the Month of JANUARY 2025</p>
                </div>
                <form>
                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">Accreditation No.:</label>
                        <input type="text" class="form-control" name="accreditation_no">
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">Region:</label>
                        <input type="text" class="form-control" name="region">
                    </div>
                </div>
            
                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">Name of Hospital:</label>
                        <input type="text" class="form-control" name="hospital_name">
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">Category:</label>
                        <input type="text" class="form-control" name="category">
                    </div>
                </div>
            
                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">Address No./Street:</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <label class="form-label me-2">PHIC Accredited Beds:</label>
                        <input type="text" class="form-control" name="phic_beds">
                    </div>
                </div>
            
                <div class="row mb-3">
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="form-label me-2">Municipality:</label>
                        <input type="text" class="form-control" name="municipality">
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="form-label me-2">DOH Authorized Beds:</label>
                        <input type="text" class="form-control" name="doh_beds">
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="form-label me-2">Province:</label>
                        <input type="text" class="form-control" name="province">
                    </div>
                </div>
            
                <div class="row mb-3">
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="form-label me-2">Zip Code:</label>
                        <input type="text" class="form-control" name="zip_code">
                    </div>
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
        function printContent() {
            window.print();
        }
    </script>

</body>
</html>
