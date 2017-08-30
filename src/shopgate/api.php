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

/*
 * OpenCart manipualtes the global $_POST variable (as well as $_GET, $_REQUEST, $_COOKIES etc.) which can lead to
 * problems in the plugin. So we save our own copy of $_POST BEFORE OpenCart modifies it.
 * (See class "Request" in /system/library/request.php)
 */
$postVariables = $_POST;

// put this into a breakpoint condition to disable authentication and destroy the session:
// define('SHOPGATE_DEBUG', 1) && $_COOKIE = array()

/**
 * Shopgate Plugin API
 */
//include shop system to get access to db registry and other models
ob_start();
//for bad inclusion paths in shop system index.php
chdir("..");
require_once('./index.php');
//clear output
ob_end_clean();

//include Shopgate plugin
require_once(DIR_APPLICATION . '../shopgate/includes.php');

$plugin   = new ShopgatePluginOpencart();
$response = $plugin->handleRequest($postVariables);
if (!$response) {
    $plugin->rollback();
}
