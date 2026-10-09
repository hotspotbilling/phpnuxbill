# PHPNuxBill — Project Guidelines

> PHP Mikrotik Billing Management (Hotspot & PPPoE) with FreeRADIUS integration.
> Version: 2025.4.16 | Requires: PHP 8.2+, MySQL, GD2, cURL, ZIP

## Build & Run

```bash
# No build step — pure PHP app. Place public/ as web root.
# Cron (process expired users):
php /path/to/public/system/cron.php

# Cron (reminder notifications):
php /path/to/public/system/cron_reminder.php

# Update migration:
php /path/to/public/update.php
```

No linters, test suites, or CI/CD configured. No `package.json` or `npm`.

## Architecture

```
HTTP Request → index.php → system/boot.php → Router (?_route=controller/action)
                                     ↑
                                  init.php (autoloader, DB/Idiorm, lang, plugins, config)
```

### Bootstrap Chain

1. **`index.php`**: `session_start()`, captures Mikrotik GET params, requires `system/vendor/autoload.php` and `system/boot.php`
2. **`boot.php`** (via `system/boot.php`): Requires `init.php`, sets up **Smarty** (`$ui` global), parses `$_GET['_route']` into `$routes` array, includes `system/controllers/{$routes[0]}.php`
3. **`init.php`**: Custom `spl_autoload_register` (maps `Class` → `system/autoload/Class.php`), config, **Idiorm ORM** connection, language JSON, glob-includes all `system/plugin/*.php`, loads `tbl_appconfig` into `$config` global

### Key Directories

| Path | Purpose |
|---|---|
| `public/system/autoload/` | All classes — no namespaces, filename = class name |
| `public/system/controllers/` | Route handlers — `$routes[0]` maps to filename |
| `public/system/devices/` | Device drivers (Mikrotik, Radius, Voucher, Dummy) |
| `public/system/plugin/` | Plugins — auto-included at boot |
| `public/system/paymentgateway/` | Payment gateways — loaded on demand |
| `public/ui/ui/` | Default Smarty theme (admin/, customer/, sections/) |
| `public/ui/themes/` | Alternative themes |
| `public/ui/ui_custom/` | Template overrides (highest priority) |
| `public/install/` | Install wizard + `phpnuxbill.sql` + `radius.sql` |
| `paymentGateway/` | Gateway source files (symlink/copy to `system/paymentgateway/`) |
| `plugin/` | External plugin source files (symlink/copy to `system/plugin/`) |
| `mikrotik/` | Static Hotspot login pages served by RouterOS |

## Conventions

### PHP Patterns

- **No framework** — DIY procedural + class-based hybrid. No namespaces.
- **Autoloading**: Class `Admin` → `system/autoload/Admin.php`. Supports `PEAR2\Net\RouterOS` → `system/autoload/PEAR2/Net/RouterOS.php`
- **Database**: [Idiorm](https://idiorm.readthedocs.io/) ORM — fluent query builder. See `system/orm.php`. No migrations — schema via `install/phpnuxbill.sql`
- **Global state**: `$ui` (Smarty), `$config` (app config), `$admin` (user obj), `$routes` (URL segments), `$_L` (language), `$root_path` are globals — use `global $ui, $config;` in functions
- **Auth**: `_admin()` for admin pages, `_auth()` for customer pages — both set globals and enforce login
- **Routing**: URL pattern `?_route=controller/action/id`. Plugin routes: `?_route=plugin/functionName`
- **Redirect with message**: `r2(getUrl('path'), 's', Lang::T('Message'))` — 's'=success, 'e'=error, 'w'=warning
- **Safe input**: `_post('key')`, `_get('key')`, `_req('key')` (all with optional defaults)
- **i18n**: `Lang::T('English string')` — JSON files in `system/lan/`. Missing keys auto-added with Google Translate fallback
- **CSRF**: Use `Csrf::csrf()` in forms — controller should validate if data mutates
- **File header**:
  ```php
  /**
   *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
   *  by https://t.me/ibnux
   **/
  ```

### Smarty Templates

- Templates use `.tpl` extension with standard Smarty syntax
- Resolution order: `ui_custom/` → `themes/{theme}/` → `ui/ui/` → `plugin/ui/` (as `'plugin'` source) → `paymentgateway/ui/` (as `'pg'` source)
- Include pattern: `{include file="sections/header.tpl"}` / `{include file="sections/user-header.tpl"}`
- Config access: `{$_c['setting_key']}`
- Language: `{Lang::T('string')}`
- Controller pattern: `$ui->assign('_title', ...)`, `$ui->assign('_system_menu', ...)`, `$ui->display('admin/module/template.tpl')`

### Controller Pattern

```php
<?php
_admin();  // or _auth() for customer
$ui->assign('_title', Lang::T('Page Title'));
$ui->assign('_system_menu', 'module_name');
$action = $routes['1'] ?? 'list';

switch ($action) {
    case 'add':
        if (_post('save')) {
            // ORM save, then r2(getUrl('module'), 's', Lang::T('Saved'));
        }
        $ui->display('admin/module/add.tpl');
        break;
    case 'edit':
        $item = ORM::for_table('tbl_xxx')->find_one($routes['2']);
        // ...
        break;
    case 'delete':
        // Csrf check, delete, redirect
        break;
    default:
        $items = ORM::for_table('tbl_xxx')->find_many();
        $ui->assign('items', $items);
        $ui->display('admin/module/list.tpl');
}
```

## Plugin System

See [Plugin List](https://github.com/orgs/hotspotbilling/repositories?q=plugin)

Plugins are auto-included at boot: `glob($PLUGIN_PATH . '/*.php')`. Each file registers via:

```php
register_menu($name, $admin, $function, $position, $icon);   // Add menu item
register_hook('hook_name', 'function_name');                   // Hook lifecycle events
```

| Admin Menu Positions | Customer Menu Positions |
|---|---|
| `SETTINGS`, `AFTER_DASHBOARD`, `CUSTOMERS`, `PREPAID`, `SERVICES`, `REPORTS`, `VOUCHER`, `AFTER_ORDER`, `NETWORK`, `AFTER_PAYMENTGATEWAY` | `ORDER`, `HISTORY`, `ACCOUNTS` |

URL `?_route=plugin/functionName` → `call_user_func('functionName')`. Templates go in `system/plugin/ui/`.

## Payment Gateways

See [Payment Gateway List](https://github.com/orgs/hotspotbilling/repositories?q=payment+gateway)

Each gateway is a single `.php` file in `system/paymentgateway/` with prefixed functions:
`{gateway}_validate_config()`, `{gateway}_show_config()`, `{gateway}_save_config()`, `{gateway}_create_transaction($trx, $user)`, `{gateway}_get_status($trx, $user)`, `{gateway}_payment_notification()` (optional callback).

Config stored in `tbl_appconfig` key-value table. Templates in the gateway's `ui/` folder, accessed via `'pg'` Smarty source.

## Device Drivers

See `system/devices/readme.md` for the full interface.

Each device class (in `system/devices/`) implements: `description()`, `add_customer()`, `remove_customer()`, `change_username()`, `add_plan()`, `update_plan()`, `remove_plan()`, `online_customer()`, `connect_customer()`, `disconnect_customer()`.

Plans reference device via the `device` column. `Package::getDevice($plan)` resolves the class dynamically.

## Database

Key tables: `tbl_users`, `tbl_customers`, `tbl_plans`, `tbl_routers`, `tbl_pool`, `tbl_bandwidth`, `tbl_user_recharges`, `tbl_transactions`, `tbl_payment_gateway`, `tbl_appconfig`, `tbl_logs`, `tbl_widgets`, `tbl_customers_fields`.

Full schema: `install/phpnuxbill.sql`. Radius schema: `install/radius.sql`.

ORM using Idiorm : `system/orm.php`

## Pitfalls

- **No migrations** — schema changes directly in `.sql` files or ad-hoc in plugin code. Check `update.php` for version migrations.
- **Global state is pervasive** — always declare `global $ui, $config, $admin, $routes, $_L;` inside functions that use them.
- **`.htaccess` blocks direct PHP access** except `index.php`, `update.php`, `radius.php`. Never create publicly accessible PHP files without `.htaccess` whitelisting.
- **Payment gateways NOT auto-loaded** — they must be `include`d explicitly. Plugins ARE auto-loaded.
- **Template changes need BOTH** controller modification AND corresponding `.tpl` file updates.
- **Docker image uses PHP 7.4** but project requires 8.2+. Update `docker/Dockerfile` if using Docker.
- **No Composer autoload for app classes** — custom autoloader only; `composer.json` is metadata-only.
