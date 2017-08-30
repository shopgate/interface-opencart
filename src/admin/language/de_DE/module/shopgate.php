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
$_['text_module']  = 'Module';
$_['text_success'] = 'Erfolg: Sie haben die Shopgate-Einstellungen geändert!';
$_['text_simple']  = 'Einfach';
$_['text_full']    = 'Vollständig';
$_['text_edit']    = 'Bearbeiten';

// Entry
$_['entry_status']               = 'Status:';
$_['entry_customer_number']      = 'Kundennummer<span class="help">Ihre Kundennummer bei Shopgate.</span>';
$_['entry_shop_number']          = 'Shopnummer<span class="help">Die Nummer Ihres Shops bei Shopgate.</span>';
$_['entry_apikey']               = 'API-Key:<span class="help"></span>';
$_['entry_cname']                = 'CName:<span class="help">Falls Sie eine eigene URL für Ihre mobile Webseite haben, hinterlegen Sie diese hier (z.B. http://m.meinshop.de.)</span>';
$_['entry_alias']                = 'Alias<span class="help">Der Alias Ihres Shops bei Shopgate.</span>';
$_['entry_store']                = 'Store:<span class="help">Wählen Sie den OpenCart-Store, den Sie mit Shopgate verbinden möchten.</span>';
$_['entry_server']               = 'Merchant API-Server:<span class="help"></span>';
$_['entry_customer_server_url']  = 'Benutzerdefinierte URL zur Merchant-API:<span class="help"></span>';
$_['entry_shop_is_active']       = 'Shop ist aktiv:<span class="help">Ist der Shop bereits aktiviert?</span>';
$_['entry_encoding']             = 'Encoding:<span class="help">Das Encoding in Ihrem Store.<br />I.d.R. ist UTF-8 die korrekte Wahl.</span>';
$_['entry_storage']              = 'Lagersbestand überprüfen:<span class="help">Wenn ein Produkt nicht auf Lager ist, soll es nicht exportiert werden.</span>';
$_['entry_price']                = 'Preis überprüfen:<span class="help">Wenn ein Produkt einen Preis von 0 hat, soll es nicht exportiert werden.</span>';
$_['entry_comment_detail_level'] = 'Kommentar-Detailstufe:<span class="help">Wählen Sie, wie viele Informationen in den Kommentaren beim Bestellungsimport eingefügt werden sollen.</span>';
$_['entry_default_store']        = '- Default Store -';

$_['entry_order_status_shipping_blocked']     = 'Bestellungsstatus "Versand blockiert"';
$_['entry_order_status_shipping_not_blocked'] = 'Bestellungsstatus "Versand nicht blockiert"';
$_['entry_order_status_shipped']              = 'Bestellungsstatus "Versendet"';
$_['entry_order_status_canceled']             = 'Bestellungsstatus "Storniert"';

// Error
$_['error_permission'] = 'Warnung: Sie haben keine Berechtigung, Änderungen am Shopgate-Modul vorzunehmen.';
$_['error_required']   = 'Pflichtfeld!';

// Orders
$_['order_comment_processed_by_shopgate'] = "Bestellung verarbeitet durch Shopgate\nShopgate Bestellnummer: %s\n";
$_['order_comment_test_order']            = '<p style=\"color:red\">Dies ist eine Testbestellung. Bitte nicht versenden!</p>';
$_['order_tax']                           = 'Steuer (%0.2f%%)';
$_['order_tax_payment']                   = 'Steuer für Zahlungsartkosten';
$_['order_amount_payment']                = 'Zahlungsartkosten';
$_['order_coupon_code']                   = 'Coupon';
$_['order_voucher_code']                  = 'Geschenkgutschein';
