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
		}

		if (count($whereAr) > 1) {
			$where['AND'] = $whereAr;
		} else {
			$where = array_merge($whereAr, $where);
		}


		$select = $this->db->select('biblio',
			['[>]mst_gmd' => ['gmd_id' => 'gmd_id'], '[>]mst_publisher' => ['publisher_id' => 'publisher_id']],
			['biblio.biblio_id', 'biblio.title', 'biblio.sor', 'biblio.edition', 'biblio.isbn_issn',
			'biblio.publish_year', 'biblio.collation', 'biblio.series_title', 'biblio.call_number',
			'biblio.language_id', 'biblio.image', 'biblio.file_att', 'biblio.opac_hide', 'biblio.promoted',
			'biblio.gmd_id', 'mst_gmd.gmd_name', 'mst_gmd.icon_image(gmd_icon_image)',
			'biblio.publisher_id', 'mst_publisher.publisher_name'
			],
			$where);


		for ($i = 0; $i < count($select); $i++) {
			$select[$i]['item_count'] = $this->getBiblioItemCount($select[$i]['biblio_id']);
			$select[$i]['rate'] = $this->getBiblioRate($select[$i]['biblio_id']);
			$select[$i]['authors'] = $this->getBibioAuthors($select[$i]['biblio_id']);
			$select[$i]['topic'] = $this->getBibioTopics($select[$i]['biblio_id']);
		}

		if (is_array($select)) {
			$args['total'] = $this->db->count('biblio', $where);

			$this->setTrue();
			$this->setData(['params' => $args, 'results' => $select]);
		}

		return $this->result;
	}

	public function get(Request $req, Response $res, $args) {
		$biblio_id = $args['biblio_id'];

		$get = $this->db->get('biblio',
			[
			'[>]mst_gmd' => ['gmd_id' => 'gmd_id'],
			'[>]mst_publisher' => ['publisher_id' => 'publisher_id'],
			'[>]mst_place' => ['publish_place_id' => 'place_id']
			],
			['biblio.biblio_id',
			'biblio.title',
			'biblio.sor',
			'biblio.edition',
			'biblio.isbn_issn',
			'biblio.publish_year',
			'biblio.collation',
			'biblio.series_title',
			'biblio.call_number',
			'biblio.language_id',
			'biblio.source',
			'biblio.classification',
			'biblio.notes',
			'biblio.image',
			'biblio.file_att',
			'biblio.opac_hide',
			'biblio.promoted',
			'biblio.input_date',
			'biblio.last_update',
			'mst_place.place_id',
			'mst_place.place_name',
			'biblio.gmd_id', 'mst_gmd.gmd_name', 'mst_gmd.icon_image(gmd_icon_image)',
			'biblio.publisher_id', 'mst_publisher.publisher_name'
			],
			['biblio.biblio_id' => $biblio_id]);

		if ($get) {
			$get['item'] = $this->getBiblioItems($biblio_id);
			$get['authors'] = $this->getBibioAuthors($biblio_id);
			$get['topic'] = $this->getBibioTopics($biblio_id);
			$get['rate'] = $this->getBiblioRate($biblio_id);

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
		$manual = $this->db->manual('SELECT COALESCE(TRUNCATE(SUM(`rate`) / COUNT(`rate`), 2), 0) as `average` FROM `biblio_rate` WHERE `biblio_id` = 132');
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
		return $this->db->select('item(i)',
			['[>]mst_coll_type(t)' => ['coll_type_id' => 'coll_type_id']],
			['i.item_id', 'i.call_number', 't.coll_type_id', 't.coll_type_name', 'i.item_code', 'i.inventory_code'],
			['i.biblio_id' => $biblio_id]);
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
}

?>