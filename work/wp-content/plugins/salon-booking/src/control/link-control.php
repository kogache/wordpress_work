<?php

	require_once(SL_PLUGIN_SRC_DIR . 'control/salon-control.php');
	require_once(SL_PLUGIN_SRC_DIR . 'data/link-data.php');
	require_once(SL_PLUGIN_SRC_DIR . 'comp/link-component.php');

class Link_Control extends Salon_Control  {

	private $pages = null;
	private $datas = null;
	private $comp = null;

	private $action_class = '';
	private $permits = null;



	function __construct() {
		parent::__construct();
		if (empty($_REQUEST['menu_func']) ) {
			$this->action_class = 'Link_Page';
			$this->set_response_type(Response_Type::HTML);
		}
		else {
			$this->action_class = $_REQUEST['menu_func'];
		}
		$this->datas = new Link_Data();
		$this->comp = new Link_Component($this->datas);
		$this->set_config($this->datas->getConfigData());
		$this->permits = array('Link_Page','Link_Edit');

	}



	public function do_action() {
		$this->do_require($this->action_class ,'page',$this->permits);
		$this->pages = new $this->action_class($this->is_multi_branch,$this->is_use_session);
		$this->pages->set_config_datas($this->datas->getConfigData());

		if ($this->action_class == 'Link_Page' ) {
			$this->pages->set_link_information($this->comp->getLinkInformation());
			$this->pages->set_link_datas($this->datas->getLinkDatas());
			$user_login = $this->datas->getUserLogin();
			$this->pages->set_messages_of_check($this->comp->checkEnviroment(Salon_Service::HOTPEPPER,$this->datas->getBracnCdbyCurrentUser($user_login)));



		}
		elseif ($this->action_class == 'Link_Edit' ) {
			$this->pages->check_request();
			if ($_POST['type'] == 'run' ) {
				$this->comp->runLink();
			}
			elseif ($_POST['type'] == 'stop' ) {
				$this->comp->stopLink();
			}

		}

		$this->pages->show_page();
		if ($this->action_class != 'Link_Page') wp_die();
	}
}		//class


