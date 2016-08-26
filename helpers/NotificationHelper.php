<?php

namespace helpers;

use \Firebase\JWT\JWT;

class NotificationHelper {
	private $db;
	private $ci;
	public function __construct($ci) {
		$this->ci = $ci;
		$this->db = $ci->db;
	}

	public function send($from_id, $to_id, $text, $param) {
		$insert = $this->db->insert('member_notification', [
			'to_id'		=> $to_id,
			'from_id'	=> $from_id,
			'text'		=> $text,
			'param'		=> $param,
			'is_read'	=> 0
		]);

		if ($insert) {
			return true;
		}
		return false;
	}

	public function read($notifId) {
		$this->db->update('member_notification', ['is_read' => 0], ['id' => $notifId]);
		return true;
	}

	public function fetch($user_id, $offset = 0, $limit = 10, $prev_id = null) {
		$result = [
			'data'	=> [],
			'next'	=> null
		];

		if ($prev_id) {
			$result['data'] = $this->db->select('member_notification',
				['id', 'from_id', 'text', 'param', 'is_read'],
				['AND' => ['to_id' => $user_id, 'id[>]' => $prev_id], 'ORDER' => 'timestamp DESC', 'LIMIT' => [0, $limit]]
			);
		} else {
			$result['data'] = $this->db->select('member_notification',
				['id', 'from_id', 'text', 'param', 'is_read'],
				['AND' => ['to_id' => $user_id, 'id[>]' => $prev_id], 'ORDER' => 'timestamp DESC', 'LIMIT' => [$offset, $limit]]
			);
		}
		if (count($result)) {
			if ($this->db->count('member_notification', ['AND' => ['id[>]' => $result[0]['id'], 'to_id' => $user_id ]]) > 0) {
				$result['next'] = $result[0]['id'];
			}
		}

		return $result;
	}

}