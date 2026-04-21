<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in') || $this->session->userdata('user_type') != 'employee') {
            redirect('auth/login');
        }
        $this->load->model('User_model');
        $this->load->library('pagination');
    }

    public function index() {
        $zip_search = $this->input->post('zip_search');
        $submit = $this->input->post('update');
        $dealer_search = $this->input->post('search_dealer');
        $show_list = false;
        if(!empty($dealer_search)){
            $show_list = true;
        }

        if($submit){
            echo 'hey';die;
            $data = [
                'city'  => $this->input->post('city'),
                'state' => $this->input->post('state'),
                'zip'   => $this->input->post('zip')
            ];
        }
        $data['show_list'] = $show_list;

        // echo $zip_search;die;
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;
        $per_page = 3;

        $config['base_url'] = base_url('employee/index');
        $config['total_rows'] = $this->User_model->get_dealers_count($zip_search);
        $config['per_page'] = $per_page;
        $config['uri_segment'] = 3;  // explicit
        $config['use_page_numbers'] = FALSE; // use offset, not page number
        $config['full_tag_open'] = '<ul class="pagination">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';  
        $config['next_link'] = 'Next &raquo;';   
        $config['prev_link'] = '&laquo; Prev';   
        $config['attributes'] = array('class' => 'page-link'); 
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);
        $data['offset'] = $page;
        $data['dealers'] = $this->User_model->get_dealers_paginated($config['per_page'], $page, $dealer_search);
        $data['pagination'] = $this->pagination->create_links();
        $data['zip_search'] = $zip_search;
        $data['all_dealers'] = $this->User_model->get_dealers();
        // echo '<pre>';print_r($data['dealers']);die;
        // $this->load->view('templates/header.html');
        $this->load->view('employee/dealer_list.html', $data);
        // $this->load->view('templates/footer.html');
    }

    public function edit_dealer($dealer_id) {
        if (!$this->session->userdata('logged_in') || $this->session->userdata('user_type') != 'employee') {
            echo json_encode(['status' => 'error', 'errors' => 'Unauthorized']);
            return;
        }

        $this->form_validation->set_rules('city', 'City', 'required|trim');
        $this->form_validation->set_rules('state', 'State', 'required|trim');
        $this->form_validation->set_rules('zip', 'Zip Code', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => validation_errors()]);
            return;
        }

        $data = [
            'city'  => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'zip'   => $this->input->post('zip')
        ];

        if ($this->User_model->update_dealer_location($dealer_id, $data)) {
            echo json_encode(['status' => 'success', 'message' => 'Dealer info updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'errors' => 'Update failed.']);
        }
    }

    public function get_dealer($dealer_id) {
        $dealer = $this->User_model->get_user_by_id($dealer_id);
        if ($dealer && $dealer->user_type == 'dealer') {
            echo json_encode(['status' => 'success', 'data' => $dealer]);
        } else {
            echo json_encode(['status' => 'error', 'errors' => 'Dealer not found']);
        }
    }
}