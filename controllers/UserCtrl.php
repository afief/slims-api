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
			'mst_language.language_name', 'biblio.image', 'biblio.promoted',
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
			$ext = 'jpg';//pathinfo($file->file, PATHINFO_EXTENSION);

			$filename = $key . '.' . $ext;

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
}

?>