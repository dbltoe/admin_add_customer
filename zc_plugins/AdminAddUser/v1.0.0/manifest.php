<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: manifest.php 2026-07-26 05:30:22Z dbltoe $
 */
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Admin Add Customer',
    'pluginDescription' => 'Lets a store admin capture minimal customer details (name and email; phone is optional) one-by-one or via CSV bulk upload - e.g. at a public event - optionally assigning a pricing group and/or a wholesale level. Each new customer is emailed an activation link; their account stays pending until they click it and complete their own registration, including their address.',
    'pluginAuthor' => 'dbltoe based on the add_customers_from_admin mod by Vinos de Frutas Tropicales (lat9)',
    'pluginId' => '1477',
    // -----
    // The latest actual tagged Zen Cart release is v2.2.2; the next release is v3.0.0
    // (there is no separate v2.3.0 milestone). This plugin doesn't depend on any
    // version-specific core internals beyond what's confirmed present since v2.0.0
    // (customers_whole/Wholesale Pricing) or its own self-contained schema, so it's listed
    // as compatible across that whole range.
    //
    'zcVersions' => ['v2.0.0', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v3.0.0'],
    'changelog' => 'readme.html',
    'github_repo' => 'https://github.com/dbltoe/admin_add_customer',
    'pluginGroups' => [],
];
