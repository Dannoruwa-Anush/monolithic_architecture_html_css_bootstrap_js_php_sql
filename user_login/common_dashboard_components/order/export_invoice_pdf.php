<?php
ob_start(); // Prevent unwanted output

require_once('utils/libs/tcpdf/tcpdf.php');
include("setup/config.php");
include("db_connection.php");
include('queries/customerOrder_queries.php');

// Get Order ID
if (!isset($_GET['id'])) {
    die("Order ID is required.");
}
$order_id = $_GET['id'];
$orderDetails = getOrderByOrderId($mysqli_conn, $order_id);

if (!$orderDetails) {
    die("Order not found.");
}

// Create new PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Header
$pdf->SetFont('', 'B', 16);
$pdf->Cell(0, 10, 'ONLINE HARDWARE', 0, 1, 'C');

$pdf->SetFont('', '', 10);
$pdf->Cell(0, 6, 'Online Hardware, No 11, Colombo', 0, 1, 'C');
$pdf->Cell(0, 6, 'Tel: 011 111 2222', 0, 1, 'C');
$pdf->Ln(8);

// Invoice Info
$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 6, 'Invoice No: #' . $orderDetails['order_id'], 0, 1, 'L');

$pdf->Ln(3);
$pdf->SetFont('', '', 10);
$pdf->Cell(0, 6, 'Customer: ' . $orderDetails['user_name'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Address: ' . $orderDetails['user_address'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Telephone: ' . $orderDetails['user_telephone_no'], 0, 1, 'L');
$pdf->Ln(5);

// Table Header
$html = '
<table border="1" cellpadding="4">
<thead>
    <tr style="background-color:#f2f2f2;">
        <th><b>No</b></th>
        <th><b>Product Name</b></th>
        <th align="right"><b>Variant</b></th>
        <th align="right"><b>Unit Price</b></th>
        <th align="right"><b>Qty</b></th>
        <th align="right"><b>Subtotal</b></th>
    </tr>
</thead><tbody>';

$counter = 1;
foreach ($orderDetails['products'] as $item) {
    $html .= '<tr>
        <td>' . $counter++ . '</td>
        <td>' . htmlspecialchars($item['product_name']) . '</td>
        <td>' . htmlspecialchars($item['variant_name']) . '</td>
        <td align="right">' . number_format($item['product_price'], 2) . '</td>
        <td align="right">' . intval($item['quantity']) . '</td>
        <td align="right">' . number_format($item['sub_total_amount'], 2) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Ln(5);

// Total
$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 10, 'Total: Rs. ' . number_format($orderDetails['total_amount'], 2), 0, 1, 'R');

// Clean and output
ob_end_clean();
$pdf->Output('invoice_' . $orderDetails['order_id'] . '.pdf', 'I'); // Open in browser
exit;
