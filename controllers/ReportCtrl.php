<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class ReportCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
   	}

   	public function send(Request $req, Response $res, $args) {
   		$posts = $this->getPosts(['text']);

   		if ($posts) {
   			$insert = $this->db->insert('member_report', ['member_id' => $this->user->id, 'text' => $posts['text']]);
   			if ($insert) {

   				try {
   					set_error_handler(function() {});
	   				$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	   				$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

					// Additional headers
					$headers .= 'From: PERPUS <noreply@' . $host . '>' . "\r\n";
	   				mail('ramacyber@gmail.com', 'Perpus Report', $this->user->id . ", " . $posts['text'],$headers);
	   				restore_error_handler();
	   			} catch(\Exception $e) {

	   			}

   				$this->setTrue();
   			}
   		}

   		$this->setTrue();
   		return $this->result;
   	}
}

?>