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
// Heading
$_['heading_title'] = 'Shopgate';

// Text
$_['text_module']  = 'Moduły';
$_['text_success'] = 'Gotowe: Zmiany zapisane prawidłowo!';

// Entry
$_['entry_status']          = 'Stan:';
$_['entry_customer_number'] = 'Numer klienta<span class="help">Numer klienta nadany przez Shopgate</span>';
$_['entry_shop_number']     = 'Numer sklepu<span class="help">Numer sklepu nadany przez Shopgate</span>';
$_['entry_apikey']          = 'Klucz API:<span class="help"></span>';
$_['entry_cname']           = 'CName:<span class="help">Przekierowanie na własny adres URL</span>';
$_['entry_alias']           = 'Alias<span class="help">Alias sklepu używana w przekierowaniu do Shopgate. Nie powinna zawierac spacji i znaków specjalnych</span>';
$_['entry_store']           = 'Wybierz sklep:<span class="help"> Wybierz w jakiego sklepu będzie publikowana oferta na Shopgate</span>';
$_['entry_server']          = 'Tryb pracy:<span class="help">Jeśli chcesz przetestować usługę wybierz tryb testowy</span>';
$_['entry_customer_server_url']
                            = 'Specjalny Merchant-API serwer URL:<span class="help">Tylko jeżeli chcesz testować API przy użyciu własnego specjalnego URL</span>';
$_['entry_shop_is_active']  = 'Sklep dostępny online:<span class="help">Czy sklep jest włączony</span>';
$_['entry_encoding']        = 'Kodowanie:<span class="help">Kodowanie sklepu. <br />Zostaw domyślnie ustawione jeśli nie jesteś pewien kodowania swojego sklepu</span>';
$_['entry_storage']         = 'Kontrola stanu:<span class="help">Jeśli WŁĄCZONE to stan produktów jest taki ile pokazuje pole "Ilość" w produkcie</span>';
$_['entry_price']           = 'Kontrola ceny:<span class="help">Jeśli WŁĄCZONE produkty z wartością 0zł zostaną pominięte</span>';
$_['entry_default_store']   = '- Domyślny sklep -';

// Error
$_['error_permission'] = 'Uwaga: Brak uprawnień!';
$_['error_required']   = 'To pole jest wymagane!';

// Orders
$_['order_comment_processed_by_shopgate'] = "Zamówienie procedowane przez Shopgate\nNumer zamówienia Shopgate: %s\n";
$_['order_comment_test_order']            = '<p style=\"color:red\">To jest zamówienie testowe. Prosimy nie realizować wysyłki</p>';
$_['order_tax']                           = 'Podatek (%0.2f%%)';
$_['order_tax_payment']                   = 'Podatek od dodatkowej opłaty';
$_['order_amount_payment']                = 'Dodatkowa opłata za metodę płatności';
$_['order_coupon_code']                   = 'Kupon rabatowy';
$_['order_voucher_code']                  = 'Kupon';
