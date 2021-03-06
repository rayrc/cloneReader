<?php
class Feedbacks extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('Feedbacks_Model');
	}

	function index() {
	}

	function addFeedback() {
		if (! $this->safety->allowByControllerName(__METHOD__) ) { return errorForbidden(); }

		$this->load->helper('email');
		$this->load->model('Users_Model');

		$userId = (int)$this->session->userdata('userId');
		$data	= array();
		if ($userId != config_item('userAnonymous')) {
			$data = $this->Users_Model->get($userId);
		}

		$feedbackUserEmail = element('userEmail', $data);
		if (valid_email($feedbackUserEmail) == false) {
			$feedbackUserEmail = '';
		}

		$form = array(
			'frmName'   => 'frmFeedbackEdit',
			'callback'  => 'function(response) { $.Feedback.onSaveFeedback(response); };',
			'fields' => array(
				'feedbackId' => array(
					'type'  => 'hidden',
					'value' => element('feedbackId', $data, 0)
				),
				'feedbackUserName' => array(
					'type'   => 'text',
					'label'  => lang('Name'),
					'value'  => trim(element('userFirstName', $data).' '.element('userLastName', $data)),
				),
				'feedbackUserEmail' => array(
					'type'   => 'text',
					'label'  => lang('Email'),
					'value'  => $feedbackUserEmail
				),
				'feedbackDesc' => array(
					'type'  => 'textarea',
					'label' => lang('Comment'),
					'value' => ''
				),
			),
			'buttons' => array( '<button type="submit" class="btn btn-primary"><i class="fa fa-comment"></i> '.lang('Send').'</button> '),
		);

		$form['rules'] = array(
			array(
				'field' => 'feedbackUserName',
				'label' => $form['fields']['feedbackUserName']['label'],
				'rules' => 'trim|required'
			),
			array(
				'field' => 'feedbackUserEmail',
				'label' => $form['fields']['feedbackUserEmail']['label'],
				'rules' => 'trim|required|valid_email'
			),
			array(
				'field' => 'feedbackDesc',
				'label' => $form['fields']['feedbackDesc']['label'],
				'rules' => 'trim|required'
			),
		);

		$this->form_validation->set_rules($form['rules']);

		if ($this->input->post() != false) {
			$code = $this->form_validation->run();
			if ($code == true) {
				$this->Feedbacks_Model->saveFeedback($this->input->post());
			}

			if ($this->input->is_ajax_request()) {
				return loadViewAjax($code);
			}
		}

		$this->load->view('pageHtml', array(
			'view'  => 'includes/crForm',
			'meta'  => array( 'title' => lang('Feedback') ),
			'form'  => $form,
			'langs' => array( 'Thanks for contacting us' ),
		));
	}

	function listing() {
		if (! $this->safety->allowByControllerName(__METHOD__) ) { return errorForbidden(); }

		$page = (int)$this->input->get('page');
		if ($page == 0) { $page = 1; }

		$query = $this->Feedbacks_Model->selectToList($page, config_item('pageSize'), array('search' => $this->input->get('search')));

		$this->load->view('pageHtml', array(
			'view'    => 'includes/crList',
			'meta'    => array( 'title' => lang('Feedbacks')),
			'list'    => array(
				'urlList'   => strtolower(__CLASS__).'/listing',
				'urlEdit'   => strtolower(__CLASS__).'/edit/%s',
				'columns'   => array(
					'feedbackUserName'   => lang('Name'),
					'feedbackUserEmail'  => lang('Email'),
					'feedbackDesc'       => array('class' => 'dotdotdot', 'value' =>  lang('Description')),
					'feedbackDate'       => array('class' => 'datetime', 'value' => lang('Date')),
				),
				'data'       => $query['data'],
				'foundRows'  => $query['foundRows'],
				'showId'     => true,
			)
		));
	}

	function edit($feedbackId) {
		if (! $this->safety->allowByControllerName(__METHOD__) ) { return errorForbidden(); }

		$data = getCrFormData($this->Feedbacks_Model->get($feedbackId), $feedbackId);
		if ($data === null) { return error404(); }

		$form = $this->_getFormProperties($feedbackId, false);

		if ($this->input->post() != false) {
			$code = $this->form_validation->run();
			if ($code == true) {
				$this->Feedbacks_Model->save($this->input->post());
			}

			if ($this->input->is_ajax_request()) {
				return loadViewAjax($code);
			}
		}

		$this->load->view('pageHtml', array(
			'view'    => 'includes/crForm',
			'meta'    => array('title' => lang('Feedbacks')),
			'form'    => populateCrForm($form, $data),
		));
	}

	function _getFormProperties($feedbackId) {
		$form = array(
			'frmName'  => 'frmFeedbackEdit',
			'buttons'  => array('<button type="button" class="btn btn-default" onclick="$.goToUrlList();"><i class="fa fa-arrow-left"></i> '.lang('Back').' </button> '),
			'fields'   => array(
				'feedbackId' => array(
					'type'  => 'hidden',
					'value' => $feedbackId,
				),
				'feedbackUserName' => array(
					'type'      => 'text',
					'label'     => lang('Name'),
					'disabled'  => true,
				),
				'feedbackUserEmail' => array(
					'type'   => 'text',
					'label'  => lang('Email'),
				),
				'feedbackDesc' => array(
					'type'   => 'textarea',
					'label'  => lang('Description'),
				),
				'feedbackDate' => array(
					'type'   => 'datetime',
					'label'  => lang('Date'),
				),
			)
		);

		$form['rules'] = array(
			array(
				'field' => 'feedbackDesc',
				'label' => $form['fields']['feedbackDesc']['label'],
				'rules' => 'trim|required'
			),
			array(
				'field' => 'feedbackDate',
				'label' => $form['fields']['feedbackDate']['label'],
				'rules' => 'trim|required'
			),
		);

		$this->form_validation->set_rules($form['rules']);

		return $form;
	}
}
