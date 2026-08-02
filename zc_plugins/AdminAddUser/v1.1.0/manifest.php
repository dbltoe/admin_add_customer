<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: manifest.php 2026-08-02 12:32:04Z dbltoe $
 */
// -----
// Read Me / GitHub buttons shown in the Plugin Manager's info box, matching the pattern
// established for Social Contact Footer (f:/Social_Contact_Footer). The Read Me URL is
// derived from this file's own on-disk location (rather than a hardcoded version string)
// so it can't go stale on a future version bump, the same fix already applied to
// getHeaderImageUrl() in admin/includes/classes/addCustomers.php. Zen Cart's shipped
// zc_plugins/.htaccess denies everything then explicitly re-allows .html, so readme.html
// is reachable by design.
//
$aacPluginRelativeDir = basename(dirname(__DIR__)) . '/' . basename(__DIR__);
$aacReadmeUrl = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . 'zc_plugins/' . $aacPluginRelativeDir . '/readme.html';
$aacGithubUrl = 'https://github.com/dbltoe/admin_add_customer';
$aacForumUrl = 'https://www.zen-cart.com/showthread.php/231166-Admin-Add-Customer';
$aacButtonGap = '6px';
$aacLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $aacButtonGap . '">'
    . '<a href="' . $aacReadmeUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $aacButtonGap . ' 0 0">Read Me</a>'
    . '<a href="' . $aacGithubUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $aacButtonGap . ' 0 0">GitHub</a>'
    . '</div>'
    . '<div style="margin:6px 0 0;padding:0 0 0 ' . $aacButtonGap . '">'
    . '<a href="' . $aacForumUrl . '" target="_blank" rel="noopener noreferrer">Forum Support Thread</a>'
    . '</div>';

return [
    'pluginVersion' => 'v1.1.0',
    'pluginName' => 'Admin Add Customer',
    'pluginDescription' => 'Lets a store admin capture minimal customer details (name and email; phone is optional) one-by-one or via CSV bulk upload - e.g. at a public event - optionally assigning a pricing group and/or a wholesale level. Each new customer is emailed an activation link; their account stays pending until they click it and complete their own registration, including their address.' . $aacLinks,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => '2445',
    // -----
    // The latest actual tagged Zen Cart release is v2.2.2; the next release is v3.0.0
    // (there is no separate v2.3.0 milestone). This plugin doesn't depend on any
    // version-specific core internals beyond what's confirmed present since v2.0.0
    // (customers_whole/Wholesale Pricing) or its own self-contained schema, so it's listed
    // as compatible across that whole range.
    //
    'zcVersions' => ['v2.0.0', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v3.0.0'],
    'changelog' => 'readme.html',
    'github_repo' => $aacGithubUrl,
    'pluginGroups' => [],
];
