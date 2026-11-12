![PHPNuxBill](install/img/logo.png)

# CHANGELOG

## Unreleased

- Fixed (settings review): confirming Delete on Widgets or in the widget editor, or Reset File in the page editor, submitted the surrounding form (saving the sequence, the widget or the page) instead of following the link, because ask() submits the closest form; the confirmed action now opens the link. Database backup refuses an empty selection in the page and in the handler, which also accepts only names of tables that exist (it called count() on whatever was posted). The page editor's Copy says "Copied" only once the clipboard took the address, and otherwise leaves it selected with a hint to copy by hand. Cron Job blocks copy their commands line by line (a detached copy lost the line breaks), and each web address has its own Copy that copies that address only. A settings row with no label gives its field the whole row (Translation).
- Fixed (General Settings > Email): the SMTP port field sat on top of the help text under the label; host and port now share the field column, the port at its right end, in line with the other rows. A second column only counts as help text when it holds no field.
- Added (General Settings > SMS / WhatsApp): when the Routely plugin is gone but the address still points at Routely (its folder deleted by hand, so its uninstall step did not restore the old address), the tab says messages are still sent through Routely and that the plugin is not installed, with Clear address and Reinstall Routely buttons, instead of the "Get Routely free" pitch.
- Changed: Radius Rest Interim-Update moved from Miscellaneous to General Settings > FreeRADIUS, beside the other session settings (same setting, `frrest_interim_update`, still used by cron.php; its value is kept). The "You can use WhatsApp in here too. Free Server" line on the SMS and WhatsApp tabs is now a block that says what the free community gateway (wa.nux.my.id) is for, the three steps to use it and its limits, with an Open free server button. The WhatsApp tab's Routely block opens the plugin's WhatsApp page now that WhatsApp is live on Routely. Both Routely blocks show a Free plan badge and what the free plan covers, and the buttons read Start free / Get Routely free.
- Changed (settings pages): every Yes/No setting is a switch (custom.js turns a two-option dropdown with a yes/no, 1/0, true/false, on/off, enable/disable or active/inactive pair into a switch; the dropdown stays in the form with its name and values, and the switch fires its change event, so saving and the scripts that watch it are unchanged). Protected Pages (captcha) options are chips. Searchable dropdowns (Select2) open a list in the settings style. Each Cron Job command block has a Copy button that copies the commands without the comment lines. The SMS and WhatsApp tabs introduce Routely: Connected when Routely filled in the gateway address, Connect Routely when the plugin is installed, otherwise Learn more / Get Routely (WhatsApp says coming soon). The Redis page is rebuilt: overview tiles with a memory bar (used against peak) and a coloured hit-ratio bar, the toggles as switch rows, and the connection fields in one row.
- Fixed: the Redis Purge / Flush button showed as bare red text outside any card, and as a submit button using the confirm dialog it could be left on the footer script's 5-second spinner; it is a styled button in the Cache Management card (type=button, the dialog submits the form) and is disabled while Redis is not connected.
- Changed: the settings pages are redesigned in the look of the referral settings form, in the default and shadcn themes, light and dark. General Settings, Localisation, Miscellaneous, User Notification, Maintenance, Redis, Custom Fields and Translation keep their markup and field names; a shared stylesheet (`.st`) turns each setting into a row (name and help on the left, the control on the right), each tab or panel into a card, the save button into the card's footer strip, and the General Settings tab list into a side menu (a scrolling row of tabs on a phone, instead of the slide-out drawer). Fields use the same 40px controls as the customer and referral forms, searchable selects included; checkbox groups (notification channels, mandatory fields) are chips; section headers and the Cron Job commands have their own blocks. Devices, Database (backup with Select all and row counts, restore with a warning), Widgets (role tabs, a clickable structure preview, an area card per slot with sequence, Edit and Delete), the widget editor, Plugin Manager (type tabs with counts) and the page editor (page address with Copy) are rebuilt on the list kit with the same routes and field names.
- Fixed: on Plugin Manager, choosing a zip did nothing (the file input called `this.submit()`), and the install warning sat on the form, where the confirm dialog's restore of the form's HTML emptied the chosen file and the typed GitHub address; the warning is now on the Install button and choosing a zip goes through it. The widget editor's Status marked Active as selected for a disabled widget (it only worked because the last selected option wins). Showing the Session Timeout, custom tax rate and login-page custom fields rows no longer forces them to `display: block`. Restoring a database now asks first and needs a file.
- Changed: the fields on the customer add and edit pages (default admin theme) use the same style as the referral settings and Send Message forms: 40px tall, 9px corners, the shared field colours and the accent ring on focus. Icon addons and the buttons beside a field (show password, Generate, GPS) join it as one control with rounded outer ends, and the profile photo picker's button sits inside the field. The edit cards now share the list kit's colour tokens. The Save / Cancel bar at the bottom uses the same buttons and footer strip as the referral settings form (40px list-kit buttons, an icon on Cancel, the soft footer fill); on a phone the buttons stack full width with Save on top.
- Fixed: opening the updater at its database step (`update.php?step=4`) on a site with no migration record set the starting point to the text "the previous version", which covers no version, so every database change since 2023 ran again. Old changes undo newer ones: they put the plan and transaction types back to a list without VPN, and the run stopped at 2024.2.12, which would have removed the Asset and Support staff roles. The updater now stops and asks for the version (`php system/cron/migrate.php --baseline=VERSION`), the same as the command line, and `Migrator::baseline()` refuses anything that is not a version number. A site that already has such a record is recognised: the update page explains it in plain words and offers a Repair the list button that asks for the version the database was last fully updated to (left empty on purpose: the installed files' version can be newer than the database), followed by Finish the database update; nothing else can start until it is repaired. From the command line it is `php system/cron/migrate.php --repair=VERSION`. The repair takes the migration lock, runs in one transaction, and keeps every update the record shows as run, so repairing to an earlier version never runs them again. Update 2026.11.11 also puts VPN back into the plan and transaction types and the customer service type, for sites where the re-run removed it or that were installed from the older install script. The install script now creates `tbl_plans.type`, `tbl_transactions.type` and `tbl_customers.service_type` with VPN (from update 2024.9.13), which fresh installs were missing.
- Fixed: the referral program could not be switched off (and other referral settings did not stick) on sites that had two copies of each referral setting. The 2025.10.9 update inserted the referral settings without checking whether they existed, so a site that already had them got a second row; saving wrote the first row while the app reads the last. Saving now writes every copy, the 2025.10.9 insert only adds settings that are missing, and update 2026.11.11 removes the extra copies, keeping the last one, which is the value the app has been using (save the referral settings again afterwards if a change made before this fix did not stick).
- Fixed (Send Message review): a bulk run can be stopped. Stop (or Cancel while a run is going) lets the batch in flight finish and starts no other, then reports how many customers were processed; leaving the page mid-run asks first. When a batch does not answer, the run pauses instead of ending: some of that batch may already have gone out, so Continue skips it and carries on with the next batch (nobody gets the message twice, and a Stop pressed before the failure no longer ends the resumed run), and Finish here ends the run. The final status never counts such a batch as sent or as not sent: it names the batch and its customers as unknown and points to the Message log. The progress bar starts at 0% instead of full, the history table is redrawn once per batch instead of once per row, a batch whose result is unknown before the total is known is reported as "up to" the batch size, and the SMS part count for Unicode messages counts an emoji as two characters, as the network does. Send Personal Message no longer opens the confirmation when no channel is ticked.
- Fixed (Referral review): the reward amount field that is hidden (Fixed amount or % of purchase, whichever is not chosen) no longer blocks saving with an error nobody can see (it is read-only while hidden, which leaves it out of validation); it still saves its value, and a value cleared, half-typed or out of range before switching modes is put back to the last one that passed the field's checks. Saving also limits a percentage reward to 0-100 (it only stopped negatives before). The Rewards paid chart has the same numbers as a table for screen readers.
- Fixed: dashboard icons. The cron warning showed an empty circle and Disk Storage had no icon, because `fa-clock-o` and `fa-hdd-o` are Font Awesome 4 names and the admin draws `.fa` with Font Awesome 6 (now `fa-clock` and `fa-hard-drive`, also in the shadcn dashboard and the cron widget). The stat card circles (Total Customers, Active Connections, Online Users, Monthly Revenue, Today Sales) were squeezed into ovals of different widths when the text beside them was long, and their icons sat at the left because `isp-justify-center` was never defined; they are now the same 44px circle with the icon centred, and Online Users has the same fill strength as the others in light and dark mode.
- Fixed: in every list-kit table the Balance, amount and Manage headings were left-aligned over right-aligned values, because `.ls-table th` outweighed `.ls-num` and `.ls-end`; they now line up with their column. The table loader no longer flashes over the table on every page load (the table arrives with the page); it still shows while a search, page size or page change loads, without blurring the text, with its badge near the top of the table instead of the middle of a long one, and it is cleared when the Back button restores a page. While a customer's packages load, the Package cell keeps its table-cell layout (the loading style made it inline-block, which moved the columns) and has room reserved, so the other columns stay put when the packages arrive. The same cell fix applies to the bandwidth cells on the customer's order page.
- Changed: the admin Referral Settings and Referrals pages are redesigned on the shared list kit. Settings: the program and "Reward on purchase" are switches, minimum purchase and code length sit in the same settings list, the referrer and the new customer each get a card with a Fixed amount / % of purchase toggle and one amount field with its unit, and a side panel says in plain words what each side gets. A percentage can only be picked with Reward on purchase on (as before), the minimum purchase keeps its value when that is off (it used to be saved as 0), and the page warns when the referral tables need the database update or customer balance is off. Referrals: four overview tiles (referrals and the last 30 days, rewarded, paid out split between referrers and new customers, pending), a bar chart of rewards paid per day over the last 30 days (every day shown), the top five referrers by customers brought in with what they earned, and a searchable table (customer name, username or code) linking to both customers. The Reward Type charts are gone, since balance is the only reward type that can be set.
- Fixed: the Referrals list showed wrong reward figures per row, because the rewards were grouped by recipient only (one total for all referrers, one for all new customers) and then matched to a single arbitrary relationship. Rewards are now added up per relationship for the rows on the page, and pending rewards are no longer counted as paid. The page also stopped writing two debug entries to the activity log on every visit.
- Fixed: a menu badge from `register_menu()` ("New", "Beta") ran into the menu name in the default admin sidebar, because the name could not shrink and there was no gap before the badge. The name now ends in an ellipsis when it does not fit, the badge keeps its size with an 8px gap, and the full name is still in the tooltip.
- Changed: the Send Personal Message and Send Bulk Message pages are redesigned on the shared list kit and share one composer (`admin/message/composer.tpl`, `composer-side.tpl`). Channels are toggle chips, the subject appears only for Email, Inbox and Push, placeholders are buttons that insert at the cursor, and a live preview shows the message with the placeholders filled in (with the picked customer's name on the single page). A counter shows characters and, when SMS is ticked, how many SMS parts it takes (and when an emoji makes it Unicode). The bulk page groups Audience and Delivery, has a Test run switch that is on by default and changes the button to Start test run / Start sending, shows a progress bar with Sent, Failed and Skipped counts, and colours the history statuses. Router names are now escaped, the batch list offers 100 (which the server already accepted), and the second element with id="status" is gone. Light and dark mode, core and themed admin. Neither page has a header of its own (the page title already says Send Message); the bulk page's Delivery is a settings list (Test run switch, which turns amber with "the message goes to real customers" when off, and the batch size); the footer bar follows the card's rounded corners; the searchable router and customer fields (Select2) and their open list match the other fields in light and dark mode, without the white flash on focus, and Select2's hidden select stays 1px (the global min-height: 100% rule stretched it to the height of the page, so clicking an empty part of the page scrolled it into blank space); Cancel has an icon, and the send confirmation keeps the page where it is (SweetAlert's heightAuto, which reset the page height, is off there).
- Fixed: Transactions by Service on the dashboard showed one bar per voucher ("Voucher - 162514914", "Voucher - 767096210", ...), because it grouped on the whole payment method and a voucher's method ends in its code. It now groups on the service part before " - ", so all vouchers are one Voucher bar (and cash by different admins one Cash bar), and only the top 10 services get a bar of their own; the rest are added up as Other.
- Changed: the new-version popup on the admin dashboards is redesigned and shared by all four dashboard templates (`admin/update-notice.tpl`). It is a card instead of a SweetAlert toast: the installed and latest versions side by side, up to four "What's new" points from the public changelog, Update now (View details for roles that cannot install), Later (hidden for a day) and Skip this version. It no longer closes itself after 30 seconds, Esc closes it, it follows light and dark mode in the core and themed admin, and its text is translated. The GitHub check is reused for six hours instead of running on every dashboard visit.
- Fixed: the new-version popup offered the public release whenever it differed from the installed version, so a site running a newer build was told to "update" to an older one. It now only appears when the public release is newer, using the same version comparison as the Community page.
- Fixed: the installer could hang until Cloudflare answered 524 when a replace-existing install had to wait for a table another connection still held open. It now gives up after 20 seconds with an error that says what to do.
- Fixed: the installer's "Replace the existing PHPNuxBill tables" option still failed with `Table 'tbl_message_logs' already exists`, because the setup script only drops some of the tables it creates. A fresh start now drops every table the setup scripts create (and no other table in the database) before importing.
- Fixed: the "Show" (entries per page) menu on the Vouchers list did not stick when the site was opened with URL rewriting (`/plan/voucher`): the shared handler guessed the cookie from the query string only and wrote the customers cookie instead, so the old size came back. The select now names its cookie, and the guess also reads the path.
- Fixed: pressing "Yes, subscribe" on the customer portal's push prompt could leave the browser recording `push_subscribed = no` (a refusal, with a 30 day wait). Closing the prompt wrote the refusal before the subscription finished. Choosing Yes is now remembered, a success clears the refusal time, and a technical failure (no HTTPS, a server error) asks again after one day instead of 30.
- Fixed: white text on the primary buttons, the selected tab and the current page in dark mode was about 3:1 on the light indigo. Filled controls now use a deeper indigo in dark mode (4.8:1) through new `--ls-fill`, `--cp-fill`, `--rb-fill`, `--cv-pri-fill` and `--pg-fill` tokens; light mode and accent text are unchanged. Their hover now darkens the fill (it used to brighten it, which dropped the text below 4.5:1).
- Added: `CONTRIBUTING.md`, and a "CSS and UI conventions" page in the documentation (Extending) covering where styles live, the colour tokens, the list kit, dark-mode traps and the stylesheet checklist.
- Changed: the active item in the admin side menu (and the active child, the collapsed icon rail and its flyouts) is filled with the primary indigo with white text (a slightly deeper indigo in dark mode so the text reads at 4.8:1). The menu hover colour and the footer links use the same indigo.
- Fixed (list kit review): Port Pool links use full route paths; the PPPoE and VPN status filter says "Disabled" like the row badge; bandwidth option values are escaped in the plan lists; the Referral status labels are escaped and the rewards tooltip is translated.
- Fixed (list kit review): the phone card layout uses one column unless a row has a checkbox cell; the coupons page no longer starts DataTables on an empty list; the Hotspot, PPPoE and VPN lists keep the searched name; the Activity log search says it searches the description; the payment gateway audit escapes the gateway name in its links; the daily report's Total row lines up under Plan Price.
- Fixed: the Referral page cards (the four stat cards, charts and tables) had a hard-coded white background; they are now transparent with the neutral border and 12px radius used on the dashboard, in light and dark mode. The coloured left edge on the stat cards stays.
- Changed: Coupons and the Referral relationships and top-referrers tables use the shared list kit. Coupon codes are blurred until hovered or tapped, and a bulk bar with Delete Selected appears when coupons are ticked (the delete button no longer errors for roles that cannot delete).
- Fixed: log rows written without a type showed an empty badge. `_log()` now records the type "System" when none is given, and the Activity log shows "System" for older rows with no type. The Keep logs field and the Clean up button no longer wrap or look unstyled, and red buttons in the list kit are red at rest.
- Changed: the shared list kit now also styles Administrators, Routers, Port Pool, RADIUS NAS, Activity, RADIUS and Message logs, Payment Gateway audit, Activation report, Daily report and Invoices. Secrets (router address and user, RADIUS passwords) are blurred until hovered or tapped. Log text is now escaped when shown.
- Changed: the Balance, Bandwidth and IP Pool lists use the shared list kit (toolbar with search, table with row actions and icon delete, phone card layout, empty state). Links and actions are unchanged.
- Changed: the Hotspot, PPPoE and VPN plan lists use the shared list kit: one toolbar for search and filters, a single table with a Disabled pill and a Postpaid chip, time and data limits in one column, right-aligned prices, phone card layout, and an empty state. Filters, actions and links are unchanged.
- Changed: the customers-list table, toolbar, tabs, pills and buttons are now a shared list kit under neutral `ls-*` names (they were `cl-*`, customers only), and the Vouchers page uses it: search and filters in one toolbar, status pills, a bulk-delete bar that appears when rows are selected, vouchers blurred until hovered or tapped, a phone card layout, and an empty state. The batch filter is now kept when you change page.
- Changed: the default admin skin's styles are scoped by its own `body.as-chrome` class (the customer pager also by `body.cm-chrome`) instead of by excluding a named theme, so core no longer mentions any theme. A theme's body does not carry these classes, so its pages look the same as before. Theme names were also removed from code comments and older entries in this file.
- Changed: the Activity list on Marketplace > Tools is paginated (15 per page) and shows the whole history, not only the latest 60 rows.
- Changed: the Send Message window on the customers list is redesigned. It shows how many customers are selected, offers the channels as toggle chips, shows the subject field only when a channel needs one, counts characters, and follows light and dark mode with readable typed text.
- Fixed: typed text was near-black in the Marketplace install panel in dark mode. A global rule (`.dark-mode input:focus { color: #1f201f }`) paints focused fields dark for the white focus
  fill it also forces; the panel keeps its dark fill, so the text was dark on dark. The panel's fields now set the text-fill colour (light in dark mode, dark in light mode), the caret and the
  placeholder, and the autofill preview. The other pages I restyled already did this.
- Marketplace install panel: the SHA-256 label is tied to its field (clicking the label focuses it, and screen readers read it with the field), the file picker has a visible keyboard focus
  (indigo border and ring), and in dark mode the focus border of the URL, text and file fields shows (a later dark-mode border rule was hiding it).
- Customers list: the "Show" dropdown now shows the page size in use. The controller falls back to 30 when no size is saved, but the dropdown only matched its fixed list, so it showed 10
  while 30 rows were listed; a saved size outside the list (for example 30) is added to the dropdown and selected. The saved size is read as a whole number between 1 and 5000, so a
  damaged cookie can no longer be stored as the setting.
- Fixed: the search box on Rollback Update did nothing. The script hides rows with the `hidden` attribute, but the rows set `display: grid`, which overrides it, so every row stayed visible. The
  hidden attribute now wins for the rows, the list and the "no version matches" note.
- Fixed: the page background had gone back to `#f6f9fc` / `#151521` instead of the menu's colour. The rule that gives the page, content header and top bar the menu background was cut out of
  `admin-chrome.css` by a later edit of the search-list styles; it is back. Checked on 26 admin pages in light and dark: the menu, page, content header, top bar and footer are one colour.
- Community and Rollback Update (core theme): the cards now look like the Routely card on the dashboard, with no fill of their own (the page shows through), a 1px neutral border and a 12px radius,
  instead of `#1e1e2d` fills on the darker page. Their tinted parts (the version tiles, link tiles, status banner, rollback bar and rows) follow, and the rollback search keeps the form-field
  surface. Done through the pages' colour tokens, so hover and active states are unchanged.
- Marketplace install panel layout: the package address and its SHA-256 (which had no label and hung under the address) are now two labelled fields side by side on the second row, under
  the file and GitHub fields, with the Install button at the end of that row; on a phone the fields stack and Install comes last. Panels with only a file field (themes, gateways) keep the
  file field wide with the button beside it.
- Marketplace install panel: the SHA-256 field under the package address was an unstyled browser input stuck to the field above it. All fields in the panel (file, GitHub address, package
  address, SHA-256) are now the same 38px control with a focus ring and a gap between the two stacked ones, the fields line up from the top, the Install button sits level with the first
  row, and the panel has no fill of its own (it follows the cards) with the dashed indigo border.
- Marketplace details drawer and licence dialog: fixed, they were see-through (they carry the `.mp-wrap` class, so the transparent card fill from the last change reached them and the page showed
  through behind the text). They now have the page colour with the neutral border.
- The top-bar search results list uses the same page colour with the neutral border of the cards (not the card fill), a 1px divider above its footer (it was a thick coloured line), and soft
  neutral tints for the icon tiles, badges, hover row and key caps. Core theme only.
- Marketplace (core theme, every tab): the cards, hub cards, panels, empty states, diagnostics bar, filter chips, tabs, sort and small buttons now look like the Routely card on the dashboard, with
  no fill of their own (the page shows through), a 1px neutral border and a 12px radius (they were `#1e1e2d` and zinc `#27272a` fills on the darker page). It is done through the `--mp-*` tokens
  so hover, active and focus states keep working; the coloured banners and action buttons are unchanged.
- Review fixes: `Migrator::baseline()` refuses to run inside a caller's database transaction (creating the record table would commit it), before any DDL runs; the Community page treats a
  version answer without a `version` as unavailable instead of showing it; and the updater clears the rollback permission ("Allow an older version") when a run ends or fails, so it does not
  carry over to the next one.
- Fixed: the status tabs, header buttons, CSV, pagination and the view page's toolbar buttons had lost their hover state when the cards were made transparent (the resting style was more
  specific than the hover rule). Hover again gives a soft indigo fill and border (red for Delete) on the customers list, Active Customers and the customer pages.
- Customers pages (core theme: the list, Active Customers, view, add and edit): the cards now look clean on the dashboard, with no fill of their own (the page shows
  through), a 1px neutral border and a 12px radius, and the toolbar, table header, tabs, pagination and header buttons follow (lightly tinted or transparent, neutral borders). Fields
  inside an input group now have the same fill as the plain fields, and the eye, Generate and GPS buttons use the page colours (they were pale in dark mode).
- Dashboard (core theme, both dashboards): every card now looks uniform, with no fill of its own (the page shows through), a 1px neutral border (`rgba(127,127,127,.28)`)
  and a 12px radius: the summary cards, the chart and list boxes, the System Status panel with its resource and service tiles, the online-users boxes and the date bar. It replaces the
  card-coloured fill from the last change.
- The admin page background is now the menu's background (white in light mode, `#0f141c` in dark; it was `#f6f9fc` and `#151521`), and the top bar follows it, so the menu, top bar, page and footer
  are one colour and the cards stand on it. The cards are unchanged. Core theme only.
- Rollback Update: the version search box has the same look and focus as the other search boxes (38px, 8px radius, one accent border and soft ring instead of a browser outline plus a
  border, readable text in dark mode, no browser autocomplete suggestions).
- Fixed: on the customer page, showing or hiding the Password and PPPoE Password made the box jump (it grew about 34px and the eye icon changed width). The field and both buttons now have
  fixed sizes, the two eye icons share one width, and the script swaps the icon's class instead of rebuilding the button.
- Dashboard (core theme, both dashboards): every card, the four summary cards, the chart boxes, the cron and gateway cards and the date bar, now share the surface, a 1px border
  and a 14px radius of the cards on the other pages (they had three radii, 10, 12 and 15px, and a grey-purple date bar). The cron warning keeps its red heading.
- Typed text can no longer turn black on the dark surface: the customers and Active Customers search boxes turn browser autocomplete off, and the search and form fields set their text
  colour for the autofill and history preview too (the preview paints the suggested value black).
- The results list under the top-bar search uses the card surfaces too (it was zinc grey beside the navy cards in dark mode), the focused search box no longer turns black, and the pagination
  under the customers list and Active Customers follows the same colours. Core theme only.
  - Fixed: the quick actions listed an action twice when two of its keywords matched the typed text (typing "d" showed Add Customer and Redis Cache twice). Each action shows once.
- The search box in the top bar and the search box and filter selects on the customers list and Active Customers now share one look, the surface of the form fields on the add and edit
  pages (white, or `#1e1e2d` with a `#2d3748` border in dark mode), at the same 38px height and 8px radius. They were two different shades and shapes. Core theme only.
- Focus states on the customers list, Active Customers, add and edit: one soft indigo ring instead of a browser outline plus a border plus a halo (the list search showed a double
  ring), and in an input group (username, email, phone, password) the ring goes round the addon and the field together. In dark mode a focused field kept turning white because of a
  global `input:focus` rule; it now keeps the page surface colour.
- Customer add, edit and view pages (default theme) now share the look of the new customers list: rounded 14px cards without shadows, one neutral button style with the indigo
  primary (Login as Customer, Edit, Delete and Sync were four different colours), the same dark-mode surface colours as the list (the cards were slate blue, `#1e293b`, beside the list's `#1e1e2d`), a red-on-hover Delete, 36px rounded form fields with a focus ring, and pill badges. Styles only:
  no markup or behaviour changed.
- Active Customers (`plan/list`) now matches the new customers list: Active / Expired tabs with counts, a search bar with location and plan filters, a status pill, Edit and
  Extend on the row with a delete button, and a card layout on phones; light, dark. The add, edit and view pages already used the newer layout.
  - Fixed: the search was read from the form post only, so it was lost on the next page and in the tabs, and the location filter was dropped from the page links (it used an
    undefined variable). Both now travel in the address.
- Customers list redesigned (default theme): status tabs with counts (All, Active, Banned, ...), a search bar with sort and page-size beside it, one row per customer
  (avatar, name, username, account type, phone and email, package, service and PPPoE, balance, status pill, created date), View and Recharge on the row with Edit
  and Sync under a "..." menu, a bar that appears once customers are ticked ("N selected, Send Message"), an empty state, and a card layout on phones. Light and
  dark; its page still gets the safer query below.
  - Fixed: the search text was pasted into the SQL (`LIKE '%$search%'`), so a crafted search ran SQL, and `AND status = ...` only bound the last of the OR terms,
    so the status filter did not apply to a search. The text is now a bound value, the status filter applies to the whole search, and the sort column, direction and
    status are checked against known values.
  - Fixed: a customer with no email (stored as `1`) or no phone (stored as `0`) showed a mail and a phone button; they show only for a real address or number.
- Rollback Update page redesigned: a note on what a rollback does (program files only, the database is not undone), Master and Development tabs with a search box, and one
  row per version with its message, author, date, a link to the commit and a "Roll back to this" button; the page works on a phone and follows light, dark. The Updates card on Community now lays its six buttons out in an even grid that reflows instead of clipping.
  - Fixed: commit messages were printed as HTML (a commit message could inject markup into the admin's page); they are escaped now.
  - Fixed: when GitHub refused the request (the anonymous limit is 60 an hour) the page tried to list the error as commits. The list is kept for ten minutes in
    `system/cache/rollback_*.json`, an error answer is ignored, and an empty list says so. The page is SuperAdmin only, like the updater.
- Community page redesigned: an Updates card that says whether you are up to date (versions compared properly, not as text), update, download, old-version and changelog
  actions, a documentation link, help and support tiles, collapsible donation details and a compact credits list. It follows the light and dark switch. The install and database buttons show for a SuperAdmin only (they were shown to every admin and then refused by `update.php`).
- Fixed: "Select Old Version" no longer worked with the new updater (the link's address was ignored, and a package older than the installed one was refused). The link now
  fills the address and ticks **Allow an older version** on the updater's first page; nothing downloads until the button is pressed, and a rollback needs that box.
  The updater also accepts GitHub commit and tag zips, which the list links to. The update guide is on the website: Docs > Using the system > Updating and backups.
- `Migrator` lock is per database (sites sharing a MySQL server no longer block each other), and `system/cron/migrate.php` never guesses the starting point of a
  site with no migration record: it asks for `--baseline=VERSION`, which also honours an old `updates.done.json`.
- The updater looks at the database work before any file is replaced (step 2), and `php system/cron/migrate.php --status` shows the same: how many changes will run,
  the largest tables they touch with rows and size, and it refuses to go on if the disk where the database lives is too small to rewrite the largest table,
  and warns (without stopping) when the database account does not appear to have ALTER/CREATE/DROP/INDEX. A refusal, or a check that cannot be completed, replaces no program file and runs no
  pending change (the small `tbl_migrations` record table may already have been created).
- Customers see the maintenance page while the database step runs (web updater and `system/cron/migrate.php`), and it is lifted when the last change is done or
  not at all if a change failed. A maintenance mode the admin switched on themselves is never turned off. Staff pages keep working.
- Install and upgrade now produce the same database (checked by building one from `install/phpnuxbill.sql` and another from the 2025 install plus every
  migration, then comparing columns and indexes):
  - Fixed: a new install had no `tbl_voucher.batch_name`, which the voucher screens and `plan.php` use. Migration `2026.11.7` adds it to sites that lack it,
    gives `tbl_customers.referral_code` its NULL default and unique index on upgraded sites, and widens `tbl_referral_relationships.referral_code` to 25.
  - Fixed in `Migrator`: a guarded change (`SET @s` / `PREPARE` / `EXECUTE`) that resumed halfway failed with "Unknown prepared statement handler", and an
    `EXECUTE` answering with a result set blocked the next statement. Such a group now runs, and re-runs, as a whole.

- Database updates rebuilt around `Migrator`:
  - Fixed: which database changes had run was kept in `system/cache/updates.done.json`, written once at the very end. A request that died halfway recorded nothing,
    clearing the cache folder replayed all 70 versions, and a new install replayed them on its first update (including data fixes such as the widget resets,
    which do not fail on a second run, they silently change data). The record is now the table `tbl_migrations`, one row per statement, written as each
    statement finishes.
  - A new install records every version as applied; a site with no record counts everything up to its installed version as applied (an old `updates.done.json`
    is honoured too), so only newer changes run.
  - The run stops at the first real error and reports the statement; nothing after it runs, and "Try this step again" resumes from there. Web runs work in
    20-second slices at version boundaries so no request outlives a proxy timeout; statements that belong together (`SET @s` / `PREPARE` / `EXECUTE`) are never split.
  - "Already exists" answers are skipped, but a duplicate-key error on an `ALTER` (duplicate data blocking a unique key) is now a real failure, not hidden.
  - `php system/cron/migrate.php` applies, `--status` shows pending and failed, `--baseline=VERSION` sets the starting point: use it for large databases,
    where a table change can run longer than a web request. One run at a time, from any entry point (`GET_LOCK`).
- Send Message and Bulk Messages review:
  - Fixed: bulk to Active or Expired customers without a router sent nothing (the rows had no phone or name), and inbox and push in bulk went to customer 0
    for All and New. One query (`MessageSender::recipients()`) now serves every group, returns each customer once in a fixed order, and counts the group
    before paging, so batches no longer repeat, skip or run empty.
  - Fixed: failures were reported as sent. SMS, WhatsApp and email now return a real result, a push without subscriptions is "skipped", and every screen
    shows sent, failed and skipped per channel. A customer with no phone or email is skipped for those channels only, so inbox and push still reach them.
  - Fixed: the bulk history table inserted customer names and messages as HTML (a name typed at registration could run script in the admin's browser);
    placeholder values are now escaped for email and inbox, and the table escapes everything it shows.
  - Fixed: the three send actions had no CSRF check and the bulk sender accepted a GET; the batch size went unchecked into the SQL LIMIT. They now need a POST
    with the token, and the batch size is one of the dropdown values.
  - `[[payment_link]]` is now an absolute link to the customer's active recharge (also in "Send to selected", which skipped placeholders altogether).
  - Added: a pause between SMS and WhatsApp sends, a confirmation before a real bulk send, a progress count and a skipped count, and the subject field of the bulk
    page works again.
- New look for the installer and the updater: a dark panel with the five steps as numbered ports with status lights (blue = current, green = done, red = failed,
  all five light up on the last page), a calm form on the right, light and dark colour schemes, and a step strip on phones. The installer gains a "Test the
  connection" button, a show/hide toggle and strength meter for passwords, and a clear confirmation before replacing an existing database. The updater page
  keeps its CSS inline because it replaces `ui/` while it runs. The Bootstrap copy in `install/css/` is gone; the installer uses `install/css/installer.css`.
- Installer and updater review:
  - Fixed: the installer could be re-run on a live site. `install/step4.php` rewrote `config.php` (pointing the site at the visitor's database, a full
    takeover) and the setup script starts with DROP TABLE. Every installer page now refuses to run once `config.php` exists, and step 4 never wipes a database
    that already holds PHPNuxBill tables unless told to.
  - Fixed: `config.php` was built by pasting the form values into PHP code, so a database password could inject code. Values are now written as escaped string
    literals; `config.php` is written last, so a failed install leaves nothing half-done; errors in any statement of the setup script are reported.
  - There is no default `admin` / `admin` any more: the installer asks for the admin password (10+ characters, stored with bcrypt). The requirements page
    checks what the app really needs (PHP 8.2, mbstring, curl, gd, zip, writable folders), the database password field is a password field, and step 5 can delete
    the install folder. The old `install/update.php` migration ran without sign-in and is retired.
  - `update.php` is now SuperAdmin only (any staff member could replace the program files), starts only from a button and a POST with a token (opening it did start
    a download, so a link on another site could trigger an update from an address of the attacker's choosing), takes only an https GitHub branch zip or the address
    in `config.php`, checks the download (200, a real zip, size), refuses unsafe zip paths and packages that are not PHPNuxBill or are older than the installed
    version, backs up the replaced folders to `system/cache/backups/` and puts them back when the copy fails, and reports database errors instead of hiding them
    (a version with a real error is retried next time; absent FreeRADIUS tables are skipped). An update started by the previous updater continues with
    `update.php?step=4`.
  - Fixed: `system/uploads/notifications.default.json`, which `init.php` needs at boot, was never committed (`.gitignore` ignored all of `system/uploads/`), so a copy
    of the repository stopped at "file not found". `.gitignore` now lets it through; add the file to git.
- Sign-in, registration and password-reset hardening:
  - Fixed: the CSRF setting did not work. `boot.php` made a new token on every request, including the POST that was about to be checked, so a submitted
    token could never match. `Csrf::generateAndStoreToken()` now returns the session's token while it is valid. Registration (both steps) and
    voucher activation now check the token too, and their forms carry it.
  - Fixed: entering a customer's username with a voucher of your own replaced that customer's password with the voucher code. An existing
    account's password is no longer changed by a voucher; voucher activation is also CSRF-checked and limited per address.
  - New `AuthThrottle`: attempts are counted on the server (per username from one address, per address, per username), not in the visitor's session,
    for customer login (web and API), admin login, forgot password, registration codes and voucher activation. Forwarding headers are believed only
    from an address listed in the setting `trusted_proxies` (addresses or CIDR ranges, comma separated). Behind a reverse proxy set it (for nginx on
    the same machine: `127.0.0.1`, behind Cloudflare also its ranges); private addresses are not trusted by default because hotspot visitors have them.
  - New `Otp`: codes come from `random_int`, only a keyed hash is stored, a code lives 10 minutes and dies after 5 wrong tries. Forgot password
    no longer shows a generated password: after a right code the customer chooses a new one, is told by SMS/email that it changed, and every session and
    remember-me cookie opened with the old password ends. Answers no longer reveal whether a username, phone or email exists.
  - Staff passwords are now bcrypt (`Password::_crypt`); the old unsalted SHA-1 still works and is upgraded on the first good login. Customer
    passwords stay in clear text because FreeRADIUS/hotspot need them. Password comparisons use `hash_equals` (loose `==` treated "1e3" as "1000").
  - Admin login regenerates the session id. Remember-me cookies are HttpOnly + SameSite=Lax (Secure on https), die on logout and on password change,
    and the session cookie is HttpOnly + SameSite=Lax with strict mode. Customers need to sign in once after this update (the mobile app too).
  - Registration has an "I agree to the Terms and Conditions" box (links to `pages/Terms_and_Conditions.html`): the register button stays off
    until it is ticked (and until the captcha is solved), and the server refuses a registration without it. The setting `register_terms = no`
    turns it off.
  - Fixed: the Privacy and T & C links in the footer of the sign-in, register and forgot-password pages went to routes that did not exist. They now open
    public pages (`?_route=privacy`, `?_route=terms`, no sign-in needed) that show the text of Pages > Privacy Policy and Terms and Conditions in the
    same look as the sign-in pages; the register checkbox and the custom "moon" pages link there too.
  - New customer passwords must be at least `min_password_length` (default 6) and not trivial; the public register/forgot pages show their messages
    and escape what they echo back.
- Marketplace packages from your own server or CDN: a `repository.json` entry (or a plugin's `plugin.json`) can name `zip`, an https address
  of a `.zip`, instead of `github`, with an optional `sha256` the download must match. Redirects stay on https and a package may be up to
  50 MB. Marketplace > Plugins (and gateways, devices, themes) also accepts a package address with an optional SHA-256. `PLUGINS.md` >
  Publishing and serving updates.
- Signed Marketplace packages: a `zip` entry can carry `version`, `public_key` and `signature` (Ed25519, made with `php skeletons/build.php
  keygen|pubkey|sign`). The signature covers type, id, version and the zip's SHA-256. The first install remembers the key; after that only
  the same key can sign updates, and an unsigned or differently signed entry is refused. `PLUGINS.md` > Signing your releases.
  Downloads also refuse internal/private hosts at every redirect, packages with more than 5,000 files or over 200 MB unpacked.
- Marketplace licences: an extension can now run its own licence rules with `register_license($slug, $statusFn, $saveFn, $type)`:
  the developer owns the key format, server and storage, and the core only shows the License button, the badge and the result
  (and keeps **Activate** disabled until the state is `valid`). A manifest flag is editable by the customer, so it is now documented
  as a reminder only. `PLUGINS.md` > Licensing.
- Licensing for payment gateways and device drivers: `"license_provider": true` in `gateway.json` / `device.json` makes the core include
  the file once to find its `register_license()`; the extension is not used (checkout, status check, webhook, plans) until the
  developer's status function says `valid`. The skeletons for plugins, gateways, devices and themes now demonstrate every
  developer API with comments (`skeletons/*`), `build.php new device` also renames the lowercase helper prefix, and the payment
  gateway controllers `include_once` the gateway file.
- Licence tiers: the status callback of `register_license()` can return `features`, `license_has($slug, $feature)` tells whether
  the licence unlocks one, and a menu or submenu in `plugin.json` can carry `"feature": "name"` to be hidden without it.
- Marketplace licences (manifest mode): an extension with `requires_license: true` is now **not run** until a valid key is saved, not just
  blocked from the Activate button. Plugins are not loaded (no menu, route or hook), payment gateways are not used at checkout,
  in the status check or in the callback, device drivers are not used by plans, and a theme falls back to the default look.
  Installing is unchanged, and Marketplace still lists the extension and takes the key. New optional manifest key
  `license_public_key` (base64 Ed25519): the `license_check_url` answer must then carry `expires` and a `signature` over
  `type|slug|host|key|expires|valid`, which is checked locally on every request, so editing the database or copying the key to another
  domain no longer works. `php system/cron/marketplace_sync.php` asks again before a signed answer expires. Details in
  `PLUGINS.md` > Licensing.

- Admin: the side menu now has a white and a dark theme that follow the system / light / dark switch (before, it was navy in
  both, and each item in dark mode was its own boxed row). Covers the open menu, submenus, the collapsed icon rail and the
  flyouts that open beside it, and the logo cell above the menu. The footer is now pinned to the bottom of the screen (it starts
  where the menu ends, and the page ends above it). Styles: `ui/ui/styles/admin-chrome.css`. A theme with its own menu and
  footer styles is not affected.
- Customer portal: new header, menu, mobile bar and footer with a light and a dark theme that follow the existing system /
  light / dark switch (before, the header and menu were always dark). Frosted top bar with a logo mark, balance as a pill,
  rounded account / inbox / language dropdowns, rounded menu items, a floating mobile bar with a soft active-tab pill, and a
  slim footer that clears the bar. The raised Buy button keeps its place and markup. The saved theme is applied before the
  page paints (no light flash). The account dropdown no longer dims the whole page. Styles: `ui/ui/styles/customer-modern.css`.
- Themes: a theme can restyle the customer portal chrome by shipping `styles/customer.css` (loaded after the core styles,
  parent theme first).

- Plugins: `?_route=plugin/<function>` now runs only functions a plugin has **declared**. A function is declared by its
  menu (`register_menu`, `register_submenu`, or `"menus"` in `plugin.json`) or as a route: `register_route($function,
  $access)` or `"routes": [{"function": "...", "access": "public|admin|customer"}]` in `plugin.json`. `admin` and `customer`
  routes are sign-in checked by the core before the function runs; `public` is for webhooks, callbacks and a plugin's own API.
  Built-in PHP functions, functions outside `system/plugin/` and functions that need arguments can no longer be called this way.
  (Before, `plugin/phpinfo` ran PHP's own `phpinfo()`.)
- **Transition**: Settings > General Settings > Security > *Plugin Routes* (`plugin_routes_mode`). The default, *warn*, still runs an undeclared plugin
  function and writes one log line a day for it; *enforce* refuses it. The default will become *enforce* in a later
  release. Update a plugin: declare its routes, then `php skeletons/build.php check plugin system/plugin/<slug>`.
- New developer guide `PLUGINS.md`; the plugin skeleton declares a sample public webhook route. It now also explains how to publish
  a plugin and serve updates (versions, `update_url`, `repository.json`, what the Update button does, migrations), and how payment
  gateways, device drivers and themes differ (manifests, layout, contracts, package detection, what an update replaces).
- Hotspot plugin 3.9.0 declares its routes and fixes two missing sign-in checks found while doing so: `hotspot_GenerateVoucher`
  trusted `is_admin` / `generate_by` from the request (voucher generation without signing in, or charging another merchant),
  and `hotspot_block_mac` could be used by anyone (now admin only, with a CSRF token).

## 2026.8.25

- Fixed: plugin menus registered with `admin = true` (e.g. Skeleton, My Plugin) showed in the customer portal sidebar whenever an admin session was open in the same browser; admin and customer menus are now kept apart (`_menu_publish()`, called by `_admin()` / `_auth()`)
- Skeleton sample plugin now has both an admin and a customer menu with submenus (SVG icons); the plugin starter used slot names the templates never print (`DASHBOARD`, `ACCOUNTS`) and a submenu in a different slot than its parent, so its menus never showed - fixed, and the slot lists in the docs now match the templates
- Customer portal sidebar: plugin menus with submenus are styled (stray second chevron removed, font icons aligned with the built-in 24px icons, submenu indented under the parent label, long names cut with an ellipsis while the sidebar is expanded, chevron turns when the group opens)
- Default theme, collapsed sidebar: the icon rail scrolls when it is taller than the window (last items such as Community were cut off); each submenu flyout is one card with its title inside it, and long menus (Settings) stay inside the screen and scroll
- Dashboard data (dynamic metrics and classic widgets) is cached in `system/cache/dashboard/dash_*.temp` (folder is blocked over HTTP) for the dashboard TTL when Redis is not enabled or not reachable; the Refresh button clears it
- Dashboard, Services Status Matrix: a "Server Clocks" card shows the server (OS) time, PHP time and database time with their timezones, ticking live, with an "In sync" / "Clock skew" / "Timezone differs" badge
- Dashboard: the FreeRADIUS status no longer shows "Offline - Connection Error" on REST setups (freeradius-rest, no separate radius database). Without a radius database it is online when a FreeRADIUS process is running on the server (same `pgrep` check as the System Info plugin) or FreeRADIUS has called `radius.php` / written `rad_acct` within 24 hours, and the card says which
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
- Fixed: global `min-height:100%` rule collapsed marketplace cards; the marketplace showed dark colours in light mode in themes that remap its tokens

## 2026.5.23

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