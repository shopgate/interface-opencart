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
<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-account" data-toggle="tooltip" title="<?php echo $button_save; ?>"
                        class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>"
                   class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><img src="view/image/module/shopgate.png" alt=""/></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-shopgate"
                      class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="shopgate_store_id"><?php echo $entry_store; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_store_id" id="shopgate_store_id" class="form-control">
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
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-status"><?php echo $entry_status; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_status" id="input-status" class="form-control">
                                <?php if ($shopgate_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label"
                               for="input-customer-number"><?php echo $entry_customer_number; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-customer-number" class="form-control"
                                   value="<?php echo $shopgate_customer_number; ?>"
                                   name="shopgate_customer_number"/>
                            <?php if ($error_shopgate_customer_number) { ?>
                            <div class="text-danger"><?php echo $error_shopgate_customer_number; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label"
                               for="input-shop-number"><?php echo $entry_shop_number; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-shop-number" class="form-control"
                                   value="<?php echo $shopgate_shop_number; ?>" name="shopgate_shop_number"/>
                            <?php if ($error_shopgate_shop_number) { ?>
                            <div class="text-danger"><?php echo $error_shopgate_shop_number; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label"
                               for="input-api-key"><?php echo $entry_apikey; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-api-key" class="form-control"
                                   value="<?php echo $shopgate_apikey; ?>" name="shopgate_apikey"/>
                            <?php if ($error_shopgate_apikey) { ?>
                            <div class="text-danger"><?php echo $error_shopgate_apikey; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-cname"><?php echo $entry_cname; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-cname" placeholder="http://m.mydomain.com"
                                   class="form-control" value="<?php echo $shopgate_customer_cname; ?>"
                                   name="shopgate_customer_cname"/>
                        </div>
                    </div>
                    <div class="form-group required">
                        <label class="col-sm-2 control-label"
                               for="input-alias"><?php echo $entry_alias; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-alias" placeholder="ALIAS.shopgatepg.com"
                                   class="form-control" value="<?php echo $shopgate_alias; ?>"
                                   name="shopgate_alias"/>
                            <?php if ($error_shopgate_alias) { ?>
                            <div class="text-danger"><?php echo $error_shopgate_alias; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-server"><?php echo $entry_server; ?></label>

                        <div class="col-sm-10">
                            <select id="input-server" class="form-control" name="shopgate_server"
                                    onchange="changeMerchantAPIServer();">
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
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="customServerUrl" style="display: none">
                        <label class="col-sm-2 control-label"
                               for="input-custom-server-url"><?php echo $entry_customer_server_url; ?></label>

                        <div class="col-sm-10">
                            <input type="text" id="input-custom-server-url" class="form-control"
                                   value="<?php echo $shopgate_custom_server_url; ?>"
                                   name="shopgate_custom_server_url"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-encoding"><?php echo $entry_encoding; ?></label>

                        <div class="col-sm-10">
                            <select id="input-encoding" class="form-control" name="shopgate_encoding">
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
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-comment_detail_level"><?php echo $entry_comment_detail_level; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_comment_detail_level" id="input-comment_detail_level"
                                    class="form-control">
                                <?php if (!empty($shopgate_comment_detail_level)) { ?>
                                <option value="0"><?php echo $text_simple; ?></option>
                                <option value="1" selected="selected"><?php echo $text_full; ?></option>
                                <?php } else { ?>
                                <option value="0" selected="selected"><?php echo $text_simple; ?></option>
                                <option value="1"><?php echo $text_full; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-order_status_shipping_blocked"><?php echo $entry_order_status_shipping_blocked; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_order_status_shipping_blocked"
                                    id="input-order_status_shipping_blocked" class="form-control">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                <option value="<?php echo $id ?>"
                                <?php if($shopgate_order_status_shipping_blocked == $id): ?>
                                selected="selected"<?php endif ?>>
                                <?php echo $status ?>
                                </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-order_status_shipping_not_blocked"><?php echo $entry_order_status_shipping_not_blocked; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_order_status_shipping_not_blocked"
                                    id="input-order_status_shipping_not_blocked" class="form-control">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                <option value="<?php echo $id ?>"
                                <?php if($shopgate_order_status_shipping_not_blocked == $id): ?>
                                selected="selected"<?php endif ?>>
                                <?php echo $status ?>
                                </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-order_status_shipped"><?php echo $entry_order_status_shipped; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_order_status_shipped" id="input-order_status_shipped"
                                    class="form-control">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                <option value="<?php echo $id ?>"
                                <?php if($shopgate_order_status_shipped == $id): ?>selected="selected"<?php endif ?>>
                                <?php echo $status ?>
                                </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"
                               for="input-order_status_canceled"><?php echo $entry_order_status_canceled; ?></label>

                        <div class="col-sm-10">
                            <select name="shopgate_order_status_canceled" id="input-order_status_canceled"
                                    class="form-control">
                                <?php foreach($order_statuses as $id =>  $status): ?>
                                <option value="<?php echo $id ?>"
                                <?php if($shopgate_order_status_canceled == $id): ?>selected="selected"<?php endif ?>>
                                <?php echo $status ?>
                                </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    <!--
    function changeMerchantAPIServer() {
        if ($('#input-server').val() == 'custom') {
            $('#customServerUrl').show();
        } else {
            $('#customServerUrl').hide();
        }
    }
    var sel = document.getElementById('shopgate_store_id');
    sel.onchange = function () {
        var url = window.location.href;
        if (url.indexOf('current_store=') > -1) {
            url = url.slice(0, url.indexOf('current_store') - 1);
        }
        if (url.indexOf('?') > -1) {
            url += '&current_store=' + this.value;
        } else {
            url += '?current_store=' + this.value;
        }
        window.location.href = url;
    }
    changeMerchantAPIServer();
    //-->
</script>
<?php echo $footer; ?>
