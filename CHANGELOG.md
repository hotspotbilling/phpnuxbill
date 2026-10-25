![PHPNuxBill](install/img/logo.png)

# CHANGELOG

## 2026.10.25

- Fixed: plugin menus registered with `admin = true` (e.g. Skeleton, My Plugin) showed in the customer portal sidebar whenever an admin session was open in the same browser; admin and customer menus are now kept apart (`_menu_publish()`, called by `_admin()` / `_auth()`)
- Skeleton sample plugin now has both an admin and a customer menu with submenus (SVG icons); the plugin starter used slot names the templates never print (`DASHBOARD`, `ACCOUNTS`) and a submenu in a different slot than its parent, so its menus never showed - fixed, and the slot lists in the docs now match the templates
- Customer portal sidebar: plugin menus with submenus are styled (stray second chevron removed, font icons aligned with the built-in 24px icons, submenu indented under the parent label, long names cut with an ellipsis while the sidebar is expanded, chevron turns when the group opens)
- Default theme, collapsed sidebar: the icon rail scrolls when it is taller than the window (last items such as Community were cut off); each submenu flyout is one card with its title inside it, and long menus (Settings) stay inside the screen and scroll
- Dashboard data (dynamic metrics and classic widgets) is cached in `system/cache/dashboard/dash_*.temp` (folder is blocked over HTTP) for the dashboard TTL when Redis is not enabled or not reachable; the Refresh button clears it
- Security: FreeRADIUS REST endpoint (`radius.php`)
  - `Password::chap_verify()` returned true for a wrong password and false for the right one; it now compares correctly (constant time) and rejects malformed CHAP input
  - Customers are always checked against their own (main or PPPoE) password; the "username = password" / empty-password voucher fallback no longer lets anyone in as an existing customer
  - Inactive/banned customers can no longer authorize, and are never treated as vouchers; a voucher login needs a real `tbl_voucher` row for the `radius` router
  - Strict constant-time password comparison (no `0e123 == 0e999` type juggling)
  - Optional shared secret (Settings > FreeRadius > REST secret): send it as header `X-Radius-Token` (`update request { &REST-HTTP-Header += "X-Radius-Token: <secret>" }` before `rest`) or add `&token=<secret>` to the REST uri; unset keeps the old open behaviour
  - Vouchers are claimed atomically, so two simultaneous logins cannot both activate one
  - Shared-device limit identifies the device by its MAC instead of a client-chosen field
  - Data usage counts `Acct-*-Gigawords` (sessions over 4 GiB were undercounted) and `Mikrotik-Total-Limit-Gigawords` carries limits above 4 GiB; add `&acctInputGigawords=...&acctOutputGigawords=...` to the accounting uri (already in the documented config)
  - Usage is summed in SQL once per login instead of loading every accounting row twice
  - RADIUS Redis cache is also cleared for PPPoE usernames on customer edit and delete
- Marketplace overhaul
  - Hub, plugins, payment gateways, themes and device drivers share one layout, filters, search and sorting (Free / Premium / New / Watchlist, Updates, Issues)
  - Hub-wide search across installed items and every store, with install buttons on results
  - Details drawer with README preview (GitHub), version, requirements, health, licence and update info
  - Watchlist: star store items and revisit them on the hub
  - Update detection for plugins, gateways, devices and themes; per-item Update and hub "Update all"
  - Automatic backups (zip, `system/cache/backups/`) before every install, update, convert and uninstall; restore, download and manual backup in Marketplace > Tools
  - Install safety: PHP syntax lint of the whole package, `requires_php` / `requires_extensions` / `requires_version` / `requires_plugins` checks, zip-slip protection, GitHub-only sources, writable-folder checks
  - Health: syntax errors and plugin load errors are flagged on cards and on the hub; broken gateways/themes cannot be enabled
  - Licence keys for premium extensions (`requires_license`, optional `license_check_url`); activation is blocked until a valid key is saved
  - Activity history written to `tbl_logs` (type `Marketplace`)
  - Custom store repositories (https `repository.json`) next to the official one; repositories may also publish `themes`
  - Scheduled sync + Telegram notification: `php system/cron/marketplace_sync.php`
  - Theme previews (`"preview": "preview.png"` in `theme.json`)
- Payment gateways and device drivers can use the folder layout like plugins
  - Gateway: `system/paymentgateway/<slug>/gateway.json` + `<slug>.php` + `ui/`
  - Device: `system/devices/<Class>/device.json` + `<Class>.php`
  - Flat files keep working; "Convert to folder" is available per extension
  - New `GatewayLoader` / `DeviceLoader`; gateway templates load by plain name or `pg:name.tpl`
  - Checkout, callback and payment-status code now resolve gateways through `GatewayLoader` (only installed gateways can be included)
- Old `?_route=themes` page now redirects to Marketplace > Themes
- Fixed: global `min-height:100%` rule collapsed marketplace cards; shadcn theme showed dark marketplace colours in light mode

## 2026.10.23

- Plugin & Theme System overhaul (Phase 1-4)
  - Folder-based plugins with `plugin.json` manifest (`system/plugin/<slug>/`)
  - `PluginLoader`: scan, load, enable/disable, convert legacy plugins
  - Converted 11 legacy plugins to folder style: `wga`, `semaphore`, `radon_rest`, all `hotspot_pg-*`
  - `register_submenu()` + manifest `menus` array support (nested sidebar menus)
  - Marketplace UI (`?_route=marketplace`) — activate/deactivate/convert/delete plugins
  - Plugin Manager renamed to "Plugin Manager (Legacy)"
  - `install.php` / `uninstall.php` lifecycle hooks
  - `update_url` version checking via GitHub releases API
  - `pluginAssetUrl()` helper for plugin static assets
  - Theme system: `theme.json` manifest, child theme fallback, `functions.php` auto-load
  - Themes page (`?_route=themes`) — list and activate themes

## 2025.11.1

- Added complete customer referral system
  - Customers can refer friends using unique referral codes
  - Track referrals and referred customers
  - Reward system for successful referrals
  - Admin dashboard to manage referral settings
  - Widget for customers to view and share referrals

## 2024.10.23

- Custom Balance admin refill Requested by Javi Tech
- Only Admin can edit Customer Requested by Fiberwan
- Only Admin can show password Requested by Fiberwan

## 2024.10.18

- Single Session Admin Can be set in the Settings
- Auto expired unpaid transaction
- Registration Type
- Can Login as User from Customer View
- Can select customer register must using OTP or not
- Add Meta.php for additional information

## 2024.10.15

- CSRF Security
- Admin can only have 1 active session
- Move Miscellaneous Settings to new page
- Fix Customer Online
- Count Shared user online for Radius REST
- Fix Invoice Print

## 2024.10.7

- Show Customer is Online or not
- Change Invoice Theme for printing
- Rearange Customer View

## 2024.9.23

- Discount Price
- Burst Preset

## 2024.9.20

- Forgot Password
- Forgot Username
- Public header template

## 2024.9.13

- Add Selling Mikrotik VPN By @agstrxyz
- Theme Redesign by @Focuslinkstech
- Fix That and this


## 2024.8.28

- add Router Status Offline/Online by @Focuslinkstech
- Show Router Offline in the Dashbord
- Fix Translation by by @ahmadhusein17
- Add Payment Info Page, to show to customer before buy
- Voucher Template
- Change Niceedit to summernote
- Customer can change their language by @Focuslinkstech
- Fix Voucher case sensitive
- 3 Tabs Plugin Manager

## 2024.8.19

- New Page, Payment Info, To Inform Customer, which payment gateway is good
- Move Customer UI to user-ui folder
- Voucher Template
- Change editor to summernote
- Customer can change language

## 2024.8.6

- Fix QRCode Scanner
- Simplify Chap verification password
- Quota based Freeradius Rest
- Fix Payment Gateway Audit

## 2024.8.6

- Fix Customer pppoe username

## 2024.8.5

- Add Customer Mail Inbox
- Add pppoe customer and pppoe IP to make static username and IP
- Add Sync button
- Allow Mac Address Username
- Router Maps

## 2024.8.1

- Show Bandwidth Plan in the customer dashboard
- Add Audit Payment Gateway
- Fix Plugin Manager

## 2024.7.23

- add Voucher Used Date
- Reports page just 1 for all
- fix start date at dashboard
- fix installation parameter

## 2024.7.23

- Add Additional Bill Info to Customer
- Add Voucher only Login, without username
- Add Additional Bill info to Mikrotik Comment
- Add dynamic Application URL For Installation
- Fix Active Customers for Voucher

## 2024.7.15

- Radius Rest API
- Getting Started Documentation
- Only Show new update just once

## 2024.6.21

- Add filter result in voucher and internet plan
- Add input script on-login and on-logout
- Add local ip for pppoe

## 2024.6.19

- new system for device, it can support non mikrotik devices, as long someone create device file
- add local ip in the pool
- Custom Fix Expired Date for postpaid
- Expired customer can move to another Internet Plan
- Plugin installer
- refresh plugin manager cache
- Docker File by George Njeri (@Swagfin)

## 2024.5.21

- Add Maintenance Mode by @freeispradius
- Add Tax System by @freeispradius
- Add Export Customer List to CSV with Filter
- Fix some Radius Variable by @freeispradius
- Add Rollback update

## 2024.5.17

- Status Customer: Active/Banned/Disabled
- Add search with order in Customer list

## 2024.5.16

- Confirm can change Using

## 2024.5.14

- Show Plan and Location on expired list
- Customizeable payment for recharge

## 2024.5.8

- Fix bugs burst by @Gerandonk
- Fix sync for burst by @Gerandonk

## 2024.5.7

- Fix time for period Days
- Fix Free radius attributes by @agstrxyz
- Add Numeric Voucher Code by @pro-cms

## 2024.4.30

- CRITICAL UPDATE: last update Logic recharge not check is status on or off, it make expired customer stay in expired pool
- Prevent double submit for recharge balance

## 2024.4.29

- Maps Pagination
- Maps Search
- Fix extend logic
- Fix logic customer recharge to not delete when customer not change the plan

## 2024.4.23

- Fix Pagination Voucher
- Fix Languange Translation
- Fix Alert Confirmation for requesting Extend
- Send Telegram Notification when Customer request to extend expiration
- prepaid users export list by @freeispradius
- fix show voucher by @agstrxyz

## 2024.4.21

- Restore old cron

## 2024.4.15

- Postpaid Customer can request extends expiration day if it enabled
- Some Code Fixing by @ahmadhusein17 and @agstrxyz

## 2024.4.4

- Data Tables for Customers List by @Focuslinkstech
- Add Bills to Reminder
- Prevent double submit for recharge and renew

## 2024.4.3

- Export logs to CSV by @agstrxyz
- Change to Username if Country code empty

## 2024.4.2

- Fix REST API
- Fix Log IP Cloudflare by @Gerandonk
- Show Personal or Business in customer dashboard

## 2024.3.26

- Change paginator, to make easy customization using pagination.tpl

## 2024.3.25

- Fix maps on HTTP
- Fix Cancel payment

## 2024.3.23

- Maps full height
- Show Get Directions instead Coordinates
- Maps Label always show

## 2024.3.22

- Fix Broadcast Message by @Focuslinkstech
- Add Location Picker

## 2024.3.20

- Fixing some bugs

## 2024.3.19

- Add Customer Type Personal or Bussiness by @pro-cms
- Fix Broadcast Message by @Focuslinkstech
- Add Customer Geolocation by @Focuslinkstech
- Change Customer Menu

## 2024.3.18

- Add Broadcasting SMS by @Focuslinkstech
- Fix Notification with Bills

## 2024.3.16

- Fix Zero Charging
- Fix Disconnect Customer from Radius without loop by @Gerandonk

## 2024.3.15

- Fix Customer View to list active Plan
- Additional Bill using Customer Attributes

## 2024.3.14

- Add Note to Invoices
- Add Additional Bill
- View Invoice from Customer side

## 2024.3.13

- Postpaid System
- Additional Cost

## 2024.3.12

- Check if Validity Period, so calculate price will not affected other validity
- Add firewall using .htaccess for apache only
- Multiple Payment Gateway by @Focuslinkstech
- Fix Logic Multiple Payment gateway
- Fix delete Attribute
- Allow Delete Payment Gateway
- Allow Delete Plugin

## 2024.3.6

- change attributes view

## 2024.3.4

- add [[username]] for reminder
- fix agent show when editing
- fix password admin when sending notification
- add file exists for pages

## 2024.3.3

- Change loading button by @Focuslinkstech
- Add Customer Announcements by @Gerandonk
- Add PPPOE Period Validity by @Gerandonk

## 2024.2.29

- Fix Hook Functionality
- Change Customer Menu

## 2024.2.28

- Fix Buy Plan with Balance
- Add Expired date for reminder

## 2024.2.27

- fix path notification
- redirect to dashboard if already login

## 2024.2.26

- Clean Unused JS and CSS
- Add some Authorization check
- Custom Path for folder
- fix some bugs

## 2024.2.23

- Integrate with PhpNuxBill Printer
- Fix Invoice
- add admin ID in transaction

## 2024.2.22

- Add Loading when click submit
- link to settings when hide widget

## 2024.2.21

- Fix SQL Installer
- remove multiple space in language
- Change Phone Number require OTP by @Focuslinkstech
- Change burst Form
- Delete Table Responsive, first Column Freeze

## 2024.2.20

- Fix list admin
- Burst Limit
- Pace Loading by @Focuslinkstech

## 2024.2.19

- Start API Development
- Multiple Admin Level
- Customer Attributes by @Focuslinkstech
- Radius Menu

## 2024.2.13

- Auto translate language
- change language structur to json
- save collapse menu

## 2024.2.12

- Admin Level : SuperAdmin,Admin,Report,Agent,Sales
- Export Customers to CSV
- Session using Cookie

## 2024.2.7

- Hide Dashboard content

## 2024.2.6

- Cache graph for faster opening graph

## 2024.2.5

- Admin Dashboard Update
  - Add Monthly Registered Customers
  - Total Monthly Sales
  - Active Users

## 2024.2.2

- Fix edit plan for user

## 2024.1.24

- Add Send test for SMS, Whatsapp and Telegram

## 2024.1.19

- Paid Plugin, Theme, and payment gateway marketplace using codecanyon.net
- Fix Plugin manager List

## 2024.1.18

- fix(mikrotik): set pool $poolId always empty

## 2024.1.17

- Add minor change, for plugin, menu can have notifications by @Focuslinkstech

## 2024.1.16

- Add yellow color to table for plan not allowed to purchase
- Fix Radius pool select
- add price to reminder notification
- Support thermal printer for invoice

## 2024.1.15

- Fix cron job for Plan only for admin by @Focuslinkstech

## 2024.1.11

- Add Plan only for admin by @Focuslinkstech
- Fix Plugin Manager

## 2024.1.9

- Add Prefix when generate Voucher

## 2024.1.8

- User Expired Order by Expired Date

## 2024.1.2

- Pagination User Expired by @Focuslinkstech

## 2023.12.21

- Modern AdminLTE by @sabtech254
- Update user-dashboard.tpl by @Focuslinkstech

## 2023.12.19

- Fix Search Customer
- Disable Registration, Customer just activate voucher Code, and the voucher will be their password
- Remove all used voucher codes

## 2023.12.18

- Split sms to 160 characters only for Mikrotik Modem

## 2023.12.14

- Can send SMS using Mikrotik with Modem Installed
- Add Customer Type, so Customer can only show their PPPOE or Hotspot Package or both

## 2023.11.17

- Error details not show in Customer

## 2023.11.15

- Customer Multi Router package
- Fix edit package, Admin can change Customer to another router

## 2023.11.9

- fix bug variable in cron
- fix update plan

## 2023.10.27

- Backup and restore database
- Fix checking radius client

## 2023.10.25

- fix wrong file check in cron, error only for newly installed

## 2023.10.24

- Fix logic cronjob
- assign router to NAS, but not yet used
- Fix Pagination
- Move Alert from hardcode

## 2023.10.20

- View Invoice
- Resend Invoice
- Custom Voucher

## 2023.10.17

- Happy Birthday To Me 🎂 \(^o^)/
- Support FreeRadius with Mysql
- Bring back Themes support
- Log Viewer

## 2023.9.21

- Customer can extend Plan
- Customer can Deactivate active plan
- add variable nux-router to select  only plan from that router
- Show user expired until 30 items

## 2023.9.20

- Fix Customer balance header
- Deactivate Customer active plan
- Sync Customer Plan to Mikrotik
- Recharge Customer from Customer Details
- Add Privacy Policy and Terms and Conditions Pages

## 2023.9.13

- add Current balance in notification
- Buy Plan for Friend
- Recharge Friend plan
- Fix recharge Plan
- Show Customer active plan in Customer list
- Fix Customer counter in dashboard
- Show Customer Balance in header
- Fix Plugin Manager using Http::Get
- Show Some error page when crash
## 2023.9.7

- Fix PPPOE Delete Customer
- Remove active Customer before deleting
- Show IP and Mac even if it not Hotspot

## 2023.9.6

- Expired Pool
Customer can be move to expired pool after plan expired by cron
- Fix Delete customer
- tbl_language removed

## 2023.9.1.1

- Fix cronjob Delete customer
- Fix reminder text

## 2023.9.1

- Critical bug fixes, bug happen when user buy package, expired time will be calculated from last expired, not from when they buy the package
- Time not change after user buy package for extending
- Add Cancel Button to user dashboard when it show unpaid package
- Fix username in user dashboard

## 2023.8.30

- Upload Logo from settings
- Fix Print value
- Fix Time when editing prepaid

## 2023.8.28

- Extend expiration if buy same package
- Fix calendar
- Add recharge time
- Fix allow balance transfer

## 2023.8.24

- Balance transfer between Customer
- Optimize Cronjob
- View Customer Info
- Ajax for select customer

## 2023.8.18

- Fix Auto Renewall Cronjob
- Add comment to Mikrotik User

## 2023.8.16

- Admin Can Add Balance to Customer
- Show Balance in user
- Using Select2 for Dropdown

## 2023.8.15

- Fix PPPOE Delete Customer
- Fix Header Admin and Customer
- Fix PDF Export by Period
- Add pppoe_password for Customer, this pppoe_password only admin can change
- Country Code Number Settings
- Customer Meta Table for Customers Attributess
- Fix Add and Edit Customer Form for admin
- add Notification Message Editor
- cron reminder
- Balance System, Customer can deposit money
- Auto renewal when package expired using Customer Balance


## 2023.8.1

- Add Update file script, one click updating PHPNuxBill
- Add Custom UI folder, to custome your own template
- Delete debug text
- Fix Vendor JS

## 2023.7.28

- Fix link buy Voucher
- Add email field to registration form
- Change registration design Form
- Add Setting to disable Voucher
- Fix Title for PPPOE plans
- Fix Plugin Cache
## 2023.6.20

- Hide time for Created date.
  Because the first time phpmixbill created, plan validity only for days and Months, many request ask for minutes and hours, i change it, but not the database.
## 2023.6.15

- Customer can connect to internet from Customer Dashboard
- Fix Confirm when delete
- Change Logo PHPNuxBill
- Using Composer
- Fix Search Customer
- Fix Customer check, if not found will logout
- Customer password show but hidden
- Voucher code hidden

## 2023.6.8

- Fixing registration without OTP
- Username will not go to phonenumber if OTP registration is not enabled
- Fix Bug PPOE