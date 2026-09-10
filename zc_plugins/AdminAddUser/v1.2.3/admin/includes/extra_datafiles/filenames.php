<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: extra_datafiles/filenames.php 2026-08-03 17:27:49Z dbltoe $
 */
// -----
// A plugin's own root-level filenames.php is only auto-loaded by core starting at ZC v2.2.0 -
// confirmed against core's actual admin/includes/application_bootstrap.php and
// includes/application_top.php at the v2.0.0, v2.1.0, v2.2.0-alpha, v2.2.1, and v2.2.2 tags.
// On v2.0.0/v2.1.0 specifically, FILENAME_ADD_CUSTOMERS/BOX_CUSTOMERS_ADD_CUSTOMERS/
// TABLE_ADMIN_ADD_USER_TOKENS would otherwise never get defined on a normal admin page load,
// breaking the admin menu and this plugin's own pages - confirmed by a real user report on
// v2.1.0. admin/includes/extra_datafiles/*.php, unlike root-level filenames.php, has been
// auto-loaded per-plugin since v2.0.0, so requiring the canonical file from here covers the
// gap. Every define() in that file is already guarded with !defined(), so this is safe to
// load a second time on v2.2.0+, where core loads both.
//
require dirname(__DIR__, 3) . '/filenames.php';
