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
 * Shopgate Configuration
 */

define("SHOPGATE_PLUGIN_VERSION", "2.9.35");

class ShopgateConfigOpencart extends ShopgateConfig
{
    const COMMENT_DETAIL_LEVEL_SIMPLE               = 0;
    const COMMENT_DETAIL_LEVEL_FULL                 = 1;
    const DEFAULT_ORDER_STATUS_CANCELED             = 7;
    const DEFAULT_ORDER_STATUS_SHIPPING_BLOCKED     = 1;
    const DEFAULT_ORDER_STATUS_SHIPPING_NOT_BLOCKED = 2;
    const DEFAULT_ORDER_STATUS_SHIPPED              = 3;

    /** @var int */
    protected $languageId;

    /** @var float */
    protected $currencyRate;

    /** @var int */
    protected $customerGroup;

    /** @var string */
    protected $shopName;

    /** @var string */
    protected $invoicePrefix;

    /** @var string */
    protected $currencyId;

    /** @var string */
    protected $languageCode;

    /** @var int */
    protected $storeId;

    /** @var array */
    protected $storeConfig;

    /** @var Language */
    protected $languageObject;

    /** @var int */
    protected $commentDetailLevel;

    /** @var int */
    protected $orderStatusCanceled;

    /** @var int */
    protected $orderStatusShippingBlocked;

    /** @var int */
    protected $orderStatusShippingNotBlocked;

    /** @var int */
    protected $orderStatusShipped;

    /** @var ShopgateOpencartDatabase */
    protected $opencartDatabase;

    /** @var array */
    protected $pluginInfo;

    public function startup()
    {
        $supportedFieldsCheckCart   = array(
            'items',
            'external_coupons',
            'shipping_methods',
            'payment_methods',
        );
        $supportedFieldsGetSettings = array(
            'tax',
            'customer_groups',
            'allowed_address_countries',
            'allowed_shipping_countries',
        );

        $this->commentDetailLevel = self::COMMENT_DETAIL_LEVEL_SIMPLE;

        $this->plugin_name                   = 'OpenCart';
        $this->enable_ping                   = 1;
        $this->enable_add_order              = 1;
        $this->enable_update_order           = 1;
        $this->enable_get_customer           = 1;
        $this->enable_get_items_csv          = 1;
        $this->enable_get_items              = 1;
        $this->enable_get_categories_csv     = 1;
        $this->enable_get_categories         = 1;
        $this->enable_get_reviews_csv        = 1;
        $this->enable_get_reviews            = 1;
        $this->enable_get_log_file           = 1;
        $this->enable_register_customer      = 1;
        $this->enable_cron                   = 1;
        $this->enable_check_stock            = 1;
        $this->enable_get_settings           = 1;
        $this->enable_get_orders             = 1;
        $this->enable_check_cart             = 1;
        $this->enable_redeem_coupons         = 1;
        $this->supported_fields_check_cart   = $supportedFieldsCheckCart;
        $this->supported_fields_get_settings = $supportedFieldsGetSettings;
        $this->pluginInfo                    = defined('VERSION')
            ? array('OpenCart-Version' => VERSION)
            : array();

        $this->orderStatusShippingBlocked    = 1;
        $this->orderStatusShippingNotBlocked = 2;
        $this->orderStatusShipped            = 3;
        $this->orderStatusCanceled           = 7;
    }

    /**
     * @return array
     */
    public function getPluginInfo()
    {
        return $this->pluginInfo;
    }

    /**
     * @param array $value
     */
    public function setPluginInfo(array $value)
    {
        $this->pluginInfo = $value;
    }

    /**
     * @param string $model
     *
     * @return Object
     */
    public function getModel($model)
    {
        if (empty($GLOBALS['registry'])) {
            // OpenCart 1.3 has a static Registry class
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            return Registry::get($model);
        }

        return $GLOBALS['registry']->get($model);
    }

    /**
     * @return ShopgateOpencartDatabase
     */
    public function getOpencartDatabase()
    {
        return $this->opencartDatabase;
    }

    /**
     * @return array
     */
    public function getStoreConfig()
    {
        return $this->storeConfig;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return mixed
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getCustomerGroup()
    {
        return $this->customerGroup;
    }

    /**
     * @return Language
     */
    public function getLanguageObject()
    {
        return $this->languageObject;
    }

    public function getCommentDetailLevel()
    {
        return $this->commentDetailLevel;
    }

    /**
     * @return float
     */
    public function getCurrencyRate()
    {
        return $this->currencyRate;
    }

    /**
     * @return mixed
     */
    public function getShopName()
    {
        return $this->shopName;
    }

    /**
     * @return mixed
     */
    public function getInvoicePrefix()
    {
        return $this->invoicePrefix;
    }

    /**
     * @return mixed
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @return mixed
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @return int
     */
    public function getOrderStatusCanceled()
    {
        return $this->orderStatusCanceled;
    }

    /**
     * @return int
     */
    public function getOrderStatusShippingBlocked()
    {
        return $this->orderStatusShippingBlocked;
    }

    /**
     * @return int
     */
    public function getOrderStatusShippingNotBlocked()
    {
        return $this->orderStatusShippingNotBlocked;
    }

    /**
     * @return int
     */
    public function getOrderStatusShipped()
    {
        return $this->orderStatusShipped;
    }

    /**
     * loading the shop and plugin configuration depending on the shop version
     */
    protected function loadSettings()
    {
        $settings            = $this->opencartDatabase->getSettings($this->storeId);
        $this->storeConfig   = $settings;
        $this->invoicePrefix = "";

        if ($this->opencartDatabase->assertMinimumVersion("1.5.0.0")) {
            if (!empty($settings["config_name"])) {
                $this->shopName = $settings["config_name"];
            } elseif (!empty($settings["config_title"])) {
                $this->shopName = $settings["config_title"];
            } elseif (!empty($settings["config_store"])) {
                $this->shopName = $settings["config_store"];
            }
            $this->invoicePrefix =
                (!empty($settings["config_invoice_prefix"])
                    ? $settings["config_invoice_prefix"]
                    : '');
        } elseif ($this->opencartDatabase->assertMinimumVersion("1.4")
            || $this->opencartDatabase->assertMinimumVersion("1.4.0")
        ) {
            if (!empty($settings["name"])) {
                $this->shopName = $settings["name"];
            } elseif (!empty($settings["title"])) {
                $this->shopName = $settings["title"];
            } elseif (!empty($settings["store"])) {
                $this->shopName = $settings["store"];
            }
        }

        $commentDetailLevels      = array(
            self::COMMENT_DETAIL_LEVEL_SIMPLE => true,
            self::COMMENT_DETAIL_LEVEL_FULL   => true,
        );
        $this->commentDetailLevel =
            isset($settings['shopgate_comment_detail_level'])
                ? $settings['shopgate_comment_detail_level']
                : self::COMMENT_DETAIL_LEVEL_SIMPLE;
        if (empty($commentDetailLevels[$this->commentDetailLevel])) {
            $this->commentDetailLevel = self::COMMENT_DETAIL_LEVEL_SIMPLE;
        }

        $this->orderStatusCanceled           =
            isset($settings['shopgate_order_status_canceled'])
                ? $settings['shopgate_order_status_canceled']
                : self::DEFAULT_ORDER_STATUS_CANCELED;
        $this->orderStatusShippingBlocked    = isset($settings['shopgate_order_status_shipping_blocked'])
            ? $settings['shopgate_order_status_shipping_blocked']
            : self::DEFAULT_ORDER_STATUS_SHIPPING_BLOCKED;
        $this->orderStatusShippingNotBlocked = isset($settings['shopgate_order_status_shipping_not_blocked'])
            ? $settings['shopgate_order_status_shipping_not_blocked']
            : self::DEFAULT_ORDER_STATUS_SHIPPING_NOT_BLOCKED;
        $this->orderStatusShipped            =
            isset($settings['shopgate_order_status_shipped'])
                ? $settings['shopgate_order_status_shipped']
                : self::DEFAULT_ORDER_STATUS_SHIPPED;

        $this->currencyId   = $settings["config_currency"];
        $this->languageCode = $settings["config_language"];
        $this->currencyRate = $this->opencartDatabase->getCurrencyRate($this->currencyId);
        if (is_null($this->customerGroup)) {
            $this->customerGroup = 1;
        }
        $this->setCname($settings['shopgate_customer_cname']);
        $this->setAlias($settings['shopgate_alias']);
        $this->setEnableMobileWebsite($settings['shopgate_status']);
        $this->setEncoding($settings['shopgate_encoding']);
        $this->setCustomerNumber($settings['shopgate_customer_number']);
        $this->setShopNumber($settings['shopgate_shop_number']);
        $this->setApikey($settings['shopgate_apikey']);
        $server = $this->storeConfig['shopgate_server'];
        $this->setServer($server);
        if ($server == "custom") {
            $this->setApiUrl($settings['shopgate_custom_server_url']);
        }
    }

    /**
     * set the current shop id depending on the shop version
     *
     * @param $shopNumber
     * @param $shopId
     */
    private function setShopId($shopNumber, $shopId)
    {
        if (is_null($shopId) && !empty($shopNumber)) {
            $this->storeId = $this->opencartDatabase->getStoreIdByShopNumber($shopNumber);
        } else {
            if (!is_null($shopId)) {
                $this->storeId = $shopId;
            }
        }
    }

    /**
     * set the language data depending on the shop version
     */
    private function setLanguageData()
    {
        // overwrite current language (set by client's language detection) with the default frontend language
        $languageCode         = $this->getModel('config')->get('config_language');
        $_SESSION['language'] = $languageCode;

        if ($this->opencartDatabase->assertMinimumVersion('1.4.0')) {
            $languageRow          = $this->opencartDatabase->getLanguage($languageCode);
            $this->languageObject = new Language($languageRow['directory']);
            $this->languageObject->load(
                isset($languageRow['filename'])
                    ? $languageRow['filename']
                    : $languageRow['directory']
            );
        } else {
            $this->languageObject = new Language($languageCode);
        }

        if (!empty($this->languageObject)) {
            $this->language = $this->languageObject->get("code");
        }

        if (is_null($this->languageId)) {
            $this->languageId = $this->opencartDatabase->getLanguageId($languageCode);
        }
    }

    /**
     * initialize the database object
     */
    private function initDatabase()
    {
        if (is_null($this->opencartDatabase)) {
            $this->opencartDatabase = new ShopgateOpencartDatabase();
        }
    }

    /**
     * core method to initialize the whole configuration
     *
     * hint: do not change the method call sequence
     *
     * @param null $shopNumber
     * @param null $shopId
     */
    public function initializeShopgateStoreConfig($shopNumber = null, $shopId = null)
    {
        $this->initDatabase();
        $this->setShopId($shopNumber, $shopId);
        $this->loadSettings();
        $this->setLanguageData();
    }
}
