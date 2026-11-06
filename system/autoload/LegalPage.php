<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/** Shows one of the admin-editable pages (pages/<file>.html) on its own public page, in the same look as the sign-in pages. */
class LegalPage
{
    public static function show($file, $title)
    {
        global $ui, $PAGES_PATH;
        // only a plain page name (letters, digits, underscore) ever reaches the path
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $file) || !is_file($PAGES_PATH . DIRECTORY_SEPARATOR . $file . '.html')) {
            $ui->assign('_title', Lang::T('Page not found'));
            $ui->display('customer/404.tpl');
            return;
        }
        $ui->assign('_title', Lang::T($title));
        $ui->assign('legal_title', Lang::T($title));
        $ui->assign('legal_file', $file);
        $ui->display('customer/legal.tpl');
    }
}
