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

include_once(DIR_APPLICATION . '..' . DIRECTORY_SEPARATOR . 'shopgate' . DIRECTORY_SEPARATOR . 'includes.php');

/**
 * Shopgate module admin controller
 */
class ControllerModuleShopgate extends Controller
{
    private $error = array();

    public function index()
    {
        $this->_installShopgateTable();
        $shopgateOpenCartDatabase = new ShopgateOpencartDatabase();
        $this->language->load('module/shopgate');
        $this->setDocumentTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (defined('VERSION') && version_compare(VERSION, '1.5.0.0', '>=')) {
                $this->model_setting_setting->editSetting(
                    'shopgate',
                    $this->request->post,
                    $this->request->post['shopgate_store_id']
                );
            } else {
                $this->model_setting_setting->editSetting(
                    'shopgate_' . $this->request->post['shopgate_store_id'],
                    $this->request->post
                );
            }

            $this->session->data['success'] = $this->language->get('text_success');
            if (!defined('VERSION') || version_compare(VERSION, '2.0.0.0', '<')) {
                $this->redirect($this->buildLink('extension/module'));
            } else {
                $this->response->redirect($this->buildLink('extension/module'));
            }
        }

        $data['heading_title']              = $this->language->get('heading_title');
        $data['text_enabled']               = $this->language->get('text_enabled');
        $data['text_disabled']              = $this->language->get('text_disabled');
        $data['text_simple']                = $this->language->get('text_simple');
        $data['text_full']                  = $this->language->get('text_full');
        $data['entry_status']               = $this->language->get('entry_status');
        $data['text_edit']                  = $this->language->get('text_edit');
        $data['entry_customer_number']      = $this->language->get('entry_customer_number');
        $data['entry_shop_number']          = $this->language->get('entry_shop_number');
        $data['entry_apikey']               = $this->language->get('entry_apikey');
        $data['entry_alias']                = $this->language->get('entry_alias');
        $data['entry_cname']                = $this->language->get('entry_cname');
        $data['entry_server']               = $this->language->get('entry_server');
        $data['entry_customer_server_url']  = $this->language->get('entry_customer_server_url');
        $data['entry_shop_is_active']       = $this->language->get('entry_shop_is_active');
        $data['entry_encoding']             = $this->language->get('entry_encoding');
        $data['entry_store']                = $this->language->get('entry_store');
        $data['button_save']                = $this->language->get('button_save');
        $data['button_cancel']              = $this->language->get('button_cancel');
        $data['tab_general']                = $this->language->get('tab_general');
        $data['entry_comment_detail_level'] = $this->language->get('entry_comment_detail_level');

        $data['entry_order_status_shipping_blocked']     = $this->language->get('entry_order_status_shipping_blocked');
        $data['entry_order_status_shipping_not_blocked'] =
            $this->language->get('entry_order_status_shipping_not_blocked');
        $data['entry_order_status_shipped']              = $this->language->get('entry_order_status_shipped');
        $data['entry_order_status_canceled']             = $this->language->get('entry_order_status_canceled');

        $fieldsToCheck = array(
            "warning",
            "shopgate_customer_number",
            "shopgate_shop_number",
            "shopgate_apikey",
            "shopgate_alias",
        );

        foreach ($fieldsToCheck as $field) {
            if (isset($this->error[$field])) {
                $data['error_' . $field] = $this->error[$field];
            } else {
                $data['error_' . $field] = '';
            }
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->buildLink('common/home'),
            'separator' => false,
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->buildLink('extension/module'),
            'separator' => ' :: ',
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->buildLink('module/shopgate'),
            'separator' => ' :: ',
        );

        $data['action'] = $this->buildLink('module/shopgate');
        $data['cancel'] = $this->buildLink('extension/module');

        $currentSelectedStoreId = null;
        $data['stores']         = array();

        if ($shopgateOpenCartDatabase->assertMinimumVersion('1.4.1')) {
            // multi store is available since 1.4.1
            $this->load->model('setting/store');
            $results = $this->model_setting_store->getStores();

            foreach ($results as $result) {
                if (is_null($currentSelectedStoreId)) {
                    $currentSelectedStoreId = $result['store_id'];
                }
                $data['stores'][$result['store_id']] = array(
                    'name'     => $result['name'],
                    'store_id' => $result['store_id'],
                );
            }
        }

        if (!$shopgateOpenCartDatabase->assertMinimumVersion('1.4.1')
            || $shopgateOpenCartDatabase->assertMinimumVersion('1.4.5')
        ) {
            // strange behavior between 1.4.1 and 1.4.5: there is also an entry for the default store in the table.
            // Only between 1.4.1 and 1.4.4 we need to hide the default store entry
            $currentSelectedStoreId = 0;
            $data['stores'][0]      = array(
                'name'     => $this->language->get('entry_default_store'),
                'store_id' => 0,
            );
        }

        $languageId             = $this->config->get('config_language_id');
        $orderStatusTable       = DB_PREFIX . 'order_status';
        $whereClause            = !empty($languageId)
            ? "WHERE language_id = $languageId"
            : '';
        $orderStatuses          =
            $this->db->query("SELECT * FROM ${orderStatusTable} $whereClause ORDER BY order_status_id");
        $data['order_statuses'] = array();
        foreach ($orderStatuses->rows as $row) {
            $data['order_statuses'][$row['order_status_id']] = $row['name'];
        }

        $configStringKeys = array(
            "shopgate_customer_cname",
            "shopgate_alias",
            "shopgate_apikey",
            "shopgate_shop_number",
            "shopgate_customer_number",
            "shopgate_custom_server_url",
        );

        if (isset($_REQUEST['current_store'])) {
            $currentSelectedStoreId = $_REQUEST['current_store'];
        }

        if (defined('VERSION') && version_compare(VERSION, '1.5.0.0', '>=')) {
            $settings = $this->model_setting_setting->getSetting('shopgate', $currentSelectedStoreId);
        } else {
            $settings = $this->model_setting_setting->getSetting('shopgate_' . $currentSelectedStoreId);
        }

        foreach ($configStringKeys as $key) {
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } elseif (!empty($settings[$key])) {
                $data[$key] = $settings[$key];
            } else {
                $data[$key] = '';
            }
        }

        $configKeys = array(
            'shopgate_store_id'                          => null,
            'shopgate_status'                            => null,
            'shopgate_encoding'                          => null,
            'shopgate_shop_is_active'                    => null,
            'shopgate_server'                            => null,
            'shopgate_comment_detail_level'              => 0,
            'shopgate_order_status_shipping_blocked'     => 1,
            'shopgate_order_status_shipping_not_blocked' => 2,
            'shopgate_order_status_shipped'              => 3,
            'shopgate_order_status_canceled'             => 7,
        );

        foreach ($configKeys as $key => $defaultValue) {
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = empty($settings[$key])
                    ? $defaultValue
                    : $settings[$key];
            }
        }

        $data['shopgate_store_id'] = $currentSelectedStoreId;

        if (!defined('VERSION') || version_compare(VERSION, '2.0.0.0', '<')) {
            $this->template = 'module/shopgate_old.tpl';
            $this->layout   = 'common/layout';
            $this->id       = 'content';
            $this->data     = array_merge($data, $this->data);
            $this->children = array(
                'common/header',
                'common/footer',
            );
            $this->render();
            $this->response->setOutput($this->output);
        } else {
            $data['header']      = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer']      = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('module/shopgate.tpl', $data));
        }
    }

    /**
     * @param string $route
     *
     * @return string
     */
    private function buildLink($route)
    {

        // $this->url is available until version 1.4.0 (included)
        if (!empty($this->url) && method_exists($this->url, "link")) {
            return $this->url->link($route, 'token=' . $this->session->data['token'], 'SSL');
        } else {
            if (!empty($this->url) && method_exists($this->url, "https")) {
                return $this->url->https($route);
            } else {
                return HTTPS_SERVER . 'index.php?route=' . $route . (isset($this->session->data['token'])
                        ? '&token='
                        . $this->session->data['token']
                        : '');
            }
        }
    }

    /**
     * @param string $title
     */
    private function setDocumentTitle($title)
    {
        if (method_exists($this->document, 'setTitle')) {
            // In newer versions of OpenCart title has to be set via setter...
            $this->document->setTitle($title);
        } else {
            // ...while in older Versions there is no setter.
            $this->document->title = $title;
        }
    }

    /**
     * @return bool
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/shopgate')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $fieldsToCheck = array(
            "shopgate_customer_number",
            "shopgate_shop_number",
            "shopgate_apikey",
            "shopgate_alias",
        );

        foreach ($fieldsToCheck as $field) {
            if (!$this->request->post[$field]) {
                $this->error[$field] = $this->language->get('error_required');
            }
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    protected function _installShopgateTable()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "shopgate_orders` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `shopgate_order_number` varchar(32) NOT NULL,
                  `external_order_number` varchar(32) DEFAULT NULL,
                  `sync_shipment` smallint(6) DEFAULT '0',
                  `sync_cancellation` smallint(6) DEFAULT '0',
                  `sync_payment` smallint(6) DEFAULT '0',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `shopgate_order_number` (`shopgate_order_number`)
                  );"
        );
    }

    public function install()
    {
        require_once(DIR_APPLICATION . '../shopgate/includes.php');
        $installHelper = new ShopgateOpencartExportInstall();
        $installHelper->generateData($this->registry->get('config'));
        $this->_installShopgateTable();
    }
}
