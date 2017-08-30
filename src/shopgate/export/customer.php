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
 * Customer export
 */
class ShopgateOpencartExportCustomer extends ShopgateOpencartAbstract
{
    /** @var  string */
    protected $_user;

    /** @var  string */
    protected $_password;

    /**
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     */
    public function generateData()
    {
        /** @var Customer $ocCustomer */
        $ocCustomer = $this->_config->getModel('customer');
        if (!$ocCustomer->login($this->_user, $this->_password)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD,
                'User: ' . $this->_user
            );
        }

        $openCartCustomer = $this->_getOpencartDatabase()
            ->getCustomer($ocCustomer->getId(), $this->_getConfiguration()->getLanguageId());

        if (empty($openCartCustomer['customer_id'])) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DATABASE_ERROR, 'Could not pull user data.'
            );
        }
        $customerToken = $openCartCustomer["customer_id"] . "-" . strtotime($openCartCustomer["date_added"]);

        $customer = new ShopgateCustomer();
        $customer->setCustomerId($openCartCustomer["customer_id"]);
        $customer->setCustomerNumber($openCartCustomer["customer_id"]);
        $customer->setFirstName($openCartCustomer["firstname"]);
        $customer->setLastName($openCartCustomer["lastname"]);
        $customer->setPhone($openCartCustomer["telephone"]);
        $customer->setMail($openCartCustomer["email"]);
        $customer->setNewsletterSubscription($openCartCustomer["newsletter"]);
        $customer->setCustomerToken($customerToken);

        $customerGroups = array();
        if (isset($openCartCustomer['customer_group_id'])) {
            $customerGroup = new ShopgateCustomerGroup();
            $customerGroup->setId($openCartCustomer['customer_group_id']);
            $customerGroup->setName($openCartCustomer['name']);
            $customerGroups[] = $customerGroup;
        }
        $customer->setCustomerGroups($customerGroups);

        $addresses           = array();
        $dbCustomerAddresses = $this->_getOpencartDatabase()->getCustomerAddresses($openCartCustomer["customer_id"]);

        foreach ($dbCustomerAddresses as $dbCustomerAddress) {
            $address = new ShopgateAddress();
            array_walk(
                $dbCustomerAddress,
                array($this, '_utfEightEncode')
            );
            $address->setId($dbCustomerAddress['address_id']);
            $address->setAddressType(ShopgateAddress::BOTH);
            $address->setFirstName($dbCustomerAddress["firstname"]);
            $address->setLastName($dbCustomerAddress["lastname"]);
            $address->setCompany($dbCustomerAddress["company"]);
            $address->setStreet1($dbCustomerAddress["address_1"]);
            $address->setStreet2($dbCustomerAddress["address_2"]);
            $address->setCity($dbCustomerAddress["city"]);
            $address->setZipcode($dbCustomerAddress["postcode"]);
            $address->setCountry($dbCustomerAddress["country"]);
            $address->setState($dbCustomerAddress["country"] . "-" . $dbCustomerAddress["zone"]);
            $address->setMail($openCartCustomer["email"]);

            $addresses[] = $address;
        }

        $customer->setAddresses($addresses);

        return $customer;
    }

    /**
     * @param string $entry
     *
     * @return string
     */
    protected function _utfEightEncode($entry)
    {
        return utf8_encode($entry);
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
