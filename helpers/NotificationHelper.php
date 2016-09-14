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

	private function sendGCM($member_id, $title, $message, $path = '') {
		$postData = [
			'registration_ids' => [],
			'data'=> [
				'message' => '',
				'title' => ''
			]
		];

		$reg_id = $this->db->select('member_reg_id', 'reg_id', ['AND' => ['member_id' => $member_id, 'status' => 1]]);
		if ($reg_id) {

			$postData['registration_ids'] = $reg_id;
			$postData['data']['title'] = $title;
			$postData['data']['message'] = $message;
			if ($path) {
				$postData['data']['path'] = $path;
			}

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://gcm-http.googleapis.com/gcm/send",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($postData),
				CURLOPT_HTTPHEADER => array(
					"authorization: key=AIzaSyDwXasEsaWbEtO_0ySlt2wEkcEKBAvNuMY",
					"content-type: application/json"
					),
				));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			return $response;

		}
		return '';
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

			if (strpos($text, 'mengirim pesan') !== false) {
				$memberName = $this->db->get('member', 'member_name', ['member_id' => $from_id]);
				if (!$memberName) {
					$memberName = "Pesan Baru";
				} else {
					$memberName = $memberName . " mengirim pesan";
				}
				$messageText = $this->db->get('member_message', 'text', ['id' => $param]);
				if (!$messageText) {
					$messageText = "Klik untuk membaca pesan baru";
				}

				$this->sendGCM($to_id, $memberName, $messageText, '/app/pesan');
			}
			return true;
		}
		return false;
	}

	public function read($notifId) {
		$this->db->update('member_notification', ['is_read' => 1], ['id' => $notifId]);
		return true;
	}

	public function fetch($user_id, $offset = 0, $limit = 10, $prev_id = null) {
		$result = [
			'data'	=> [],
			'next'	=> null,
			'unread' => 0
		];

		if ($prev_id) {
			$result['data'] = $this->db->select('member_notification(mn)',
				['[>]member(m)' => ['mn.from_id' => 'member_id']],
				['mn.id', 'mn.from_id', 'm.member_name(from_name)', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](from_image)',
				'mn.text', 'mn.param', 'mn.is_read', 'mn.timestamp'],
				['AND' => ['mn.to_id' => $user_id, 'mn.id[>]' => $prev_id], 'ORDER' => 'mn.timestamp DESC', 'LIMIT' => [0, $limit]]
			);
		} else {
			$result['data'] = $this->db->select('member_notification(mn)',
				['[>]member(m)' => ['mn.from_id' => 'member_id']],
				['mn.id', 'mn.from_id', 'm.member_name(from_name)', 'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](from_image)',
				'mn.text', 'mn.param', 'mn.is_read', 'mn.timestamp'],
				['AND' => ['mn.to_id' => $user_id, 'mn.id[>]' => $prev_id], 'ORDER' => 'mn.timestamp DESC', 'LIMIT' => [$offset, $limit]]
			);
		}

		if (count($result['data'])) {
			if ($this->db->count('member_notification', ['AND' => ['id[>]' => $result['data'][0]['id'], 'to_id' => $user_id ]]) > 0) {
				$result['next'] = $result['data'][0]['id'];
			}

			$unreadCount = 0;
			foreach ($result['data'] as $rs) {
				if (!$rs['is_read']) {
					$unreadCount++;
				}
			}
			$result['unread'] = $unreadCount;
		}

		return $result;
	}

}