<?php


class customer_expired
{


    public function getWidget()
    {
        global $ui, $current_date;

        //user expire
        $query = ORM::for_table('tbl_user_recharges')
        ->where_lte('expiration', $current_date)
        ->order_by_desc('expiration');
        $expire = Paginator::findMany($query);

        // Get the total count of expired records for pagination
        $totalCount = ORM::for_table('tbl_user_recharges')
        ->where_lte('expiration', $current_date)
        ->count();

        // Pass the total count and current page to the paginator
        $paginator['total_count'] = $totalCount;

        // Assign the pagination HTML to the template variable
        $ui->assign('expire', $expire);
        return $ui->fetch('widget/customer_expired.tpl');
    }
}
