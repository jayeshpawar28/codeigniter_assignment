<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }


    public function register() {
        if ($this->session->userdata('logged_in')) {
            redirect($this->session->userdata('user_type') == 'employee' ? 'employee' : 'dealer');
        }
        $this->load->view('auth/register.html');
    }

    public function do_register() {
        $this->form_validation->set_rules('first_name', 'First Name', 'required');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        $this->form_validation->set_rules('user_type', 'User Type', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => validation_errors()]);
            return;
        }

        $email = $this->input->post('email');
        if ($this->User_model->check_email_exists($email)) {
            echo json_encode(['status' => 'error', 'errors' => 'Email already exists!']);
            return;
        }

        $data = [
            'first_name' => $this->input->post('first_name'),
            'last_name'  => $this->input->post('last_name'),
            'email'      => $email,
            'password'   => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
            'user_type'  => $this->input->post('user_type')
        ];

        if ($this->User_model->insert_user($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful! Redirecting on login...']);
        } else {
            echo json_encode(['status' => 'error', 'errors' => 'Registration failed. Try again.']);
        }
    }

    public function check_email() {
        $email = $this->input->post('email');
        if ($this->User_model->check_email_exists($email)) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }

    public function login() {
        // If session exists and user is logged in, redirect to dashboard
        if ($this->session->userdata('logged_in') === TRUE) {
            $user_type = $this->session->userdata('user_type');
            if ($user_type == 'employee') {
                redirect('employee');
            } elseif ($user_type == 'dealer') {
                redirect('dealer');
            } else {
                // fallback: logout
                $this->session->sess_destroy();
            }
        }
        // Load login view
        $this->load->view('auth/login.html');
    }

    public function do_login() {
        $this->form_validation->set_rules('login_email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => validation_errors()]);
            return;
        }

        $email = $this->input->post('login_email');
        $password = $this->input->post('password');
        $user = $this->User_model->get_user_by_email($email);

        if ($user && password_verify($password, $user->password)) {
            $session_data = [
                'logged_in'   => true,
                'user_id'     => $user->id,
                'email'       => $user->email,
                'user_type'   => $user->user_type,
                'full_name'   => $user->first_name . ' ' . $user->last_name,
                'city'        => $user->city,
                'state'       => $user->state,
                'zip'         => $user->zip
            ];
            $this->session->set_userdata($session_data);

            if ($user->user_type == 'dealer' && (empty($user->city) && empty($user->state) && empty($user->zip))) {
                echo json_encode(['status' => 'success', 'redirect' => 'dealerSetup']);
            } else {
                echo json_encode(['status' => 'success', 'redirect' => $user->user_type == 'employee' ? 'employee' : 'dealer']);
            }
        } else {
            echo json_encode(['status' => 'error', 'errors' => 'Invalid email or password']);
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('auth/login');
    }
}