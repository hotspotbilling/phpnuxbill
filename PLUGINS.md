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
menus and routes, and prints what is missing (paste it into `"routes"` in `plugin.json`, or add `register_route()` lines in a
single-file plugin). It exits with 1 when something
needs declaring, so it can run in a plugin's own CI.

## Updating an older plugin: three steps

1. Add `register_route()` calls (or a `"routes"` list) for every webhook, callback and AJAX function.
2. Run the check command until it prints *nothing to declare*.
3. Remove your own `_admin()` check from a function you declared `admin` if you like; the core does it, doing it twice
   is harmless.

## `plugin.json` reference

The manifest of a folder plugin (`system/plugin/<slug>/plugin.json`). The core reads it as plain data and never runs it, so
treat everything in it as public and editable by the customer. Menus, routes and licence rules can live in PHP instead (see the
last column).

**What is required.** Technically only the file itself: it must exist and hold a JSON object, otherwise the folder is not
treated as a plugin. Every key below is optional, but a plugin with no `name`, `version` or `description` looks unfinished in
Marketplace, so set at least those and `author`.

### A complete manifest

```json
{
  "name": "Acme SMS",
  "slug": "acme_sms",
  "version": "1.2.0",
  "description": "Send SMS through Acme.",
  "author": "Acme Ltd",
  "url": "https://acme.example/sms",
  "main": "acme_sms.php",

  "requires_php": "8.2",
  "requires_version": "2025.11.1",
  "requires_extensions": ["curl", "mbstring"],
  "requires_plugins": ["hotspot"],

  "update_url": "https://acme.example/sms/latest.json",
  "github": "https://github.com/acme/acme_sms",

  "routes": [
    { "function": "acme_sms_webhook", "access": "public" },
    { "function": "acme_sms_send_test", "access": "admin" }
  ],
  "menus": [
    { "type": "menu", "name": "Acme SMS", "admin": true, "function": "acme_sms", "position": "AFTER_MESSAGE",
      "icon": "ion ion-chatbox", "label": "New", "color": "success", "auth": ["SuperAdmin", "Admin"] },
    { "type": "submenu", "parent": "acme_sms", "name": "Settings", "admin": true, "function": "acme_sms/settings",
      "position": "AFTER_MESSAGE", "auth": ["SuperAdmin", "Admin"] }
  ]
}
```

### Identity

| Key | Required | Type | If missing | Notes |
|---|---|---|---|---|
| `name` | recommended | string | the folder name | Shown on the Marketplace card |
| `slug` | no | string | the folder name | **Ignored.** The folder name is the slug; keep the key equal to it for readers |
| `version` | recommended | string | `1.0.0` is shown | Plain dotted numbers (`1.2.0`), compared with `version_compare()`. Raise it on every release |
| `description` | recommended | string | empty | One line on the card |
| `author` | no | string | empty | Shown on the card |
| `url` | no | string | none | Link on the card (http / https only) |
| `main` | no | string | `<slug>.php` | The file the core includes on every request. Must be inside the plugin folder, or it is not loaded |

### Requirements (checked before install, update and activation)

| Key | Required | Type | Effect when not met |
|---|---|---|---|
| `requires_php` | no | string, e.g. `"8.2"` | **Blocks** install, update and activation; the plugin is not loaded |
| `requires_version` | no | string, e.g. `"2025.11.1"` | **Blocks** (compared with the billing system's version) |
| `requires_extensions` | no | array of PHP extension names | **Blocks** when one is not loaded |
| `requires_plugins` | no | array of plugin folder names | **Warns only**: the plugin is missing or paused, but install is allowed |

### Updates

| Key | Required | Type | Notes |
|---|---|---|---|
| `update_url` | no | https URL | Answers JSON with `version` (or a GitHub release's `tag_name`); cached 6 hours. Without it the update check uses the store's `last_update` instead |
| `github` | no | `https://github.com/<owner>/<repo>` | Where **Update** downloads the package (`master`, then `main`) |
| `zip` | no | https URL of a `.zip` | Where **Update** downloads the package from your own server or CDN instead of GitHub. Wins over `github` when both are set |

None of these is secret. They are read for every installed plugin on every Marketplace page, loaded or not, so they cannot move into PHP.

### Licence (optional)

| Key | Required | Type | Notes |
|---|---|---|---|
| `requires_license` | no | boolean | Manifest mode only: the plugin is not loaded until a valid key is saved. **Do not set it** if you use `register_license()` in PHP, or the plugin never loads to register |
| `license_check_url` | no | https URL | Manifest mode: the core POSTs `key`, `slug`, `type`, `domain` and expects `{"valid": true}` |
| `license_public_key` | no | base64 Ed25519 key | Manifest mode: the answer must then carry `expires` and a `signature` |

A customer can edit all three. For anything you sell, put the rules in PHP instead (see *Licensing a paid extension*).

### `routes` (or `register_route()` in PHP)

An array of declarations. Each entry is a string (an `admin` route) or an object:

| Key | Required | Notes |
|---|---|---|
| `function` | **yes** | A plain PHP function name that needs no arguments and is defined under `system/plugin/` |
| `access` | no, default `admin` | `admin`, `customer` or `public` (see *Declaring what a URL may call*) |

### `menus` (or `register_menu()` / `register_submenu()` in PHP)

An array of menu entries. The core registers them in order, **after** your main file has run.

| Key | Required | Type | Default | Notes |
|---|---|---|---|---|
| `type` | no | `menu` or `submenu` | `menu` | |
| `name` | **yes** | string | | The text in the menu. An entry with no `name` shows an empty item |
| `function` | **yes** | string | | The function that handles the page. `acme_sms/settings` runs `acme_sms()` with `$routes[2] = 'settings'`. An entry with no `function` is skipped |
| `admin` | no | boolean | `false` | `true` for the staff menu, `false` for the customer portal. A submenu needs the same value as its parent |
| `position` | no | string | `AFTER_DASHBOARD` | A slot the menu templates print, for example `AFTER_MESSAGE` or `SETTINGS` (list in the skeleton README). A submenu needs the same position as its parent |
| `parent` | submenu: **yes** | string | | The `function` of the parent menu. A submenu with no `parent` is skipped |
| `icon` | no | string | none | An icon class (`ion ion-cube`, `fa fa-cog`) or an inline `<svg>` |
| `label` | no | string | none | A small badge, for example `New`. Text only |
| `color` | no | string | `success` | Badge colour: `success`, `danger`, `warning`, `info`, `primary` |
| `auth` | no | array of roles | everyone | `SuperAdmin`, `Admin`, `Report`, `Agent`, `Sales`, `Asset`, `Support`. Empty means all |
| `feature` | no | string | none | Show this entry only when the plugin's licence unlocks that feature (see *Licence tiers*) |

### What can move into PHP

| In `plugin.json` | The same thing in PHP |
|---|---|
| `routes` | `register_route('fn', 'public')` |
| `menus` | `register_menu(...)`, `register_submenu(...)` (mind the argument order) |
| `requires_license`, `license_*` | `register_license(...)` and `license_has(...)` |
| `name`, `version`, `description`, `author`, `main`, `requires_*`, `update_url`, `github`, `url` | stays in the manifest: the core needs them without running your code |

## A card on the dashboard

`register_dashboard_card($id, $title, $function, $auth = [])` puts a card on the admin dashboard, on both the default and the dynamic
dashboard. Call it from your main file, next to `register_menu()`:

```php
if (function_exists('register_dashboard_card')) { // absent on a core from before dashboard cards: skip it
    register_dashboard_card('myplugin', 'My plugin', 'myplugin_dashboard_card', ['SuperAdmin', 'Admin']);
}

function myplugin_dashboard_card()
{
    return '<div class="box box-primary"><div class="box-body">Hello</div></div>';
}
```

| Argument | |
| --- | --- |
| `$id` | Letters, digits and underscores, unique to your plugin. |
| `$title` | Shown on the card's bar while an admin is arranging the dashboard. |
| `$function` | Returns the card's HTML as a string. It runs on every dashboard load, so read saved data and never wait on a slow service. A function that throws or returns nothing is skipped. |
| `$auth` | The user types that see the card; an empty array is everyone. |

A card is arranged like any widget: each admin can drag it, or move it with the arrow buttons (Arrange on the dashboard), and the
position is saved for that admin. The page does not load your plugin's stylesheet, so put the few styles the card needs in a
`<style>` block inside the returned HTML, and use `currentColor` and semi-transparent borders so it suits a light or dark theme.

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
3. Put the package where admins download it. Pick one:
   * **GitHub:** commit and **push to the default branch** (`master`, then `main`). The marketplace downloads the branch
     archive, not a tag or a release asset, so what is on that branch is what admins receive. The repository has to be public,
     unless the admin has entered a GitHub username and token in Settings > General Settings > GitHub Auth.
   * **Your own server or CDN:** zip the plugin (the manifest `plugin.json` at the top of the zip, or inside a single wrapper
     folder), upload it to an `https` address and set `zip` in `repository.json` (and optionally in `plugin.json`). Add
     `sha256` with the file's SHA-256 (`shasum -a 256 acme_sms-1.3.0.zip`) so the admin's site refuses a file that was changed
     on the way. `php skeletons/build.php new plugin <slug> "<Name>"` already makes a zip of this shape.
4. Make the new version visible: publish the `update_url` JSON (or create the GitHub release), or bump `last_update` (and
   `sha256`, for a zip) in `repository.json`.

### 4. What the admin's site does on Update

1. It checks `requires_php`, `requires_version`, `requires_extensions` and `requires_plugins`, and lints every `.php`
   file. A syntax error or an unmet requirement stops the update before anything is touched.
2. It backs the current version up as a zip in `system/cache/backups/` (restorable from Marketplace > Tools).
3. It downloads the package (your `zip`, else `<github>/archive/refs/heads/master.zip`, or `main`) and **copies the files over the installed folder**.
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
    },
    {
      "id": "acme_voice",
      "name": "Acme Voice",
      "description": "Voice calls through Acme, served from our own CDN.",
      "author": "Acme",
      "ispaid": false,
      "url": "https://acme.example/voice",
      "zip": "https://cdn.acme.example/plugins/acme_voice-1.0.0.zip",
      "sha256": "9f2b5c0d7e1a4b83c6d5e0f1a2b3c4d5e6f708192a3b4c5d6e7f8091a2b3c4d5",
      "last_update": "2026-11-06"
    }
  ],
  "payment_gateway": [],
  "devices": [],
  "themes": []
}
```

* `id` must equal the plugin's folder name (`slug`), or the entry is not matched to the installed plugin.
* An entry needs one package source to be installable: `zip` or `github`. Choose either; if both are present, `zip` is used.
* `zip` is an `https://` address of a `.zip`. Redirects are followed only over `https`, and a package larger than 50 MB is
  refused, as is one that unpacks to more than 200 MB or holds more than 5,000 files. The address, and every redirect it takes, must lead
  to a public host: one that resolves to a private, loopback or internal address is refused. It can be on any server or CDN, with no GitHub account involved.
* `sha256` (optional, recommended) is the file's SHA-256 in 64 hexadecimal characters. When present, the site installs the zip
  only if it matches, and an entry whose `sha256` is not 64 hex characters is refused rather than installed unchecked. Change it
  whenever you replace the zip.
* An update shows when `last_update` is newer than the installed date, so bump it with every new zip.
* `version`, `public_key` and `signature` (all three, optional) sign the release: see *Signing your releases* below.
* `url` is only the link on the card.

A real entry that points at one fixed download address (Routely, a plugin served from its own server). The address always returns the
newest release, so the entry never needs editing for a new version, and there is no `sha256` because the file behind it changes:

```json
{
  "id": "routely",
  "name": "Routely | SMS, WhatsApp and VPN",
  "description": "VPN tunnels, SMS and WhatsApp for PHPNuxBill from one Routely account. Use your own Android phones or gateway, or link WhatsApp devices.",
  "ispaid": false,
  "author": "Focuslinkstech",
  "url": "https://routely.focuslinkstech.com.ng/",
  "zip": "https://routely-api.focuslinkstech.com.ng/v1/plugin/releases/routely-latest.zip",
  "last_update": "2026-11-06"
}
```

Use `zip` where an older entry has `"github": "https://github.com/..."`. The `github` field only accepts a github.com address, so a
direct link put there is ignored. Keep a `github` address on the entry as well if sites on an older PHPNuxBill Ultimate should still
be able to install it: those sites ignore `zip`.

### 6. Signing your releases (optional, recommended)

A SHA-256 only proves a file is the one the repository entry lists. A **signature** proves it was made by you: it can only be
created with a private key that never leaves your computer, and every site checks it before anything is installed. Use it for
anything people trust with their billing server.

**The two keys**

| | What it is | Who has it |
|---|---|---|
| Private key | A file with 128 hex characters. Signs each release. | Only you. Keep it offline, back it up, never commit or publish it. |
| Public key | 64 hex characters. Checks signatures. | Everyone: it goes in your repository entry. |

**Step 1. Make the key pair (once).** The PHP `sodium` extension is needed (it ships with PHP; enable `extension=sodium` if it is off):

```
php skeletons/build.php keygen acme-signing.key
```

It saves the private key in `acme-signing.key` and prints the public key. It refuses to overwrite an existing file. To print the
public key again later: `php skeletons/build.php pubkey acme-signing.key`.

**Step 2. For every release, build the zip and sign it.** The version you sign must be the `version` inside the zip's
`plugin.json` (`gateway.json`, `device.json` or `theme.json`):

```
php skeletons/build.php sign acme-signing.key acme_voice-1.2.0.zip plugin acme_voice 1.2.0
```

The arguments are the key file, the zip, the type (`plugin`, `gateway`, `device` or `theme`), the entry `id` and the version.
It prints the values for your entry:

```json
{
  "zip": "https://cdn.acme.example/plugins/acme_voice-1.2.0.zip",
  "sha256": "…64 hex…",
  "version": "1.2.0",
  "public_key": "…64 hex, the same every release…",
  "signature": "…128 hex, new for every zip…"
}
```

**Step 3. Upload the zip and publish the entry.** Put those five keys in the entry next to `id`, `name`, `description` and
`last_update`. The `zip` must be a *versioned* address (`…-1.2.0.zip`): a fixed "latest" address cannot be signed, because its
content changes while a signature is tied to one exact file.

**What the admin's site checks**

1. It downloads the zip and only verifies; it never signs. The signature covers the type, the `id`, the `version` and the
   SHA-256 of the exact file, so it cannot be reused for another extension, an older version, or a changed file.
2. The `version` inside the package must equal the signed `version`, or the install stops.
3. **The first install remembers your public key.** From then on only that key can sign updates for that extension. An entry
   that has another key, or that drops the signature, is refused ("installed with a signed release… not signed by the same
   key"). So even if someone takes over your `repository.json`, they cannot push code without your private key.
4. A signed extension can only be updated from its repository entry, not from a bare `zip` in its manifest.
5. An incomplete set (a key without a signature or a version) is refused, never installed unchecked.

**If you lose or leak the private key.** Nothing already installed breaks, but sites that remember the old key refuse updates
signed with a new one. Make a new key pair and tell your users to **uninstall and reinstall** once (uninstall forgets the remembered
key; plugin data and settings are kept in the database). A leaked key means anyone can sign as you, so rotate it at once.

**Good practice**

* Keep the key file off the server and out of git (add `*.key` to `.gitignore`), and keep a copy in a password manager.
* Sign on the computer that builds your release, not on the web server.
* Never reuse a version number for a different zip: it needs a new signature, and sites will have already seen the old one.

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
`license_check_url`, `license_public_key`, `update_url`, `github`, `zip` (a theme has no `main`, `requires_extensions` or `requires_plugins`). Gateways add
`region`, devices add `category` (`Mikrotik`, `Radius`, `Voucher` or `Other`), themes add `parent` and `preview`. `update_url`, `github` and `zip` work exactly as described above for any of them.

### What differs from `plugin.json`

The identity, requirement, licence and update keys behave as in the `plugin.json` reference above (all optional). Gateways, drivers
and themes have no `routes` or `menus`: a gateway's webhook is `callback/<slug>`, a driver is called by the billing engine and a theme
is only templates. Only these keys differ:

| Manifest | Key | Required | Notes |
|---|---|---|---|
| `gateway.json` | `main` | no, default `<slug>.php` | The file included on demand. A folder with no such file is not listed |
| `gateway.json` | `region` | no | Groups the gateway in Marketplace (`Global`, `Africa`, `Indonesia`, `Bangladesh`, `India`, `Middle East` or `Other`). Guessed from the slug and description when missing, otherwise `Other` |
| `gateway.json`, `device.json` | `license_provider` | no, default `false` | Set `true` when the file calls `register_license(..., 'gateway' / 'device')`. Gateway and driver files are only included on demand, so the core includes the file once to find the licence rules; it then refuses to use the extension until your status function answers `valid`. Folder style only. Do not combine with `requires_license` |
| `device.json` | `slug` | no, but must match | The folder name **and** the PHP class name. The core does `new <slug>` |
| `device.json` | `main` | no, default `<slug>.php` | The file that defines that class |
| `device.json` | `category` | no | `Mikrotik`, `Radius`, `Voucher` or `Other`, to group the driver. Guessed from the class name's prefix when missing, otherwise `Other` |
| `theme.json` | `parent` | no | A plain theme folder name. Templates the theme does not override fall back to the parent, then to the default. Leave it out for a theme that stands alone |
| `theme.json` | `preview` | no | An image path inside the theme folder for the Marketplace card. Without it the core looks for `preview.png`, `preview.jpg`, `preview.webp` or a `screenshot.*` |
| `theme.json` | `main`, `requires_extensions`, `requires_plugins` | not needed | A theme has no PHP entry point |

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

## Licensing a paid extension

**You decide how your extension is licensed.** The core does not own the rules: a manifest flag is only a file the
customer can edit, so the real check has to live in your own code. The core gives you two things: a **License** button on your
extension's Marketplace card, and a place to show the result. Everything else (key format, your server, how the key is stored,
what happens without one) is yours.

### 1. Register your licence rules (recommended)

In your plugin's main file, next to `register_menu()`:

```php
register_license('acme_sms', 'acme_sms_license_status', 'acme_sms_license_save');

// current state, called on every Marketplace page and whenever the core asks
function acme_sms_license_status()
{
    // read your own storage, check your own signature, call your own server: anything you like
    $key = acme_sms_stored_key();
    if ($key === '') {
        return ['state' => 'none', 'message' => 'Enter your licence key'];
    }
    return acme_sms_verify_offline($key)          // e.g. an Ed25519 signature bound to this domain
        ? ['state' => 'valid', 'message' => 'Licensed to ' . $_SERVER['HTTP_HOST']]
        : ['state' => 'invalid', 'message' => 'This key is not valid for this site'];
}

// the admin pressed License and typed a key
function acme_sms_license_save($key)
{
    $ok = acme_sms_activate_on_my_server(trim($key), parse_url(APP_URL, PHP_URL_HOST));
    if ($ok) {
        acme_sms_store_key($key);
    }
    return ['state' => $ok ? 'valid' : 'invalid', 'message' => $ok ? 'Thank you' : 'Rejected'];
}
```

Both callbacks answer `true` / `false`, or `['state' => 'valid' | 'invalid' | 'unchecked' | 'none', 'message' => '...']`. A callback
that throws counts as `invalid`. The core shows the badge (*License needed* until `valid`, *Licensed* after), shows your
`message` to the admin, and refuses the Marketplace **Activate** button until `state` is `valid`.

`register_license($slug, $status, $save, $type)` takes `'plugin'` (default), `'gateway'`, `'device'` or `'theme'` as `$type`. A
plugin is always loaded (that is how it registers), so **your code must guard itself**: call
`Extensions::licenseMissing('plugin', 'acme_sms', [])` (or your own status function) at the top of the main file and `return`
before registering menus, routes and hooks, and again in anything sensitive such as a webhook or a cron job. Spread a few
checks around rather than one, and do not name them after the licence.

Do **not** set `requires_license` in `plugin.json` when you use `register_license()`: that flag makes the core skip loading the
plugin until a key exists, so it would never get to register.

### Licence tiers: hide menus and features

A licence can unlock only part of a plugin (for example Hotspot for everyone, Merchants only with the merchant licence). Have
your status callback list what the key unlocks:

```php
return ['state' => 'valid', 'features' => ['core', 'merchants'], 'message' => 'Merchant licence'];
```

Then ask with `license_has('acme_sms', 'merchants')` (the feature `licensed` means any valid licence). Two places use it:

* **Menus in `plugin.json`**: add `"feature": "merchants"` to a menu or submenu and it is only registered when the licence unlocks
  that feature. With no licence, or no `register_license()`, it is hidden.

  ```json
  { "type": "submenu", "parent": "hotspot", "name": "Merchants", "admin": true, "function": "hotspot/merchant/admin", "feature": "merchants" }
  ```
* **Menus in code**: `if (license_has('acme_sms', 'merchants')) { register_submenu(...); }`.

Hiding a menu is cosmetic. The page and its actions must check `license_has()` themselves (and an unlicensed request should end
with `r2()` or `showResult()`), because a hidden menu does not stop someone typing the address. The `"feature"` key lives in the
manifest, which the customer can edit, so it only decides what the menu shows; the real gate is the check in your code.

### 2. Make editing and reading hard

PHP is source code: whoever installs the extension can read it and remove a check. Nothing in the core can change that. What
helps, in order of effect:

* **Verify offline, with a signature.** Give each customer a key that is your Ed25519 signature over their domain (and an expiry).
  Embed only the *public* key in your code and check with `sodium_crypto_sign_verify_detached()`. A key cannot be forged, and it
  does not work on another domain. Needs PHP's `sodium` extension, which ships with PHP 7.2+.
* **Ship encoded.** Run the main files through ionCube or SourceGuardian (the server then needs the loader), so the checks cannot
  simply be edited out.
* **Call home occasionally** from cron (`register_hook('cronjob_end', ...)`) so you can revoke a key; keep working through a short
  outage.

This keeps honest customers honest, stops key sharing and lets you revoke. It cannot stop a determined person with the source.

### 3. The simple manifest mode (only a reminder)

For a free-form "type a key" box with no logic of your own, a manifest can declare `"requires_license": true`, optionally with
`"license_check_url"` (an https endpoint the core POSTs `key`, `slug`, `type`, `domain` to, answering `{"valid": true}`) and
`"license_public_key"` (base64 Ed25519; the answer must then carry `expires` and a `signature` over
`type|slug|host|key|expires|valid`, checked on every request). The core then does not run the extension until a valid key exists
(a plugin is not loaded, a gateway is not used at checkout or in its webhook, a driver is not used by plans, a theme falls back
to the default). Because the manifest is editable (set it to `false` and the core stops checking), treat this mode as a convenience for
open-source or low-value extensions and use section 1 for anything you sell.

## Other parts of the contract

* **Menus**: see the skeleton README for `position`, `auth`, icons and the admin / customer split. The argument order matters:
  `register_menu($name, $admin, $function, $position, $icon, $label, $color, $auth)` and
  `register_submenu($parentFunction, $name, $admin, $function, $position, $icon, $label, $color, $auth)`. Skipping `$label` and
  `$color` and passing the `$auth` array as the sixth or seventh argument puts it in the wrong slot (it used to show as a badge
  reading "Array"), and the menu then has no role restriction. Pass `''` and `'success'` to skip them, or use `plugin.json`.
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
`plugin/<function>` addresses and prints what is missing. In a folder plugin, paste those into `"routes"` in `plugin.json`.
A single-file plugin has no `plugin.json`: add one `register_route('<function>', '<access>');` line per missing address
next to your `register_menu()` calls, then run the check again.

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
5. The repository entry's `id` equals your plugin's folder name, and it has a `zip` or `github` address. Without a matching entry or
   an `update_url` there is nothing to compare against.

**The update installed, but my new table or setting is missing.**
`install.php` runs only on the first install, never on an update. Create tables and settings in code that runs when the plugin
loads, guarded by a stored version (see *What the admin's site does on Update*).

**I deleted a file in the new version, but it is still on admins' sites.**
An update copies files over the installed folder and removes nothing. Keep the old file as a harmless stub, or have your
migration delete it.

**Can I serve the plugin from my own server or CDN instead of GitHub?**
Yes. Put the `.zip` on any `https` address and set `zip` (and ideally `sha256`) in your `repository.json` entry, or `zip` in
`plugin.json` for the Update button. `repository.json` and `update_url` can live on the same server. GitHub is then not used at all.
Admins can also paste a package address (with its SHA-256) under Marketplace > Plugins > Install a new plugin.

**The install says the package does not match the SHA-256.**
The file at `zip` is not the one `sha256` describes: a stale CDN cache, a re-uploaded zip, or a `sha256` not updated for the
new version. Recompute it with `shasum -a 256 <file>` and update `repository.json`.

**My repository is private.**
The admin has to enter a GitHub username and token in Settings > General Settings > GitHub Auth, or the download fails. The
README shown on the plugin's card is fetched without that token, so a private plugin shows no README.

**My README images do not show in the Marketplace.**
The marketplace renders a small, safe Markdown subset (headings, lists, code, links, bold, italic) and removes images on
purpose, so remote images cannot track the admin. Put what matters in text; screenshots belong on your own page, linked from
`url` in the repository entry.

### Menus and loading

**My plugin has no menu, or does not load at all.**
Errors in a plugin file are swallowed while plugins load, so a PHP syntax error simply means no menu. Lint
every file, one `php -l` per file (PHP older than 8.3 accepts only one file per call):
`find system/plugin/myplugin -name '*.php' -exec php -l {} \;` (or `php -l system/plugin/myplugin.php` for a single-file
plugin). Then check the menu itself: `"admin": true` for staff and
`false` for customers, a position name the header actually prints (`AFTER_DASHBOARD` ... see the skeleton README), and for a
submenu the **same position and audience as its parent**. An unmet `requires_php` also stops a folder plugin loading.

**Which is better, a folder plugin or a single file?**
A folder plugin (`plugin.json` + `<slug>.php`). It declares menus and routes in JSON, keeps templates in its own `ui/`, has
`install.php` / `uninstall.php`, and the marketplace can update and back it up cleanly. Single files still work.
