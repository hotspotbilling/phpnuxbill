<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *  Coupons Controller by https://t.me/focuslinkstech
 **/

_admin();
$ui->assign('_title', Lang::T('Coupons'));
$ui->assign('_system_menu', 'crm');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {

    case 'add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }
        $ui->assign('_title', Lang::T('Add Coupon'));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/coupons/add.tpl');
        break;

    case 'add-post':

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $code = Text::alphanumeric(_post('code', ''));
        $type = _post('type', '');
        $value = floatval(_post('value', ''));
        $description = _post('description', '');
        $max_usage = _post('max_usage', '0');
        $min_order_amount = _post('min_order_amount', '');
        $max_discount_amount = intval(_post('max_discount_amount', ''));
        $status = _post('status', 'active');
        $start_date = strtotime(_post('start_date', '0000-00-00'));
        $end_date = strtotime(_post('end_date', '0000-00-00'));

        $error = [];
        if (empty($code)) {
            $error[] = Lang::T('Coupon Code is required');
        }
        if (empty($type)) {
            $error[] = Lang::T('Coupon Type is required');
        }
        if (empty($value)) {
            $error[] = Lang::T('Coupon Value is required');
        }
        if (empty($description)) {
            $error[] = Lang::T('Coupon Description is required');
        }
        if ($max_usage < 0) {
            $error[] = Lang::T('Coupon Maximum Usage must be greater than or equal to 0');
        }
        if (empty($min_order_amount)) {
            $error[] = Lang::T('Coupon Minimum Order Amount is required');
        }
        if (empty($max_discount_amount)) {
            $error[] = Lang::T('Coupon Maximum Discount Amount is required');
        }
        if (empty($status)) {
            $error[] = Lang::T('Coupon Status is required');
        }
        if (empty($start_date)) {
            $error[] = Lang::T('Coupon Start Date is required');
        }
        if (empty($end_date)) {
            $error[] = Lang::T('Coupon End Date is required');
        }

        if (!empty($error)) {
            r2(getUrl('coupons/add'), 'e', implode('<br>', $error));
            exit;
        }

        //check if coupon code already exists
        $coupon = ORM::for_table('tbl_coupons')->where('code', $code)->find_one();
        if ($coupon) {
            r2(getUrl('coupons/add'), 'e', Lang::T('Coupon Code already exists'));
            exit;
        }

        $coupon = ORM::for_table('tbl_coupons')->create();
        $coupon->code = $code;
        $coupon->type = $type;
        $coupon->value = $value;
        $coupon->description = $description;
        $coupon->max_usage = $max_usage;
        $coupon->min_order_amount = $min_order_amount;
        $coupon->max_discount_amount = $max_discount_amount;
        $coupon->status = $status;
        $coupon->start_date = date('Y-m-d', $start_date);
        $coupon->end_date = date('Y-m-d', $end_date);
        $coupon->created_at = date('Y-m-d H:i:s');
        try {
            $coupon->save();
            r2(getUrl('coupons'), 's', Lang::T('Coupon has been added successfully'));
        } catch (Exception $e) {
            _log(Lang::T('Error adding coupon: ' . $e->getMessage()));
            r2(getUrl('coupons/add'), 'e', Lang::T('Error adding coupon: ' . $e->getMessage()));
        }
        break;

    case 'edit':

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }

        $coupon_id = intval($routes['2']);
        if (empty($coupon_id)) {
            r2(getUrl('coupons'), 'e', Lang::T('Invalid Coupon ID'));
            exit;
        }
        $coupon = ORM::for_table('tbl_coupons')->find_one($coupon_id);
        if (!$coupon) {
            r2(getUrl('coupons'), 'e', Lang::T('Coupon Not Found'));
            exit;
        }
        $ui->assign('coupon', $coupon);
        $ui->assign('_title', Lang::T('Edit Coupon: ' . $coupon['code']));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/coupons/edit.tpl');
        break;

    case 'edit-post':

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }

        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        $code = Text::alphanumeric(_post('code', ''));
        $type = _post('type', '');
        $value = floatval(_post('value', ''));
        $description = _post('description', '');
        $max_usage = _post('max_usage', '');
        $min_order_amount = _post('min_order_amount', '');
        $max_discount_amount = intval(_post('max_discount_amount', ''));
        $status = _post('status', 'active');
        $start_date = strtotime(_post('start_date', '0000-00-00'));
        $end_date = strtotime(_post('end_date', '0000-00-00'));

        $error = [];
        if (empty($code)) {
            $error[] = Lang::T('Coupon code is required');
        }
        if (empty($type)) {
            $error[] = Lang::T('Coupon type is required');
        }
        if (empty($value)) {
            $error[] = Lang::T('Coupon value is required');
        }
        if (empty($description)) {
            $error[] = Lang::T('Coupon description is required');
        }
        if ($max_usage < 0) {
            $error[] = Lang::T('Coupon Maximum Usage must be greater than or equal to 0');
        }
        if (empty($min_order_amount)) {
            $error[] = Lang::T('Coupon minimum order amount is required');
        }
        if (empty($max_discount_amount)) {
            $error[] = Lang::T('Coupon maximum discount amount is required');
        }
        if (empty($status)) {
            $error[] = Lang::T('Coupon status is required');
        }
        if (empty($start_date)) {
            $error[] = Lang::T('Coupon start date is required');
        }
        if (empty($end_date)) {
            $error[] = Lang::T('Coupon end date is required');
        }
        if (!empty($error)) {
            r2(getUrl('coupons/edit/') . $coupon_id, 'e', implode('<br>', $error));
            exit;
        }
        $coupon = ORM::for_table('tbl_coupons')->find_one($coupon_id);
        $coupon->code = $code;
        $coupon->type = $type;
        $coupon->value = $value;
        $coupon->description = $description;
        $coupon->max_usage = $max_usage;
        $coupon->min_order_amount = $min_order_amount;
        $coupon->max_discount_amount = $max_discount_amount;
        $coupon->status = $status;
        $coupon->start_date = date('Y-m-d', $start_date);
        $coupon->end_date = date('Y-m-d', $end_date);
        $coupon->updated_at = date('Y-m-d H:i:s');
        try {
            $coupon->save();
            r2(getUrl('coupons'), 's', Lang::T('Coupon has been updated successfully'));
        } catch (Exception $e) {
            _log(Lang::T('Error updating coupon: ') . $e->getMessage());
            r2(getUrl('coupons/edit/') . $coupon_id, 'e', Lang::T('Error updating coupon: ') . $e->getMessage());
        }
        break;

    case 'delete':

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $couponIds = json_decode($_POST['couponIds'], true);

            if (is_array($couponIds) && !empty($couponIds)) {
                // Delete coupons from the database
                ORM::for_table('tbl_coupons')
                    ->where_in('id', $couponIds)
                    ->delete_many();

                // Return success response
                echo json_encode(['status' => 'success', 'message' => Lang::T("Coupons Deleted Successfully.")]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid or missing coupon IDs.")]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => Lang::T("Invalid request method.")]);
        }
        break;

    case 'status':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $couponId = $_GET['coupon_id'] ?? '';
            $csrf_token =  $_GET['csrf_token'] ?? '';
            $status = $_GET['status'] ?? '';
            if (empty($couponId) || empty($csrf_token) || !Csrf::check($csrf_token) || empty($status)) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid request"));
                exit;
            }
            $coupon = ORM::for_table('tbl_coupons')->where('id', $couponId)->find_one();
            if (!$coupon) {
                r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Coupon not found."));
                exit;
            }
            $coupon->status = $status;
            $coupon->save();
            r2($_SERVER['HTTP_REFERER'], 's', Lang::T("Coupon status updated successfully."));
        } else {
            r2($_SERVER['HTTP_REFERER'], 'e', Lang::T("Invalid request method"));
        }
        break;

    default:
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Sales'])) {
            echo json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]);
            exit;
        }
        $ui->assign('_title', Lang::T('Coupons'));
        $ui->assign('_system_menu', 'crm');

        $search = _post('search');
        $filter = _post('filter', 'none');

        $couponsData = ORM::for_table('tbl_coupons')
            ->table_alias('c')
            ->select_many(
                'c.id',
                'c.code',
                'c.type',
                'c.value',
                'c.description',
                'c.max_usage',
                'c.usage_count',
                'c.status',
                'c.min_order_amount',
                'c.max_discount_amount',
                'c.start_date',
                'c.end_date',
                'c.created_at',
                'c.updated_at'
            );

        // Apply filters
        if ($search != '') {
            $searchLike = "%$search%";
            $couponsData->whereRaw(
                "code LIKE ? OR type LIKE ? OR value LIKE ? OR max_usage LIKE ? OR usage_count LIKE ? OR status LIKE ? OR min_order_amount LIKE ? OR max_discount_amount LIKE ?",
                [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike]
            );
        }
        $couponsData->order_by_asc('c.id');
        $coupons = Paginator::findMany($couponsData, ['search' => $search], 5, '');
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('coupons', $coupons);
        $ui->display('admin/coupons/list.tpl');
        break;
}
