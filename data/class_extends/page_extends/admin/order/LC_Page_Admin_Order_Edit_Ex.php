<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2011 LOCKON CO.,LTD. All Rights Reserved.
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
require_once CLASS_REALDIR . 'pages/admin/order/LC_Page_Admin_Order_Edit.php';

/**
 * 受注修正 のページクラス(拡張).
 *
 * LC_Page_Admin_Order_Edit をカスタマイズする場合はこのクラスを編集する.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 * @version $Id: LC_Page_Admin_Order_Edit_Ex.php 20764 2011-03-22 06:26:40Z nanasess $
 */
class LC_Page_Admin_Order_Edit_Ex extends LC_Page_Admin_Order_Edit {

	// }}}
	// {{{ functions

	/**
	 * Page を初期化する.
	 *
	 * @return void
	 */
	function init() {
		parent::init();

		/*## 顧客法人管理 ADD BEGIN ##*/
		if(constant("USE_CUSTOMER_COMPANY") === true){
			$this->arrShippingKeys[] = "shipping_company";
			$this->arrShippingKeys[] = "shipping_company_kana";
			$this->arrShippingKeys[] = "shipping_company_department";
		}
		/*## 顧客法人管理 ADD END ##*/
		
        /*## 追加規格 ADD BEGIN ##*/
        if(USE_EXTRA_CLASS === true){
        	$objDb = new SC_Helper_DB_Ex();
        	$this->arrAllExtraClass = $objDb->lfGetAllExtraClass();
        	$this->arrAllExtraClassCat = $objDb->lfGetAllExtraClassCategory();
        }
        /*## 追加規格 ADD END ##*/    		
        
        /*## 写真希望・用途選択 ADD BEGIN ##*/
		$masterData = new SC_DB_MasterData();
		if(USE_ORDER_PHOTO_APPLY === true){
			$this->arrPhotoApply = $masterData->getMasterData('mtb_order_photo_apply');
		}
		if(USE_ORDER_USE_SELECT === true){
			$this->arrUseSelect = $masterData->getMasterData('mtb_order_use_select');
		}
		/*## 写真希望・用途選択 ADD END	 ##*/
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
		$objPurchase = new SC_Helper_Purchase_Ex();
		$objFormParam = new SC_FormParam_Ex();

		// パラメーター情報の初期化
		$this->lfInitParam($objFormParam);
		$objFormParam->setParam($_REQUEST);
		$objFormParam->convParam();
		$order_id = $objFormParam->getValue('order_id');
		$arrValuesBefore = array();
		
		// DB受注情報に上書きするため、空項目を追加する
		if(!isset($_POST["semi_custom"])){
			$_POST["semi_custom"] = "";
		}
				
		// DBから受注情報を読み込む
		if (!SC_Utils_Ex::isBlank($order_id)) {
			$this->setOrderToFormParam($objFormParam, $order_id);
			$this->tpl_subno = 'index';
			$arrValuesBefore['payment_id'] = $objFormParam->getValue('payment_id');
			$arrValuesBefore['payment_method'] = $objFormParam->getValue('payment_method');
		} else {
			$this->tpl_subno = 'add';
			$this->tpl_mode = 'add';
			$arrValuesBefore['payment_id'] = NULL;
			$arrValuesBefore['payment_method'] = NULL;
			// お届け先情報を空情報で表示
			$arrShippingIds[] = null;
			$objFormParam->setValue('shipping_id', $arrShippingIds);

			// 新規受注登録で入力エラーがあった場合の画面表示用に、顧客の現在ポイントを取得
			if (!SC_Utils_Ex::isBlank($objFormParam->getValue('customer_id'))) {
				$customer_id = $objFormParam->getValue('customer_id');
				$arrCustomer = SC_Helper_Customer_Ex::sfGetCustomerDataFromId($customer_id);
				$objFormParam->setValue('customer_point', $arrCustomer['point']);
			}
		}

		$this->arrSearchHidden = $objFormParam->getSearchArray();

		switch($this->getMode()) {
			case 'pre_edit':
			case 'order_id':
				break;

			case 'edit':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->arrErr = $this->lfCheckError($objFormParam);
				if (SC_Utils_Ex::isBlank($this->arrErr)) {
					$message = '受注を編集しました。';
					$order_id = $this->doRegister($order_id, $objPurchase, $objFormParam, $message, $arrValuesBefore);
					if ($order_id >= 0) {
						$this->setOrderToFormParam($objFormParam, $order_id);
					}
					$this->tpl_onload = "window.alert('" . $message . "');";
				}
				break;

			case 'add':
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$objFormParam->setParam($_POST);
					$objFormParam->convParam();
					$this->arrErr = $this->lfCheckError($objFormParam);
					if (SC_Utils_Ex::isBlank($this->arrErr)) {
						$message = '受注を登録しました。';
						$order_id = $this->doRegister(null, $objPurchase, $objFormParam, $message, $arrValuesBefore);
						if ($order_id >= 0) {
							$this->tpl_mode = 'edit';
							$objFormParam->setValue('order_id', $order_id);
							$this->setOrderToFormParam($objFormParam, $order_id);
						}
						$this->tpl_onload = "window.alert('" . $message . "');";
					}
				}

				break;

				// 再計算
			case 'recalculate':
				//支払い方法の選択
			case 'payment':
				// 配送業者の選択
			case 'deliv':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->arrErr = $this->lfCheckError($objFormParam);
				break;

				// 商品削除
			case 'delete_product':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$delete_no = $objFormParam->getValue('delete_no');
				$this->doDeleteProduct($delete_no, $objFormParam);
				$this->arrErr = $this->lfCheckError($objFormParam);
				break;

				// 商品追加ポップアップより商品選択
			case 'select_product_detail':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->doRegisterProduct($objFormParam);
				$this->arrErr = $this->lfCheckError($objFormParam);
				break;

				// 顧客検索ポップアップより顧客指定
			case 'search_customer':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->setCustomerTo($objFormParam->getValue('edit_customer_id'),
				$objFormParam);
				$this->arrErr = $this->lfCheckError($objFormParam);
				break;

				// 複数配送設定表示
			case 'multiple':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->arrErr = $this->lfCheckError($objFormParam);
				break;

				// 複数配送設定を反映
			case 'multiple_set_to':
				$this->lfInitMultipleParam($objFormParam);
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->setMultipleItemTo($objFormParam);
				break;

				// お届け先の追加
			case 'append_shipping':
				$objFormParam->setParam($_POST);
				$objFormParam->convParam();
				$this->addShipping($objFormParam);
				break;

			default:
		}

		$this->arrForm = $objFormParam->getFormParamList();
		$this->arrAllShipping = $objFormParam->getSwapArray(array_merge($this->arrShippingKeys, $this->arrShipmentItemKeys));
		$this->arrDelivTime = $objPurchase->getDelivTime($objFormParam->getValue('deliv_id'));
		$this->tpl_onload .= $this->getAnchorKey($objFormParam);
		if ($arrValuesBefore['payment_id'])
		$this->arrPayment[$arrValuesBefore['payment_id']] = $arrValuesBefore['payment_method'];

		$this->lfDecompressExtraInfo();
	}

	/**
	 * パラメーター情報の初期化を行う.
	 *
	 * @param SC_FormParam $objFormParam SC_FormParam インスタンス
	 * @return void
	 */
	function lfInitParam(&$objFormParam) {
		// 検索条件のパラメーターを初期化
		parent::lfInitParam($objFormParam);

		// お客様情報
		/*## 顧客法人管理 ADD BEGIN ##*/
		if(constant("USE_CUSTOMER_COMPANY") === true){
			$objFormParam->addParam("注文者 法人名", 'order_company', STEXT_LEN, 'aKV', array("MAX_LENGTH_CHECK"));
			$objFormParam->addParam("注文者 法人名(フリガナ)", 'order_company_kana', STEXT_LEN, 'CKV', array("MAX_LENGTH_CHECK", "KANA_CHECK"));
			$objFormParam->addParam("注文者 部署名", 'order_company_department', STEXT_LEN, 'aKV', array("MAX_LENGTH_CHECK"));
		}
		/*## 顧客法人管理 ADD END ##*/

		/*## 顧客お届け先FAX ADD BEGIN ##*/
		if(USE_OTHER_DELIV_FAX === true){
			$objFormParam->addParam("FAX番号1", "order_fax01", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
			$objFormParam->addParam("FAX番号2", "order_fax02", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
			$objFormParam->addParam("FAX番号3", "order_fax03", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
		}
		/*## 顧客お届け先FAX ADD END ##*/

		/*## 追加規格 ADD BEGIN ##*/
		if(USE_EXTRA_CLASS === true){
			$objFormParam->addParam("追加規格情報", "extra_info");
			$objFormParam->addParam("追加追加規格情報", "add_extra_info");
			$objFormParam->addParam("修正追加規格情報", "edit_extra_info");
		}
		/*## 追加規格 ADD END ##*/
		 
		/*## 顧客管理フォーム ADD BEGIN ##*/
		if(constant("USE_CUSTOMER_ADMIN_FORM") === true)
		SC_Helper_Customer_Ex::sfCustomerAdminParam($objFormParam);
		/*## 顧客管理フォーム ADD END ##*/
		 

		// 複数情報
		/*## 顧客法人管理 ADD BEGIN ##*/
		if(constant("USE_CUSTOMER_COMPANY") === true){
			$objFormParam->addParam("法人名", 'shipping_company', STEXT_LEN, 'aKV', array("MAX_LENGTH_CHECK"));
			$objFormParam->addParam("法人名(フリガナ)", 'shipping_company_kana', STEXT_LEN, 'CKV', array("MAX_LENGTH_CHECK", "KANA_CHECK"));
			$objFormParam->addParam("部署名", 'shipping_company_department', STEXT_LEN, 'aKV', array("MAX_LENGTH_CHECK"));
		}
		/*## 顧客法人管理 ADD END ##*/

		/*## 顧客お届け先FAX ADD BEGIN ##*/
		if(USE_OTHER_DELIV_FAX === true){
			$objFormParam->addParam("FAX番号1", "shipping_fax01", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
			$objFormParam->addParam("FAX番号2", "shipping_fax02", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
			$objFormParam->addParam("FAX番号3", "shipping_fax03", TEL_ITEM_LEN, 'n', array("MAX_LENGTH_CHECK" ,"NUM_CHECK"));
		}
		/*## 顧客お届け先FAX ADD END ##*/		

		/*## 写真希望・用途選択 ADD BEGIN ##*/
		if(USE_ORDER_PHOTO_APPLY === true){
			$objFormParam->addParam("写真希望", "photo_apply_id", INT_LEN, 'n', array("EXIST_CHECK", "MAX_LENGTH_CHECK", "NUM_CHECK"));
			$objFormParam->addParam("写真希望", "photo_apply");
		}
		if(USE_ORDER_USE_SELECT === true){
			$objFormParam->addParam("用途", "use_select_id", INT_LEN, 'n', array("EXIST_CHECK", "MAX_LENGTH_CHECK", "NUM_CHECK"));
			$objFormParam->addParam("用途", "use_select");
		}
		/*## 写真希望・用途選択 ADD END	 ##*/
		
		/*## 商品非課税 ADD BEGIN ##*/
		if(USE_TAXFREE_PRODUCT === true){
			$objFormParam->addParam("非課税", "taxfree");
		}
		/*## 商品非課税 ADD END ##*/
		
		$objFormParam->addParam("セミオーダー", "semi_custom", INT_LEN, 'n', array("MAX_LENGTH_CHECK", "NUM_CHECK"));
     	$objFormParam->addParam('メッセージカードの内容', 'message_card', LTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
     	$objFormParam->addParam('オーダー内容', 'custom_note', LTEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
     	
     	/*## 店舗作成予定日 ADD BEGIN ##*/
     	if(USE_ORDER_MAKE_DATE === true){
     		// YYYY/MM/DDのフォーマットなので、10桁
     		$objFormParam->addParam('店舗作成予定日', 'make_date', 10, 'a', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
     	}
     	/*## 店舗作成予定日 ADD END ##*/
	}

    function lfCheckError(&$objFormParam) {
        $objProduct = new SC_Product_Ex();
        
        $arrErr = $objFormParam->checkError();
        $arrValues = $objFormParam->getHashArray();
        
        /*## 店舗作成予定日 ADD BEGIN ##*/
        $objErr = new SC_CheckError_Ex($arrValues);
        if (!SC_Utils_Ex::isBlank($arrErr)) {
            return $arrErr;
        }
        $objErr->doFunc(array('店舗作成予定日', 'make_date'), array('DATE_STRING_CHECK'));
        $arrErr["make_date"] = $objErr->arrErr["make_date"];
        /*## 店舗作成予定日 ADD END ##*/
        
        // 商品の種類数
        $max = count($arrValues['quantity']);
        $subtotal = 0;
        $totalpoint = 0;
        $totaltax = 0;
        for ($i = 0; $i < $max; $i++) {
        	/*## 商品非課税 MDF BEGIN ##*/
        	if(USE_TAXFREE_PRODUCT === true && $arrValues['taxfree'][$i] == 1){
        		$subtotal += $arrValues['price'][$i] * $arrValues['quantity'][$i];
        	}else{
        		$subtotal += SC_Helper_DB_Ex::sfCalcIncTax($arrValues['price'][$i]) * $arrValues['quantity'][$i];
        		// 小計の計算
        		$totaltax += SC_Helper_DB_Ex::sfTax($arrValues['price'][$i]) * $arrValues['quantity'][$i];
        	}
        	/*## 商品非課税 MDF END ##*/
            
            // 加算ポイントの計算
            $totalpoint += SC_Utils_Ex::sfPrePoint($arrValues['price'][$i], $arrValues['point_rate'][$i]) * $arrValues['quantity'][$i];

            // 在庫数のチェック
            $arrProduct = $objProduct->getDetailAndProductsClass($arrValues['product_class_id'][$i]);

            // 編集前の値と比較するため受注詳細を取得
            $objPurchase = new SC_Helper_Purchase_Ex();
            $arrOrderDetail = SC_Utils_Ex::sfSwapArray($objPurchase->getOrderDetail($objFormParam->getValue('order_id'), false));

            if ($arrProduct['stock_unlimited'] != '1'
                && $arrProduct['stock'] < $arrValues['quantity'][$i] - $arrOrderDetail['quantity'][$i]) {
                $class_name1 = $arrValues['classcategory_name1'][$i];
                $class_name1 = SC_Utils_Ex::isBlank($class_name1) ? 'なし' : $class_name1;
                $class_name2 = $arrValues['classcategory_name2'][$i];
                $class_name2 = SC_Utils_Ex::isBlank($class_name2) ? 'なし' : $class_name2;
                $arrErr['quantity'][$i] .= $arrValues['product_name'][$i]
                    . '/(' . $class_name1 . ')/(' . $class_name2 . ') の在庫が不足しています。 設定できる数量は「'
                    . ($arrOrderDetail['quantity'][$i] + $arrProduct['stock']) . '」までです。<br />';
            }
        }

        // 消費税
        $arrValues['tax'] = $totaltax;
        // 小計
        $arrValues['subtotal'] = $subtotal;
        // 合計
        $arrValues['total'] = $subtotal - $arrValues['discount'] + $arrValues['deliv_fee'] + $arrValues['charge'];
        // お支払い合計
        $arrValues['payment_total'] = $arrValues['total'] - ($arrValues['use_point'] * POINT_VALUE);

        // 加算ポイント
        $arrValues['add_point'] = SC_Helper_DB_Ex::sfGetAddPoint($totalpoint, $arrValues['use_point']);

        // 最終保持ポイント
        $arrValues['total_point'] = $objFormParam->getValue('point') - $arrValues['use_point'];

        if ($arrValues['total'] < 0) {
            $arrErr['total'] = '合計額がマイナス表示にならないように調整して下さい。<br />';
        }

        if ($arrValues['payment_total'] < 0) {
            $arrErr['payment_total'] = 'お支払い合計額がマイナス表示にならないように調整して下さい。<br />';
        }

        if ($arrValues['total_point'] < 0) {
            $arrErr['use_point'] = '最終保持ポイントがマイナス表示にならないように調整して下さい。<br />';
        }

        $objFormParam->setParam($arrValues);

		/*## 写真希望・用途選択 ADD BEGIN ##*/
    	if(USE_ORDER_PHOTO_APPLY === true && empty($arrErr["photo_apply_id"])){
        	if(!isset($this->arrPhotoApply[$arrValues["photo_apply_id"]])){
        		$arrErr["photo_apply_id"] = "※ 写真希望を正しくご選択ください。<br/>";
        	}
        }
        if(USE_ORDER_USE_SELECT === true && empty($arrErr["use_select_id"])){
            if(!isset($this->arrUseSelect[$arrValues["use_select_id"]])){
        		$arrErr["use_select_id"] = "※ ご用途を正しくご選択ください。<br/>";
        	}      	
        }
        /*## 写真希望・用途選択 ADD END ##*/
        
    	return $arrErr;
    }
    
	function lfDecompressExtraInfo(){
		if(is_array($this->arrForm["extra_info"]["value"])){
			foreach($this->arrForm["extra_info"]["value"] as $idx => $extra_info){
				$this->arrExtraInfo[$idx] = unserialize($extra_info);
			}
		}
	}

	/**
	 * 受注商品の追加/更新を行う.
	 *
	 * 小画面で選択した受注商品をフォームに反映させる.
	 *
	 * @param SC_FormParam $objFormParam SC_FormParam インスタンス
	 * @return void
	 */
	function doRegisterProduct(&$objFormParam) {
		$product_class_id = $objFormParam->getValue('add_product_class_id');
		if (SC_Utils_Ex::isBlank($product_class_id)) {
			$product_class_id = $objFormParam->getValue('edit_product_class_id');
			$changed_no = $objFormParam->getValue('no');
		}
		// 選択済みの商品であれば数量を1増やす
		$exists = false;
		$arrExistsProductClassIds = $objFormParam->getValue('product_class_id');

		/*## 追加規格 MDF BEGIN ##*/
		// 追加規格情報を解析して同じフォーマットにする
		if(USE_EXTRA_CLASS === true){
			$arrExtraInfo = $objFormParam->getValue("extra_info");
			$extraInfo_tmp = split(",", $objFormParam->getValue("edit_extra_info"));
			 
			$edit_extra_classcategory_id = array();
			foreach($extraInfo_tmp as $extra){
				$extra = split(":", $extra);
				$edit_extra_classcategory_id[$extra[0]] = "$extra[1]";
			}
		}
		 
		foreach (array_keys($arrExistsProductClassIds) as $key) {
			$exists_product_class_id = $arrExistsProductClassIds[$key];
			// 同じ規格かつ同じ追加規格の場合、数量を追加する
			if ($exists_product_class_id == $product_class_id) {
				 
				$extraInfo = unserialize($arrExtraInfo[$key]);
				if(serialize($extraInfo["extra_classcategory_id"]) == serialize($edit_extra_classcategory_id)){
					$exists = true;
					$exists_no = $key;
					$arrExistsQuantity = $objFormParam->getValue('quantity');
					$arrExistsQuantity[$key]++;
					$objFormParam->setValue('quantity', $arrExistsQuantity);
				}
			}
		}
		/*## 追加規格 MDF END ##*/

		// 新しく商品を追加した場合はフォームに登録
		// 商品を変更した場合は、該当行を変更
		if (!$exists) {
			$objProduct = new SC_Product_Ex();
			$arrProduct = $objProduct->getDetailAndProductsClass($product_class_id);
			$arrProduct['quantity'] = 1;
			$arrProduct['price'] = $arrProduct['price02'];
			$arrProduct['product_name'] = $arrProduct['name'];
			$arrProduct['tax_rate'] = $objFormParam->getValue('order_tax_rate') == '' ? $this->arrInfo['tax']      : $objFormParam->getValue('order_tax_rate');
            $arrProduct['tax_rule'] = $objFormParam->getValue('order_tax_rule') == '' ? $this->arrInfo['tax_rule'] : $objFormParam->getValue('order_tax_rule');
            
			$arrUpdateKeys = array('product_id', 'product_class_id',
                                   'product_type_id', 'point_rate',
                                   'product_code', 'product_name',
                                   'classcategory_name1', 'classcategory_name2',
                                   'quantity', 'price', 'tax_rate', 'tax_rule');
			/*## 追加規格 ADD BEGIN ##*/
			if(USE_EXTRA_CLASS === true){
				$arrUpdateKeys[] = 'extra_info';
                /*## 追加規格 ADD BEGIN ##*/
                $extra_classcategory = array();
                foreach($edit_extra_classcategory_id as $extcls_id=>$extclscat_id){
                	$extra_classcategory["extra_class_name$extcls_id"] = $this->arrAllExtraClass[$extcls_id];
                	$extra_classcategory["extra_classcategory_name$extcls_id"] = $this->arrAllExtraClassCat[$extcls_id][$extclscat_id];
                }
                $extra_info["extra_classcategory_id"] = $edit_extra_classcategory_id;
                $extra_info["extra_classcategory"] = $extra_classcategory;
                $arrProduct['extra_info'] = serialize($extra_info);
			}
            /*## 追加規格 ADD END ##*/

			/*## 商品非課税 ADD BEGIN ##*/
			if(USE_TAXFREE_PRODUCT === true){
				$arrUpdateKeys[] = 'taxfree';
			}
			/*## 商品非課税 ADD END ##*/
			
			foreach ($arrUpdateKeys as $key) {
				$arrValues = $objFormParam->getValue($key);
				if (isset($changed_no)) {
					$arrValues[$changed_no] = $arrProduct[$key];
				} else {
					$added_no = 0;
					if (is_array($arrExistsProductClassIds)) {
						$added_no = count($arrExistsProductClassIds);
					}
					$arrValues[$added_no] = $arrProduct[$key];
				}
				$objFormParam->setValue($key, $arrValues);
			}
		} elseif (isset($changed_no) && $exists_no != $changed_no) {
			// 変更したが、選択済みの商品だった場合は、変更対象行を削除。
			$this->doDeleteProduct($changed_no, $objFormParam);
		}
	}
	

    /**
     * 受注商品を削除する.
     *
     * @param integer $delete_no 削除する受注商品の項番
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @return void
     */
    function doDeleteProduct($delete_no, &$objFormParam) {
        $arrDeleteKeys = array('product_id', 'product_class_id',
                               'product_type_id', 'point_rate',
                               'product_code', 'product_name',
                               'classcategory_name1', 'classcategory_name2',
                               'quantity', 'price', 'tax_rate', 'tax_rule');
        /*## 追加規格 ADD BEGIN ##*/
        if(USE_EXTRA_CLASS === true){
			$arrDeleteKeys[] = "extra_info";
        }
        /*## 追加規格 ADD END ##*/		
        
        /*## 商品非課税 ADD BEGIN ##*/
        if(USE_TAXFREE_PRODUCT === true){
        	$arrDeleteKeys[] = 'taxfree';
        }
        /*## 商品非課税 ADD END ##*/
        
        foreach ($arrDeleteKeys as $key) {
            $arrNewValues = array();
            $arrValues = $objFormParam->getValue($key);
            foreach ($arrValues as $index => $val) {
                if ($index != $delete_no) {
                    $arrNewValues[] = $val;
                }
            }
            $objFormParam->setValue($key, $arrNewValues);
        }
    }
    

    /**
     * DB更新処理
     *
     * @param integer $order_id 受注ID
     * @param SC_Helper_Purchase $objPurchase SC_Helper_Purchase インスタンス
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param string $message 通知メッセージ
     * @param array $arrValuesBefore 更新前の受注情報
     * @return integer $order_id 受注ID
     *
     * エラー発生時は負数を返す。
     */
    function doRegister($order_id, &$objPurchase, &$objFormParam, &$message, &$arrValuesBefore) {

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $arrValues = $objFormParam->getDbArray();

        $where = "order_id = ?";

        $objQuery->begin();

        // 支払い方法が変更されたら、支払い方法名称も更新
        if ($arrValues['payment_id'] != $arrValuesBefore['payment_id']) {
            $arrValues['payment_method'] = $this->arrPayment[$arrValues['payment_id']];
            $arrValuesBefore['payment_id'] = NULL;
        }

        /*## 写真希望・用途選択 ADD BEGIN ##*/
		if(USE_ORDER_PHOTO_APPLY === true){
			$arrValues["photo_apply"] = $this->arrPhotoApply[$arrValues["photo_apply_id"]];
		}
		if(USE_ORDER_USE_SELECT === true){
			$arrValues["use_select"] = $this->arrUseSelect[$arrValues["use_select_id"]];
		}
		/*## 写真希望・用途選択 ADD END	 ##*/
     	
        // 受注テーブルの更新
        $order_id = $objPurchase->registerOrder($order_id, $arrValues);

        /*## 追加規格 MDF BEGIN ##*/
        $arrCols = array("product_id",
                          "product_class_id",
                          "product_code",
                          "product_name",
                          'price', 'quantity',
                          "point_rate",
                          "classcategory_name1",
                          "classcategory_name2",
                		  "tax_rate",
                		  "tax_rule"
        );
        if(USE_EXTRA_CLASS === true){
        	$arrCols[] = "extra_info";
        }
        /*## 追加規格 MDF END ##*/
        
        /*## 商品非課税 ADD BEGIN ##*/
        if(USE_TAXFREE_PRODUCT === true){
        	$arrCols[] = 'taxfree';
        }
        /*## 商品非課税 ADD END ##*/
		
        $arrDetail = $objFormParam->getSwapArray($arrCols);
        
        // 変更しようとしている商品情報とDBに登録してある商品情報を比較することで、更新すべき数量を計算
        $max = count($arrDetail);
        $k = 0;
        $arrStockData = array();
        for($i = 0; $i < $max; $i++) {
            if (!empty($arrDetail[$i]['product_id'])) {
                $arrPreDetail = $objQuery->select('*', "dtb_order_detail", "order_id = ? AND product_class_id = ?", array($order_id, $arrDetail[$i]['product_class_id']));
                if (!empty($arrPreDetail) && $arrPreDetail[0]['quantity'] != $arrDetail[$i]['quantity']) {
                    // 数量が変更された商品
                    $arrStockData[$k]['product_class_id'] = $arrDetail[$i]['product_class_id'];
                    $arrStockData[$k]['quantity'] = $arrPreDetail[0]['quantity'] - $arrDetail[$i]['quantity'];
                    ++$k;
                } elseif (empty($arrPreDetail)) {
                    // 新しく追加された商品 もしくは 違う商品に変更された商品
                    $arrStockData[$k]['product_class_id'] = $arrDetail[$i]['product_class_id'];
                    $arrStockData[$k]['quantity'] = -$arrDetail[$i]['quantity'];
                    ++$k;
                }
                $objQuery->delete("dtb_order_detail", "order_id = ? AND product_class_id = ?", array($order_id, $arrDetail[$i]['product_class_id']));
            }
        }

        // 上記の新しい商品のループでDELETEされなかった商品は、注文より削除された商品
        $arrPreDetail = $objQuery->select('*', "dtb_order_detail", "order_id = ?", array($order_id));
        foreach ($arrPreDetail AS $key=>$val) {
            $arrStockData[$k]['product_class_id'] = $val['product_class_id'];
            $arrStockData[$k]['quantity'] = $val['quantity'];
            ++$k;
        }

        // 受注詳細データの更新
        $objPurchase->registerOrderDetail($order_id, $arrDetail);

        // 在庫数調整
        if (ORDER_DELIV != $arrValues['status']
            && ORDER_CANCEL != $arrValues['status']) {
            foreach ($arrStockData AS $stock) {
                $objQuery->update('dtb_products_class', array(),
                                  'product_class_id = ?',
                                  array($stock['product_class_id']),
                                  array('stock' => 'stock + ?'),
                                  array($stock['quantity']));
            }
        }

        $arrAllShipping = $objFormParam->getSwapArray($this->arrShippingKeys);
        $arrAllShipmentItem = $objFormParam->getSwapArray($this->arrShipmentItemKeys);

        $arrDelivTime = $objPurchase->getDelivTime($objFormParam->getValue('deliv_id'));

        $arrShippingValues = array();
        foreach ($arrAllShipping as $shipping_index => $arrShipping) {
            $shipping_id = $arrShipping['shipping_id'];
            $arrShippingValues[$shipping_index] = $arrShipping;

            $arrShippingValues[$shipping_index]['shipping_date']
                = SC_Utils_Ex::sfGetTimestamp($arrShipping['shipping_date_year'],
                                              $arrShipping['shipping_date_month'],
                                              $arrShipping['shipping_date_day']);

            // 配送業者IDを取得
            $arrShippingValues[$shipping_index]['deliv_id'] = $objFormParam->getValue('deliv_id');

            // お届け時間名称を取得
            $arrShippingValues[$shipping_index]['shipping_time'] = $arrDelivTime[$arrShipping['time_id']];

            // 複数配送の場合は配送商品を登録
            if (!SC_Utils_Ex::isBlank($arrAllShipmentItem)) {
                $arrShipmentValues = array();

                foreach ($arrAllShipmentItem[$shipping_index] as $key => $arrItem) {
                    $i = 0;
                    foreach ($arrItem as $item) {
                        $arrShipmentValues[$shipping_index][$i][str_replace('shipment_', '', $key)] = $item;
                        $i++;
                    }
                }
                $objPurchase->registerShipmentItem($order_id, $shipping_id,
                                                   $arrShipmentValues[$shipping_index]);
            }
        }
        $objPurchase->registerShipping($order_id, $arrShippingValues, false);
        $objQuery->commit();
        return $order_id;
    }    
}
?>
