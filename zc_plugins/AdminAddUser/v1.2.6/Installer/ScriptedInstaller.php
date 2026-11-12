<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @version $Id: ScriptedInstaller.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// filenames.php (which defines TABLE_ADMIN_ADD_USER_TOKENS, among others) is normally
// auto-loaded for installed plugins, but this plugin isn't marked installed until
// executeInstall() below finishes - so on a fresh install it isn't loaded yet by the time
// this file runs. Requiring it explicitly avoids depending on that ordering.
//
require dirname(__DIR__) . '/filenames.php';

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        zen_deregister_admin_pages(['customersAddCustomer']);
        zen_register_admin_page('customersAddCustomer', 'BOX_CUSTOMERS_ADD_CUSTOMERS', 'FILENAME_ADD_CUSTOMERS', '', 'customers', 'Y');

        // -----
        // This plugin's own activation-token table, used by the catalog-side
        // admin_add_user_activate.php landing page. Kept independent of core's own (v2.2.0+
        // only) activation-token system so this works identically across the whole
        // v2.0.0-through-current-master compatibility range.
        //
        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_ADMIN_ADD_USER_TOKENS . " (
            token_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            customers_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            coupon_id INT NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_token (token),
            KEY idx_customers_id (customers_id)
        ) ENGINE=InnoDB";
        $this->executeInstallerSql($sql);

        // -----
        // Optional resale/tax-exemption number for wholesale customers. `customers_whole`
        // itself has been core schema since v2.0.0, so it's safe to anchor the new column
        // to it without an existence check.
        //
        global $db;
        $column_check = $db->Execute("SHOW COLUMNS FROM " . TABLE_CUSTOMERS . " LIKE 'customers_tax_number'");
        if ($column_check->EOF) {
            $sql = "ALTER TABLE " . TABLE_CUSTOMERS . " ADD COLUMN customers_tax_number VARCHAR(32) NULL DEFAULT NULL AFTER customers_whole";
            $this->executeInstallerSql($sql);
        }

        $this->aacRefreshPluginDescription();
    }

    // -----
    // Every step above is safe to repeat, so an upgrade is a re-run of the install.
    // Zen Cart v2.0.0 passes the old version; nothing here depends on it.
    //
    protected function executeUpgrade($oldVersion = null)
    {
        return $this->executeInstall();
    }

    // -----
    // Save the manifest's description (the Plugin Manager panel, with its ReadMe button)
    // again. Zen Cart v2.0 and v2.1 store plugin_control.description only when Plugin
    // Manager first sees the plugin, so without this an upgraded store would keep the
    // previous version's panel, whose ReadMe button pointed at a file that no longer
    // exists. v2.2 and later rewrite the description from the manifest on every Plugin
    // Manager visit, so there this changes nothing.
    //
    protected function aacRefreshPluginDescription()
    {
        if (!defined('TABLE_PLUGIN_CONTROL')) {
            return;
        }
        $manifest = require dirname(__DIR__) . '/manifest.php';
        if (!is_array($manifest) || !isset($manifest['pluginDescription'])) {
            return;
        }
        $this->executeInstallerSql(
            "UPDATE " . TABLE_PLUGIN_CONTROL
            . " SET description = '" . $this->dbConn->prepare_input($manifest['pluginDescription']) . "'"
            . " WHERE unique_key = 'AdminAddUser'"
        );
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(['customersAddCustomer']);

        // -----
        // Plugin-owned table, safe to drop outright. customers_tax_number is left in place
        // since it's real data on real customer records, not something an uninstall should
        // delete.
        //
        $sql = "DROP TABLE IF EXISTS " . TABLE_ADMIN_ADD_USER_TOKENS;
        $this->executeInstallerSql($sql);
    }
}
