<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: extra_definitions/lang.add_customers.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// This is a guarded backup of the box-title constant that's now defined reliably in the
// plugin-root filenames.php (see that file's comments). Kept in the classic extra_definitions
// location and in the array-return format required by Zencart\LanguageLoader\ArraysLanguageLoader
// in case a given Zen Cart 2.x build does load this file too.
//
$define = [
    'BOX_CUSTOMERS_ADD_CUSTOMERS' => 'Add Customer(s)',
];

return $define;
