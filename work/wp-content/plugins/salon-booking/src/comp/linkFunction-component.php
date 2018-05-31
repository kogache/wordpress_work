<?php


abstract class LinkFunction_component  {

	protected $agent = "";

	function __construct() {
	}

	abstract public function update_service_from_salon($service_cd, $agent, $set_datas);
	abstract public function update_reservation_from_service();

	public function set_agent($agent) {
		$this->agent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36";
		if (! empty($agent)) {
			$this->agent = $agent;
		}
	}


	public function httpGet($url,$set_cookies
			,&$response_cookies,&$response_body) {

		$args = array(
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent'  => $this->agent,
				'blocking'    => true,
				'headers'     => array(),
				'cookies'     => $set_cookies,
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
				'filename'    => null
		);

		$response = wp_remote_get( $url , $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			throw new Exception( Salon_Component::getMsg('E931',$error_message." ".basename(__FILE__).':'.__LINE__),3);
		}
		else {
			if ( is_array( $response ) ) {
				$header = $response['headers'];
				$response_body = $response['body'];
				$httpResponse = $response['response'];
				$response_cookies =  $response['cookies'];
			}
			else {
				throw new Exception( Salon_Component::getMsg('E931',serialize($response)." ".basename(__FILE__).':'.__LINE__),3);
			}
		}
	}

	public function httpPost($url,$set_cookies,$set_header, $set_body
			,&$response_cookies,&$response_body
			,&$response_header, &$response_httpResponse	) {

		$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => $set_header,
				'body' => $set_body,
				'cookies' => $set_cookies

		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			throw new Exception( Salon_Component::getMsg('E931',$error_message." ".basename(__FILE__).':'.__LINE__),3);
		}
		else {
			if ( is_array( $response ) ) {
				$reponse_header = $response['headers'];
				$response_body = $response['body'];
				$response_httpResponse = $response['response'];
				$response_cookies =  $response['cookies'];
			}
			else {
				throw new Exception( Salon_Component::getMsg('E931',serialize($response)." ".basename(__FILE__).':'.__LINE__),3);
			}
		}
	}

	public function checkGetElement($element) {
		if (empty($element)) {
			return false;
		}
		return true;
	}

}

