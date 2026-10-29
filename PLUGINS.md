# Writing plugins for PHPNuxBill

A plugin is PHP that the core loads on every request: it adds menu pages, reacts to hooks, receives webhooks and keeps
its own settings. This guide is the contract between the core and a plugin. A ready-to-copy starter lives in
`skeletons/plugin/` (`php skeletons/build.php new plugin acme_sms "Acme SMS"`).

## Two shapes

| | Folder plugin (recommended) | Single-file plugin (older) |
|---|---|---|
| Where | `system/plugin/<slug>/` with a `plugin.json` | `system/plugin/<slug>.php` |
| Menus | `"menus"` in `plugin.json` | `register_menu()` / `register_submenu()` |
| Routes | `"routes"` in `plugin.json` | `register_route()` |
| Templates | `ui/` inside the folder | `system/plugin/ui/` |
| Install / uninstall code | `install.php`, `uninstall.php` | none |

Both load the same way and both can use every API below.

## How a request reaches a plugin

`?_route=plugin/<function>` (or `/plugin/<function>` with the rewrite rules) runs the PHP function `<function>`. What
comes after it arrives in `$routes[2]`, `$routes[3]` ...: `plugin/myplugin/settings/5` runs `myplugin()` with
`$routes[2] = 'settings'` and `$routes[3] = '5'`.

The core only runs a function when **all** of these are true:

1. it is defined by a file under `system/plugin/` (never a PHP built-in or a core function),
2. it needs no arguments,
3. the plugin has **declared** it (below), when the site is in *enforce* mode. In *warn* mode (today's default) an
   undeclared plugin function still runs and is logged; see [Warn mode and enforce mode](#warn-mode-and-enforce-mode).

A function that **is** declared as an `admin` or `customer` route gets its sign-in check in both modes.

## Declaring what a URL may call

Three things count as declared:

**1. A menu function.** Anything you register as a menu or submenu. `"function": "myplugin/settings"` declares
`myplugin`. The function is responsible for its own sign-in check (`_admin()` for staff, `_auth()` for customers),
because one function can serve several pages.

**2. A route.** For everything that is not a menu: a webhook, a payment return page, an AJAX endpoint, your own login,
your own API.

Folder plugin, in `plugin.json`:

```json
"routes": [
  { "function": "acme_sms_webhook", "access": "public" },
  { "function": "acme_sms_send_test", "access": "admin" },
  { "function": "acme_sms_balance_customer", "access": "customer" }
]
```

Single-file plugin, next to your `register_menu()` calls:

```php
register_route('acme_sms_webhook', 'public');
register_route('acme_sms_send_test', 'admin');
```

**3. Access levels.** The core checks these *before* your function runs:

| `access` | Who gets through | Use it for |
|---|---|---|
| `admin` (default) | a signed-in staff member; anyone else is sent to the admin login | admin pages and AJAX |
| `customer` | a signed-in customer | customer pages |
| `public` | anyone | webhooks, payment callbacks, your own API or portal |

`public` means *you* check: verify a signature (HMAC), a secret in the URL, or your own session. Never trust a
`public` route's input: it is typed by a stranger. A route you forget to give an `access` is `admin`, the safe choice.

## Warn mode and enforce mode

Settings > General Settings > Security tab > *Plugin Routes*, stored as `plugin_routes_mode`:

* **warn** (the default today): a plugin function that nothing declares still runs, and the core writes one line to
  the system log a day: `Plugin route 'x' is not declared ...`. Nothing breaks while plugins are updated.
* **enforce**: an undeclared function is refused ("Function not found"). On a non-live site the message names the
  function and how to declare it.

The default will move to *enforce* in a later release. To prepare a plugin: declare its routes, run the check below, set
the mode to *enforce* on a test site and click through every page and webhook.

## Checking a plugin

```bash
php skeletons/build.php check plugin system/plugin/acme_sms
```

It reads the plugin's PHP, templates and scripts for `plugin/<function>` addresses, compares them with the declared
menus and routes, and prints what is missing, ready to paste into `plugin.json`. It exits with 1 when something
needs declaring, so it can run in a plugin's own CI.

## Updating an older plugin: three steps

1. Add `register_route()` calls (or a `"routes"` list) for every webhook, callback and AJAX function.
2. Run the check command until it prints *nothing to declare*.
3. Remove your own `_admin()` check from a function you declared `admin` if you like; the core does it, doing it twice
   is harmless.

## Publishing and serving updates

How an admin learns your plugin has a new version, and what happens when they press **Update** in Marketplace.

### 1. Put a version in `plugin.json`

```json
{ "name": "Acme SMS", "slug": "acme_sms", "version": "1.2.0", "github": "https://github.com/acme/acme_sms", "update_url": "" }
```

`version` is what is installed. Use plain dotted numbers (`1.2.0`): they are compared with PHP's `version_compare()`.
`github` is where the package is downloaded from (see step 4).

### 2. Tell the marketplace a newer version exists

There are two ways. Use either, or both.

**A. `update_url` (recommended for plugins and themes).** Set `"update_url"` in `plugin.json` to any `https` address that
returns JSON with the latest version:

```json
{ "version": "1.3.0" }
```

A GitHub release also works as is: `https://api.github.com/repos/acme/acme_sms/releases/latest` returns `tag_name`
(`v1.3.0`; the leading `v` is ignored). The update shows when `version_compare(installed, latest, '<')`. The answer is
cached for 6 hours (failures too), so a new release can take a few hours to show unless the admin presses *Check for updates*.

**B. A repository entry with `last_update`.** If your plugin is listed in a `repository.json` (the official one or your
own, see below), the update shows when the entry's `last_update` date (`YYYY-MM-DD`) is newer than the date the plugin was
installed. Bump `last_update` whenever you release.

Admins are told about updates in Marketplace, by `php system/cron/marketplace_sync.php` (add it to cron), and by one Telegram
message per new version (Marketplace > Tools > Settings can turn that off).

### 3. Release, step by step

1. Raise `version` in `plugin.json` (and fix anything in `requires_*`, below).
2. Run `php skeletons/build.php check plugin <folder>` and click through the plugin once.
3. Commit and **push to the default branch** (`master`, then `main`). The marketplace downloads the branch archive, not a
   tag or a release asset, so what is on that branch is what admins receive.
4. Make the new version visible: publish the `update_url` JSON (or create the GitHub release), or bump `last_update` in
   `repository.json`.

The repository has to be public, unless the admin has entered a GitHub username and token in Settings > General Settings > GitHub Auth.

### 4. What the admin's site does on Update

1. It checks `requires_php`, `requires_version`, `requires_extensions` and `requires_plugins`, and lints every `.php`
   file. A syntax error or an unmet requirement stops the update before anything is touched.
2. It backs the current version up as a zip in `system/cache/backups/` (restorable from Marketplace > Tools).
3. It downloads `<github>/archive/refs/heads/master.zip` (or `main`) and **copies the files over the installed folder**.
   Files you delete in a new version stay on the admin's site, and the plugin's own data (tables, `tbl_appconfig` rows,
   uploads) is kept.
4. `install.php` does **not** run again on an update; it runs once, on first install. Put schema changes in code that runs
   when the plugin loads and guards itself with a stored version:

```php
// in <slug>.php, runs on every request: cheap check, work only when the version changed
if (($config['acme_sms_db_version'] ?? '') !== '1.3.0') {
    acme_sms_migrate();   // CREATE TABLE IF NOT EXISTS ..., ALTER TABLE ... guarded by SHOW COLUMNS
    // then save 'acme_sms_db_version' = '1.3.0' into tbl_appconfig
}
```

### 5. Listing a plugin in a repository

A repository is one JSON file served over `https` (GitHub Pages, any web server or CDN). Admins add its URL in
Marketplace > Tools > Settings (one per line, `https` only), next to or instead of the official catalogue:

```json
{
  "plugins": [
    {
      "id": "acme_sms",
      "name": "Acme SMS",
      "description": "Send SMS through Acme.",
      "author": "Acme",
      "ispaid": false,
      "url": "https://acme.example/sms",
      "github": "https://github.com/acme/acme_sms",
      "last_update": "2026-10-29"
    }
  ],
  "payment_gateway": [],
  "devices": [],
  "themes": []
}
```

* `id` must equal the plugin's folder name (`slug`), or the entry is not matched to the installed plugin.
* `github` is required to install from the entry. Today packages are fetched from GitHub only: a repository file on your
  own server can list plugins, but the `.zip` itself is still downloaded from `github.com`. (An admin can always upload any
  `.zip` by hand under Marketplace > Plugins.)
* `url` is only the link on the card.

### Updates checklist

* [ ] `version` raised, `requires_*` correct
* [ ] pushed to the default branch (`master` or `main`)
* [ ] `update_url` answers with the new version, or `last_update` bumped in `repository.json`
* [ ] schema changes are in a load-time migration, not only in `install.php`
* [ ] tried *Update* on a test site and the backup shows under Marketplace > Tools

## Payment gateways, device drivers and themes

The Marketplace handles four kinds of extension the same way: install, update (with a backup), uninstall, requirement checks
and the repository file. Everything in *Publishing and serving updates* applies to all of them. What differs is the shape.
Each has its own starter with a README: `skeletons/gateway/`, `skeletons/device/`, `skeletons/theme/` (scaffold one with
`php skeletons/build.php new <type> <slug> "<Name>"`).

| | Plugin | Payment gateway | Device driver | Theme |
|---|---|---|---|---|
| Lives in | `system/plugin/` | `system/paymentgateway/` | `system/devices/` | `ui/themes/` |
| Manifest | `plugin.json` | `gateway.json` | `device.json` | `theme.json` |
| Layout | folder (`<slug>/`) or one file | folder (`<slug>/`) or one file | folder (`<Class>/`) or one file | folder only |
| Entry point | `<slug>.php`, loaded on every request | `<slug>.php`, loaded **on demand** | `<Class>.php`, `new <Class>` | templates + optional `functions.php` |
| Name rule | letters, digits, `_`, `-` | letters, digits, `_`; must start with a letter or `_` | the PHP class name, same rule | letters, digits, `_`, `-` |
| Repository key | `plugins` | `payment_gateway` | `devices` | `themes` |
| `install.php` on first install | yes | no | no | no |

All four manifests share the same keys for versions and requirements: `name`, `slug`, `version`, `description`, `author`,
`main`, `requires_php`, `requires_extensions`, `requires_version`, `requires_plugins`, `requires_license`,
`license_check_url`, `update_url`, `github` (a theme has no `main`, `requires_extensions` or `requires_plugins`). Gateways add
`region`, devices add `category` (`Mikrotik`, `Radius`, `Voucher` or `Other`), themes add `parent` and `preview`. `update_url` and `github` work exactly as described above for any of them.

### Payment gateways

The billing system includes the gateway file only when it is needed (checkout, the gateway settings page, the provider's
webhook) and calls functions named after the slug:

| Function | Job |
|---|---|
| `<slug>_validate_config()` | before every checkout: redirect with a message if the admin has not configured it |
| `<slug>_show_config()` / `<slug>_save_config()` | the admin's settings page and its POST (check CSRF; settings live in `tbl_appconfig` as `<slug>_*`) |
| `<slug>_create_transaction($trx, $user)` | the customer chose the gateway: create the payment at the provider and redirect |
| `<slug>_get_status($trx, $user)` | the customer returns or presses "check payment" |
| `<slug>_payment_notification()` | the provider's webhook, `callback/<slug>`: verify the signature, settle the order |

Settling must be idempotent, because polling and the webhook can both arrive. Resolve the file with
`GatewayLoader::file($slug)`, never by joining paths. Templates go in `ui/<slug>.tpl` and are referenced as plain
`<slug>.tpl` or `pg:<slug>.tpl`. A gateway with a PHP error, an unmet requirement or a missing licence cannot be enabled.
Full contract, status codes and webhook-security notes: `skeletons/gateway/README.md`.

### Device drivers

A driver is one class that the billing engine builds with no constructor arguments (`new $plan['device']`) and calls when a
customer is activated or expires (`add_customer`, `remove_customer`), when a username or plan changes (`change_username`,
`add_plan`, `update_plan`, `remove_plan`) and for hotspot login and status (`connect_customer`, `online_customer`,
`disconnect_customer`). Leave unused methods empty rather than deleting them. The core drivers (`MikrotikHotspot`,
`MikrotikPppoe`, `MikrotikStatic`, `MikrotikVpn`, `Radius`, `RadiusALE`, `RadiusChillispot`, `RadiusRest`, `Voucher`,
`Dummy`) cannot be replaced by an upload, and a driver that plans still use cannot be uninstalled. Interface:
`system/devices/readme.md` and `skeletons/device/README.md`.

### Themes

A theme overrides templates by path: a file at `ui/themes/<slug>/admin/customers/list.tpl` replaces
`ui/ui/admin/customers/list.tpl`, and anything it does not override falls back to the parent theme (`"parent"` in
`theme.json`) and then to the default. Optional extras:

* `functions.php` runs on every request while the theme is active (add a stylesheet, Smarty helpers). It must not print or
  `exit`; a PHP error in it blocks activation.
* `styles/customer.css` re-skins the customer portal's header, menu, mobile bar and footer by redefining the `--cm-*`
  colour tokens, with no template copying. Details in `ui/themes/README.md`.
* Dark mode is the `dark-mode` class on `<body>`.

Folder theme only: there is no single-file theme. See `skeletons/theme/README.md`.

### How the installer reads a package

For a `.zip` (an upload, a GitHub archive) it looks for, in this order: the manifest at the top of the package; folders
named `plugin/`, `paymentgateway/` (or `paymentGateway/`, `payment_gateway/`), `device/`, `theme/` that hold extension
folders; then loose `.php` files (an older plugin or flat gateway or device). A single wrapper folder such as GitHub's
`repo-main/` is stepped into. A package that holds several extensions, for example a plugin and a gateway, installs them
all.

### What an update replaces

| | On update |
|---|---|
| Folder plugin, gateway, device, theme | files are copied over the installed folder: new and changed files replace old ones, files removed from the new version stay, the extension's data is kept |
| One-file gateway or device | the `.php` file (and the gateway's `ui/*.tpl`) is overwritten |
| Any of them | the old version is first zipped to `system/cache/backups/` |

Because only plugins get `install.php` and only on the first install, a gateway or driver that needs a new table or setting
after an update has to create it when its own code runs.

## Other parts of the contract

* **Menus**: see the skeleton README for `position`, `auth`, icons and the admin / customer split.
* **Hooks**: `register_hook('cronjob_end', 'myplugin_cron');` Find names with `grep -rn "run_hook(" system/`.
* **Settings**: rows in `tbl_appconfig` (`myplugin_*`), available in `$config` and in templates as `$_c`.
* **Database**: create tables in `install.php`; there are no migrations. Use Idiorm (`ORM::for_table(...)`).
* **Security checklist**: sign-in check (or an `access` level), CSRF on every form that changes data
  (`Csrf::check(_post('csrf_token'))`), prepared queries only, escape output in templates (`|escape`), verify webhooks.
* **Input**: `_post()`, `_get()` and `_req()` only trim. They do not escape.
* **A plugin function must never `die()` with a message**: use `r2()` (redirect with a notice) or `showResult()` so
  the HTML and the JSON (`system/api.php`) modes both work.

## Questions

### Routes and access

**My webhook stopped working in enforce mode.**
It is not declared, so the core refuses it. Add it to `"routes"` in `plugin.json` (or call `register_route()`) with
`"access": "public"`, then check it with `php skeletons/build.php check plugin <folder>`. A webhook has no signed-in user, so
`public` is correct, and the function must verify the caller itself (a signature, a secret in the URL).

**How do I find the functions I forgot to declare?**
In warn mode the core writes one line a day per function to the system log (Logs menu, type *Plugin*): `Plugin route 'x' is
not declared ...`. The check command finds them without waiting for traffic: it reads your PHP, templates and scripts for
`plugin/<function>` addresses and prints what is missing, ready to paste into `plugin.json`.

**My page asks for a login twice.**
Declaring the function `admin` (or `customer`) makes the core run the sign-in check before your function, so your own
`_admin()` / `_auth()` can go. Keeping it is harmless.

**I only have a menu.**
Then there is nothing to do: a menu function is declared by its menu. `"function": "myplugin/settings"` declares
`myplugin`, which reads `$routes[2]` to know which page to show. It must do its own sign-in check, because one function can
serve several pages.

**My function needs arguments.**
It cannot be a route; the core only calls functions with no required parameters. Make a no-argument function that reads
`$_GET` / `$_POST` and calls the real one.

**Does a `public` route need a CSRF token?**
Not when the caller is a provider's server (a payment webhook cannot send one): verify its signature instead. A browser
form that changes data does need `Csrf::check(_post('csrf_token'))`, and such a route should be `admin` or `customer`, not
`public`.

**Does the capitalisation of the URL matter?**
No. PHP function names are not case sensitive, so the core treats `plugin/MyPlugin_Hook` and `plugin/myplugin_hook` as the
same function, and your declarations match either way.

**Why must a plugin never `die("message")`?**
The same controllers also serve the JSON mode (`system/api.php`). `die()` bypasses it and prints raw text. Use `r2()` to
redirect with a notice, or `showResult()`, and both modes work.

**Where is the Plugin Routes switch, and how do I try enforce safely?**
Settings > General Settings > Security tab > *Plugin Routes*. Try *Enforce* on a test site first: click through every plugin
page and fire every webhook once. Anything that answers "Function not found" is missing from `"routes"`. On a non-live site
the message names the function and how to declare it.

### Updates and the marketplace

**I released a new version but admins do not see the update.**
Check, in this order:
1. `version` in `plugin.json` is higher than the installed one (`version_compare`, so `1.10.0` is newer than `1.9.0`).
2. The new code is pushed to the repository's default branch (`master` or `main`). The marketplace downloads that branch,
   not a tag or a release file.
3. Something announces it: `update_url` answers with the new version, or `last_update` is bumped in `repository.json`.
4. The `update_url` answer is cached for 6 hours. The admin can press *Check for updates* to skip the wait.
5. The repository entry's `id` equals your plugin's folder name, and it has a `github` address. Without a matching entry or
   an `update_url` there is nothing to compare against.

**The update installed, but my new table or setting is missing.**
`install.php` runs only on the first install, never on an update. Create tables and settings in code that runs when the plugin
loads, guarded by a stored version (see *What the admin's site does on Update*).

**I deleted a file in the new version, but it is still on admins' sites.**
An update copies files over the installed folder and removes nothing. Keep the old file as a harmless stub, or have your
migration delete it.

**Can I serve the plugin from my own server or CDN instead of GitHub?**
The repository list (`repository.json`) can live anywhere over `https`, and so can your `update_url`. The package itself is
still downloaded from `github.com` today. An admin can always upload any `.zip` by hand under Marketplace > Plugins.

**My repository is private.**
The admin has to enter a GitHub username and token in Settings > General Settings > GitHub Auth, or the download fails. The
README shown on the plugin's card is fetched without that token, so a private plugin shows no README.

**My README images do not show in the Marketplace.**
The marketplace renders a small, safe Markdown subset (headings, lists, code, links, bold, italic) and removes images on
purpose, so remote images cannot track the admin. Put what matters in text; screenshots belong on your own page, linked from
`url` in the repository entry.

### Menus and loading

**My plugin has no menu, or does not load at all.**
Errors in a plugin file are swallowed while plugins load, so a PHP syntax error simply means no menu. Run
`php -l` on every file (`php -l myplugin.php includes/*.php`). Then check the menu itself: `"admin": true` for staff and
`false` for customers, a position name the header actually prints (`AFTER_DASHBOARD` ... see the skeleton README), and for a
submenu the **same position and audience as its parent**. An unmet `requires_php` also stops a folder plugin loading.

**Which is better, a folder plugin or a single file?**
A folder plugin (`plugin.json` + `<slug>.php`). It declares menus and routes in JSON, keeps templates in its own `ui/`, has
`install.php` / `uninstall.php`, and the marketplace can update and back it up cleanly. Single files still work.
