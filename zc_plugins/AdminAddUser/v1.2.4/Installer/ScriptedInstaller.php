<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
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
