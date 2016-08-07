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

   		// query database
   		$select = $this->db->select('biblio',
   			['[>]mst_gmd' => ['gmd_id' => 'gmd_id'], '[>]mst_publisher' => ['publisher_id' => 'publisher_id']],
   			['biblio.biblio_id', 'biblio.title', 'biblio.sor', 'biblio.edition', 'biblio.isbn_issn',
   			 'biblio.publish_year', 'biblio.collation', 'biblio.series_title', 'biblio.call_number',
   			 'biblio.language_id', 'biblio.image', 'biblio.file_att', 'biblio.opac_hide', 'biblio.promoted',
   			 'biblio.gmd_id', 'mst_gmd.gmd_name', 'mst_gmd.icon_image(gmd_icon_image)',
   			 'biblio.publisher_id', 'mst_publisher.publisher_name'
   			],
   			$where);

   		// insert details
   		for ($i = 0; $i < count($select); $i++) {
   			$select[$i]['authors'] = $this->getBibioAuthors($select[$i]['biblio_id']);
   			$select[$i]['topic'] = $this->getBibioTopics($select[$i]['biblio_id']);
   		}

   		if ($select) {
   			//get totals
   			$args['total'] = $this->db->count('biblio', $where);

   			$this->setTrue();
   			$this->setData(['params' => $args, 'results' => $select]);
   		}

   		return $this->result;
   	}

   	public function get(Request $req, Response $res, $args) {
   		$biblio_id = $args['id'];

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
	   		$get['authors'] = $this->getBibioAuthors($biblio_id);
			$get['topic'] = $this->getBibioTopics($biblio_id);

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
}

?>