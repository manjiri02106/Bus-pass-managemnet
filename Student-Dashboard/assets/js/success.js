// =============================================
// Payment Success Page JavaScript
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    // 1. Show loading screen for 2-3 seconds
    const loadingScreen = document.getElementById('loading-screen');
    const successContent = document.getElementById('success-content');
    const printReceiptBtn = document.getElementById('printReceiptBtn');
    
    setTimeout(() => {
        // Hide loading screen with fade out
        loadingScreen.style.opacity = '0';
        
        setTimeout(() => {
            loadingScreen.style.display = 'none';
            successContent.classList.remove('hidden');
            
            // Trigger confetti
            createConfetti();
            
            // Start countdown to redirect
            startCountdown();
            
            // Add print receipt button click listener
            if (printReceiptBtn) {
                printReceiptBtn.addEventListener('click', generateReceipt);
            }
        }, 500);
    }, 2500); // 2.5 seconds loading time
});

// =============================================
// Receipt Generation with jsPDF
// =============================================
function generateReceipt() {
    try {
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Set document properties
        doc.setProperties({
            title: 'Payment Receipt - ' + receiptData.applicationNo,
            subject: 'Bus Pass Payment Receipt',
            author: receiptData.merchantName,
            keywords: 'receipt, payment, bus pass'
        });
        
        // --------------------------
        // Header Section
        // --------------------------
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(20);
        doc.setTextColor(22, 163, 74); // Success green
        doc.text(receiptData.merchantName, 105, 20, { align: 'center' });
        
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('Email: ' + receiptData.merchantEmail, 105, 28, { align: 'center' });
        doc.text('Phone: ' + receiptData.merchantPhone, 105, 34, { align: 'center' });
        
        // Divider line
        doc.setDrawColor(200);
        doc.setLineWidth(0.5);
        doc.line(20, 40, 190, 40);
        
        // --------------------------
        // Receipt Title & Transaction ID
        // --------------------------
        doc.setFontSize(16);
        doc.setTextColor(0);
        doc.text('PAYMENT RECEIPT', 105, 50, { align: 'center' });
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Transaction ID: ' + receiptData.transactionId, 105, 58, { align: 'center' });
        doc.text('Date: ' + receiptData.paymentDate, 105, 64, { align: 'center' });
        
        // --------------------------
        // Student & Application Details
        // --------------------------
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text('Student Details', 20, 78);
        
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text('Name: ' + receiptData.studentName, 20, 86);
        doc.text('Application No: ' + receiptData.applicationNo, 20, 92);
        
        // --------------------------
        // Itemized Breakdown Table
        // --------------------------
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
        
        // --------------------------
        // Total Amount Section
        // --------------------------
        const finalY = doc.lastAutoTable.finalY + 10;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(22, 163, 74);
        doc.text('Total Amount Paid: ₹' + (receiptData.amountFormatted || receiptData.amount), 105, finalY, { align: 'center' });
        
        // --------------------------
        // Footer Section
        // --------------------------
        doc.setFontSize(10);
        doc.setTextColor(150);
        doc.setFont('helvetica', 'italic');
        doc.text('Thank you for your payment! This is an official receipt.', 105, finalY + 15, { align: 'center' });
        doc.text('Generated on ' + receiptData.paymentDate, 105, finalY + 21, { align: 'center' });
        
        // --------------------------
        // Save the PDF
        // --------------------------
        doc.save('Receipt_' + receiptData.applicationNo + '.pdf');
        
    } catch (error) {
        // Error handling: Show user-friendly alert
        console.error('Receipt generation error:', error);
        alert('Sorry, there was an error generating your receipt. Please try again later.');
    }
}

// =============================================
// Confetti Animation
// =============================================
function createConfetti() {
    const container = document.getElementById('confetti-container');
    const colors = ['#22c55e', '#16a34a', '#15803d', '#f0fdf4', '#86efac', '#bbf7d0'];
    const confettiCount = 150;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.classList.add('confetti');
        
        // Random properties
        confetti.style.left = `${Math.random() * 100}%`;
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = `${Math.random() * 10 + 5}px`;
        confetti.style.height = `${Math.random() * 10 + 5}px`;
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        confetti.style.animationDuration = `${Math.random() * 3 + 2}s`;
        confetti.style.animationDelay = `${Math.random() * 2}s`;
        
        container.appendChild(confetti);
        
        // Remove confetti after animation finishes
        setTimeout(() => {
            confetti.remove();
        }, 5000); // 5 seconds total confetti time
    }
}

// =============================================
// Countdown to Dashboard Redirect
// =============================================
function startCountdown() {
    let countdown = 12;
    const countdownElement = document.getElementById('countdown');
    
    const interval = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(interval);
            // Redirect to dashboard
            window.location.href = BASE_URL + '/dashboard.php';
        }
    }, 1000);
}
