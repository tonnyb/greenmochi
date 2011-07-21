<?php

class Curl {
	public static function sendRequest($url,$type = 'GET',$data = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ( isset($data['gzip']) && $data['gzip'] ) {
			curl_setopt($ch, CURLOPT_ENCODING, 1);
		}
		if ( isset($data['apiUser']) && isset($data['apiPass']) ) {
			curl_setopt($ch, CURLOPT_USERPWD, $data['apiUser'].':'.$data['apiPass']);
			curl_setopt($ch, CURLOPT_HEADER, false);
			unset($data['apiUser']);
			unset($data['apiPass']);
		}
		if ($type == "POST") {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}

		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
	}
}
