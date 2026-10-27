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
3. the plugin has **declared** it (below).

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

Settings > App > *Plugin Routes*, stored as `plugin_routes_mode`:

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

*My webhook stopped working in enforce mode.* Declare it with `access: public`.

*My page asks for a login twice.* Declaring it `admin` makes the core check first; your own `_admin()` can go.

*I only have a menu.* Then there is nothing to do: menu functions are declared by their menu.

*My function needs arguments.* It cannot be a route. Make a no-argument function that reads `$_GET` / `$_POST` and
calls it.
