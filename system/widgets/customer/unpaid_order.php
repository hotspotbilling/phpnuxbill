<?php


class unpaid_order
{
    public function getWidget()
    {
        global $ui, $user;
        $unpaid = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();

        // check expired payments
        if ($unpaid) {
            try {
                if (strtotime($unpaid['expired_date']) < time()) {
                    $unpaid->status = 4;
                    $unpaid->save();
                    $unpaid = [];
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                if (strtotime($unpaid['created_date'], "+24 HOUR") < time()) {
                    $unpaid->status = 4;
                    $unpaid->save();
                    $unpaid = [];
                }
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
        }

        $ui->assign('unpaid', $unpaid);
        return $ui->fetch('widget/customers/unpaid_order.tpl');
    }
}
