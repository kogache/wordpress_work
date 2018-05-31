<?php

class Link_Component {

	private $version = '1.0';

	private $datas = null;

	public function __construct(&$datas) {
		$this->datas = $datas;
	}


	public function editTableData () {
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT'] = stripslashes($_POST['config_mail_text']);
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT_USER'] = stripslashes($_POST['config_mail_text_user']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT'] = stripslashes($_POST['config_mail_subject']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT_USER'] = stripslashes($_POST['config_mail_subject_user']);
		$edit_from = stripslashes($_POST['config_mail_from']);
		if (strpos($edit_from,"<") !== false ) {
			$edit_from_array = explode('<',$edit_from);
			$edit_from = trim($edit_from_array[0])." <".trim($edit_from_array[1]);
		}
		$set_data['SALON_CONFIG_SEND_MAIL_FROM'] = $edit_from;
		$set_data['SALON_CONFIG_SEND_MAIL_RETURN_PATH'] = stripslashes($_POST['config_mail_returnPath']);
		//[2014/11/01]Ver1.5.1
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT_INFORMATION'] = stripslashes($_POST['config_mail_text_information']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT_INFORMATION'] = stripslashes($_POST['config_mail_subject_information']);
		$set_data['SALON_CONFIG_SEND_MAIL_BCC'] = stripslashes($_POST['config_mail_bcc']);
		//Ver1.6.1
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT_COMPLETED'] = stripslashes($_POST['config_mail_text_completed']);
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT_ACCEPTED'] = stripslashes($_POST['config_mail_text_accepted']);
		$set_data['SALON_CONFIG_SEND_MAIL_TEXT_CANCELED'] = stripslashes($_POST['config_mail_text_canceled']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT_COMPLETED'] = stripslashes($_POST['config_mail_subject_completed']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT_ACCEPTED'] = stripslashes($_POST['config_mail_subject_accepted']);
		$set_data['SALON_CONFIG_SEND_MAIL_SUBJECT_CANCELED'] = stripslashes($_POST['config_mail_subject_canceled']);

		return $set_data;

	}

	public function runLink() {
		wp_schedule_event(time(), 'hourly', 'sl_update_reservation_hotpepper');
	}

	public function checkEnviroment($service_cd, $branch_cd) {
		$isOk = true;
		$messages = array();
		//店の予約時間は３０分単位
		if (!$this->checkBranch($service_cd, $branch_cd, $messages)) {
			$isOk = false;
		}
		//imapのクラスが必要
		if (!$this->checkConfig($service_cd,$messages)) {
			$isOk = false;
		}
		if (!$isOk) {
			return $messages;
		}
		return $isOk;

	}

	public function checkBranch($service_cd, $branch_cd, &$messages) {
		$isOk = true;
		$branch_datas = $this->datas->getBranchData($branch_cd, 'open_time,close_time,time_step');
		if ($branch_datas !== false) {
			switch ($service_cd) {
			case Salon_Service::HOTPEPPER:
				//hotpepperは30分単位
				if ($branch_datas['time_step'] % 30 != 0) {
					$messages[] = "店情報の「"
							.__('Unit of Time (minutes)',SL_DOMAIN)
							."」は30分で割り切れる時間にしてください。";
					$isOk = false;
				}
				if (+substr($branch_datas['open_time'], -2) % 30 != 0) {
					$messages[] = "店情報の「"
							.__('Open Time',SL_DOMAIN)
							."」はXX:00またはXX:30にしてください。";
					$isOk = false;
				}
				if (+substr($branch_datas['close_time'], -2) % 30 != 0) {
					$messages[] = "店情報の「"
							.__('Close Time',SL_DOMAIN)
							."」はXX:00またはXX:30にしてください。";
					$isOk = false;
				}
				break;
			}
		}
		return $isOk;
	}
	public function checkConfig($service_cd, &$messages) {
		global $wp_filter;
		$isOk = true;

		if (!is_array($messages)) {
			$messages = array();
		}
		if ( !function_exists('imap_open') ) {
			$messages[] = "IMAPが使用できません。";
			$isOk = false;
		}
		switch($service_cd) {
			case Salon_Service::HOTPEPPER:
				if (!isset($wp_filter['salon_set_config_for_hotpepper'])) {
					$messages[] = "必要とするFILTERが定義されていません。";
					$isOk = false;
				}
				break;
		}
		return $isOk;
	}

	public function getLinkInformation() {
		return wp_next_scheduled( 'sl_update_reservation_hotpepper' );
	}

	public function stopLink() {
		$start_time = wp_next_scheduled( 'sl_update_reservation_hotpepper' );
		$original_args = array();

		wp_unschedule_event( $start_time, 'sl_update_reservation_hotpepper', $original_args );
	}


}