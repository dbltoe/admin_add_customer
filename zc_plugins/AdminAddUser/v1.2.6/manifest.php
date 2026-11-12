<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @version $Id: manifest.php 2026-11-11 dbltoe $
 */
// -----
// ReadMe / Forum Thread / GitHub buttons shown in the Plugin Manager's info box.
//
// ReadMe opens README.md through the plugin's own admin page
// (index.php?cmd=admin_add_customer_readme): behind the admin login, superusers only, in
// its own window. It is shown only to superusers; the page enforces that too, because
// Zen Cart 2.0 and 2.1 keep the description they first saw. Forum Thread and GitHub go
// off-site, so they open in a new window with rel="noopener noreferrer"
// (zencart/zencart#8031). The link carries no version folder, so it can't go stale on a
// future version bump.
//
$aacReadmeUrl = 'index.php?cmd=admin_add_customer_readme';
// Zen Cart 3.0.0 reads every manifest on every admin page, early (template resolver),
// before admin access is set up: zen_is_superuser() there is a fatal "Undefined constant
// SUPERUSER_PROFILE" that takes down the whole admin. Ask only once that exists.
$aacShowReadme = true;
if (function_exists('zen_is_superuser') && defined('SUPERUSER_PROFILE')) {
    $aacShowReadme = zen_is_superuser();
}
$aacGithubUrl = 'https://github.com/dbltoe/admin_add_customer';
$aacForumUrl = 'https://www.zen-cart.com/threads/207238';
// The row is ReadMe | Forum Thread | GitHub, the house row for a plugin with no settings page
// (this one's admin page is the tool itself), at the house button spacing.
$aacButtonStyle = ' class="btn btn-primary" role="button" style="margin:0 4px 0 0;padding-left:8px;padding-right:8px"';
$aacLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 4px">'
    . ($aacShowReadme ? '<a href="' . $aacReadmeUrl . '" target="_blank" rel="noopener noreferrer"' . $aacButtonStyle . '>ReadMe</a>' : '')
    . '<a href="' . $aacForumUrl . '" target="_blank" rel="noopener noreferrer"' . $aacButtonStyle . '>Forum Thread</a>'
    . '<a href="' . $aacGithubUrl . '" target="_blank" rel="noopener noreferrer"' . $aacButtonStyle . '>GitHub</a>'
    . '</div>';

return [
    'pluginVersion' => 'v1.2.6',
    'pluginName' => 'Admin Add Customer',
    'pluginDescription' => 'Lets a store admin capture minimal customer details (name and email; phone is optional) one-by-one or via CSV bulk upload - e.g. at a public event - optionally assigning a pricing group and/or a wholesale level. Each new customer is emailed an activation link; their account stays pending until they click it and complete their own registration, including their address.' . $aacLinks,
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    'pluginId' => 2445,
    // -----
    // The latest actual tagged Zen Cart release is v2.2.2; the next release is v3.0.0
    // (there is no separate v2.3.0 milestone). This plugin doesn't depend on any
    // version-specific core internals beyond what's confirmed present since v2.0.0
    // (customers_whole/Wholesale Pricing) or its own self-contained schema, so it's listed
    // as compatible across that whole range.
    //
    'zcVersions' => ['v2.0.0', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v2.3.0', 'v3.0.0'],
    'changelog' => 'changelog.txt',
    'github_repo' => $aacGithubUrl,
    'pluginGroups' => [],
];
