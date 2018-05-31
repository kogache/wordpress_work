<?php

	require_once(SL_PLUGIN_SRC_DIR . 'page/salon-page.php');


class Checkconfig_Page extends Salon_Page {


	private $datas = null;
	private $is_collation_different = false;
	private $collation_datas = null;



	public function __construct($is_multi_branch,$use_session) {
		parent::__construct($is_multi_branch,$use_session);
	}

	public function setDatas($key,$data) {
		$this->datas[$key] = $data;
		if ($key == "COLLATION") {
			$this->collation_datas = $data['collation'];
			if ($data['collation']['users'] != $data['collation']['staff']
				|| $data['collation']['users'] != $data['collation']['customer'])
			$this->is_collation_different = true;

		}
	}

	private function setShowData($datas) {
		if (is_array($datas)) {
			foreach($datas as $k1 => $d1) {
				if (is_numeric($k1)) {
					//テーブル項目のようなキーと値って前提で編集
					$details = "";
					$comma = "";
					if (is_array($d1)) {
						foreach($d1 as $k2 => $d2 ) {
							$details .= $k2.":".$d2.$comma;
							$comma=",";
						}
					}
					else {
						$details = $d1;
					}
					echo "<li>".$details."</li>";
				}
				else {
					echo "<li>".$k1."</li>";
					echo "<ul>";
					$this->setShowData($d1);
					echo "</ul>";
				}
			}
		}
		else {
			echo "<li>".$datas."</li>";
		}
	}
	public function show_page() {

		wp_enqueue_style('salon', SL_PLUGIN_URL.'/css/salon.css');
?>
	<script type="text/javascript" charset="utf-8">
		var $j = jQuery;
		var ajaxOn = false;
		$j(document).ready(function() {
			$j("#sl_button_update_collation").click(function() {

				if (ajaxOn) return;
				ajaxOn = true;
				$j.ajax({
					 	type: "post",
						url:  "<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php?action=slcheckconfig",
						dataType : "json",
						data: {
							"menu_func":"Checkconfig_Edit"
							,"nonce":"<?php echo $this->nonce; ?>"
						},
						success: function(data) {
							ajaxOn = false;
							if (data.status == "Error" ) {
								alert(data.message);
								return false;
							}
							else {
								location.reload();
							}
				        },
						error:  function(XMLHttpRequest, textStatus){
							ajaxOn = false;
							alert (textStatus);
							return false;
						}
				});
			});
		});
	</script>

	<div id="salon_detail">
		<ol >
<?php
		$this->setShowData($this->datas);
?>
		</ol>
<?php
		if ($this->is_collation_different ){
			echo <<<EOF
			<input type="button" value="UPDATE COLLATION" id="sl_button_update_collation" class="sl_button"  />
EOF;
		}
?>

		</div>
<?php
	}	//show_page
}		//class

