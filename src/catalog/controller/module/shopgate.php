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
 * Shopgate catalog redirect controller
 */
class ControllerModuleShopgate extends Controller
{
    public function index()
    {
        require_once(DIR_APPLICATION . '../shopgate/redirect.php');
        $shopgateRedirect = new ShopgatePluginOpencartRedirect();
        $this->id         = "shopgate";
        if (isset($this->request->get['_route_'])) {
            $parts = explode('/', $this->request->get['_route_']);

            foreach ($parts as $part) {
                $query = $this->db->query(
                    "SELECT * FROM " . DB_PREFIX . "url_alias WHERE keyword = '" . $this->db->escape($part) . "'"
                );

                if ($query->num_rows) {
                    $url = explode('=', $query->row['query']);

                    if ($url[0] == 'product_id') {
                        $this->request->get['product_id'] = $url[1];
                    }

                    if ($url[0] == 'category_id') {
                        if (!isset($this->request->get['path'])) {
                            $this->request->get['path'] = $url[1];
                        } else {
                            $this->request->get['path'] .= '_' . $url[1];
                        }
                    }
                }
            }

            if (isset($this->request->get['product_id'])) {
                $this->request->get['route'] = 'product/product';
            } elseif (isset($this->request->get['path'])) {
                $this->request->get['route'] = 'product/category';
            }
            $redirectScript = $shopgateRedirect->buildRedirectScript($this->request->get);
        } else {
            $redirectScript = $shopgateRedirect->buildRedirectScript();
        }
        if (!defined('VERSION') || version_compare(VERSION, '2.0.0.0', '<')) {
            $this->output = $redirectScript;
        } else {
            $data['redirect_script'] = $redirectScript;

            return $this->load->view('default/template/module/shopgate.tpl', $data);
        }
    }
}
