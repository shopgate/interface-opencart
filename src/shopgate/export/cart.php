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
 * Cart export
 */
class ShopgateOpencartExportCart extends ShopgateOpencartAbstract
{
    /** @var  ShopgateCart */
    protected $_data;

    /** @var ShopgateOpencartCartShipping */
    protected $_shopgateOpencartShipping;

    protected function initialize()
    {
        $this->_loadModel('localisation/country');
        $this->_loadModel('localisation/zone');
        $this->_loadModel('catalog/product');

        if ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
            && !$this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')
        ) {
            $this->_loadModel('setting/extension');
        }

        $this->_shopgateOpencartShipping = new ShopgateOpencartCartShipping(
            $this->_config,
            $this->_config->getOpencartDatabase(),
            $this->_getModel('load'),
            $this->_getModel('config'),
            $this->_getModel('model_localisation_country'),
            $this->_getModel('session'),
            $this->_getModel('cart')
        );
    }

    /**
     * @return array
     */
    public function generateData()
    {
        $this->initialize();

        $shopgateCart = $this->_data;

        if ($shopgateCart->getExternalCustomerId()) {
            /** @var Session $session */
            $session                      = $this->_getModel('session');
            $session->data['customer_id'] = $shopgateCart->getExternalCustomerId();

            if (!empty($GLOBALS['registry'])) {
                if ($this->_config->getOpencartDatabase()->assertMinimumVersion('2.2.0.0')) {
                    $namespaceHelper = new ShopgateOpencartNamespaces();
                    $GLOBALS['registry']->set('customer', $namespaceHelper->getCustomerClass());
                } else {
                    /** @noinspection PhpUndefinedClassInspection */
                    $GLOBALS['registry']->set('customer', new Customer($GLOBALS['registry']));
                }
            } else {
                // OpenCart 1.3 has a static Registry class and Customer::__construct() has no parameters.
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                /** @noinspection PhpParamsInspection */
                /** @noinspection PhpUndefinedClassInspection */
                Registry::set('customer', new Customer());
            }
        }

        /** @var Cart $shopCart */
        $shopCart        = $this->_getModel('cart');
        $externalCoupons = array();
        $cartTotal       = 0;

        foreach ($shopgateCart->getExternalCoupons() as $coupon) {
            $externalCoupon = new ShopgateExternalCoupon();
            $externalCoupon->setIsValid(0);
            $externalCoupon->setCode($coupon->getCode());

            switch ($this->_getDiscountTypeByCode($coupon->getCode())) {
                case self::DEFAULT_IDENTIFIER_COUPON:
                    $shopCart->session->data[self::DEFAULT_IDENTIFIER_COUPON] = $coupon->getCode();
                    break;
                case self::DEFAULT_IDENTIFIER_VOUCHER:
                    $shopCart->session->data[self::DEFAULT_IDENTIFIER_VOUCHER] = $coupon->getCode();
                    break;
            }

            $externalCoupons[$coupon->getCode()] = $externalCoupon;
        }

        $items = $this->_prepareItems($shopgateCart);

        return array(
            "currency"         => $shopCart->session->data['currency'],
            "customer"         => $this->_processCustomer($shopgateCart),
            "external_coupons" => $this->_processCoupons($externalCoupons, $shopgateCart, $cartTotal),
            "shipping_methods" => $this->_shopgateOpencartShipping->returnShippingMethods($shopgateCart),
            "payment_methods"  => $this->_processPaymentMethods($shopgateCart, $cartTotal),
            "items"            => $this->_processItems($items),
        );
    }

    /**
     * @param ShopgateCart $shopgateCart
     *
     * @return ShopgateCartCustomer
     */
    protected function _processCustomer($shopgateCart)
    {
        $customer         = new ShopgateCartCustomer();
        $customerGroup    = new ShopgateCartCustomerGroup();
        $customerGroupKey = null;

        $customerId = $shopgateCart->getExternalCustomerId();
        if (!empty($customerId)) {
            $openCartCustomer = $this->_getOpencartDatabase()
                ->getCustomerById($shopgateCart->getExternalCustomerId());

            if (!is_null($openCartCustomer['customer_group_id'])) {
                $customerGroupKey = $openCartCustomer['customer_group_id'];
            }
        } else {
            $customerGroupKey = (int)ShopgateOpencart::getModel('config')
                ->get('config_customer_group_id');
        }
        $customerGroup->setId($customerGroupKey);
        $customer->setCustomerGroups(array($customerGroup));

        return $customer;
    }

    /**
     * @param ShopgateCart $cart
     * @param float        $total
     *
     * @return array
     */
    protected function _processPaymentMethods($cart, $total)
    {
        $paymentMethods = array();
        $invoiceAddress = $cart->getInvoiceAddress();
        if (!$invoiceAddress) {
            return array();
        }
        $invoiceAddressData = $this->_shopgateOpencartShipping->buildAddress($invoiceAddress);
        if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
            || $this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')
        ) {
            $paymentExtensions = $this->_config->getOpencartDatabase()->getExtensions('payment');
        } else {
            $paymentExtensions = $this->_getModel('model_setting_extension')->getExtensions('payment');
        }

        foreach ($paymentExtensions as $paymentExtension) {
            $extensionCode = (isset($paymentExtension['code'])
                ? $paymentExtension['code']
                : $paymentExtension['key']);
            $configKey     = $extensionCode . '_status';
            if (!$this->_getConfig($configKey)) {
                continue;
            }
            $this->_loadModel('payment/' . $extensionCode);
            $methodModel = $this->_getModel('model_payment_' . $extensionCode);
            $method      = $methodModel->getMethod($invoiceAddressData, $total);
            if ($method) {
                $shopgatePaymentMethod = new ShopgatePaymentMethod();
                $shopgatePaymentMethod->setId(
                    isset($method['code'])
                        ? $method['code']
                        : $method['id']
                );// in older versions its "id" instead of "code"
                $shopgatePaymentMethod->setAmount(0.00);
                $shopgatePaymentMethod->setAmountWithTax(0.00);
                $shopgatePaymentMethod->setTaxClass('');
                $shopgatePaymentMethod->setTaxPercent(0.00);

                $paymentMethods[] = $shopgatePaymentMethod;
            }
        }

        return $paymentMethods;
    }

    /**
     * @param ShopgateOrderItem $shopgateOrderItem
     *
     * @return string
     */
    protected function _getProductId(ShopgateOrderItem $shopgateOrderItem)
    {
        $parentItemNumber = $shopgateOrderItem->getParentItemNumber();

        return !empty($parentItemNumber)
            ? $parentItemNumber
            : $shopgateOrderItem->getItemNumber();
    }

    /**
     * @param $product
     *
     * @return string
     */
    protected function _buildProductId($product)
    {
        $optionIds = array();
        if (is_array($product['option'])) {
            foreach ($product['option'] as $option) {
                $optionIds[] =
                    (isset($option['product_option_id'])
                        ? $option['product_option_id']
                        : 'no_option_id') . '-'
                    . $option['product_option_value_id'];
            }
        }

        return $product['product_id'] . (!empty($optionIds)
                ? '_' . implode('_', $optionIds)
                : '');
    }

    /**
     * @param int   $productId
     * @param array $options
     *
     * @return string
     */
    protected function _buildProductIdWithOptions($productId, $options)
    {
        $optionIds = array();
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $optionIds[] =
                    ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
                        ? $key
                        : 'no_option_id') . '-'
                    . (is_string($option)
                        ? $option
                        : current($option));
            }
        }

        return $productId . (!empty($optionIds)
                ? '_' . implode('_', $optionIds)
                : '');
    }

    /**
     * @param ShopgateCart $cart
     *
     * @return mixed
     */
    protected function _prepareItems(ShopgateCart $cart)
    {
        /** @var Cart $shopCart */
        $shopCart = $this->_getModel('cart');

        $responseItems = array();
        $productStock  = new ProductStock($this->_config);
        foreach ($cart->getItems() as $item) {
            $shopgateCartItem = $productStock->createShopgateCartItem($item);

            $options   = $this->_buildOptionArray($shopgateCartItem, $item);
            $productId = (int)$this->_getProductId($item);

            if ($shopgateCartItem->getError() == ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND) {
                $responseItems[$this->_buildProductIdWithOptions($productId, $options)] = $shopgateCartItem;
                continue;
            }

            // Add product to virtual cart
            $shopCart->add($productId, $item->getQuantity(), $options);

            $shopgateCartItem->setQtyBuyable(min($shopgateCartItem->getStockQuantity(), $item->getQuantity()));

            if ($shopgateCartItem->getQtyBuyable() < $item->getQuantity()) {
                $shopgateCartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE);
            }

            $responseItems[$this->_buildProductIdWithOptions($productId, $options)] = $shopgateCartItem;
        }

        return $responseItems;
    }

    /**
     * @param ShopgateCartItem[] $shopgateCartItems
     *
     * @return array
     */
    protected function _processItems(array $shopgateCartItems)
    {
        /** @noinspection PhpUndefinedNamespaceInspection */
        /** @noinspection PhpUndefinedClassInspection */
        /** @var \Cart\Cart $shopCart */
        $shopCart = $this->_getModel('cart');

        /** @noinspection PhpUndefinedMethodInspection */
        foreach ($shopCart->getProducts() as $product) {
            if (!isset($shopgateCartItems[$this->_buildProductId($product)])) {
                // probably product or product options not found
                continue;
            }
            /**
             * @var $shopgateCartItem ShopgateCartItem
             */
            $shopgateCartItem = $shopgateCartItems[$this->_buildProductId($product)];
            $shopgateCartItem->setUnitAmount($product['price']);
            $error = $shopgateCartItem->getError();

            if (empty($error)
                && isset($product['minimum'])
                && $shopgateCartItem->getQtyBuyable() < $product['minimum']
            ) {
                $shopgateCartItem->setQtyBuyable($product['minimum']);
                $shopgateCartItem->setError(
                    ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_UNDER_MINIMUM_QUANTITY
                );
            }

            if (
                empty($error)
                && isset($product['maximum'])
                && $product['maximum'] > 0
                && $shopgateCartItem->getQtyBuyable() > $product['maximum']
            ) {
                $shopgateCartItem->setQtyBuyable($product['maximum']);
                $shopgateCartItem->setError(
                    ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_OVER_MAXIMUM_QUANTITY
                );
            }

            $shopgateCartItems[$this->_buildProductId($product)] = $shopgateCartItem;
        }

        return $shopgateCartItems;
    }

    /**
     * @param ShopgateExternalCoupon[] $externalCoupons
     * @param ShopgateCartBase         $shopgateCart
     * @param float                    $total
     *
     * @return array
     */
    protected function _processCoupons(array $externalCoupons, ShopgateCartBase $shopgateCart, &$total)
    {
        if (empty($externalCoupons)) {
            return $externalCoupons;
        }

        /** @var Cart $shopCart */
        $shopCart      = $this->_getModel('cart');
        $couponCode    = (isset($shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON])
            ? $shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON]
            : null);
        $voucherCode   = (isset($shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER])
            ? $shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER]
            : null);
        $shopCartTaxes = $shopCart->getTaxes();
        $totalData     = array();
        $sortOrder     = array();

        if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
            || $this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')
        ) {
            $totalExtensions = $this->_config->getOpencartDatabase()->getExtensions('total');
        } else {
            $totalExtensions = $this->_getModel('model_setting_extension')->getExtensions('total');
        }

        foreach ($totalExtensions as $key => $value) {
            // 'code' from version 1.5.0.0 before that it was 'key'
            $configKey       = (isset($value['code'])
                    ? $value['code']
                    : $value['key']) . '_sort_order';
            $sortOrder[$key] = $this->_getConfig($configKey);
        }
        array_multisort($sortOrder, SORT_ASC, $totalExtensions);

        foreach ($totalExtensions as $key => $value) {
            $extensionCode = (isset($value['code'])
                ? $value['code']
                : $value['key']);

            $configKey = $extensionCode . '_status';
            if (!$this->_getConfig($configKey)) {
                continue;
            }

            $this->_loadModel('total/' . $extensionCode);

            if ($this->_config->getOpencartDatabase()->assertMinimumVersion('2.2.0.0')) {
                $this->_getModel('model_total_' . $extensionCode)->getTotal(
                    array(
                        'totals' => &$totalData,
                        'taxes'  => &$shopCartTaxes,
                        'total'  => &$total,
                    )
                );
            } else {
                $this->_getModel('model_total_' . $extensionCode)->getTotal($totalData, $total, $shopCartTaxes);
            }

            if ($extensionCode == ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON) {
                $couponTotal = array_pop($totalData);
                if ($couponTotal && array_key_exists($couponCode, $externalCoupons)
                    && (!isset($couponTotal['code']) || $couponTotal['code'] == 'coupon')
                ) {
                    // beginning from OpenCart version 1.5.0.0 the column 'code' is available
                    /** @var ShopgateExternalCoupon $externalCoupon */
                    $externalCoupon = $externalCoupons[$couponCode];

                    $couponInfo =
                        $this->_getModel($this->_getDiscountModel(ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON))
                            ->getCoupon($couponCode);

                    if ($couponInfo) {
                        // we only get a valid $couponInfo if the coupon van be redeemed
                        $externalCoupon->setIsValid(true);
                        $externalCoupon->setName($couponInfo['name']);
                        $externalCoupon->setAmountNet(abs($couponTotal['value']));
                        $externalCoupon->setIsFreeShipping($couponInfo['shipping']);
                        $externalCoupon->setTaxType('auto');
                        $externalCoupon->setCurrency($shopgateCart->getCurrency());
                        $externalCoupon->setInternalInfo($this->jsonEncode(array('is_voucher' => 0)));
                        $externalCoupons[$shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON]] =
                            $externalCoupon;
                    }
                }
            }

            if ($extensionCode == ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER) {
                $voucherTotal = array_pop($totalData);
                if ($voucherTotal && array_key_exists($voucherCode, $externalCoupons)
                    && (!isset($voucherTotal['code']) || $voucherTotal['code'] == 'voucher')
                ) {
                    // beginning from OpenCart version 1.5.0.0 the column 'voucher' is available
                    /** @var ShopgateExternalCoupon $externalCoupon */
                    $externalCoupon = $externalCoupons[$voucherCode];

                    $voucherInfo =
                        $this->_getModel($this->_getDiscountModel(ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER))
                            ->getVoucher($voucherCode);

                    if ($voucherInfo) {
                        // we only get a valid $voucherInfo if the voucher can be redeemed
                        $externalCoupon->setIsValid(true);
                        $externalCoupon->setName($voucherInfo['theme']);
                        $externalCoupon->setAmountNet(abs($voucherInfo['amount']));
                        $externalCoupon->setIsFreeShipping(false);
                        $externalCoupon->setTaxType('not_taxable');
                        $externalCoupon->setCurrency($shopgateCart->getCurrency());
                        $externalCoupon->setInternalInfo($this->jsonEncode(array('is_voucher' => 1)));
                        $externalCoupons[$shopCart->session->data[ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER]] =
                            $externalCoupon;
                    }
                }
            }
        }

        return $externalCoupons;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function _getDiscountModel($type)
    {
        if (!defined('VERSION') || version_compare(VERSION, '2.1.0.0', '<')) {
            switch ($type) {
                case ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON:
                    return 'model_checkout_coupon';
                case ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER:
                    return 'model_checkout_voucher';
            }

            return 'model_checkout_coupon';
        } else {
            switch ($type) {
                case ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_COUPON:
                    return 'model_total_coupon';
                case ShopgateOpencartAbstract::DEFAULT_IDENTIFIER_VOUCHER:
                    return 'model_total_voucher';
            }

            return 'model_total_coupon';
        }
    }

    /**
     * @param   ShopgateCartItem  $cartItem
     * @param   ShopgateOrderItem $item
     *
     * @return array
     */
    protected function _buildOptionArray($cartItem, $item)
    {
        $options = array();

        foreach ($cartItem->getOptions() as $option) {
            $localOption                                =
                $this->_getOpencartDatabase()->getProductOptionValue($option->getValueNumber());
            $options[$localOption['product_option_id']] = $option->getValueNumber();
        }

        foreach ($cartItem->getInputs() as $input) {
            $options[$input->getUserInput()] = $input->getUserInput();
        }

        $internalOrderInfo = $this->jsonDecode($item->getInternalOrderInfo(), true);
        if (isset($internalOrderInfo['option_selection'])) {
            foreach ($internalOrderInfo['option_selection'] as $optionId => $optionData) {
                $productOptionValueId = $optionData['product_option_value_id'];

                if ($productOptionValueId == 0) {
                    continue;
                }

                if ($optionData['type'] == Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_CHECKBOX) {
                    $optionNumbers             = explode('_', $optionId);
                    $productOptionId           = $optionNumbers[0];
                    $productOptionValueId      = $optionNumbers[1];
                    $options[$productOptionId] =
                        empty($options[$productOptionId]) || !is_array($options[$productOptionId])
                            ? array($productOptionValueId)
                            : array_push($options[$productOptionId], $productOptionValueId);
                } else {
                    $options[$optionId] = $productOptionValueId;
                }
            }
        }

        return $options;
    }
}
