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
 * Import order to opencart
 */
class ShopgateOpencartOrder extends ShopgateOpencartAbstract
{
    /** @var ShopgateOrder */
    protected $_data;

    /** @var int */
    protected $_couponTotal = 0;

    /** @var int */
    protected $_voucherTotal = 0;

    /** @var null|string */
    protected $_couponCode = null;

    /** @var null|string */
    protected $_voucherCode = null;

    /**
     * @return int
     * @throws ShopgateLibraryException
     */
    public function generateData()
    {
        $order              = $this->_data;
        $opencartDatabase   = $this->_getOpencartDatabase();
        $customerId         = $order->getExternalCustomerId();
        $shopgateOrderEntry = $opencartDatabase->getShopgateOrderEntry($order->getOrderNumber());
        if ($shopgateOrderEntry) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER);
        }
        if (empty($customerId)) {
            $opencartCustomer = $opencartDatabase->getCustomerByMail($order->getMail());
        } else {
            $opencartCustomer = $opencartDatabase->getCustomerById($customerId);
        }
        $deliveryAddress = $order->getDeliveryAddress();
        $invoiceAddress  = $order->getInvoiceAddress();

        if (empty($opencartCustomer)) {
            $customerId      = 0;
            $customerGroupId = 1;
            $firstname       = $invoiceAddress->getFirstName();
            $lastname        = $invoiceAddress->getLastName();
            $telephone       = $order->getPhone();
            $fax             = "";
            $email           = $order->getMail();
        } else {
            $customerId      = $opencartCustomer["customer_id"];
            $customerGroupId = $opencartCustomer["customer_group_id"];
            $firstname       = $opencartCustomer["firstname"];
            $lastname        = $opencartCustomer["lastname"];
            $telephone       = $opencartCustomer["telephone"];
            $fax             = $opencartCustomer["fax"];
            $email           = $opencartCustomer["email"];
        }

        $opencartOrder                      = array();
        $opencartOrder['invoice_prefix']    = $this->_getConfiguration()->getInvoicePrefix();
        $opencartOrder['store_id']          = $this->_getConfiguration()->getStoreId();
        $opencartOrder['store_name']        = $this->_getConfiguration()->getShopname();
        $opencartOrder['store_url']         = HTTP_SERVER;
        $opencartOrder['customer_id']       = $customerId;
        $opencartOrder['customer_group_id'] = $customerGroupId;
        $opencartOrder['firstname']         = $firstname;
        $opencartOrder['lastname']          = $lastname;
        $opencartOrder['email']             = $email;
        $opencartOrder['telephone']         = $telephone;
        $opencartOrder['fax']               = $fax;

        $country   = $opencartDatabase->getCountryByIsoCode($invoiceAddress->getCountry());
        $zoneSplit = explode('-', $deliveryAddress->getState());
        $stateCode = !empty($zoneSplit[1])
            ? $zoneSplit[1]
            : $deliveryAddress->getState();
        if (!empty($stateCode)) {
            $zone = $opencartDatabase->getZone($country["country_id"], $stateCode);
        }

        $opencartLanguage = ShopgateOpencart::loadLanguageFrom('module/shopgate', 'admin');
        $paymentInfos     = $order->getPaymentInfos();
        $comment          =
            sprintf($opencartLanguage->get('order_comment_processed_by_shopgate'), $order->getOrderNumber());
        $paymentMethod    = "Mobile Payment";
        $paymentCode      = "shopgatepay";
        switch ($order->getPaymentMethod()) {
            case ShopgateOrder::COD:
                $paymentMethod = "Cash On Delivery";
                $paymentCode   = "cod";
                break;
            case ShopgateOrder::KLARNA_INV:
                $paymentMethod = "Klarna Invoice";
                $paymentCode   = "klarna_invoice";
                break;
            case ShopgateOrder::DT_CC:
            case ShopgateOrder::BILLSAFE:
            case ShopgateOrder::SHOPGATE:
            case ShopgateOrder::PREPAY:
            case ShopgateOrder::INVOICE:
            case ShopgateOrder::DEBIT:
                break;
        }

        if ($order->getIsTest()) {
            $comment .= $opencartLanguage->get('order_comment_test_order');
        }

        $comment .= $this->generatePaymentInfos($paymentInfos);

        foreach ($order->getCustomFields() as $customField) {
            $comment .= $customField->getLabel() . ': ' . $customField->getValue() . "\n";
        }

        $opencartOrder['payment_firstname']  = $invoiceAddress->getFirstName();
        $opencartOrder['payment_lastname']   = $invoiceAddress->getLastName();
        $opencartOrder['payment_company']    = $invoiceAddress->getCompany();
        $opencartOrder['payment_company_id'] = "";
        $opencartOrder['payment_tax_id']     = "";
        $opencartOrder['payment_address_1']  = $invoiceAddress->getStreet1();
        $opencartOrder['payment_address_2']  = $invoiceAddress->getStreet2();
        $opencartOrder['payment_city']       = $invoiceAddress->getCity();
        $opencartOrder['payment_postcode']   = $invoiceAddress->getZipcode();
        $opencartOrder['payment_country']    = $country["name"];
        $opencartOrder['payment_country_id'] = $country["country_id"];
        if (!empty($zone)) {
            $opencartOrder['payment_zone']    = $zone["name"];
            $opencartOrder['payment_zone_id'] = $zone["zone_id"];
        }
        $opencartOrder['payment_address_format'] = "";
        $opencartOrder['payment_method']         = $paymentMethod;
        $opencartOrder['payment_code']           = $paymentCode;

        $country   = $opencartDatabase->getCountryByIsoCode($deliveryAddress->getCountry());
        $zoneSplit = explode('-', $deliveryAddress->getState());
        $stateCode = !empty($zoneSplit[1])
            ? $zoneSplit[1]
            : $deliveryAddress->getState();
        if (!empty($stateCode)) {
            $zone = $opencartDatabase->getZone($country["country_id"], $stateCode);
        }

        $opencartOrder['shipping_firstname']  = $deliveryAddress->getFirstName();
        $opencartOrder['shipping_lastname']   = $deliveryAddress->getLastName();
        $opencartOrder['shipping_company']    = $deliveryAddress->getCompany();
        $opencartOrder['shipping_address_1']  = $deliveryAddress->getStreet1();
        $opencartOrder['shipping_address_2']  = $deliveryAddress->getStreet2();
        $opencartOrder['shipping_city']       = $deliveryAddress->getCity();
        $opencartOrder['shipping_postcode']   = $deliveryAddress->getZipcode();
        $opencartOrder['shipping_country']    = $country["name"];
        $opencartOrder['shipping_country_id'] = $country["country_id"];
        if (!empty($zone)) {
            $opencartOrder['shipping_zone']    = $zone["name"];
            $opencartOrder['shipping_zone_id'] = $zone["zone_id"];
        }
        $opencartOrder['shipping_address_format'] = "";
        $opencartOrder['shipping_method']         = $order->getShippingInfos()->getDisplayName();

        $opencartOrder['shipping_code'] = "shopgateshipping";
        if ($this->isValidShippingExtension($order->getShippingInfos()->getName())) {
            $opencartOrder['shipping_code'] = $order->getShippingInfos()->getName();
        }

        $currency = $opencartDatabase->getCurrencyByCode($this->_getConfiguration()->getCurrencyId());

        $opencartOrder['comment']        = $comment;
        $opencartOrder['affiliate_id']   = 0;
        $opencartOrder['language_id']    = $this->_getConfiguration()->getLanguageId();
        $opencartOrder['currency_id']    = $currency["currency_id"];
        $opencartOrder['currency_code']  = $order->getCurrency();
        $opencartOrder['currency']       = $order->getCurrency(); // currency code before 1.5
        $opencartOrder['currency_value'] = 1; // as exported currency is always default for store
        $opencartOrder['value']          = 1; // currency value before 1.5
        $opencartOrder['total']          = $order->getAmountComplete();
        $opencartOrder['date_added']     = date('Y-m-d H:i:s');
        $opencartOrder['date_modified']  = date('Y-m-d H:i:s');

        $orderId    = $opencartDatabase->insertOrder($opencartOrder);
        $orderItems = $order->getItems();
        $subTotal   = 0;
        $taxTotal   = array();

        foreach ($orderItems as $orderItem) {
            $totalItemPrice = $orderItem->getQuantity() * $orderItem->getUnitAmount();
            $unitTax        = $orderItem->getUnitAmountWithTax() - $orderItem->getUnitAmount();
            $totalItemTax   = $orderItem->getQuantity() * $unitTax;

            $subTotal += $totalItemPrice;

            // Must be a string since floats must not be used as array indices
            $taxPercent = (string)$orderItem->getTaxPercent();
            if (empty($taxTotal[$taxPercent])) {
                $taxTotal[$taxPercent] = 0;
            }
            $taxTotal[$taxPercent] += $totalItemTax;

            $opencartOrderItem               = array();
            $opencartOrderItem['order_id']   = $orderId;
            $opencartOrderItem['product_id'] = $orderItem->getItemNumber();
            $opencartOrderItem['name']       = htmlentities($orderItem->getName(), ENT_QUOTES);
            $opencartOrderItem['quantity']   = $orderItem->getQuantity();
            $opencartOrderItem['price']      = $orderItem->getUnitAmount();
            $opencartOrderItem['total']      = $totalItemPrice;
            $opencartOrderItem['tax']        = $unitTax;

            if ($orderItem->isItem()) {
                $product = $opencartDatabase->getProduct($orderItem->getItemNumber());

                $opencartOrderItem['model'] = $product['model'];
                if (isset($product['reward'])) {
                    $opencartOrderItem['reward'] = $product['reward'];
                }
            }

            $orderProductId = $opencartDatabase->insertOrderItem($opencartOrderItem);

            foreach ($orderItem->getOptions() as $option) {
                if (!$option->getValueNumber()) {
                    continue;
                }
                $optionValue = $this->_getOpencartDatabase()->getProductOptionValue($option->getValueNumber());
                $optionType  = $this->_getOpencartDatabase()->getProductOptionType($optionValue['option_id']);

                $orderOption                            = array();
                $orderOption['order_id']                = $orderId;
                $orderOption['order_product_id']        = $orderProductId;
                $orderOption['product_option_id']       = $optionValue['product_option_id'];
                $orderOption['product_option_value_id'] = $option->getValueNumber();
                $orderOption['name']                    = $option->getName();
                $orderOption['value']                   = $option->getValue();
                $orderOption['type']                    = $optionType;

                $this->_getOpencartDatabase()->insertOrderItemOption($orderOption);
            }

            foreach ($orderItem->getInputs() as $input) {
                if (!$input->getInputNumber()) {
                    continue;
                }
                $optionType = $this->_getOpencartDatabase()->getProductOptionType($input->getInputNumber());

                $orderOption                            = array();
                $orderOption['order_id']                = $orderId;
                $orderOption['order_product_id']        = $orderProductId;
                $orderOption['product_option_id']       = $input->getInputNumber();
                $orderOption['product_option_value_id'] = 0;
                $orderOption['name']                    = $input->getLabel();
                $orderOption['value']                   = $input->getUserInput();
                $orderOption['type']                    = $optionType;

                $this->_getOpencartDatabase()->insertOrderItemOption($orderOption);
            }

            $internalOrderInfo = $this->jsonDecode($orderItem->getInternalOrderInfo(), true);
            if (isset($internalOrderInfo['option_selection'])) {
                foreach ($internalOrderInfo['option_selection'] as $optionId => $optionData) {
                    $productOptionValueId = $optionData['product_option_value_id'];

                    if ($productOptionValueId == 0) {
                        continue;
                    }

                    $orderOption                            = array();
                    $orderOption['order_id']                = $orderId;
                    $orderOption['order_product_id']        = $orderProductId;
                    $orderOption['product_option_id']       = $optionId;
                    $orderOption['product_option_value_id'] = $productOptionValueId;
                    $orderOption['name']                    = $optionData['product_option_name'];
                    $orderOption['value']                   = $optionData['product_option_value_name'];
                    $orderOption['type']                    = $optionData['type'];

                    $this->_getOpencartDatabase()->insertOrderItemOption($orderOption);
                }
            }
        }

        $this->_processOpencartCoupons($order, $orderId, $opencartCustomer, $taxTotal);

        $shopgateOrderEntry                          = array();
        $shopgateOrderEntry['external_order_number'] = $orderId;
        $shopgateOrderEntry['shopgate_order_number'] = $order->getOrderNumber();
        $shopgateOrderEntry['shopgate_order_number'] = $order->getOrderNumber();
        $shopgateOrderEntry['sync_shipment']         = (int)$order->getIsShippingCompleted();
        $shopgateOrderEntry['sync_payment']          = (int)$order->getIsPaid();

        $shippingInfos = $order->getShippingInfos();
        if (!empty($shippingInfos) && $shippingInfos->getAmountNet() && $shippingInfos->getAmountGross()) {
            $taxTotal[(string)$order->getShippingTaxPercent()] +=
                $shippingInfos->getAmountGross() - $shippingInfos->getAmountNet();
        }

        $opencartDatabase->insertOrderTotals(
            $orderId,
            $order,
            $subTotal,
            $taxTotal,
            $this->_couponTotal,
            $this->_voucherTotal,
            $this->_couponCode,
            $this->_voucherCode
        );
        $opencartDatabase->insertShopgateOrderEntry($shopgateOrderEntry);

        if ($order->getIsShippingCompleted()) {
            $status = $this->_config->getOrderStatusShipped();
        } else {
            if ($order->getIsShippingBlocked()) {
                $status = $this->_config->getOrderStatusShippingBlocked();
            } else {
                $status = $this->_config->getOrderStatusShippingNotBlocked();
            }
        }

        $this->_loadModel('checkout/order');
        /** @var ModelCheckoutOrder $opencartOrderModel */
        $opencartOrderModel = $this->_getModel('model_checkout_order');
        ob_start();
        if (!defined('VERSION') || version_compare(VERSION, '2.0.0.0', '<')) {
            $opencartOrderModel->confirm($orderId, $status);
        } else {
            $opencartOrderModel->addOrderHistory($orderId, $status, '', true);
        }
        ob_end_clean();

        return $orderId;
    }

    /**
     * generates the payment info recursively and returns them as a string
     *
     * @param $paymentInfos
     * @param $topLevelKey
     *
     * @return string
     */
    protected function generatePaymentInfos($paymentInfos, $topLevelKey = '')
    {
        $commentDetailWhitelist = $this->getCommentDetailWhitelist();

        $comment = '';
        foreach ($paymentInfos as $key => $value) {
            if ($key == "paypal_ipn_data") {
                continue;
            }

            if (is_array($value)) {
                return $this->generatePaymentInfos($value,
                    !empty($topLevelKey)
                        ? $topLevelKey
                        : $key
                );
            } else {
                $whitelist = $commentDetailWhitelist;
                if (!empty($topLevelKey) && !empty($commentDetailWhitelist[$topLevelKey])
                    && is_array(
                        $commentDetailWhitelist[$topLevelKey]
                    )
                ) {
                    $whitelist = $commentDetailWhitelist[$topLevelKey];
                }
                if ($this->_config->getCommentDetailLevel() == ShopgateConfigOpencart::COMMENT_DETAIL_LEVEL_FULL
                    || !empty($whitelist[$key])
                ) {
                    $key     = str_replace('_', " ", $key);
                    $comment .= (!empty($topLevelKey)
                            ? (ucfirst($topLevelKey) . ": ")
                            : "")
                        .
                        ucfirst($key) . ': ' . $value . "\n";
                }
            }
        }

        return $comment;
    }

    /**
     * @param string $shippingCode
     *
     * @return bool
     */
    private function isValidShippingExtension($shippingCode)
    {
        if (!$this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')
            || $this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')
        ) {
            $shippingExtensions = $this->_config->getOpencartDatabase()->getExtensions('shipping');
        } else {
            $shippingExtensions = $this->_getModel('model_setting_extension')->getExtensions('shipping');
        }

        foreach ($shippingExtensions as $shippingExtension) {
            if ($shippingExtension['code'] == $shippingCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a whitelist for all allowed fields to be displayed in the "generatePaymentInfos" method. Returns an empty
     * array if all fields are to be displayed.
     *
     * @return array
     */
    private function getCommentDetailWhitelist()
    {
        if ($this->_config->getCommentDetailLevel() == ShopgateConfigOpencart::COMMENT_DETAIL_LEVEL_SIMPLE) {
            return array(
                // Shared
                'shopgate_payment_name'   => 1,
                'status'                  => 0,
                'transaction_id'          => 1,
                'credit_card'             => array(
                    'holder'        => 1,
                    'masked_number' => 1,
                    'type'          => 1,
                ),
                'bank_account'            => array(
                    'bank_account_holder' => 1,
                    'bank_account_number' => 1,
                    'bank_code'           => 1,
                    'iban'                => 1,
                    'bic'                 => 1,
                ),

                // PayPal
                'token'                   => 0,
                'payer_id'                => 1,
                'payer_email'             => 1,
                'receiver_email'          => 0,
                'receiver_id'             => 0,
                'invnum'                  => 1,
                'payer_status'            => 1,
                'payment_type'            => 0,
                'payment_status'          => 1,
                'payment_date'            => 1,
                'mc_shipping'             => 0,
                'mc_currency'             => 0,
                'mc_fee'                  => 0,
                'mc_gross'                => 0,
                'num_cart_items'          => 0,
                'first_name'              => 0,
                'last_name'               => 0,
                'address_name'            => 0,
                'address_street'          => 0,
                'address_city'            => 0,
                'address_state'           => 0,
                'address_zip'             => 0,
                'address_country'         => 0,
                'address_status'          => 0,
                'txn_type'                => 0,
                'residence_country'       => 0,
                'address_country_code'    => 0,
                'pending_reason'          => 0,
                'reason_code'             => 0,
                'business'                => 0,

                // PaypalWebsite payments Pro
                'paypal_transaction_id'   => 1,
                'paypal_payer_id'         => 1,
                'paypal_payer_email'      => 1,
                'paypal_receiver_id'      => 0,
                'paypal_receiver_email'   => 0,
                'paypal_txn_id'           => 0,
                'paypal_ipn_track_id'     => 0,
                'paypal_ipn_data'         => 0,

                // Amazon Payments MWS
                'payment_transaction_id'  => 1,
                'mws_order_id'            => 1,
                'mws_auth_id'             => 0,
                'mws_capture_id'          => 0,
                'mws_merchant_id'         => 0,
                'mws_payment_date'        => 1,
                'mws_refund_ids'          => array(
                    'refund_id'    => 0,
                    'status'       => 0,
                    'amount_gross' => 0,
                    'currency_id'  => 0,
                ),

                // Direct Debit
                'bank_account_holder'     => 1,
                'bank_account_number'     => 1,
                'bank_code'               => 1,
                'bank_name'               => 1,
                'iban'                    => 1,
                'bic'                     => 1,

                // Direct Debit Paymorrow
                'payment_reference'       => 1,
                'pm_order_transaction_id' => 1,
                'national_bank_name'      => 1,
                'national_bank_code'      => 1,
                'national_bank_acc_num'   => 1,

                // PAYONE
                'txid'                    => 1, // PAYONE transaction id

                // Shopgate CC
                'card_type'               => 1,

                // Prepayment (merchant)
                'purpose'                 => 1,

                // Shopgate Invoice
                // -see shared-

                // Shopgate Sofortüberweisung
                // -see shared-

                // CC (Authorize.net)
                'response_code'           => 0,
                'response_reason_code'    => 0,
                'response_reason_text'    => 1,
                'md5_hash'                => 0,
                'authorization_code'      => 0,
                'transaction_type'        => 1,

                // Merchant Invoice
                // -see shared-

                // COD (merchant)
                // -see shared-

                // PayU
                'trans'                   => array(
                    'id'                   => 1,
                    'pos_id'               => 0,
                    'session_id'           => 1,
                    'order_id'             => 0,
                    'amount'               => 0,
                    'status'               => 1,
                    'pay_type'             => 1,
                    'pay_gw_name'          => 0,
                    'desc'                 => 1,
                    'desc2'                => 0,
                    'create'               => 0,
                    'init'                 => 0,
                    'sent'                 => 1,
                    'recv'                 => 1,
                    'cancel'               => 0,
                    'auth_fraud'           => 0,
                    'ts'                   => 0,
                    'sig'                  => 0,
                    'add_client_name'      => 0,
                    'add_client_street'    => 0,
                    'add_client_city'      => 0,
                    'add_client_post_code' => 0,
                    'add_client_account'   => 0,
                    'add_client_address'   => 0,
                ),
            );
        }

        // ShopgateConfigOpencart::COMMENT_DETAIL_LEVEL_FULL
        return array();
    }

    /**
     * @param ShopgateOrder $order
     * @param int           $orderId
     * @param array         $opencartCustomer
     * @param array         $taxTotal
     *
     * @throws ShopgateLibraryException
     */
    protected function _processOpencartCoupons($order, $orderId, $opencartCustomer, &$taxTotal)
    {
        $coupons = $order->getExternalCoupons();
        if (!empty($coupons)) {
            if ($this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')) {
                $this->_loadModel('total/coupon');
                $this->_loadModel('total/voucher');
                /** @var ModelTotalVoucher $voucherModel */
                $voucherModel = $this->_getModel('model_total_voucher');
            } else {
                $this->_loadModel('checkout/coupon');
                if ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')) {
                    // Vouchers are available from 1.5.0.0
                    $this->_loadModel('checkout/voucher');
                    /** @var ModelCheckoutVoucher $voucherModel */
                    $voucherModel = $this->_getModel('model_checkout_voucher');
                }
            }

            foreach ($coupons as $coupon) {
                $internalCouponInfo = $this->jsonDecode($coupon->getInternalInfo(), true);
                if (
                    !empty($internalCouponInfo) && isset($internalCouponInfo['is_voucher'])
                    && empty($internalCouponInfo['is_voucher'])
                ) {
                    $taxTotal[(string)$order->getShippingTaxPercent()] -=
                        $coupon->getAmountGross() - $coupon->getAmountNet();
                }

                switch ($this->_getDiscountTypeByCode($coupon->getCode())) {
                    case self::DEFAULT_IDENTIFIER_COUPON:
                        $this->_couponCode  = $coupon->getCode();
                        $this->_couponTotal += $coupon->getAmountNet();
                        $couponInfo         = $this->_getOpencartDatabase()->getCoupon($this->_couponCode);

                        if ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')) {
                            $this->_getOpencartDatabase()->redeemCoupon(
                                $couponInfo['coupon_id'],
                                $orderId,
                                $opencartCustomer["customer_id"],
                                $coupon->getAmount()
                            );
                        }

                        break;
                    case self::DEFAULT_IDENTIFIER_VOUCHER:
                        // Voucher only available from 1.5.0.0
                        if ($this->_config->getOpencartDatabase()->assertMinimumVersion('1.5.0')) {
                            $this->_voucherCode  = $coupon->getCode();
                            $this->_voucherTotal += $coupon->getAmountGross();
                            $voucherInfo         = $this->_getOpencartDatabase()->getVoucher($this->_voucherCode);

                            if (abs($coupon->getAmountGross()) > $voucherInfo['amount']) {
                                throw new ShopgateLibraryException(
                                    ShopgateLibraryException::COUPON_NOT_VALID
                                );
                            }

                            if ($this->_config->getOpencartDatabase()->assertMinimumVersion('2.0.0')) {
                                $this->_getOpencartDatabase()->redeemVoucher(
                                    $voucherInfo['voucher_id'],
                                    $orderId,
                                    -abs($coupon->getAmountGross())
                                );
                            } else {
                                $voucherModel->redeem(
                                    $voucherInfo['voucher_id'],
                                    $orderId,
                                    -abs($coupon->getAmountGross())
                                );
                            }
                        }
                        break;
                }
            }
        }
    }
}
