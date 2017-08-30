<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

$shopgateFolder = dirname(__FILE__) . '/';

require_once($shopgateFolder . 'vendor/autoload.php');
require_once($shopgateFolder . 'opencart/abstract.php');
require_once($shopgateFolder . 'opencart/cron.php');
require_once($shopgateFolder . 'opencart/customer.php');
require_once($shopgateFolder . 'opencart/database.php');
require_once($shopgateFolder . 'opencart/order.php');
require_once($shopgateFolder . 'exception/product_skipped.php');
require_once($shopgateFolder . 'export/cart/shipping.php');
require_once($shopgateFolder . 'export/cart.php');
require_once($shopgateFolder . 'export/category.php');
require_once($shopgateFolder . 'export/category/csv.php');
require_once($shopgateFolder . 'export/category/xml.php');
require_once($shopgateFolder . 'export/customer.php');
require_once($shopgateFolder . 'export/install.php');
require_once($shopgateFolder . 'export/order.php');
require_once($shopgateFolder . 'export/product.php');
require_once($shopgateFolder . 'export/product/csv.php');
require_once($shopgateFolder . 'export/product/xml.php');
require_once($shopgateFolder . 'export/review.php');
require_once($shopgateFolder . 'export/review/csv.php');
require_once($shopgateFolder . 'export/review/xml.php');
require_once($shopgateFolder . 'export/settings.php');
require_once($shopgateFolder . 'export/stock.php');
require_once($shopgateFolder . 'export/product_variant.php');
require_once($shopgateFolder . 'export/product_stock.php');
require_once($shopgateFolder . 'configuration.php');
require_once($shopgateFolder . 'opencart.php');
require_once($shopgateFolder . 'plugin.php');

/**
 * Include a name space helper if PHP version is above 5.3
 */
if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
    require_once($shopgateFolder . 'opencart/namespaces.php');
}
