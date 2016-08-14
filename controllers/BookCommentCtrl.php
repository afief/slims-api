<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class BookCommentCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
	}

	public function select(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$offset = 0;
		$limit = 10;

		$gets = $this->getGets();
		if (isset($gets['offset']))
			$offset = $gets['offset'];
		if (isset($gets['limit']))
			$limit = $gets['limit'];

		$select = $this->db->select('comment',
			['[>]member' => ['member_id' => 'member_id']],
			['comment.comment_id', 'comment.comment', 'comment.input_date', 'member.member_id', 'member.member_name', 'member.member_image'],
			['biblio_id' => $biblio_id, 'ORDER' => 'comment.input_date DESC', 'LIMIT' => [$offset, $limit]]);

		if (is_array($select)) {
			$this->setTrue();
			$this->setData($select);
		}

		return $this->result;
	}

	public function create(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];
		$posts = $this->getPosts(['text']);

		if ($posts) {
			//check duplicate
			$check = $this->db->get('comment', 'comment_id', ['AND' => ['member_id' => $this->user->id, 'comment' => $posts['text'], 'biblio_id' => $biblio_id]]);
			if ($check) {
				$this->error('duplicate');
			} else {
				$insert = $this->db->insert('comment', [
					'biblio_id' => $biblio_id,
					'member_id' => $this->user->id,
					'comment'	=> $posts['text'],
					'input_date'=> date('Y-m-d H:i:s')
				]);
				if ($insert) {
					$user = $this->user->getCurrentUser();

					$this->setTrue();
					$this->setData([
						'comment_id' => $insert,
						'comment'	 => $posts['text'],
						'input_date' => date('Y-m-d H:i:s'),
						'member_id'	 => $this->user->id,
						'member_name'=> $user['member_name'],
						'member_image' => $user['member_image']
					]);
				} else {
					$this->error('failed');
				}
			}
		} else {
			$this->error("Data tidak lengkap");
		}

		return $this->result;
	}
}

?>