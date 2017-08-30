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
 * Settings export
 */
class ShopgateOpencartExportSettings extends ShopgateOpencartAbstract
{
    /**
     * @return array
     */
    public function generateData()
    {
        $database = $this->_getOpencartDatabase();
        $result   = array();

        $allowedAddressCountries = array();
        foreach ($database->getCountries() as $country) {
            $responseCountry['country'] = $country['iso_code_2'];
            $states                     = array();
            foreach ($database->getZone($country['country_id']) as $state) {
                $states[] = $state['code'];
            }
            $responseCountry['state']  = $states;
            $allowedAddressCountries[] = $responseCountry;
        }

        $customerGroups = array();
        $i              = 1;
        foreach ($database->getCustomerGroups($this->_getConfiguration()->getLanguageId()) as $customerGroup) {
            $responseGroup['id']         = $customerGroup['customer_group_id'];
            $responseGroup['name']       = $customerGroup['name'];
            $responseGroup['is_default'] = (int)($i == 1);
            $customerGroups[]            = $responseGroup;
            $i++;
        }

        $taxes = array();
        foreach ($database->getProductTaxClasses() as $tax) {
            $responseProductTax             = array();
            $responseProductTax['id']       = $tax['tax_class_id'];
            $responseProductTax['key']      = $tax['title'];
            $taxes['product_tax_classes'][] = $responseProductTax;
        }

        $taxes['customer_tax_classes'][] = array('id' => '0', 'key' => 'default');

        foreach ($database->getTaxRates() as $tax) {
            $responseTaxRate                 = array();
            $responseTaxRate['id']           = $tax['tax_rate_id'] . "_" . $tax['country_code'] . ($tax['zone_id']
                    ? "_" . $tax['zone_id']
                    : "");
            $responseTaxRate['key']          = $tax['tax_rate_id'] . "_" . $tax['country_code'] . ($tax['zone_id']
                    ? "_" . $tax['zone_id']
                    : "");
            $responseTaxRate['display_name'] = $tax['name'];
            $responseTaxRate['tax_percent']  = $tax['rate'];
            $responseTaxRate['country']      = $tax['country_code'];
            $responseTaxRate['state']        = $tax['state_code'];
            $responseTaxRate['zipcode_type'] = "all";

            $taxes['tax_rates'][] = $responseTaxRate;
        }

        foreach ($database->getTaxRules() as $tax) {
            $responseTaxRule                         = array();
            $responseTaxRule['id']                   = $tax['tax_rule_id'];
            $responseTaxRule['name']                 = $tax['based'];
            $responseTaxRule['priority']             = $tax['priority'];
            $responseTaxRule['product_tax_classes']  = array('id' => $tax['tax_class_id'], 'key' => $tax['title']);
            $responseTaxRule['customer_tax_classes'] = array('id' => '0', 'key' => 'default');
            $responseTaxRate                         = array();
            foreach ($database->getTaxRates($tax['tax_rate_id']) as $taxRate) {
                $id                = $taxRate['tax_rate_id'] . "_" . $taxRate['country_code'] . ($taxRate['zone_id']
                        ? "_" . $taxRate['zone_id']
                        : "");
                $key               = $taxRate['tax_rate_id'] . "_" . $taxRate['country_code'] . ($taxRate['zone_id']
                        ? "_" . $taxRate['zone_id']
                        : "");
                $responseTaxRate[] = array('id' => $id, 'key' => $key);
            }
            $responseTaxRule['tax_rates'] = $responseTaxRate;
            $taxes['tax_rules'][]         = $responseTaxRule;
        }

        $result['allowed_address_countries']  = $allowedAddressCountries;
        $result['allowed_shipping_countries'] = $allowedAddressCountries;
        $result['customer_groups']            = $customerGroups;
        $result['tax']                        = $taxes;

        return $result;
    }
}
