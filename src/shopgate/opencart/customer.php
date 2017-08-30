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
 * Import customer to opencart
 */
class ShopgateOpencartCustomer extends ShopgateOpencartAbstract
{
    /** @var string */
    protected $_user;

    /** @var string */
    protected $_password;

    /**
     * Facilitates customer & address creation
     *
     * @throws ShopgateLibraryException
     */
    public function generateData()
    {
        $customer = $this->_data;
        $database = $this->_getOpencartDatabase();
        $storeId  = $this->_getConfiguration()->getStoreId();
        $salt     = substr(md5(uniqid(rand(), true)), 0, 9);

        $openCartCustomer['store_id']  = $storeId;
        $openCartCustomer['firstname'] = $customer->getFirstName();
        $openCartCustomer['lastname']  = $customer->getLastName();
        $openCartCustomer['email']     = strtolower($this->_user);
        $openCartCustomer['telephone'] = $customer->getPhone();
        $openCartCustomer['fax']       = "";
        $openCartCustomer['password']  = sha1($salt . sha1($salt . sha1($this->_password)));
        $openCartCustomer['salt']      = $salt;

        $openCartCustomer['newsletter'] = 0;
        $openCartCustomer['address_id'] = 0;

        $openCartCustomer['customer_group_id'] = $database->getDefaultCustomerGroupId($storeId);
        $openCartCustomer['status']            = 1;
        $openCartCustomer['approved']          = 1;
        $openCartCustomer['date_added']        = date('Y-m-d H:i:s');

        try {
            $customerId = $database->insertCustomer($openCartCustomer);
        } catch (Excpetion $e) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_REGISTER_CUSTOMER_ERROR);
        }

        /** @var ShopgateAddress $address */
        foreach ($customer->getAddresses() as $address) {
            $country   = $database->getCountryByIsoCode($address->getCountry());
            $zoneSplit = explode('-', $address->getState());
            if (isset($zoneSplit[1])) {
                $zoneIdData = $database->getZone($country['country_id'], $zoneSplit[1]);
                $zoneId     = isset($zoneIdData['zone_id'])
                    ? $zoneIdData['zone_id']
                    : null;
            } else {
                $zoneId = null;
            }

            $opencartAddress['customer_id'] = $customerId;
            $opencartAddress['firstname']   = $address->getFirstName();
            $opencartAddress['lastname']    = $address->getLastName();
            $opencartAddress['company']     = $address->getCompany();
            $opencartAddress['tax_id']      = "";
            $opencartAddress['address_1']   = $address->getStreet1();
            $opencartAddress['address_2']   = $address->getStreet2();
            $opencartAddress['city']        = $address->getCity();
            $opencartAddress['postcode']    = $address->getZipcode();
            $opencartAddress['country_id']  = $country["country_id"];
            $opencartAddress['zone_id']     = $zoneId;

            $database->insertCustomerAddress($opencartAddress);
        }
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->_user = $user;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }
}
