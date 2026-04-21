<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dealer extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('logged_in') || $this->session->userdata('user_type') != 'dealer') {
            redirect('auth/login');
        }
        $this->load->model('User_model');
    }

    public function index() {
        $user_id = $this->session->userdata('user_id');
        $data['dealer'] = $this->User_model->get_user_by_id($user_id);
        $this->load->view('dealer/dashboard.html', $data);
    }
    // public function getdetails() {
    //     echo 'hey';die;        
    //     $user_id = $this->input->post('dealer');
    //     $dealer = $this->User_model->get_user_by_id($user_id);
    //     echo $dealer;die;
    //     return $dealer;
    // }

    public function update_location() {
        echo '<pre>';print_r($this->input->post());die;

        $this->form_validation->set_rules('city', 'City', 'required|trim');
        $this->form_validation->set_rules('state', 'State', 'required|trim');
        $this->form_validation->set_rules('zip', 'Zip Code', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => validation_errors()]);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $data = [
            'city'  => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'zip'   => $this->input->post('zip')
        ];

        if ($this->User_model->update_dealer_location($user_id, $data)) {
            $this->session->set_userdata(['city' => $data['city'], 'state' => $data['state'], 'zip' => $data['zip']]);
            echo json_encode(['status' => 'success', 'message' => 'Location updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'errors' => 'Update failed.']);
        }
    }
}