<?php

use Mpdf\Mpdf;

class Invoice
{
    public static function generateInvoice($invoiceData)
    {
        try {
            if (empty($invoiceData['invoice'])) {
                throw new Exception(Lang::T("Invoice No is required"));
            }

            $template = Lang::getNotifText('email_invoice');
            if (!$template) {
                throw new Exception(Lang::T("Invoice template not found"));
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
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $mpdf->Output($outputPath, 'F');

            if (!file_exists($outputPath)) {
                throw new Exception(Lang::T("Failed to save PDF file"));
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
                _log(Lang::T("Missing invoice key: ") . $key);
                return '';
            }

            if (in_array($key, ['created_at', 'due_date'])) {
                return date('F j, Y', strtotime($invoiceData[$key]));
            }

            if (in_array($key, ['amount', 'total', 'subtotal', 'tax'])) {
                return $invoiceData['currency_code'] . number_format((float) $invoiceData[$key], 2);
            }

            if ($key === 'bill_rows') {
                return $invoiceData[$key];
            }

            return htmlspecialchars($invoiceData[$key] ?? '');
        }, $template);
    }

    /**
     * Send invoice to user
     *
     * @param int $userId
     * @param array $invoice
     * @param array $bills
     * @param string $status
     * @param string $invoiceNo
     * @return bool
     */

    public static function sendInvoice($userId, $invoice = null, $bills = [], $status = "Unpaid", $invoiceNo = null)
    {
        global $config, $root_path, $UPLOAD_PATH;

        // Set default currency code
        $config['currency_code'] ??= '$';

        $account = ORM::for_table('tbl_customers')->find_one($userId);
        self::validateAccount($account);
        if (!$invoiceNo) {
            $invoiceNo = "INV-" . Package::_raid();
        }
        // Fetch invoice if not provided
        if ($status === "Unpaid" && !$invoice) {
            $data = ORM::for_table('tbl_user_recharges')->where('customer_id', $userId)
                ->where('status', 'off')
                ->left_outer_join('tbl_plans', 'tbl_user_recharges.namebp = tbl_plans.name_plan')
                ->select('tbl_plans.price', 'price')
                ->select('tbl_plans.name_plan', 'namebp')
                ->find_one();
            if (!$data) {
                $data = ORM::for_table('tbl_user_recharges')->where('username', $account->username)
                    ->left_outer_join('tbl_plans', 'tbl_user_recharges.namebp = tbl_plans.name_plan')
                    ->select('tbl_plans.price', 'price')
                    ->select('tbl_plans.name_plan', 'namebp')
                    ->where('status', 'off')
                    ->find_one();
            }
            if (!$data) {
                throw new Exception(Lang::T("No unpaid invoice found for username:") . $account->username);
            }
            $invoice = [
                'price' => $data->price,
                'plan_name' => $data->namebp,
                'routers' => $data->routers,
            ];

        } else if ($status === "Paid" && !$invoice) {
            $invoice = ORM::for_table("tbl_transactions")->where("username", $account->username)->find_one();
        }
        if (!$invoice) {
            throw new Exception(Lang::T("Transaction not found for username: ") . $account->username);
        }

        // Get additional bills if not provided
        if (empty($bills)) {
            [$bills, $add_cost] = User::getBills($account->id);
        }

        $invoiceItems = self::generateInvoiceItems($invoice, $bills, $add_cost);
        $subtotal = array_sum(array_column($invoiceItems, 'amount'));
        $tax = $config['enable_tax'] ? Package::tax($subtotal) : 0;
        $tax_rate = $config['tax_rate'] ?? 0;
        $total = $subtotal + $tax;

        $payLink = self::generatePaymentLink($account, $invoice, $status);
        $logo = self::getCompanyLogo($UPLOAD_PATH, $root_path);

        $invoiceData = [
            'invoice' => $invoiceNo,
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

        if (empty($invoiceData['bill_rows'])) {
            throw new Exception(Lang::T("Bill rows data is empty."));
        }

        $filename = self::generateInvoice($invoiceData);
        if (!$filename) {
            throw new Exception(Lang::T("Failed to generate invoice PDF"));
        }

        $pdfPath = "system/uploads/invoices/{$filename}";
        self::saveToDatabase($filename, $account->id, $invoiceData, $total);

        try {
            Message::sendEmail(
                $account->email,
                Lang::T("Invoice for Account {$account->fullname}"),
                Lang::T("Please find your invoice attached"),
                $pdfPath
            );
            return true;
        } catch (\Exception $e) {
            throw new Exception(Lang::T("Failed to send email invoice to ") . $account->email . ". " . Lang::T("Reason: ") . $e->getMessage());
        }
    }

    private static function validateAccount($account)
    {
        if (!$account) {
            throw new Exception(Lang::T("User not found"));
        }
        if (!$account->email || !filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(Lang::T("Invalid user email"));
        }
    }

    private static function generateInvoiceItems($invoice, $bills, $add_cost)
    {
        $items = [
            [
                'description' => $invoice['plan_name'],
                'details' => Lang::T('Subscription'),
                'amount' => (float) $invoice['price']
            ]
        ];

        if ($invoice->routers != 'balance') {
            foreach ($bills as $description => $amount) {
                if (is_numeric($amount)) {
                    $items[] = [
                        'description' => $description,
                        'details' => Lang::T('Additional Bill'),
                        'amount' => (float) $amount
                    ];
                } else {
                    _log(Lang::T("Invalid bill amount for {$description}: {$amount}"));
                }
            }
        }
        return $items;
    }

    private static function generatePaymentLink($account, $invoice, $status)
    {
        $token = User::generateToken($account->id, 1);
        if (empty($token['token'])) {
            return '?_route=home';
        }

        $tur = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $account->id)
            ->where('namebp', $invoice->plan_name);

        $tur->where('status', $status === 'Paid' ? 'on' : 'off');
        $turResult = $tur->find_one();

        return $turResult ? '?_route=home&recharge=' . $turResult['id'] . '&uid=' . urlencode($token['token']) : '?_route=home';
    }

    private static function getCompanyLogo($UPLOAD_PATH, $root_path)
    {
        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        return file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png') ?
            $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png?' . time() :
            $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.default.png';
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
            $desc = htmlspecialchars($item['description'], ENT_QUOTES);
            $details = htmlspecialchars($item['details'], ENT_QUOTES);
            $html .= "<tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$desc}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$details}</td>
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

    private static function saveToDatabase($filename, $customer_id, $invoiceData, $total)
    {
        $invoice = ORM::for_table('tbl_invoices')->create();
        $invoice->number = $invoiceData['invoice'];
        $invoice->customer_id = $customer_id;
        $invoice->fullname = $invoiceData['fullname'];
        $invoice->email = $invoiceData['email'];
        $invoice->address = $invoiceData['address'];
        $invoice->status = $invoiceData['status'];
        $invoice->due_date = $invoiceData['due_date'];
        $invoice->filename = $filename;
        $invoice->amount = $total;
        $invoice->data = json_encode($invoiceData);
        $invoice->created_at = date('Y-m-d H:i:s');
        $invoice->save();
        return $invoice->id;
    }

    public static function getAll()
    {
        return ORM::for_table('tbl_invoices')->order_by_desc('id')->find_many();
    }
    public static function getById($id)
    {
        return ORM::for_table('tbl_invoices')->find_one($id);
    }
    public static function getByNumber($number)
    {
        return ORM::for_table('tbl_invoices')->where('number', $number)->find_one();
    }
    public static function delete($id)
    {
        $invoice = ORM::for_table('tbl_invoices')->find_one($id);
        if ($invoice) {
            $invoice->delete();
            return true;
        }
        return false;
    }

}
