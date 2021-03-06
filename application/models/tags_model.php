<?php
class Tags_Model extends CI_Model {

	/*
	 * @param  (array)  $filters es un array con el formato:
	 * 		array(
	 * 			'search'          => null,
	 * 			'userId'          => null,
	 * 			'feedId'          => null,
	 * 			'hideSystemTags'  => null,
	 * 			'notOnlyFeedId'   => null, // filtra tags que tengan más feeds que el seleccionado
	 * 		);
	 * */
	function selectToList($pageCurrent = null, $pageSize = null, array $filters = array(), array $orders = array()){
		$this->db
			->select('SQL_CALC_FOUND_ROWS tags.tagId, tagName', false)
			->from('tags');

		if (element('search', $filters) != null) {
			$this->db->like('tagName', $filters['search']);
		}
		if (element('userId', $filters) != null) {
			$this->db
				->join('users_tags', 'users_tags.tagId = tags.tagId', 'inner')
				->where('userId', $filters['userId']);
		}
		if (element('feedId', $filters) != null) {
			$this->db
				->join('feeds_tags', 'feeds_tags.tagId = tags.tagId', 'inner')
				->where('feedId', $filters['feedId']);
		}

		if (element('hideSystemTags', $filters) == true) {
			// TODO: meter $aSystenTags en el config
			$aSystenTags = array(config_item('tagAll'), config_item('tagStar'), config_item('tagHome'), config_item('tagBrowse'));
			$this->db->where_not_in('tags.tagId', $aSystenTags);
		}

		if (element('notOnlyFeedId', $filters) == true && element('feedId', $filters) != null) {
			$this->db->where(' tags.tagId IN ( SELECT tagId FROM feeds_tags WHERE feedId <> '.$filters['feedId'].')', null, false);
		}

		$this->Commond_Model->appendOrderByInQuery($orders, array( 'tagId', 'tagName', 'countTotal'));

		if ($pageCurrent != null) {
			$this->Commond_Model->appendLimitInQuery($pageCurrent, $pageSize);
		}

		$query = $this->db->get();
		//pr($this->db->last_query()); die;

		return array('data' => $query->result_array(), 'foundRows' => $this->Commond_Model->getFoundRows());
	}

	function select(){
		return $this->db->get('tags')->result_array();
	}

	function get($tagId){
		$result = $this->db
				->where('tags.tagId', $tagId)
				->get('tags')->row_array();
		return $result;
	}

	function save($data){
		$tagId = $data['tagId'];

		$values = array(
			'tagName' => $data['tagName'],
		);


		if ((int)$tagId != 0) {
			$this->db->where('tagId', $tagId);
			$this->db->update('tags', $values);
		}
		else {
			$this->db->insert('tags', $values);
			$tagId = $this->db->insert_id();
		}
		//pr($this->db->last_query());

		return $tagId;
	}

	function delete($tagId) {
		$this->db->delete('tags', array('tagId' => $tagId));
		return true;
	}

	function saveTagByUserId($userId, $tagId, $tagName) {
		$tagName  = substr(trim($tagName), 0, 200);

		$query = $this->db->where('tagName', $tagName)->get('tags')->result_array();
		//pr($this->db->last_query());
		if (!empty($query)) {
			$newTagId = $query[0]['tagId'];
		}
		else {
			$this->db->insert('tags', array( 'tagName'	=> $tagName ));
			$newTagId = $this->db->insert_id();
			//pr($this->db->last_query());
		}

		// users_tags
		$this->db->ignore()->insert('users_tags', array( 'tagId'=> $newTagId, 'userId' => $userId ));

		// users_feeds_tags
		$query = ' INSERT IGNORE INTO users_feeds_tags
				(userId, feedId, tagId)
				SELECT userId, feedId, '.$newTagId.'
				FROM users_feeds_tags
				WHERE userId = '.(int)$userId.'
				AND   tagId  = '.(int)$tagId.' ';
		$this->db->query($query);

		// users_entries
		$query = ' INSERT IGNORE INTO users_entries
				(userId, entryId, tagId, feedId, entryRead, entryDate)
				SELECT userId, entryId, '.$newTagId.', feedId, entryRead, entryDate
				FROM users_entries
				WHERE userId = '.(int)$userId.'
				AND   tagId  = '.(int)$tagId.' ';
		$this->db->query($query);

		$this->deleteTagByUserId($userId, $tagId);

		return $newTagId;
	}

	function deleteTagByUserId($userId, $tagId) {
		$this->db->delete('users_tags', array('tagId' => $tagId, 'userId' => $userId));

		$this->db->delete('users_feeds_tags', array('tagId' => $tagId, 'userId' => $userId));

		$this->db->delete('users_entries', array('tagId' => $tagId, 'userId' => $userId));

		return true;
	}

	function saveTagsSearch($deleteEntitySearch = false, $onlyUpdates = false, $tagId = null) {
		if ($deleteEntitySearch == true) {
			$this->Commond_Model->deleteEntitySearch(config_item('entityTypeTag'));
		}

		$aWhere = array(' tags.tagId NOT IN ( '.config_item('tagAll').', '.config_item('tagStar').', '.config_item('tagHome').', '.config_item('tagBrowse').' ) ');
		if ($onlyUpdates == true) {
			$lastUpdate = $this->Commond_Model->getProcessLastUpdate('saveTagsSearch');
			$aWhere[] = ' tags.lastUpdate > \''.$lastUpdate.'\' ';
		}
		if ($tagId != null) {
			$aWhere[] = ' tags.tagId = '.(int)$tagId;
		}

		$searchKey = 'searchTags';
		$query = " REPLACE INTO entities_search
			(entityTypeId, entityId, entityNameSearch, entityName, entityFullName)
			SELECT DISTINCT ".config_item('entityTypeTag').", tags.tagId, CONCAT_WS(' ', IF(feedId IS NOT NULL, ' tagHasFeed ', ''), '".$searchKey."', tagName), tagName, tagName
			FROM tags
			LEFT JOIN feeds_tags ON feeds_tags.tagId = tags.tagId
			".(!empty($aWhere) ? ' WHERE '.implode(' AND ', $aWhere) : '')." ";
		$this->db->query($query);
		//pr($this->db->last_query()); die;

		if ($tagId == null) {
			$this->Commond_Model->updateProcessDate('saveTagsSearch');
		}

		return true;
	}
}
