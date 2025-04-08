<?php
// Advanced usage with custom parameters
try {
    $userId = 8; // Customer ID
    $status = "Unpaid";
    $invoiceNo = "INV-2023-00987";

    // Manually provide invoice data
    $invoiceData = [
        'plan_name' => 'Premium Plan',
        'price' => 49.99,
        'routers' => 'router123',
        // Add other required fields from tbl_transactions
    ];

    // Custom bills
    $bills = [
        'Additional Bandwidth' => 15.00,
        'Support Fee' => 10.00,
        'IP Address' => 5.00,
        'Custom Service' => 25.00,
        'Late Fee' => 5.00,
        'Discount' => -10.00,
    ];

    $add_cost = 20;

    $result = Invoice::sendInvoice(
        userId: $userId,
        invoice: $invoiceData,
        bills: $bills,
        status: $status,
        invoiceNo: $invoiceNo
    );

    if($result) {
        echo "Custom invoice sent! PDF generated at: system/uploads/invoices/invoice_{$invoiceNo}.pdf";
    }
} catch (Exception $e) {
    echo "Invoice Error: " . $e->getMessage();
}
