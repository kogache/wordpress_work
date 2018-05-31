<?php

	require_once(SL_PLUGIN_SRC_DIR . 'data/salon-data.php');


class Link_Data extends Salon_Data {

	const TABLE_NAME = 'salon_link';

	function __construct() {
		parent::__construct();
	}


	public function update ($table_data){
		$this->setConfigData($table_data);
		return true;

	}

	public function insertTable ($table_data){
// do not insert here, because link data is inserted by booking
// 		try {
// 			$sts = $this->insertSql(self::TABLE_NAME, $table_data,'%d,%s,%d,%d,%s,%s,%s,%s');
// 		}
// 		catch (Exception $e) {
// 			error_log("\n".'salon booking Link Db error '.date_i18n('Y-m-d H:i:s')."\n".$e->getMessage()
// 				, 3
// 				, SALON_UPLOAD_DIR.date('Y').'_Link.txt');
// 			return false;
// 		}
// 		return true;
		try {
			$set_string = 	' link_status = %d , '
					.' link_cd = %s , '
					.' information = %s , '
					.' update_time = %s ';

			$set_data_temp = array(
					$table_data['link_status'],
					$table_data['link_cd'],
					$table_data['information'],
					date_i18n('Y-m-d H:i:s')	);

			$set_data_temp[] = $table_data['reservation_cd'];
			$where_string = ' reservation_cd = %d ';
			$sts = $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp);
		}
		catch (Exception $e) {
			error_log("\n".'salon booking Link Db error '.date_i18n('Y-m-d H:i:s')."\n".$e->getMessage()
					, 3
					, SALON_UPLOAD_DIR.date('Y').'_Link.txt');
			return false;
		}
		return true;

	}

	public function updateTable ($table_data){

		try {
			$set_string = 	' link_status = %d , '.
							' information = %s , '.
							' update_time = %s ';

			$set_data_temp = array(
							$table_data['link_status'],
							$table_data['information'],
							date_i18n('Y-m-d H:i:s')	);

			$set_data_temp[] = $table_data['reservation_cd'];
			$where_string = ' reservation_cd = %d ';
			$sts = $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp);
		}
		catch (Exception $e) {
			error_log("\n".'salon booking Link Db error '.date_i18n('Y-m-d H:i:s')."\n".$e->getMessage()
				, 3
				, SALON_UPLOAD_DIR.date('Y').'_Link.txt');
			return false;
		}
		return true;
	}

	public function deleteTable ($table_data){

		try {
			$set_string = 	' link_status = %d , '.
							' information = %s , '.
							' delete_flg = %d , '.
							' update_time = %s ';

			$set_data_temp = array(
							Salon_Link_Status::SALON_AFTER_CANCEL,
							$table_data['information'],
							Salon_Reservation_Status::DELETED,
							date_i18n('Y-m-d H:i:s')	);

			$set_data_temp[] = $table_data['reservation_cd'];
			$where_string = ' reservation_cd = %d ';
			$sts = $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp);
		}
		catch (Exception $e) {
			error_log("\n".'salon booking Link Db error '.date_i18n('Y-m-d H:i:s')."\n".$e->getMessage()
				, 3
				, SALON_UPLOAD_DIR.date('Y').'_Link.txt');
			return false;
		}
		return true;
	}

	public function getLinkCd ($reservation_cd ) {
		global $wpdb;

		$sql = 	$wpdb->prepare(
				' SELECT '.
				' link_cd , link_status'.
				' FROM '.$wpdb->prefix.'salon_link '.
				'   WHERE reservation_cd = %d ',
				$reservation_cd
		);
		if ($wpdb->query($sql) === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		else {
			$result = $wpdb->get_results($sql,ARRAY_A);
		}
		// 		if (! isset($result[0]['link_cd']) || $result[0]['link_status'] == Salon_Link_Status::SALON_LINK_ERROR) {
		if (! isset($result[0]['link_cd']) ) {
			return false;
		}
		return $result[0]['link_cd'];

	}

	public function getLinkDatas ( ) {
		global $wpdb;

		$sql = 	' SELECT '.
				' * '.
				' FROM '.$wpdb->prefix.'salon_link '.
				' ORDER BY update_time desc ';
		if ($wpdb->query($sql) === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		else {
			$result = $wpdb->get_results($sql,ARRAY_A);
		}
		// 		if (! isset($result[0]['link_cd']) || $result[0]['link_status'] == Salon_Link_Status::SALON_LINK_ERROR) {
		if (! isset($result[0]['link_cd']) ) {
			return false;
		}
		return $result;

	}

}