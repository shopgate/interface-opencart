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

/**
 * Shopgate installation helper to collect shop settings/data
 */
class ShopgateOpencartExportInstall
{
    /**
     * request url
     */
    const URL_TO_UPDATE_SHOPGATE = 'https://api.shopgate.com/log';
    /**
     * interface installation action
     */
    const INSTALL_ACTION = "interface_install";

    /**
     * @var null
     */
    protected $_date = null;

    /**
     * @param Config $config
     */
    public function generateData(Config $config)
    {
        $uid      = $this->_getUid($config->get('config_encryption'));
        $database = new ShopgateOpencartDatabase();

        $subShops[] = array(
            'uid'                 => $uid,
            'name'                => $config->get('config_name'),
            'url'                 => $config->get('config_url'),
            'contact_name'        => $config->get('config_owner'),
            'contact_phone'       => $config->get('config_telephone'),
            'contact_email'       => $config->get('config_email'),
            'stats_items'         => $this->_getItems($database),
            'stats_categories'    => $this->_getCategories($database),
            'stats_orders'        => $this->_getOrders($database),
            'stats_acs'           => $this->_calculateAverage($database),
            'stats_currency'      => $config->get('config_currency'),
            'stats_unique_visits' => 0,
            'stats_mobile_visits' => 0,
        );

        $data = array(
            'action'             => self::INSTALL_ACTION,
            'uid'                => $uid,
            'plugin_version'     => SHOPGATE_PLUGIN_VERSION,
            'shopping_system_id' => 93,
            'subshops'           => $subShops,
        );

        try {
            $curl = curl_init(self::URL_TO_UPDATE_SHOPGATE);

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            curl_exec($curl);
            curl_close($curl);
        } catch (Exception $e) {
            ShopgateLogger::getInstance()->log(
                "Shopgate_Framework Message: " . self::URL_TO_UPDATE_SHOPGATE . " could not be reached.",
                ShopgateLogger::LOGTYPE_ERROR
            );
        }
    }

    /**
     * @param $encryptionKey
     *
     * @return string
     */
    protected function _getUid($encryptionKey)
    {
        $salt = "5h0p6473.c0m";

        return md5($encryptionKey . $salt);
    }

    /**
     * @param ShopgateOpencartDatabase $database
     *
     * @return int
     */
    protected function _getItems(ShopgateOpencartDatabase $database)
    {
        return $database->getProductsCount();
    }

    /**
     * @param ShopgateOpencartDatabase $database
     *
     * @return int
     */
    protected function _getCategories(ShopgateOpencartDatabase $database)
    {
        return $database->getCategoriesCount();
    }

    /**
     * @param ShopgateOpencartDatabase $database
     *
     * @return int
     */
    protected function _getOrders(ShopgateOpencartDatabase $database)
    {
        return $database->getOrdersCount();
    }

    /**
     * @param ShopgateOpencartDatabase $database
     *
     * @return float
     */
    protected function _calculateAverage(ShopgateOpencartDatabase $database)
    {
        if ($database->getOrdersCount() == 0) {
            return 0;
        }

        $average = $database->getOrderAverage() / $database->getOrdersCount();

        return round($average, 2);
    }
}
