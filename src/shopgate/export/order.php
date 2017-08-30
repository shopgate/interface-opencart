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
 * Order export
 */
class ShopgateOpencartExportOrder extends ShopgateOpencartAbstract
{
    /** @var float */
    protected $_orderTaxAmount = 0;

    /** @var  float */
    protected $_orderTaxPercent;

    /**
     * @return ShopgateOrder
     */
    public function generateData()
    {
        $shopgateOrder = new ShopgateExternalOrder();
        $opencartOrder = $this->_data;

        $shopgateOrder->setExternalOrderId($opencartOrder['order_id']);
        $shopgateOrder->setExternalOrderNumber($opencartOrder['order_id']);
        $shopgateOrder->setCreatedTime($opencartOrder['date_added']);
        $shopgateOrder->setMail($opencartOrder['email']);
        $shopgateOrder->setPhone($opencartOrder['telephone']);
        $shopgateOrder->setInvoiceAddress($this->buildInvoiceAddress($opencartOrder));
        $shopgateOrder->setDeliveryAddress($this->buildDeliveryAddress($opencartOrder));
        $shopgateOrder->setCurrency($opencartOrder['currency_code']);
        $shopgateOrder->setAmountComplete($opencartOrder['total']);
        $shopgateOrder->setIsPaid(null);
        $shopgateOrder->setPaymentMethod($opencartOrder['payment_method']);
        $shopgateOrder->setIsShippingCompleted(null);
        $shopgateOrder->setExternalCoupons($this->buildExternalCoupons($opencartOrder));
        $shopgateOrder->setItems($this->buildItems($opencartOrder));
        $shopgateOrder->setOrderTaxes($this->buildOrderTax());

        return $shopgateOrder;
    }

    /**
     * @return ShopgateExternalOrderTax
     */
    public function buildOrderTax()
    {
        $orderTax = new ShopgateExternalOrderTax();
        $orderTax->setAmount($this->_orderTaxAmount);
        $orderTax->setLabel((float)$this->_orderTaxPercent . "%");
        $orderTax->setTaxPercent($this->_orderTaxPercent);

        return array($orderTax);
    }

    /**
     * @param array $order
     *
     * @return array
     */
    public function buildExternalCoupons($order)
    {
        if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')) {
            // Vouchers are available from 1.5.0.0
            return array();
        }

        $vouchers        = $this->_getOpencartDatabase()->getOrderVouchers($order['order_id']);
        $responseCoupons = array();

        foreach ($vouchers as $voucher) {
            $shopgateCoupon = new ShopgateExternalCoupon();
            $shopgateCoupon->setCurrency($order['currency_code']);
            $shopgateCoupon->setName($voucher['description']);
            $shopgateCoupon->setAmountGross($voucher['amount']);
            $shopgateCoupon->setCode($voucher['code']);

            $responseCoupons[] = $shopgateCoupon;
        }

        return $responseCoupons;
    }

    /**
     * @param array $order
     *
     * @return array
     */
    public function buildItems($order)
    {
        $responseItems = array();
        $orderItems    = $this->_getOpencartDatabase()->getOrderItems($order['order_id']);

        foreach ($orderItems as $orderItem) {
            $shopgateOrderItem      = new ShopgateExternalOrderItem();
            $taxAmount              = $orderItem['tax'] * $orderItem['total'] / 100;
            $this->_orderTaxAmount  += $taxAmount;
            $this->_orderTaxPercent = $orderItem['tax'];

            $shopgateOrderItem->setItemNumber($orderItem['product_id']);
            $shopgateOrderItem->setItemNumberPublic($orderItem['model']);
            $shopgateOrderItem->setQuantity($orderItem['quantity']);
            $shopgateOrderItem->setName($orderItem['name']);
            $shopgateOrderItem->setUnitAmount($orderItem['total'] - $taxAmount);
            $shopgateOrderItem->setUnitAmountWithTax($orderItem['total']);
            $shopgateOrderItem->setTaxPercent($orderItem['tax']);
            $shopgateOrderItem->setCurrency($order['currency_code']);

            $responseItems[] = $shopgateOrderItem;
        }

        return $responseItems;
    }

    /**
     * @param $order
     *
     * @return ShopgateAddress
     */
    public function buildDeliveryAddress($order)
    {
        $deliveryAddress = new ShopgateAddress();
        $country         = $this->_getOpencartDatabase()->getCountryById($order['shipping_country_id']);
        $zone            = $this->_getOpencartDatabase()->getZoneById($order['shipping_zone_id']);

        $deliveryAddress->setAddressType(ShopgateAddress::DELIVERY);
        $deliveryAddress->setFirstName($order['shipping_firstname']);
        $deliveryAddress->setLastName($order['shipping_lastname']);
        $deliveryAddress->setStreet1($order['shipping_address_1']);
        $deliveryAddress->setStreet2($order['shipping_address_2']);
        $deliveryAddress->setCompany($order['shipping_company']);
        $deliveryAddress->setCity($order['shipping_city']);
        $deliveryAddress->setZipcode($order['shipping_postcode']);
        $deliveryAddress->setCountry($country['iso_code_2']);
        $deliveryAddress->setState($zone['code']);
        $deliveryAddress->setMail($order['email']);
        $deliveryAddress->setPhone($order['telephone']);

        return $deliveryAddress;
    }

    /**
     * @param array $order
     *
     * @return ShopgateAddress
     */
    public function buildInvoiceAddress($order)
    {
        $invoiceAddress = new ShopgateAddress();
        $country        = $this->_getOpencartDatabase()->getCountryById($order['payment_country_id']);
        $zone           = $this->_getOpencartDatabase()->getZoneById($order['payment_zone_id']);

        $invoiceAddress->setAddressType(ShopgateAddress::INVOICE);
        $invoiceAddress->setFirstName($order['payment_firstname']);
        $invoiceAddress->setLastName($order['payment_lastname']);
        $invoiceAddress->setStreet1($order['payment_address_1']);
        $invoiceAddress->setStreet2($order['payment_address_2']);
        $invoiceAddress->setCompany($order['payment_company']);
        $invoiceAddress->setCity($order['payment_city']);
        $invoiceAddress->setZipcode($order['payment_postcode']);
        $invoiceAddress->setCountry($country['iso_code_2']);
        $invoiceAddress->setState($zone['code']);
        $invoiceAddress->setMail($order['email']);
        $invoiceAddress->setPhone($order['telephone']);

        return $invoiceAddress;
    }
}
