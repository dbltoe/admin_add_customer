<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @version $Id: extra_datafiles/filenames.php 2026-08-03 17:27:49Z dbltoe $
 */
// -----
// Catalog-side counterpart to admin/includes/extra_datafiles/filenames.php - see that file's
// comments. Without this, FILENAME_ADMIN_ADD_USER_ACTIVATE would never get defined on the
// storefront on ZC v2.0.0/v2.1.0, breaking the activation link in every welcome email on
// those versions.
//
require dirname(__DIR__, 3) . '/filenames.php';
