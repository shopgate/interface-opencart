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
 * Class ShopgateOpencartCartShipping
 */
class ShopgateOpencartCartShipping
{
    const SHIPPING_METHOD_TITLE        = 'title';
    const SHIPPING_METHOD_COST         = 'cost';
    const SHIPPING_METHOD_TAX_CLASS_ID = 'tax_class_id';
    const SHIPPING_METHOD_ID           = 'id';
    const SHIPPING_METHOD_CODE         = 'code';
    const CARRIER_ERROR      = 'error';
    const CARRIER_QUOTE      = 'quote';
    const CARRIER_TITLE      = 'title';
    const CARRIER_SORT_ORDER = 'sort_order';
    const COUNTRY_ID = 'country_id';
    const ZONE_ID    = 'zone_id';

    /** @var ShopgateConfigOpencart */
    protected $shopgateConfigOpencart;

    /** @var ShopgateOpencartDatabase */
    protected $shopgateOpencartDatabase;

    /** @var Session */
    protected $opencartSessionModel;

    /** @var Config */
    protected $opencartConfigModel;

    /** @var ModelLocalisationCountry */
    protected $opencartLocalisationCountryModel;

    /** @var Cart */
    protected $opencartCartModel;

    /** @var Loader */
    protected $opencartLoader;

    /**
     * @param ShopgateConfigOpencart   $shopgateConfigOpencart
     * @param ShopgateOpencartDatabase $shopgateOpencartDatabase
     * @param Loader                   $loaderModel
     * @param Config                   $opencartConfigModel
     * @param ModelLocalisationCountry $localisationCountryModel
     * @param Session                  $sessionModel
     * @param Cart                     $cartModel
     */
    public function __construct(
        ShopgateConfigOpencart $shopgateConfigOpencart,
        ShopgateOpencartDatabase $shopgateOpencartDatabase,
        Loader $loaderModel,
        Config $opencartConfigModel,
        ModelLocalisationCountry $localisationCountryModel,
        Session $sessionModel,
        Cart $cartModel
    ) {
        $this->shopgateConfigOpencart           = $shopgateConfigOpencart;
        $this->shopgateOpencartDatabase         = $shopgateOpencartDatabase;
        $this->opencartConfigModel              = $opencartConfigModel;
        $this->opencartLocalisationCountryModel = $localisationCountryModel;
        $this->opencartLoader                   = $loaderModel;
        $this->opencartSessionModel             = $sessionModel;
        $this->opencartCartModel                = $cartModel;
    }

    /**
     * @param ShopgateCart $shopgateCart
     *
     * @return array
     */
    public function returnShippingMethods(ShopgateCart $shopgateCart)
    {
        $deliveryAddress = $shopgateCart->getDeliveryAddress();
        if (!$deliveryAddress) {
            return array();
        }

        if (!$this->isShippingRequired($this->opencartCartModel->getProducts())) {
            return array($this->createVirtualShippingMethod('No shipping necessary', 'no_shipping'));
        }

        $shippingMethods         = array();
        $opencartDeliveryAddress = $this->buildAddress($deliveryAddress);
        foreach ($this->getShippingQuotes($opencartDeliveryAddress) as $carrier) {
            if ($carrier[self::CARRIER_ERROR]) {
                continue;
            }
            foreach ($carrier[self::CARRIER_QUOTE] as $method) {
                $shippingMethods[] = $this->createShopgateShippingMethod($method, $carrier, $opencartDeliveryAddress);
            }
        }

        return $shippingMethods;
    }

    /**
     * @param array $products
     *
     * @return bool
     */
    protected function isShippingRequired(array $products)
    {
        foreach ($products as $product) {
            if ($product['shipping']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $shippingMethod requires fields: title, cost, tax_class_id, (code or id). optional field: sort_order
     * @param array $carrier        accessed fields: sort_order and title
     * @param array $opencartDeliveryAddress
     *
     * @return ShopgateShippingMethod
     */
    protected function createShopgateShippingMethod(
        array $shippingMethod,
        array $carrier,
        array $opencartDeliveryAddress
    ) {
        $shopgateShippingMethod = new ShopgateShippingMethod();

        $shopgateShippingMethod->setTitle($shippingMethod[self::SHIPPING_METHOD_TITLE]);
        $shopgateShippingMethod->setShippingGroup($carrier[self::CARRIER_TITLE]);
        $shopgateShippingMethod->setSortOrder(
            (isset($carrier[self::CARRIER_SORT_ORDER])
                ? $carrier[self::CARRIER_SORT_ORDER]
                : 0)
        );
        $shopgateShippingMethod->setAmount($shippingMethod[self::SHIPPING_METHOD_COST]);
        $shopgateShippingMethod->setTaxClass($shippingMethod[self::SHIPPING_METHOD_TAX_CLASS_ID]);

        $shopgateShippingMethod->setAmountWithTax(
            $this->calculateGrossPrice(
                $shippingMethod[self::SHIPPING_METHOD_COST],
                $shippingMethod[self::SHIPPING_METHOD_TAX_CLASS_ID],
                $opencartDeliveryAddress[self::COUNTRY_ID],
                $opencartDeliveryAddress[self::ZONE_ID]
            )
        );

        // in older versions it's "id" instead of "code"
        $shopgateShippingMethod->setId(
            isset($shippingMethod[self::SHIPPING_METHOD_CODE])
                ? $shippingMethod[self::SHIPPING_METHOD_CODE]
                : $shippingMethod[self::SHIPPING_METHOD_ID]
        );

        return $shopgateShippingMethod;
    }

    /**
     * returns all available extensions of type 'shipping' in the store
     *
     * @return array
     */
    protected function getShippingExtensions()
    {
        if (
            !$this->shopgateOpencartDatabase->assertMinimumVersion('1.5.0')
            || $this->shopgateOpencartDatabase->assertMinimumVersion('2.0.0')
        ) {
            $shippingExtensions = $this->shopgateOpencartDatabase->getExtensions('shipping');
        } else {
            $shippingExtensions =
                $this->shopgateConfigOpencart->getModel('model_setting_extension')->getExtensions('shipping');
        }

        return $shippingExtensions;
    }

    /**
     * @param array $opencartDeliveryAddress
     *
     * @return array
     */
    protected function getShippingQuotes(array $opencartDeliveryAddress)
    {
        $shippingQuotes = array();
        foreach ($this->getShippingExtensions() as $shippingExtension) {
            $extensionCode = $this->getExtensionCode($shippingExtension);
            if (!$this->isExtensionActive($extensionCode)) {
                continue;
            }

            $this->opencartLoader->model('shipping/' . $extensionCode);

            $quote = $this->getShippingQuote($extensionCode, $opencartDeliveryAddress);

            if (!$quote) {
                continue;
            }

            $shippingQuotes[$extensionCode] = array(
                self::CARRIER_TITLE      => $quote[self::CARRIER_TITLE],
                self::CARRIER_QUOTE      => $quote[self::CARRIER_QUOTE],
                self::CARRIER_SORT_ORDER => $quote[self::CARRIER_SORT_ORDER],
                self::CARRIER_ERROR      => $quote[self::CARRIER_ERROR],
            );
        }

        return $shippingQuotes;
    }

    /**
     * @param string $extensionCode
     *
     * @return bool
     */
    protected function isExtensionActive($extensionCode)
    {
        return (bool)$this->opencartConfigModel->get($extensionCode . '_status');
    }

    /**
     * @param array $shippingExtension
     *
     * @return string
     */
    protected function getExtensionCode(array $shippingExtension)
    {
        return (isset($shippingExtension['code'])
            ? $shippingExtension['code']
            : $shippingExtension['key']);
    }

    /**
     * Calculates the price with tax for the passed $price
     * Attention: if the OpenCart Version is lower than 1.3.4 we just return the net price.
     *
     * For different OpenCart version we need to inject different 'hacks'. Here is kind of a changelog (not complete)
     *
     * Starting from OpenCart 1.3: The tax model don't have parameter in the constructor
     *                             and reads $shipping_address_id and $customer_id from session->data
     *                             in method __construct to directly calculate the tax rates (unchangeable later)
     *       since OpenCart 1.3.2: Tax class was renamed to HelperTax and moved to system/helper
     *       since OpenCart 1.3.4: Moved Tax class helper back to system/library constructor still doesn't take any
     *                             parameter but reads $countryId and $zoneId from session->data in
     *                             method __construct to directly to calculate the tax rates (unchangeable later)
     *          in OpenCart 1.4.1: The tax model needs the registry passed as parameter.
     *     since OpenCart 1.5.1.3: There are a lot of ways for class Tax to read $countryId and $zoneId but we will
     *     just
     *                             call method setShippingAddress() since the workflow changed
     *     since OpenCart 2.0.2.0: Workflow changed - the tax rates are calculated in the __constructor (not
     *     unchangeable).
     *
     *
     * @param float $price
     * @param int   $taxClassId
     * @param int   $countryId
     * @param int   $zoneId
     *
     * @return float price with calculated tax
     */
    protected function calculateGrossPrice($price, $taxClassId, $countryId, $zoneId)
    {
        if (!$this->shopgateOpencartDatabase->assertMinimumVersion('1.3.4')) {
            return $price;
        }

        if (method_exists($this->opencartSessionModel, 'setShippingAddress')) {
            $this->opencartSessionModel->setShippingAddress($countryId, $zoneId);
        } else {
            $this->opencartSessionModel->data[self::COUNTRY_ID] = $countryId;
            $this->opencartSessionModel->data[self::ZONE_ID]    = $zoneId;
            // starting from OpenCart 2.0.0.0 the format changed to:
            $this->opencartSessionModel->data['shipping_address'][self::COUNTRY_ID] = $countryId;
            $this->opencartSessionModel->data['shipping_address'][self::ZONE_ID]    = $zoneId;
        }

        if ($this->shopgateOpencartDatabase->assertMinimumVersion('1.4.1')) {
            $taxModel = new Tax($GLOBALS['registry']);
        } else {
            $taxModel = new Tax();
        }

        if (method_exists($taxModel, 'setShippingAddress')) {
            $taxModel->setShippingAddress($countryId, $zoneId);
        }

        return round($taxModel->calculate($price, $taxClassId), 2);
    }

    /**
     * @param ShopgateAddress $shopgateAddress
     *
     * @return array
     */
    public function buildAddress(ShopgateAddress $shopgateAddress)
    {
        $country     = $this->shopgateOpencartDatabase->getCountryByIsoCode($shopgateAddress->getCountry());
        $countryInfo = $this->opencartLocalisationCountryModel->getCountry($country[self::COUNTRY_ID]);
        $zoneSplit   = explode('-', $shopgateAddress->getState());
        $zone        = $this->shopgateOpencartDatabase->getZone(
            $country[self::COUNTRY_ID],
            !empty($zoneSplit[1])
                ? $zoneSplit[1]
                : null
        );

        if (is_array($zone) && empty($zoneSplit[1])) {
            // in case no state ($shopgateAddress->getState()) was passed we need to fetch the first result
            $zone = $zone[0];
        }

        if (is_array($zone) && !is_array(reset($zone))) {
            $zoneInfo = $this->getZoneInfo($zone[self::ZONE_ID]);
        }

        return array(
            'firstname'      => $shopgateAddress->getFirstName(),
            'lastname'       => $shopgateAddress->getLastName(),
            'company'        => $shopgateAddress->getCompany(),
            'address_1'      => $shopgateAddress->getStreet1(),
            'address_2'      => $shopgateAddress->getStreet2(),
            'postcode'       => $shopgateAddress->getZipcode(),
            'city'           => $shopgateAddress->getCity(),
            self::ZONE_ID    => $zone[self::ZONE_ID],
            'zone'           => isset($zoneInfo['name'])
                ? $zoneInfo['name']
                : "",
            'zone_code'      => isset($zoneInfo['code'])
                ? $zoneInfo['code']
                : "",
            self::COUNTRY_ID => $country[self::COUNTRY_ID],
            'country'        => $countryInfo
                ? $countryInfo['name']
                : "",
            'iso_code_2'     => $countryInfo
                ? $countryInfo['iso_code_2']
                : "",
            'iso_code_3'     => $countryInfo
                ? $countryInfo['iso_code_3']
                : "",
            'address_format' => $countryInfo
                ? $countryInfo['address_format']
                : "",
        );
    }

    /**
     * @param int $zoneId
     *
     * @return array
     */
    protected function getZoneInfo($zoneId)
    {
        if ($this->shopgateOpencartDatabase->assertMinimumVersion('1.3.3')) {
            $zoneInfo =
                $this->shopgateConfigOpencart->getModel('model_localisation_zone')->getZone($zoneId);
        } else {
            $zoneInfo = $this->shopgateOpencartDatabase->getZoneById($zoneId);
        }

        return $zoneInfo;
    }

    /**
     * @param string $extensionCode
     * @param array  $opencartDeliveryAddress
     *
     * @return array
     */
    protected function getShippingQuote($extensionCode, array $opencartDeliveryAddress)
    {
        if (!$this->shopgateOpencartDatabase->assertMinimumVersion('1.4.0')) {
            $quote = $this->shopgateConfigOpencart->getModel('model_shipping_' . $extensionCode)->getQuote(
                $opencartDeliveryAddress[self::COUNTRY_ID],
                $opencartDeliveryAddress[self::ZONE_ID],
                $opencartDeliveryAddress['postcode']
            );
        } else {
            $quote = $this->shopgateConfigOpencart->getModel('model_shipping_' . $extensionCode)->getQuote(
                $opencartDeliveryAddress
            );
        }

        return $quote;
    }

    /**
     * @param string $shippingMethodTitle
     * @param string $shippingMethodGroup
     *
     * @return ShopgateShippingMethod
     */
    protected function createVirtualShippingMethod($shippingMethodTitle, $shippingMethodGroup)
    {
        $shopgateMethod = new ShopgateShippingMethod();
        $shopgateMethod->setId(0);
        $shopgateMethod->setTitle($shippingMethodTitle);
        $shopgateMethod->setShippingGroup($shippingMethodGroup);
        $shopgateMethod->setSortOrder(0);
        $shopgateMethod->setAmount(0);
        $shopgateMethod->setAmountWithTax(0);

        return $shopgateMethod;
    }
}
