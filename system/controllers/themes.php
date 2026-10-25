<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

// Theme management lives in the Marketplace now; keep the old route working.
_admin();
r2(getUrl('marketplace/themes'));
