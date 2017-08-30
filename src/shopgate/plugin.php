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
 * Shopgate Plugin containing main api methods
 *
 * Version information about opencart configuration
 * from version 1.4     => The whole configuration is stored in <setting> table. Until this version opencart doesn't
 * support multi stores from version 1.4.1 => The main configuration is stored in <setting> table. The configuration
 * for multi stores is stored in the <store> table from version 1.5.0.0 => The main and also the store configuration is
 * stored in <settings> table. The <store> table only contains the the store url
 *
 */
class ShopgatePluginOpencart extends ShopgatePlugin
{
    /**
     * @var ShopgateConfigOpencart
     */
    protected $config;

    public function startup()
    {
        if (!isset($_REQUEST['shop_number'])) {
            die('shop_number not set');
        }
        $this->config = new ShopgateConfigOpencart();
        $this->config->initializeShopgateStoreConfig($_REQUEST['shop_number']);
    }

    /**
     * @param string $user
     * @param string $pass
     *
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     */
    public function getCustomer($user, $pass)
    {
        $customer = new ShopgateOpencartExportCustomer();
        $customer->setConfiguration($this->_getConfig());
        $customer->setUser($user);
        $customer->setPassword($pass);

        return $customer->generateData();
    }

    public function rollback()
    {
        $this->getOpencartDatabase()->rollback();
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order)
    {
        if (empty($GLOBALS['registry'])) {
            Registry::set('language', $this->config->getLanguageObject());
        } else {
            $GLOBALS['registry']->set('language', $this->config->getLanguageObject());
        }

        $opencartOrder = new ShopgateOpencartOrder($order);
        $opencartOrder->setConfiguration($this->_getConfig());
        $orderId = $opencartOrder->generateData();

        return array(
            'external_order_id'     => $orderId,
            'external_order_number' => $orderId,
        );
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws ShopgateLibraryException
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $comment            = "";
        $orderId            = $order->getExternalOrderId();
        $opencartDatabase   = $this->getOpencartDatabase();
        $shopgateOrderEntry = $opencartDatabase->getShopgateOrderEntry($order->getOrderNumber());

        if (!$shopgateOrderEntry) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND,
                "Shopgate order number: {$order->getOrderNumber()}."
            );
        }

        $opencartOrder = $opencartDatabase->getOrderById($orderId);

        $statusId = $opencartOrder['order_status_id'] > 0
            ? $opencartOrder['order_status_id']
            : 0;

        $this->config->getLanguageObject()->load('module/shopgate');

        if ($order->getUpdatePayment() && $order->getIsPaid()) {
            $comment                            =
                "Shopgate: " . $this->config->getLanguageObject()->get('payment_received');
            $statusId                           = $this->config->getOrderStatusShippingNotBlocked();
            $shopgateOrderEntry['sync_payment'] = 1;
        }

        if ($order->getUpdateShipping()) {
            if ($order->getIsShippingBlocked()) {
                $comment  = "Shopgate: " . $this->config->getLanguageObject()->get('shipping_blocked');
                $statusId = $this->config->getOrderStatusShippingBlocked();
            } else {
                $comment                             =
                    "Shopgate: " . $this->config->getLanguageObject()->get('shipping_not_blocked');
                $statusId                            = $this->config->getOrderStatusShippingNotBlocked();
                $shopgateOrderEntry['sync_shipment'] = 1;
            }
        }
        if ($order->getIsStorno()) {
            $comment                                 =
                "Shopgate: " . $this->config->getLanguageObject()->get('canceled');
            $statusId                                = $this->config->getOrderStatusCanceled();
            $shopgateOrderEntry['sync_payment']      = 1;
            $shopgateOrderEntry['sync_shipment']     = 1;
            $shopgateOrderEntry['sync_cancellation'] = 1;
        }

        $opencartDatabase->setOrderStatus($orderId, $statusId);
        $opencartDatabase->setOrderHistoryComment($orderId, $statusId, $comment);
        $opencartDatabase->insertShopgateOrderEntry($shopgateOrderEntry);

        return array(
            'external_order_id'     => $order->getExternalOrderId(),
            'external_order_number' => $order->getExternalOrderNumber(),
        );
    }

    protected function createItemsCsv()
    {
        $this->config->getLanguageObject()->load('product/product');
        $grossMarket = $this->_getConfig()->getModel('config')->get('config_tax');
        if (!$grossMarket) {
            $this->useTaxClasses();
        }
        $products = $this->getOpencartDatabase()->getProductsForExport(
            $this->_getConfig()->getStoreId(),
            $this->_getConfig()->getLanguageId(),
            $this->exportLimit,
            $this->exportOffset
        );
        foreach ($products as $product) {
            $productExport = new ShopgateOpencartExportProductCsv($this->config->getLanguageObject());
            $productExport->setDefaultRow($this->buildDefaultItemRow());
            $productExport->setTaxCalculationModel(ShopgateOpencart::getModel('tax'));
            $productExport->setItem($product);
            $productExport->setConfiguration($this->_getConfig());
            $productExport->setHighestSort($this->getOpencartDatabase()->getProductHighestSortOrder());
            $this->addItemRow($productExport->generateData());
        }
    }

    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        $this->config->getLanguageObject()->load('product/product');
        $this->config->getLanguageObject()->load('module/shopgate');
        $products = $this->getOpencartDatabase()->getProductsForExport(
            $this->_getConfig()->getStoreId(),
            $this->_getConfig()->getLanguageId(),
            $limit,
            $offset,
            $uids
        );

        foreach ($products as $product) {
            $productExport = new ShopgateOpencartExportProductXml($this->config->getLanguageObject());
            $productExport->setTaxCalculationModel(ShopgateOpencart::getModel('tax'));
            $productExport->setItem($product);
            $productExport->setConfiguration($this->_getConfig());
            $productExport->setHighestSort($this->getOpencartDatabase()->getProductHighestSortOrder());

            try {
                $productData = $productExport->generateData();
                $this->addItem($productData);
            } catch (ShopgateProductSkippedException $e) {
                $this->log(
                    'product uid: ' . $product['id'] . ' was skipped and is NOT part of the export: ' .
                    $e->getMessage(),
                    SHOPGATELOGGER::LOGTYPE_DEBUG
                );
            }
        }
    }

    protected function createCategoriesCsv()
    {
        $categories = $this->getOpencartDatabase()->getCategoriesForExport(
            $this->_getConfig()->getStoreId(),
            $this->_getConfig()->getLanguageId(),
            $this->exportLimit,
            $this->exportOffset
        );

        foreach ($categories as $category) {
            $categoryExport = new ShopgateOpencartExportCategoryCsv();
            $categoryExport->setDefaultRow($this->buildDefaultCategoryRow());
            $categoryExport->setHighestSort($this->getOpencartDatabase()->getCategoryHighestSortOrder());
            $categoryExport->setItem($category);
            $this->addCategoryRow($categoryExport->generateData());
        }
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     * @param array    $uids
     */
    protected function createCategories($limit = null, $offset = null, array $uids = array())
    {
        $categories = $this->getOpencartDatabase()->getCategoriesForExport(
            $this->_getConfig()->getStoreId(),
            $this->_getConfig()->getLanguageId(),
            $limit,
            $offset,
            $uids
        );

        foreach ($categories as $category) {
            $categoryExport = new ShopgateOpencartExportCategoryXml();
            $categoryExport->setHighestSort($this->getOpencartDatabase()->getCategoryHighestSortOrder());
            $categoryExport->setItem($category);
            $this->addCategoryModel($categoryExport->generateData());
        }
    }

    protected function createReviewsCsv()
    {
        $reviews = $this->getOpencartDatabase()->getReviewsForExport();

        foreach ($reviews as $review) {
            $row         = $this->buildDefaultReviewRow();
            $reviewModel = new ShopgateOpencartExportReviewCsv();
            $reviewModel->setDefaultRow($row);
            $reviewModel->setItem($review);
            $this->addReviewRow($reviewModel->generateData());
        }
    }

    /**
     * @param null|int $limit
     * @param null|int $offset
     * @param array    $uids
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        $reviews = $this->getOpencartDatabase()->getReviewsForExport($limit, $offset, $uids);
        foreach ($reviews as $review) {
            $reviewModel = new ShopgateOpencartExportReviewXml();
            $reviewModel->setItem($review);
            $this->addReviewModel($reviewModel->generateData());
        }
    }

    /**
     * @return ShopgateOpencartDatabase
     */
    protected function getOpencartDatabase()
    {
        return $this->_getConfig()->getOpencartDatabase();
    }

    /**
     * @return ShopgateConfigOpencart
     */
    protected function _getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $jobname
     * @param array  $params
     * @param string $message
     * @param int    $errorcount
     */
    public function cron($jobname, $params, &$message, &$errorcount)
    {
        $cron = new ShopgateOpencartCron($jobname);
        $cron->setConfiguration($this->_getConfig());
        $cron->setMerchantApi($this->merchantApi);
        list($messageTmp, $errorcountTmp) = $cron->generateData();
        $message    .= $messageTmp;
        $errorcount += $errorcountTmp;
    }

    /**
     * @param string           $user
     * @param string           $pass
     * @param ShopgateCustomer $customer
     *
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        $database      = $this->getOpencartDatabase();
        $checkCustomer = $database->getCustomerByMail($user);
        if (strtolower($user) == strtolower($checkCustomer['email'])) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
        }

        $openCartCustomer = new ShopgateOpencartCustomer($customer);
        $openCartCustomer->setConfiguration($this->_getConfig());
        $openCartCustomer->setUser($user);
        $openCartCustomer->setPassword($pass);
        $openCartCustomer->generateData();
    }

    /**
     * @param ShopgateCart $cart
     *
     * @return array
     */
    public function checkStock(ShopgateCart $cart)
    {
        $stockItems = new ShopgateOpencartExportStock($cart);
        $stockItems->setConfiguration($this->_getConfig());

        return $stockItems->generateData();
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        $settings = new ShopgateOpencartExportSettings();
        $settings->setConfiguration($this->_getConfig());

        return $settings->generateData();
    }

    /**
     * @param string $customerToken
     * @param string $customerLanguage
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return array|ShopgateExternalOrder[]
     * @throws ShopgateLibraryException
     */
    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        $split         = explode("-", $customerToken);
        $customerId    = $split[0];
        $customerAdded = $split[1];
        $database      = $this->getOpencartDatabase();
        $customer      = $database->getCustomerById($customerId);

        if (!$customer || strtotime($customer['date_added']) != $customerAdded) {
            throw new ShopgateLibraryException(73);
        }

        $orders       = $database->getOrdersFromCustomer($customerId, $limit, $offset);
        $resultOrders = array();
        foreach ($orders as $order) {
            $shopgateOrder = new ShopgateOpencartExportOrder($order);
            $shopgateOrder->setConfiguration($this->_getConfig());
            $resultOrders[] = $shopgateOrder->generateData();
        }

        return $resultOrders;
    }

    /**
     * @param ShopgateCart $cart
     *
     * @return array
     */
    public function checkCart(ShopgateCart $cart)
    {
        $exportCartModel = new ShopgateOpencartExportCart($cart);
        $exportCartModel->setConfiguration($this->_getConfig());

        return $exportCartModel->generateData();
    }

    /**
     * get additional data from the magento instance
     *
     * @return array
     */
    public function createShopInfo()
    {
        $shopInfo                   = parent::createShopInfo();
        $shopInfo['category_count'] = $this->getOpencartDatabase()->getCategoriesCount();
        $shopInfo['item_count']     = $this->getOpencartDatabase()->getProductsCount();
        $shopInfo['review_count']   = $this->getOpencartDatabase()->getReviewsCount();

        return $shopInfo;
    }

    /**
     * @return array
     */
    public function createPluginInfo()
    {
        $pluginInfos = array();

        if (defined('VERSION')) {
            $pluginInfos['OpenCart Version'] = VERSION;
        }

        $pluginInfos['Assumed OpenCart Version'] = $this->config->getOpencartDatabase()->getOpenCartVersion();

        return $pluginInfos;
    }

    /**
     * @param ShopgateCart $cart
     *
     * @return array[]|ShopgateExternalCoupon[]
     */
    public function redeemCoupons(ShopgateCart $cart)
    {
        return $cart->getExternalCoupons();
    }

    protected function createMediaCsv()
    {
        // TODO: Implement createMediaCsv() method.
    }

    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }
}
