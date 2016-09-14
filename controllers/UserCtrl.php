<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class UserCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
	}

	public function getUser(Request $req, Response $res) {

		$user = $this->getUserById($this->user->id);
		if ($user) {
			$user['setting'] = $this->user->setting;

			$this->setTrue();
			$this->setData($user);
		}

		return $this->result;
	}

	public function getOtherUser(Request $req, Response $res, $args) {	
		$member_id = $args['member_id'];
		$user = $this->getUserById($member_id);
		if ($user) {
			$this->setTrue();
			$this->setData($user);
		}

		return $this->result;	
	}

	private function getUserById($memberId) {
		$user = $this->db->get('member',
			[
			'member_id',
			'member_name',
			'CONCAT[\'' . BASE_URL . 'images/persons/\', member_image](member_image)',
			'member_email',
			'member_address', 
			'gender',
			'birth_date',
			'member_phone'
			], ['member_id' => $memberId]);

		return $user;
	}

	public function updateUser(Request $req, Response $res, $args) {
		$posts = $this->getPosts();
		if ($posts) {
			$diss = [];
			if ($this->cleanInput($posts, ['member_name', 'member_phone', 'member_address'], $diss)) {
				$update = $this->db->update('member', $posts, ['member_id' => $this->user->id]);
				$this->setTrue();
			} else {
				if (count($diss)) {
					$this->error(implode(', ', $diss) . ' tidak diperbolehkan');
				} else {
					$this->error('Kesalahan sistem');
				}
			}
		} else {
			$this->error("Data tidak lengkap");
		}
		return $this->result;
	}

	public function getBookHistory(Request $req, Response $res, $args) {
		$select = $this->db->select('loan',
			[
			'[>]item' => ['item_code' => 'item_code'],
			'[>]biblio' => ['item.biblio_id' => 'biblio_id'],
			'[>]mst_gmd' => ['biblio.gmd_id' => 'gmd_id'],
			'[>]mst_publisher' => ['biblio.publisher_id' => 'publisher_id'],
			'[>]mst_language' => ['biblio.language_id' => 'language_id']
			],
			[
			'loan.loan_date', 'loan.due_date', 'loan.is_lent', 'loan.is_return', 'loan.return_date', 'loan.renewed',
			'biblio.biblio_id', 'biblio.title', 
			'biblio.publish_year', 
			'mst_language.language_name',  'CONCAT[\'' . BOOK_URL . '\', biblio.image](image)', 'biblio.promoted',
			'mst_gmd.gmd_name',
			'mst_publisher.publisher_name'
			],
			[
			'loan.member_id' => $this->user->id,
			'ORDER' => 'loan.is_return ASC'
			]);

		if ($select) {
			$this->setTrue();
			$this->setData($select);
		}

		return $this->result;
	}

	public function updateAvatar(Request $req, Response $res, $args) {
		$files = $req->getUploadedFiles();

		if (!empty($files['file'])) {

			$file = $files['file'];

			$key = 'member_' . $this->user->id . '_' . time();
			$ext = strtolower(strrchr($file->getClientFilename(), '.'));

			$filename = $key . $ext;

			if (move_uploaded_file($file->file, AVATAR_DIR . $filename)) {
				$resCrop = $this->ci->util->cropImage( AVATAR_DIR . $filename, 400 );
				if ($resCrop) {
					$resDB = $this->db->update("member",
						["member_image"	=> $filename],
						["member_id" => $this->user->id]
					);

					if ($resDB) {
						$this->setTrue();
						$this->setData(AVATAR_URL . $filename);
					} else {
						$this->error('Gagal update database');
					}
				} else {
					$this->error('Gagal mengatur ukuran gambar');
				}
			} else {
				$this->error('Gagal upload file');
			}
		} else {
			$this->error('File tidak ditemukan');
		}

		return $this->result;
	}

	public function updateRegId(Request $req, Response $res, $args) {
		$posts = $this->getPosts(['reg_id']);

		if ($posts) {
			$getPrevMember = $this->db->get('member_reg_id', 'reg_id', ['AND' => ['member_id' => $this->user->id, 'status' => 1]]);
			if ($getPrevMember && ($getPrevMember == $posts['reg_id'])) {
				$this->setTrue();
				$this->setData('same as the old');
			} else if ($posts['reg_id'] && (strlen($posts['reg_id']) > 5)) {
				/* update all old user reg id */
				$this->db->update('member_reg_id', ['status' => 0], ['member_id' => $this->user->id]);

				$checkPast = $this->db->get('member_reg_id', 'id', ['AND' => ['member_id' => $this->user->id, 'reg_id' => $posts['reg_id']]]);
				if ($checkPast) {
					$this->db->update('member_reg_id', ['status' => 1], ['id' => $checkPast]);
					$this->setTrue();
					$this->setData('update registration');
				} else {
					$insert = $this->db->insert('member_reg_id', [
						'member_id'		=> $this->user->id,
						'reg_id'		=> $posts['reg_id'],
						'status'		=> 1
					]);
					if ($insert) {
						$this->setTrue();
						$this->setData('new registration');
					} else {
						$this->error('Tidak dapat menyimpan informasi device ke server');
					}
				}
			} else {
				$this->error('Tidak dapat menyimpan informasi device ke server. Data registrasi terlalu pendek');
			}
		}

		return $this->result;
	}

	public function updateUserSetting(Request $req, Response $res, $args) {
		$posts = $this->getPosts();

		$notif_email = isset($posts['notif_email']) ? intval($posts['notif_email']) : '1';
		$notif_app = isset($posts['notif_app']) ? intval($posts['notif_app']) : '1';

		$update = $this->db->update('member_settings', ['notif_email' => $notif_email, 'notif_app' => $notif_app], ['member_id' => $this->user->id]);

		$this->setTrue();

		return $this->result;
	}
}

?>