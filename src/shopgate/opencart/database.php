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
 * Database helper class
 */
class ShopgateOpencartDatabase extends ShopgateObject
{
    /** @var resource */
    protected $_database;

    /**
     * @var array
     */
    protected $_rollBackActions = array();

    public function __construct()
    {
        $database = @($GLOBALS["___mysqli_ston"] = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE));
        mysqli_set_charset($database, 'utf8');

        $this->_database = $database;
    }

    /**
     * This method tries to figure out what OpenCart version is installed and returns the version
     *
     * @return string version
     */
    public function getOpenCartVersion()
    {
        if (!empty($this->version)) {
            return $this->version;
        }

        if (defined('VERSION')) {
            // The constant VERSION is avaible since version 1.4.8
            return $this->version = VERSION; // 1.4.8 -> open end
        }

        if (file_exists(
            DIR_APPLICATION . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'setting' . DIRECTORY_SEPARATOR
            . 'store.php'
        )) {
            // The class Store is available since 1.4.1
            return $this->version = '1.4.1'; // Version is between 1.4.1 and 1.4.7
        }

        if (class_exists('Action', false)) {
            // The class Action is available since 1.4.0
            return $this->version = '1.4.0';
        }

        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_option_value` LIKE 'quantity'");
        if (mysqli_num_rows($result)) {
            return $this->version = '1.3.4';
        }

        if (file_exists(
            DIR_APPLICATION . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'localisation' . DIRECTORY_SEPARATOR
            . 'packaging.php'
        )) {
            // The class packaging is available since 1.3.3
            return $this->version = '1.3.3';
        }

        return $this->version = '1.3.0';
    }

    /**
     * Checks if we are currently in a OpenCart version that is equal or higher then the passed $minVersion
     *
     * @param $minVersion
     *
     * @return bool returns if the minimum OpenCart version is given
     */
    public function assertMinimumVersion($minVersion)
    {
        return version_compare($this->getOpenCartVersion(), $minVersion, '>=');
    }

    /**
     * @param int $shopNumber
     *
     * @return int
     * @throws Exception
     */
    public function getStoreIdByShopNumber($shopNumber)
    {
        $defaultStoreId = 0;
        if ($this->assertMinimumVersion('1.5.0.0')) {
            $query   = "SELECT * FROM `" . DB_PREFIX . "setting`
                    WHERE `value` = '" . $shopNumber . "'
                    AND `key` = 'shopgate_shop_number'";
            $row     = $this->_fetchOne($query);
            $storeId = $row["store_id"]
                ? $row["store_id"]
                : $defaultStoreId;
        } elseif ($this->assertMinimumVersion('1.4.1')) {
            //1.4.1 shop entries in setting and store table
            $query = "SELECT * FROM `" . DB_PREFIX . "setting`
                    WHERE `value` = '" . $shopNumber . "'
                    AND `key` = 'shopgate_shop_number'
                    AND `group` LIKE 'shopgate_%'";
            $row   = $this->_fetchOne($query);

            // the store_id is saved in the group key name e.g. $group = "shopgate_8"
            $group   = explode('_', $row['group']);
            $storeId = isset($group[1])
                ? $group[1]
                : $defaultStoreId;
        } else {
            /*
             * Attention: This will select one random shop (in case the merchant has more than one shop configured)
             * OpenCart version <= 1.4.0 doesn't support multi shops so we only need to get the first "random" shop id
             * because there should be only one available
             */
            $query   = "SELECT * FROM `" . DB_PREFIX . "setting`
                  WHERE `key` = 'shopgate_store_id'";
            $row     = $this->_fetchOne($query);
            $storeId = $row["value"];
        }

        return $storeId;
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getStoreById($storeId)
    {
        return $this->_fetchOne(
            "SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . (int)$storeId . "'"
        );
    }

    /**
     * @param $type
     *
     * @return array
     * @throws Exception
     */
    public function getExtensions($type)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = '" . ((isset($GLOBALS["___mysqli_ston"]) && is_object(
                    $GLOBALS["___mysqli_ston"]
                ))
                ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $type)
                : ((trigger_error(
                    "[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.",
                    E_USER_ERROR
                ))
                    ? ""
                    : "")) . "'";

        return $this->_fetchAll($query);
    }

    /**
     * @param string   $user
     * @param null|int $languageId
     *
     * @return array
     */
    public function getCustomer($user, $languageId = null)
    {
        $customerGroupTable = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "customer_group'");
        $customerGroup      = mysqli_num_rows($customerGroupTable)
            ? " LEFT JOIN `" . DB_PREFIX . "customer_group`
                  ON `" . DB_PREFIX . "customer`.`customer_group_id` = `" .
            DB_PREFIX . "customer_group`.`customer_group_id` "
            : "";

        $customerGroupDescriptionTable = $this->_runQuery(
            "SHOW TABLES LIKE '" . DB_PREFIX . "customer_group_description'"
        );
        $customerGroup                 .= mysqli_num_rows($customerGroupDescriptionTable)
            ? " LEFT JOIN `" . DB_PREFIX . "customer_group_description`
                  ON `" . DB_PREFIX . "customer`.`customer_group_id` = `" .
            DB_PREFIX . "customer_group_description`.`customer_group_id` "
            : "";

        $query = "SELECT * FROM `" . DB_PREFIX . "customer`" . $customerGroup . "
                  WHERE `customer_id` = '" . $user . "'";

        if (mysqli_num_rows($customerGroupDescriptionTable)
            && $languageId != null
        ) {
            $query .= " AND `" . DB_PREFIX . "customer_group_description`.`language_id` = " . (int)$languageId;
        }

        return $this->_fetchOne($query);
    }

    /**
     * @param string $user
     *
     * @return array
     */
    public function getCustomerByMail($user)
    {
        $result        = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "customer_group'");
        $customerGroup = mysqli_num_rows($result)
            ? " LEFT JOIN `" . DB_PREFIX . "customer_group`
                  ON `" . DB_PREFIX . "customer`.`customer_group_id` = `" .
            DB_PREFIX . "customer_group`.`customer_group_id` "
            : "";

        $query = "SELECT * FROM `" . DB_PREFIX . "customer` " . $customerGroup . "
                  WHERE `email` = '" . $user . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getCustomerById($id)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = " . $id;

        return $this->_fetchOne($query);
    }

    /**
     * @param int $customerId
     *
     * @return array
     */
    public function getCustomerAddresses($customerId)
    {
        $query
            = "SELECT DISTINCT 
                `addr`.`address_id`, 
                `addr`.`company`,
                `addr`.`customer_id`, 
                `addr`.`firstname`, 
                `addr`.`lastname`, 
                `addr`.`address_1`, 
                `addr`.`address_2`, 
                `addr`.`city`, 
                `addr`.`postcode`, 
                `z`.`code` AS `zone`, 
                `cnt`.`iso_code_2` AS `country`
              FROM `" . DB_PREFIX . "address` AS `addr`
              LEFT JOIN `" . DB_PREFIX . "country` AS `cnt` 
                ON (`addr`.`country_id` = `cnt`.`country_id`)
              LEFT JOIN `" . DB_PREFIX . "zone` AS `z` 
                ON (`addr`.`zone_id` = `z`.`zone_id`)
              WHERE `addr`.`customer_id` = " . $customerId . "
              GROUP BY `addr`.`address_id`  
              ORDER BY `addr`.`address_id` ASC";

        return $this->_fetchAll($query);
    }

    /**
     * @return array
     */
    public function getTaxRules()
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "tax_rule'");
        if (!mysqli_num_rows($result)) {
            $query = "SELECT 
                        *,
                        concat(`class`.`title`, ' ', `rate`.`description`) as based,
                        concat(`rate`.`tax_rate_id`, '-', `rate`.`tax_class_id`) as tax_rule_id
                      FROM `" . DB_PREFIX . "tax_rate` AS `rate`
                      JOIN `" . DB_PREFIX . "tax_class` AS `class`
                        ON `class`.`tax_class_id` = `rate`.`tax_class_id`";
        } else {
            $query = "SELECT * FROM `" . DB_PREFIX . "tax_rule` AS `rule`
                      JOIN `" . DB_PREFIX . "tax_rate` AS `rate` ON `rule`.`tax_rate_id` = `rate`.`tax_rate_id`
                      JOIN `" . DB_PREFIX . "tax_class` AS `class`
                        ON `class`.`tax_class_id` = `rule`.`tax_class_id`";
        }

        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "tax_rate` LIKE 'type'");
        if (mysqli_num_rows($result)) {
            $query .= " WHERE `rate`.`type` = 'P'";
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param $languageId
     *
     * @return array
     */
    public function getCustomerGroups($languageId)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "customer_group_description'");
        $cgd    = mysqli_num_rows($result)
            ? "
                  LEFT JOIN `" . DB_PREFIX . "customer_group_description` AS `description`
                    ON `group`.`customer_group_id` = `description`.`customer_group_id` 
                    AND `description`.`language_id` = " . $languageId
            : "";
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "customer_group'");
        if (!mysqli_num_rows($result)) {
            return array();
        }
        $query = "SELECT * FROM `" . DB_PREFIX . "customer_group` AS `group` " . $cgd;

        return $this->_fetchAll($query);
    }

    /**
     * @param $part
     *
     * @return array
     * @throws Exception
     */
    public function getUrlAlias($part)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "url_alias'");
        if (!mysqli_num_rows($result)) {
            return array();
        }
        $query = "SELECT * FROM " . DB_PREFIX . "url_alias WHERE keyword = '" . ((isset($GLOBALS["___mysqli_ston"]) && is_object(
                    $GLOBALS["___mysqli_ston"]
                ))
                ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $part)
                : ((trigger_error(
                    "[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.",
                    E_USER_ERROR
                ))
                    ? ""
                    : "")) . "'";

        return $this->_fetchAll($query);
    }

    /**
     * @return array
     */
    public function getProductTaxClasses()
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "tax_class`";

        return $this->_fetchAll($query);
    }

    /**
     * @param null|int $taxRateId
     *
     * @return array
     */
    public function getTaxRates($taxRateId = null)
    {
        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "tax_rate` LIKE 'name'");
        $name   = mysqli_num_rows($result)
            ? "`rate`.`name`,"
            : "`rate`.`description` AS `name`,";

        $query
            = "SELECT   
                 `rate`.`tax_rate_id`, " . $name . "
                 `rate`.`rate`,
                 `country`.`iso_code_2` AS `country_code`,
                 `zone`.`code` AS `state_code`,
                 `zone`.`zone_id`
               FROM `" . DB_PREFIX . "tax_rate` AS `rate`
               JOIN `" . DB_PREFIX . "geo_zone` AS `geo`
                 ON `geo`.`geo_zone_id` = `rate`.`geo_zone_id`
               JOIN `" . DB_PREFIX . "zone_to_geo_zone` AS `map`
                 ON `map`.`geo_zone_id` = `geo`.`geo_zone_id`
               JOIN `" . DB_PREFIX . "country` AS `country`
                 ON `country`.`country_id` = `map`.`country_id`
               LEFT JOIN `" . DB_PREFIX . "zone` AS `zone`
                 ON `zone`.`zone_id` = `map`.`zone_id`";

        $conditions = array();
        if ($taxRateId) {
            $conditions[] = "`rate`.`tax_rate_id` = " . $taxRateId;
        }

        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "tax_rate` LIKE 'type'");
        if (mysqli_num_rows($result)) {
            $conditions[] = "`rate`.`type` = 'P'";
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getProduct($productId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "product`
                  WHERE `product_id` = '" . $productId . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int $valueNumber
     *
     * @return array
     */
    public function getProductOptionValue($valueNumber)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "product_option_value`
                  WHERE `product_option_value_id` = " . $valueNumber;

        return $this->_fetchOne($query);
    }

    /**
     * @param int $optionId
     *
     * @return string
     */
    public function getProductOptionType($optionId)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "option'");
        if (!mysqli_num_rows($result)) {
            return "select";
        }

        $query = "SELECT `type` FROM `" . DB_PREFIX . "option`
                  WHERE `option_id` = " . $optionId;
        $row   = $this->_fetchOne($query);

        return $row['type'];
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getProductDiscount($productId)
    {
        $result        =
            $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_discount` LIKE 'customer_group_id'");
        $customerGroup = mysqli_num_rows($result)
            ? "`customer_group_id`"
            : "'' AS `customer_group_id`";

        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_discount` LIKE 'price'");
        $price  = mysqli_num_rows($result)
            ? "`price`"
            : "`discount` AS `price`";

        $result         = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_discount` LIKE 'date_start'");
        $dateValidation = mysqli_num_rows($result)
            ? " AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW()))"
            : "";

        $query = "SELECT " . $customerGroup . "," . $price . ",`quantity`
                    FROM `" . DB_PREFIX . "product_discount`
                  WHERE `product_id` = " . (int)$productId . $dateValidation;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $itemTaxId
     * @param int $geoZoneId
     *
     * @return float
     */
    public function getTaxRate($itemTaxId, $geoZoneId)
    {
        $geoZoneId = $geoZoneId
            ? $geoZoneId
            : 0;
        if (!$itemTaxId) {
            return 0;
        }
        $query
             = "SELECT `tr`.`tax_class_id`, `tr`.`tax_rate_id`, `tr`.`based`, `t`.`rate`, `t`.`geo_zone_id`
                FROM `" . DB_PREFIX . "tax_rule` `tr`
                LEFT JOIN `" . DB_PREFIX . "tax_rate` `t`
                  ON (`tr`.`tax_rate_id` = `t`.`tax_rate_id`)
                WHERE `tr`.`tax_class_id` = " . $itemTaxId . "
                AND `t`.`geo_zone_id` = " . $geoZoneId . "
                AND `tr`.`based` = 'shipping'";
        $tax = $this->_fetchOne($query);

        return $tax["rate"];
    }

    /**
     * @param int $countryId
     * @param int $zoneId
     *
     * @return int
     */
    public function getGeoZone($countryId, $zoneId)
    {
        $query   = "SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` 
                    WHERE (`country_id` = '" . $countryId . "' AND `zone_id` = '" . $zoneId . "') 
                    OR (`country_id` = '" . $countryId . "' AND `zone_id` = '0')";
        $geoZone = $this->_fetchOne($query);

        return $geoZone["geo_zone_id"];
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "country` LIKE 'status'");
        $status = mysqli_num_rows($result)
            ? " WHERE `status` = 1"
            : "";
        $query  = "SELECT * FROM `" . DB_PREFIX . "country`" . $status;

        return $this->_fetchAll($query);
    }

    /**
     * @param int   $shopId
     * @param int   $languageId
     * @param null  $limit
     * @param null  $offset
     * @param array $uids
     *
     * @return array
     */
    public function getProductsForExport($shopId, $languageId, $limit = null, $offset = null, $uids = array())
    {
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'ean'");
        $ean       = mysqli_num_rows($result)
            ? "`p`.`ean`,"
            : "'' AS `ean`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'isbn'");
        $isbn      = mysqli_num_rows($result)
            ? "`p`.`isbn`,"
            : "'' AS `isbn`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'mpn'");
        $mpn       = mysqli_num_rows($result)
            ? "`p`.`mpn`,"
            : "'' AS `mpn`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'sku'");
        $sku       = mysqli_num_rows($result)
            ? "`p`.`sku`,"
            : "'' AS `sku`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'sort_order'");
        $sortOrder = mysqli_num_rows($result)
            ? "`p`.`sort_order`,"
            : "'' AS `sort_order`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'minimum'");
        $minimum   = mysqli_num_rows($result)
            ? "`p`.`minimum`,"
            : "'' AS `minimum`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'upc'");
        $upc       = mysqli_num_rows($result)
            ? "`p`.`upc`,"
            : "'' AS `upc`,";
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'subtract'");
        $subtract  = mysqli_num_rows($result)
            ? "`p`.`subtract`,"
            : "'' AS `subtract`,";
        $result    = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "product_to_store'");
        $useStore  = mysqli_num_rows($result);
        $result    = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'tag'");
        $tags      = mysqli_num_rows($result)
            ? "`pd`.`tag` AS `product_tags`,"
            : "'' AS `product_tags`,";

        $query
            = "SELECT DISTINCT " . $ean . $isbn . $mpn . $sku . $minimum . $upc . $subtract . $sortOrder . "
              `p`.`model`,
              `p`.`price` AS `normal_price`,
              `p`.`image`,
              `p`.`shipping`,
              `p`.`weight`,
              `p`.`weight_class_id`,
              `p`.`date_modified`,
              `p`.`quantity`,
              `p`.`tax_class_id`,
              `p`.`stock_status_id`,
              `p`.`product_id` AS `id`,
              `p`.`status`,
              `sta`.`name` AS `status_name`,
              `pd`.`name` AS `products_name`,
              `pd`.`description` AS `product_description`,
              `pd`.`language_id`,
              `pd`.`product_id`,
              " . $tags . "
              `m`.`name` AS `manufacturer_name`,
              `cd`.`name` AS `category_name`,
              `cd`.`category_id`,
              `cd`.`language_id`" .
            ($useStore
                ? ",`st`.`store_id`"
                : " ") . "
              FROM `" . DB_PREFIX . "product` `p`
              LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON `p`.`product_id` = `pd`.`product_id`
              LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON `p`.`product_id` = `p2c`.`product_id`
              LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON `cd`.`category_id` = `p2c`.`category_id`
              LEFT JOIN `" . DB_PREFIX . "manufacturer` `m` ON `p`.`manufacturer_id` = `m`.`manufacturer_id`

              LEFT JOIN `" . DB_PREFIX . "stock_status` `sta` ON `p`.`stock_status_id` = `sta`.`stock_status_id`
                            AND `sta`.`language_id` = " . $languageId .
            ($useStore
                ? " LEFT JOIN `" . DB_PREFIX . "product_to_store` `st` ON `p`.`product_id` = `st`.`product_id`"
                : " ") . "
              WHERE `p`.status = 1
              AND `pd` .`language_id` = " . $languageId . "
              AND `cd` .`language_id` = " . $languageId .
            ($useStore
                ? " AND `st`.`store_id` = " . $shopId
                : " ");

        if (!empty($uids)) {
            $query .= " AND `p`.`product_id` in (" . implode(",", $uids) . ")";
        }

        $query .= " GROUP BY `p`.`product_id`
              ORDER BY `p`.`product_id` ASC";

        if (!is_null($limit) && !is_null($offset)) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param int $productTaxClassId
     *
     * @return array
     */
    public function getTaxByProductTaxClassId($productTaxClassId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "tax_class`
                  WHERE `tax_class_id` = '" . $productTaxClassId . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getPromoRules($productId)
    {
        $customerGroups =
            $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_special` LIKE 'customer_group_id'");
        $priority       = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_special` LIKE 'priority'");

        $customerCheck = mysqli_num_rows($customerGroups)
            ? " AND `customer_group_id` = " . (int)ShopgateOpencart::getModel('config')->get('config_customer_group_id')
            : "";

        $query = "SELECT * FROM `" . DB_PREFIX . "product_special`
                  WHERE `product_id` = " . $productId
            . $customerCheck
            . (mysqli_num_rows($priority)
                ? " ORDER BY `priority` DESC"
                : "");

        return $this->_fetchAll($query);
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getProductImages($productId)
    {
        $sortOrder = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_image` LIKE 'sort_order'");

        $query = "SELECT * FROM `" . DB_PREFIX . "product_image`
                  WHERE `product_id` = " . $productId
            . (mysqli_num_rows($sortOrder)
                ? " ORDER BY `sort_order` ASC"
                : "");

        return $this->_fetchAll($query);
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getProductCategoryIds($productId)
    {
        $query       = "SELECT `category_id` FROM `" . DB_PREFIX . "product_to_category` 
                        WHERE `product_id` = " . $productId;
        $result      = $this->_runQuery($query);
        $categoryIds = array();

        while ($cat = mysqli_fetch_array($result)) {
            $categoryIds[] = $cat['category_id'];
        }

        return $categoryIds;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    public function getRelatedProductIds($productId)
    {
        $query      = "SELECT * FROM `" . DB_PREFIX . "product_related`
                       WHERE `product_id` = " . $productId;
        $relatedIds = array();
        $rows       = $this->_fetchAll($query);

        foreach ($rows as $row) {
            $relatedIds[] = $row['related_id'];
        }

        return $relatedIds;
    }

    /**
     * @param int $productId
     * @param int $languageId
     *
     * @return array
     */
    public function getProductOptions($productId, $languageId)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "option'");
        if (mysqli_num_rows($result)) {
            $query
                = "SELECT
                 `pov`.`product_option_value_id`,
                 `pov`.`product_option_id`,
                 `pov`.`product_id`,
                 `pov`.`option_id`,
                 `opt`.`type`,
                 `od`.`name` AS `option_name`,
                 `pov`.`option_value_id`,
                 `ovd`.`name` AS `option_value_name`,
                 `pov`.`price`,
                 `pov`.`price_prefix`,
                 `od`.`language_id`,
                 `po`.`required`,
                 `pov`.`subtract`,
                 `pov`.`weight`,
                 `pov`.`weight_prefix`,
                 `pov`.`quantity`
               FROM `" . DB_PREFIX . "product_option_value` `pov`
               LEFT JOIN `" . DB_PREFIX . "option_description` `od`
                  ON `pov`.`option_id` = `od`.`option_id`
               LEFT JOIN `" . DB_PREFIX . "option` `opt`
                   ON `pov`.`option_id` = `opt`.`option_id`
               LEFT JOIN `" . DB_PREFIX . "option_value` `ov`
                   ON `ov`.`option_value_id` = `pov`.`option_value_id`
               LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd`
                   ON `pov`.`option_value_id` = `ovd`.`option_value_id`
               LEFT JOIN `" . DB_PREFIX . "product_option` `po`
                   ON `po`.`product_option_id` = `pov`.`product_option_id`
               WHERE `pov`.`product_id` = " . $productId . "
               AND `od`.`language_id` = " . $languageId . "
               AND `ovd`.`language_id` = " . $languageId . "
               ORDER BY `opt`.`sort_order`, `ov`.`sort_order`";
        } else {
            $extraFields = array();
            if ($this->assertMinimumVersion('1.3.4')) {
                // There is a possiblity that we create for OpenCart version 1.3.4 and higher attributes. Therefore we need these fields
                $extraFields = array(
                    '`pov`.`subtract`',
                    '`pov`.`quantity`',
                    '0 as `weight`',
                    '0 as `required`',
                    '"+" as `weight_prefix`',
                );
            }

            $query
                = "SELECT
                 `pov`.`product_option_value_id`,
                 `pov`.`product_option_id`,
                 `pov`.`product_id`,
                 'select' AS `type`,
                 `od`.`name` AS `option_name`,
                 `ovd`.`name` AS `option_value_name`,
                 `pov`.`price`,
                 `pov`.`prefix` AS `price_prefix`,
                 `od`.`language_id`
                 " . (!empty($extraFields)
                    ? "," . implode(',', $extraFields)
                    : '') . "
               FROM `" . DB_PREFIX . "product_option_value` `pov`
               LEFT JOIN `" . DB_PREFIX . "product_option_description` `od`
                  ON `pov`.`product_option_id` = `od`.`product_option_id`
               LEFT JOIN `" . DB_PREFIX . "product_option_value_description` `ovd`
                   ON `pov`.`product_option_value_id` = `ovd`.`product_option_value_id`
               LEFT JOIN `" . DB_PREFIX . "product_option` `po`
                   ON `po`.`product_option_id` = `pov`.`product_option_id`
               WHERE `pov`.`product_id` = " . $productId . "
               AND `od`.`language_id` = " . $languageId . "
               AND `ovd`.`language_id` = " . $languageId . "
               ORDER BY `po`.`sort_order`, `pov`.`sort_order`";
        }

        $options = $this->_fetchAll($query);

        if (is_array($options)) {
            $optionsTemp = array();
            foreach ($options as $option) {
                $optionsTemp[$option['product_option_id'] . '_' . $option['product_option_value_id']] = $option;
            }
            $options = $optionsTemp;
        }

        return $options;
    }

    /**
     * @return int
     */
    public function getMaxOptionCount()
    {
        $query
            = "SELECT 
                 MAX(`count`) AS `max`
               FROM (
                 SELECT
                   count(product_option_id) AS `count`
                 FROM `" . DB_PREFIX . "product_option`
                 GROUP BY `product_id`
               ) AS `max_count`";

        $result = $this->_fetchOne($query);

        return $result["max"];
    }

    /**
     * @param int $productId
     * @param int $languageId
     *
     * @return array
     */
    public function getProductPersonalisations($productId, $languageId)
    {
        $result      = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "product_option` LIKE 'option_value'");
        $valueColumn = mysqli_num_rows($result)
            ? "`po`.`option_value`,"
            : "`po`.`value`,";

        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "option_description'");
        if (mysqli_num_rows($result)) {
            $query
                = "SELECT
                 `po`.`product_id`,
                 `po`.`option_id`,
                 " . $valueColumn . "
                 `po`.`required`,
                 `po`.`product_option_id`,
                 `opt`.`type`,
                 `od`.`name` AS `option_name`
               FROM `" . DB_PREFIX . "product_option` `po`
               LEFT JOIN `" . DB_PREFIX . "option_description` `od`
                   ON `po`.`option_id` = `od`.`option_id`
               LEFT JOIN `" . DB_PREFIX . "option` `opt`
                   ON `po`.`option_id` = `opt`.`option_id`
               WHERE `po`.`product_id` = " . $productId . "
               AND `od`.`language_id` = " . $languageId . "
               AND
                 (`opt`.`type` = 'text'
                 OR `opt`.`type` = 'textarea'
                 OR `opt`.`type` = 'date'
                 OR `opt`.`type` = 'time'
                 OR `opt`.`type` = 'datetime'
                 OR `opt`.`type` = 'file')
               ORDER BY `po`.`option_id`";
        } else {
            return array();
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param int $productId
     * @param int $languageId
     *
     * @return array
     */
    public function getProductProperties($productId, $languageId)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "product_attribute'");
        if (mysqli_num_rows($result)) {
            $query
                = "SELECT
                 `pa`.`product_id`,
                 `pa`.`attribute_id`,
                 `pa`.`language_id`,
                 `pa`.`text`,
                 `ad`.`name` AS `attr_desc`,
                 `a`.`attribute_group_id`,
                 `agd`.`name` AS `group_desc`
               FROM `" . DB_PREFIX . "product_attribute` `pa`
               LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad`
                   ON `pa`.`attribute_id` = `ad`.`attribute_id`
               LEFT JOIN `" . DB_PREFIX . "attribute` `a`
                   ON `pa`.`attribute_id` = `a`.`attribute_id`
               LEFT JOIN `" . DB_PREFIX . "attribute_group_description` `agd`
                   ON `a`.`attribute_group_id` = `agd`.`attribute_group_id`
               WHERE `product_id` = " . $productId . "
               AND `pa`.`language_id` = " . $languageId . "
               AND `ad`.`language_id` = " . $languageId . "
               AND `agd`.`language_id` = " . $languageId . "
               ORDER BY `a`.`attribute_group_id`";

            return $this->_fetchAll($query);
        }

        return array();
    }

    /**
     * @param int      $shopId
     * @param int      $languageId
     * @param null|int $limit
     * @param null|int $offset
     * @param int      $isSplitted
     *
     * @return array
     */
    public function getCategoriesForExport($shopId, $languageId, $limit = null, $offset = null, $isSplitted = 0)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "category_to_store'");
        $query  = "SELECT * FROM `" . DB_PREFIX . "category`
                  LEFT JOIN `" . DB_PREFIX . "category_description`
                      ON `" . DB_PREFIX . "category`.`category_id` = `" . DB_PREFIX . "category_description`.`category_id`
                      " . (mysqli_num_rows($result)
                ? " LEFT JOIN `" . DB_PREFIX . "category_to_store`
                      ON `" . DB_PREFIX . "category`.`category_id` = `" . DB_PREFIX . "category_to_store`.`category_id`"
                : "")
            . " WHERE `language_id` = " . $languageId . (mysqli_num_rows($result)
                ?
                " AND `store_id` = '" . $shopId . "'"
                : "");

        if ($isSplitted) {
            $query .= " LIMIT " . $offset . ", " . $limit;
        }

        return $this->_fetchAll($query);
    }

    /**
     * @return int
     */
    public function getCategoryHighestSortOrder()
    {
        $query = "SELECT max(`sort_order`) AS `max` FROM `" . DB_PREFIX . "category`";
        $sort  = $this->_fetchOne($query);

        return $sort['max'];
    }

    /**
     * @return int
     */
    public function getProductHighestSortOrder()
    {
        $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "setting` LIKE 'store_id'");
        if (mysqli_num_rows($result)) {
            $query = "SELECT max(`sort_order`) AS `max` FROM `" . DB_PREFIX . "product`";
            $sort  = $this->_fetchOne($query);
        }

        return isset($sort['max'])
            ? $sort['max']
            : 999999999;
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     * @param array    $uids
     *
     * @return array
     */
    public function getReviewsForExport($limit = null, $offset = null, array $uids = array())
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "review` WHERE `status` = 1";
        if (!empty($uids)) {
            $query .= " AND `review_id` in (" . implode(",", $uids) . ")";
        }
        if ($limit && $offset) {
            $query .= " LIMIT " . $offset . "," . $limit;
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param int $weightClassId
     *
     * @return array
     */
    public function getWeightClass($weightClassId)
    {
        $result = $this->_runQuery("SHOW TABLES LIKE '" . DB_PREFIX . "weight_class_description'");
        if (mysqli_num_rows($result)) {
            $query = "SELECT `weight_class_id`, `unit` FROM `" . DB_PREFIX . "weight_class_description`
                  WHERE `weight_class_id` = " . $weightClassId;
        } else {
            $query = "SELECT `weight_class_id`, `unit` FROM `" . DB_PREFIX . "weight_class`
                  WHERE `weight_class_id` = " . $weightClassId;
        }

        return $this->_fetchOne($query);
    }

    /**
     * @param string $isoCode
     *
     * @return array
     */
    public function getCountryByIsoCode($isoCode)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "country`
                  WHERE `iso_code_2` = '" . $isoCode . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getCountryById($id)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "country`
                  WHERE `country_id` = '" . $id . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int         $countryId
     * @param string|null $state
     *
     * @return array
     */
    public function getZone($countryId, $state = null)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "zone`
                  WHERE `country_id` = '" . $countryId . "'";
        if ($state) {
            $query .= " AND `code` = '" . $state . "'";

            return $this->_fetchOne($query);
        }

        return $this->_fetchAll($query);
    }

    /**
     * @param int $zoneId
     *
     * @return array
     */
    public function getZoneById($zoneId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "zone`
                  WHERE `zone_id` = " . $zoneId;

        return $this->_fetchOne($query);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    public function getCurrencyByCode($code)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "currency`
                  WHERE `code` = '" . $code . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @return int
     */
    public function getConfigStockCheckout()
    {
        $query   = "SELECT `value` FROM `" . DB_PREFIX . "setting`
                    WHERE `key` = 'config_stock_checkout'";
        $setting = $this->_fetchOne($query);

        return $setting["value"];
    }

    /**
     * @return int
     */
    public function getConfigStockSubtract()
    {
        $query   = "SELECT `value` FROM `" . DB_PREFIX . "setting`
                    WHERE `key` = 'config_stock_subtract'";
        $setting = $this->_fetchOne($query);

        return $setting["value"];
    }

    /**
     * @param string $currencyId
     *
     * @return float
     */
    public function getCurrencyRate($currencyId)
    {
        $query = "SELECT `value` FROM `" . DB_PREFIX . "currency` 
                  WHERE `code` = '" . $currencyId . "'";
        $row   = $this->_fetchOne($query);

        return $row["value"];
    }

    /**
     * @param string $languageCode
     *
     * @return mixed[]
     */
    public function getLanguage($languageCode)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "language`
                  WHERE code = '" . $languageCode . "'";
        $row   = $this->_fetchOne($query);

        return $row;
    }

    /**
     * @param string $languageCode
     *
     * @return string
     */
    public function getLanguageId($languageCode)
    {
        $row = $this->getLanguage($languageCode);

        return $row['language_id'];
    }

    /**
     * @param string $languageCode
     *
     * @return string
     */
    public function getLanguageDirectory($languageCode)
    {
        $row = $this->getLanguage($languageCode);

        return $row['directory'];
    }

    protected function _tableExists($tableName)
    {
        return $this->_fetchOne("SHOW TABLES LIKE '{$tableName}'");
    }

    /**
     * @param int $shopId
     *
     * @return array
     */
    public function getSettings($shopId)
    {
        $query  = "SELECT * FROM " . DB_PREFIX . "setting ";
        $config = array();
        //Version information about opencart configuration
        //Since version 1.4     => The whole configuration is stored in <setting> table. Until this version opencart doesn't support multi stores
        //Since version 1.4.1   => The main configuration is stored in <setting> table. The configuration for multi stores is stored in the <store> table
        //Since 1.4.5           => the default shop the entry is missing and you can find the currency, language and the name in the settings table
        //Since version 1.5.0.0 => The main and also the store configuration is stored in <settings> table. The <store> table only contains the the store url
        if ($this->assertMinimumVersion('1.5.0.0')) {
            $result = $this->_runQuery("SHOW COLUMNS FROM `" . DB_PREFIX . "setting` LIKE 'store_id'");
            if (mysqli_num_rows($result)) {
                $sgSettingsResult = $this->_fetchAll($query . " WHERE `store_id` = '" . $shopId . "'");
            } else {
                $sgSettingsResult = $this->_fetchAll(
                    $query . " WHERE `group` = 'shopgate_" . $shopId . "' OR `group` NOT LIKE 'shopgate_%'"
                );
            }
        } else {
            $sgSettingsQuery  = " WHERE `group` = 'shopgate_" . (int)$shopId . "' OR `group` NOT LIKE 'shopgate_%';";
            $sgSettingsResult = $this->_fetchAll($query . $sgSettingsQuery);
        }

        if ($this->assertMinimumVersion('1.4.1') && ($this->_tableExists(DB_PREFIX . "store"))) {
            $storeQuery    = "SELECT * FROM " . DB_PREFIX . "store WHERE store_id={$shopId}";
            $storeSettings = $this->_fetchOne($storeQuery);

            if (!empty($storeSettings)) {
                foreach ($storeSettings as $key => $value) {
                    if (is_string($key)) {
                        $config[$key] = $value;
                    }
                }
            }
        }

        foreach ($sgSettingsResult as $row) {
            $key          = isset($config[$row["key"]])
                ? $row["key"] . "_"
                : $row["key"];
            $config[$key] = (!empty($row["serialized"]))
                ? (
                $this->jsonDecode($row["value"]) !== false && $this->jsonDecode($row["value"]) !== null
                    ? $this->jsonDecode($row["value"])
                    : unserialize($row["value"])
                )
                : $row["value"];
        }
        if (!empty($config["config_title"][$config["config_language"]])) {
            $config["config_title"] = $config["config_title"][$config["config_language"]];
        }

        return $config;
    }

    /**
     * @return array
     */
    public function getShopgateStoreConfiguration()
    {
        $values = array();
        $query  = "SELECT * FROM `" . DB_PREFIX . "setting`
                   WHERE `key` LIKE 'shopgate_%'";
        $rows   = $this->_fetchAll($query);

        foreach ($rows as $row) {
            $values[$row['key']] = $row['value'];
        }

        return $values;
    }

    /**
     * Returns default group ID for customers as
     * configured in shop's settings
     *
     * @param int $shopId
     *
     * @return int
     */
    public function getDefaultCustomerGroupId($shopId = 0)
    {
        $query  = "SELECT * FROM `" . DB_PREFIX . "setting`
                   WHERE `key` = 'config_customer_group_id' AND `store_id` = {$shopId}";
        $rows   = $this->_fetchAll($query);
        $config = array_pop($rows);

        return (int)$config['value'];
    }

    /**
     * @param int $shopgateOrderNumber
     *
     * @return array
     */
    public function getShopgateOrderEntry($shopgateOrderNumber)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "shopgate_orders`
                  WHERE `shopgate_order_number` = '" . $shopgateOrderNumber . "'";

        return $this->_fetchOne($query);
    }

    /**
     * @param int $statusId
     *
     * @return array
     */
    public function getUnsyncedShippingOrders($statusId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "shopgate_orders` AS `shopgate`
                  INNER JOIN `" . DB_PREFIX . "order` AS `order`
                    ON `shopgate`.`external_order_number` = `order`.`order_id`
                  WHERE `shopgate`.`sync_shipment` = 0
                  AND `order`.`order_status_id` = " . (int)$statusId;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $statusId
     *
     * @return array
     */
    public function getUnsyncedCancellationOrders($statusId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "shopgate_orders` AS `shopgate`
                  INNER JOIN `" . DB_PREFIX . "order` AS `order`
                    ON `shopgate`.`external_order_number` = `order`.`order_id`
                  WHERE `sync_cancellation` = 0
                  AND `order`.`order_status_id` = " . (int)$statusId;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getOrderById($id)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "order`
                  WHERE `order_id` = " . $id;

        return $this->_fetchOne($query);
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderItems($orderId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "order_product`
                  WHERE `order_id` = " . $orderId;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function getOrderVouchers($orderId)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "order_voucher`
                  WHERE `order_id` = " . $orderId;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $customerId
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getOrdersFromCustomer($customerId, $limit, $offset)
    {
        $query = "SELECT * FROM `" . DB_PREFIX . "order`
                  WHERE `customer_id` = " . $customerId . "
                  LIMIT " . $offset . ", " . $limit;

        return $this->_fetchAll($query);
    }

    /**
     * @param int $orderId
     * @param int $statusId
     */
    public function setOrderStatus($orderId, $statusId)
    {
        $query = "UPDATE `" . DB_PREFIX . "order` 
                  SET `order_status_id` = '" . $statusId . "' 
                  WHERE `order_id` = '" . $orderId . "'";

        $this->_runQuery($query);
    }

    /**
     * @param int    $orderId
     * @param int    $statusId
     * @param string $comment
     */
    public function setOrderHistoryComment($orderId, $statusId, $comment)
    {
        $data['order_id']        = $orderId;
        $data['order_status_id'] = $statusId;
        $data['notify']          = 0;
        $data['comment']         = $comment;
        $data['date_added']      = date('Y-m-d H:i:s');

        $this->_insertToDatabase('order_history', $data);
    }

    /**
     * @param array $openCartOrder
     *
     * @return int
     */
    public function insertOrder($openCartOrder)
    {
        return $this->_insertToDatabase('order', $openCartOrder);
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function insertOrderItem($data)
    {
        $orderItemId = $this->_insertToDatabase('order_product', $data);

        return $orderItemId;
    }

    /**
     * @param array $orderOption
     *
     * @return int
     */
    public function insertOrderItemOption($orderOption)
    {
        return $this->_insertToDatabase('order_option', $orderOption);
    }

    /**
     * @param array $openCartCustomer
     *
     * @return int
     */
    public function insertCustomer($openCartCustomer)
    {
        return $this->_insertToDatabase('customer', $openCartCustomer);
    }

    /**
     * @param array $opencartAddress
     *
     * @return int
     */
    public function insertCustomerAddress($opencartAddress)
    {
        return $this->_insertToDatabase('address', $opencartAddress);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    public function getCoupon($code)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "coupon as coupon ";
        if (!$this->assertMinimumVersion('1.5.0')) {
            // coulumn name is until version 1.5.0 in the table coupon_description
            $query .= "JOIN " . DB_PREFIX . "coupon_description as coupon_description ON coupon.coupon_id = coupon_description.coupon_id ";
        }
        $query .= "WHERE coupon.code = '" . $code . "' 
                   AND ((date_start = '0000-00-00' OR date_start < NOW()) 
                   AND (date_end = '0000-00-00' OR date_end > NOW())) 
                   AND status = '1'";

        return $this->_fetchOne($query);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    public function getVoucher($code)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "voucher
            WHERE code = '" . $code . "'
            AND (date_added = '0000-00-00' OR date_added < NOW())
            AND status = '1'";

        return $this->_fetchOne($query);
    }

    /**
     * for OpenCart > 2.0 only
     *
     * @param $couponId
     * @param $orderId
     * @param $customerId
     * @param $amount
     */
    public function redeemCoupon($couponId, $orderId, $customerId, $amount)
    {
        $data = array(
            'coupon_id'   => $couponId,
            'order_id'    => $orderId,
            'customer_id' => $customerId,
            'amount'      => $amount,
            'date_added'  => date('Y-m-d'),
        );
        $this->_insertToDatabase("coupon_history", $data);
    }

    /**
     * for OpenCart > 2.0 only
     *
     * @param $voucherId
     * @param $orderId
     * @param $amount
     */
    public function redeemVoucher($voucherId, $orderId, $amount)
    {
        $data = array(
            'voucher_id' => $voucherId,
            'order_id'   => $orderId,
            'amount'     => $amount,
            'date_added' => date('Y-m-d'),
        );
        $this->_insertToDatabase("voucher_history", $data);
    }

    /**
     * @param int           $orderId
     * @param ShopgateOrder $order
     * @param int           $subTotal
     * @param array         $taxTotal
     * @param int           $couponTotal
     * @param int           $voucherTotal
     * @param string        $couponCode
     * @param string        $voucherCode
     */
    public function insertOrderTotals(
        $orderId,
        $order,
        $subTotal,
        $taxTotal,
        $couponTotal,
        $voucherTotal,
        $couponCode,
        $voucherCode
    ) {
        $config = ShopgateOpencart::getModel('config');
        /** @var Language $language */
        $language = ShopgateOpencart::getModel('language');
        $currency = ShopgateOpencart::getModel('currency');
        $language->load('total/sub_total');
        $subTotalData['order_id']   = $orderId;
        $subTotalData['code']       = "sub_total";
        $subTotalData['title']      = $language->get('text_sub_total');
        $subTotalData['text']       = $currency->format($subTotal, $config->get('config_currency'));
        $subTotalData['value']      = $subTotal;
        $subTotalData['sort_order'] = $config->get('sub_total_sort_order');
        $this->_insertToDatabase('order_total', $subTotalData);

        $shippingData['order_id']   = $orderId;
        $shippingData['code']       = "shipping";
        $shippingData['title']      = $order->getShippingInfos()->getDisplayName();
        $shippingData['text']       = $currency->format(
            $order->getShippingInfos()->getAmountNet(),
            $config->get('config_currency')
        );
        $shippingData['value']      = $order->getShippingInfos()->getAmountNet();
        $shippingData['sort_order'] = $config->get('shipping_sort_order');
        $this->_insertToDatabase('order_total', $shippingData);

        $language = ShopgateOpencart::loadLanguageFrom('module/shopgate', 'admin', $language);
        foreach ($taxTotal as $rate => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $taxData['order_id']   = $orderId;
            $taxData['code']       = "tax";
            $taxData['title']      = sprintf($language->get('order_tax'), $rate);
            $taxData['text']       = $currency->format((double)$amount, $config->get('config_currency'));
            $taxData['value']      = $amount;
            $taxData['sort_order'] = $config->get('tax_sort_order');
            $this->_insertToDatabase('order_total', $taxData);
        }

        if ($order->getAmountShopPayment() > 0) {
            $paymentData['order_id']   = $orderId;
            $paymentData['code']       = "shipping";
            $paymentData['title']      = $language->get('order_payment');
            $paymentData['text']       =
                $currency->format($order->getAmountShopPayment(), $config->get('config_currency'));
            $paymentData['value']      = $order->getAmountShopPayment();
            $paymentData['sort_order'] = 6;
            $this->_insertToDatabase('order_total', $paymentData);

            $paymentTax                   = $order->getAmountShopPayment() * $order->getPaymentTaxPercent() / 100;
            $paymentTaxData['order_id']   = $orderId;
            $paymentTaxData['code']       = "tax";
            $paymentTaxData['title']      = $language->get('order_payment');
            $paymentTaxData['text']       = $currency->format($paymentTax, $config->get('config_currency'));
            $paymentTaxData['value']      = $paymentTax;
            $paymentTaxData['sort_order'] = 7;
            $this->_insertToDatabase('order_total', $paymentTaxData);
        }

        if (!empty($couponTotal)) {
            if ($this->assertMinimumVersion('2.0.0')) {
                $couponTitle = $language->get('order_coupon_code') . ' (' . $couponCode . ')';
            } elseif ($this->assertMinimumVersion('1.5.0')) {
                $couponTitle = $language->get('order_coupon_code') . '(' . $couponCode . ')';
            } else {
                $couponInfo  = $this->getCoupon($couponCode);
                $couponTitle = $couponInfo['name'];
            }

            $couponData['title']      = $couponTitle;
            $couponData['order_id']   = $orderId;
            $couponData['code']       = "coupon";
            $couponData['text']       = $currency->format(-$couponTotal, $config->get('config_currency'));
            $couponData['value']      = -$couponTotal;
            $couponData['sort_order'] = $config->get('coupon_sort_order');
            $this->_insertToDatabase('order_total', $couponData);
        }

        if (!empty($voucherTotal)) {
            $voucherTitle = $language->get('order_voucher_code');
            if ($this->assertMinimumVersion('2.0.0')) {
                $voucherTitle .= ' (' . $voucherCode . ')';
            } elseif ($this->assertMinimumVersion('1.5.0')) {
                $voucherTitle .= '(' . $voucherCode . ')';
            }

            $voucherData['order_id']   = $orderId;
            $voucherData['code']       = "voucher";
            $voucherData['title']      = $voucherTitle;
            $voucherData['text']       = $currency->format(-$voucherTotal, $config->get('config_currency'));
            $voucherData['value']      = -$voucherTotal;
            $voucherData['sort_order'] = $config->get('voucher_sort_order');
            $this->_insertToDatabase('order_total', $voucherData);
        }

        $language->load('total/total');
        $totalData['order_id']   = $orderId;
        $totalData['code']       = "total";
        $totalData['title']      = $language->get('text_total');
        $totalData['text']       = $currency->format($order->getAmountComplete(), $config->get('config_currency'));
        $totalData['value']      = $order->getAmountComplete();
        $totalData['sort_order'] = $config->get('total_sort_order');
        $this->_insertToDatabase('order_total', $totalData);
    }

    /**
     * @param array $shopgateOrderData
     *
     * @return int
     */
    public function insertShopgateOrderEntry($shopgateOrderData)
    {
        if (!empty($shopgateOrderData['id'])) {
            $query     = "UPDATE `" . DB_PREFIX . "shopgate_orders` SET";
            $syncQueue = array();
            if ($shopgateOrderData['sync_shipment']) {
                $syncQueue[] = " `sync_shipment` = " . $shopgateOrderData['sync_shipment'];
            }
            if ($shopgateOrderData['sync_cancellation']) {
                $syncQueue[] = " `sync_cancellation` = " . $shopgateOrderData['sync_cancellation'];
            }
            if ($shopgateOrderData['sync_payment']) {
                $syncQueue[] = " `sync_payment` = " . $shopgateOrderData['sync_payment'];
            }
            $query .= implode(',', $syncQueue);
            $query .= " WHERE `id` = " . $shopgateOrderData['id'];

            return $this->_runQuery($query);
        }

        return $this->_insertToDatabase('shopgate_orders', $shopgateOrderData);
    }

    /**
     * @return int
     */
    public function getProductsCount()
    {
        $query  = "SELECT count(product_id) AS count FROM `" . DB_PREFIX . "product`";
        $result = $this->_fetchOne($query);

        return $result['count'];
    }

    /**
     * @return int
     */
    public function getCategoriesCount()
    {
        $query  = "SELECT count(category_id) AS count FROM `" . DB_PREFIX . "category`";
        $result = $this->_fetchOne($query);

        return $result['count'];
    }

    /**
     * @return int
     */
    public function getOrdersCount()
    {
        $query  = "SELECT count(order_id) AS count FROM `" . DB_PREFIX . "order`";
        $result = $this->_fetchOne($query);

        return $result['count'];
    }

    /**
     * @return int
     */
    public function getReviewsCount()
    {
        $query  = "SELECT count(review_id) AS count FROM `" . DB_PREFIX . "review`";
        $result = $this->_fetchOne($query);

        return $result['count'];
    }

    /**
     * @return float
     */
    public function getOrderAverage()
    {
        $query  = "SELECT SUM(total) AS sum FROM `" . DB_PREFIX . "order`";
        $result = $this->_fetchOne($query);

        return $result['sum'];
    }

    /**
     * @param string $table
     * @param array  $data
     *
     * @return int
     * @throws Exception
     */
    protected function _insertToDatabase($table, $data)
    {
        $data  = $this->_prepareDataForTable($data, $table);
        $keys  = array_keys($data);
        $query = "INSERT INTO `" . DB_PREFIX . $table . "` SET ";
        $query .= implode(', ', array_map(array($this, '_arrayMapHelper'), $data, $keys));
        mysqli_query($GLOBALS["___mysqli_ston"], "SET NAMES 'utf8'");
        $this->_runQuery($query);

        $this->_rollBackActions[] = array(
            'table' => $table,
            'key'   => $keys[0],
            'value' => ((is_null(
                $___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"])
            ))
                ? false
                : $___mysqli_res),
        );

        return ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS["___mysqli_ston"])))
            ? false
            : $___mysqli_res);
    }

    public function rollback()
    {
        foreach ($this->_rollBackActions as $action) {
            $query = "DELETE FROM `" . DB_PREFIX . $action['table'] . "` WHERE " . $action['key'] . " = " . $action['value'];
            $this->_runQuery($query);
        }
    }

    /**
     * @param array $v
     * @param mixed $k
     *
     * @return string
     */
    protected function _arrayMapHelper($v, $k)
    {
        $value = $v === null
            ? "null"
            : $v;

        return $k . ' = ' . $value;
    }

    /**
     * @param string $query
     *
     * @return resource
     * @throws Exception
     */
    protected function _runQuery($query)
    {
        $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
        if (!$result) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DATABASE_ERROR,
                "Query error ($query): " . ((is_object($GLOBALS["___mysqli_ston"]))
                    ? mysqli_error($GLOBALS["___mysqli_ston"])
                    : (($___mysqli_res = mysqli_connect_error())
                        ? $___mysqli_res
                        : false)),
                true,
                true
            );
        }

        return $result;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    protected function _fetchQuery($query)
    {
        $result = $this->_runQuery($query);

        return mysqli_fetch_array($result);
    }

    /**
     * @param array  $data
     * @param string $table
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareDataForTable($data, $table)
    {
        $query        = "SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`;";
        $result       = $this->_runQuery($query);
        $preparedData = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $type  = explode("(", $row['Type']);
            $type  = explode(' ', $type[0]);
            $value = (isset($data[$row['Field']]))
                ? $data[$row['Field']]
                : $row['Default'];
            switch ($type[0]) {
                case "char":
                case "varchar":
                case "text":
                case "datetime":
                    $preparedData[$row['Field']] = '"' . mysqli_real_escape_string($this->_database, $value) . '"';
                    break;
                case "int":
                case "smallint":
                case "tinyint":
                    $preparedData[$row['Field']] = (int)$value;
                    break;
                case "decimal":
                    $preparedData[$row['Field']] = (float)$value;
                    break;
                default:
                    $preparedData[$row['Field']] = $value;
            }
        }

        return $preparedData;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    protected function _fetchAll($query)
    {
        $result = $this->_runQuery($query);
        $data   = array();
        while ($row = mysqli_fetch_array($result)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    protected function _fetchOne($query)
    {
        $result = $this->_runQuery($query);

        return mysqli_fetch_array($result);
    }
}
