<?php
/**
 * Generate Receipt Page
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];
$pass_id = (int)($_GET['pass_id'] ?? 0);

if (!$pass_id) {
    redirect('/my_applications.php', 'Invalid request.', 'danger');
}

// Fetch pass, student, and payment details
$q = "SELECT bp.*, r.route_name, r.source, r.destination, 
             s.full_name, s.email, s.phone,
             p.transaction_id, p.payment_method, p.payment_date as payment_date_db
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      JOIN students s ON bp.student_id = s.id
      LEFT JOIN payments p ON bp.id = p.pass_id
      WHERE bp.id = ? AND bp.student_id = ?";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'ii', $pass_id, $student_id);
mysqli_stmt_execute($s);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$data || $data['payment_status'] !== 'Paid') {
    redirect('/my_applications.php', 'Receipt not available.', 'danger');
}

// Decode all database data
$data = decode_db_data($data);
$student = decode_db_data(getCurrentStudent());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $data['application_no']; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- jsPDF for Receipt Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="bi bi-receipt text-success me-2"></i>Payment Receipt</h4>
            <div class="btn-group">
                <button id="printReceiptBtn" class="btn btn-success">
                    <i class="bi bi-printer me-2"></i>Download PDF Receipt
                </button>
                <a href="<?php echo BASE_URL; ?>/my_applications.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Applications
                </a>
            </div>
        </div>

        <div class="card shadow-sm" id="receiptCard">
            <div class="card-body">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h2 class="text-success fw-bold"><?php echo APP_NAME; ?></h2>
                    <p class="text-muted">Email: <?php echo APP_EMAIL; ?> | Phone: <?php echo APP_PHONE; ?></p>
                    <hr>
                    <h4 class="fw-bold">PAYMENT RECEIPT</h4>
                    <p class="text-muted">Transaction ID: <span class="fw-bold"><?php echo decode_display($data['transaction_id']); ?></span></p>
                    <p class="text-muted">Date: <span class="fw-bold"><?php echo date('d M Y, h:i A', strtotime($data['payment_date_db'] ?? $data['payment_date'])); ?></span></p>
                </div>

                <!-- Student & Application Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-person me-2"></i>Student Details</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted">Name:</td><td class="fw-bold"><?php echo decode_display($data['full_name']); ?></td></tr>
                            <tr><td class="text-muted">Application No:</td><td class="fw-bold"><?php echo decode_display($data['application_no']); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-bus-front me-2"></i>Pass Details</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted">Pass Type:</td><td class="fw-bold"><?php echo decode_display($data['pass_type']); ?></td></tr>
                            <tr><td class="text-muted">Route:</td><td><?php echo decode_display($data['route_name']); ?></td></tr>
                            <tr><td class="text-muted">Source → Destination:</td><td><?php echo decode_display($data['source']); ?> → <?php echo decode_display($data['destination']); ?></td></tr>
                            <tr><td class="text-muted">Valid From:</td><td><?php echo date('d M Y', strtotime($data['valid_from'])); ?></td></tr>
                            <tr><td class="text-muted">Valid Until:</td><td><?php echo date('d M Y', strtotime($data['valid_until'])); ?></td></tr>
                            <tr><td class="text-muted">Payment Method:</td><td class="fw-bold"><?php echo decode_display($data['payment_method']); ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-success">Total Amount Paid: ₹<?php echo number_format($data['fee'], 2, '.', ','); ?></h3>
                </div>

                <!-- Footer & Useful Info -->
                <div class="text-center text-muted">
                    <hr>
                    <p><em>Thank you for your payment! This is an official receipt.</em></p>
                    
                    <div class="card mt-3 mb-3 bg-light border">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-info-circle"></i> Useful Information</h6>
                            <div class="row g-2">
                                <div class="col-md-6 text-start">
                                    <p class="mb-1 small"><i class="bi bi-arrow-clockwise"></i> <strong>Renew your pass</strong> before it expires from <a href="<?php echo BASE_URL; ?>/renew_pass.php" class="text-primary">here</a>.</p>
                                    <p class="mb-1 small"><i class="bi bi-files"></i> View all your applications <a href="<?php echo BASE_URL; ?>/my_applications.php" class="text-primary">here</a>.</p>
                                </div>
                                <div class="col-md-6 text-start">
                                    <p class="mb-1 small"><i class="bi bi-envelope"></i> For any queries, contact us at <a href="mailto:<?php echo APP_EMAIL; ?>" class="text-primary"><?php echo APP_EMAIL; ?></a>.</p>
                                    <p class="mb-0 small"><i class="bi bi-telephone"></i> Call us at <span class="fw-bold text-dark"><?php echo APP_PHONE; ?></span>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p><small>Generated on <?php echo date('d M Y, h:i A'); ?></small></p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        const receiptData = <?php 
            $receipt_array = [
                'studentName' => $data['full_name'],
                'applicationNo' => $data['application_no'],
                'transactionId' => $data['transaction_id'],
                'amount' => number_format($data['fee'], 2, '.', ''),
                'amountFormatted' => number_format($data['fee'], 2, '.', ','),
                'paymentMethod' => $data['payment_method'],
                'paymentDate' => date('d M Y, h:i A', strtotime($data['payment_date_db'] ?? $data['payment_date'])),
                'passType' => $data['pass_type'],
                'routeName' => $data['route_name'],
                'source' => $data['source'],
                'destination' => $data['destination'],
                'validFrom' => date('d M Y', strtotime($data['valid_from'])),
                'validUntil' => date('d M Y', strtotime($data['valid_until'])),
                'merchantName' => APP_NAME,
                'merchantEmail' => APP_EMAIL,
                'merchantPhone' => APP_PHONE
            ];
            echo json_encode($receipt_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); 
        ?>;

        document.getElementById('printReceiptBtn').addEventListener('click', generateReceipt);

        function generateReceipt() {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                doc.setProperties({
                    title: 'Payment Receipt - ' + receiptData.applicationNo,
                    subject: 'Bus Pass Payment Receipt',
                    author: receiptData.merchantName,
                    keywords: 'receipt, payment, bus pass'
                });
                
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(20);
                doc.setTextColor(22, 163, 74);
                doc.text(receiptData.merchantName, 105, 20, { align: 'center' });
                
                doc.setFontSize(10);
                doc.setTextColor(100);
                doc.text('Email: ' + receiptData.merchantEmail, 105, 28, { align: 'center' });
                doc.text('Phone: ' + receiptData.merchantPhone, 105, 34, { align: 'center' });
                
                doc.setDrawColor(200);
                doc.setLineWidth(0.5);
                doc.line(20, 40, 190, 40);
                
                doc.setFontSize(16);
                doc.setTextColor(0);
                doc.text('PAYMENT RECEIPT', 105, 50, { align: 'center' });
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Transaction ID: ' + receiptData.transactionId, 105, 58, { align: 'center' });
                doc.text('Date: ' + receiptData.paymentDate, 105, 64, { align: 'center' });
                
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(12);
                doc.text('Student Details', 20, 78);
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                doc.text('Name: ' + receiptData.studentName, 20, 86);
                doc.text('Application No: ' + receiptData.applicationNo, 20, 92);
                
                doc.setFont('helvetica', 'bold');
                doc.text('Itemized Breakdown', 20, 106);
                
                doc.setFont('helvetica', 'normal');
                const tableColumn = ['Description', 'Details'];
                const tableRows = [
                    ['Pass Type', receiptData.passType],
                    ['Route', receiptData.routeName],
                    ['Source', receiptData.source],
                    ['Destination', receiptData.destination],
                    ['Valid From', receiptData.validFrom],
                    ['Valid Until', receiptData.validUntil],
                    ['Payment Method', receiptData.paymentMethod]
                ];
                
                doc.autoTable({
                    head: [tableColumn],
                    body: tableRows,
                    startY: 112,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [22, 163, 74],
                        textColor: 255,
                        fontStyle: 'bold'
                    },
                    alternateRowStyles: {
                        fillColor: [240, 253, 244]
                    }
                });
                
                const finalY = doc.lastAutoTable.finalY + 10;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(14);
                doc.setTextColor(22, 163, 74);
                doc.text('Total Amount Paid: ₹' + (receiptData.amountFormatted || receiptData.amount), 105, finalY, { align: 'center' });
                
                doc.setFontSize(10);
                doc.setTextColor(150);
                doc.setFont('helvetica', 'italic');
                doc.text('Thank you for your payment! This is an official receipt.', 105, finalY + 15, { align: 'center' });
                doc.text('Generated on ' + receiptData.paymentDate, 105, finalY + 21, { align: 'center' });
                
                doc.save('Receipt_' + receiptData.applicationNo + '.pdf');
                
            } catch (error) {
                console.error('Receipt generation error:', error);
                alert('Sorry, there was an error generating your receipt. Please try again later.');
            }
        }
    </script>
</body>
</html>