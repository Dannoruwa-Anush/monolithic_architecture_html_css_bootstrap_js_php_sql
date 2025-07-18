<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once('utils/libs/tcpdf/tcpdf.php'); 
include("setup/config.php");
include("db_connection.php");
include('queries/customerOrder_queries.php');

// Get filter input
$report_type = $_GET['report_type'] ?? '';
$start_date = $end_date = null;

switch ($report_type) {
    case 'daily':
        $start_date = $end_date = $_GET['date'] ?? '';
        break;
    case 'weekly':
        $start_date = $_GET['start'] ?? '';
        $end_date = $_GET['end'] ?? '';
        break;
    case 'monthly':
        $start_date = ($_GET['month'] ?? '') . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        break;
    case 'yearly':
        $year = $_GET['year'] ?? '';
        $start_date = "$year-01-01";
        $end_date = "$year-12-31";
        break;
    default:
        die("Invalid report type.");
}

// Fetch report data
$data = getDeliveredProductReport($mysqli_conn, $start_date, $end_date);

// Initialize TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Header Section
$pdf->SetFont('', 'B', 16);
$pdf->Cell(0, 8, 'ONLINE HARDWARE', 0, 1, 'C');

$pdf->SetFont('', '', 10);
$pdf->Cell(0, 6, 'Online Hardware, No 11, Colombo', 0, 1, 'C');
$pdf->Cell(0, 6, 'Tel: 011 111 2222', 0, 1, 'C');
$pdf->Ln(5);

// Title Section
$pdf->SetFont('', 'B', 14);
$pdf->Cell(0, 10, 'Delivered Orders Report', 0, 1, 'C');

$pdf->SetFont('', '', 10);
$pdf->Cell(0, 8, "From $start_date to $end_date", 0, 1, 'C');
$pdf->Ln(5);

// Build Table HTML
$tbl = '<table border="1" cellpadding="4">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th><b>Product Name</b></th>
            <th>Variant</th>
            <th><b>Category</b></th>
            <th><b>Subcategory</b></th>
            <th align="right"><b>Unit Price</b></th>
            <th align="right"><b>Qty</b></th>
            <th align="right"><b>SubTotal</b></th>
        </tr>
    </thead><tbody>';

$totalAmount = 0;

foreach ($data as $row) {
    $unit_price = number_format($row['unit_price'], 2);
    $qty = (int)$row['qty'];
    $subtotal = number_format($row['sub_total'], 2);
    $totalAmount += $row['sub_total'];

    $tbl .= '<tr>
        <td>' . htmlspecialchars($row['product_name']) . '</td>
        <td>' . htmlspecialchars($row['variant_name']) . '</td>
        <td>' . htmlspecialchars($row['category_name']) . '</td>
        <td>' . htmlspecialchars($row['subcategory_name']) . '</td>
        <td align="right">' . $unit_price . '</td>
        <td align="right">' . $qty . '</td>
        <td align="right">' . $subtotal . '</td>
    </tr>';
}

$tbl .= '</tbody></table>';

// Output table
$pdf->writeHTML($tbl, true, false, false, false, '');

// Add total
$pdf->Ln(5);
$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 10, 'Total: Rs. ' . number_format($totalAmount, 2), 0, 1, 'R');

// Clean the output buffer to prevent header issues
ob_end_clean();

// Output PDF
$pdf->Output('delivered_orders_report.pdf', 'I'); // 'I' for inline viewing
exit;
?>
