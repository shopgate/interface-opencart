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

include_once(DIR_APPLICATION . '../shopgate/includes.php');

class ShopgatePluginOpencartRedirect
{
    /**
     * @param array|null $getParams
     *
     * @return string|void
     */
    public function buildRedirectScript($getParams = null)
    {
        global $config;

        if ($config->get('shopgate_status')) {
            $path      = null;
            $productId = null;
            if ($getParams) {
                $route = $getParams['route'];
                if (array_key_exists('path', $getParams)) {
                    $path = $getParams['path'];
                }
                if (array_key_exists('product_id', $getParams)) {
                    $productId = $getParams['product_id'];
                }
            } else {
                $action = $GLOBALS['action'];
                if (($action instanceof Action) || ($action instanceof Router)) {
                    if (method_exists($action, 'getClass')) {
                        $route = $action->getClass();
                    } else {
                        $route = !empty($_GET['route'])
                            ? $_GET['route']
                            : "Controllercommonhome";
                    }
                    if (array_key_exists('path', $_GET)) {
                        $path = $_GET['path'];
                    }
                    if (array_key_exists('product_id', $_GET)) {
                        $productId = $_GET['product_id'];
                    }
                } else {
                    return "";
                }
            }

            $configuration = new ShopgateConfigOpencart();
            $configuration->initializeShopgateStoreConfig(null, $config->get('config_store_id'));
            $builder          = new ShopgateBuilder($configuration);
            $shopgateRedirect = $builder->buildRedirect();

            switch ($route) {
                case "product/product":
                case "Controllerproductproduct":
                    return $shopgateRedirect->buildScriptItem($productId);
                    break;
                case "product/category":
                case "Controllerproductcategory":
                    $parts      = explode('_', (string)$path);
                    $categoryId = (int)array_pop($parts);

                    return $shopgateRedirect->buildScriptCategory($categoryId);
                    break;
                case "common/home":
                case "Controllercommonhome":
                    return $shopgateRedirect->buildScriptShop();
                    break;
                default:
                    return $shopgateRedirect->buildScriptDefault();
                    break;
            }
        }
    }
}
