<?php


class customer_expired
{


    public function getWidget()
    {
        global $ui, $current_date, $config;

        //user expire
        $query = ORM::for_table('tbl_user_recharges')
        ->table_alias('tur')
        ->selects([
            'c.id',
            'tur.username',
            'c.fullname',
            'c.phonenumber',
            'c.email',
            'tur.expiration',
            'tur.time',
            'tur.recharged_on',
            'tur.recharged_time',
            'tur.namebp',
            'tur.routers'
        ])
        ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
        ->where_lte('expiration', $current_date)
        ->order_by_desc('expiration');
        $expire = Paginator::findMany($query);

        // Get the total count of expired records for pagination
        $totalCount = ORM::for_table('tbl_user_recharges')
        ->where_lte('expiration', $current_date)
        ->count();

        // Pass the total count and current page to the paginator
        $paginator['total_count'] = $totalCount;

        if(!empty($_COOKIE['expdef']) && $_COOKIE['expdef'] != $config['customer_expired_expdef']) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'customer_expired_expdef')->find_one();
            if ($d) {
                $d->value = $_COOKIE['expdef'];
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'customer_expired_expdef';
                $d->value = $_COOKIE['expdef'];
                $d->save();
            }
        }
        if(!empty($config['customer_expired_expdef']) && empty($_COOKIE['expdef'])){
            $_COOKIE['expdef'] = $config['customer_expired_expdef'];
            setcookie('expdef', $config['customer_expired_expdef'], time() + (86400 * 30), "/");
        }

        // Assign the pagination HTML to the template variable
        $ui->assign('expire', $expire);
        $ui->assign('cookie', $_COOKIE);
        return $ui->fetch('widget/customer_expired.tpl');
    }
}
