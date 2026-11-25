<div align="center">

[![ReadMeSupportPalestine](https://raw.githubusercontent.com/Safouene1/support-palestine-banner/master/banner-project.svg)](https://s.id/standwithpalestine)

<img src="install/img/logo.png" alt="PHPNuxBill" width="220">

# PHPNuxBill

**Billing for Mikrotik Hotspot and PPPoE, with FreeRADIUS support.**
Sell internet access, expire and renew customers automatically, and get paid online.

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)](#requirements)
[![MySQL / MariaDB](https://img.shields.io/badge/MySQL-MariaDB-00758f?logo=mysql&logoColor=white)](#requirements)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL%20v2%2B-2563eb)](LICENSE)
[![Telegram](https://img.shields.io/badge/chat-Telegram-26a5e4?logo=telegram&logoColor=white)](https://t.me/phpmixbill)

[**Docs**](https://phpnuxbill.org/docs/) · [Install](#install) · [Update](#update) · [Features](#features) · [Extend](#extend) · [Changelog](CHANGELOG.md) · [Wiki](https://github.com/hotspotbilling/phpnuxbill/wiki) · [Support](#support)

</div>

---

## Features

| | |
|---|---|
| **Sell access** | Hotspot and PPPoE on one or many Mikrotik routers, vouchers you can generate and print, online payment gateways, customer balance with transfers and automatic renewal. |
| **Know your customers** | Self registration with an optional terms agreement, expiry and renewal messages by WhatsApp, SMS and email, invoices, and Telegram alerts for admins. |
| **Stay in control** | Seven staff roles (SuperAdmin, Admin, Report, Agent, Sales, Asset, Support), SMS code or captcha on sign-in, and server-side limits on repeated attempts. |
| **Scale up** | [FreeRADIUS](https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius) with its own database or through the REST endpoint, and a multi-language portal in light and dark mode that works on a phone. |
| **Make it yours** | A Marketplace for plugins, payment gateways, themes and devices, including your own repository. |

Guides, setup and plugin development are in the [documentation](https://phpnuxbill.org/docs/). New here? Read [How it Works / Cara Kerja](https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja).

## Requirements

Most current web servers with PHP and MySQL can run PHPNuxBill, a Raspberry Pi included. The installer checks everything below before it touches anything.

- **PHP 8.2 or newer** with PDO MySQL, MySQLi, GD, cURL, ZIP, mbstring and JSON
- **MySQL or MariaDB**
- **Writable folders:** `system/cache`, `system/logs`, `system/uploads`, `ui/compiled`, `ui/cache` and `pages`
- *Recommended:* the OpenSSL and Sodium extensions, used to verify signed extensions
- Linux or Windows. Linux is better, because cron jobs are hard to set up on Windows.

## Install

1. Copy the files to your web server. The folder that holds `index.php` is the web root.
2. Create an empty MySQL database and a user for it.
3. Open `/install` and follow the five steps: welcome, requirements, database and admin password, install, done.
4. Press **Delete it now** on the last page to remove the installer. It switches itself off once `config.php` exists anyway.
5. Add the cron jobs, then link your Mikrotik login page to PHPNuxBill ([wiki](https://github.com/hotspotbilling/phpnuxbill/wiki)).

`cron.php` expires and renews customers and `cron_reminder.php` sends the expiry reminders. Schedule both with your system's cron:

```bash
php system/cron.php
php system/cron_reminder.php
```

## Update

Sign in as a SuperAdmin and open `/update.php`. Nothing happens until you press **Start the update**. It then:

1. downloads the package and checks it,
2. backs up the files it replaces to `system/cache/backups/`,
3. installs the new files, and puts the old ones back if copying fails,
4. applies the database changes.

Your `config.php`, uploads and settings are kept. Back up your database first. See the [CHANGELOG](CHANGELOG.md) for what changed.

## Extend

Install plugins, payment gateways, themes and devices from the Marketplace in the admin portal.

- [Payment gateway list](https://github.com/orgs/hotspotbilling/repositories?q=payment+gateway)
- [Plugin list](https://github.com/orgs/hotspotbilling/repositories?q=plugin)
- Build your own: the [plugin guide](https://phpnuxbill.org/docs/) and [PLUGINS.md](PLUGINS.md)

## Support

### Community

- [Github Discussion](https://github.com/hotspotbilling/phpnuxbill/discussions)
- [Telegram Group](https://t.me/phpmixbill)

### Technical support

This Software is Free and Open Source, Without any Warranty.

Even if the software is free, but Technical Support is not,
Technical Support Start from Rp 500.000 or $50

If you chat me for any technical support,
you need to pay,

ask anything for free in the [discussion](/hotspotbilling/phpnuxbill/discussions) page or [Telegram Group](https://t.me/phpnuxbill)

Contact me at [Telegram](https://t.me/ibnux)

## License

GNU General Public License version 2 or later

see [LICENSE](LICENSE) file


## Donate to ibnux

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/ibnux)

BCA: 5410454825

Mandiri: 163-000-1855-793

a.n Ibnu Maksum

## SPONSORS

- [mixradius.com](https://mixradius.com/) Paid Services Billing Radius
- [mlink.id](https://mlink.id)
- [https://github.com/sonyinside](https://github.com/sonyinside)

## Thanks
We appreciate all people who are participating in this project.

<a href="https://github.com/hotspotbilling/phpnuxbill/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=hotspotbilling/phpnuxbill" />
</a>
