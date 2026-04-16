<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function insert_user($data) {
        return $this->db->insert('users', $data);
    }

    public function check_email_exists($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        return $query->num_rows() > 0;
    }

    public function get_user_by_email($email) {
        $this->db->where('email', $email);
        $query = $this->db->get('users');
        return $query->row();
    }

    public function get_user_by_id($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('users');
        return $query->row();
    }

    public function update_dealer_location($user_id, $data) {
        $this->db->where('id', $user_id);
        $this->db->where('user_type', 'dealer');
        return $this->db->update('users', $data);
    }

    public function get_dealers_count($zip_search = null) {
        $this->db->where('user_type', 'dealer');
        if (!empty($zip_search)) {
            $this->db->like('zip', $zip_search);
        }
        return $this->db->count_all_results('users');
    }

    public function get_dealers_paginated($limit, $offset, $zip_search = null) {
        $this->db->where('user_type', 'dealer');
        if (!empty($zip_search)) {
            $this->db->like('zip', $zip_search);
        }
        $this->db->limit($limit, $offset);
        $query = $this->db->get('users');
        return $query->result();
    }
}