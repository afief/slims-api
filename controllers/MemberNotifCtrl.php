<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class MemberNotifCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);

	}

	public function select(Request $req, Response $res, $args) {
		$gets = $this->getGets();

		$offset = isset($gets['offset']) ? $gets['offset'] : 0;
		$limit	= isset($gets['limit']) ? $gets['limit'] : 10;
		$prevId = isset($gets['prev_id']) ? $gets['prev_id'] : null;

		$notif = $this->notif->fetch($this->user->id, $offset, $limit, $prevId);

		if ($notif) {
			$this->setTrue();
			$this->setData($notif);
		}

		return $this->result;
	}

	public function read(Request $req, Response $res, $args) {
		$posts = $this->getPosts(['notif_id']);

		if ($posts) {
			$this->notif->read($posts['notif_id']);

			$this->setTrue();
			$this->setData($posts['notif_id']);
		}

		return $this->result;
	}

}

?>