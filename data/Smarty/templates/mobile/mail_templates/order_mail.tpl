<!--{*
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2012 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
*}-->
<!--{$arrOrder.order_name01}--> <!--{$arrOrder.order_name02}--> 様

<!--{$tpl_header}-->

■ご請求金額
ご注文番号：<!--{$arrOrder.order_id}-->
お支払合計：￥ <!--{$arrOrder.payment_total|number_format|default:0}-->
ご決済方法：<!--{$arrOrder.payment_method}-->
メッセージ：<!--{$Message_tmp}-->

<!--{if $arrOther.title.value}-->
■<!--{$arrOther.title.name}-->情報
<!--{foreach key=key item=item from=$arrOther}-->
<!--{if $key != "title"}-->
<!--{if $item.name != ""}--><!--{$item.name}-->：<!--{/if}--><!--{$item.value}-->
<!--{/if}-->
<!--{/foreach}-->

<!--{/if}-->
■ご注文商品明細
<!--{section name=cnt loop=$arrOrderDetail}-->
商品コード: <!--{$arrOrderDetail[cnt].product_code}-->
商品名: <!--{$arrOrderDetail[cnt].product_name}--> <!--{$arrOrderDetail[cnt].classcategory_name1}--> <!--{$arrOrderDetail[cnt].classcategory_name2}-->
単価：￥ <!--{$arrOrderDetail[cnt].price|sfCalcIncTax:$arrOrderDetail[cnt].tax_rate:$arrOrderDetail[cnt].tax_rule|number_format}-->
数量：<!--{$arrOrderDetail[cnt].quantity}-->

<!--{/section}-->

小　計 ￥ <!--{$arrOrder.subtotal|number_format|default:0}--> <!--{if 0 < $arrOrder.tax}-->(うち消費税 ￥<!--{$arrOrder.tax|number_format|default:0}-->）<!--{/if}-->

値引き ￥ <!--{$arrOrder.use_point+$arrOrder.discount|number_format|default:0}-->
送　料 ￥ <!--{$arrOrder.deliv_fee|number_format|default:0}-->
手数料 ￥ <!--{$arrOrder.charge|number_format|default:0}-->
===============================================================
合　計 ￥ <!--{$arrOrder.payment_total|number_format|default:0}-->

<!--{if count($arrShipping) >= 1}-->
■配送情報

<!--{foreach item=shipping name=shipping from=$arrShipping}-->
◎お届け先<!--{if count($arrShipping) > 1}--><!--{$smarty.foreach.shipping.iteration}--><!--{/if}-->

<!--{*## 顧客法人管理 ADD BEGIN ##*}--><!--{if $smarty.const.USE_CUSTOMER_COMPANY === true}-->
　法人名　：<!--{$shipping.shipping_company|h}-->
<!--{*　部署名　：<!--{$shipping.shipping_company_department|h}--> *}-->
<!--{/if}--><!--{*## 顧客法人管理 ADD END ##*}-->
　お名前　：<!--{$shipping.shipping_name01}--> <!--{$shipping.shipping_name02}-->　様
　郵便番号：〒<!--{$shipping.shipping_zip01}-->-<!--{$shipping.shipping_zip02}-->
　住所　　：<!--{$arrPref[$shipping.shipping_pref]}--><!--{$shipping.shipping_addr01}--><!--{$shipping.shipping_addr02}-->
　電話番号：<!--{$shipping.shipping_tel01}-->-<!--{$shipping.shipping_tel02}-->-<!--{$shipping.shipping_tel03}-->
<!--{*## 顧客お届け先FAX ADD BEGIN ##*}--><!--{if $smarty.const.USE_OTHER_DELIV_FAX === true}-->
<!--{if $shipping.shipping_fax01}-->
　FAX番号：<!--{$shipping.shipping_fax01}-->-<!--{$shipping.shipping_fax02}-->-<!--{$shipping.shipping_fax03}-->
<!--{/if}-->
<!--{/if}--><!--{*## 顧客法人管理 ADD END ##*}-->

　お届け日：<!--{$shipping.shipping_date|date_format:"%Y/%m/%d"|default:"指定なし"}-->
お届け時間：<!--{$shipping.shipping_time|default:"指定なし"}-->

<!--{foreach item=item name=item from=$shipping.shipment_item}-->
商品コード: <!--{$item.product_code}-->
商品名: <!--{$item.product_name}--> <!--{$item.classcategory_name1}--> <!--{$item.classcategory_name2}-->
単価：￥ <!--{$item.price|sfCalcIncTax:$arrOrder.order_tax_rate:$arrOrder.order_tax_rule|number_format}-->
数量：<!--{$item.quantity}-->

<!--{/foreach}-->
<!--{/foreach}-->
<!--{/if}-->
<!--{if $arrOrder.customer_id && $smarty.const.USE_POINT !== false}-->
■ポイント情報
<!--{* ご注文前のポイント {$tpl_user_point} pt *}-->
ご使用ポイント：<!--{$arrOrder.use_point|number_format|default:0}--> pt
加算予定ポイント：<!--{$arrOrder.add_point|number_format|default:0}--> pt
現在の所持ポイント：<!--{$arrCustomer.point|number_format|default:0}--> pt
<!--{/if}-->
<!--{$tpl_footer}-->
