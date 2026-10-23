<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
require __DIR__ . '/system/vendor/autoload.php';
$smarty = new Smarty();
$smarty->setTemplateDir([__DIR__ . '/ui/ui_custom/', __DIR__ . '/ui/ui/']);
$smarty->setCompileDir(sys_get_temp_dir() . '/phnuxbill_smarty_test');
$smarty->setCacheDir(sys_get_temp_dir() . '/phnuxbill_smarty_cache');
$templates = [
    'customer/header.tpl',
    'customer/footer.tpl',
    'customer/dashboard.tpl',
    'admin/header.tpl',
    'admin/footer.tpl',
    'widget/customers/account_info.tpl',
    'widget/customers/active_internet_plan.tpl',
    'widget/customers/unpaid_order.tpl',
];
$fail = 0;
foreach ($templates as $t) {
    try {
        $tpl = $smarty->createTemplate($t);
        $tpl->compileTemplateSource();
        echo "OK    $t\n";
    } catch (Throwable $e) {
        $fail++;
        echo "FAIL  $t : " . $e->getMessage() . "\n";
    }
}
exit($fail > 0 ? 1 : 0);
