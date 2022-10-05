# PHP Mikrotik Billing

## Feature

- Voucher Generator and Print
- Self registration
- Multi Router Mikrotik
- Hotspot & PPPOE
- Easy Installation
- Multi Language
- Payment Gateway Midtrans, Xendit and Tripay
- SMS validation for login
- Whatsapp Notification to Consumer
- Telegram Notification for Admin

## Payment Gateway

- [Tripay.com](https://github.com/hotspotbilling/phpmixbill-tripay) | Indonesia
- [Xendit.com](https://github.com/hotspotbilling/phpmixbill-xendit) | Indonesia and Philippine ( Philippine not tested )
- [Duitku.com](https://github.com/hotspotbilling/phpmixbill-duitku) | Indonesia

Click link to download

Goto Discussionif you want another Payment Gateway

## System Requirements

Most current web servers with PHP & MySQL installed will be capable of running PHPMixBill

Minimum Requirements

- Linux or Windows OS
- PHP Version 7.0+
- Both PDO & MySQLi Support
- GD2 Image Library
- CURL support
- MySQL Version 4.1.x and above

can be Installed in Raspberry Pi Device.

The problem with windows is hard to set cronjob, better Linux

## Installation

- Rename **pages_template** to **pages**
- Rename **config.sample.php** to **config.php** and make it writeable (chmod 777)
- make writeable folder **ui/cache/** and **ui/compiled**
- Open web and run installation
- set cronjobs or scheduller for **system/cron.php**
- make **config.php** unwriteable (chmod 644)


See [WIKI](https://github.com/ibnux/phpmixbill/wiki/Instalation)

baca [WIKI](https://github.com/ibnux/phpmixbill/wiki/Instalation)

## UPDGRADE

for old version, below Version 6, backup **system/config.php**, delete all file except folder **pages**, upload all new files, put **config.php** in root folder (not in system folder), got to folder **/install** and run Update.

for version 6 above, just replace all files, using filezilla can choose overwrite if different file size or time.

## RADIUS system

Still on development
## Paid Support

Start from Rp 500.000 or $50

[Telegram](https://t.me/ibnux)

[Website](https://ibnux.net/layanan)

## License

GNU General Public License version 2 or later

see LICENSE file

## Donate to ibnux

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/ibnux)

BCA: 5410454825

Mandiri: 163-000-1855-793

a.n Ibnu Maksum

## SPONSORS

none :(
