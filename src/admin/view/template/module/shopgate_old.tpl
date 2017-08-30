<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the   License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS  IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF  ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
* @copyright Shopgate Inc
* @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
*/
?>
<?php
if (!defined('VERSION')) {
    define('VERSION', '1.4.0.0');
}
if (version_compare(VERSION, '1.4.0.0', '<')) {
    $header = "";
    $footer = "";
    $page   = " page";
}
?>
<?php echo $header; ?>
<style>
    table.form td:first-child {
        text-align: right;
    }
</style>
<div id="content<?php echo empty($page) ? "" : $page ?>">
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
            <?php echo $breadcrumb['separator']; ?><a
            href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
        <div class="warning"><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class="box">
        <div class="heading">
            <h1><?php if (empty($page)): ?><img src="view/image/module.png" alt=""/><?php endif; ?>
                <img src="view/image/module/shopgate.png" alt=""/>
            </h1>

            <div class="buttons">
                <a onclick="$('#form').submit();" class="button">
                    <span class="button_left button_save"></span><span class="button_middle">
                            <?php echo $button_save; ?>
                        </span><span class="button_right"></span>
                </a>
                <a onclick="location='<?php echo $cancel; ?>';" class="button">
                    <span class="button_left button_cancel"></span><span class="button_middle">
                            <?php echo $button_cancel; ?>
                        </span><span class="button_right"></span>
                </a>
            </div>
        </div>
        <?php if (!empty($page)): ?>
            <div class="tabs">
                <a tab="#tab_general" class="selected"><?php echo $tab_general; ?></a>
            </div>
        <?php endif; ?>
        <div class="content<?php echo empty($page) ? "" : $page ?>">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <table class="form">
                    <tr>
                        <td width="5%"><label for="shopgate_store_id"><?php echo $entry_store; ?></label></td>
                        <td>
                            <select name="shopgate_store_id" id="shopgate_store_id">
                                <?php foreach ($stores as $store) { ?>
                                    <?php if ($store['store_id'] == $shopgate_store_id) { ?>
                                        <option value="<?php echo $store['store_id']; ?>"
                                                selected="selected"><?php echo $store['name']; ?></option>
                                    <?php } else { ?>

                                        <option
                                            value="<?php echo $store['store_id']; ?>"><?php echo $store['name']; ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="35%"><label for="shopgate_status"><?php echo $entry_status; ?></label></td>
                        <td>
                            <select name="shopgate_status" id="shopgate_status">
                                <?php if ($shopgate_status) { ?>
                                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                    <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                    <option value="1"><?php echo $text_enabled; ?></option>
                                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_customer_number"><span class="required">*</span> <?php echo $entry_customer_number; ?></label></td>
                        <td>
                            <input type="text" name="shopgate_customer_number" id="shopgate_customer_number" value="<?php echo $shopgate_customer_number; ?>"/>
                            <?php if ($error_shopgate_customer_number) { ?>
                                <span class="error"><?php echo $error_shopgate_customer_number; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_shop_number"><span class="required">*</span> <?php echo $entry_shop_number; ?></label></td>
                        <td>
                            <input type="text" name="shopgate_shop_number" id="shopgate_shop_number" value="<?php echo $shopgate_shop_number; ?>"/>
                            <?php if ($error_shopgate_shop_number) { ?>
                                <span class="error"><?php echo $error_shopgate_shop_number; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_apikey"><span class="required">*</span> <?php echo $entry_apikey; ?></label></td>
                        <td>
                            <input name="shopgate_apikey" id="shopgate_apikey" type="text" value="<?php echo $shopgate_apikey; ?>" size="30"/>
                            <?php if ($error_shopgate_apikey) { ?>
                            <span class="error"><?php echo $error_shopgate_apikey; ?></span>
                            <?php } ?>

                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_customer_cname"><?php echo $entry_cname; ?></label></td>
                        <td>
                            <input type="text" name="shopgate_customer_cname" id="shopgate_customer_cname" value="<?php echo $shopgate_customer_cname; ?>"
                                   placeholder="http://m.mydomain.com"/>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_alias"><span class="required">*</span><?php echo $entry_alias; ?></label></td>
                        <td>
                            <input type="text" name="shopgate_alias" id="shopgate_alias" value="<?php echo $shopgate_alias; ?>"
                                   placeholder="ALIAS.shopgatepg.com"
                                   size="30"/>
                            <?php if ($error_shopgate_alias) { ?>
                                <span class="error"><?php echo $error_shopgate_alias; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="serverMode"><?php echo $entry_server; ?></label></td>
                        <td>
                            <select id="serverMode" name="shopgate_server" onchange="changeMerchantAPIServer();">
                                <option
                                    <?php if ($shopgate_server == "live") {
                                        echo "selected=\"selected\"";
                                    } ?> value="live">Live
                                </option>
                                <option
                                    <?php if ($shopgate_server == "pg") {
                                        echo "selected=\"selected\"";
                                    } ?> value="pg">Test
                                </option>
                                <option
                                    <?php if ($shopgate_server == "custom") {
                                        echo "selected=\"selected\"";
                                    } ?> value="custom">Custom
                                </option>
                            </select></td>
                    </tr>
                    <tr id="customServerUrl" style="display: none">
                        <td><label for="shopgate_custom_server_url"><?php echo $entry_customer_server_url; ?></label></td>
                        <td>
                            <input type="text" name="shopgate_custom_server_url" id="shopgate_custom_server_url"
                                   value="<?php echo (isset($shopgate_custom_server_url) ? $shopgate_custom_server_url : ''); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_encoding"><?php echo $entry_encoding; ?></label></td>
                        <td>
                            <select name="shopgate_encoding" id="shopgate_encoding">
                                <option
                                    <?php if ($shopgate_encoding == "UTF-8") {
                                        echo "selected=\"selected\"";
                                    } ?> value="UTF-8">UTF-8
                                </option>
                                <option
                                    <?php if ($shopgate_encoding == "ISO-8859-1") {
                                        echo "selected=\"selected\"";
                                    } ?>
                                    value="ISO-8859-1">ISO-8859-1
                                </option>
                                <option
                                    <?php if ($shopgate_encoding == "UTF-16") {
                                        echo "selected=\"selected\"";
                                    } ?> value="UTF-16">UTF-16
                                </option>
                                <option
                                    <?php if ($shopgate_encoding == "windows-1252") {
                                        echo "selected=\"selected\"";
                                    } ?>
                                    value="windows-1252">windows-1252
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="35%"><label for="shopgate_comment_detail_level"><?php echo $entry_comment_detail_level; ?></label></td>
                        <td>
                            <select name="shopgate_comment_detail_level" id="shopgate_comment_detail_level">
                                <?php if (!empty($shopgate_comment_detail_level)) { ?>
                                <option value="0"><?php echo $text_simple; ?></option>
                                <option value="1" selected="selected"><?php echo $text_full; ?></option>
                                <?php } else { ?>
                                <option value="0" selected="selected"><?php echo $text_simple; ?></option>
                                <option value="1"><?php echo $text_full; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_order_status_shipping_blocked"><?php echo $entry_order_status_shipping_blocked; ?></label></td>
                        <td>
                            <select name="shopgate_order_status_shipping_blocked" id="shopgate_order_status_shipping_blocked">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                    <option value="<?php echo $id ?>" <?php if($shopgate_order_status_shipping_blocked == $id): ?>selected="selected"<?php endif ?>>
                                        <?php echo $status ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_order_status_shipping_not_blocked"><?php echo $entry_order_status_shipping_not_blocked; ?></label></td>
                        <td>
                            <select name="shopgate_order_status_shipping_not_blocked" id="shopgate_order_status_shipping_not_blocked">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                    <option value="<?php echo $id ?>" <?php if($shopgate_order_status_shipping_not_blocked == $id): ?>selected="selected"<?php endif ?>>
                                        <?php echo $status ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_order_status_shipped"><?php echo $entry_order_status_shipped; ?></label></td>
                        <td>
                            <select name="shopgate_order_status_shipped" id="shopgate_order_status_shipped">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                    <option value="<?php echo $id ?>" <?php if($shopgate_order_status_shipped == $id): ?>selected="selected"<?php endif ?>>
                                        <?php echo $status ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="shopgate_order_status_canceled"><?php echo $entry_order_status_canceled; ?></label></td>
                        <td>
                            <select name="shopgate_order_status_canceled" id="shopgate_order_status_canceled">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                    <option value="<?php echo $id ?>" <?php if($shopgate_order_status_canceled == $id): ?>selected="selected"<?php endif ?>>
                                        <?php echo $status ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>
    <script type="text/javascript">
        <!--
        function changeMerchantAPIServer() {
            if ($('#serverMode').val() == 'custom') {
                $('#customServerUrl').show();
            } else {
                $('#customServerUrl').hide();
            }
        }
        var sel = document.getElementById('shopgate_store_id');
        sel.onchange = function() {
            var url = window.location.href;
            if (url.indexOf('current_store=') > -1) {
                url = url.slice(0, url.indexOf('current_store') - 1);
            }
            if (url.indexOf('?') > -1){
                url += '&current_store=' + this.value;
            }else{
                url += '?current_store=' + this.value;
            }
            window.location.href = url;
        }
        //-->
    </script>
<?php echo $footer; ?>
