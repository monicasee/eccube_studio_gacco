<?php
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

// {{{ requires
require_once CLASS_REALDIR . 'pages/shopping/LC_Page_Shopping_Payment.php';

/**
 * 支払い方法選択 のページクラス(拡張).
 *
 * LC_Page_Shopping_Payment をカスタマイズする場合はこのクラスを編集する.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 * @version $Id: LC_Page_Shopping_Payment_Ex.php 21867 2012-05-30 07:37:01Z nakanishi $
 */
class LC_Page_Shopping_Payment_Ex extends LC_Page_Shopping_Payment {

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
        parent::process();
    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }
    
    /**
     * Page のアクション.
     *
     * @return void
     */
    function action() {

        $objSiteSess = new SC_SiteSession_Ex();
        $objCartSess = new SC_CartSession_Ex();
        $objPurchase = new SC_Helper_Purchase_Ex();
        $objCustomer = new SC_Customer_Ex();
        $objFormParam = new SC_FormParam_Ex();

        $this->is_multiple = $objPurchase->isMultiple();

        // カートの情報を取得
        $this->arrShipping = $objPurchase->getShippingTemp($this->is_multiple);

        $this->tpl_uniqid = $objSiteSess->getUniqId();
        $cart_key = $objCartSess->getKey();
        $this->cartKey = $cart_key;
        $objPurchase->verifyChangeCart($this->tpl_uniqid, $objCartSess);

        // 配送業者を取得
        
        /*## 商品配送方法指定 MDF BEGIN ##*/
        if(USE_PRODUCT_DELIV === true){
        	$this->arrDeliv = $objPurchase->getDelivByProduct($cart_key, $objCartSess->getAllProductID($this->cartKey));
        }else{
			$this->arrDeliv = $objPurchase->getDeliv($cart_key);
        }
        /*## 商品配送方法指定 MDF END ##*/
        
        $this->is_single_deliv = $this->isSingleDeliv($this->arrDeliv);

        // 会員情報の取得
        if ($objCustomer->isLoginSuccess(true)) {
            $this->tpl_login = '1';
            $this->tpl_user_point = $objCustomer->getValue('point');
            $this->name01 = $objCustomer->getValue('name01');
            $this->name02 = $objCustomer->getValue('name02');
        }

        // 戻り URL の設定
        // @deprecated 2.12.0 テンプレート直書きに戻した
        $this->tpl_back_url = '?mode=return';

        $arrOrderTemp = $objPurchase->getOrderTemp($this->tpl_uniqid);
        // 正常に受注情報が格納されていない場合はカート画面へ戻す
        if (SC_Utils_Ex::isBlank($arrOrderTemp)) {
            SC_Response_Ex::sendRedirect(CART_URLPATH);
            SC_Response_Ex::actionExit();
        }

        // カート内商品の妥当性チェック
        $this->tpl_message = $objCartSess->checkProducts($cart_key);
        if (strlen($this->tpl_message) >= 1) {
            SC_Response_Ex::sendRedirect(CART_URLPATH);
            SC_Response_Ex::actionExit();
        }

        /*
         * 購入金額の取得
         * ここでは送料を加算しない
         */
        $this->arrPrices = $objCartSess->calculate($cart_key, $objCustomer);

        // お届け日一覧の取得
        $this->arrDelivDate = $objPurchase->getDelivDate($objCartSess, $cart_key);

        switch ($this->getMode()) {
            /*
             * 配送業者選択時のアクション
             * モバイル端末以外の場合は, JSON 形式のデータを出力し, ajax で取得する.
             */
            case 'select_deliv':
                $this->setFormParams($objFormParam, $arrOrderTemp, true, $this->arrShipping);
                $objFormParam->setParam($_POST);
                $this->arrErr = $objFormParam->checkError();
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    $deliv_id = $objFormParam->getValue('deliv_id');
                    $arrSelectedDeliv = $this->getSelectedDeliv($objPurchase, $objCartSess, $deliv_id);
                    $arrSelectedDeliv['error'] = false;
                } else {
                    $arrSelectedDeliv = array('error' => true);
                    $this->tpl_mainpage = 'shopping/select_deliv.tpl'; // モバイル用
                }
        
                if (SC_Display_Ex::detectDevice() != DEVICE_TYPE_MOBILE) {
                	// セミオーダーが使える支払方法を取る
                	$arrSelectedDeliv['arrPayment'] = $this->lfGetSemiCustomPayment($arrSelectedDeliv['arrPayment'], $objFormParam);
                
                    echo SC_Utils_Ex::jsonEncode($arrSelectedDeliv);
                    SC_Response_Ex::actionExit();
                } else {
                    $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                    $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                }
                break;

            // 登録処理
            case 'confirm':
                // パラメーター情報の初期化
                $this->setFormParams($objFormParam, $_POST, false, $this->arrShipping);

                $deliv_id = $objFormParam->getValue('deliv_id');
                $arrSelectedDeliv = $this->getSelectedDeliv($objPurchase, $objCartSess, $deliv_id);
                $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                $this->img_show = $arrSelectedDeliv['img_show'];

                $this->arrErr = $this->lfCheckError($objFormParam, $this->arrPrices['subtotal'], $this->tpl_user_point);

                if (empty($this->arrErr)) {
                    $this->saveShippings($objFormParam, $this->arrDelivTime);
                    $this->lfRegistData($this->tpl_uniqid, $objFormParam->getDbArray(), $objPurchase, $this->arrPayment);

                    // 正常に登録されたことを記録しておく
                    $objSiteSess->setRegistFlag();


                    // 確認ページへ移動
                    SC_Response_Ex::sendRedirect(SHOPPING_CONFIRM_URLPATH);
                    SC_Response_Ex::actionExit();
                }

                break;

            // 前のページに戻る
            case 'return':

                // 正常な推移であることを記録しておく
                $objSiteSess->setRegistFlag();


                $url = null;
                if ($this->is_multiple) {
                    $url = MULTIPLE_URLPATH . '?from=multiple';
                } elseif ($objCustomer->isLoginSuccess(true)) {
                    if ($product_type_id == PRODUCT_TYPE_DOWNLOAD) {
                        $url = CART_URLPATH;
                    } else {
                        $url = DELIV_URLPATH;
                    }
                } else {
                    $url = SHOPPING_URL . '?from=nonmember';
                }

                SC_Response_Ex::sendRedirect($url);
                SC_Response_Ex::actionExit();
                break;
                            	
            default:
                // FIXME 前のページから戻ってきた場合は別パラメーター(mode)で処理分岐する必要があるのかもしれない
                $this->setFormParams($objFormParam, $arrOrderTemp, false, $this->arrShipping);

                if (!$this->is_single_deliv) {
                    $deliv_id = $objFormParam->getValue('deliv_id');
                } else {
                    $deliv_id = $this->arrDeliv[0]['deliv_id'];
                }

                if (!SC_Utils_Ex::isBlank($deliv_id)) {
                    $objFormParam->setValue('deliv_id', $deliv_id);
                    $arrSelectedDeliv = $this->getSelectedDeliv($objPurchase, $objCartSess, $deliv_id);
                    $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                    $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                    $this->img_show = $arrSelectedDeliv['img_show'];
                }
                break;
        }

        // セミオーダー指定でページ更新の場合、セミオーダー値を設定する
        if($this->getMode() == "custom"){
        	$objFormParam->setValue('semi_custom', $_POST["semi_custom"]);
        }
            	
        // モバイル用 ポストバック処理
        if (SC_Display_Ex::detectDevice() == DEVICE_TYPE_MOBILE
            && SC_Utils_Ex::isBlank($this->arrErr)) {
            $this->tpl_mainpage = $this->getMobileMainpage($this->is_single_deliv, $this->getMode());
        }

        // セミオーダーが使える支払方法を取る
        $this->arrPayment = $this->lfGetSemiCustomPayment($this->arrPayment, $objFormParam);
        
        $this->arrForm = $objFormParam->getFormParamList();
    }
    
    /**
     * 配送業者IDから, 支払い方法, お届け時間の配列を取得する.
     *
     * 結果の連想配列の添字の値は以下の通り
     * - 'arrDelivTime' - お届け時間の配列
     * - 'arrPayment' - 支払い方法の配列
     * - 'img_show' - 支払い方法の画像の有無
     *
     * @param SC_Helper_Purchase $objPurchase SC_Helper_Purchase インスタンス
     * @param SC_CartSession $objCartSess SC_CartSession インスタンス
     * @param integer $deliv_id 配送業者ID
     * @return array 支払い方法, お届け時間を格納した配列
     */
     function getSelectedDeliv(&$objPurchase, &$objCartSess, $deliv_id) {
     	$arrResults = array();
        $arrResults['arrDelivTime'] = $objPurchase->getDelivTime($deliv_id);
        $total = $objCartSess->getAllProductsTotal($objCartSess->getKey(), $deliv_id);
     	
        /*## 商品支払方法指定 ADD BEGIN ##*/
     	if(USE_PRODUCT_PAYMENT === true){
     		$cartProductClsIds = $objCartSess->getAllProductClassID(1);
     		if(!count($cartProductClsIds)){
     			return array();
     		}

     		$objQuery =& SC_Query_Ex::getSingletonInstance();
     		$cartProductIds = $objQuery->getCol("product_id", "dtb_products_class", 
     			"product_class_id IN(".join(", ", array_fill(0, count($cartProductClsIds), "?")). ")", $cartProductClsIds);

     		$arrResults['arrPayment'] = $objPurchase->getPaymentsByProduct($total, $cartProductIds, $deliv_id);
     	}
     	else{
        	$arrResults['arrPayment'] = $objPurchase->getPaymentsByPrice($total, $deliv_id);
        	$arrResults['img_show'] = $this->hasPaymentImage($arrResults['arrPayment']);
     	}
     	/*## 商品支払方法指定 ADD END ##*/
        
     	return $arrResults;
     }
    
    /**
     * パラメーター情報の初期化を行う.
     *
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param boolean $deliv_only 必須チェックは deliv_id のみの場合 true
     * @param array $arrShipping 配送先情報の配列
     * @return void
     */
     function lfInitParam(&$objFormParam, $deliv_only, &$arrShipping) {
     	parent::lfInitParam($objFormParam, $deliv_only, $arrShipping);

     	$objFormParam->addParam('セミオーダー', 'semi_custom', INT_LEN, 'n', array('MAX_LENGTH_CHECK', 'NUM_CHECK'));
     	$objFormParam->addParam('メッセージカードの内容', 'message_card', LTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
     	$objFormParam->addParam('オーダー内容', 'custom_note', LTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
     }
     
    /**
     * 受注一時テーブルへ登録を行う.
     *
     * @param integer $uniqid 受注一時テーブルのユニークID
     * @param array $arrForm フォームの入力値
     * @param SC_Helper_Purchase $objPurchase SC_Helper_Purchase インスタンス
     * @param array $arrPayment お支払い方法の配列
     * @return void
     */
    function lfRegistData($uniqid, $arrForm, &$objPurchase, $arrPayment) {

    	// セミオーダー非チェックの場合、オーダー内容をクリアする
    	if(empty($arrForm["semi_custom"])){
    		$arrForm['custom_note'] = "";
    	}
   		
		parent::lfRegistData($uniqid, $arrForm, $objPurchase, $arrPayment);
    }
    
    function lfGetSemiCustomPayment($arrPayment, $objFormParam){
    	$arrCustomPayment = $arrPayment;
    	$semi_custom = $objFormParam->getValue('semi_custom');
    	 
    	if($semi_custom && is_array($arrPayment)
    		&& defined("SEMI_CUSTOM_PAYMENT_IDS") && strlen(SEMI_CUSTOM_PAYMENT_IDS) > 0 ){
    		$arrCustomPaymentIds = split(",", SEMI_CUSTOM_PAYMENT_IDS);
    		$arrCustomPayment = array();
    		
    		foreach($arrPayment as $p){
    			if(in_array($p["payment_id"], $arrCustomPaymentIds)){
    				$arrCustomPayment[] = $p;
    			}
    		}
    	}
    	return $arrCustomPayment;
    }
}
