<?php
class Feedbacks_Model extends CI_Model {
	function selectToList($pageCurrent = null, $pageSize = null, array $filters = array()){
		$this->db
			->select('SQL_CALC_FOUND_ROWS feedbacks.feedbackId, feedbackDesc, feedbackDate, feedbackUserName, feedbackUserEmail ', false)
			->from('feedbacks')
			->join('users', 'feedbacks.userId = users.userId', 'inner');

		if (element('search', $filters) != null) {
			$this->db->like('feedbackDesc', $filters['search']);
		}

		$this->Commond_Model->appendLimitInQuery($pageCurrent, $pageSize);

		$query = $this->db->get();
		//pr($this->db->last_query());

		return array('data' => $query->result_array(), 'foundRows' => $this->Commond_Model->getFoundRows());
	}

	function save($data){
		$feedbackId = $data['feedbackId'];

		$values = array(
			'userId'       => element('userId', $data),
			'feedbackDesc' => element('feedbackDesc', $data),
			'feedbackDate' => element('feedbackDate', $data),
		);

		if ((int)$feedbackId != 0) {
			$this->db->where('feedbackId', $feedbackId)->update('feedbacks', $values);
		}
		else {
			$this->db->insert('feedbacks', $values);
			$feedbackId = $this->db->insert_id();
		}

		$this->db->where('feedbackId', $feedbackId);

		return true;
	}

	function saveFeedback($data) {
		$values = array(
			'userId'            => $this->session->userdata('userId'),
			'feedbackDesc'      => element('feedbackDesc', $data),
			'feedbackDate'      => date("Y-m-d H:i:s"),
			'feedbackUserName'  => element('feedbackUserName', $data),
			'feedbackUserEmail' => element('feedbackUserEmail', $data),
		);

		$this->db->insert('feedbacks', $values);

		$this->load->model(array('Tasks_Model'));
		$this->Tasks_Model->addTask('sendFeedback', $values);

		return true;
	}

	function get($feedbackId) {
		$query = $this->db
				->select('feedbacks.feedbackId, feedbackDesc, users.userId, feedbackDate, feedbackUserName, feedbackUserEmail ', false)
				->where('feedbacks.feedbackId', $feedbackId)
				->join('users', 'feedbacks.userId = users.userId', 'inner')
				->get('feedbacks')->row_array();
		//pr($this->db->last_query());
		return $query;
	}
}
