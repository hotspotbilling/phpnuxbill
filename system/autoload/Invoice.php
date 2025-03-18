<?php

use Mpdf\Mpdf;

class Invoice
{
    public static function generateInvoice($invoiceData)
    {
        try {
            if (empty($invoiceData['invoice'])) {
                throw new Exception("Invoice ID is required");
            }

            $template = Lang::getNotifText('email_invoice');
            if (!$template) {
                throw new Exception("Invoice template not found");
            }

            if (strpos($template, '<body') === false) {
                $template = "<html><body>$template</body></html>";
            }

            $processedHtml = self::renderTemplate($template, $invoiceData);

            // Debugging: Save processed HTML to file for review
            // file_put_contents('debug_invoice.html', $processedHtml);

            // Generate PDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'helvetica',
                'orientation' => 'P',
            ]);

            $mpdf->SetDisplayMode('fullpage');
            $mpdf->SetProtection(['print']);
            $mpdf->shrink_tables_to_fit = 1;
            $mpdf->SetWatermarkText(strtoupper($invoiceData['status'] ?? 'UNPAID'), 0.15);
            $mpdf->showWatermarkText = true;
            $mpdf->WriteHTML($processedHtml);

            // Save PDF
            $filename = "invoice_{$invoiceData['invoice']}.pdf";
            $outputPath = "system/uploads/invoices/{$filename}";
            $mpdf->Output($outputPath, 'F');

            if (!file_exists($outputPath)) {
                throw new Exception("Failed to save PDF file");
            }

            return $filename;

        } catch (\Exception $e) {
            _log("Invoice generation failed: " . $e->getMessage());
            sendTelegram("Invoice generation failed: " . $e->getMessage());
            return false;
        }
    }

    private static function renderTemplate($template, $invoiceData)
    {
        return preg_replace_callback('/\[\[(\w+)\]\]/', function ($matches) use ($invoiceData) {
            $key = $matches[1];
            if (!isset($invoiceData[$key])) {
                _log("Missing invoice key: $key");
                return '';
            }

            if (in_array($key, ['created_at', 'due_date'])) {
                return date('F j, Y', strtotime($invoiceData[$key]));
            }

            if (in_array($key, ['amount', 'total', 'subtotal', 'tax'])) {
                return $invoiceData['currency_code'] . number_format((float) $invoiceData[$key], 2);
            }

            if ($key === 'bill_rows') {
                return html_entity_decode($invoiceData[$key]);
            }


            return htmlspecialchars($invoiceData[$key] ?? '');
        }, $template);
    }

    public static function sendInvoice($userId, $status = "Unpaid")
    {
        global $config, $root_path, $UPLOAD_PATH;

        if (empty($config['currency_code'])) {
            $config['currency_code'] = '$';
        }

        $account = ORM::for_table('tbl_customers')->find_one($userId);

        if (!$account) {
            _log("Failed to send invoice: User not found");
            sendTelegram("Failed to send invoice: User not found");
            return false;
        }

        $invoice = ORM::for_table("tbl_transactions")->where("username", $account->username)->find_one();

        if (!$invoice) {
            _log("Failed to send invoice: Transaction not found");
            sendTelegram("Failed to send invoice: Transaction not found");
            return false;
        }

        [$additionalBills, $add_cost] = User::getBills($account->id);

        $invoiceItems = [
            [
                'description' => $invoice->plan_name,
                'details' => 'Monthly Subscription',
                'amount' => (float) $invoice->price
            ]
        ];
        $subtotal = (float) $invoice->price;

        if ($add_cost > 0 && $invoice->routers != 'balance') {
            foreach ($additionalBills as $description => $amount) {
                if (is_numeric($amount)) {
                    $invoiceItems[] = [
                        'description' => $description,
                        'details' => 'Additional Bill',
                        'amount' => (float) $amount
                    ];
                    $subtotal += (float) $amount;
                } else {
                    _log("Invalid bill amount for {$description}: {$amount}");
                }
            }
        }

        $tax_rate = (float) ($config['tax_rate'] ?? 0);
        $tax = $config['enable_tax'] ? Package::tax($subtotal) : 0;
        $total = ($tax > 0) ? $subtotal + $tax : $subtotal + $tax;

        $token = User::generateToken($account->id, 1);
        if (!empty($token['token'])) {
            $tur = ORM::for_table('tbl_user_recharges')
                ->where('customer_id', $account->id)
                ->where('namebp', $invoice->plan_name);

            switch ($status) {
                case 'Paid':
                    $tur->where('status', 'on');
                    break;
                default:
                    $tur->where('status', 'off');
                    break;
            }
            $turResult = $tur->find_one();
            $payLink = $turResult ? '?_route=home&recharge=' . $turResult['id'] . '&uid=' . urlencode($token['token']) : '?_route=home';
        } else {
            $payLink = '?_route=home';
        }

        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        $logo = (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) ? $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png?' . time() : $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.default.png';
        $invoiceData = [
            'invoice' => "INV-" . Package::_raid(),
            'fullname' => $account->fullname,
            'email' => $account->email,
            'address' => $account->address,
            'phone' => $account->phonenumber,
            'bill_rows' => self::generateBillRows($invoiceItems, $config['currency_code'], $subtotal, $tax_rate, $tax, $total),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'due_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'currency' => $config['currency_code'],
            'company_address' => $config['address'],
            'company_name' => $config['CompanyName'],
            'company_phone' => $config['phone'],
            'logo' => $logo,
            'payment_link' => $payLink
        ];

        if (!isset($invoiceData['bill_rows']) || empty($invoiceData['bill_rows'])) {
            _log("Invoice Error: Bill rows data is empty.");
        }

        $filename = self::generateInvoice($invoiceData);

        if ($filename) {
            $pdfPath = "system/uploads/invoices/{$filename}";

            try {
                Message::sendEmail(
                    $account->email,
                    "Invoice for Account {$account->fullname}",
                    "Please find your invoice attached",
                    $pdfPath
                );
                return true;
            } catch (\Exception $e) {
                _log("Failed to send invoice email: " . $e->getMessage());
                sendTelegram("Failed to send invoice email: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    private static function generateBillRows($items, $currency, $subtotal, $tax_rate, $tax, $total)
    {
        $html = "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <thead>
                    <tr>
                        <th style='background: #3498db; color: white; padding: 12px; text-align: left;'>Description</th>
                        <th style='background: #3498db; color: white; padding: 12px; text-align: left;'>Details</th>
                        <th style='background: #3498db; color: white; padding: 12px; text-align: left;'>Amount</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($items as $item) {
            $html .= "<tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['description']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['details']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$currency}" . number_format((float) $item['amount'], 2) . "</td>
                  </tr>";
        }

        $html .= "<tr>
                <td colspan='2' style='text-align: right; padding: 10px; border-top: 2px solid #3498db;'>Subtotal:</td>
                <td style='padding: 10px; border-top: 2px solid #3498db;'>{$currency}" . number_format($subtotal, 2) . "</td>
              </tr>
              <tr>
                <td colspan='2' style='text-align: right; padding: 10px;'>TAX ({$tax_rate}%):</td>
                <td style='padding: 10px;'>{$currency}" . number_format($tax, 2) . "</td>
              </tr>
              <tr>
                <td colspan='2' style='text-align: right; padding: 10px; font-weight: bold;'>Total:</td>
                <td style='padding: 10px; font-weight: bold;'>{$currency}" . number_format($total, 2) . "</td>
              </tr>";

        $html .= "</tbody></table>";

        return $html;
    }



}
