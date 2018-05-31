<?php
	require_once(SL_PLUGIN_SRC_DIR . 'comp/salonconst-component.php');
	require_once(SL_PLUGIN_SRC_DIR . 'comp/linkFunction-component.php');
	require_once(SL_PLUGIN_SRC_DIR . 'data/link-data.php');

class LinkFunctionHotpepper_component extends LinkFunction_component {

	private $datas = null;

	private $link_config = null;
	private $login_cookies = null;

	private $now = 0;
	private $today = "";
	private $user_id = "";
	private $password = "";


	const URL_LOGIN = "https://salonboard.com/login/";
	const URL_AFTER_LOGIN = "https://salonboard.com/CNC/login/doLogin/";
	const URL_AFTER_POST_LOGIN = "https://salonboard.com/CLP/bt/top/";
	const URL_GET_REGIST = "https://salonboard.com/CLP/bt/reserve/ext/extReserveRegist/";
	const URL_POST_REGIST = "https://salonboard.com/CLP/bt/reserve/ext/extReserveRegist/doComplete";
	const URL_GET_SEARCH_LIST = "https://salonboard.com/CLP/bt/schedule/salonSchedule/";

	const URL_GET_UPDATE = "https://salonboard.com/CLP/bt/reserve/ext/extReserveChange/";
	const URL_POST_UPDATE = "https://salonboard.com/CLP/bt/reserve/ext/extReserveChange/doComplete";

	const URL_GET_CANCEL = "https://salonboard.com/CLP/bt/reserve/ext/extReserveDetail/";
	const URL_POST_CANCEL = "https://salonboard.com/CLP/bt/reserve/ext/extReserveDetail/doCancel";

	function __construct() {
		add_action( 'sl_update_reservation_hotpepper', array(&$this,'update_reservation_from_service') );
		add_action( 'sl_update_service_hotpepper', array(&$this,'update_service_from_salon'), 1, 3 );
	}

	public function update_service_from_salon($service_cd, $agent, $set_datas_serialized) {

		$this->datas = new Link_Data();

		try {
			//get hotpepper base information

			$set_datas = unserialize($set_datas_serialized);

			$this->now = time();

			//set filter
			$set_config_hotpepper = null;
			$set_config_hotpepper = apply_filters('salon_set_config_for_hotpepper'
					, $set_config_hotpepper
					, $set_datas);

			if ( count($set_config_hotpepper) == 0 ) {
				throw new Exception( Salon_Component::getMsg('E931',"Need the config for Hotpepper".basename(__FILE__).':'.__LINE__),3);
			}

			$this->user_id = $set_config_hotpepper['user_id'];
			$this->password = $set_config_hotpepper['password'];
			$set_datas['stylist_id'] = $set_config_hotpepper['stylist_id'];
			$set_datas['route_id_select_char'] = $set_config_hotpepper['route_id_select_char'];
			$set_datas['rsv_remark'] = $set_config_hotpepper['rsv_remark'];


			$this->set_agent($agent);

	 		$this->link_config = get_option("salon_link_config");
	 		//
	 		$this->link_config = null;

			//first time or after 30 min login, login again
			if ( ($this->link_config === false )
			||(empty($this->link_config['cookies'])
			|| ($this->link_config['set_time'] + 60 * 30 < $this->now) )){
				$this->_login($set_datas);
			}
			else {
				$this->login_cookies = unserialize($this->link_config['cookies']);
			}
			//if update the reservation, cancel first and regist next in Hotpepper.
			if ($set_datas['type'] == 'inserted') {
				$set_datas['link_cd'] = $this->_createResevation($set_datas);
				$set_datas['information'] = "";
				$set_datas['link_status'] = Salon_Link_Status::SALON_AFTER_REGIST;
				$this->datas->insertTable($this->_editLinkData($set_datas));
			}
			elseif ($set_datas['type'] == 'updated') {
				$link_cd = $this->datas->getLinkCd($set_datas['reservation_cd']);
				if ($link_cd === false) {
					throw new Exception( Salon_Component::getMsg('E931',"Link Cd does not exist -> ".$set_datas_serialized." ".basename(__FILE__).':'.__LINE__),3);
				}
				$this->_updateReservation($link_cd, $set_datas);
				$set_datas['link_cd'] = $link_cd;
				$set_datas['information'] = "";
				$set_datas['link_status'] = Salon_Link_Status::SALON_AFTER_UPDATE;
				$this->datas->updateTable($this->_editLinkData($set_datas));

			}
			elseif ($set_datas['type'] == 'deleted'
					|| $set_datas['type'] == 'cancel' ) {
				$link_cd = $this->datas->getLinkCd($set_datas['reservation_cd']);
				if ($link_cd === false) {
					throw new Exception( Salon_Component::getMsg('E931',"Link Cd does not exist -> ".$set_datas_serialized." ".basename(__FILE__).':'.__LINE__),3);
				}
				$this->_cancelReservation($link_cd, $set_datas);
				$set_datas['link_cd'] = $link_cd;
				$set_datas['information'] = "";
				$set_datas['link_status'] = Salon_Link_Status::SALON_AFTER_CANCEL;
				$this->datas->deleteTable($this->_editLinkData($set_datas));
			}
			else {
				throw new Exception( Salon_Component::getMsg('E931',"Link Data Error -> ".$set_datas_serialized." ".basename(__FILE__).':'.__LINE__),3);
			}


		}
		catch (Exception $e) {
			$set_datas['information'] = $e->getMessage();
	 		$set_datas['link_status'] = Salon_Link_Status::SALON_LINK_ERROR;
			if ($set_datas['type'] == 'inserted') {
				$set_datas['link_cd'] = "";
				$this->datas->insertTable($this->_editLinkData($set_datas));
			}
			else {
				$this->datas->updateTable($this->_editLinkData($set_datas));
			}
		}

	}

	private function _editLinkData($datas) {
		$set_data = array();
		$set_data['reservation_cd'] = $datas['reservation_cd'];
		$set_data['link_cd'] = $datas['link_cd'];
		$set_data['service_cd'] = Salon_Service::HOTPEPPER;
		$set_data['link_status'] = $datas['link_status'];
		$set_data['information'] = $datas["information"];
		$set_data['remark'] = "";
		$set_data['memo'] = "";
		$set_data['notes'] = "";

		return $set_data;

	}

	public function update_reservation_from_service() {
		//メールを読む
		//アクセスする
		//salon-bookingを更新する

	}

	private function _login() {

		$url = self::URL_LOGIN;

		$set_cookies = array();
		$response_cookies = null;
		$response_body = null;
		//http get login
		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');
		//set userid and password, Post it
		$url = self::URL_AFTER_LOGIN;

		$set_cookies = array();
		$set_header = array(
				"Referer" => self::URL_LOGIN
		);
		$set_body = array(
				"userId" => $this->user_id
				,"password" => $this->password
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Login Post Error ".basename(__FILE__).':'.__LINE__),3);
		}

		//analize the body, get login_key and store_id
		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);
		//found input tag and attribute name
		$nodes = $domDocument->getElementsByTagName("input");
		$LOGIN_KEY = "";
		$STORE_ID = "";
		foreach ($nodes as $node ) {
			$name = $node->getAttribute("name");
			if ($name == "HPB_LOGIN_KEY") {
				$LOGIN_KEY = $node->getAttribute("value");
			}
			if ($name == "STORE_ID") {
				$STORE_ID = $node->getAttribute("value");
			}
		}
		//post again
		$url = self::URL_AFTER_POST_LOGIN;

		$set_cookies = array();
		$set_header = array(
				"Referer" => self::URL_AFTER_LOGIN
				,'user-agent'  => $this->agent,
		);
		$set_body = array(
				"HPB_LOGIN_KEY" => $LOGIN_KEY
				,"STORE_ID" => $STORE_ID
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Login Post Again Error ".basename(__FILE__).':'.__LINE__),3);
		}

		//get cookeis and save it, each 30 min do this function
		$this->login_cookies = $response_cookies;


		$this->link_config['cookies'] = serialize($this->login_cookies);
		$this->link_config['set_time'] = time();
		$this->link_config['HPB_LOGIN_KEY'] = $LOGIN_KEY;
		$this->link_config['STORE_ID'] = $STORE_ID;

		update_option("salon_link_config", $this->link_config);

		return true;

	}

	private function _checkResponsePage ($body ) {

		$start = strpos($body,'SALON BOARD : エラー');
		if ($start === false) {
			$start = strpos($body,'SALON BOARD　システムエラー');
			if ($start === false) {
				return true;
			}
		}
		return false;
	}

	private function _getNameFromInputTag($query, $xpath, $node) {
		$datas = $xpath->query($query, $node);
		//if  result of  the query is not exist, error
		if ($datas->length == 0) {
			return false;
		}
	 	return $datas->item(0)->getAttribute('value');
	}

	private function _getNameFromSelectTag($query, $select_value, $xpath, $node) {
		$datas = $xpath->query($query, $node);
		//if  result of  the query is not exist, error
		if ($datas->length == 0) {
			return false;
		}
		$child_nodes = $datas->item(0)->childNodes;
		foreach ($child_nodes as $node ) {
			if ($node->nodeValue == $select_value) {
				return $node->getAttribute("value");
			}
		}

		return false;
	}

	private function _createResevation($set_datas) {
		$now = date('YmdHis');


		$url = self::URL_GET_REGIST;
		$YYYYMMDD = str_replace('-','',substr($set_datas['time_from'],0,10));
		$HHMM =  str_replace(':', '', substr($set_datas['time_from'],-5,5));
		$url .= "?date=".$YYYYMMDD;
		$url .= "&time=".$HHMM;
		$url .= "&stylistId=".$set_datas['stylist_id'];
		$url .= "&rlastupdate=".$now;
		$url .= "&SUBMIT_STORE_ID=".$this->link_config['STORE_ID'];


		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Get Regist Error ".basename(__FILE__).':'.__LINE__),3);
		}

		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);

		$domDocument = new DOMDocument();
		$domDocument->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'utf-8'));

		$xpath = new DOMXPath($domDocument);

		$forms = $domDocument->getElementById("extReserveRegist");

		$apache_token = $this->_getNameFromInputTag(
			'.//input[@name="org.apache.struts.taglib.html.TOKEN"]'
			, $xpath
			, $forms );

		$store_id_tabcheck = $this->_getNameFromInputTag(
			'.//input[@name="storeIdForMultipleTabCheck"]'
			, $xpath
			, $forms );

		$route_id = $this->_getNameFromSelectTag(
			'.//select[@name="rsvRouteId"]'
			, $set_datas['route_id_select_char']
			, $xpath
			, $forms );


		$customer_id = $domDocument->getElementById('customerId');
		$caution_flag = $domDocument->getElementById('cautionFlg');
		$rsv_disp_date = $domDocument->getElementById('rsvDispDate');

		$from = new DateTime($set_datas['time_from']);
		$to = new DateTime($set_datas['time_to']);
		$min = $from->diff($to);

		$kanji = str_replace("　"," ",$set_datas['non_regist_name']);

// 		$kana = mb_convert_kana($kanji, "C");
// 		if(preg_match("/^[ァ-ヾ]+$/u",$kana) === false) {
// 			$kana = "サロン ブッキング";
// 		}
// 		$kanas = explode(' ', $kana);
		$kana = array("サロン","ブッキング");
		$names = explode(' ', $kanji);

		$url = self::URL_POST_REGIST;

		$set_cookies = $this->login_cookies;
		//referer is the same to request
		$set_header = array(
				"Referer" => self::URL_POST_REGIST
				,'user-agent'  => $this->agent
		);
		$set_body = array(
				"org.apache.struts.taglib.html.TOKEN" => $apache_token
				,"storeIdForMultipleTabCheck" => $store_id_tabcheck
				,"date" => $YYYYMMDD
				,"customerId" => $customer_id->getAttribute("value")
				,"cautionFlg" => $caution_flag->getAttribute("value")
				,"stylistId" => $set_datas['stylist_id']
				,"rsvDispDate" => $rsv_disp_date->getAttribute("value")
				,"time" => $HHMM
				,"rsvTerm" => $min->h * 60 + $min->i
				,"rsvRouteId" => $route_id
				,"setmenuId" => ""
				,"menuCategoryCdList" => ""
				,"menuIdList" => ""
				,"netCouponId" => ""
				,"extCouponCategoryCdList" => ""
				,"extCouponIdList" => ""
				,"nmSeiKana" => $kanas[0]
				,"nmMeiKana" => $kanas[1]
				,"nmSei" => trim($names[0])
				,"nmMei" => trim($names[1])
				,"tel" => ""
				,"tel2" => ""
				,"customerNo" => ""
				,"rsvEtc" => $set_datas['rsv_remark']
				,"SUBMIT_STORE_ID" => $this->link_config['STORE_ID']
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		if (SALON_FOR_LINK_LOG ) error_log($set_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		sleep(3);

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Post regist reservation ".basename(__FILE__).':'.__LINE__),3);
		}


		$url = self::URL_GET_SEARCH_LIST;
		$url .= "?date=".$YYYYMMDD;

		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		sleep(3);

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Get Reservation List after Regist Detail ".basename(__FILE__).':'.__LINE__),3);
		}

		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);

		$domDocument = new DOMDocument();
		$domDocument->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'utf-8'));

		$xpath = new DOMXPath($domDocument);

		$reserved_area = $domDocument->getElementById("scheduleItemArea");

		$reserved_informations = $xpath->query(
				'.//div[contains(@class,"panel_reserve")]'
				,$reserved_area);
		//if  result of  the query is not exist, error
		if ($reserved_informations->length == 0) {
			throw new Exception( Salon_Component::getMsg('E931',"Can not analyze the  Reservation List after Regist Detail ".basename(__FILE__).':'.__LINE__),3);
		}
		$set_reserved_id = false;
		foreach ($reserved_informations as $each_reserved_information) {
			$reserved_id = $xpath->query(
					'.//span[contains(@class,"panel_reserve_id")]'
					,$each_reserved_information);
			$reserved_date = $xpath->query(
					'.//span[contains(@class,"panel_reserve_date")]'
					,$each_reserved_information);
			$reserved_start = $xpath->query(
					'.//span[contains(@class,"panel_reserve_start")]'
					,$each_reserved_information);
			$reserved_update = $xpath->query(
					'.//span[contains(@class,"panel_reserve_update")]'
					,$each_reserved_information);

			//if  result of  the query is not exist, skip
			if ($reserved_id->length == 0
			|| $reserved_date->length == 0
			|| $reserved_start->length == 0
			|| $reserved_update->length == 0
												) {
				throw new Exception( Salon_Component::getMsg('E931',"Get after Regist Detail ".basename(__FILE__).':'.__LINE__),3);
			}
			else {
				if ($reserved_start->item(0)->nodeValue  == $HHMM ) {
					//the difference from now is less than 1 minute.
					$check_local = date_i18n('YmdHis');

					$diff = +$reserved_update->item(0)->nodeValue
						 	- $check_local;
					if ($diff <  60) {
						$set_reserved_id = $reserved_id->item(0)->nodeValue;
						break;
					}
				}
			}
		}

		if ($set_reserved_id === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"does not get reservation id form Hotpepper after Regist Detail ".basename(__FILE__).':'.__LINE__),3);
		}

		return $set_reserved_id;

	}

	private function _updateReservation($link_cd, $set_datas) {
		if ( !isset($link_cd) ) {
			return false;
		}
		$now = date('YmdHis');
		//詳細画面
		$url = self::URL_GET_UPDATE;
		$url .= "?reserveId=".$link_cd;

		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Get Reservation List before Change Detail ".basename(__FILE__).':'.__LINE__),3);
		}

		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);

		$domDocument = new DOMDocument();
		$domDocument->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'utf-8'));

		$xpath = new DOMXPath($domDocument);

		$forms = $domDocument->getElementById("extReserveChange");

		$apache_token = $this->_getNameFromInputTag(
			'.//input[@name="org.apache.struts.taglib.html.TOKEN"]'
			, $xpath
			, $forms );

		$store_id_tabcheck = $this->_getNameFromInputTag(
			'.//input[@name="storeIdForMultipleTabCheck"]'
			, $xpath
			, $forms );

		$route_id = $this->_getNameFromSelectTag(
			'.//select[@name="rsvRouteId"]'
			, $set_datas['route_id_select_char']
			, $xpath
			, $forms );


		$customer_id = $domDocument->getElementById('customerId');
		$caution_flag = $domDocument->getElementById('cautionFlg');
		$rsv_disp_date = $domDocument->getElementById('rsvDispDate');

		$SeiKana = $domDocument->getElementById('orgNmSeiKana');
		$MeiKana = $domDocument->getElementById('orgNmMeiKana');

		$from = new DateTime($set_datas['time_from']);
		$to = new DateTime($set_datas['time_to']);
		$min = $from->diff($to);

		$YYYYMMDD = str_replace('-','',substr($set_datas['time_from'],0,10));
		$HHMM =  str_replace(':', '', substr($set_datas['time_from'],-5,5));

		$url = self::URL_POST_UPDATE;

		$set_cookies = $this->login_cookies;
		//referer is the same to request
		$set_header = array(
				"Referer" => self::URL_GET_UPDATE."?reserveId=".$link_cd
				,'user-agent'  => $this->agent
		);
		$set_body = array(
				"org.apache.struts.taglib.html.TOKEN" => $apache_token
				,"storeIdForMultipleTabCheck" => $store_id_tabcheck
				,"rsvdate" => $YYYYMMDD
				,"customerId" => $customer_id->getAttribute("value")
				,"cautionFlg" => $caution_flag->getAttribute("value")
 				,"stylistId" => $set_datas['stylist_id']
// 				,"stylistId" => "noChange"
				,"rsvDispDate" => $rsv_disp_date->getAttribute("value")
				,"rsvtime" => $HHMM
				,"rsvTerm" => $min->h * 60 + $min->i
				,"rsvRouteId" => $route_id
				,"setmenuId" => ""
				,"menuCategoryCdList" => ""
				,"menuIdList" => ""
				,"menuSnoList" => ""
				,"netCouponId" => ""
				,"extCouponCategoryCdList" => ""
				,"extCouponIdList" => ""
				,"extCouponSnoList" => ""
				,"nmSeiKana" => $SeiKana->nodeValue
				,"nmMeiKana" => $MeiKana->nodeValue
				,"nmSei" => ""
				,"nmMei" => ""
				,"tel" => ""
				,"tel2" => ""
				,"customerNo" => ""
				,"rsvEtc" => $set_datas['rsv_remark']."(更新）"
				,"operationMemo" => ""
				,"SUBMIT_STORE_ID" => $this->link_config['STORE_ID']
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		if (SALON_FOR_LINK_LOG ) error_log($set_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		sleep(3);

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);


		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Post update reservation ".basename(__FILE__).':'.__LINE__),3);
		}

	}

	private function _cancelReservation($link_cd, $set_datas) {
		if ( !isset($link_cd) ) {
			return false;
		}
		$now = date('YmdHis');
		//詳細画面
		$url = self::URL_GET_CANCEL;
		$url .= "?reserveId=".$link_cd;

		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Get Reservation List before cancel detail ".basename(__FILE__).':'.__LINE__),3);
		}

		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);

		$domDocument = new DOMDocument();
		$domDocument->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'utf-8'));

		$xpath = new DOMXPath($domDocument);

		$forms = $domDocument->getElementById("extReserveDetail");

		$apache_token = $this->_getNameFromInputTag(
				'.//input[@name="org.apache.struts.taglib.html.TOKEN"]'
				, $xpath
				, $forms );

		$store_id_tabcheck = $this->_getNameFromInputTag(
				'.//input[@name="storeIdForMultipleTabCheck"]'
				, $xpath
				, $forms );

		$customer_id = $this->_getNameFromInputTag(
				'.//input[@name="customerId"]'
				, $xpath
				, $forms );

		$url = self::URL_POST_CANCEL;

		$set_cookies = $this->login_cookies;
		//referer is the same to request
		$set_header = array(
				"Referer" => self::URL_GET_CANCEL."?reserveId=".$link_cd
				,'user-agent'  => $this->agent
		);
		$set_body = array(
				"org.apache.struts.taglib.html.TOKEN" => $apache_token
				,"storeIdForMultipleTabCheck" => $store_id_tabcheck
				,"customerId" => $customer_id
				,"acceptFlg" => 0
				,"disposeFlg" => 0
				,"transferFlg" => 0
				,"isEditRsvEtc" => 0
				,"operationMemo" => ""
				,"isEditOperationMemo" => 0
				,"rsvEtc" => $set_datas['rsv_remark']."(キャンセル）"
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		if (SALON_FOR_LINK_LOG ) error_log($set_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		sleep(3);

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);

		if (SALON_FOR_LINK_LOG ) error_log($response_body, 3, SALON_UPLOAD_DIR.__FUNCTION__."_".__LINE__."_".date('YmdHi').'.txt');

		if ($this->_checkResponsePage($response_body) === false ) {
			throw new Exception( Salon_Component::getMsg('E931',"Post cancel reservation ".basename(__FILE__).':'.__LINE__),3);
		}

	}


	private function _getReservation() {
		$now = date('YmdHis');
		//検索一覧画面
		$url = "https://salonboard.com/CLP/bt/reserve/reserveList/init";

		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);


		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);
		$xml = $domDocument->saveXML();

		$xmlObject = simplexml_load_string($xml);
		$htmlarray = json_decode(json_encode($xmlObject), true);



		$STORE_ID = $htmlarray["body"]["form"]["input"][0]["@attributes"]["value"];
		$KMAGIC = $htmlarray["body"]["form"]["input"][1]["@attributes"]["value"];

		$div0 = $htmlarray["body"]["div"][2]["div"]["form"]["div"][0];
		$apache = $div0["input"]["@attributes"]["value"];

		$storeIdFor = $htmlarray["body"]["div"][2]["div"]["form"]["input"][0]["@attributes"]["value"];



		$url = "https://salonboard.com/CLP/bt/reserve/reserveList/search";

		$set_cookies = $response_cookies;
		$set_header = array(
				"Referer" => "https://salonboard.com/CLP/bt/reserve/reserveList/init"
		);
		$set_body = array(
				"org.apache.struts.taglib.html.TOKEN" => $apache
				,"storeIdForMultipleTabCheck" => $htmlarray["body"]["div"][2]["div"]["form"]["input"][0]["@attributes"]["value"]
				,"searchAreaDisplay" => 0
				,"rsvDateFrom" => substr($this->today,0,8)
				,"rsvDateTo" => "20170907"
				,"dispDateFrom" => "2017年8月15日（火）"
				,"dispDateTo" => "2017年9月7日（木）"
				,"temporaryReserve" => "on"
				,"waitingForReceptionist" => "on"
				,"operation" => "on"
				,"waitingForAccounts" => "on"
				,"rsvCustomerNameKana" => ""
				,"reserveId" => ""
				,"stylistId" => ""
				,"reservationCategoryId" => ""
				,"downloadAuthRequired" => 0
				,"csvPassword" => ""
				,"SUBMIT_STORE_ID" => $STORE_ID
		);

		$response_cookies = null;
		$response_body = null;
		$response_header = null;
		$response_httpResponse = null;

		$this->httpPost($url, $set_cookies, $set_header, $set_body
				, $response_cookies, $response_body
				, $response_header, $response_httpResponse);


		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);
		$domDocument = new DOMDocument();
		$domDocument->loadHTML($body);
		$xml = $domDocument->saveXML();

		$xmlObject = simplexml_load_string($xml);
		$htmlarray = json_decode(json_encode($xmlObject), true);

		$table = $htmlarray["body"]["div"][2]["div"]["form"][2]["table"]["tbody"];

		$reserve_ids = array();


		foreach ($table["tr"] as $k1 => $d1) {
			echo "</br>";
			var_export($d1["td"][2]["p"][1]);
			$reserve_ids[] = $d1["td"][2]["p"][1];
		}

		var_export($reserve_ids);

	}

	private function _getReservationDetail($reservation_id) {
		if ( !isset($reservation_id) ) {
			return false;
		}


		$now = date('YmdHis');
		//詳細画面
		//		$url = "https://salonboard.com/CLP/bt/reserve/ext/extReserveDetail/";
		$url = "https://salonboard.com/CLP/bt/reserve/ext/extReserveChange/";
		$url .= "?reserveId=".$reservation_id;

		$set_cookies = $this->login_cookies;
		$response_cookies = null;
		$response_body = null;

		$this->httpGet($url, $set_cookies
				, $response_cookies, $response_body);


		$start = strpos($response_body,'<body');
		$body = substr($response_body,$start);


		$domDocument = new DOMDocument();
		// 		@$domDocument->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8')
		// 		, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$domDocument->loadHTML($body);

		$reservation_id = $domDocument->getElementById("extReserveId");
		$from_ymd = $domDocument->getElementById("rsvDate");
		$from_hhmm = $domDocument->getElementById("rsvTime");
		$term_hh = $domDocument->getElementById("rsvTermId");

		$get_yoyaku['reservation_id'] = $reservation_id->firstChild->textContent;
		// 		$get_yoyaku['staff'] = $yoyaku_information['table'][1]['tbody']['tr'][0]['td']['div']['select']['option'];
		$get_yoyaku['from_ymd'] = $from_ymd->getAttribute("value");

		$get_yoyaku['from_hhmm'] = "";
		$from_hhmm_children = $from_hhmm->childNodes;
		foreach($from_hhmm_children as $child) {
			if (!empty($child->getAttribute("selected"))) {
				$get_yoyaku['from_hhmm'] = $child->getAttribute("value");
				break;
			}
		}



		$get_yoyaku['to_hhmm'] = "";

		$term_hh_children = $term_hh->childNodes;
		foreach($term_hh_children as $child) {
			if (!empty($child->getAttribute("selected"))) {
				$get_yoyaku['to_hhmm'] = $child->getAttribute("value");
				break;
			}
		}
		echo "</br>";
		var_export($get_yoyaku);
		// var_export($term_hh);

		return;

		$xml = $domDocument->saveXML();

		$xmlObject = simplexml_load_string($xml);
		$htmlarray = json_decode(json_encode($xmlObject), true);

		$yoyaku_information = $htmlarray["body"]["div"][2]["div"]["form"]["div"][3];
		$get_yoyaku['reservation_id'] = $yoyaku_information['table'][0]['tr']['td'][0]['p'];
		$get_yoyaku['staff'] = $yoyaku_information['table'][1]['tbody']['tr'][0]['td']['div']['select']['option'];
		$get_yoyaku['from_ymd'] = $yoyaku_information['table'][1]['tbody']['tr'][1]['td']['p']['input']["@attributes"]["value"];
		$get_yoyaku['from_hhmm'] = $yoyaku_information['table'][1]['tbody']['tr'][2]['td']['select']['option'];
		$get_yoyaku['to_hhmm'] = $yoyaku_information['table'][1]['tbody']['tr'][2]['td']['span'][2];

		var_export($get_yoyaku);

	}
}

$cl = new LinkFunctionHotpepper_component();



