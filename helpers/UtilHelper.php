<?php

namespace helpers;

class UtilHelper {

	private $ci;
	public function __construct($ci) {
		$this->ci = $ci;
	}

	public function generateRandomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public function getBrowser() {
		$request = $this->ci->get('request');
		$brow = $request->getHeader('User-Agent');
		if (is_array($brow) && count($brow))
			return $brow[0];
		else
			return $brow;
	}

	public function determineClientIpAddress()
    {
    	$request = $this->ci->get('request');

        $ipAddress = '';
        $serverParams = $request->getServerParams();

        if (isset($serverParams['REMOTE_ADDR']) && $this->isValidIpAddress($serverParams['REMOTE_ADDR'])) {
            $ipAddress = $serverParams['REMOTE_ADDR'];
        }
        return $ipAddress;
    }
    protected function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }
        return true;
    }
}