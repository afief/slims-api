<?php

namespace controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface;

class BookCtrl extends BaseController {

	public function __construct(ContainerInterface $ci) {
		parent::__construct($ci);
	}

	public function select(Request $req, Response $res, $args) {
		$args = (count($args) > 0) ? $args : $this->getGets();

		$whereAr = [];
		$where = ['LIMIT' => [0, 10], 'ORDER' => 'biblio.promoted DESC, biblio.input_date DESC'];
		if ($args) {
			if (isset($args['offset'])) {
				$where['LIMIT'][0] = $args['offset'];
			}
			if (isset($args['limit'])) {
				$where['LIMIT'][1] = $args['limit'];
			}
			if (isset($args['promoted'])) {
				$whereAr['biblio.promoted'] = $args['promoted'];
			}
			if (isset($args['title'])) {
				$whereAr['biblio.title[~]'] = '%' . $args['title'] . '%';
			}
			if (isset($args['favorit']) && $args['favorit']) {
				if ($this->user->isLogin) {
					$favIds = $this->db->select('member_favorit', 'biblio_id', ['member_id' => $this->user->id]);
					$whereAr['biblio.biblio_id'] = $favIds;
				}
			}
			if (isset($args['topic'])) {
				$blids = $this->db->select('biblio_topic', 'biblio_id', ['topic_id' => $args['topic']]);
				if ($blids) {
					$whereAr['biblio.biblio_id'] = $blids;

					$args['topic_name'] = $this->db->get('mst_topic', 'topic', ['topic_id' => $args['topic']]);
				}
			}
		}

		if (count($whereAr) > 1) {
			$where['AND'] = $whereAr;
		} else {
			$where = array_merge($whereAr, $where);
		}


		$select = $this->db->select('biblio',
			[
				'[>]mst_gmd' => ['gmd_id' => 'gmd_id'],
				'[>]mst_publisher' => ['publisher_id' => 'publisher_id'],
				'[>]mst_language' => ['language_id' => 'language_id']
			],
			['biblio.biblio_id', 'biblio.title', 
			'biblio.publish_year', 
			'mst_language.language_name', 'CONCAT[\'' . BOOK_URL . '\', biblio.image](image)', 'biblio.promoted',
			'mst_gmd.gmd_name',
			'mst_publisher.publisher_name'
			],
			$where);

		//print_r($this->db->lastError);

		for ($i = 0; $i < count($select); $i++) {
			$select[$i]['item_count'] = $this->getBiblioItemCount($select[$i]['biblio_id']);
			$select[$i]['rate'] = $this->getBiblioRate($select[$i]['biblio_id']);

			$authors = $this->getBibioAuthors($select[$i]['biblio_id']);
			$authorTexts = [];
			foreach ($authors as $author) {
				$authorTexts[] = $author['author_name'];
			}
			$select[$i]['authors'] = implode(', ', $authorTexts);

			$topics = $this->getBibioTopics($select[$i]['biblio_id']);
			$topicTexts = [];
			foreach ($topics as $topic) {
				$topicTexts[] = $topic['topic'];
			}
			$select[$i]['topic'] = implode(', ', $topicTexts);
		}

		if (is_array($select)) {
			unset($where['LIMIT']);
			unset($where['ORDER']);
			$args['total'] = $this->db->count('biblio', $where);

			$this->setTrue();
			$this->setData(['params' => $args, 'results' => $select]);
		}

		return $this->result;
	}

	public function get(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$joins = [
		'[>]mst_gmd' => ['gmd_id' => 'gmd_id'],
		'[>]mst_publisher' => ['publisher_id' => 'publisher_id'],
		'[>]mst_place' => ['publish_place_id' => 'place_id'],
		'[>]mst_language' => ['language_id' => 'language_id']
		];

		$select = ['biblio.biblio_id',
		'biblio.title',
		'biblio.sor',
		'biblio.edition',
		'biblio.isbn_issn',
		'biblio.publish_year',
		'biblio.collation',
		'biblio.series_title',
		'biblio.call_number',
		'mst_language.language_name',
		'biblio.source',
		'biblio.classification',
		'biblio.notes',
		'CONCAT[\'' . BOOK_URL . '\', biblio.image](image)',
		'biblio.file_att',
		'biblio.opac_hide',
		'biblio.promoted',
		'biblio.input_date',
		'biblio.last_update',
		'mst_place.place_id',
		'mst_place.place_name',
		'biblio.gmd_id', 'mst_gmd.gmd_name', 'mst_gmd.icon_image(gmd_icon_image)',
		'biblio.publisher_id', 'mst_publisher.publisher_name'
		];

		$get = $this->db->get('biblio', $joins, $select, ['biblio.biblio_id' => $biblio_id]);

		if ($get) {
			$get['items'] = $this->getBiblioItems($biblio_id);
			$get['authors'] = $this->getBibioAuthors($biblio_id);
			$get['topic'] = $this->getBibioTopics($biblio_id);
			$get['rate'] = $this->getBiblioRate($biblio_id);

			if ($this->user->isLogin) {
				$get['is_fav'] = $this->db->count('member_favorit', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
				$get['own_rate'] = $this->db->get('biblio_rate', 'rate', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
				$get['is_reserve'] = $this->db->count('reserve', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
			}

			$this->setTrue();
			$this->setData($get);
		}

		return $this->result;
	}

	private function getBibioAuthors($biblio_id) {
		$select = $this->db->select('biblio_author',
			['[>]mst_author(mst)' => ['author_id' => 'author_id']],
			['mst.author_id', 'mst.author_name'],
			['biblio_author.biblio_id' => $biblio_id, 'ORDER' => 'biblio_author.level ASC']);

		return $select;
	}

	private function getBibioTopics($biblio_id) {
		$select = $this->db->select('biblio_topic',
			['[>]mst_topic(mst)' => ['topic_id' => 'topic_id']],
			['mst.topic_id', 'mst.topic'],
			['biblio_topic.biblio_id' => $biblio_id, 'ORDER' => 'biblio_topic.level ASC']);

		return $select;
	}

	private function getBiblioRate($biblio_id) {
		$manual = $this->db->manual('SELECT COALESCE(TRUNCATE(SUM(`rate`) / COUNT(`rate`), 2), 0) as `average` FROM `biblio_rate` WHERE `biblio_id` = ' . $this->db->quote($biblio_id));
		if ($manual) {
			$rate = $manual[0]['average'];
			return $rate;
		}
		return 0;
	}

	private function getBiblioItemCount($biblio_id) {
		$manual = $this->db->manual('SELECT COUNT(`item`.`item_id`) as `jumlah` FROM `item` ' .
			'LEFT JOIN `loan` ON `item`.`item_code` = `loan`.`item_code` AND `loan`.`is_return` = 0 AND `loan`.`is_lent` = 1 ' .
			'WHERE biblio_id = ' . $biblio_id . ' AND `loan`.`is_return` IS NULL');
		if ($manual) {
			return $manual[0]['jumlah'];
		}
		return 0;
	}
	private function getBiblioItems($biblio_id) {
		$items = $this->db->select('item(i)',
			[
				'[>]reserve(r)'	=> ['i.item_code' => 'item_code'],
				'[>]mst_coll_type(t)' => ['coll_type_id' => 'coll_type_id'],
				'[>]loan(l)' => ['item_code' => 'item_code', 'is_lent' => '=1', 'is_return' => '=0'],
				'[>]member(m)' => ['l.member_id' => 'member_id']
			],
			['i.item_id', 'i.call_number', 'l.member_id', 'm.member_name', 't.coll_type_id', 't.coll_type_name', 'i.item_code', 'l.loan_date', 'l.due_date', 'r.member_id(rev_member_id)', 'r.reserve_date'],
			['i.biblio_id' => $biblio_id]);

		//print_r( $this->db->last_query() );

		return $items;
	}

	public function setRate(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$posts = $this->getPosts(['rate']);
		if ($posts) {
			$check = $this->db->get('biblio_rate', 'id', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
			if ($check) {
				$this->db->update('biblio_rate', ['rate' => $posts['rate']], ['id' => $check]);
			} else {
				$this->db->insert('biblio_rate', [
					'biblio_id' => $biblio_id,
					'member_id' => $this->user->id,
					'rate' => $posts['rate']]);
			}
			if (!$this->db->lastError) {
				$this->setTrue();
			} else {
				$this->error($this->db->lastError);
			}
		} else {
			$this->error("Data tidak lengkap");
		}

		return $this->result;
	}

	public function getRate(Request $req, Response $res, $args)  {
		$biblio_id = $args['biblio_id'];

		$this->setTrue();

		$rate = $this->db->get('biblio_rate', 'rate', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
		if ($rate) {	
			$this->setData($rate);
		} else {
			$this->setData(0);
		}

		return $this->result;
	}


	public function setFav(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$check = $this->db->get('member_favorit', 'id', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
		if (!$check) {
			$insert = $this->db->insert('member_favorit', ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]);
			if ($insert) {
				$this->setTrue();
			}
		} else {
			$this->setTrue();
		}

		return $this->result;
	}

	public function unsetFav(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$this->db->delete('member_favorit', ['AND' => ['biblio_id' => $biblio_id, 'member_id' => $this->user->id]]);
		$this->setTrue();

		return $this->result;
	}

	public function getTopics(Request $req, Response $res, $args) {
		$select = $this->db->manual('SELECT topic_id as id, topic FROM `mst_topic` WHERE topic_id IN (SELECT DISTINCT(topic_id) FROM biblio_topic) ORDER BY `topic_id` ASC');

		if ($select) {
			$this->setTrue();
			$this->setData($select);
		}

		return $this->result;
	}

	public function selectReserve(Request $req, Response $res, $args) {
		$select = $this->db->select('reserve',
			[
				'[>]biblio' => ['biblio_id' => 'biblio_id'],
				'[>]mst_gmd' => ['biblio.gmd_id' => 'gmd_id'],
				'[>]mst_publisher' => ['biblio.publisher_id' => 'publisher_id'],
				'[>]mst_language' => ['biblio.language_id' => 'language_id']
			],
			['biblio.biblio_id', 'biblio.title', 'reserve.item_code', 'reserve.reserve_date',
			'biblio.publish_year', 
			'mst_language.language_name', 'CONCAT[\'' . BOOK_URL . '\', biblio.image](image)', 'biblio.promoted',
			'mst_gmd.gmd_name',
			'mst_publisher.publisher_name'
			],
			['member_id' => $this->user->id]);

		for ($i = 0; $i < count($select); $i++) {
			$authors = $this->getBibioAuthors($select[$i]['biblio_id']);
			$authorTexts = [];
			foreach ($authors as $author) {
				$authorTexts[] = $author['author_name'];
			}
			$select[$i]['authors'] = implode(', ', $authorTexts);
		}

		if (is_array($select)) {
			$this->setTrue();
			$this->setData($select);
		}

		return $this->result;
	}

	public function setReserve(Request $req, Response $res, $args) {
		$posts = $this->getPosts(['biblio_id']);

		if ($posts) {
			/* check kuota reservasi */
			$checkUserRevCount = $this->db->count('reserve', ['member_id' => $this->user->id]);
			if ($checkUserRevCount < 5) {

				/* check apakah sudah reservasi */
				$checkRevBiblio = $this->db->get('reserve', 'reserve_id', ['AND' => ['member_id' => $this->user->id, 'biblio_id' => $posts['biblio_id']]]);

				if ($checkRevBiblio) {
					$this->error("Anda sudah reservasi buku ini.");
				} else {

					/* check item code dari biblio tersebut */
					$item_codes = $this->db->select('item', 'item_code', ['biblio_id' => $posts['biblio_id']]);

					if ($item_codes) {
						$foundCode = "";
						$unLoanFound = "";
						$i = 0;
						while (($unLoanFound == "") && ($i < count($item_codes))) {
							$exist = $this->db->get('reserve', 'member_id', ['item_code' => $item_codes[$i]]);
							if (!$exist) {
								/* bila tidak di reserve anggota lain, ambil */
								$foundCode = $item_codes[$i];

								$existUnloan = $this->db->get('loan', 'loan_id', ['AND' => ['item_code' => $item_codes[$i], 'is_lent' => 1, 'is_return' => 0]]);
								if (!$existUnloan) {
									$unLoanFound = $item_codes[$i];
									$foundCode = $item_codes[$i];
								}
							}

							$i++;
						}

						if ($foundCode) {
							$insert = $this->db->insert('reserve', [
								'member_id' => $this->user->id,
								'biblio_id' => $posts['biblio_id'],
								'item_code' => $foundCode,
								'reserve_date' => date('Y-m-d H:i:s')
							]);
							if ($insert) {
								$this->setTrue();
								$this->setData("Buku berhasil direservasi. Anda dapat melihat daftar buku yang direservasi pada halaman Reservasi.");
							} else {
								$this->error("Terjadi kesalahan ketika menyimpan database");
							}
						} else {
							$this->error("Semua buku ini sudah direservasi");
						}
					} else {
						$this->error("Jumlah eksemplar di perpustakaan tidak mencukupi");
					}
				}

			} else {
				$this->error("Hanya bisa reservasi paling banyak lima buku.");
			}
		} else {
			$this->error("Data tidak lengkap");
		}

		return $this->result;
	}

	public function unsetReserve(Request $req, Response $res, $args) {
		$posts = $this->getPosts(['biblio_id']);

		if ($posts) {
			$checkRevBiblio = $this->db->get('reserve', 'reserve_id', ['AND' => ['member_id' => $this->user->id, 'biblio_id' => $posts['biblio_id']]]);
			if ($checkRevBiblio) {
				$this->db->delete('reserve', ['reserve_id' => $checkRevBiblio]);
				$this->setTrue();
			} else {
				$this->error("Anda belum reservasi buku ini");
			}
		} else {
			$this->error("Data tidak lengkap");
		}

		return $this->result;
	}

}

?>