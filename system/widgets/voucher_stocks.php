<?php

class voucher_stocks
{
    public function getWidget()
    {
        global $CACHE_PATH,$ui;
        $cacheStocksfile = $CACHE_PATH . File::pathFixer('/VoucherStocks.temp');
        $cachePlanfile = $CACHE_PATH . File::pathFixer('/VoucherPlans.temp');
        //Cache for 5 minutes
        if (file_exists($cacheStocksfile) && time() - filemtime($cacheStocksfile) < 600) {
            $stocks = json_decode(file_get_contents($cacheStocksfile), true);
            $plans = json_decode(file_get_contents($cachePlanfile), true);
        } else {
            // Count stock
            $tmp = $v = ORM::for_table('tbl_plans')->select('id')->select('name_plan')->find_many();
            $plans = array();
            $stocks = array("used" => 0, "unused" => 0);
            $n = 0;
            foreach ($tmp as $plan) {
                $unused = ORM::for_table('tbl_voucher')
                    ->where('id_plan', $plan['id'])
                    ->where('status', 0)->count();
                $used = ORM::for_table('tbl_voucher')
                    ->where('id_plan', $plan['id'])
                    ->where('status', 1)->count();
                if ($unused > 0 || $used > 0) {
                    $plans[$n]['name_plan'] = $plan['name_plan'];
                    $plans[$n]['unused'] = $unused;
                    $plans[$n]['used'] = $used;
                    $stocks["unused"] += $unused;
                    $stocks["used"] += $used;
                    $n++;
                }
            }
            file_put_contents($cacheStocksfile, json_encode($stocks));
            file_put_contents($cachePlanfile, json_encode($plans));
        }
        $ui->assign('stocks', $stocks);
        $ui->assign('plans', $plans);
        return $ui->fetch('widget/voucher_stocks.tpl');
    }
}