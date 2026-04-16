<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Loader extends CI_Loader {

    public function __construct() {
        parent::__construct();
        // Force view extension to .html
        $this->_ci_view_extensions = array('html' => TRUE, 'php' => TRUE);
    }

    /**
     * Find a view file with .html extension first
     */
    protected function _ci_find_view_path($view, $cascade = TRUE) {
        // Try with .html extension first
        if (file_exists(APPPATH . 'views/' . $view . '.html')) {
            return APPPATH . 'views/' . $view . '.html';
        }
        // Fallback to parent method (looks for .php)
        return parent::_ci_find_view_path($view, $cascade);
    }
}