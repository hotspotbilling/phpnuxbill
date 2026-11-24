{include file="sections/header.tpl"}
{*
    Shown by radius.php instead of the NAS pages while the RADIUS database cannot be used. $problem: why (config, enabled,
    database, tables, columns, login, connect), config / enabled (whether those steps are done), host, name, detail (the database's
    own message, when there is one).
*}
{$why = $problem['why']}
<div class="ls st">
    <section class="ls-card">
        <header class="st-card-head">
            <div>
                <h3><i class="fa fa-database" aria-hidden="true"></i>
                    {if $why == 'config'}{Lang::T('The RADIUS database is not set up yet')}
                    {elseif $why == 'enabled'}{Lang::T('RADIUS is turned off')}
                    {elseif $why == 'database'}{Lang::T('The RADIUS database does not exist')}
                    {elseif $why == 'driver'}{Lang::T('PHP cannot use MySQL here')}
                    {elseif $why == 'tables'}{if $problem['table']|default:'nas' == 'nas'}{Lang::T('The RADIUS database is missing the NAS table')}{else}{Lang::T('The RADIUS database is missing the log table')}{/if}
                    {elseif $why == 'columns'}{Lang::T('The RADIUS NAS table needs an upgrade')}
                    {elseif $why == 'login'}{Lang::T('PHPNuxBill cannot sign in to the RADIUS database')}
                    {else}{Lang::T('The RADIUS database cannot be reached')}{/if}
                </h3>
                <small>{if $problem['table']|default:'nas' == 'nas'}{Lang::T('Network Access Servers (NAS) are kept in the FreeRADIUS database, separate from the PHPNuxBill one. Set it up once with the steps below, then open this page again.')}{else}{Lang::T('The RADIUS logs are kept in the FreeRADIUS database, separate from the PHPNuxBill one. Set it up once with the steps below, then open this page again.')}{/if}</small>
            </div>
        </header>
        <div class="st-card-body">
            {if $problem['detail']|default:'' != ''}
            <p class="rt-err rt-err--block"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {$problem['detail']|escape}</p>
            {/if}

            <ul class="rt-checks">
                {if $why == 'driver'}
                <li class="rt-check rt-check--bad">
                    <i class="fa fa-times-circle" aria-hidden="true"></i>
                    <div>
                        <b>{Lang::T('Install the PHP MySQL driver')}</b>
                        <small>{Lang::T('PHP has no pdo_mysql extension, so it cannot connect to any MySQL database. Install it, for example on Docker or Debian:')}</small>
<pre class="nf__code">docker-php-ext-install pdo_mysql mysqli
# or: apt-get install php-mysql</pre>
                        <small>{Lang::T('Then restart PHP (or the web server) and open this page again.')}</small>
                    </div>
                </li>
                {/if}
                {if $why == 'columns'}
                <li class="rt-check rt-check--bad">
                    <i class="fa fa-times-circle" aria-hidden="true"></i>
                    <div>
                        <b>{Lang::T('Add the routers column to the nas table')}</b>
                        <small>{Lang::T('PHPNuxBill saves the router a NAS belongs to in a routers column, and the nas table of this database does not have it yet. Run only this in phpMyAdmin (SQL tab) on the RADIUS database: it adds the column and changes nothing else.')}</small>
<pre class="nf__code">ALTER TABLE `nas` ADD COLUMN IF NOT EXISTS `routers` varchar(32) NOT NULL DEFAULT '';</pre>
                        <small>{Lang::T('On a MySQL version without ADD COLUMN IF NOT EXISTS, leave out the words IF NOT EXISTS (it is only run once).')}</small>
                    </div>
                </li>
                {/if}
                {* known to be missing only once the database was reached (or the server said it has no such database) *}
                <li class="rt-check rt-check--{if $why == 'database' || $why == 'tables'}bad{else}warn{/if}">
                    <i class="fa {if $why == 'database' || $why == 'tables'}fa-times-circle{else}fa-exclamation-circle{/if}" aria-hidden="true"></i>
                    <div>
                        <b>1. {Lang::T('Create the RADIUS database and its tables')}</b>
                        <small>{Lang::T('In your hosting panel (cPanel: MySQL Databases) create a database and a user for it. Then, under Add User To Database, add that user to the database and tick ALL PRIVILEGES. Then import install/radius.sql from the PHPNuxBill folder into it (phpMyAdmin: Import). From a terminal:')}</small>
                        <pre class="nf__code">mysql -h RADIUS_HOST -u RADIUS_USER -p RADIUS_DATABASE &lt; install/radius.sql</pre>
                        <small>{Lang::T('Use the same host as $radius_host in config.php (step 2 below).')}</small>
                        <small>{Lang::T('Import the whole file only into a new, empty database: it replaces the RADIUS tables it creates, with the logins and accounting in them.')}</small>
                        {if $why == 'tables' && $problem['table']|default:'nas' == 'nas'}
                        <small>{Lang::T('The database exists, but its nas table is missing. If the database is new and empty, import install/radius.sql as above. If FreeRADIUS already uses it (it has tables such as radcheck and radacct), do not import the file: run only this in phpMyAdmin (SQL tab), which adds the nas table and changes nothing else:')}</small>
<pre class="nf__code">CREATE TABLE IF NOT EXISTS `nas` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `nasname` varchar(128) NOT NULL,
  `shortname` varchar(32) DEFAULT NULL,
  `type` varchar(30) DEFAULT 'other',
  `ports` int(5) DEFAULT NULL,
  `secret` varchar(60) NOT NULL DEFAULT 'secret',
  `server` varchar(64) DEFAULT NULL,
  `community` varchar(50) DEFAULT NULL,
  `description` varchar(200) DEFAULT 'RADIUS Client',
  `routers` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `nasname` (`nasname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;</pre>
                        {elseif $why == 'tables'}
                        <small>{Lang::T('The database exists, but its radpostauth table (the log of sign-ins) is missing. If FreeRADIUS already uses this database, do not import install/radius.sql: run only this in phpMyAdmin (SQL tab), which adds that table and changes nothing else:')}</small>
<pre class="nf__code">CREATE TABLE IF NOT EXISTS `radpostauth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '',
  `pass` varchar(64) NOT NULL DEFAULT '',
  `reply` varchar(32) NOT NULL DEFAULT '',
  `authdate` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `class` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;</pre>
                        {/if}
                    </div>
                </li>
                <li class="rt-check rt-check--{if $problem['config']}ok{else}bad{/if}">
                    <i class="fa {if $problem['config']}fa-check-circle{else}fa-times-circle{/if}" aria-hidden="true"></i>
                    <div>
                        <b>2. {Lang::T('Add its login to config.php')}</b>
                        <small>{Lang::T('In config.php, in the PHPNuxBill folder, add these lines with your own values:')}</small>
<pre class="nf__code">$radius_host = 'localhost';
$radius_user = 'RADIUS_USER';
$radius_pass = 'RADIUS_PASSWORD';
$radius_name = 'RADIUS_DATABASE';</pre>
                        {if $problem['config']}<small>{Lang::T('Found in config.php')}: {$problem['name']|escape}{if $problem['host'] != ''} @ {$problem['host']|escape}{/if}. {if $why == 'login'}{Lang::T('Check the user name and password there.')}{elseif $why == 'database'}{Lang::T('Check the database name there.')}{elseif $why == 'connect'}{Lang::T('Check the host there, and that the database server is running.')}{/if}</small>{/if}
                    </div>
                </li>
                <li class="rt-check rt-check--{if $problem['enabled']}ok{else}bad{/if}">
                    <i class="fa {if $problem['enabled']}fa-check-circle{else}fa-times-circle{/if}" aria-hidden="true"></i>
                    <div>
                        <b>3. {Lang::T('Turn RADIUS on')}</b>
                        <small>{Lang::T('Settings > General Settings, Radius tab: set Enable Radius to Yes and save.')}</small>
                    </div>
                </li>
            </ul>
        </div>
        <footer class="st-card-foot">
            <a class="ls-btn" href="{Text::url('settings/app')}"><i class="fa fa-cog" aria-hidden="true"></i> {Lang::T('General Settings')}</a>
            <a class="ls-btn ls-btn--primary" href="{$retry_url|default:Text::url('radius/nas-list')}"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Check again')}</a>
        </footer>
    </section>
</div>
{include file="sections/footer.tpl"}
