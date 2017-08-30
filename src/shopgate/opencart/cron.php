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
 * Processes cron orders
 */
class ShopgateOpencartCron extends ShopgateOpencartAbstract
{
    /** @var ShopgateMerchantApi */
    protected $_merchantApi;

    /** @var ShopgateOpencartDatabase */
    protected $_database;

    public function generateData()
    {
        $jobname         = $this->_data;
        $this->_database = $this->_getOpencartDatabase();

        $orders = array();
        if ($jobname == 'set_shipping_completed') {
            $orders = $this->_database->getUnsyncedShippingOrders($this->_config->getOrderStatusShipped());
        } else {
            if ($jobname == 'cancel_orders') {
                $orders = $this->_database->getUnsyncedCancellationOrders($this->_config->getOrderStatusCanceled());
            }
        }

        $errorCount   = 0;
        $successCount = 0;
        $message      = empty($orders)
            ? "$jobname: no orders to process\n"
            : '';
        foreach ($orders as $shopgateOrderEntry) {
            $order = $this->_database->getOrderById($shopgateOrderEntry['external_order_number']);
            if (!empty($order)) {
                try {
                    if ($jobname == 'set_shipping_completed') {
                        $this->setShippingCompleted($shopgateOrderEntry);
                    } else {
                        if ($jobname == 'cancel_orders') {
                            $this->cancelOrder($shopgateOrderEntry);
                        }
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $message .= "$jobname: {$e->getMessage()}\n";
                    $errorCount++;
                }
            }
        }
        if ($successCount) {
            $message .= "$jobname: successfully processed $successCount orders\n";
        }

        return array($message, $errorCount);
    }

    /**
     * @param $shopgateOrderEntry
     *
     * @throws Exception
     * @throws ShopgateMerchantApiException
     */
    private function setShippingCompleted($shopgateOrderEntry)
    {
        try {
            $response = $this->_merchantApi->setOrderShippingCompleted($shopgateOrderEntry['shopgate_order_number']);
            if (!$response->getErrors()) {
                $shopgateOrderEntry['sync_shipment'] = 1;
                $this->_database->insertShopgateOrderEntry($shopgateOrderEntry);
            }
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED) {
                $shopgateOrderEntry['sync_shipment'] = 1;
                $this->_database->insertShopgateOrderEntry($shopgateOrderEntry);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param $shopgateOrderEntry
     *
     * @throws Exception
     * @throws ShopgateMerchantApiException
     */
    private function cancelOrder($shopgateOrderEntry)
    {
        try {
            $response = $this->_merchantApi->cancelOrder($shopgateOrderEntry['shopgate_order_number'], true);
            if (!$response->getErrors()) {
                $shopgateOrderEntry['sync_cancellation'] = 1;
                $this->_database->insertShopgateOrderEntry($shopgateOrderEntry);
            }
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == ShopgateMerchantApiException::ORDER_ALREADY_CANCELLED) {
                $shopgateOrderEntry['sync_cancellation'] = 1;
                $this->_database->insertShopgateOrderEntry($shopgateOrderEntry);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param ShopgateMerchantApiInterface $merchantApi
     */
    public function setMerchantApi($merchantApi)
    {
        $this->_merchantApi = $merchantApi;
    }
}
