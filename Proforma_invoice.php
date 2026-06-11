<?php
ob_start();
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Proforma_invoice extends CI_Controller {

	private $billToRendered = false;
	function __construct()
	{
		parent::__construct();
		$this->load->helper('custom_functions_helper');
		$this->load->model('superadmin/Master_model');
	}

	public function list_proforma_invoice()
	{
		$data["loc_access"] = $this->Master_model->getAccessStatus('Proforma Invoice Master');
		$data['module_id'] = 2;

		// Jayesh - 26-05-2026
	    $data["order_access"] = $this->Master_model->getAccessForModule('Order List');
	    $data["followup_access"] = $this->Master_model->getAccessForModule('Followup List');

       // ===== Reset filters =====
		if (isset($_POST["btn_reset"]) || $this->uri->segment(2) == "0" || $this->uri->segment(2) == "") {
			$this->session->unset_userdata([
				'search_proforma_invoice_dealer_name',
				'search_proforma_invoice_dealer_id',
				'search_source',
				'search_proforma_invoice_no',
				'search_po_no',
				'search_order_generated',
				'search_tax_type',
				'search_from_date',
				'search_to_date',
				'search_proforma_invoice_employee_id',
				'search_proforma_invoice_employee_name',
				'search_product_category',
	            'search_product_name',
	            'search_product_id',
	            'search_team_leader',
	            'search_main_product_category',
	            'search_team_leader_id',
				'search_product_sub_category'

			]);
		}

       // ===== Set filters =====
		if (isset($_POST["btn_search"])) {
			$this->session->set_userdata([
				'search_proforma_invoice_dealer_name' => $this->input->post('txt_dealer_name'),
				'search_proforma_invoice_dealer_id'   => $this->input->post('txt_dealer_id'),
				'search_source'                => $this->input->post('sel_source'),
				'search_proforma_invoice_no'          => trim($this->input->post('txt_proforma_invoice_no')),
				'search_po_no'				          => trim($this->input->post('txt_po_no')),
				'search_order_generated'       => $this->input->post('sel_order_generated'),
				'search_tax_type'              => $this->input->post('sel_tax_type'),
				'search_from_date'             => $this->input->post('from_date'),
				'search_to_date'               => $this->input->post('to_date'),
				'search_proforma_invoice_employee_name' => $this->input->post('txt_employee_name'),
				'search_proforma_invoice_employee_id'   => $this->input->post('txt_employee_id'),
				 'search_team_leader'           => $this->input->post('txt_team_leader'),
	            'search_team_leader_id'        => $this->input->post('hid_team_leader_id'),
	            'search_product_category'      => $this->input->post('sel_product_category'),
	            'search_product_name'          => $this->input->post('txt_product_name'),
	            'search_product_id'            => $this->input->post('hid_product_id'),
	            'search_main_product_category'      => $this->input->post('sel_main_product_category'),
	            'search_product_sub_category'  => $this->input->post('sel_product_sub_category')
			]);
		}
        $product_main_category = $this->session->userdata('search_main_product_category');
        $product_category = $this->session->userdata('search_product_category');
		
		$product_sub_category = $this->session->userdata('search_product_sub_category');

	    $product_id       = $this->session->userdata('search_product_id');
       // ===== Load Pagination Library =====
		$this->load->library('pagination');

		$config = [];
		$config['base_url']    = base_url('list-proforma-invoice');
	    $config["per_page"] = $this->session->userdata("JYOTI_PAGING_SESS" . SES_CONSTANT);
	    $config["uri_segment"] = 2;
		$config['reuse_query_string'] = true;

        // ===== Build WHERE conditions =====
	    $where = ['a.fld_isdeleted' => 0];
		// Apply customer visibility filter -jayesh on 28/11/2025
		apply_customer_visibility_filter('a', 'fld_id', 'fld_team_lead_id');
	    if ($this->session->userdata('search_proforma_invoice_dealer_id') != '') {
	    	$where['a.fld_dealer_id'] = $this->session->userdata('search_proforma_invoice_dealer_id');
	    }
	   
	    if ($this->session->userdata('search_source') != '') {
	    	$where['a.fld_source_id'] = $this->session->userdata('search_source');
	    }
	    if ($this->session->userdata('search_proforma_invoice_no') != '') {
	    	$where['a.fld_proforma_invoice_no'] = $this->session->userdata('search_proforma_invoice_no');
	    }

		// $this->db->where('a.fld_isdeleted', 0);

		if ($this->session->userdata('search_po_no') != '') {
		    $this->db->like('a.fld_po_no', $this->session->userdata('search_po_no'), 'both');
		}

	    if ($this->session->userdata('search_order_generated') != '') {
	    	$where['a.fld_order_generated'] = $this->session->userdata('search_order_generated');
	    }
		if ($this->session->userdata('search_tax_type') != '') {
	    	$where['a.fld_tax_type'] = $this->session->userdata('search_tax_type');
	    }
		
	    if ($this->session->userdata('search_from_date') != '' && $this->session->userdata('search_to_date') != '') {
	    $where['a.fld_proforma_invoice_date >='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_from_date'))));
	    $where['a.fld_proforma_invoice_date <='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_to_date'))));
	    }

		if ($this->session->userdata('search_proforma_invoice_employee_id') != '') {
		    $where['a.fld_id'] = $this->session->userdata('search_proforma_invoice_employee_id');
		}

		if ($this->session->userdata('search_team_leader_id') != '') {
	        $this->db->where('a.fld_team_lead_id', $this->session->userdata('search_team_leader_id'));
	    }

	    // ===== Get total count =====
	    $this->db->from('tbl_proforma_invoice_master as a');
	    // ===== Product Category / Product Filter =====
				// ===== Product Category / Product Filter (COUNT) =====
		// ===== Product Category / Product Filter =====
		if (!empty($product_category) || !empty($product_id) || !empty($product_main_category)) {

		    // ---- Subquery 1 : tbl_proforma_invoice_details ----
		    $subquery1 = "EXISTS (
		        SELECT 1
		        FROM tbl_proforma_invoice_details pid
		        LEFT JOIN tbl_product_master pm 
		            ON pid.fld_product_master_id = pm.fld_product_master_id 
		            AND pm.fld_isdeleted = 0
		        WHERE pid.fld_proforma_invoice_id = a.fld_proforma_invoice_id
		        AND pid.fld_isdeleted = 0";

		    if (!empty($product_main_category)) {
		        $subquery1 .= " AND pm.fld_product_main_cat_id = " . (int)$product_main_category;
		    }

		    if (!empty($product_category)) {
		        $subquery1 .= " AND pid.fld_product_group_id = " . (int)$product_category;
		    }

			
			if (!empty($product_sub_category)) {
				$subquery1 .= " AND pm.fld_product_sub_category_id = " . (int)$product_sub_category;
			}


		    if (!empty($product_id)) {
		        $subquery1 .= " AND pid.fld_product_master_id = " . (int)$product_id;
		    }

		    $subquery1 .= ")";


		    // ---- Apply WHERE condition ----
		    if (!empty($product_id)) {

		        $this->db->where($subquery1, null, false);

		    } else {

		        // ---- Subquery 2 : tbl_proforma_invoice_other_product_details ----
		        $subquery2 = "EXISTS (
		            SELECT 1
		            FROM tbl_proforma_invoice_other_product_details sopd
		            WHERE sopd.fld_proforma_invoice_id = a.fld_proforma_invoice_id
		            AND sopd.fld_isdeleted = 0";

		        if (!empty($product_category)) {
		            $subquery2 .= " AND sopd.fld_category = " . (int)$product_category;
		        }

		        $subquery2 .= ")";

		        if (!empty($product_main_category)) {
		            $this->db->where($subquery1, null, false);
		        } else {
		            $this->db->where("($subquery1 OR $subquery2)", null, false);
		        }
		    }
		}


	    $this->db->join('tbl_admin AS ad', 'ad.fld_id  = a.fld_id AND ad.fld_isdeleted = 0', 'LEFT');
	    $this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = a.fld_dealer_id AND dm.fld_isdeleted = 0', 'LEFT');
	    $this->db->where($where);

	    $total_rows = $this->db->count_all_results();

	    $config['total_rows'] = $total_rows;

	    // ===== Initialize Pagination =====
	    $this->pagination->initialize($config);
	    $page = ($this->uri->segment(2)) ? intval($this->uri->segment(2)) : 0;

	    $data["page_no"]     = $page;
	    $data['total_count'] = $total_rows;
	    $data['links']       = $this->pagination->create_links();

	     $this->db->select('
	        IFNULL(SUM((spd.fld_qty * pm.fld_weight) / 1000), 0) as grand_total_weight
	    ');
	  
	    apply_customer_visibility_filter('a', 'fld_id', 'fld_team_lead_id');
	    $this->db->from('tbl_proforma_invoice_master as a');
	    
	    $this->db->join('tbl_admin AS ad', 'ad.fld_id = a.fld_id AND ad.fld_isdeleted = 0', 'LEFT');
	    $this->db->join('tbl_proforma_invoice_details AS spd', 'spd.fld_proforma_invoice_id  = a.fld_proforma_invoice_id  AND spd.fld_isdeleted = 0', 'LEFT');
	    $this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = spd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
	    
	    // Apply ONLY the base condition - no search filters
	    $this->db->where(['a.fld_isdeleted' => 0]);
	    
	    $grand_total_result = $this->db->get()->row();
	    $data['grand_total_weight'] = $grand_total_result ? $grand_total_result->grand_total_weight : 0;

	    // ===== Build WHERE again for paginated records =====
	    $where = ['a.fld_isdeleted' => 0];
	    if ($this->session->userdata('search_proforma_invoice_dealer_id') != '') {
	    	$where['a.fld_dealer_id'] = $this->session->userdata('search_proforma_invoice_dealer_id');
	    }
	   
	    if ($this->session->userdata('search_source') != '') {
	    	$where['a.fld_source_id'] = $this->session->userdata('search_source');
	    }
	    if ($this->session->userdata('search_proforma_invoice_no') != '') {
	    	$where['a.fld_proforma_invoice_no'] = $this->session->userdata('search_proforma_invoice_no');
	    }
	    // $this->db->where('a.fld_isdeleted', 0);

		if ($this->session->userdata('search_po_no') != '') {
		    $this->db->like('a.fld_po_no', $this->session->userdata('search_po_no'), 'both');
		}
	    if ($this->session->userdata('search_order_generated') != '') {
	    	$where['a.fld_order_generated'] = $this->session->userdata('search_order_generated');
	    }
		if ($this->session->userdata('search_tax_type') != '') {
	    	$where['a.fld_tax_type'] = $this->session->userdata('search_tax_type');
	    }
	    if ($this->session->userdata('search_from_date') != '' && $this->session->userdata('search_to_date') != '') {
	    $where['a.fld_proforma_invoice_date >='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_from_date'))));
	    $where['a.fld_proforma_invoice_date <='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_to_date'))));
	    }

		if ($this->session->userdata('search_proforma_invoice_employee_id') != '') {
		    $where['a.fld_id'] = $this->session->userdata('search_proforma_invoice_employee_id');
		}
		if ($this->session->userdata('search_team_leader_id') != '') {
	        $this->db->where('a.fld_team_lead_id', $this->session->userdata('search_team_leader_id'));
	    }
			// echo $this->db->last_query();die;
	    // 

	    // ===== Get paginated records =====
		// Apply customer visibility filter -jayesh on 28/11/2025
		apply_customer_visibility_filter('a', 'fld_id', 'fld_team_lead_id');
		// ===== Product Category / Product Filter (DATA) =====
		if (!empty($product_category) || !empty($product_id) || !empty($product_main_category)) {

		    // ---- Subquery 1 : tbl_proforma_invoice_details ----
		    $subquery1 = "EXISTS (
		        SELECT 1
		        FROM tbl_proforma_invoice_details pid
		        LEFT JOIN tbl_product_master pm 
		            ON pid.fld_product_master_id = pm.fld_product_master_id 
		            AND pm.fld_isdeleted = 0
		        WHERE pid.fld_proforma_invoice_id = a.fld_proforma_invoice_id
		        AND pid.fld_isdeleted = 0";

		    if (!empty($product_main_category)) {
		        $subquery1 .= " AND pm.fld_product_main_cat_id = " . (int)$product_main_category;
		    }

		    if (!empty($product_category)) {
		        $subquery1 .= " AND pid.fld_product_group_id = " . (int)$product_category;
		    }

			
			if (!empty($product_sub_category)) {
				$subquery1 .= " AND pm.fld_product_sub_category_id = " . (int)$product_sub_category;
			}


		    if (!empty($product_id)) {
		        $subquery1 .= " AND pid.fld_product_master_id = " . (int)$product_id;
		    }

		    $subquery1 .= ")";


		    // ---- Apply WHERE condition ----
		    if (!empty($product_id)) {

		        $this->db->where($subquery1, null, false);

		    } else {

		        // ---- Subquery 2 : tbl_proforma_invoice_other_product_details ----
		        $subquery2 = "EXISTS (
		            SELECT 1
		            FROM tbl_proforma_invoice_other_product_details sopd
		            WHERE sopd.fld_proforma_invoice_id = a.fld_proforma_invoice_id
		            AND sopd.fld_isdeleted = 0";

		        if (!empty($product_category)) {
		            $subquery2 .= " AND sopd.fld_category = " . (int)$product_category;
		        }

		        $subquery2 .= ")";

		        if (!empty($product_main_category)) {
		            $this->db->where($subquery1, null, false);
		        } else {
		            $this->db->where("($subquery1 OR $subquery2)", null, false);
		        }
		    }
		}


	    $this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = a.fld_dealer_id AND dm.fld_isdeleted = 0', 'LEFT');
	     $this->db->join('tbl_admin AS tl', 'tl.fld_id = a.fld_team_lead_id AND tl.fld_isdeleted = 0', 'LEFT');
	    $this->db->join('tbl_admin AS ad', 'ad.fld_id  = a.fld_id AND ad.fld_isdeleted = 0', 'LEFT');
	    $this->db->join('tbl_state_master AS s', 's.fld_state_id  = dm.fld_state_id AND s.fld_isdeleted = 0', 'LEFT');
		$this->db->join('tbl_dist_master AS d', 'd.fld_dist_id = dm.fld_dist_id  AND d.fld_isdeleted = 0', 'LEFT');
		$this->db->join('tbl_taluka_master AS t', 't.fld_taluka_id  = dm.fld_taluka_id  AND t.fld_isdeleted = 0', 'LEFT');
	    $this->db->order_by('a.fld_proforma_invoice_id', 'DESC');

	    $data['result'] = $this->Master_model->getRecords(
		    'tbl_proforma_invoice_master as a',
		    $where,
		    [
		        'a.fld_proforma_invoice_id',
		        'a.fld_order_generated',
		        'a.fld_proforma_invoice_no',
		        'a.fld_po_no',
		        'a.fld_grand_total',
		        'a.fld_dealer_id',
		        'a.fld_id','a.fld_tax_type',
		        'IFNULL(dm.fld_dealer_name, "") as fld_dealer_name',
		        'IFNULL(dm.fld_mobile_no, "") as fld_mobile_no',
		        'DATE_FORMAT(a.fld_proforma_invoice_date, "%d/%m/%Y") as fld_date',
		        'ad.fld_adm_name as employee_name',
		        'tl.fld_adm_name as team_lead',
		        'CONCAT(
		            IFNULL(dm.fld_dealer_address, ""),
		            CASE WHEN dm.fld_dealer_address != "" THEN ", " ELSE "" END,
		            IFNULL(t.fld_taluka_name, ""),
		            CASE WHEN t.fld_taluka_name != "" THEN ", " ELSE "" END,
		            IFNULL(d.fld_dist_name, ""),
		            CASE WHEN d.fld_dist_name != "" THEN ", " ELSE "" END,
		            IFNULL(s.fld_state_name, "")
		        ) AS fld_dealer_address',
		        '(
				    SELECT SUM((lpd.fld_qty * pm.fld_weight) / 1000)
				    FROM tbl_proforma_invoice_details AS lpd
				    LEFT JOIN tbl_product_master AS pm 
				        ON lpd.fld_product_master_id = pm.fld_product_master_id AND pm.fld_isdeleted =0
				    WHERE lpd.fld_isdeleted = 0 
				      AND lpd.fld_proforma_invoice_id = a.fld_proforma_invoice_id
				) fld_weight_kg'
		    ],
		    [],
		    $page,
		    $config["per_page"]
		);
 
	    // ===== Master Data =====
	  
	    $data['sources'] = $this->Master_model->get_source();
        $data['product_category'] = $this->Master_model->getRecords(
	        'tbl_product_category_master', 
	        array('fld_isdeleted !=' => '1'), 
	        'fld_product_group_name,fld_product_group_id', 
	        array('fld_product_group_name' => 'ASC')
	    );
	    $data['main_product_category'] = $this->Master_model->getRecords(
	        'tbl_product_main_category_master', 
	        array('fld_isdeleted !=' => '1'), 
	        'fld_product_main_cat_name,fld_product_main_cat_id', 
	        array('fld_product_main_cat_name' => 'ASC')
	    );
	    $data['middle_content'] = 'superadmin/list_proforma_invoice';
	    $this->load->view('superadmin/common-file', $data);
    }


public function add()
{
	$data['module_id'] = 2;	
	$data['sources'] = $this->Master_model->get_source();
	$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));
	$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));
	$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));
	$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));
	
	// Get GST percentage from software parameter
	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	$data['gst_percentage'] = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? $software_param[0]['fld_gst_percentage'] : 18;
	$data['units'] = $this->Master_model->getRecords('tbl_unit_master', array('fld_isdeleted'=>0),'fld_id,fld_unit', array('fld_unit'=>'ASC'));
	// Get default terms and conditions for Proforma Invoice
	$terms_records = $this->Master_model->getRecords(
		'tbl_term_and_condition_master',
		array('fld_isdeleted' => 0, 'fld_term_cond_for' => 'Proforma Invoice'),
		'fld_term_cond_det',
		array('fld_term_cond_id' => 'DESC')
	);
	$data['proforma_invoice_terms_default'] = !empty($terms_records) ? $terms_records[0]['fld_term_cond_det'] : '';

	$data['middle_content'] = 'superadmin/frm_proforma_invoice';
	$this->load->view('superadmin/common-file',$data);
}


public function add_proforma_invoice_quotation($id)
{
	$loc_edit_id = base64_decode($id);
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = em.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'left');
	$data['edit'] = $this->Master_model->getRecords('tbl_enquiry_master as em', array('em.fld_enquiry_id'=>$loc_edit_id,'em.fld_isdeleted'=>0),'em.fld_enquiry_id,em.fld_lead_id,dm.fld_source_id,em.fld_enq_reference_id,em.fld_id,em.fld_dealer_id,em.fld_enquiry_no,em.fld_enquiry_date as fld_date,dm.fld_email,dm.fld_mobile_no,dm.fld_gst_no, dm.fld_dealer_address,dm.fld_shipping_address,sm.fld_gst_code,dm.fld_state_id as fld_state_id,dm.fld_dist_id as fld_dist_id,dm.fld_taluka_id as fld_taluka_id');

 

	$this->db->select('lpd.fld_prod_enquiry_details_id,lpd.fld_enquiry_id,lpd.fld_product_group_id,lpd.fld_product_master_id,lpd.fld_dealer_id,lpd.fld_qty,lpd.fld_unit,"" as fld_total_amt, pm.fld_product_name, pg.fld_product_group_name,"" as fld_taxable_amt,"" as fld_gst_perc,"" as fld_gst_amt,"" as fld_grand_total,pm.fld_price_excl_gst as fld_rate,pm.fld_gst_percentage,0 as fld_discount_perc,0 as fld_discount_amt,0 as fld_disc_perc,0 as fld_disc_amt');
	$this->db->from('tbl_enquiry_product_details AS lpd');
	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = lpd.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = lpd.fld_product_group_id AND pg.fld_isdeleted = 0', 'left');
	$this->db->where(array('lpd.fld_enquiry_id' => $loc_edit_id, 'lpd.fld_isdeleted' => 0));
	$data['product_det'] = $this->db->get()->result_array();

	// Add packing details for each product
	foreach ($data['product_det'] as &$product) {
		$product_id = $product['fld_product_master_id'];
		
		// Get outer grid data (packing details) with subcategory information
		$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
		$this->db->from('tbl_product_packing_details as pd');
		$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
		$this->db->where('pd.fld_product_master_id', $product_id);
		$this->db->where('pd.fld_isdeleted', 0);
		$this->db->order_by('pd.fld_det_id', 'ASC');
		$packing_query = $this->db->get();
		$packing_details = $packing_query->result_array();
		
		$product['packing_details'] = $packing_details;
	}
	unset($product); // Unset reference to avoid issues

	$this->db->select('cd.*, dm.fld_dealer_name, di.fld_designation_name');
	$this->db->from('tbl_dealer_contact_person_details AS cd');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = cd.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_designation_master AS di', 'di.fld_designation_id = cd.fld_designation_id AND di.fld_isdeleted = 0', 'left'); 
	$this->db->where(array(
		'cd.fld_dealer_id' => $data['edit'][0]['fld_dealer_id'],
		'cd.fld_isdeleted' => 0
	));

	$data['dealer_contact'] = $this->db->get()->result_array();




	$data['sources'] = $this->Master_model->get_source();

	$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));

	$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));


	$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));

	$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));
	
	// Get GST percentage from software parameter
	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	$data['gst_percentage'] = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? $software_param[0]['fld_gst_percentage'] : 18;

	$data['units'] = $this->Master_model->getRecords('tbl_unit_master', array('fld_isdeleted'=>0),'fld_id,fld_unit', array('fld_unit'=>'ASC'));
	
	$data['middle_content'] = 'superadmin/frm_proforma_invoice';
	$this->load->view('superadmin/common-file',$data);
}

public function add_proforma_invoice_from_quotation($id)
{
	$loc_quotation_id = base64_decode($id);

	$data['module_id'] = 2;
	
	// Get quotation master data
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = qm.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS a', 'a.fld_id = qm.fld_id AND a.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS t', 't.fld_id = qm.fld_team_lead_id AND t.fld_isdeleted = 0', 'left');
	$quotation_data = $this->Master_model->getRecords('tbl_quotation_master as qm', array('qm.fld_quotation_id'=>$loc_quotation_id,'qm.fld_isdeleted'=>0),'qm.fld_quotation_id,qm.fld_enquiry_id,qm.fld_id,qm.fld_dealer_id,qm.fld_quotation_no, qm.fld_quotation_date as fld_date,fld_total_amt,qm.fld_discount,qm.fld_grand_total,dm.fld_email,dm.fld_mobile_no,dm.fld_gst_no,dm.fld_dealer_address,dm.fld_shipping_address,sm.fld_gst_code,qm.fld_discount_per,dm.fld_state_id as fld_state_id,dm.fld_dist_id as fld_dist_id,dm.fld_taluka_id as fld_taluka_id,qm.fld_terms_condition,qm.fld_organization,a.fld_adm_name,t.fld_adm_name as team_lead,qm.fld_remark,qm.fld_id,qm.fld_team_lead_id, dm.fld_tax_type');
	 //print_r($quotation_data);die();
	
	if(empty($quotation_data)) {
		$this->session->set_flashdata('error', 'Quotation not found.');
		redirect(base_url().'list-quotation');
		return;
	}
	
	// Structure data for Proforma Invoice form (map quotation fields to proforma invoice fields)
	$data['edit'] = array();
	$data['edit'][0] = array(
		'fld_proforma_invoice_id' => '', // New record
		'fld_date' => $quotation_data[0]['fld_date'],
		'fld_dealer_id' => $quotation_data[0]['fld_dealer_id'],
		'fld_enquiry_id' => $quotation_data[0]['fld_enquiry_id'],
		'fld_total_amt' => $quotation_data[0]['fld_total_amt'],
		'fld_discount' => $quotation_data[0]['fld_discount'],
		'fld_grand_total' => $quotation_data[0]['fld_grand_total'],
		'fld_state_id' => $quotation_data[0]['fld_state_id'],
		'fld_dist_id' => $quotation_data[0]['fld_dist_id'],
		'fld_taluka_id' => $quotation_data[0]['fld_taluka_id'],
		'fld_email' => $quotation_data[0]['fld_email'],
		'fld_mobile_no' => $quotation_data[0]['fld_mobile_no'],
		'fld_gst_no' => $quotation_data[0]['fld_gst_no'],
		'fld_dealer_address' => $quotation_data[0]['fld_dealer_address'],
		'fld_shipping_address' => isset($quotation_data[0]['fld_shipping_address']) ? $quotation_data[0]['fld_shipping_address'] : '',
		'fld_gst_code' => $quotation_data[0]['fld_gst_code'],
		'fld_discount_per' => $quotation_data[0]['fld_discount_per'],
		'fld_adm_name'=> $quotation_data[0]['fld_adm_name'],
		'fld_id'=> $quotation_data[0]['fld_id'],
		'fld_team_lead_id'=> $quotation_data[0]['fld_team_lead_id'],
		'team_lead'=> $quotation_data[0]['team_lead'],
		'fld_remark'=> $quotation_data[0]['fld_remark'],
		'fld_tax_type'=> $quotation_data[0]['fld_tax_type'],
	);
	
	// Get dealer name for display
	$dealer_info = $this->Master_model->getRecords('tbl_dealer_master', array('fld_dealer_id' => $quotation_data[0]['fld_dealer_id']), 'fld_dealer_name');
	if(!empty($dealer_info)) {
		$data['edit'][0]['fld_dealer_name'] = $dealer_info[0]['fld_dealer_name'];
	}
	
	// Get quotation product details
	$this->db->select('lpd.fld_quotation_details_id, lpd.fld_quotation_id, lpd.fld_dealer_id, lpd.fld_product_group_id, lpd.fld_product_master_id, lpd.fld_hsn_code, lpd.fld_qty, lpd.fld_unit, lpd.fld_rate, lpd.fld_prod_gst_incluidng_rate, lpd.fld_total_amt, lpd.fld_disc_perc as fld_discount_perc, lpd.fld_disc_amt as fld_discount_amt, lpd.fld_taxable_amt, lpd.fld_gst_perc as fld_gst_percentage, lpd.fld_gst_amt, lpd.fld_grand_total, lpd.fld_description,lpd.fld_remark, pm.fld_product_name, pg.fld_product_group_name, pm.fld_moq, lpd.fld_packing_id'); 
	$this->db->from('tbl_quotation_details AS lpd');	
	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = lpd.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = lpd.fld_product_group_id AND pg.fld_isdeleted = 0', 'left');
	$this->db->where(array('lpd.fld_quotation_id' => $loc_quotation_id, 'lpd.fld_isdeleted' => 0));
	$quotation_product_result = $this->db->get()->result_array();
	// echo '<pre>';print_r($quotation_product_result);die;

	$this->db->select('pid.fld_product_group_id, pid.fld_product_master_id, pid.fld_qty, pid.fld_total_amt as fld_total_amt, pid.fld_unit, pm.fld_product_name, pm.fld_weight, pid.fld_rate, pid.fld_disc_perc, pid.fld_disc_amt, pid.fld_taxable_amt, pid.fld_gst_perc, pid.fld_gst_amt, pid.fld_grand_total,"" as fld_exp_delivery_date');
	$this->db->from('tbl_proforma_invoice_details AS pid');
	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = pid.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
	$this->db->where(array('pid.fld_proforma_invoice_id' => $loc_quotation_id, 'pid.fld_isdeleted' => 0));
	$data['product_det'] = $this->db->get()->result_array();

	// Other product grid
	// $this->db->select('opd.*, um.fld_unit');
	// $this->db->from('tbl_quotation_other_product_details AS opd');
	// $this->db->join('tbl_unit_master AS um','um.fld_id = opd.fld_unit_id','left');
	// $this->db->where(array('opd.fld_quotation_id' => $loc_quotation_id, 'opd.fld_isdeleted' => 0));
	// $enquiry_other_products = $this->db->get()->result_array();
	// 	if(!empty($enquiry_other_products)){
	// 		foreach($enquiry_other_products as &$sop){
	// 			$sop['fld_other_product_id'] = '';
	// 			$sop['file_source'] = 'quotation';
	// 		}
	// 		unset($sop);
	// 	}
	// $data['other_product_det'] = $enquiry_other_products;

	$this->db->select('opd.*, um.fld_unit');
	$this->db->from('tbl_quotation_other_product_details AS opd');
	$this->db->join('tbl_unit_master AS um','um.fld_id = opd.fld_unit_id','left');
	$this->db->where(array('opd.fld_quotation_id' => $loc_quotation_id, 'opd.fld_isdeleted' => 0));
	$enquiry_other_products = $this->db->get()->result_array();
		if(!empty($enquiry_other_products)){
			foreach($enquiry_other_products as &$sop){
				$sop['fld_other_product_id'] = '';
				$sop['file_source']          = 'quotation';
				// Initialise discount/amount fields so the view does not throw undefined index notices
				if (!isset($sop['fld_other_total_amt'])) $sop['fld_other_total_amt'] = floatval($sop['fld_qty'] ?? 0) * floatval($sop['fld_rate'] ?? 0);
				if (!isset($sop['fld_other_disc_perc'])) $sop['fld_other_disc_perc'] = 0;
				if (!isset($sop['fld_other_disc_amt']))  $sop['fld_other_disc_amt']  = 0;
			}
			unset($sop);
		}
	$data['other_product_det'] = $enquiry_other_products;
	
	// Add packing details for each product
	foreach ($data['product_det'] as &$product) {
		$product_id = $product['fld_product_master_id'];
		
		// Get outer grid data (packing details) with subcategory information
		$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
		$this->db->from('tbl_product_packing_details as pd');
		$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
		$this->db->where('pd.fld_product_master_id', $product_id);
		$this->db->where('pd.fld_isdeleted', 0);
		$this->db->order_by('pd.fld_det_id', 'ASC');
		$packing_query = $this->db->get();
		$packing_details = $packing_query->result_array();
		
		$product['packing_details'] = $packing_details;
	}
	unset($product); // Unset reference to avoid issues
	
	// Pass proforma invoice ID to link it with Order
	$data['loc_quotation_id'] = $loc_quotation_id;

	// echo "<pre>";
	// print_r($data['product_det']);
	// die();
	// Fetch the details from proforma
	$this->db->select('pid.fld_product_master_id, SUM(pid.fld_qty) AS total_converted');
	$this->db->from('tbl_proforma_invoice_master pim');
	$this->db->join('tbl_proforma_invoice_details pid',
		'pid.fld_proforma_invoice_id = pim.fld_proforma_invoice_id AND pid.fld_isdeleted = 0',
		'INNER'
	);
	$this->db->where('pim.fld_quotation_id', $loc_quotation_id);
	$this->db->where('pim.fld_isdeleted', 0);
	$this->db->group_by('pid.fld_product_master_id');

	$proforma_totals = $this->db->get()->result_array();
	// echo '<pre>';print_r($proforma_totals);die;
	$proforma_map = [];

	foreach ($proforma_totals as $row) {
		$proforma_map[$row['fld_product_master_id']] = intval($row['total_converted']);
	}

	$data['product_det'] = [];

	foreach ($quotation_product_result as $product) {

		$quotation_qty = intval($product['fld_qty']);
		$product_id = intval($product['fld_product_master_id']);

		$already_converted = isset($proforma_map[$product_id]) ? $proforma_map[$product_id] : 0;

		$pending_qty = $quotation_qty - $already_converted;

		if ($pending_qty > 0) {

			$product['fld_qty_original'] = $quotation_qty;
			$product['fld_already_converted_qty'] = $already_converted;
			$product['fld_pending_qty'] = $pending_qty;

			// IMPORTANT: override qty to pending
			$product['fld_qty'] = $pending_qty;

			$data['product_det'][] = $product;
		}
	}
	// echo '<pre>';print_r($data['product_det']);die;



	// Add packing details for each product
	foreach ($data['product_det'] as &$product) {
		$product_id = $product['fld_product_master_id'];
		
		// Get outer grid data (packing details) with subcategory information
		$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
		$this->db->from('tbl_product_packing_details as pd');
		$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
		$this->db->where('pd.fld_product_master_id', $product_id);
		$this->db->where('pd.fld_isdeleted', 0);
		$this->db->order_by('pd.fld_det_id', 'ASC');
		$packing_query = $this->db->get();
		$packing_details = $packing_query->result_array();
		
		$product['packing_details'] = $packing_details;
	}
	unset($product); // Unset reference to avoid issues

	// Get dealer contact details
	$this->db->select('cd.*, dm.fld_dealer_name, di.fld_designation_name');
	$this->db->from('tbl_dealer_contact_person_details AS cd');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = cd.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_designation_master AS di', 'di.fld_designation_id = cd.fld_designation_id AND di.fld_isdeleted = 0', 'left'); 
	$this->db->where(array(
		'cd.fld_dealer_id' => $data['edit'][0]['fld_dealer_id'],
		'cd.fld_isdeleted' => 0
	));
	$data['dealer_contact'] = $this->db->get()->result_array();

	// Get required dropdown data
	
	$data['sources'] = $this->Master_model->get_source();
	$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));
	$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));
	$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));
	$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));

	$data['district']      = $this->Master_model->getRecords('tbl_dist_master', array('fld_isdeleted !=' => '1'), 'fld_dist_name,fld_dist_id', array('fld_dist_name' => 'ASC'));

	$data['taluka']      = $this->Master_model->getRecords('tbl_taluka_master', array('fld_isdeleted !=' => '1'), 'fld_taluka_name,fld_taluka_id', array('fld_taluka_name' => 'ASC'));
	
	// Get GST percentage from software parameter
	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	$data['gst_percentage'] = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? $software_param[0]['fld_gst_percentage'] : 18;
	
	// Get default terms and conditions for Proforma Invoice
	$terms_records = $this->Master_model->getRecords(
		'tbl_term_and_condition_master',
		array('fld_isdeleted' => 0, 'fld_term_cond_for' => 'Proforma Invoice'),
		'fld_term_cond_det',
		array('fld_term_cond_id' => 'DESC')
	);
	$data['proforma_invoice_terms_default'] = !empty($terms_records) ? $terms_records[0]['fld_term_cond_det'] : '';
	
	// Pass quotation ID to link it with Proforma Invoice
	$data['quotation_id'] = $loc_quotation_id;
	
	// // Pass quotation terms if available, otherwise use default
	// if (!empty($quotation_data[0]['fld_terms_condition'])) {
	// 	$data['edit'][0]['fld_terms_condition'] = $quotation_data[0]['fld_terms_condition'];
	// }

	// print_r($data['edit']);die();
	$data['edit'][0]['fld_organization']=$quotation_data[0]['fld_organization'];
	$data['from_quotation'] = 1;
	$data['units'] = $this->Master_model->getRecords('tbl_unit_master', array('fld_isdeleted'=>0),'fld_id,fld_unit', array('fld_unit'=>'ASC'));
	// echo "<pre>";print_r($data);die();
	$data['middle_content'] = 'superadmin/frm_proforma_invoice';
	$this->load->view('superadmin/common-file',$data);
}


public function save()
{

	$enquiry_id  = $this->input->post('hid_enquiry_id');
	$quotation_id = $this->input->post('hid_quotation_id'); // Quotation ID when creating from quotation

	// Load form validation library
	$this->load->library('form_validation');

	// Set validation rules
	$this->form_validation->set_rules('sel_organization', 'Organization', 'required', array('required' => 'Please select %s.'));
	$this->form_validation->set_rules('txt_dealer_name', 'Customer Name', 'required', array('required' => 'Please enter %s.'));
	$this->form_validation->set_rules('txt_date', 'Date', 'required', array('required' => 'Please select %s.'));
	$this->form_validation->set_rules('txt_employee_name', 'Sales Executive', 'required');
	$this->form_validation->set_rules('txt_team_leader', 'Team Lead', 'required');

	// $this->form_validation->set_rules('txt_state_id', 'State', 'required', array('required' => 'Please select %s.'));
	// $this->form_validation->set_rules('txt_district_id', 'District', 'required', array('required' => 'Please select %s.'));
	// $this->form_validation->set_rules('sel_taluka', 'Taluka', 'required', array('required' => 'Please select %s.'));

	// Run validation
	if ($this->form_validation->run() == FALSE) {
		// Validation failed, reload the form with errors
		$edit_id = $this->input->post('hid_edit_id');
		
		// Load all necessary data for form reload
		
		$data['sources'] = $this->Master_model->get_source();
		$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));
		$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));
		$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));
		$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));
		
		// Load district and taluka based on POST values if available
		$post_state_id = $this->input->post('txt_state_id');
		$post_district_id = $this->input->post('txt_district_id');
		
		if (!empty($post_state_id)) {
			$data['district'] = $this->Master_model->getRecords('tbl_district_master', array('fld_state_id' => $post_state_id, 'fld_isdeleted' => 0), 'fld_dist_id,fld_dist_name', array('fld_dist_name' => 'ASC'));
		} else {
			$data['district'] = array();
		}
		
		if (!empty($post_district_id)) {
			$data['taluka'] = $this->Master_model->getRecords('tbl_taluka_master', array('fld_dist_id' => $post_district_id, 'fld_isdeleted' => 0), 'fld_taluka_id,fld_taluka_name', array('fld_taluka_name' => 'ASC'));
		} else {
			$data['taluka'] = array();
		}
		
		// Get GST percentage from software parameter
		$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
		$data['gst_percentage'] = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? $software_param[0]['fld_gst_percentage'] : 18;
		
		// Get default terms and conditions for Proforma Invoice
		$terms_records = $this->Master_model->getRecords(
			'tbl_term_and_condition_master',
			array('fld_isdeleted' => 0, 'fld_term_cond_for' => 'Proforma Invoice'),
			'fld_term_cond_det',
			array('fld_term_cond_id' => 'DESC')
		);
		$data['proforma_invoice_terms_default'] = !empty($terms_records) ? $terms_records[0]['fld_term_cond_det'] : '';

		// If edit mode, load existing data
		if (!empty($edit_id)) {
			$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = qm.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
			$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'left');
			$data['edit'] = $this->Master_model->getRecords('tbl_proforma_invoice_master as qm', array('qm.fld_proforma_invoice_id'=>$edit_id,'qm.fld_isdeleted'=>0),'qm.fld_proforma_invoice_id,qm.fld_enquiry_id,qm.fld_id,qm.fld_dealer_id,qm.fld_proforma_invoice_no, qm.fld_proforma_invoice_date as fld_date,fld_total_amt,qm.fld_discount,qm.fld_grand_total,dm.fld_email,dm.fld_mobile_no,dm.fld_gst_no,dm.fld_dealer_address,sm.fld_gst_code,qm.fld_discount_per,qm.fld_packing_forwarding_amt,qm.fld_transportation_amt,dm.fld_state_id as fld_state_id,dm.fld_dist_id as fld_dist_id,dm.fld_taluka_id as fld_taluka_id,qm.fld_terms_condition,qm.fld_shipping_address,qm.fld_po_no,qm.fld_po_date,qm.fld_po_mode,qm.fld_po_attachment,qm.fld_payment_terms, qm.fld_igst_amt, qm.fld_cgst_amt, qm.fld_sgst_amt,qm.fld_organization,qm.fld_tax_type,qm.fld_lut_bond_no,qm.fld_lut_from_date,qm.fld_lut_to_date');

			$this->db->select('lpd.fld_proforma_invoice_details_id, lpd.fld_proforma_invoice_id, lpd.fld_dealer_id, lpd.fld_product_group_id, lpd.fld_product_master_id, lpd.fld_hsn_code, lpd.fld_qty, lpd.fld_unit, lpd.fld_rate, lpd.fld_prod_gst_incluidng_rate, lpd.fld_total_amt, lpd.fld_disc_perc, lpd.fld_disc_amt, lpd.fld_taxable_amt, lpd.fld_gst_perc as fld_gst_percentage, lpd.fld_gst_amt, lpd.fld_grand_total, lpd.fld_description, pm.fld_product_name, pg.fld_product_group_name,lpd.fld_remark, lpd.fld_packing_id, lpd.fld_per_bag_qty, lpd.fld_no_of_bags'); 

			$this->db->from('tbl_proforma_invoice_details AS lpd');
			$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = lpd.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
			$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = lpd.fld_product_group_id AND pg.fld_isdeleted = 0', 'left');
			$this->db->where(array('lpd.fld_proforma_invoice_id' => $edit_id, 'lpd.fld_isdeleted' => 0));
			$data['product_det'] = $this->db->get()->result_array();

			// Add packing details for each product
			foreach ($data['product_det'] as &$product) {
				$product_id = $product['fld_product_master_id'];
				
				$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
				$this->db->from('tbl_product_packing_details as pd');
				$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
				$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
				$this->db->where('pd.fld_product_master_id', $product_id);
				$this->db->where('pd.fld_isdeleted', 0);
				$this->db->order_by('pd.fld_det_id', 'ASC');
				$packing_query = $this->db->get();
				$packing_details = $packing_query->result_array();
				
				$product['packing_details'] = $packing_details;
			}
			unset($product);

			$data['edit_id'] = $edit_id;
			$data['view_mode'] = false;
		} else {
			// Add mode - reconstruct form data from POST to preserve user input
			$data['edit'] = array();
			$data['edit'][0] = array(
				'fld_proforma_invoice_id' => '',
				'fld_date' => $this->input->post('txt_date'),
				'fld_dealer_id' => $this->input->post('hid_dealer_id'),
				'fld_organization' => $this->input->post('sel_organization'),
				'fld_state_id' => $this->input->post('txt_state_id'),
				'fld_dist_id' => $this->input->post('txt_district_id'),
				'fld_taluka_id' => $this->input->post('sel_taluka'),
				'fld_email' => $this->input->post('txt_dealer_email'),
				'fld_mobile_no' => $this->input->post('txt_dealer_mobile'),
				'fld_gst_no' => $this->input->post('txt_dealer_gst_no'),
				'fld_dealer_address' => $this->input->post('txt_dealer_address'),
				'fld_shipping_address' => $this->input->post('txt_shipping_address'),
				'fld_po_no' => $this->input->post('txt_po_no'),
				'fld_po_date' => $this->input->post('txt_po_date'),
				'fld_po_mode' => $this->input->post('sel_po_mode'),
				'fld_total_amt' => $this->input->post('txt_subtotal'),
				'fld_discount' => $this->input->post('txt_discount_amount'),
				'fld_discount_per' => $this->input->post('txt_discount_percentage'),
				'fld_packing_forwarding_amt' => $this->input->post('txt_packing_transportation_amount'),
				'fld_igst_amt' => $this->input->post('txt_igst_amt'),
				'fld_cgst_amt' => $this->input->post('txt_cgst_amt'),
				'fld_sgst_amt' => $this->input->post('txt_sgst_amt'),
				'fld_grand_total' => $this->input->post('txt_grand_total'),
				'fld_terms_condition' => $this->input->post('txt_terms_cond'),
				'fld_lut_bond_no' => $this->input->post('txt_lut_bond_no'),
				'fld_lut_from_date' => $this->input->post('txt_lut_from'),
				'fld_lut_to_date' => $this->input->post('txt_lut_to'),
			);
			
			// Reconstruct product details from POST
			$product_names = $this->input->post('product_name');
			$product_ids = $this->input->post('product_id');
			$prod_group_ids = $this->input->post('prod_group_id');
			$qtys = $this->input->post('qty');
			$rates = $this->input->post('rate');
			$amounts = $this->input->post('amount');
			$disc_percs = $this->input->post('txt_disc_per');
			$disc_amts = $this->input->post('txt_disc_amt');
			
			$data['product_det'] = array();
			if (!empty($product_names) && is_array($product_names)) {
				foreach ($product_names as $index => $product_name) {
					if (!empty($product_name) && !empty($product_ids[$index])) {
						$data['product_det'][] = array(
							'fld_proforma_invoice_details_id' => '',
							'fld_product_group_id' => isset($prod_group_ids[$index]) ? $prod_group_ids[$index] : '',
							'fld_product_master_id' => isset($product_ids[$index]) ? $product_ids[$index] : '',
							'fld_product_name' => $product_name,
							'fld_qty' => isset($qtys[$index]) ? $qtys[$index] : '',
							'fld_rate' => isset($rates[$index]) ? $rates[$index] : '',
							'fld_total_amt' => isset($amounts[$index]) ? $amounts[$index] : '',
							'fld_disc_perc' => isset($disc_percs[$index]) ? $disc_percs[$index] : '',
							'fld_disc_amt' => isset($disc_amts[$index]) ? $disc_amts[$index] : '',
							'fld_unit' => 'Nos',
							'packing_details' => array() // Packing details will be loaded via AJAX if needed
						);
					}
				}
			}
		}

		$data['middle_content'] = 'superadmin/frm_proforma_invoice';
		$this->load->view('superadmin/common-file', $data);
		return; // Stop execution
	}

	    $edit_id = $this->input->post('hid_edit_id');
		$dealer_id = $this->input->post('hid_dealer_id');
		$dealer_name = trim($this->input->post('txt_dealer_name')); // Customer Name
		$source_id = $this->input->post('sel_source');
		$dealer_addresss = $this->input->post('txt_dealer_address'); // Customer Address
		$dealer_mobile = $this->input->post('txt_dealer_mobile'); // Customer Mobile
		$dealer_email = $this->input->post('txt_dealer_email'); // Customer Email
		$dealer_gst_no = $this->input->post('txt_dealer_gst_no'); // Customer GST
		$date = $this->input->post('txt_date');
		$loc_state = $this->input->post('txt_state_id');
		$loc_district = $this->input->post('txt_district_id');
		$loc_taluka = $this->input->post('sel_taluka');
		$loc_employee_id = $this->input->post('hid_employee_id');
        $loc_team_lead_id = $this->input->post('hid_team_leader_id');
        $loc_remark = $this->input->post('txt_remark');
	    // NEW: Proforma Invoice total fields
	$loc_total_amt  = floatval($this->input->post('txt_subtotal'));
	$loc_discount_perc = floatval($this->input->post('txt_discount_percentage'));
	$loc_discount_amt = floatval($this->input->post('txt_discount_amount'));
	$loc_packing_transportation_amount = floatval($this->input->post('txt_packing_transportation_amount'));
	$loc_taxable_amt = floatval($this->input->post('txt_taxable_amount'));
	// $loc_igst_per   = floatval($this->input->post('txt_gst_per'));
	$loc_tax_type   = $this->input->post('txt_tax_type');

	$loc_lut_bond_no = $this->input->post('txt_lut_bond_no');
	$loc_lut_from    = $this->input->post('txt_lut_from');
	$loc_lut_to      = $this->input->post('txt_lut_to');

    $loc_igst_per   = ($loc_tax_type == '1') ? 0 : floatval($this->input->post('txt_gst_per'));
	$loc_igst_amt   = floatval($this->input->post('txt_igst_amt'));
	$loc_cgst_amt = floatval($this->input->post('txt_cgst_amt'));
	$loc_sgst_amt = floatval($this->input->post('txt_sgst_amt'));
	$loc_total_before_round = floatval($this->input->post('txt_total_before_round'));
	$loc_round_off = floatval($this->input->post('txt_round_off'));
	$loc_grand_total = floatval($this->input->post('txt_grand_total'));
	$loc_tds = $this->input->post('txt_tds');	
	$loc_hsn_distributed_json = $this->input->post('txt_hsn_distributed_json');
	$dealer_addresss = $this->input->post('txt_dealer_address');
	$dealer_shipping_address = $this->input->post('txt_shipping_address');
	$dealer_mobile = $this->input->post('txt_dealer_mobile');
	$dealer_email = $this->input->post('txt_dealer_email');
	$dealer_gst_no = $this->input->post('txt_dealer_gst_no');
	$loc_organization = $this->input->post('sel_organization');
	$loc_tds_per = $this->input->post('txt_tds_per');
	
	// PO fields
	$loc_po_no = $this->input->post('txt_po_no');
	$loc_po_date = $this->input->post('txt_po_date');
	$loc_po_mode = $this->input->post('sel_po_mode');
	$loc_payment_terms = $this->input->post('sel_payment_terms');
	
	// Handle PO Attachment file upload
	$po_attachment = '';
	$upload_path = './uploads/po_attachments/';
	if (!is_dir($upload_path)) {
		mkdir($upload_path, 0777, true);
	}

	if (!empty($_FILES['file_attach_po']['name'])) {
		$config['upload_path']   = $upload_path;
		$config['allowed_types'] = 'pdf|jpg|jpeg|png|gif';
		$config['max_size']      = 5120; // 5MB in KB
		$config['file_name']     = 'pi_po_' . time() . '_' . $_FILES['file_attach_po']['name'];
		
		$this->load->library('upload', $config);
		$this->upload->initialize($config);
		
		if ($this->upload->do_upload('file_attach_po')) {
			$upload_data = $this->upload->data();
			$po_attachment = $upload_data['file_name'];
		} else {
			$error = $this->upload->display_errors();
			$this->session->set_flashdata('error', 'File upload failed: ' . $error);
			redirect(base_url().(!empty($edit_id) ? 'edit-proforma-invoice/'.base64_encode($edit_id) : 'add-proforma-invoice'));
			return;
		}
	}
	 
	    // Insert dealer if new
	if($dealer_id == '' && $dealer_name != '') {
		$insDealer = array(
				'fld_dealer_name' => $dealer_name,
				'fld_dealer_type' => 'Dealer',
				'fld_status' => 'Active',
				'fld_registration_type' => 'NEW',
				'fld_created_date' => date('Y-m-d'),
				'fld_system_date' => time(),
				'fld_source_id'=>$source_id,
				'fld_dealer_address'=>$dealer_addresss,
				'fld_mobile_no'=>$dealer_mobile,
				'fld_email'=>$dealer_email,
				'fld_gst_no'=>$dealer_gst_no,
				'fld_state_id'=>$loc_state,
				'fld_dist_id'=>$loc_district,
				'fld_customer_type'=>'Pre Customer',
				'fld_employee_id' => $loc_employee_id,
				'fld_taluka_id'=>$loc_taluka,
				'fld_team_leader_id'=>$loc_team_lead_id
			);
		$this->Master_model->insertRecord('tbl_dealer_master', $insDealer);
		$dealer_id = $this->db->insert_id();
	}

	$terms_condition = $this->input->post('txt_terms_cond');
	
	$input = [
		'fld_proforma_invoice_date' => date('Y-m-d', strtotime(str_replace('/', '-', $date))),
		'fld_enquiry_id'     => $enquiry_id != "" ? $enquiry_id : '',
		'fld_quotation_id'     => $quotation_id != "" ? $quotation_id : '',
		'fld_dealer_id'      => $dealer_id,
		'fld_total_amt'      => $loc_total_amt,
		'fld_discount_per'   => $loc_discount_perc,
		'fld_discount'       => $loc_discount_amt,
		'fld_packing_forwarding_amt' => $loc_packing_transportation_amount,
		'fld_transportation_amt'  => 0,
		'fld_gst_per'       => $loc_igst_per,
		'fld_igst_amt'       => $loc_igst_amt,
		'fld_cgst_amt'       => $loc_cgst_amt,
		'fld_sgst_amt'       => $loc_sgst_amt,
		'fld_sub_total2'     => $loc_total_before_round,
		'fld_round_off'      => $loc_round_off,
		'fld_tds'            => $loc_tds,
		'fld_grand_total'    => $loc_grand_total,
		'fld_terms_condition' => $terms_condition,
		'fld_shipping_address' => $dealer_shipping_address,
		'fld_po_no'          => $loc_po_no,
		'fld_po_date'        => !empty($loc_po_date) ? date_format_db($loc_po_date) : '0000-00-00',
		'fld_po_mode'        => $loc_po_mode,
		'fld_payment_terms'  => $loc_payment_terms,
		'fld_id'    		 =>$loc_employee_id,
	    'fld_team_lead_id'   =>$loc_team_lead_id,
	    'fld_remark'         =>$loc_remark,
	    'fld_tax_type'         =>$loc_tax_type,
		'fld_lut_bond_no' => ($loc_tax_type == '1') ? $loc_lut_bond_no : '',
		'fld_lut_from_date' => ($loc_tax_type == '1' && !empty($loc_lut_from))
			? date_format_db($loc_lut_from)
			: NULL,

		'fld_lut_to_date' => ($loc_tax_type == '1' && !empty($loc_lut_to))
			? date_format_db($loc_lut_to)
			: NULL,
		'fld_tds_per'        => $loc_tds_per
	];

	// Ensure tbl_proforma_invoice_master has fld_hsn_distributed_json (same behavior as Tax Invoice)
	if (!$this->db->field_exists('fld_hsn_distributed_json', 'tbl_proforma_invoice_master')) {
		$this->db->query("ALTER TABLE `tbl_proforma_invoice_master` ADD `fld_hsn_distributed_json` LONGTEXT NULL");
	}
	$input['fld_hsn_distributed_json'] = $loc_hsn_distributed_json;

	// echo '<pre>';print_r($input);die;
	
	// Add PO attachment only if new file uploaded
	if (!empty($po_attachment)) {
		$input['fld_po_attachment'] = $po_attachment;
	}

	    // === EDIT MODE ===
	if ($edit_id != '') {
		// For update, handle existing file if no new file uploaded
		if (empty($po_attachment)) {
			// Keep existing file - don't update the field
			// Get existing file name from database
			$existing_proforma = $this->Master_model->getRecords('tbl_proforma_invoice_master', array('fld_proforma_invoice_id' => $edit_id), 'fld_po_attachment');
			if (!empty($existing_proforma) && !empty($existing_proforma[0]['fld_po_attachment'])) {
				$input['fld_po_attachment'] = $existing_proforma[0]['fld_po_attachment'];
			}
		} else {
			// Delete old file if new file uploaded
			$existing_proforma = $this->Master_model->getRecords('tbl_proforma_invoice_master', array('fld_proforma_invoice_id' => $edit_id), 'fld_po_attachment');
			if (!empty($existing_proforma) && !empty($existing_proforma[0]['fld_po_attachment'])) {
				$old_file = $upload_path . $existing_proforma[0]['fld_po_attachment'];
				if (file_exists($old_file)) {
					@unlink($old_file);
				}
			}
		}
		
		$input['fld_updated_by']   = $this->session->userdata('JYOTI_SES_ADM_ID'.SES_CONSTANT);
		$input['fld_updated_date'] = date('Y-m-d');

		$this->Master_model->updateRecord('tbl_proforma_invoice_master', $input, ['fld_proforma_invoice_id' => $edit_id]);

	        // Update product rows
		$this->save_product_rows($edit_id, $dealer_id);
		$this->save_other_product_rows($edit_id, $dealer_id);
	        // $this->save_contact_rows($dealer_id);

		$this->session->set_flashdata('success', 'Records Updated successfully');
		redirect(base_url().'list-proforma-invoice');
	}

	    // === NEW INSERT ===
	// Update quotation master to mark Proforma Invoice as generated
	if(!empty($quotation_id)){
		$update_quotation = [
			'fld_proforma_invoice_generated' => 1,
			'fld_updated_by'          => $this->session->userdata('JYOTI_SES_ADM_ID'.SES_CONSTANT),
			'fld_updated_date'        => date('Y-m-d'),
		];
		$this->Master_model->updateRecord('tbl_quotation_master', $update_quotation, ['fld_quotation_id' => $quotation_id]);
	} elseif(!empty($enquiry_id)){
		// Fallback: if enquiry_id is provided (for backward compatibility)
		$update_suspect = [
			'fld_proforma_invoice_generated' => 1,
			'fld_updated_by'          => $this->session->userdata('JYOTI_SES_ADM_ID'.SES_CONSTANT),
			'fld_updated_date'        => date('Y-m-d'),
		];
		$this->Master_model->updateRecord('tbl_quotation_master', $update_suspect, ['fld_quotation_id' => $enquiry_id]);
	}

// Generate Proforma Invoice Number with financial year
	$this->load->helper('custom_functions_helper');
	// $fy_details = get_financial_year_details("", "Yes");
	// $fy_year_short = '';
	// if (!empty($fy_details)) {
	// 	$from_year = date('Y', strtotime($fy_details['fld_fy_from_date']));
	// 	$to_year = date('Y', strtotime($fy_details['fld_fy_to_date']));
	// 	// Convert to short format: 2024-2025 -> 24-25
	// 	$fy_year_short = substr($from_year, -2) . '-' . substr($to_year, -2);
	// }
	
	// $this->db->select('fld_last_count + 1 AS count', false);
	// $this->db->where('fld_parameter_name', 'Proforma Invoice Master');
	// $counter = $this->Master_model->getRecords('tbl_counter_master');
	// $count = (!empty($counter) && $counter[0]['count'] > 0) ? $counter[0]['count'] : 1;
	// // Format: JC/PI/24-25/001
	// $count_padded = str_pad($count, 3, '0', STR_PAD_LEFT);
	// $proformaInvoiceNo = !empty($fy_year_short) ? ('JC/PI/' . $fy_year_short . '/' . $count_padded) : ('JC/PI/' . $count_padded);

	$this->db->select('fld_last_count+1 AS count', false);
	$this->db->where('fld_parameter_name', 'Proforma Invoice Master');
	$counter = $this->Master_model->getRecords('tbl_counter_master');
	$count = (!empty($counter) && $counter[0]['count'] > 0) ? $counter[0]['count'] : 1;

	$proformaInvoiceNo = get_prefix('Proforma Invoice Master');
	// echo "<pre>";print_r($proformaInvoiceNo);die();

	$input['fld_proforma_invoice_no']  = $proformaInvoiceNo;
	$input['fld_organization']  = $loc_organization;
	$input['fld_created_by']    = $this->session->userdata('JYOTI_SES_ADM_ID'.SES_CONSTANT);
	$input['fld_created_date']  = date('Y-m-d');
	$input['fld_system_date']   = time();
 	// echo "<pre>";print_r($input);die;
	$this->Master_model->insertRecord('tbl_proforma_invoice_master', $input);
	$inserted = $this->db->insert_id();

	if ($inserted && !empty($counter)) {
		$this->Master_model->updateRecord('tbl_counter_master',
			['fld_last_count' => $count],
			['fld_parameter_name' => 'Proforma Invoice Master']
		);
	}
	 
	    // Save Product Details
	$this->save_product_rows($inserted, $dealer_id);
	$this->save_other_product_rows($inserted, $dealer_id);
	    // $this->save_contact_rows($dealer_id);

	$this->session->set_flashdata('success', 'Records Inserted successfully');
	redirect(base_url().'list-proforma-invoice');
}


public function view($id='')
{
	$loc_edit_id = base64_decode($id);
	$data["loc_access"] = $this->Master_model->getAccessStatus('Proforma Invoice Master');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = qm.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS a', 'a.fld_id = qm.fld_id AND a.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS t', 't.fld_id = qm.fld_team_lead_id AND t.fld_isdeleted = 0', 'left');
	$data['edit'] = $this->Master_model->getRecords('tbl_proforma_invoice_master as qm', array('qm.fld_proforma_invoice_id'=>$loc_edit_id,'qm.fld_isdeleted'=>0),'qm.fld_proforma_invoice_id,qm.fld_enquiry_id,qm.fld_id,qm.fld_dealer_id,qm.fld_proforma_invoice_no,qm.fld_tds,qm.fld_tds_per,qm.fld_proforma_invoice_date as fld_date,fld_total_amt,qm.fld_discount,qm.fld_grand_total,dm.fld_email,dm.fld_mobile_no,dm.fld_gst_no,dm.fld_dealer_address,sm.fld_gst_code,qm.fld_discount_per,qm.fld_packing_forwarding_amt,qm.fld_transportation_amt,dm.fld_state_id as fld_state_id,dm.fld_dist_id as fld_dist_id,dm.fld_taluka_id as fld_taluka_id,qm.fld_terms_condition,qm.fld_shipping_address,qm.fld_po_no,qm.fld_po_date,qm.fld_po_mode,qm.fld_po_attachment,qm.fld_payment_terms, qm.fld_igst_amt, qm.fld_cgst_amt, qm.fld_sgst_amt,qm.fld_organization,a.fld_adm_name,t.fld_adm_name as team_lead,qm.fld_remark,qm.fld_id,qm.fld_team_lead_id, qm.fld_gst_per, qm.fld_sub_total2, qm.fld_round_off,qm.fld_tax_type,qm.fld_lut_bond_no,qm.fld_lut_from_date,qm.fld_lut_to_date');
	


	$this->db->select('lpd.fld_proforma_invoice_details_id, lpd.fld_proforma_invoice_id, lpd.fld_dealer_id, lpd.fld_product_group_id, lpd.fld_product_master_id, lpd.fld_hsn_code, lpd.fld_qty, lpd.fld_unit, lpd.fld_rate, lpd.fld_prod_gst_incluidng_rate, lpd.fld_total_amt, lpd.fld_disc_perc, lpd.fld_disc_amt, lpd.fld_taxable_amt, lpd.fld_gst_perc as fld_gst_percentage, lpd.fld_gst_amt, lpd.fld_grand_total, lpd.fld_description, pm.fld_product_name, pg.fld_product_group_name,lpd.fld_remark, lpd.fld_packing_id, lpd.fld_per_bag_qty, lpd.fld_no_of_bags'); 

	$this->db->from('tbl_proforma_invoice_details AS lpd');
	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = lpd.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = lpd.fld_product_group_id AND pg.fld_isdeleted = 0', 'left');
	$this->db->where(array('lpd.fld_proforma_invoice_id' => $loc_edit_id, 'lpd.fld_isdeleted' => 0));
	$data['product_det'] = $this->db->get()->result_array();

	// $this->db->select('opd.fld_other_product_id, opd.fld_proforma_id, opd.fld_category, opd.fld_product_name, opd.fld_hsn_code, opd.fld_qty, opd.fld_unit_id, um.fld_unit, opd.fld_moq, opd.fld_rate, opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo'); 
	// $this->db->from('tbl_proforma_other_product_details AS opd');
	// $this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	// $this->db->where(array('opd.fld_proforma_id' => $loc_edit_id, 'opd.fld_isdeleted' => 0));
	// $data['other_product_det'] = $this->db->get()->result_array();
	$this->db->select('opd.fld_other_product_id, opd.fld_proforma_id, opd.fld_category, opd.fld_product_name, opd.fld_hsn_code, opd.fld_qty, opd.fld_unit_id, um.fld_unit, opd.fld_moq, opd.fld_rate, opd.fld_other_total_amt, opd.fld_other_disc_perc, opd.fld_other_disc_amt, opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo'); 
	$this->db->from('tbl_proforma_other_product_details AS opd');
	$this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	$this->db->where(array('opd.fld_proforma_id' => $loc_edit_id, 'opd.fld_isdeleted' => 0));
	$data['other_product_det'] = $this->db->get()->result_array();
	// echo "<pre>";print_r($data['other_product_det']);die;
	$data['units'] = $this->Master_model->getRecords('tbl_unit_master', array('fld_isdeleted'=>0),'fld_id,fld_unit', array('fld_unit'=>'ASC'));
	// Add packing details for each product
	foreach ($data['product_det'] as &$product) {
		$product_id = $product['fld_product_master_id'];
		
		// Get outer grid data (packing details) with subcategory information
		$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
		$this->db->from('tbl_product_packing_details as pd');
		$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
		$this->db->where('pd.fld_product_master_id', $product_id);
		$this->db->where('pd.fld_isdeleted', 0);
		$this->db->order_by('pd.fld_det_id', 'ASC');
		$packing_query = $this->db->get();
		$packing_details = $packing_query->result_array();
		
		$product['packing_details'] = $packing_details;
	}
	unset($product); // Unset reference to avoid issues

	$this->db->select('cd.*, dm.fld_dealer_name, di.fld_designation_name');
	$this->db->from('tbl_dealer_contact_person_details AS cd');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = cd.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_designation_master AS di', 'di.fld_designation_id = cd.fld_designation_id AND di.fld_isdeleted = 0', 'left'); 
	$this->db->where(array(
		'cd.fld_dealer_id' => $data['edit'][0]['fld_dealer_id'],
		'cd.fld_isdeleted' => 0
	));

	$data['dealer_contact'] = $this->db->get()->result_array();

	$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));

	$data['district']      = $this->Master_model->getRecords('tbl_dist_master', array('fld_isdeleted !=' => '1'), 'fld_dist_name,fld_dist_id', array('fld_dist_name' => 'ASC'));

	$data['taluka']      = $this->Master_model->getRecords('tbl_taluka_master', array('fld_isdeleted !=' => '1'), 'fld_taluka_name,fld_taluka_id', array('fld_taluka_name' => 'ASC'));
	$data['sources'] = $this->Master_model->get_source();
	$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));
	$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));
	$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));

	// Get GST percentage from software parameter
	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	$data['gst_percentage'] = !empty($data['edit'][0]['fld_gst_per']) && isset($data['edit'][0]['fld_gst_per']) ? $data['edit'][0]['fld_gst_per'] : $software_param[0]['fld_gst_percentage'];
	// Get default terms and conditions for Proforma Invoice
	$terms_records = $this->Master_model->getRecords(
		'tbl_term_and_condition_master',
		array('fld_isdeleted' => 0, 'fld_term_cond_for' => 'Proforma Invoice'),
		'fld_term_cond_det',
		array('fld_term_cond_id' => 'DESC')
	);
	$data['proforma_invoice_terms_default'] = !empty($terms_records) ? $terms_records[0]['fld_term_cond_det'] : '';
	$data['from_quotation'] = 1;
	$data['edit_id'] = $loc_edit_id;
	$data['view_mode'] = true; // Flag to indicate view-only mode
	// echo "<pre>";print_r($data['other_product_det']);die;
	$data['middle_content'] = 'superadmin/frm_proforma_invoice';
	$this->load->view('superadmin/common-file',$data);
}

public function edit($id='')
{

	$loc_edit_id = base64_decode($id);
	$data["loc_access"] = $this->Master_model->getAccessStatus('Proforma Invoice Master');
	$data['module_id'] = 2;
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = qm.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS a', 'a.fld_id = qm.fld_id AND a.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_admin AS t', 't.fld_id = qm.fld_team_lead_id AND t.fld_isdeleted = 0', 'left');
	$data['edit'] = $this->Master_model->getRecords('tbl_proforma_invoice_master as qm', array('qm.fld_proforma_invoice_id'=>$loc_edit_id,'qm.fld_isdeleted'=>0),'qm.fld_proforma_invoice_id,qm.fld_enquiry_id,qm.fld_id,qm.fld_dealer_id,qm.fld_proforma_invoice_no,qm.fld_tds,qm.fld_tds_per, qm.fld_proforma_invoice_date as fld_date,fld_total_amt,qm.fld_discount,qm.fld_grand_total,dm.fld_email,dm.fld_mobile_no,dm.fld_gst_no,dm.fld_dealer_address,sm.fld_gst_code,qm.fld_discount_per,qm.fld_packing_forwarding_amt,qm.fld_transportation_amt,dm.fld_state_id as fld_state_id,dm.fld_dist_id as fld_dist_id,dm.fld_taluka_id as fld_taluka_id,qm.fld_terms_condition,qm.fld_shipping_address,qm.fld_po_no,qm.fld_po_date,qm.fld_po_mode,qm.fld_po_attachment,qm.fld_payment_terms, qm.fld_igst_amt, qm.fld_cgst_amt, qm.fld_sgst_amt,qm.fld_organization,a.fld_adm_name,t.fld_adm_name as team_lead,qm.fld_remark,qm.fld_id,qm.fld_team_lead_id, qm.fld_gst_per, qm.fld_sub_total2, qm.fld_round_off,qm.fld_tax_type,qm.fld_lut_bond_no,qm.fld_lut_from_date,qm.fld_lut_to_date');

	$this->db->select('lpd.fld_proforma_invoice_details_id, lpd.fld_proforma_invoice_id, lpd.fld_dealer_id, lpd.fld_product_group_id, lpd.fld_product_master_id, lpd.fld_hsn_code, lpd.fld_qty, lpd.fld_unit, lpd.fld_rate, lpd.fld_prod_gst_incluidng_rate, lpd.fld_total_amt, lpd.fld_disc_perc, lpd.fld_disc_amt, lpd.fld_taxable_amt, lpd.fld_gst_perc as fld_gst_percentage, lpd.fld_gst_amt, lpd.fld_grand_total, lpd.fld_description, pm.fld_product_name, pg.fld_product_group_name,lpd.fld_remark, lpd.fld_packing_id, lpd.fld_per_bag_qty, lpd.fld_no_of_bags'); 

	$this->db->from('tbl_proforma_invoice_details AS lpd');
	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = lpd.fld_product_master_id AND pm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = lpd.fld_product_group_id AND pg.fld_isdeleted = 0', 'left');
	$this->db->where(array('lpd.fld_proforma_invoice_id' => $loc_edit_id, 'lpd.fld_isdeleted' => 0));
	$data['product_det'] = $this->db->get()->result_array();

	// $this->db->select('opd.fld_other_product_id, opd.fld_proforma_id, opd.fld_category, opd.fld_product_name, opd.fld_hsn_code, opd.fld_qty, opd.fld_unit_id, um.fld_unit, opd.fld_moq, opd.fld_rate, opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo'); 
	// $this->db->from('tbl_proforma_other_product_details AS opd');
	// $this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	// $this->db->where(array('opd.fld_proforma_id' => $loc_edit_id, 'opd.fld_isdeleted' => 0));
	// $data['other_product_det'] = $this->db->get()->result_array();

	$this->db->select('opd.fld_other_product_id, opd.fld_proforma_id, opd.fld_category, opd.fld_product_name, opd.fld_hsn_code, opd.fld_qty, opd.fld_unit_id, um.fld_unit, opd.fld_moq, opd.fld_rate, opd.fld_other_total_amt, opd.fld_other_disc_perc, opd.fld_other_disc_amt, opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo'); 
	$this->db->from('tbl_proforma_other_product_details AS opd');
	$this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	$this->db->where(array('opd.fld_proforma_id' => $loc_edit_id, 'opd.fld_isdeleted' => 0));
	$data['other_product_det'] = $this->db->get()->result_array();
	// echo $this->db->last_query();die;
	// echo "<pre>";print_r($data['product_det']);die;

	// Add packing details for each product
	foreach ($data['product_det'] as &$product) {
		$product_id = $product['fld_product_master_id'];
		
		// Get outer grid data (packing details) with subcategory information
		$this->db->select('pd.fld_det_id, pd.fld_packing_qty, pd.fld_material_id, rm.fld_rm_item_name as packing_material, rsc.fld_rm_sub_category_master_name as subcategory_name');
		$this->db->from('tbl_product_packing_details as pd');
		$this->db->join('tbl_rm_item_master as rm', 'rm.fld_rm_item_master_id = pd.fld_material_id AND rm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_rm_sub_category_master as rsc', 'rsc.fld_rm_sub_category_master_id = rm.fld_rm_sub_category_master_id AND rsc.fld_isdeleted = 0', 'left');
		$this->db->where('pd.fld_product_master_id', $product_id);
		$this->db->where('pd.fld_isdeleted', 0);
		$this->db->order_by('pd.fld_det_id', 'ASC');
		$packing_query = $this->db->get();
		$packing_details = $packing_query->result_array();
		
		$product['packing_details'] = $packing_details;
	}
	unset($product); // Unset reference to avoid issues

		// echo "<pre>".$this->db->last_query();die;

	$this->db->select('cd.*, dm.fld_dealer_name, di.fld_designation_name');
	$this->db->from('tbl_dealer_contact_person_details AS cd');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = cd.fld_dealer_id AND dm.fld_isdeleted = 0', 'left');
	$this->db->join('tbl_designation_master AS di', 'di.fld_designation_id = cd.fld_designation_id AND di.fld_isdeleted = 0', 'left'); 
	$this->db->where(array(
		'cd.fld_dealer_id' => $data['edit'][0]['fld_dealer_id'],
		'cd.fld_isdeleted' => 0
	));

	$data['dealer_contact'] = $this->db->get()->result_array();



	$data['state'] = $this->Master_model->getRecords('tbl_state_master', array('fld_isdeleted' => 0), '', array('fld_state_name' => 'ASC'));

	$data['district']      = $this->Master_model->getRecords('tbl_dist_master', array('fld_isdeleted !=' => '1'), 'fld_dist_name,fld_dist_id', array('fld_dist_name' => 'ASC'));

	$data['taluka']      = $this->Master_model->getRecords('tbl_taluka_master', array('fld_isdeleted !=' => '1'), 'fld_taluka_name,fld_taluka_id', array('fld_taluka_name' => 'ASC'));
	
	$data['sources'] = $this->Master_model->get_source();

	$data['designations'] = $this->Master_model->getRecords('tbl_designation_master', array('fld_isdeleted' => 0),'', array('fld_designation_name'=>'ASC'));

	$data['product_groups'] = $this->Master_model->getRecords('tbl_product_category_master', array('fld_isdeleted'=>0),'', array('fld_product_group_name'=>'ASC'));

	$data['products'] = $this->Master_model->getRecords('tbl_product_master', array('fld_isdeleted'=>0),'', array('fld_product_name'=>'ASC'));

	// Get GST percentage from software parameter
	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	$data['gst_percentage'] = !empty($data['edit'][0]['fld_gst_per']) && isset($data['edit'][0]['fld_gst_per']) ? $data['edit'][0]['fld_gst_per'] : $software_param[0]['fld_gst_percentage'];
	
	// Get default terms and conditions for Proforma Invoice
	$terms_records = $this->Master_model->getRecords(
		'tbl_term_and_condition_master',
		array('fld_isdeleted' => 0, 'fld_term_cond_for' => 'Proforma Invoice'),
		'fld_term_cond_det',
		array('fld_term_cond_id' => 'DESC')
	);
	$data['proforma_invoice_terms_default'] = !empty($terms_records) ? $terms_records[0]['fld_term_cond_det'] : '';
	$data['from_quotation'] = 1;
	// echo "<pre>";print_r($data);die;
	$data['edit_id'] = $loc_edit_id;
	$data['view_mode'] = false; // Edit mode
	$data['units'] = $this->Master_model->getRecords('tbl_unit_master', array('fld_isdeleted'=>0),'fld_id,fld_unit', array('fld_unit'=>'ASC'));
	$data['middle_content'] = 'superadmin/frm_proforma_invoice';
	$this->load->view('superadmin/common-file',$data);
}

public function delete($id='')
{
	$loc_id = base64_decode($id);
	$data["loc_access"] = $this->Master_model->getAccessStatus('Proforma Invoice Master');
	$this->Master_model->updateRecord('tbl_proforma_invoice_master', array('fld_isdeleted'=>1), array('fld_proforma_invoice_id'=>$loc_id));
	$this->session->set_flashdata('success', 'Record deleted successfully');
	redirect(base_url().'list-proforma-invoice');
}

public function rptr_proforma_invoice($type="")
{	
	$data["type"] = base64_decode($type);
	$product_main_category = $this->session->userdata('search_main_product_category');
	 $product_category = $this->session->userdata('search_product_category');
	 $product_id       = $this->session->userdata('search_product_id');
	// Apply customer visibility filter -jayesh on 28/11/2025
	apply_customer_visibility_filter('a', 'fld_id', 'fld_team_lead_id');
	if (!empty($product_category) || !empty($product_id) || !empty($product_main_category)) {

    // ---- Subquery 1 : tbl_proforma_invoice_details ----
	    $subquery1 = "EXISTS (
	        SELECT 1
	        FROM tbl_proforma_invoice_details pid
	        LEFT JOIN tbl_product_master pm 
	            ON pid.fld_product_master_id = pm.fld_product_master_id 
	            AND pm.fld_isdeleted = 0
	        WHERE pid.fld_proforma_invoice_id = a.fld_proforma_invoice_id
	        AND pid.fld_isdeleted = 0";

	    if (!empty($product_main_category)) {
	        $subquery1 .= " AND pm.fld_product_main_cat_id = " . (int)$product_main_category;
	    }

	    if (!empty($product_category)) {
	        $subquery1 .= " AND pid.fld_product_group_id = " . (int)$product_category;
	    }

	    if (!empty($product_id)) {
	        $subquery1 .= " AND pid.fld_product_master_id = " . (int)$product_id;
	    }

	    $subquery1 .= ")";


	    // ---- Apply WHERE condition ----
	    if (!empty($product_id)) {

	        $this->db->where($subquery1, null, false);

	    } else {

	        // ---- Subquery 2 : tbl_proforma_invoice_other_product_details ----
	        $subquery2 = "EXISTS (
	            SELECT 1
	            FROM tbl_proforma_invoice_other_product_details sopd
	            WHERE sopd.fld_proforma_invoice_id = a.fld_proforma_invoice_id
	            AND sopd.fld_isdeleted = 0";

	        if (!empty($product_category)) {
	            $subquery2 .= " AND sopd.fld_category = " . (int)$product_category;
	        }

	        $subquery2 .= ")";

	        if (!empty($product_main_category)) {
	            $this->db->where($subquery1, null, false);
	        } else {
	            $this->db->where("($subquery1 OR $subquery2)", null, false);
	        }
	    }
	}
	 if ($this->session->userdata('search_proforma_invoice_dealer_id') != '') {
    	$where['a.fld_dealer_id'] = $this->session->userdata('search_proforma_invoice_dealer_id');
    }
  
    if ($this->session->userdata('search_source') != '') {
    	$where['a.fld_source_id'] = $this->session->userdata('search_source');
    }
    if ($this->session->userdata('search_proforma_invoice_no') != '') {
    	$where['a.fld_proforma_invoice_no'] = $this->session->userdata('search_proforma_invoice_no');
    }
    // $this->db->where('a.fld_isdeleted', 0);

	if ($this->session->userdata('search_po_no') != '') {
	    $this->db->like('a.fld_po_no', $this->session->userdata('search_po_no'), 'both');
	}
    if ($this->session->userdata('search_order_generated') != '') {
    	$where['a.fld_order_generated'] = $this->session->userdata('search_order_generated');
    }
	if ($this->session->userdata('search_tax_type') != '') {
	    	$where['a.fld_tax_type'] = $this->session->userdata('search_tax_type');
	    }
    if ($this->session->userdata('search_from_date') != '' && $this->session->userdata('search_to_date') != '') {
    	$where['a.fld_proforma_invoice_date >='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_from_date'))));
    	$where['a.fld_proforma_invoice_date <='] = date('Y-m-d', strtotime(str_replace('/', '-', $this->session->userdata('search_to_date'))));
    }
    if ($this->session->userdata('search_proforma_invoice_employee_id') != '') {
	    $where['a.fld_id'] = $this->session->userdata('search_proforma_invoice_employee_id');
	}

	if ($this->session->userdata('search_team_leader_id') != '') {
	        $this->db->where('a.fld_team_lead_id', $this->session->userdata('search_team_leader_id'));
	}

    if(isset($where)) {
      $this->db->where($where);
    }

    
    $this->db->join('tbl_admin AS ad', 'ad.fld_id  = a.fld_id AND ad.fld_isdeleted = 0', 'LEFT');
     $this->db->join('tbl_admin AS tl', 'tl.fld_id  = a.fld_team_lead_id AND tl.fld_isdeleted = 0', 'LEFT');
	$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = a.fld_dealer_id and dm.fld_isdeleted = 0', 'LEFT');
	$this->db->join('tbl_state_master AS s', 's.fld_state_id  = dm.fld_state_id AND s.fld_isdeleted = 0', 'LEFT');
    $this->db->join('tbl_dist_master AS d', 'd.fld_dist_id = dm.fld_dist_id  AND d.fld_isdeleted = 0', 'LEFT');
	$this->db->join('tbl_taluka_master AS t', 't.fld_taluka_id  = dm.fld_taluka_id  AND t.fld_isdeleted = 0', 'LEFT');
	$this->db->order_by('a.fld_proforma_invoice_id', 'DESC');
		$data['result'] = $this->Master_model->getRecords(
	    'tbl_proforma_invoice_master as a',
	    array('a.fld_isdeleted' => 0),
	    array(
	        'a.fld_proforma_invoice_no',
	        'a.fld_order_generated',
	        'a.fld_grand_total',
	        'IFNULL(dm.fld_dealer_name, "") as fld_dealer_name',
	        'DATE_FORMAT(a.fld_proforma_invoice_date, "%d/%m/%Y") as fld_date',
	        'ad.fld_adm_name as employee_name',
			'a.fld_tax_type',
	        'tl.fld_adm_name as team_lead',
	        'CONCAT(
	            IFNULL(dm.fld_dealer_address, ""),
	            CASE WHEN dm.fld_dealer_address != "" THEN ", " ELSE "" END,
	            IFNULL(t.fld_taluka_name, ""),
	            CASE WHEN t.fld_taluka_name != "" THEN ", " ELSE "" END,
	            IFNULL(d.fld_dist_name, ""),
	            CASE WHEN d.fld_dist_name != "" THEN ", " ELSE "" END,
	            IFNULL(s.fld_state_name, "")
	        ) AS fld_dealer_address',
	        '(
			    SELECT SUM((lpd.fld_qty * pm.fld_weight) / 1000)
			    FROM tbl_proforma_invoice_details AS lpd
			    LEFT JOIN tbl_product_master AS pm 
			        ON lpd.fld_product_master_id = pm.fld_product_master_id AND pm.fld_isdeleted =0
			    WHERE lpd.fld_isdeleted = 0 
			      AND lpd.fld_proforma_invoice_id = a.fld_proforma_invoice_id
			) fld_weight_kg'
	    )
	);
    
    $this->db->select('
        IFNULL(SUM((spd.fld_qty * pm.fld_weight) / 1000), 0) as grand_total_weight
    ');
  
    apply_customer_visibility_filter('a', 'fld_id', 'fld_team_lead_id');
    $this->db->from('tbl_proforma_invoice_master as a');
    
    $this->db->join('tbl_admin AS ad', 'ad.fld_id = a.fld_id AND ad.fld_isdeleted = 0', 'LEFT');
    $this->db->join('tbl_proforma_invoice_details AS spd', 'spd.fld_proforma_invoice_id  = a.fld_proforma_invoice_id  AND spd.fld_isdeleted = 0', 'LEFT');
    $this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = spd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
    
    // Apply ONLY the base condition - no search filters
    $this->db->where(['a.fld_isdeleted' => 0]);
    
    $grand_total_result = $this->db->get()->row();
    $data['grand_total_weight'] = $grand_total_result ? $grand_total_result->grand_total_weight : 0;
	$this->load->view('superadmin/rptr_proforma_invoice',$data);
}

private function save_product_rows($proforma_invoice_id, $dealer_id)
{
    // Collect all arrays from POST
	$proforma_invoice_product_det_id = $this->input->post('hid_proforma_invoice_product_det');
	$product_group_ids        = $this->input->post('prod_group_id');
	$product_ids              = $this->input->post('product_id');
	$qtys                     = $this->input->post('qty');
	$rates                    = $this->input->post('rate');
	$amounts                  = $this->input->post('amount');
	$units                    = $this->input->post('unit'); 
	$disc_per                 = $this->input->post('txt_disc_per'); 
	$disc_amt                 = $this->input->post('txt_disc_amt'); 
	$product_remarks          = $this->input->post('txt_product_remark'); 
	$loc_packing_id           = $this->input->post('packing_id');
	$per_bag_qtys             = $this->input->post('per_bag_qty');
	$no_of_bags_qtys          = $this->input->post('no_of_bags_qty');


	

// echo "<pre>";print_r($_POST['product_id']);die;
	if (is_array($product_ids) && count($product_ids) > 0) {

		for ($i = 0; $i < count($product_ids); $i++) {

			$prod_id = trim($product_ids[$i]);

            //  Skip empty rows
			if (empty($prod_id)) continue;

            // Build row data
			$row = [
				'fld_proforma_invoice_id'=> $proforma_invoice_id,
				'fld_dealer_id'          => $dealer_id,
				'fld_product_group_id'   => !empty($product_group_ids[$i]) ? $product_group_ids[$i] : 0,
				'fld_brand_id'           => 0,
				'fld_product_master_id'  => $prod_id,
				'fld_qty'                => isset($qtys[$i]) ? $qtys[$i] : 0,
				'fld_rate'               => isset($rates[$i]) ? $rates[$i] : 0,
				'fld_disc_perc'          => isset($disc_per[$i]) ? $disc_per[$i] : 0,
				'fld_disc_amt'           => isset($disc_amt[$i]) ? $disc_amt[$i] : 0,
				'fld_total_amt'          => isset($amounts[$i]) ? $amounts[$i] : 0,
				'fld_unit'               => !empty($units[$i]) ? $units[$i] : 'NOS',
				'fld_taxable_amt'        => 0,
				'fld_gst_perc'           => 0,
				'fld_gst_amt'            => 0,
				'fld_grand_total'        => isset($amounts[$i]) ? $amounts[$i] : 0, 
				'fld_remark'        	 => isset($product_remarks[$i]) ? $product_remarks[$i] : '',
				'fld_packing_id' 		 => isset($loc_packing_id[$i]) ? $loc_packing_id[$i] : 0,
				'fld_per_bag_qty' 		 => isset($per_bag_qtys[$i]) ? $per_bag_qtys[$i] : 0,
				'fld_no_of_bags' 		 => isset($no_of_bags_qtys[$i]) ? $no_of_bags_qtys[$i] : 0,
			];

            //  UPDATE if existing record ID present
			if (!empty($proforma_invoice_product_det_id[$i])) {

				$row['fld_updated_date'] = date('Y-m-d');
				$row['fld_updated_by']   = $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT);

				$this->Master_model->updateRecord(
					'tbl_proforma_invoice_details',
					$row,
					['fld_proforma_invoice_details_id' => $proforma_invoice_product_det_id[$i]]
				);
			} 
			else {
                // ✅ INSERT new record only once
				$row['fld_created_date'] = date('Y-m-d');
				$row['fld_created_by']   = $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT);
				$row['fld_system_date']  = time();

				$this->Master_model->insertRecord('tbl_proforma_invoice_details', $row);
			}
		}

        // ✅ Soft delete rows that were removed from the form
		$deleted_ids = $this->input->post('deleted_product_ids');
		if (!empty($deleted_ids) && is_array($deleted_ids)) {
			$this->db->where('fld_proforma_invoice_id', $proforma_invoice_id);
			$this->db->where('fld_isdeleted', 0);
			$this->db->where_in('fld_proforma_invoice_details_id', $deleted_ids);
			$this->db->update('tbl_proforma_invoice_details', [
				'fld_isdeleted'   => 1,
				'fld_updated_date'=> date('Y-m-d'),
				'fld_updated_by'  => $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT),
			]);
		}
	}
}

	private function save_other_product_rows($proforma_id)
	{

		// echo "<pre>";print_r($_POST);
		// $enquiry_id  = $this->input->post('hid_enquiry_id');
		// echo $enquiry_id;die;
		$other_prod_ids = $this->input->post('hid_other_product_det');
		$categories     = $this->input->post('txt_other_product_category');
		$names          = $this->input->post('txt_other_product_name');
		$hsn_codes      = $this->input->post('txt_other_prod_hsc_code');
		$qtys           = $this->input->post('txt_other_prod_qty');
		$moqs           = $this->input->post('txt_other_moq');
		$unit_ids       = $this->input->post('txt_other_unit');
		$rates          = $this->input->post('txt_other_rate');
		$other_total_amt = $this->input->post('txt_other_amount');   // Amount (Qty × Rate)
		$other_disc_perc = $this->input->post('txt_other_disc_per');  // Discount %
		$other_disc_amt  = $this->input->post('other_disc_amt');
		$wt_pcs         = $this->input->post('txt_other_wt_pcs');
		$packing_qtys   = $this->input->post('txt_other_packing_qty');
		$old_files      = $this->input->post('hid_other_prod_old_file');
	// echo "<pre>";print_r($_POST);die;
		$files = $_FILES['file_other_prod_doc'];

		$upload_dir = FCPATH . 'uploads/proforma_other_products/';
		$old_upload_dir = FCPATH . 'uploads/quotation_other_products/';
		if (!is_dir($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}

		if (is_array($names) && count($names) > 0) {

			for ($i = 0; $i < count($names); $i++) {

				// Skip blank row
				if (empty(trim($names[$i]))) continue;

				// Prepare DB row
				$row = [
					'fld_proforma_id' => $proforma_id,
					'fld_category'     => trim($categories[$i]),
					'fld_product_name' => trim($names[$i]),
					'fld_hsn_code'     => trim($hsn_codes[$i]),
					'fld_qty'          => $qtys[$i],
					'fld_moq'          => trim($moqs[$i]),
					'fld_unit_id'      => !empty($unit_ids[$i]) ? $unit_ids[$i] : null,
					'fld_rate'         => $rates[$i],
					'fld_other_total_amt'  => !empty($other_total_amt[$i]) ? floatval($other_total_amt[$i]) : 0,
					'fld_other_disc_perc'  => !empty($other_disc_perc[$i]) ? floatval($other_disc_perc[$i]) : 0,
					'fld_other_disc_amt'   => !empty($other_disc_amt[$i])  ? floatval($other_disc_amt[$i])  : 0,	
					'fld_wt_pcs'       => !empty($wt_pcs[$i]) ? $wt_pcs[$i] : null,
					'fld_packing_qty'   => !empty($packing_qtys[$i]) ? $packing_qtys[$i] : null,
				];
				// -----------------------------
				// FILE UPLOAD HANDLING
				// -----------------------------
				$uploadedFile = "";

				// No new file uploaded
				if ($files['error'][$i] == 4) {
					$uploadedFile = !empty($old_files[$i]) ? $old_files[$i] : '';
				} 
				else {

					// New file selected
					$clean_name = preg_replace('/\s+/', '_', $files['name'][$i]);
					$filename = time() . "_" . $clean_name;

					$_FILES['temp_file'] = [
						'name'     => $files['name'][$i],
						'type'     => $files['type'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'error'    => $files['error'][$i],
						'size'     => $files['size'][$i],
					];

					$config = [];
					$config['upload_path']   = $upload_dir;
					$config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
					$config['file_name']     = $filename;

					$this->load->library('upload');
					$this->upload->initialize($config);

					// Upload file
					if ($this->upload->do_upload('temp_file')) {

						// $uploadedFile = $filename;
						$uploadData = $this->upload->data();
						$uploadedFile = 'uploads/proforma_other_products/' . $uploadData['file_name'];

						// Delete old file only after successful upload
						if (!empty($old_files[$i]) && file_exists($upload_dir . $old_files[$i])) {
							unlink($upload_dir . $old_files[$i]);
						}

					} else {
						// Upload failed → keep old
						$uploadedFile = !empty($old_files[$i]) ? $old_files[$i] : '';
					}
				}
				
				$row['fld_photo'] = $uploadedFile;

				// -----------------------------
				// UPDATE ROW
				// -----------------------------

				// echo "<pre>";print_r($row);die;
				if (!empty($other_prod_ids[$i])) {

					$row['fld_updated_date'] = date('Y-m-d');
					$row['fld_updated_by']   = $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT);

					$this->Master_model->updateRecord(
						'tbl_proforma_other_product_details',
						$row,
						['fld_other_product_id' => $other_prod_ids[$i]]
					);
				}
				// -----------------------------
				// INSERT NEW ROW
				// -----------------------------
				else {

					$row['fld_created_date'] = date('Y-m-d');
					$row['fld_created_by']   = $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT);
					$row['fld_system_date']  = time();

					$this->Master_model->insertRecord(
						'tbl_proforma_other_product_details',
						$row
					);
				}
			}

			// -----------------------------
			// SOFT DELETE REMOVED ROWS
			// -----------------------------
			$deleted_ids = $this->input->post('deleted_other_product_ids');
			if (is_array($deleted_ids) && count($deleted_ids) > 0) {

				$this->db->where('fld_proforma_id', $proforma_id);
				$this->db->where('fld_isdeleted', 0);
				$this->db->where_in('fld_other_product_id', $deleted_ids);
				$this->db->update('tbl_proforma_other_product_details', [
					'fld_isdeleted'   => 1,
					'fld_updated_date'=> date('Y-m-d'),
					'fld_updated_by'  => $this->session->userdata('JYOTI_SES_ADM_ID' . SES_CONSTANT),
				]);
				// echo "<pre>".$this->db->last_query();die;
			}
		}
	}

	/**
	 * Fetch customer product details for products
	 * @param array $product_det Product details array (by reference)
	 * @param int $dealer_id Dealer ID
	 */
	private function fetch_customer_product_details(&$product_det, $dealer_id)
	{
		foreach ($product_det as &$prod) {
			$this->db->select('fld_cust_prod_code, fld_cust_prod_name');
			$this->db->from('tbl_product_details');
			$this->db->where([
				'fld_prod_id' => $prod['fld_product_master_id'],
				'fld_dealer_id' => $dealer_id,
				'fld_isdeleted' => 0
			]);
			$cust_prod = $this->db->get()->row_array();
			$prod['fld_cust_prod_code'] = !empty($cust_prod) ? $cust_prod['fld_cust_prod_code'] : '';
			$prod['fld_cust_prod_name'] = !empty($cust_prod) ? $cust_prod['fld_cust_prod_name'] : '';
		}
		unset($prod);
	}


	public function get_product_details()
	{
		$loc_proforma_invoice_id = $this->input->post('proforma_invoice_id');
		$html_details = "";

		    // ===== Fetch proforma invoice master details =====
		$this->db->select('a.fld_proforma_invoice_no, 
			IFNULL(dm.fld_dealer_name, "") AS fld_dealer_name, 
			DATE_FORMAT(a.fld_proforma_invoice_date, "%d/%m/%Y") AS fld_date');
		$this->db->from('tbl_proforma_invoice_master AS a');
		$this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = a.fld_dealer_id AND dm.fld_isdeleted = 0', 'LEFT');
		$this->db->where(['a.fld_isdeleted' => 0, 'a.fld_proforma_invoice_id' => $loc_proforma_invoice_id]);
		$loc_master_result = $this->db->get()->row_array();

		    // ===== Fetch proforma invoice product details =====
		 

		$this->db->select('epd.fld_proforma_invoice_details_id, epd.fld_proforma_invoice_id, epd.fld_dealer_id, epd.fld_product_group_id, epd.fld_product_master_id, epd.fld_hsn_code, epd.fld_qty, epd.fld_unit, epd.fld_rate, epd.fld_prod_gst_incluidng_rate, epd.fld_total_amt, epd.fld_disc_perc, epd.fld_disc_amt, epd.fld_taxable_amt, epd.fld_gst_perc as fld_gst_percentage, epd.fld_gst_amt, epd.fld_grand_total, epd.fld_description, pm.fld_product_name, pg.fld_product_group_name,pm.fld_hsn_code, pm.fld_item_code, pm.fld_model_no,pm.fld_weight'); 

		$this->db->from('tbl_proforma_invoice_details AS epd');
		$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = epd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
		$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = epd.fld_product_group_id AND pg.fld_isdeleted = 0', 'LEFT');
		$this->db->where(['epd.fld_proforma_invoice_id' => $loc_proforma_invoice_id, 'epd.fld_isdeleted' => 0]);
		$product_det = $this->db->get()->result_array();

		    // ===== Build HTML Output =====
		$html_details .= '<div class="proforma-invoice-product-details">';

		    // --- Suspect Information ---
		if (!empty($loc_master_result)) {
			$html_details .= "
			<div class='p-3 mb-3'>
			<div class='row'>
			<div class='col-md-2'><b>Proforma Invoice No:</b></div>
			<div class='col-md-2'>{$loc_master_result['fld_proforma_invoice_no']}</div>
			<div class='col-md-2'><b>Customer Name:</b></div>
			<div class='col-md-2'>{$loc_master_result['fld_dealer_name']}</div>
			<div class='col-md-2'><b>Date:</b></div>
			<div class='col-md-2'>{$loc_master_result['fld_date']}</div>
			</div>
			</div>";
		}

		    // --- Product Details Table ---
		$html_details .= "
		<h6 class='text-dark mb-3'><b>Product Details</b></h6>";

		if (!empty($product_det)) {
			$html_details .= "
			<div class='table-responsive'>
			<table class='table table-bordered table-striped'>
			<thead class='table-primary'>
			<tr>
			<th>Sr.No</th>
			<th>Product Category</th>
			<th>Product</th>
			<th>Article Weight(gm)</th> 
			<th>HSN Code</th>
			<th>Qty</th>
			<th>Wt in kg</th>
			<th>Rate</th>
			<th>Disc %</th>
			<th>Disc Amt</th>
			<th>Amount</th>
			</tr>
			</thead>
			<tbody>";

			$sr = 1;
			$loc_amount=0;
			$loc_tot_wt_kg=0;
			$total_discount_amt=0;
			$total_qty = 0;
			foreach ($product_det as $p) {

				$loc_amount += $p['fld_total_amt'];
				$total_discount_amt += $p['fld_disc_amt'];
				$total_qty += $p['fld_qty'];
				
				$loc_wt_kg = (($p['fld_qty'] * $p['fld_weight'])/1000);
				$loc_tot_wt_kg += (float)$loc_wt_kg;
				$html_details .= "
				<tr>
				<td>{$sr}</td>
				<td>{$p['fld_product_group_name']}</td>
				<td>{$p['fld_product_name']}</td>
				<td align ='right'>{$p['fld_weight']}</td>

				<td>{$p['fld_hsn_code']}</td>
				<td align = 'right'>{$p['fld_qty']}</td>
				<td align ='right'>" . money_format_india($loc_wt_kg, 3) . "</td>
				<td align ='right'>" . money_format_india($p['fld_rate'], 2) . "</td>
				<td align ='right'>" . money_format_india($p['fld_disc_perc'], 2) . "</td>
				<td align ='right'>" . money_format_india($p['fld_disc_amt'], 2) . "</td>
				<td align ='right'>" . money_format_india($p['fld_total_amt'], 2) . "</td>
				</tr>";
				$sr++;
			}

			$html_details .= "
				<tr style='font-size:12px;'>
				<td align ='right' colspan='5'><strong>Total</strong></td> 
				<td align='right'><b>" . ($total_qty) . "</b></td>

				<td align ='right'><strong>" . money_format_india($loc_tot_wt_kg, 3) . "</strong></td>
				<td></td>
				<td></td>
				<td align='right'><b>" . money_format_india($total_discount_amt, 2) . " </b></td>
				<td align ='right'><strong>" . money_format_india($loc_amount, 2) . "</strong></td>
				</tr>";

			$html_details .= "</tbody></table></div>";
		} else {
			$html_details .= '<div class="text-center text-danger"><b>No Product Details Found</b></div>';
		}

		// ===== Fetch quotation other product details =====
	// $this->db->select('opd.fld_other_product_id, opd.fld_category, opd.fld_product_name, 
	// 	opd.fld_hsn_code, opd.fld_qty, pcm.fld_product_group_name AS category_name, opd.fld_moq, opd.fld_unit_id, um.fld_unit, opd.fld_rate, 
	// 	opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo');
	// $this->db->from('tbl_proforma_other_product_details AS opd');
	// $this->db->join('tbl_product_category_master AS pcm','pcm.fld_product_group_id = opd.fld_category AND pcm.fld_isdeleted = 0','LEFT');
	// $this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	// $this->db->where(['opd.fld_proforma_id' => $loc_proforma_invoice_id, 'opd.fld_isdeleted' => 0]);
	// $this->db->order_by('opd.fld_other_product_id', 'ASC');
	// $other_product_det = $this->db->get()->result_array();

	$this->db->select('opd.fld_other_product_id, opd.fld_category, opd.fld_product_name, 
		opd.fld_hsn_code, opd.fld_qty, pcm.fld_product_group_name AS category_name, opd.fld_moq, opd.fld_unit_id, um.fld_unit, opd.fld_rate,
		opd.fld_other_total_amt, opd.fld_other_disc_perc, opd.fld_other_disc_amt,
		opd.fld_wt_pcs, opd.fld_packing_qty, opd.fld_remark, opd.fld_photo');
	$this->db->from('tbl_proforma_other_product_details AS opd');
	$this->db->join('tbl_product_category_master AS pcm','pcm.fld_product_group_id = opd.fld_category AND pcm.fld_isdeleted = 0','LEFT');
	$this->db->join('tbl_unit_master AS um', 'um.fld_id = opd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	$this->db->where(['opd.fld_proforma_id' => $loc_proforma_invoice_id, 'opd.fld_isdeleted' => 0]);
	$this->db->order_by('opd.fld_other_product_id', 'ASC');
	$other_product_det = $this->db->get()->result_array();

    // --- Other Product Details Table ---

	if (!empty($other_product_det)) {
			$html_details .= "
	<h6 class='text-dark mb-3 mt-4'><b>Other Product Details</b></h6>";
		// $html_details .= "
		// <div class='table-responsive'>
		// <table class='table table-bordered table-striped'>
		// <thead class='table-primary'>
		// <tr>
		// <th>Sr.No.</th>
		// <th>Category</th>
		// <th>Product Name</th>
		// <th>HSN Code</th>
		// <th>Qty</th>
		// <th>MOQ</th>
		// <th>Unit</th>
		// <th>Rate</th>
		// <th>Wt/Pcs(gm)</th>
		// <th>Packing Qty</th>
		// <th>Upload/View</th>
		// </tr>
		// </thead>
		// <tbody>";

		$html_details .= "
		<div class='table-responsive'>
		<table class='table table-bordered table-striped'>
		<thead class='table-primary'>
		<tr>
		<th>Sr.No.</th>
		<th>Category</th>
		<th>Product Name</th>
		<th>HSN Code</th>
		<th>Qty</th>
		<th>MOQ</th>
		<th>Unit</th>
		<th>Rate</th>
		<th>Amount</th>
		<th>Disc %</th>
		<th>Disc Amt</th>
		<th>Total Amount</th>
		<th>Wt/Pcs(gm)</th>
		<th>Packing Qty</th>
		<th>Upload/View</th>
		</tr>
		</thead>
		<tbody>";

		$sr = 1;
		foreach ($other_product_det as $op) {
			$file_link = '';
			if (!empty($op['fld_photo'])) {
				// $file_path = base_url('uploads/proforma_other_products/' . $op['fld_photo']);
				$file_path = base_url($op['fld_photo']);
				$file_link = "<a href='{$file_path}' target='_blank' class='btn btn-sm btn-primary'><i class='fa fa-eye'></i> View</a>";
			} else {
				$file_link = '-';
			}

			$qty = (isset($op['fld_qty']) && $op['fld_qty'] !== '')? number_format((float)$op['fld_qty'], 0, '.', ''): '-';
			$rate = !empty($op['fld_rate']) ? money_format_india($op['fld_rate'], 2) : '-';
			$remark = !empty($op['fld_remark']) ? htmlspecialchars($op['fld_remark']) : '-';
			$category = !empty($op['category_name']) ? htmlspecialchars($op['category_name']) : '-';
			$product_name = !empty($op['fld_product_name']) ? htmlspecialchars($op['fld_product_name']) : '-';
			$hsn_code = !empty($op['fld_hsn_code']) ? htmlspecialchars($op['fld_hsn_code']) : '-';
			// $moq = !empty($op['fld_moq']) ? htmlspecialchars($op['fld_moq']) : '-';
			$moq = (isset($op['fld_moq']) && $op['fld_moq'] !== '')? number_format((float)$op['fld_moq'], 0, '.', ''): '-';
			$unit = !empty($op['fld_unit']) ? htmlspecialchars($op['fld_unit']) : '-';
			$wt_pcs = !empty($op['fld_wt_pcs']) ? money_format_india($op['fld_wt_pcs'], 2) : '-';
			$packing_qty = !empty($op['fld_packing_qty']) ? $op['fld_packing_qty'] : '-';

			// $html_details .= "
			// <tr>
			// <td>{$sr}</td>
			// <td>{$category}</td>
			// <td>{$product_name}</td>
			// <td>{$hsn_code}</td>
			// <td align='right'>{$qty}</td>
			// <td>{$moq}</td>
			// <td>{$unit}</td>
			// <td align='right'>{$rate}</td>
			// <td align='right'>{$wt_pcs}</td>
			// <td align='right'>{$packing_qty}</td>
			// <td>{$file_link}</td>
			// </tr>";
			$calc_amount  = (isset($op['fld_other_total_amt']) && $op['fld_other_total_amt'] != '') ? money_format_india($op['fld_other_total_amt'], 2) : '-';
			$disc_perc    = (isset($op['fld_other_disc_perc']) && $op['fld_other_disc_perc'] != '') ? number_format((float)$op['fld_other_disc_perc'], 2) : '0.00';
			$disc_amt_val = (isset($op['fld_other_disc_amt'])  && $op['fld_other_disc_amt']  != '') ? money_format_india($op['fld_other_disc_amt'],  2) : '0.00';
			// Net total = Amount - Disc Amt
			$net_total_raw = floatval($op['fld_other_total_amt'] ?? 0) - floatval($op['fld_other_disc_amt'] ?? 0);
			$net_total = money_format_india($net_total_raw, 2);

			$html_details .= "
			<tr>
			<td>{$sr}</td>
			<td>{$category}</td>
			<td>{$product_name}</td>
			<td>{$hsn_code}</td>
			<td align='right'>{$qty}</td>
			<td>{$moq}</td>
			<td>{$unit}</td>
			<td align='right'>{$rate}</td>
			<td align='right'>{$calc_amount}</td>
			<td align='right'>{$disc_perc}</td>
			<td align='right'>{$disc_amt_val}</td>
			<td align='right'>{$net_total}</td>
			<td align='right'>{$wt_pcs}</td>
			<td align='right'>{$packing_qty}</td>
			<td>{$file_link}</td>
			</tr>";
			$sr++;
		}

		$html_details .= "</tbody></table></div>";}

		    $html_details .= '</div>'; // end wrapper

		    echo $html_details;
	}

	public function get_terms_condition_details()
	{
	    $loc_proforma_invoice_id = $this->input->post('proforma_invoice_id');
	    $html_details = "";

	    // ===== Fetch proforma invoice master details with stored terms =====
	    $this->db->select('a.fld_proforma_invoice_no, 
	        IFNULL(dm.fld_dealer_name, "") AS fld_dealer_name, 
	        DATE_FORMAT(a.fld_proforma_invoice_date, "%d/%m/%Y") AS fld_date,
	        a.fld_terms_condition');
	    $this->db->from('tbl_proforma_invoice_master AS a');
	    $this->db->join('tbl_dealer_master AS dm', 'dm.fld_dealer_id = a.fld_dealer_id AND dm.fld_isdeleted = 0', 'LEFT');
	    $this->db->where(['a.fld_isdeleted' => 0, 'a.fld_proforma_invoice_id' => $loc_proforma_invoice_id]);
	    $loc_master_result = $this->db->get()->row_array();

	    $html_details .= '<div class="term-condition-details">';

	    if (!empty($loc_master_result)) {
	        $html_details .= "
	        <div class='p-3 mb-3'>
	            <div class='row'>
	                <div class='col-md-2'><b>Proforma Invoice No:</b></div>
	                <div class='col-md-2'>{$loc_master_result['fld_proforma_invoice_no']}</div>
	                <div class='col-md-2'><b>Customer Name:</b></div>
	                <div class='col-md-2'>{$loc_master_result['fld_dealer_name']}</div>
	                <div class='col-md-2'><b>Date:</b></div>
	                <div class='col-md-2'>{$loc_master_result['fld_date']}</div>
	            </div>
	        </div>";
	    }

	    $html_details .= "<h5 class='text-dark mb-3'><b>Terms & Conditions</b></h5>";

	    $terms_content = !empty($loc_master_result['fld_terms_condition']) ? $loc_master_result['fld_terms_condition'] : '';

	    if (!empty(trim(strip_tags($terms_content)))) {
	        $html_details .= "<div class='p-3 border rounded' style='background-color: #f8f9fa;'>";
	        $html_details .= $terms_content;
	        $html_details .= "</div>";
	    } else {
	        $html_details .= '<div class="text-center text-danger p-3"><b>No Terms & Conditions Found!</b></div>';
	    }

	    $html_details .= '</div>';

	    echo $html_details;
	}


    private $currentHeaderData = []; 

    public function generate_invoice($id = "") {
    	if (ob_get_length()) {
    		ob_end_clean();
    	}
    	
    	// Suppress PNG iCCP profile warnings
    	error_reporting(E_ALL & ~E_WARNING);

		$this->billToRendered = false;

    	$this->load->library('Pdf'); 
    	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    	$pdf->SetCreator('Jyoti Chemicals');
    	$pdf->SetAuthor('Jyoti Chemicals');
    	$pdf->SetTitle('Proforma Invoice');

    	$pdf->setPrintHeader(false);
    	$pdf->setPrintFooter(false);

    	$pdf->SetMargins(8, 8, 8);
    	$pdf->SetAutoPageBreak(TRUE, 15);

    	$pdf->AddPage();

        // --- Fetch Proforma Invoice Data ---
    	$id = base64_decode($id);

    	$this->db->join('tbl_admin as a', 'a.fld_id = qm.fld_created_by and a.fld_isdeleted = 0', 'left');
    	$this->db->join('tbl_admin as sales_ex', 'sales_ex.fld_id = qm.fld_id and sales_ex.fld_isdeleted = 0', 'left');
    	$this->db->join('tbl_designation_master as dm', 'dm.fld_designation_id = a.fld_designation_id and dm.fld_isdeleted = 0', 'left');
    	$this->db->join('tbl_designation_master as sales_ex_dm', 'sales_ex_dm.fld_designation_id = sales_ex.fld_designation_id and sales_ex_dm.fld_isdeleted = 0', 'left');

    	$proforma_invoice_data = $this->Master_model->getRecords(
    		'tbl_proforma_invoice_master as qm',
    		array('qm.fld_proforma_invoice_id' => $id, 'qm.fld_isdeleted' => 0),
    		'qm.fld_proforma_invoice_id, qm.fld_remark, qm.fld_proforma_invoice_no,qm.fld_proforma_invoice_date, qm.fld_created_date, qm.fld_shipping_address, a.fld_adm_name as created_by_name, a.fld_mobile_no as employee_mobile, a.fld_email as employee_email, dm.fld_designation_name as employee_designation, sales_ex.fld_adm_name as sales_ex_name, sales_ex.fld_mobile_no as sales_ex_mobile, sales_ex.fld_email as sales_ex_email, sales_ex_dm.fld_designation_name as sales_ex_designation, qm.fld_dealer_id, qm.fld_terms_condition, qm.fld_po_no, qm.fld_po_mode, sales_ex.fld_sign_photo, qm.fld_po_date'
    	);

    	if (!empty($proforma_invoice_data)) {
    		$proforma_invoice = $proforma_invoice_data[0];
    	} else {
    		show_error('Proforma Invoice not found');
    		return;
    	}

    // --- Fetch Company Data ---
    // Prefer organization state id for GST state matching; keep fld_state_code as legacy fallback
   $company_data = $this->Master_model->getRecords(
       'tbl_organization_master AS om',
       array('om.fld_isdeleted !=' => 1),
       'om.fld_org_id, om.fld_org_name, om.fld_cin, om.fld_org_address, om.fld_gst_no, om.fld_pan_no, om.fld_email, om.fld_org_contact, om.fld_logo, om.fld_state, om.fld_state_code, om.fld_bank_name, om.fld_bank_ac_holder, om.fld_account_no, om.fld_ifsc_code, om.fld_iso_details, om.fld_website'
   );

    	if (!empty($company_data)) {
    		$company = $company_data[0];
    		// Set logo path
    		$company['logo_path'] = !empty($company['fld_logo']) ? FCPATH . 'uploads/' . $company['fld_logo'] : '';
    		
        // Resolve GST state code using organization state id directly
        $org_state_id = trim($company['fld_state'] ?? '');
        $company['fld_gst_state_code'] = '';
        if (!empty($org_state_id)) {
            $state_check = $this->Master_model->getRecords(
                'tbl_state_master',
                array('fld_state_id' => $org_state_id, 'fld_isdeleted' => 0),
                'fld_gst_code'
            );
            if (!empty($state_check) && !empty($state_check[0]['fld_gst_code'])) {
                $company['fld_gst_state_code'] = $state_check[0]['fld_gst_code'];
            }
        }
        // Fallback for legacy data using fld_state_code if provided as GST code
        if (empty($company['fld_gst_state_code'])) {
            $org_state_code = trim($company['fld_state_code'] ?? '');
            if (!empty($org_state_code) && is_numeric($org_state_code) && strlen($org_state_code) <= 2) {
                $company['fld_gst_state_code'] = $org_state_code;
            }
        }
    	} else {
    		$company = array(
    			'fld_org_name' => 'Company Name',
    			'fld_org_address' => 'Company Address',
    			'fld_gst_no' => '',
    			'fld_pan_no' => '',
    			'fld_email' => '',
    			'fld_org_contact' => '',
    			'fld_state_code' => '',
    			'fld_gst_state_code' => '',
    			'fld_bank_name' => '',
    			'fld_account_no' => '',
    			'fld_ifsc_code' => '',
    			'fld_iso_details' => '',
    			'fld_website' => '',
    			'logo_path' => '',
    			'fld_bank_ac_holder' => ''
    		);
    	}

        // --- Fetch Customer Data using dealer_id from proforma invoice ---
    	$dealer_id = $proforma_invoice['fld_dealer_id'];
    $this->db->select('dm.fld_dealer_name, dm.fld_dealer_address, dm.fld_gst_no, dm.fld_mobile_no, dm.fld_email, dm.fld_state_id AS fld_gst_state_code, sm.fld_state_name, dist.fld_dist_name, tal.fld_taluka_name');
    	$this->db->from('tbl_dealer_master AS dm');
    	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_dist_master AS dist', 'dist.fld_dist_id = dm.fld_dist_id AND dist.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_taluka_master AS tal', 'tal.fld_taluka_id = dm.fld_taluka_id AND tal.fld_isdeleted = 0', 'LEFT');
    	$this->db->where('dm.fld_dealer_id', $dealer_id);
    	$this->db->where('dm.fld_isdeleted', 0);
    	$dealer_query = $this->db->get();
    	$dealer_data = $dealer_query->result_array();

    	if (!empty($dealer_data)) {
    		$dealer = $dealer_data[0];
    	} else {
    		show_error('Customer not found');
    		return;
    	}
    	
    	// Fetch first contact person details
    	$this->db->select('fld_contact_person_name, fld_contact_mobile_no');
    	$this->db->from('tbl_dealer_contact_person_details');
    	$this->db->where('fld_dealer_id', $dealer_id);
    	$this->db->where('fld_isdeleted', 0);
    	$this->db->order_by('fld_contact_detail_id', 'ASC');
    	$this->db->limit(1);
    	$contact_person_query = $this->db->get();
    	$contact_person_data = $contact_person_query->result_array();
		
    	if (!empty($contact_person_data)) {
    		$dealer['contact_person_name'] = $contact_person_data[0]['fld_contact_person_name'];
    		$dealer['contact_person_mobile'] = $contact_person_data[0]['fld_contact_mobile_no'];
    	} else {
    		$dealer['contact_person_name'] = '';
    		$dealer['contact_person_mobile'] = '';
    	}

        // Save header data for repeating
    	$this->currentHeaderData = [
    		'company' => $company,
    		'proforma_invoice' => $proforma_invoice,
    		'dealer' => $dealer
    	];
    	
    	// Store employee data in proforma_invoice array for Sales Executive section
    	$this->currentHeaderData['proforma_invoice']['created_by_name'] = $proforma_invoice['created_by_name'];
    	$this->currentHeaderData['proforma_invoice']['employee_mobile'] = $proforma_invoice['employee_mobile'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_email'] = $proforma_invoice['employee_email'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_designation'] = $proforma_invoice['employee_designation'] ?? '';

		 // --- Get Product Details with Images ---
    	$this->db->select('epd.fld_proforma_invoice_details_id, epd.fld_product_group_id, epd.fld_product_master_id,epd.fld_qty, epd.fld_total_amt, epd.fld_unit,pm.fld_product_name, pm.fld_hsn_code, pm.fld_item_code, pm.fld_model_no, pg.fld_product_group_name,epd.fld_rate,epd.fld_disc_perc,epd.fld_disc_amt,pm.fld_hsn_code,epd.fld_gst_perc,epd.fld_taxable_amt,epd.fld_grand_total,pm.fld_prod_image,pm.fld_gst_percentage AS pm_gst_percentage,pm.fld_weight, pm.fld_tolerance,epd.fld_remark, epd.fld_per_bag_qty as fld_packing_qty, epd.fld_no_of_bags, rim.fld_rm_item_name');
    	$this->db->from('tbl_proforma_invoice_details AS epd');
    	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = epd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
		// $this->db->join('tbl_product_packing_details AS ppd','ppd.fld_det_id = epd.fld_packing_id AND ppd.fld_isdeleted = 0','left');
		$this->db->join('tbl_rm_item_master AS rim','rim.fld_rm_item_master_id = epd.fld_packing_id AND rim.fld_isdeleted = 0','left');
    	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = epd.fld_product_group_id AND pg.fld_isdeleted = 0', 'LEFT');
    	$this->db->where(['epd.fld_proforma_invoice_id' => $id, 'epd.fld_isdeleted' => 0]);
    	$this->db->order_by('epd.fld_proforma_invoice_details_id', 'ASC');
		$product_det1 = $this->db->get()->result_array();
    	
    	

		// --- Get Other Product Details ---
	    $this->db->select('epd.fld_other_product_id AS id, epd.fld_category AS category_id, 0 AS product_id, epd.fld_qty, fld_other_total_amt AS fld_total_amt, um.fld_unit, epd.fld_product_name, epd.fld_hsn_code, 0 AS fld_item_code, 0 AS fld_model_no, 0 AS fld_product_group_name, epd.fld_rate, fld_other_disc_perc AS fld_disc_perc, fld_other_disc_amt AS fld_disc_amt, 0 AS fld_gst_perc, 0 AS fld_taxable_amt, 0 AS fld_grand_total, epd.fld_photo AS fld_prod_image, 0 AS pm_gst_percentage, epd.fld_moq, epd.fld_wt_pcs AS fld_weight, epd.fld_wt_pcs AS fld_packing_qty_1, epd.fld_packing_qty, "" AS fld_packing_material_1,"uploads/proforma_other_products/" as fld_path, epd.fld_wt_pcs');
	    $this->db->from('tbl_proforma_other_product_details AS epd');
	    $this->db->join('tbl_unit_master AS um', 'um.fld_id = epd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
	    $this->db->where(['epd.fld_proforma_id' => $id, 'epd.fld_isdeleted' => 0]);
	    $product_det2 = $this->db->get()->result_array();

		$product_det = array_merge($product_det1, $product_det2);
    	
    	// Fetch customer product details for each product
    	$dealer_id = $proforma_invoice['fld_dealer_id'];
    	$this->fetch_customer_product_details($product_det, $dealer_id);
    	
    	// // Add product image paths
    	// foreach ($product_det as &$prod) {
    	// 	$prod['image_path'] = !empty($prod['fld_prod_image']) ? FCPATH . 'uploads/product_image/' . $prod['fld_prod_image'] 
		// 	: '';
    	// }
		foreach ($product_det as &$prod) {
	        $prod['image_path'] = !empty($prod['fld_prod_image']) ? FCPATH . $prod['fld_prod_image'] : '';
	    }
    	unset($prod); // Important: unset reference to avoid issues in subsequent loops

        // Check if any product has discount
    	$hasDiscount = false;
    	foreach ($product_det as $prod) {
    		if (!empty($prod['fld_disc_perc']) && floatval($prod['fld_disc_perc']) > 0) {
    			$hasDiscount = true;
    			break;
    		}
    	}

        // Draw initial header
    	$this->draw_header($pdf, $company, $proforma_invoice, $dealer);

        // Draw items table with page check
    	$totalAmount = $this->draw_items_table($pdf, $product_det, $hasDiscount);
        // print_r($product_det);die();

    	$piMastFields = 'fld_total_amt,fld_discount_per,fld_discount,fld_igst_amt,fld_cgst_amt,fld_sgst_amt,fld_sub_total2,fld_round_off,fld_tds,fld_tds_per,fld_grand_total,fld_packing_forwarding_amt,fld_transportation_amt, fld_gst_per';
    	if ($this->db->field_exists('fld_hsn_distributed_json', 'tbl_proforma_invoice_master')) {
    		$piMastFields .= ', fld_hsn_distributed_json';
    	}
    	$product_mast = $this->Master_model->getRecords('tbl_proforma_invoice_master',array('fld_isdeleted'=>0,'fld_proforma_invoice_id' => $id), $piMastFields);

    	// $totalAmount1 = $this->draw_hsn_table($pdf, $product_det);

        // Draw footer
    	$terms_condition = !empty($proforma_invoice['fld_terms_condition']) ? $proforma_invoice['fld_terms_condition'] : '';

			// Fetch Bank Details 1st
			$bank_details = $this->Master_model->getRecords(
				'tbl_bank_account_details',
				array(
					'fld_isdeleted' => 0,
					'fld_account_for' => 'Proforma Invoice' 
				),
				'fld_account_for, fld_bank_name, fld_ifsc_code, fld_account_no, fld_bank_ac_holder, fld_branch_name'
			);
	
			// Take first record (or apply condition if needed)
			$bankData = !empty($bank_details) ? $bank_details[0] : [];

    	$this->draw_footer($pdf,$company,$totalAmount,$product_mast,$product_det,$terms_condition,$hasDiscount,$bankData, $proforma_invoice);

    	// --- Dynamic Filename Logic for PI ---
	$pi_no_parts = explode('/', $proforma_invoice['fld_proforma_invoice_no']);
	$pi_short_id = end($pi_no_parts); 

	$customer_name = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '', $dealer['fld_dealer_name']);

	$pi_date = date('d-m-Y', strtotime($proforma_invoice['fld_created_date']));

	$fileName = "PI-" . $pi_short_id . "-" . $customer_name . "-" . $pi_date . ".pdf";

	$pdf->Output($fileName, 'I');
  }

  public function generate_invoice_new($id = "") {
		if (ob_get_length()) {
			ob_end_clean();
		}

		error_reporting(E_ALL & ~E_WARNING);

		$this->billToRendered = false;

		$this->load->library('Pdf_new'); 

		//  Use Pdf class (not TCPDF) so Header() and Footer() fire automatically
		$pdf = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);

		$pdf->SetCreator('Jyoti Chemicals');
		$pdf->SetAuthor('Jyoti Chemicals');
		$pdf->SetTitle('Proforma Invoice');

		//  Enable header and footer
		$pdf->setPrintHeader(true);
		$pdf->setPrintFooter(true);

		//  Top margin 58 = space for page 1 full header (logo + bill to section)
		// Pages 2+ header is ~32mm but TCPDF uses same top margin — content won't overlap
		$pdf->SetMargins(8, 58, 8);
		$pdf->SetHeaderMargin(6);
		$pdf->SetFooterMargin(12);
		$pdf->SetAutoPageBreak(TRUE, 20);

		// --- Fetch Proforma Invoice Data ---
		$id = base64_decode($id);

		$this->db->join('tbl_admin as a', 'a.fld_id = qm.fld_created_by and a.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_admin as sales_ex', 'sales_ex.fld_id = qm.fld_id and sales_ex.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_designation_master as dm', 'dm.fld_designation_id = a.fld_designation_id and dm.fld_isdeleted = 0', 'left');
		$this->db->join('tbl_designation_master as sales_ex_dm', 'sales_ex_dm.fld_designation_id = sales_ex.fld_designation_id and sales_ex_dm.fld_isdeleted = 0', 'left');

		$proforma_invoice_data = $this->Master_model->getRecords(
			'tbl_proforma_invoice_master as qm',
			array('qm.fld_proforma_invoice_id' => $id, 'qm.fld_isdeleted' => 0),
			'qm.fld_proforma_invoice_id, qm.fld_remark, qm.fld_proforma_invoice_no, qm.fld_proforma_invoice_date, qm.fld_created_date, qm.fld_shipping_address, a.fld_adm_name as created_by_name, a.fld_mobile_no as employee_mobile, a.fld_email as employee_email, dm.fld_designation_name as employee_designation, sales_ex.fld_adm_name as sales_ex_name, sales_ex.fld_mobile_no as sales_ex_mobile, sales_ex.fld_email as sales_ex_email, sales_ex_dm.fld_designation_name as sales_ex_designation, qm.fld_dealer_id, qm.fld_terms_condition, qm.fld_po_no, qm.fld_po_mode, sales_ex.fld_sign_photo, qm.fld_po_date'
		);

		if (!empty($proforma_invoice_data)) {
			$proforma_invoice = $proforma_invoice_data[0];
		} else {
			show_error('Proforma Invoice not found');
			return;
		}

		// --- Fetch Company Data ---
		$company_data = $this->Master_model->getRecords(
			'tbl_organization_master AS om',
			array('om.fld_isdeleted !=' => 1),
			'om.fld_org_id, om.fld_org_name, om.fld_cin, om.fld_org_address, om.fld_gst_no, om.fld_pan_no, om.fld_email, om.fld_org_contact, om.fld_logo, om.fld_state, om.fld_state_code, om.fld_bank_name, om.fld_bank_ac_holder, om.fld_account_no, om.fld_ifsc_code, om.fld_iso_details, om.fld_website'
		);

		if (!empty($company_data)) {
			$company = $company_data[0];
			$company['logo_path'] = !empty($company['fld_logo']) ? FCPATH . 'uploads/' . $company['fld_logo'] : '';
			
			$org_state_id = trim($company['fld_state'] ?? '');
			$company['fld_gst_state_code'] = '';
			if (!empty($org_state_id)) {
				$state_check = $this->Master_model->getRecords(
					'tbl_state_master',
					array('fld_state_id' => $org_state_id, 'fld_isdeleted' => 0),
					'fld_gst_code'
				);
				if (!empty($state_check) && !empty($state_check[0]['fld_gst_code'])) {
					$company['fld_gst_state_code'] = $state_check[0]['fld_gst_code'];
				}
			}
			if (empty($company['fld_gst_state_code'])) {
				$org_state_code = trim($company['fld_state_code'] ?? '');
				if (!empty($org_state_code) && is_numeric($org_state_code) && strlen($org_state_code) <= 2) {
					$company['fld_gst_state_code'] = $org_state_code;
				}
			}
		} else {
			$company = array(
				'fld_org_name'      => 'Company Name',
				'fld_org_address'   => 'Company Address',
				'fld_gst_no'        => '',
				'fld_pan_no'        => '',
				'fld_email'         => '',
				'fld_org_contact'   => '',
				'fld_state_code'    => '',
				'fld_gst_state_code'=> '',
				'fld_bank_name'     => '',
				'fld_account_no'    => '',
				'fld_ifsc_code'     => '',
				'fld_iso_details'   => '',
				'fld_website'       => '',
				'logo_path'         => '',
				'fld_bank_ac_holder'=> '',
				'fld_cin'           => '',
			);
		}

		// --- Fetch Customer Data ---
		$dealer_id = $proforma_invoice['fld_dealer_id'];
		$this->db->select('dm.fld_dealer_name, dm.fld_dealer_address, dm.fld_gst_no, dm.fld_mobile_no, dm.fld_email, dm.fld_state_id AS fld_gst_state_code, sm.fld_state_name, dist.fld_dist_name, tal.fld_taluka_name');
		$this->db->from('tbl_dealer_master AS dm');
		$this->db->join('tbl_state_master AS sm',   'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0',    'LEFT');
		$this->db->join('tbl_dist_master AS dist',  'dist.fld_dist_id = dm.fld_dist_id AND dist.fld_isdeleted = 0',  'LEFT');
		$this->db->join('tbl_taluka_master AS tal',  'tal.fld_taluka_id = dm.fld_taluka_id AND tal.fld_isdeleted = 0','LEFT');
		$this->db->where('dm.fld_dealer_id', $dealer_id);
		$this->db->where('dm.fld_isdeleted', 0);
		$dealer_data = $this->db->get()->result_array();

		if (!empty($dealer_data)) {
			$dealer = $dealer_data[0];
		} else {
			show_error('Customer not found');
			return;
		}

		// --- Fetch Contact Person ---
		$this->db->select('fld_contact_person_name, fld_contact_mobile_no');
		$this->db->from('tbl_dealer_contact_person_details');
		$this->db->where('fld_dealer_id', $dealer_id);
		$this->db->where('fld_isdeleted', 0);
		$this->db->order_by('fld_contact_detail_id', 'ASC');
		$this->db->limit(1);
		$contact_person_data = $this->db->get()->result_array();

		if (!empty($contact_person_data)) {
			$dealer['contact_person_name']   = $contact_person_data[0]['fld_contact_person_name'];
			$dealer['contact_person_mobile'] = $contact_person_data[0]['fld_contact_mobile_no'];
		} else {
			$dealer['contact_person_name']   = '';
			$dealer['contact_person_mobile'] = '';
		}

		// --- Store header data ---
		$this->currentHeaderData = [
			'company'          => $company,
			'proforma_invoice' => $proforma_invoice,
			'dealer'           => $dealer,
		];
		$this->currentHeaderData['proforma_invoice']['created_by_name']      = $proforma_invoice['created_by_name'];
		$this->currentHeaderData['proforma_invoice']['employee_mobile']       = $proforma_invoice['employee_mobile']      ?? '';
		$this->currentHeaderData['proforma_invoice']['employee_email']        = $proforma_invoice['employee_email']       ?? '';
		$this->currentHeaderData['proforma_invoice']['employee_designation']  = $proforma_invoice['employee_designation'] ?? '';

		//  Pass data to Pdf object BEFORE AddPage() so Header() has it on page 1
		$pdf->headerData = [
			'company'          => $company,
			'proforma_invoice' => $proforma_invoice,
			'dealer'           => $dealer,
		];

		// Now add the first page — Header() fires automatically here
		$pdf->AddPage();

		// --- Get Product Details ---
		$this->db->select('epd.fld_proforma_invoice_details_id, epd.fld_product_group_id, epd.fld_product_master_id, epd.fld_qty, epd.fld_total_amt, epd.fld_unit, pm.fld_product_name, pm.fld_hsn_code, pm.fld_item_code, pm.fld_model_no, pg.fld_product_group_name, epd.fld_rate, epd.fld_disc_perc, epd.fld_disc_amt, pm.fld_hsn_code, epd.fld_gst_perc, epd.fld_taxable_amt, epd.fld_grand_total, pm.fld_prod_image, pm.fld_gst_percentage AS pm_gst_percentage, pm.fld_weight, pm.fld_tolerance, epd.fld_remark, epd.fld_per_bag_qty as fld_packing_qty, epd.fld_no_of_bags, rim.fld_rm_item_name');
		$this->db->from('tbl_proforma_invoice_details AS epd');
		$this->db->join('tbl_product_master AS pm',         'pm.fld_product_master_id = epd.fld_product_master_id AND pm.fld_isdeleted = 0',  'LEFT');
		$this->db->join('tbl_rm_item_master AS rim',         'rim.fld_rm_item_master_id = epd.fld_packing_id AND rim.fld_isdeleted = 0',       'LEFT');
		$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = epd.fld_product_group_id AND pg.fld_isdeleted = 0',    'LEFT');
		$this->db->where(['epd.fld_proforma_invoice_id' => $id, 'epd.fld_isdeleted' => 0]);
		$this->db->order_by('epd.fld_proforma_invoice_details_id', 'ASC');
		$product_det1 = $this->db->get()->result_array();

		// --- Get Other Product Details ---
		$this->db->select('epd.fld_other_product_id AS id, epd.fld_category AS category_id, 0 AS product_id, epd.fld_qty, fld_other_total_amt AS fld_total_amt, um.fld_unit, epd.fld_product_name, epd.fld_hsn_code, 0 AS fld_item_code, 0 AS fld_model_no, 0 AS fld_product_group_name, epd.fld_rate, fld_other_disc_perc AS fld_disc_perc, fld_other_disc_amt AS fld_disc_amt, 0 AS fld_gst_perc, 0 AS fld_taxable_amt, 0 AS fld_grand_total, epd.fld_photo AS fld_prod_image, 0 AS pm_gst_percentage, epd.fld_moq, epd.fld_wt_pcs AS fld_weight, epd.fld_wt_pcs AS fld_packing_qty_1, epd.fld_packing_qty, "" AS fld_packing_material_1, "uploads/proforma_other_products/" as fld_path, epd.fld_wt_pcs');
		$this->db->from('tbl_proforma_other_product_details AS epd');
		$this->db->join('tbl_unit_master AS um', 'um.fld_id = epd.fld_unit_id AND um.fld_isdeleted = 0', 'LEFT');
		$this->db->where(['epd.fld_proforma_id' => $id, 'epd.fld_isdeleted' => 0]);
		$product_det2 = $this->db->get()->result_array();

		$product_det = array_merge($product_det1, $product_det2);

		// --- Fetch customer product details ---
		$this->fetch_customer_product_details($product_det, $dealer_id);

		// --- Add image paths ---
		foreach ($product_det as &$prod) {
			$prod['image_path'] = !empty($prod['fld_prod_image']) ? FCPATH . $prod['fld_prod_image'] : '';
		}
		unset($prod);

		// --- Check if any product has discount ---
		$hasDiscount = false;
		foreach ($product_det as $prod) {
			if (!empty($prod['fld_disc_perc']) && floatval($prod['fld_disc_perc']) > 0) {
				$hasDiscount = true;
				break;
			}
		}

		//  NO manual draw_header() call here — Header() already fired on AddPage()

		// --- Draw items table ---
		//  draw_items_table still uses $this->currentHeaderData internally
		// but we remove the manual draw_header() call inside it (see note below)
		$totalAmount = $this->draw_items_table_new($pdf, $product_det, $hasDiscount);

		// --- Fetch master totals ---
		$piMastFields = 'fld_total_amt, fld_discount_per, fld_discount, fld_igst_amt, fld_cgst_amt, fld_sgst_amt, fld_sub_total2, fld_round_off, fld_tds, fld_tds_per, fld_grand_total, fld_packing_forwarding_amt, fld_transportation_amt, fld_gst_per';
		if ($this->db->field_exists('fld_hsn_distributed_json', 'tbl_proforma_invoice_master')) {
			$piMastFields .= ', fld_hsn_distributed_json';
		}
		$product_mast = $this->Master_model->getRecords(
			'tbl_proforma_invoice_master',
			array('fld_isdeleted' => 0, 'fld_proforma_invoice_id' => $id),
			$piMastFields
		);

		// --- Fetch Bank Details ---
		$bank_details = $this->Master_model->getRecords(
			'tbl_bank_account_details',
			array('fld_isdeleted' => 0, 'fld_account_for' => 'Proforma Invoice'),
			'fld_account_for, fld_bank_name, fld_ifsc_code, fld_account_no, fld_bank_ac_holder, fld_branch_name'
		);
		$bankData = !empty($bank_details) ? $bank_details[0] : [];

		// --- Draw footer ---
		$terms_condition = !empty($proforma_invoice['fld_terms_condition']) ? $proforma_invoice['fld_terms_condition'] : '';
		$this->draw_footer_new($pdf, $company, $totalAmount, $product_mast, $product_det, $terms_condition, $hasDiscount, $bankData, $proforma_invoice);

		// --- Dynamic filename ---
		$pi_no_parts  = explode('/', $proforma_invoice['fld_proforma_invoice_no']);
		$pi_short_id  = end($pi_no_parts);
		$customer_name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $dealer['fld_dealer_name']);
		$pi_date      = date('d-m-Y', strtotime($proforma_invoice['fld_created_date']));
		$fileName     = "PI-" . $pi_short_id . "-" . $customer_name . "-" . $pi_date . ".pdf";

		$pdf->Output($fileName, 'I');
		}

    public function generate_invoice_image_centric($id = "") {
    	if (ob_get_length()) {
    		ob_end_clean();
    	}
    	
    	// Suppress PNG iCCP profile warnings
    	error_reporting(E_ALL & ~E_WARNING);

		$this->billToRendered = false;

    	$this->load->library('Pdf'); 
    	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    	$pdf->SetCreator('Jyoti Chemicals');
    	$pdf->SetAuthor('Jyoti Chemicals');
    	$pdf->SetTitle('Proforma Invoice - Image Centric');

    	$pdf->setPrintHeader(false);
    	$pdf->setPrintFooter(false);

    	$pdf->SetMargins(8, 8, 8);
    	$pdf->SetAutoPageBreak(TRUE, 15);

    	$pdf->AddPage();

        // --- Fetch Proforma Invoice Data ---
    	$id = base64_decode($id);

    	$this->db->join('tbl_admin as a', 'a.fld_id = qm.fld_created_by and a.fld_isdeleted = 0', 'left');
    	$this->db->join('tbl_designation_master as dm', 'dm.fld_designation_id = a.fld_designation_id and dm.fld_isdeleted = 0', 'left');
    	$proforma_invoice_data = $this->Master_model->getRecords(
    		'tbl_proforma_invoice_master as qm',
    		array('qm.fld_proforma_invoice_id' => $id, 'qm.fld_isdeleted' => 0),
    		'qm.fld_proforma_invoice_id, qm.fld_proforma_invoice_no, qm.fld_proforma_invoice_date, qm.fld_created_date, qm.fld_shipping_address, a.fld_adm_name as created_by_name, a.fld_mobile_no as employee_mobile, a.fld_email as employee_email, dm.fld_designation_name as employee_designation, qm.fld_dealer_id, qm.fld_terms_condition, qm.fld_po_no, qm.fld_po_mode'
    	);

    	if (!empty($proforma_invoice_data)) {
    		$proforma_invoice = $proforma_invoice_data[0];
    	} else {
    		show_error('Proforma Invoice not found');
    		return;
    	}

        // --- Fetch Company Data ---
    $company_data = $this->Master_model->getRecords(
        'tbl_organization_master AS om',
        array('om.fld_isdeleted !=' => 1),
        'om.fld_org_id,om.fld_cin, om.fld_org_name, om.fld_org_address, om.fld_gst_no, om.fld_pan_no, om.fld_email, om.fld_org_contact, om.fld_logo, om.fld_state, om.fld_state_code, om.fld_bank_name, om.fld_bank_ac_holder, om.fld_account_no, om.fld_ifsc_code, om.fld_iso_details, om.fld_website'
    );

    	if (!empty($company_data)) {
    		$company = $company_data[0];
    		$company['logo_path'] = !empty($company['fld_logo']) ? FCPATH . 'uploads/' . $company['fld_logo'] : '';
    		
        // Use organization state id directly for GST comparison (no gst_code lookup)
        $company['fld_gst_state_code'] = trim($company['fld_state'] ?? '');
    	} else {
    		$company = array(
    			'fld_org_name' => 'Company Name',
    			'fld_org_address' => 'Company Address',
    			'fld_gst_no' => '',
    			'fld_pan_no' => '',
    			'fld_email' => '',
    			'fld_org_contact' => '',
    			'fld_state_code' => '',
    			'fld_gst_state_code' => '',
    			'fld_bank_name' => '',
    			'fld_account_no' => '',
    			'fld_ifsc_code' => '',
    			'fld_iso_details' => '',
    			'fld_website' => '',
    			'logo_path' => '',
    			'fld_bank_ac_holder' => ''
    		);
    	}

        // --- Fetch Customer Data ---
    	$dealer_id = $proforma_invoice['fld_dealer_id'];
    $this->db->select('dm.fld_dealer_name, dm.fld_dealer_address, dm.fld_gst_no, dm.fld_mobile_no, dm.fld_state_id AS fld_gst_state_code, sm.fld_state_name, dist.fld_dist_name, tal.fld_taluka_name');
    	$this->db->from('tbl_dealer_master AS dm');
    	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_dist_master AS dist', 'dist.fld_dist_id = dm.fld_dist_id AND dist.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_taluka_master AS tal', 'tal.fld_taluka_id = dm.fld_taluka_id AND tal.fld_isdeleted = 0', 'LEFT');
    	$this->db->where('dm.fld_dealer_id', $dealer_id);
    	$this->db->where('dm.fld_isdeleted', 0);
    	$dealer_query = $this->db->get();
    	$dealer_data = $dealer_query->result_array();

    	if (!empty($dealer_data)) {
    		$dealer = $dealer_data[0];
    	} else {
    		show_error('Customer not found');
    		return;
    	}

        // Save header data for repeating
    	$this->currentHeaderData = [
    		'company' => $company,
    		'proforma_invoice' => $proforma_invoice,
    		'dealer' => $dealer
    	];
    	
    	// Store employee data in proforma_invoice array for Sales Executive section
    	$this->currentHeaderData['proforma_invoice']['created_by_name'] = $proforma_invoice['created_by_name'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_mobile'] = $proforma_invoice['employee_mobile'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_email'] = $proforma_invoice['employee_email'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_designation'] = $proforma_invoice['employee_designation'] ?? '';

        // --- Get Product Details with Images ---
    	$this->db->select('epd.fld_proforma_invoice_details_id, epd.fld_product_group_id, epd.fld_product_master_id,epd.fld_qty, epd.fld_total_amt, epd.fld_unit,pm.fld_product_name, pm.fld_hsn_code, pm.fld_item_code, pm.fld_model_no, pg.fld_product_group_name,epd.fld_rate,epd.fld_disc_perc,epd.fld_disc_amt,pm.fld_hsn_code,epd.fld_gst_perc,epd.fld_taxable_amt,epd.fld_grand_total,pm.fld_prod_image,pm.fld_gst_percentage AS pm_gst_percentage,pm.fld_packing_qty_1');
    	$this->db->from('tbl_proforma_invoice_details AS epd');
    	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = epd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = epd.fld_product_group_id AND pg.fld_isdeleted = 0', 'LEFT');
    	$this->db->where(['epd.fld_proforma_invoice_id' => $id, 'epd.fld_isdeleted' => 0]);
    	$product_det = $this->db->get()->result_array();
    	
    	// Fetch customer product details for each product
    	$dealer_id = $proforma_invoice['fld_dealer_id'];
    	$this->fetch_customer_product_details($product_det, $dealer_id);
    	
    	// Add product image paths
    	foreach ($product_det as &$prod) {
    		$prod['image_path'] = !empty($prod['fld_prod_image']) ? FCPATH . 'uploads/product_image/' . $prod['fld_prod_image'] : '';
    	}
    	unset($prod); 
    	
        // Check if any product has discount
    	$hasDiscount = false;
    	foreach ($product_det as $prod) {
    		if (!empty($prod['fld_disc_perc']) && floatval($prod['fld_disc_perc']) > 0) {
    			$hasDiscount = true;
    			break;
    		}
    	}

        // Draw header
    	$this->draw_header($pdf, $company, $proforma_invoice, $dealer);

        // Draw image-centric product layout
    	$this->draw_image_centric_products($pdf, $product_det);

    	$piMastFields = 'fld_total_amt,fld_discount_per,fld_discount,fld_igst_amt,fld_cgst_amt,fld_sgst_amt,fld_sub_total2,fld_round_off,fld_tds,fld_tds_per,fld_grand_total,fld_packing_forwarding_amt,fld_transportation_amt, fld_gst_per';
    	if ($this->db->field_exists('fld_hsn_distributed_json', 'tbl_proforma_invoice_master')) {
    		$piMastFields .= ', fld_hsn_distributed_json';
    	}
    	$product_mast = $this->Master_model->getRecords('tbl_proforma_invoice_master',array('fld_isdeleted'=>0,'fld_proforma_invoice_id' => $id), $piMastFields);

        // Draw footer
    	$terms_condition = !empty($proforma_invoice['fld_terms_condition']) ? $proforma_invoice['fld_terms_condition'] : '';

		// Fetch Bank Details 1st
		$bank_details = $this->Master_model->getRecords(
			'tbl_bank_account_details',
			array(
				'fld_isdeleted' => 0,
				'fld_account_for' => 'Proforma Invoice' 
			),
			'fld_account_for, fld_bank_name, fld_ifsc_code, fld_account_no, fld_bank_ac_holder'
		);

		// Take first record (or apply condition if needed)
		$bankData = !empty($bank_details) ? $bank_details[0] : [];

    	$this->draw_footer($pdf,$company,0,$product_mast,$product_det,$terms_condition,$hasDiscount, $bankData, $proforma_invoice);

    	$pdf->Output('Proforma_Invoice-Image-Centric.pdf', 'I');
    }

    // private function draw_header($pdf, $company, $proforma_invoice, $dealer) {
	// 	// echo "<pre>";print_r($company);die;
	// 	$x = 8;
	// 	$y = 6;
	
	// 	if (!$this->billToRendered) {
	
	// 		/* ---------------- Logo ---------------- */
	// 		$pageWidth = 194;
	// 		$topSectionWidth = $pageWidth / 2;
	// 		$logoX = $x + 2;
	// 		$logoY = $y;
	// 		$logoWidth = 0;
	// 		$logoHeight = 14;
	
	// 		if (!empty($company['logo_path']) && file_exists($company['logo_path'])) {
	// 			$pdf->Image($company['logo_path'], $logoX, $logoY, $logoWidth, $logoHeight);
	// 		}
	
	// 		/* ---------------- Right Details ---------------- */
	// 		$textX = $x + $topSectionWidth + 4;
	// 		$textY = $logoY;
	// 		$labelWidth = 20;
	// 		$textWidth = $topSectionWidth - 8;
	// 		$valueWidth = $textWidth - $labelWidth;
	
	// 		$pdf->SetXY($textX, $textY);
	
	// 		// CIN
	// 		$pdf->SetFont('helvetica','B',9);
	// 		$pdf->Cell($labelWidth,4,'CIN :',0,0);
	// 		$pdf->SetFont('helvetica','',9);
	// 		$pdf->Cell($valueWidth,4,$company['fld_cin'],0,1);
	
	// 		// Address
	// 		$pdf->SetX($textX);
	// 		$pdf->SetFont('helvetica','B',9);
	// 		$pdf->Cell($labelWidth,4,'Address :',0,0);
	// 		$pdf->SetFont('helvetica','',9);
	// 		$pdf->MultiCell($valueWidth,4,$company['fld_org_address'],0,'L');
	
	// 		// GST NO
	// 		$pdf->SetX($textX);
	// 		$pdf->SetFont('helvetica','B',9);
	// 		$pdf->Cell($labelWidth,4,'GST No:',0,0);
	// 		$pdf->SetFont('helvetica','',9);
	// 		$pdf->Cell($valueWidth,4,$company['fld_gst_no'],0,1);
			
	// 		// Phone
	// 		$pdf->SetX($textX);
	// 		$pdf->SetFont('helvetica','B',9);
	// 		$pdf->Cell($labelWidth,4,'Phone :',0,0);
	// 		$pdf->SetFont('helvetica','',9);
	// 		$pdf->Cell($valueWidth,4,$company['fld_org_contact'],0,1);
	
	// 		// Website
	// 		$pdf->SetX($textX);
	// 		$pdf->SetFont('helvetica','B',9);
	// 		$pdf->Cell($labelWidth,4,'Website :',0,0);
	// 		$pdf->SetFont('helvetica','',9);
	// 		$pdf->Cell($valueWidth,4,$company['fld_website'],0,1);
	
	// 		/* ---------------- Vertical Navy Line ---------------- */
	// 		$lineX = $x + $topSectionWidth;
	// 		$lineTop = $logoY;
	// 		$lineBottom = max($pdf->GetY(), $logoY + 18);
	
	// 		$pdf->SetDrawColor(31, 56, 100); // navy blue
	// 		$pdf->SetLineWidth(0.3);
	// 		$pdf->Line($lineX, $lineTop, $lineX, $lineBottom);
	// 		$pdf->SetLineWidth(0.2);
	
	// 		/* ---------------- Horizontal Line ---------------- */
	// 		$headerEndY = $lineBottom + 1;
	
	// 		$pdf->SetDrawColor(31, 56, 100);
	// 		$pdf->SetLineWidth(0.5);
	// 		$pdf->Line($x, $headerEndY, $x + 194, $headerEndY);
	// 		$pdf->SetLineWidth(0.2);
	
	// 		/* ---------------- QUOTATION Banner ---------------- */
	// 		$bannerY = $headerEndY + 1;
	
	// 		// Set logo blue color
	// 		//$pdf->SetTextColor(46, 41, 98);
	// 		$pdf->SetDrawColor(46, 41, 98); // logo colour
	// 		$pdf->SetFillColor(46, 41, 98); // logo colour
	// 		$pdf->Rect($x, $bannerY, 194, 7, 'F');

	// 		$pdf->SetTextColor(255,255,255);
	// 		$pdf->SetFont('helvetica','B',13);
	// 		$pdf->SetXY($x, $bannerY);
	// 		$pdf->Cell(194,7,'QUOTATION',0,0,'C');

	// 		// $pdf->SetTextColor(0,0,0);
	
	// 		$this->billToRendered = true;
        
    //     // Proforma banner - Navy Blue background with white text
	// 		$bannerY = $headerEndY + 1;
	// 		$pdf->SetFillColor(44, 38, 84);
	// 		$pdf->SetDrawColor(44, 38, 84);
	// 		$pdf->Rect($x, $bannerY, 194, 7, 'F');
	// 		$pdf->SetTextColor(255, 255, 255);
	// 		$pdf->SetFont('helvetica', 'B', 13);
	// 		$pdf->SetXY($x, $bannerY);
	// 		$pdf->Cell(194, 7, 'PROFORMA INVOICE', 0, 0, 'C');
	// 		$pdf->SetTextColor(0, 0, 0);
			
	// 		$contentY = $bannerY + 8;
	// 		$colWidth = 63; // Divide 194mm into 3 equal parts roughly
	// 		$lineHeight = 4;

	// 		// --- COLUMN 1: Bill To ---
	// 		$pdf->SetXY($x, $contentY);
	// 		$pdf->SetFont('helvetica', 'B', 10);
	// 		$pdf->SetTextColor(31, 56, 100);
	// 		$pdf->Cell($colWidth, 5, 'Bill To,', 0, 1, 'L');
			
	// 		$pdf->SetTextColor(0, 0, 0);
	// 		$pdf->SetFont('helvetica', 'B', 8); 
	// 		$pdf->Cell(25, $lineHeight, 'Customer Name :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); 
	// 		$pdf->MultiCell($colWidth-25, $lineHeight, $dealer['fld_dealer_name'], 0, 'L');
			
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Address :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8);
	// 		$fullAddress = $dealer['fld_dealer_address'].', '.$dealer['fld_taluka_name'].', '.$dealer['fld_dist_name'].', '.$dealer['fld_state_name'];
	// 		$pdf->MultiCell($colWidth-25, $lineHeight, $fullAddress, 0, 'L');
			
	// 		$pdf->SetX($x);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Contact Person :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->MultiCell($colWidth-25, $lineHeight, $dealer['contact_person_name'], 0, 'L');
			
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Contact No :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-25, $lineHeight, $dealer['fld_mobile_no'], 0, 1);
			
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'GSTIN :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-25, $lineHeight, $dealer['fld_gst_no'], 0, 1);
			
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'E-mail :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); 
	// 		$pdf->MultiCell($colWidth-25, $lineHeight, $dealer['fld_email'], 0, 'L');
	// 		$endY1 = $pdf->GetY();

	// 		// --- COLUMN 2: Shipped ---
	// 		$col2X = $x + $colWidth + 2;
	// 		$pdf->SetXY($col2X, $contentY);
	// 		$pdf->SetFont('helvetica', 'B', 10);
	// 		$pdf->SetTextColor(31, 56, 100);
	// 		$pdf->Cell($colWidth, 5, 'Shipped', 0, 1, 'L');
			
	// 		$pdf->SetTextColor(0, 0, 0);
	// 		$pdf->SetX($col2X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Customer Name :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->MultiCell($colWidth-25, $lineHeight, $dealer['fld_dealer_name'], 0, 'L');
			
	// 		$pdf->SetX($col2X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Address :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8);
	// 		$shipAddr = !empty($proforma_invoice['fld_shipping_address']) ? $proforma_invoice['fld_shipping_address'] : $fullAddress;
	// 		$pdf->MultiCell($colWidth-25, $lineHeight, $shipAddr, 0, 'L');
			
	// 		$pdf->SetX($col2X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Contact Person :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8);$pdf->MultiCell($colWidth-25, $lineHeight, $dealer['contact_person_name'], 0, 'L');
			
	// 		$pdf->SetX($col2X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(25, $lineHeight, 'Contact No :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-25, $lineHeight, $dealer['fld_mobile_no'], 0, 1);
	// 		$endY2 = $pdf->GetY();

	// 		// --- COLUMN 3: Proforma Details ---
	// 		$col3X = $col2X + $colWidth + 2;
	// 		$pdf->SetXY($col3X, $contentY);
	// 	//	$pdf->SetFont('helvetica', 'B', 10);
	// 	//	$pdf->SetTextColor(31, 56, 100);
	// 		//$pdf->Cell($colWidth, 5, 'Proforma No:-', 0, 1, 'L');
	// 		$pdf->SetFont('helvetica', 'B', 10);
	// 		$pdf->SetTextColor(31, 56, 100);
	// 		$pdf->Cell(30, $lineHeight, 'Proforma No :', 0, 0);
			
	// 		$pdf->SetTextColor(0, 0, 0);
	// 	//	$pdf->SetX($col3X);
	// 	//	$pdf->SetFont('helvetica', '', 9); 
	// 		$pdf->SetFont('helvetica', '', 8); 
	// 		$pdf->Cell($colWidth, $lineHeight, $proforma_invoice['fld_proforma_invoice_no'], 0, 1);
			
	// 		$pdf->SetX($col3X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(30, $lineHeight, 'Date :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-30, $lineHeight, date('d/m/Y', strtotime($proforma_invoice['fld_proforma_invoice_date'])), 0, 1);
			
	// 		$pdf->SetX($col3X);
	// 	//	$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(30, $lineHeight, 'Purchase Order No :', 0, 0);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(30, $lineHeight, 'PO/LOI NO :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-30, $lineHeight, $proforma_invoice['fld_po_no'] ?? '-', 0, 1);
			
	// 		$pdf->SetX($col3X);
	// 		$pdf->SetFont('helvetica', 'B', 8); 
	// 		$pdf->Cell(30, $lineHeight, 'PO Date :', 0, 0);

	// 		$pdf->SetFont('helvetica', '', 8);

	// 		// Check if date is not empty and is not a zeroed-out MySQL date
	// 		$poDate = '-';
	// 		if (
	// 		!empty($proforma_invoice['fld_po_date']) && 
	// 		$proforma_invoice['fld_po_date'] != '0000-00-00' && 
	// 		$proforma_invoice['fld_po_date'] != '0000-00-00 00:00:00'
	// 		) {
	// 		$poDate = date('d/m/Y', strtotime($proforma_invoice['fld_po_date']));
	// 		}

	// 		$pdf->Cell($colWidth - 30, $lineHeight, $poDate, 0, 1);
			
	// 		$pdf->SetX($col3X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(30, $lineHeight, 'Order Rec Mode :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-30, $lineHeight, $proforma_invoice['fld_po_mode'] ?? '-', 0, 1);
			
	// 		$pdf->SetX($col3X);
	// 		$pdf->SetFont('helvetica', 'B', 8); $pdf->Cell(30, $lineHeight, 'Prepared By :', 0, 0);
	// 		$pdf->SetFont('helvetica', '', 8); $pdf->Cell($colWidth-30, $lineHeight, $proforma_invoice['created_by_name'], 0, 1);
	// 		$endY3 = $pdf->GetY();

	// 	//	$remark = isset($proforma_invoice['fld_remark']) ? trim($proforma_invoice['fld_remark']) : '';

	// 		//if(!empty($remark)){
	// 		///	$pdf->SetX($col3X);
	// 		//	$pdf->SetFont('helvetica', 'B', 8);
	// 		//	$pdf->Cell(30, $lineHeight, 'Remark :', 0, 0);

	// 		//	$pdf->SetFont('helvetica', '', 8);
	// 		//	$pdf->MultiCell($colWidth-30, 4, $remark, 0, 'L', false, 1);
	// 		//}
	// 		//$endY3 = $pdf->GetY();

	// 		// --- 4. Vertical Orange Separators ---
	// 		$maxY = max($endY1, $endY2, $endY3);
	// 		$pdf->SetDrawColor(240, 126, 27);
	// 		$pdf->SetLineWidth(0.3);
			
	// 		// Line between Col 1 and 2
	// 		$pdf->Line($col2X - 1, $contentY, $col2X - 1, $maxY);
	// 		// Line between Col 2 and 3
	// 		$pdf->Line($col3X - 1, $contentY, $col3X - 1, $maxY);
	// 		// Bottom horizontal orange line
	// 		$pdf->Line($x, $maxY + 1, $x + 194, $maxY + 1);
			
	// 		$pdf->SetY($maxY + 2);
	// 		$this->billToRendered = true;
	// 	} else {
	// 		// Repeated header logic for new pages
	// 		$pdf->SetXY($x, $y);
	// 		$pdf->SetDrawColor(240, 126, 27);
	// 		$pdf->SetLineWidth(0.5);
	// 		$pdf->Line($x, $y, $x + 194, $y);
	// 		$pdf->SetY($y + 3);
	// 	}
	// }

	private function draw_header($pdf, $company, $proforma_invoice, $dealer) {
		$x = 8;
		$y = 6;

		if (!$this->billToRendered) {

			/* ---------------- Logo ---------------- */
			$pageWidth       = 194;
			$topSectionWidth = $pageWidth / 2;
			$logoX           = $x + 2;
			$logoY           = $y;
			$logoWidth       = 0;
			$logoHeight      = 14;

			if (!empty($company['logo_path']) && file_exists($company['logo_path'])) {
				$pdf->Image($company['logo_path'], $logoX, $logoY, $logoWidth, $logoHeight);
			}

			/* ---------------- Right Details (unchanged) ---------------- */
			$textX      = $x + $topSectionWidth + 4;
			$textY      = $logoY;
			$labelWidth = 20;
			$textWidth  = $topSectionWidth - 8;
			$valueWidth = $textWidth - $labelWidth;

			$pdf->SetXY($textX, $textY);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, 4, 'CIN :', 0, 0);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->Cell($valueWidth, 4, $company['fld_cin'], 0, 1);

			$pdf->SetX($textX);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, 4, 'Address :', 0, 0);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->MultiCell($valueWidth, 4, $company['fld_org_address'], 0, 'L');

			$pdf->SetX($textX);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, 4, 'GST No:', 0, 0);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->Cell($valueWidth, 4, $company['fld_gst_no'], 0, 1);

			$pdf->SetX($textX);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, 4, 'Phone :', 0, 0);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->Cell($valueWidth, 4, $company['fld_org_contact'], 0, 1);

			$pdf->SetX($textX);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, 4, 'Website :', 0, 0);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->Cell($valueWidth, 4, $company['fld_website'], 0, 1);

			/* ---------------- Vertical Navy Line ---------------- */
			$lineX      = $x + $topSectionWidth;
			$lineTop    = $logoY;
			$lineBottom = max($pdf->GetY(), $logoY + 18);

			$pdf->SetDrawColor(31, 56, 100);
			$pdf->SetLineWidth(0.3);
			$pdf->Line($lineX, $lineTop, $lineX, $lineBottom);
			$pdf->SetLineWidth(0.2);

			/* ---------------- Horizontal Line ---------------- */
			$headerEndY = $lineBottom + 1;
			$pdf->SetDrawColor(31, 56, 100);
			$pdf->SetLineWidth(0.5);
			$pdf->Line($x, $headerEndY, $x + 194, $headerEndY);
			$pdf->SetLineWidth(0.2);

			/* ---------------- Proforma Invoice Banner ---------------- */
			$bannerY = $headerEndY + 1;
			$pdf->SetFillColor(44, 38, 84);
			$pdf->SetDrawColor(44, 38, 84);
			$pdf->Rect($x, $bannerY, 194, 7, 'F');
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('helvetica', 'B', 13);
			$pdf->SetXY($x, $bannerY);
			$pdf->Cell(194, 7, 'PROFORMA INVOICE', 0, 0, 'C');
			$pdf->SetTextColor(0, 0, 0);

			$contentY   = $bannerY + 8;
			$colWidth   = 73;
			$lineHeight = 4;

			/*
			 * inlineLine:
			 *   - First line:  "<bold label> value..." starts at $colX
			 *   - Wrapped lines of a long value: wrap back to $colX
			 *     (NOT indented by the label width)
			 *
			 * Strategy: render as a single HTML string via writeHTMLCell
			 * at $colX with full $colWidth.  TCPDF wraps the whole block
			 * from $colX so every overflow line starts at the column edge.
			 *
			 * $curY is passed by reference and updated after each call.
			 */
			$inlineLine = function($colX, $label, $value, $colWidth, &$curY)
				use ($pdf, $lineHeight)
			{
				// Escape special HTML chars in value so they render correctly
				$safeValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
				$html = '<b>' . $label . '</b>' . $safeValue;

				$pdf->SetFont('helvetica', '', 8);
				$pdf->writeHTMLCell(
					$colWidth,   // width  = full column
					0,           // height = auto
					$colX,       // x
					$curY,       // y
					$html,
					0,           // no border
					1,           // ln: move below after
					false,       // fill
					true,        // reseth
					'L',         // align
					true         // autopadding off
				);

				$curY = $pdf->GetY();
			};

			/* ─── COLUMN 1: Bill To ─────────────────────────────────── */
			$pdf->SetXY($x, $contentY);
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->SetTextColor(31, 56, 100);
			$pdf->Cell($colWidth, 5, 'Bill To,', 0, 1, 'L');
			$pdf->SetTextColor(0, 0, 0);

			$fullAddress = $dealer['fld_dealer_address'] . ', '
			             . $dealer['fld_taluka_name'] . ', '
			             . $dealer['fld_dist_name'] . ', '
			             . $dealer['fld_state_name'];

			$curY1 = $contentY + 5;
			$inlineLine($x, 'Customer Name : ', $dealer['fld_dealer_name'],       $colWidth, $curY1);
			$inlineLine($x, 'Address : ',       $fullAddress,                     $colWidth, $curY1);
			$curY1 += 2;
			$inlineLine($x, 'Contact Person : ',$dealer['contact_person_name'],   $colWidth, $curY1);
			$inlineLine($x, 'Contact No : ',    $dealer['fld_mobile_no'],         $colWidth, $curY1);
			$inlineLine($x, 'GSTIN : ',         $dealer['fld_gst_no'],            $colWidth, $curY1);
			$inlineLine($x, 'E-mail : ',        $dealer['fld_email'],             $colWidth, $curY1);
			$endY1 = $curY1;

			/* ─── COLUMN 2: Shipped ──────────────────────────────────── */
			$col2X = $x + $colWidth + 5;

			$pdf->SetXY($col2X, $contentY);
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->SetTextColor(31, 56, 100);
			$pdf->Cell($colWidth, 5, 'Shipped', 0, 1, 'L');
			$pdf->SetTextColor(0, 0, 0);

			$shipAddr = !empty($proforma_invoice['fld_shipping_address'])
			          ? $proforma_invoice['fld_shipping_address']
			          : $fullAddress;

			$curY2 = $contentY + 5;
			$inlineLine($col2X, 'Customer Name : ', $dealer['fld_dealer_name'],      $colWidth, $curY2);
			$inlineLine($col2X, 'Address : ',       $shipAddr,                       $colWidth, $curY2);
			$inlineLine($col2X, 'Contact Person : ',$dealer['contact_person_name'],  $colWidth, $curY2);
			$inlineLine($col2X, 'Contact No : ',    $dealer['fld_mobile_no'],        $colWidth, $curY2);
			$endY2 = $curY2;

			/* ─── COLUMN 3: Proforma Details ─────────────────────────── */
			// $col3X = $col2X + $colWidth + 2;
			$col3X = $col2X + $colWidth + 2;

			$poDate = '-';
			if (
				!empty($proforma_invoice['fld_po_date']) &&
				$proforma_invoice['fld_po_date'] != '0000-00-00' &&
				$proforma_invoice['fld_po_date'] != '0000-00-00 00:00:00'
			) {
				$poDate = date('d/m/Y', strtotime($proforma_invoice['fld_po_date']));
			}

			$curY3 = $contentY;
			$inlineLine($col3X, 'Proforma No : ',    $proforma_invoice['fld_proforma_invoice_no'],                             $colWidth, $curY3);
			$inlineLine($col3X, 'Date : ',           date('d/m/Y', strtotime($proforma_invoice['fld_proforma_invoice_date'])), $colWidth, $curY3);
			$inlineLine($col3X, 'PO/LOI NO : ',      $proforma_invoice['fld_po_no'] ?? '-',                                   $colWidth, $curY3);
			$inlineLine($col3X, 'PO Date : ',        $poDate,                                                                  $colWidth, $curY3);
			$inlineLine($col3X, 'Order Rec Mode : ', $proforma_invoice['fld_po_mode'] ?? '-',                                 $colWidth, $curY3);
			$inlineLine($col3X, 'Prepared By : ',    $proforma_invoice['created_by_name'],                                    $colWidth, $curY3);
			$endY3 = $curY3;

			/* ─── Vertical Orange Separators ────────────────────────── */
			$maxY = max($endY1, $endY2, $endY3);
			$pdf->SetDrawColor(240, 126, 27);
			$pdf->SetLineWidth(0.3);
			$pdf->Line($col2X - 5, $contentY, $col2X - 5, $maxY);
			$pdf->Line($col3X - 1, $contentY, $col3X - 1, $maxY);
			$pdf->Line($x, $maxY + 1, $x + 194, $maxY + 1);

			$pdf->SetY($maxY + 2);
			$this->billToRendered = true;

		} else {
			// Repeated header on continuation pages
			$pdf->SetXY($x, $y);
			$pdf->SetDrawColor(240, 126, 27);
			$pdf->SetLineWidth(0.5);
			$pdf->Line($x, $y, $x + 194, $y);
			$pdf->SetY($y + 3);
		}
	}


 private function render_items_table_header($pdf, $hasDiscount = false)
{
    if ($hasDiscount) {
        // Total must be 194
        $w = [
            'sr'    => 8,
            'desc'  => 54,  // Increased from 42 (+12)
            'wt'    => 16,
            'hsn'   => 16,
            'pack'  => 20,
            'qty'   => 14,
            'rate'  => 18,
            'disc'  => 14,
            'damt'  => 14,
            'amt'   => 20,  // Decreased from 32 (-12)
        ];
    } else {
        // Total must be 194
        $w = [
            'sr'    => 8,
            'desc'  => 76,  // Increased from 56 (+20)
            'wt'    => 16,
            'hsn'   => 16,
            'pack'  => 20,
            'qty'   => 14,
            'rate'  => 18,
            'disc'  => 0,
            'damt'  => 0,
            'amt'   => 26,  // Decreased from 46 (-20)
        ];
    }

    $headerHeight = 10; 

    // Styling
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(240, 126, 27); // Orange Border
    $pdf->SetTextColor(31, 56, 100);  // Navy Blue Text
    $pdf->SetFont('helvetica', 'B', 8);

    $startX = 8;
    $startY = $pdf->GetY();

    $pdf->SetXY($startX, $startY);

    // 1. Sr. No.
    $pdf->MultiCell($w['sr'], $headerHeight, "Sr.\nNo.", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 2. Description (Now Wider)
    $pdf->MultiCell($w['desc'], $headerHeight, "Description", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 3. Product Weight
    $pdf->MultiCell($w['wt'], $headerHeight, "Product\nWeight(gm)", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 4. HSN Code
    $pdf->MultiCell($w['hsn'], $headerHeight, "HSN Code", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 5. Packing
    $pdf->MultiCell($w['pack'], $headerHeight, "No. of\nPolybag/Box", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 6. Qty
    $pdf->MultiCell($w['qty'], $headerHeight, "Qty", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    // 7. Rate
    $pdf->MultiCell($w['rate'], $headerHeight, "Rate (Rs.)\nPer Nos.", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');

    if ($hasDiscount) {
        $pdf->MultiCell($w['disc'], $headerHeight, "Disc.%",   1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');
        $pdf->MultiCell($w['damt'], $headerHeight, "Disc Amt", 1, 'C', 0, 0, '', '', true, 0, false, true, $headerHeight, 'M');
    }

    // 8. Amount (Now Shorter)
    $pdf->MultiCell($w['amt'], $headerHeight, "Amount", 1, 'C', 0, 1, '', '', true, 0, false, true, $headerHeight, 'M');

    // Reset styles
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
}

	private function draw_items_table($pdf, $products, $hasDiscount = false) {

    $x = 8;
    $minRowHeight = 11;

    // Adjusted Widths — Exactly matching the updated header
    if ($hasDiscount) {
        $w = [
            'sr'   => 8,
            'desc' => 54,
            'wt'   => 16,
            'hsn'  => 16,
            'pack' => 20,
            'qty'  => 14,
            'rate' => 18,
            'disc' => 14,
            'damt' => 14,
            'amt'  => 20,
        ];
        // Total: 8+54+16+16+20+14+18+14+14+20 = 194
    } else {
        $w = [
            'sr'   => 8,
            'desc' => 76,
            'wt'   => 16,
            'hsn'  => 16,
            'pack' => 20,
            'qty'  => 14,
            'rate' => 18,
            'disc' => 0,
            'damt' => 0,
            'amt'  => 26,
        ];
        // Total: 8+76+16+16+20+14+18+26 = 194
    }

    $pdf->SetDrawColor(240, 126, 27);
    $pdf->SetLineWidth(0.2);

    $this->render_items_table_header($pdf, $hasDiscount);

    $counter     = 1;
    $totalAmount = 0;

    foreach ($products as $prod) {

        // ── Build description HTML (same as before) ──────────────────────────
        $productName   = '<b>' . $prod['fld_product_name'] . '</b>';
        $productRemark = '';
        if (!empty($prod['fld_remark'])) {
            $productRemark = '<br><span style="font-size: 7pt; font-weight: normal; color: #444;">'
                           . nl2br(htmlspecialchars($prod['fld_remark']))
                           . '</span>';
        }
        $descHTML = $productName . $productRemark;

        // ── Calculate the actual height the description cell needs ────────────
        // getStringHeight returns the height of an HTML string rendered at the
        // given width without actually printing anything to the page.
        $pdf->SetFont('helvetica', 'B', 8); // match the font used inside writeHTMLCell
        $descHeight = $pdf->getStringHeight($w['desc'], $descHTML, true, false, '', 1);
        // Packing cell can also wrap (two lines: "qty x boxes\npackingName")
        $pdf->SetFont('helvetica', '', 7);
        if (isset($prod['fld_path']) && $prod['fld_path'] == "uploads/proforma_other_products/") {
            $packingText = $prod['fld_packing_qty'] ?? '';
        } else {
            $packingQty    = (float)($prod['fld_packing_qty'] ?? '1');
            $packingQty    = $packingQty > 0 ? $packingQty : 1; // Prevent division by zero
            $multiplyValue = $prod['fld_no_of_bags'] ?? '';
            $packingName   = $prod['fld_rm_item_name'] ?? '';
            $packingText   = $packingQty . " x " . $multiplyValue . "\n" . $packingName;
			
        }
        $packHeight = $pdf->getStringHeight($w['pack'], $packingText, false, false, '', 1) + 3;

        // Dynamic row height = max of all cells, but never less than minRowHeight
        $rowHeight = max($minRowHeight, $descHeight, $packHeight);

        // ── Page-break check uses the dynamic height ──────────────────────────
        if ($pdf->GetY() + $rowHeight > ($pdf->getPageHeight() - 30)) {
            $pdf->AddPage();
            $this->draw_header(
                $pdf,
                $this->currentHeaderData['company'],
                $this->currentHeaderData['proforma_invoice'],
                $this->currentHeaderData['dealer']
            );
            $this->render_items_table_header($pdf, $hasDiscount);
        }

        $startY = $pdf->GetY();
        $startX = $x;

        // 1. Sr. No.
        $pdf->SetXY($startX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($w['sr'], $rowHeight, $counter, 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 2. Description (dynamic height)
        $descX = $startX + $w['sr'];
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->writeHTMLCell($w['desc'], $rowHeight, $descX, $startY, $descHTML, 1, 0, 0, true, 'L', true);

        // 3. Weight
        $wtX = $descX + $w['desc'];
        $pdf->SetXY($wtX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        if (isset($prod['fld_path']) && $prod['fld_path'] == "uploads/proforma_other_products/") {
            $loc_weight_display = $prod['fld_wt_pcs'];
        } else {
            $loc_weight_display = $prod['fld_weight'] . " ± " . ($prod['fld_tolerance'] ?? '-');
        }
        $pdf->MultiCell($w['wt'], $rowHeight, $loc_weight_display, 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 4. HSN
        $hsnX = $wtX + $w['wt'];
        $pdf->SetXY($hsnX, $startY);
        $pdf->MultiCell($w['hsn'], $rowHeight, $prod['fld_hsn_code'] ?? '-', 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 5. Packing (reuse $packingText computed above)
        $packX = $hsnX + $w['hsn'];
        $pdf->SetXY($packX, $startY);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell($w['pack'], $rowHeight, $packingText, 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 6. Qty
        $qtyX = $packX + $w['pack'];
        $pdf->SetXY($qtyX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($w['qty'], $rowHeight, $prod['fld_qty'], 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 7. Rate
        $rateX = $qtyX + $w['qty'];
        $pdf->SetXY($rateX, $startY);
        $pdf->MultiCell($w['rate'], $rowHeight, number_format((float)$prod['fld_rate'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 8. Disc % and Disc Amt (conditional)
        $nextX = $rateX + $w['rate'];
        if ($hasDiscount) {
            $pdf->SetXY($nextX, $startY);
            $pdf->MultiCell($w['disc'], $rowHeight, number_format((float)$prod['fld_disc_perc'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $nextX += $w['disc'];

            $pdf->SetXY($nextX, $startY);
            $pdf->MultiCell($w['damt'], $rowHeight, money_format_india((float)$prod['fld_disc_amt'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $nextX += $w['damt'];
        }

        // 9. Amount
        $pdf->SetXY($nextX, $startY);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->MultiCell($w['amt'], $rowHeight, money_format_india($prod['fld_total_amt'], 2), 1, 'R', 0, 1, '', '', true, 0, false, true, $rowHeight, 'M');

        // Ensure cursor moves exactly to the bottom of this row
        $pdf->SetY($startY + $rowHeight);

        $totalAmount += $prod['fld_total_amt'];
        $counter++;
    }

    return $totalAmount;
}
	private function draw_items_table_new($pdf, $products, $hasDiscount = false) {

    $x = 8;
    $minRowHeight = 11;

    // Adjusted Widths — Exactly matching the updated header
    if ($hasDiscount) {
        $w = [
            'sr'   => 8,
            'desc' => 54,
            'wt'   => 16,
            'hsn'  => 16,
            'pack' => 20,
            'qty'  => 14,
            'rate' => 18,
            'disc' => 14,
            'damt' => 14,
            'amt'  => 20,
        ];
        // Total: 8+54+16+16+20+14+18+14+14+20 = 194
    } else {
        $w = [
            'sr'   => 8,
            'desc' => 76,
            'wt'   => 16,
            'hsn'  => 16,
            'pack' => 20,
            'qty'  => 14,
            'rate' => 18,
            'disc' => 0,
            'damt' => 0,
            'amt'  => 26,
        ];
        // Total: 8+76+16+16+20+14+18+26 = 194
    }

    $pdf->SetDrawColor(240, 126, 27);
    $pdf->SetLineWidth(0.2);

    $this->render_items_table_header($pdf, $hasDiscount);

    $counter     = 1;
    $totalAmount = 0;

    foreach ($products as $prod) {

        // ── Build description HTML (same as before) ──────────────────────────
        $productName   = '<b>' . $prod['fld_product_name'] . '</b>';
        $productRemark = '';
        if (!empty($prod['fld_remark'])) {
            $productRemark = '<br><span style="font-size: 7pt; font-weight: normal; color: #444;">'
                           . nl2br(htmlspecialchars($prod['fld_remark']))
                           . '</span>';
        }
        $descHTML = $productName . $productRemark;

        // ── Calculate the actual height the description cell needs ────────────
        // getStringHeight returns the height of an HTML string rendered at the
        // given width without actually printing anything to the page.
        $pdf->SetFont('helvetica', 'B', 8); // match the font used inside writeHTMLCell
        $descHeight = $pdf->getStringHeight($w['desc'], $descHTML, true, false, '', 1);
        // Packing cell can also wrap (two lines: "qty x boxes\npackingName")
        $pdf->SetFont('helvetica', '', 7);
        if (isset($prod['fld_path']) && $prod['fld_path'] == "uploads/proforma_other_products/") {
            $packingText = $prod['fld_packing_qty'] ?? '';
        } else {
            $packingQty    = (float)($prod['fld_packing_qty'] ?? '1');
            $packingQty    = $packingQty > 0 ? $packingQty : 1; // Prevent division by zero
            $multiplyValue = $prod['fld_no_of_bags'] ?? '';
            $packingName   = $prod['fld_rm_item_name'] ?? '';
            $packingText   = $packingQty . " x " . $multiplyValue . "\n" . $packingName;
			
        }
        $packHeight = $pdf->getStringHeight($w['pack'], $packingText, false, false, '', 1) + 3;

        // Dynamic row height = max of all cells, but never less than minRowHeight
        $rowHeight = max($minRowHeight, $descHeight, $packHeight);

        // ── Page-break check uses the dynamic height ──────────────────────────
        // if ($pdf->GetY() + $rowHeight > ($pdf->getPageHeight() - 30)) {
        //     $pdf->AddPage();
        //     $this->draw_header(
        //         $pdf,
        //         $this->currentHeaderData['company'],
        //         $this->currentHeaderData['proforma_invoice'],
        //         $this->currentHeaderData['dealer']
        //     );
        //     $this->render_items_table_header($pdf, $hasDiscount);
        // }

		if ($pdf->GetY() + $rowHeight > ($pdf->getPageHeight() - 30)) {
			$pdf->AddPage();             //  Header() fires automatically
			$this->render_items_table_header($pdf, $hasDiscount);
		}

        $startY = $pdf->GetY();
        $startX = $x;

        // 1. Sr. No.
        $pdf->SetXY($startX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($w['sr'], $rowHeight, $counter, 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 2. Description (dynamic height)
        $descX = $startX + $w['sr'];
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->writeHTMLCell($w['desc'], $rowHeight, $descX, $startY, $descHTML, 1, 0, 0, true, 'L', true);

        // 3. Weight
        $wtX = $descX + $w['desc'];
        $pdf->SetXY($wtX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        if (isset($prod['fld_path']) && $prod['fld_path'] == "uploads/proforma_other_products/") {
            $loc_weight_display = $prod['fld_wt_pcs'];
        } else {
            $loc_weight_display = $prod['fld_weight'] . " ± " . ($prod['fld_tolerance'] ?? '-');
        }
        $pdf->MultiCell($w['wt'], $rowHeight, $loc_weight_display, 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 4. HSN
        $hsnX = $wtX + $w['wt'];
        $pdf->SetXY($hsnX, $startY);
        $pdf->MultiCell($w['hsn'], $rowHeight, $prod['fld_hsn_code'] ?? '-', 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 5. Packing (reuse $packingText computed above)
        $packX = $hsnX + $w['hsn'];
        $pdf->SetXY($packX, $startY);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell($w['pack'], $rowHeight, $packingText, 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 6. Qty
        $qtyX = $packX + $w['pack'];
        $pdf->SetXY($qtyX, $startY);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell($w['qty'], $rowHeight, $prod['fld_qty'], 1, 'C', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 7. Rate
        $rateX = $qtyX + $w['qty'];
        $pdf->SetXY($rateX, $startY);
        $pdf->MultiCell($w['rate'], $rowHeight, number_format((float)$prod['fld_rate'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');

        // 8. Disc % and Disc Amt (conditional)
        $nextX = $rateX + $w['rate'];
        if ($hasDiscount) {
            $pdf->SetXY($nextX, $startY);
            $pdf->MultiCell($w['disc'], $rowHeight, number_format((float)$prod['fld_disc_perc'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $nextX += $w['disc'];

            $pdf->SetXY($nextX, $startY);
            $pdf->MultiCell($w['damt'], $rowHeight, money_format_india((float)$prod['fld_disc_amt'], 2), 1, 'R', 0, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $nextX += $w['damt'];
        }

        // 9. Amount
        $pdf->SetXY($nextX, $startY);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->MultiCell($w['amt'], $rowHeight, money_format_india($prod['fld_total_amt'], 2), 1, 'R', 0, 1, '', '', true, 0, false, true, $rowHeight, 'M');

        // Ensure cursor moves exactly to the bottom of this row
        $pdf->SetY($startY + $rowHeight);

        $totalAmount += $prod['fld_total_amt'];
        $counter++;
    }

    return $totalAmount;
}

    private function draw_hsn_table($pdf, $products)
    {
		
    	$pdf->Ln(4);
    	$pdf->SetFont('helvetica', 'B', 8);
    	$pdf->SetFillColor(240, 240, 240);

	    // HSN Table Header
	    	$this->draw_hsn_header($pdf);
	    	$pdf->SetFont('helvetica', '', 7);

	    	$rowHeight = 7;
	    	$totalAmount = 0;
	    	$grand_total_tax_amt = 0;

	    // ===== Group products by HSN + GST% =====
	    	$grouped = [];
	    	foreach ($products as $p) {
	    		$key = $p['fld_hsn_code'] . '_' . $p['fld_gst_perc'];
	    		if (!isset($grouped[$key])) {
	    			$grouped[$key] = [
	    				'hsn' => $p['fld_hsn_code'],
	    				'gst_per' => $p['fld_gst_perc'],
	    				'taxable' => 0,
	    				'gst_amt' => 0
	    			];
	    		}
	    		$grouped[$key]['taxable'] += $p['fld_taxable_amt'];
	    		$grouped[$key]['gst_amt'] += ($p['fld_taxable_amt'] * $p['fld_gst_perc'] / 100);
	    	}

	    // ===== Determine CGST/SGST vs IGST =====
	    	$depot_state_code  = trim($this->currentHeaderData['company']['fld_gst_state_code'] ?? '');
	    	$dealer_state_code = trim($this->currentHeaderData['dealer']['fld_gst_state_code'] ?? '');
	    	$is_same_state_items = (!empty($depot_state_code) && !empty($dealer_state_code) && $depot_state_code == $dealer_state_code);

	    	foreach ($grouped as $row) {
	    		if ($pdf->GetY() + $rowHeight + 20 > $pdf->getPageHeight()) {
	    			$pdf->AddPage();
	    			$this->draw_header($pdf, $this->currentHeaderData['company'], $this->currentHeaderData['proforma_invoice'], $this->currentHeaderData['dealer']);
	    			$this->draw_hsn_header($pdf);
	    		}

	    		$cgst = $sgst = $igst = $cgst_per = $sgst_per = 0;

	    		if ($is_same_state_items) {
	            // Same state → CGST + SGST
	    			$cgst = $row['gst_amt'] / 2;
	    			$sgst = $row['gst_amt'] / 2;
	    			$cgst_per = $row['gst_per'] / 2;
	    			$sgst_per = $row['gst_per'] / 2;
	    		} else {
	            // Interstate → IGST
	    			$igst = $row['gst_amt'];
	    		}

	    		$total_tax_amt = $cgst + $sgst + $igst;
	    		$grand_total_tax_amt += $total_tax_amt;
	    		$totalAmount += $row['taxable'];

	        // ===== Draw table row =====
	    		$pdf->Cell(30, $rowHeight, $row['hsn'], 1, 0, 'C');
	    		$pdf->Cell(25, $rowHeight, number_format($row['taxable'], 2), 1, 0, 'R');
	    		$pdf->Cell(12, $rowHeight, number_format($row['gst_per'], 2), 1, 0, 'C');
	    		$pdf->Cell(12, $rowHeight, number_format($cgst_per, 2), 1, 0, 'C');
	    		$pdf->Cell(25, $rowHeight, number_format($cgst, 2), 1, 0, 'R');
	    		$pdf->Cell(12, $rowHeight, number_format($sgst_per, 2), 1, 0, 'C');
	    		$pdf->Cell(25, $rowHeight, number_format($sgst, 2), 1, 0, 'R');
	    		$pdf->Cell(24, $rowHeight, number_format($igst, 2), 1, 0, 'R');
	    		$pdf->Cell(25, $rowHeight, number_format($total_tax_amt, 2), 1, 1, 'R');
	    	}

	    // ===== TOTAL ROW =====
	    	$pdf->SetFont('helvetica', 'B', 7);
	    	$pdf->Cell(30 + 25 + 12 + 12 + 25 + 12 + 25 + 24, $rowHeight, 'TOTAL', 1, 0, 'R', 1);
	    	$pdf->Cell(25, $rowHeight, number_format($grand_total_tax_amt, 2), 1, 1, 'R', 1);

	    	return $grand_total_tax_amt;
    }

    private function draw_hsn_header($pdf) {
    	$pdf->SetFont('helvetica', 'B', 7);
    	$pdf->SetFillColor(240, 240, 240);

    	$pdf->Cell(30, 8, 'HSN Code', 1, 0, 'C', 1);
    	$pdf->Cell(25, 8, 'Taxable Value', 1, 0, 'C', 1);
    	$pdf->Cell(12, 8, 'GST %', 1, 0, 'C', 1);
    	$pdf->Cell(12, 8, 'CGST %', 1, 0, 'C', 1);
    	$pdf->Cell(25, 8, 'CGST Amt', 1, 0, 'C', 1);
    	$pdf->Cell(12, 8, 'SGST %', 1, 0, 'C', 1);
    	$pdf->Cell(25, 8, 'SGST Amt', 1, 0, 'C', 1);
    	$pdf->Cell(24, 8, 'IGST Amt', 1, 0, 'C', 1);
    	$pdf->Cell(25, 8, 'Total Tax Amt', 1, 1, 'C', 1);
    }



	private function draw_footer($pdf, $company, $totalAmount, $product_mast, $product_det, $terms_condition = '', $hasDiscount = false, $bankData = [], $proforma_invoice)
	{
	    $x = 8;

	    if ($pdf->GetY() + 100 > $pdf->getPageHeight() - 10) {
	        $pdf->AddPage();
	        $this->draw_header(
	            $pdf,
	            $this->currentHeaderData['company'],
	            $this->currentHeaderData['proforma_invoice'],
	            $this->currentHeaderData['dealer']
	        );
	    }

	    $y_start = $pdf->GetY() + 3;

	    /* ── Master totals ── */
	    $subTotal               = round((float)($product_mast[0]['fld_total_amt']              ?? 0), 2);
	    $discountPerc           = round((float)($product_mast[0]['fld_discount_per']           ?? 0), 2);
	    $discountAmt            = round((float)($product_mast[0]['fld_discount']               ?? 0), 2);
		$packingTransportationAmt = round((float)($product_mast[0]['fld_packing_forwarding_amt'] ?? 0), 2);
	    $tds                    = round((float)($product_mast[0]['fld_tds']                    ?? 0), 2);
	    $tds_per                = round((float)($product_mast[0]['fld_tds_per']                ?? 0), 2);
	    $roundOff               = round((float)($product_mast[0]['fld_round_off']              ?? 0), 2);
	    $invoiceAmt             = round((float)($product_mast[0]['fld_grand_total']            ?? 0), 2);
	    $totalBeforeRound       = round((float)($product_mast[0]['fld_sub_total2']             ?? 0) - $tds, 2);
	    $dbIgst                 = round((float)($product_mast[0]['fld_igst_amt']               ?? 0), 2);
	    $dbCgst                 = round((float)($product_mast[0]['fld_cgst_amt']               ?? 0), 2);
	    $dbSgst                 = round((float)($product_mast[0]['fld_sgst_amt']               ?? 0), 2);

	    /* ── State / GST setup ── */
	    $software_param  = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	    $global_gst_perc = !empty($product_mast[0]['fld_gst_per']) && isset($product_mast[0]['fld_gst_per'])
	                       ? (float)$product_mast[0]['fld_gst_per'] : (float)$software_param[0]['fld_gst_percentage'];
		
	    $depot_state_code  = trim($this->currentHeaderData['company']['fld_state'] ?? '');
	    $dealer_state_code = trim($this->currentHeaderData['dealer']['fld_gst_state_code'] ?? '');
	    $is_same_state     = !empty($depot_state_code) && !empty($dealer_state_code)
	                         && strtolower((string)$depot_state_code) == strtolower((string)$dealer_state_code);

	    /* ════════════════════════════════════════════════════════════════════
	       DISTRIBUTED HSN CALCULATIONS  (mirrors tax-invoice logic exactly)
	       ════════════════════════════════════════════════════════════════════

	       withDisAmt = subTotal - discountAmt

	       Per HSN group:
	         1. distributed_taxable = Σ fld_total_amt  for that HSN
	         2. global_dis          = distributed_taxable / subTotal * discountAmt
	         3. less_dis            = distributed_taxable - global_dis
	         4. distribute_pf       = less_dis / withDisAmt * packingTransportationAmt
	         taxable_value          = distributed_taxable + distribute_pf

	       GST on taxable_value:
	         same state  → CGST = SGST = taxable_value * (gst/2) / 100
	         diff state  → IGST = taxable_value * gst / 100
	    ════════════════════════════════════════════════════════════════════ */
	    $withDisAmt = round($subTotal - $discountAmt, 2);

	    /* ── Prefer saved HSN distributed JSON (same as Tax Invoice PDF) ── */
	    $hsnDistributed = [];
	    if (!empty($product_mast[0]['fld_hsn_distributed_json'])) {
	        $decoded = json_decode($product_mast[0]['fld_hsn_distributed_json'], true);
	        if (is_array($decoded)) {
	            $hsnDistributed = $decoded;
	        }
	    }

	    // If saved JSON contains blank/N/A HSN keys, rebuild from product rows
	    // so PDF summary shows actual HSN codes.
	    if (!empty($hsnDistributed)) {
	        $hasInvalidHsnKey = false;
	        foreach ($hsnDistributed as $hsnKey => $row) {
	            $normalizedKey = strtoupper(trim((string)$hsnKey));
	            if ($normalizedKey === '' || $normalizedKey === 'N/A' || $normalizedKey === '-') {
	                $hasInvalidHsnKey = true;
	                break;
	            }
	        }
	        if ($hasInvalidHsnKey) {
	            $hsnDistributed = [];
	        }
	    }

	    /* Fallback: compute distributed values if JSON missing (legacy records) */
	    if (empty($hsnDistributed)) {
	        $hsnRaw = [];
	        foreach ($product_det as $prod) {
	            $hsn = trim((string)($prod['fld_hsn_code'] ?? ''));
	            if ($hsn === '') {
	                $hsn = '-';
	            }
	            $lineAmt = (float)($prod['fld_total_amt'] ?? 0);
	            if (!isset($hsnRaw[$hsn])) $hsnRaw[$hsn] = 0.0;
	            $hsnRaw[$hsn] += $lineAmt;
	        }

	        foreach ($hsnRaw as $hsn => $distTaxable) {
	            $distributedTaxable = round($distTaxable, 2);
	            $globalDis          = ($subTotal > 0)   ? round($distributedTaxable / $subTotal * $discountAmt, 2) : 0.0;
	            $lessDis            = round($distributedTaxable - $globalDis, 2);
	            $distributePF       = ($withDisAmt > 0) ? round($lessDis / $withDisAmt * $packingTransportationAmt, 2) : 0.0;
	            $hsnTaxableValue    = round($distributedTaxable + $distributePF, 2);

	            $hsnDistributed[$hsn] = [
	                'distributed_taxable' => $distributedTaxable,
	                'global_dis'          => $globalDis,
	                'less_dis'            => $lessDis,
	                'distribute_pf'       => $distributePF,
	                'taxable_value'       => $hsnTaxableValue,
	            ];
	        }
	    }

	    /* Ensure GST fields exist in HSN json for display */
	    $halfRate = $global_gst_perc / 2;
	    foreach ($hsnDistributed as $hsn => $row) {
	        $tv = (float)($row['taxable_value'] ?? 0);
	        if ($is_same_state) {
	            $hsnCgst = isset($row['cgst_amt']) ? (float)$row['cgst_amt'] : round(($tv * $halfRate) / 100, 2);
	            $hsnSgst = isset($row['sgst_amt']) ? (float)$row['sgst_amt'] : $hsnCgst;
	            $hsnDistributed[$hsn]['cgst_rate'] = isset($row['cgst_rate']) ? (float)$row['cgst_rate'] : $halfRate;
	            $hsnDistributed[$hsn]['sgst_rate'] = isset($row['sgst_rate']) ? (float)$row['sgst_rate'] : $halfRate;
	            $hsnDistributed[$hsn]['igst_rate'] = 0;
	            $hsnDistributed[$hsn]['cgst_amt']  = round($hsnCgst, 2);
	            $hsnDistributed[$hsn]['sgst_amt']  = round($hsnSgst, 2);
	            $hsnDistributed[$hsn]['igst_amt']  = 0;
	            $hsnDistributed[$hsn]['total_tax'] = isset($row['total_tax']) ? (float)$row['total_tax'] : round($hsnCgst + $hsnSgst, 2);
	        } else {
	            $hsnIgst = isset($row['igst_amt']) ? (float)$row['igst_amt'] : round(($tv * $global_gst_perc) / 100, 2);
	            $hsnDistributed[$hsn]['cgst_rate'] = 0;
	            $hsnDistributed[$hsn]['sgst_rate'] = 0;
	            $hsnDistributed[$hsn]['igst_rate'] = isset($row['igst_rate']) ? (float)$row['igst_rate'] : $global_gst_perc;
	            $hsnDistributed[$hsn]['cgst_amt']  = 0;
	            $hsnDistributed[$hsn]['sgst_amt']  = 0;
	            $hsnDistributed[$hsn]['igst_amt']  = round($hsnIgst, 2);
	            $hsnDistributed[$hsn]['total_tax'] = isset($row['total_tax']) ? (float)$row['total_tax'] : round($hsnIgst, 2);
	        }
	    }

	    /* Use GST + totals from DB (do not recalculate in PDF) */
	    $usedGstAmt      = $is_same_state ? round($dbCgst + $dbSgst, 2) : round($dbIgst, 2);
	    $recalcCgst      = $dbCgst;
	    $recalcSgst      = $dbSgst;
	    $recalcIgst      = $dbIgst;
	    $finalGrandTotal = $invoiceAmt;
	    $actualRoundOff  = $roundOff;

	    /* ── derived display values ── */
	    $taxableAmt    = round($withDisAmt + $packingTransportationAmt, 2); // for summary label
	    $amountInWords = function_exists('amount_in_words') ? amount_in_words($finalGrandTotal) : '';

	    /* ════════════════════════════════════════════════════════════════
	       LEFT COLUMN: HSN SUMMARY
	       ════════════════════════════════════════════════════════════════ */
	    $leftWidth  = 120;
	    $rightWidth = 74;
	    $y_content_start = $y_start;

	    $pdf->SetXY($x, $y_content_start);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($leftWidth, 7, ' HSN SUMMARY', 1, 1, 'L', 0);

	    $pdf->SetFillColor(245, 245, 245);
	    $pdf->SetFont('helvetica', 'B', 8);
	    $pdf->Cell(22, 7, 'HSN Code',      1, 0, 'C', 1);
	    $pdf->Cell(28, 7, 'Taxable Value', 1, 0, 'C', 1);
	    $pdf->Cell(10, 7, 'GST%',          1, 0, 'C', 1);

	    if ($is_same_state) {
	        $pdf->Cell(12, 7, 'CGST%',    1, 0, 'C', 1);
	        $pdf->Cell(18, 7, 'CGST Amt', 1, 0, 'C', 1);
	        $pdf->Cell(12, 7, 'SGST%',    1, 0, 'C', 1);
	        $pdf->Cell(18, 7, 'SGST Amt', 1, 1, 'C', 1);
	    } else {
	        $pdf->Cell(15, 7, 'IGST%',    1, 0, 'C', 1);
	        $pdf->Cell(45, 7, 'IGST Amt', 1, 1, 'C', 1);
	    }

	    $pdf->SetFont('helvetica', '', 8);

	    /* ── HSN detail rows ── */
	    $calcCgstTotal     = 0.0;
	    $calcSgstTotal     = 0.0;
	    $calcIgstTotal     = 0.0;
	    $grandTotalTaxable = 0.0;
	    $rowHeight         = 6;

	    foreach ($hsnDistributed as $hsn => $data) {
	        $tv       = $data['taxable_value'];
	        $halfRate = $global_gst_perc / 2;

	        if ($is_same_state) {
	            $hsnCgst = round(($tv * $halfRate) / 100, 2);
	            $hsnSgst = $hsnCgst;
	            $calcCgstTotal     += $hsnCgst;
	            $calcSgstTotal     += $hsnSgst;
	        } else {
	            $hsnIgst = round(($tv * $global_gst_perc) / 100, 2);
	            $calcIgstTotal += $hsnIgst;
	        }
	        $grandTotalTaxable += $tv;

	        $pdf->Cell(22, $rowHeight, $hsn,                               1, 0, 'C');
	        $pdf->Cell(28, $rowHeight, money_format_india($tv, 2),         1, 0, 'R');
	        $pdf->Cell(10, $rowHeight, number_format($global_gst_perc, 2), 1, 0, 'C');

	        if ($is_same_state) {
	            $pdf->Cell(12, $rowHeight, number_format($halfRate, 2),         1, 0, 'C');
	            $pdf->Cell(18, $rowHeight, money_format_india($hsnCgst, 2),     1, 0, 'R');
	            $pdf->Cell(12, $rowHeight, number_format($halfRate, 2),         1, 0, 'C');
	            $pdf->Cell(18, $rowHeight, money_format_india($hsnSgst, 2),     1, 1, 'R');
	        } else {
	            $pdf->Cell(15, $rowHeight, number_format($global_gst_perc, 2),  1, 0, 'C');
	            $pdf->Cell(45, $rowHeight, money_format_india($hsnIgst, 2),     1, 1, 'R');
	        }
	    }

	    /* round running totals before printing total row */
	    $calcCgstTotal     = round($calcCgstTotal, 2);
	    $calcSgstTotal     = round($calcSgstTotal, 2);
	    $calcIgstTotal     = round($calcIgstTotal, 2);
	    $grandTotalTaxable = round($grandTotalTaxable, 2);

	    $pdf->SetFont('helvetica', 'B', 8);
	    $pdf->SetFillColor(240, 240, 240);
	    if ($is_same_state) {
	        $pdf->Cell(22, $rowHeight, 'TOTAL',                                   1, 0, 'R', 1); // HSN col
	        $pdf->Cell(28, $rowHeight, money_format_india($grandTotalTaxable, 2), 1, 0, 'R', 1); // Taxable Value total
	        $pdf->Cell(10, $rowHeight, '',                                        1, 0, 'C', 1); // GST% blank
	        $pdf->Cell(12, $rowHeight, '',                                        1, 0, 'C', 1); // CGST% blank
	        $pdf->Cell(18, $rowHeight, money_format_india($calcCgstTotal, 2),     1, 0, 'R', 1); // CGST Amt
	        $pdf->Cell(12, $rowHeight, '',                                        1, 0, 'C', 1); // SGST% blank
	        $pdf->Cell(18, $rowHeight, money_format_india($calcSgstTotal, 2),     1, 1, 'R', 1); // SGST Amt
	    } else {
	        $pdf->Cell(22, $rowHeight, 'TOTAL',                                   1, 0, 'R', 1); // HSN col
	        $pdf->Cell(28, $rowHeight, money_format_india($grandTotalTaxable, 2), 1, 0, 'R', 1); // Taxable Value total
	        $pdf->Cell(10, $rowHeight, '',                                        1, 0, 'C', 1); // GST% blank
	        $pdf->Cell(15, $rowHeight, '',                                        1, 0, 'C', 1); // IGST% blank
	        $pdf->Cell(45, $rowHeight, money_format_india($calcIgstTotal, 2),     1, 1, 'R', 1); // IGST Amt
	    }

	    $hsn_end_y = $pdf->GetY();

	    /* ════════════════════════════════════════════════════════════════
	       RIGHT COLUMN: PROFORMA INVOICE SUMMARY
	       ════════════════════════════════════════════════════════════════ */
	    $x_right        = $x + $leftWidth;
	    $y_summary_start = $y_content_start;
	    $pdf->SetXY($x_right, $y_summary_start);

	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($rightWidth, 6, ' PROFORMA INVOICE SUMMARY', 1, 1, 'L', 0);

	    $summaryRowHeight    = 6;
	    $summaryAmountHeight = 7;
	    $summaryInnerTop     = 1;
	    $summaryInnerBottom  = 1;
	    $summaryRows         = [];

	    $pushSummaryRow = function($label, $value, $options = []) use (&$summaryRows) {
	        $summaryRows[] = ['label' => $label, 'value' => $value, 'options' => $options];
	    };

	    $printSummaryRow = function($label, $value, $options = []) use ($pdf, $x_right, $rightWidth, $summaryRowHeight) {
	        $pdf->SetX($x_right + 2);
	        $pdf->SetFont('helvetica', 'B', 8);
	        $pdf->Cell($rightWidth - 4, $summaryRowHeight, $label, 0, 0, 'L');
	        $pdf->SetFont('helvetica', '', 8);
	        if (!empty($options['color'])) {
	            $color = $options['color'];
	            $pdf->SetTextColor($color[0], $color[1], $color[2]);
	        }
	        $pdf->Cell(0, $summaryRowHeight, $value, 0, 1, 'R');
	        if (!empty($options['color'])) {
	            $pdf->SetTextColor(0, 0, 0);
	        }
	    };

	    $formatPercent = function($value) {
	        $formatted = number_format($value, 2);
	        $formatted = rtrim(rtrim($formatted, '0'), '.');
	        return $formatted === '' ? '0' : $formatted;
	    };

	    /* Build summary rows using distributed GST values */
	    $pushSummaryRow('Sub Total:', money_format_india($subTotal, 2));

	    if ($discountAmt > 0 || $discountPerc > 0) {
	        $discountLabel = 'Discount (' . money_format_india($discountPerc, 2) . '%):';
	        $pushSummaryRow($discountLabel, money_format_india($discountAmt, 2));
	    }

	    if ($packingTransportationAmt > 0) {
	        $pushSummaryRow('Packing And Transportation:', number_format($packingTransportationAmt, 2));
	    }

	    // "With Dis Amt" mirrors tax-invoice CHANGE #2
	    $pushSummaryRow('With Dis Amt:', money_format_india($withDisAmt, 2));

	    // GST from distributed calculation (CHANGE #3)
	    if ($is_same_state) {
	        if ($recalcCgst > 0) {
	            $cgstLabel = 'CGST (' . $formatPercent($global_gst_perc / 2) . '%):';
	            $pushSummaryRow($cgstLabel, money_format_india($recalcCgst, 2));
	        }
	        if ($recalcSgst > 0) {
	            $sgstLabel = 'SGST (' . $formatPercent($global_gst_perc / 2) . '%):';
	            $pushSummaryRow($sgstLabel, money_format_india($recalcSgst, 2));
	        }
	    } else {
	        if ($recalcIgst > 0) {
	            $igstLabel = 'IGST (' . $formatPercent($global_gst_perc) . '%):';
	            $pushSummaryRow($igstLabel, money_format_india($recalcIgst, 2));
	        }
	    }

	    if ($tds > 0 || $tds_per > 0) {
	        $TDSLabel = 'TDS (' . number_format($tds_per, 2) . '%):';
	        $pushSummaryRow($TDSLabel, number_format($tds, 2));
	    }

	    $pushSummaryRow('Sub Total:', money_format_india($totalBeforeRound, 2));

	    // Rounding Off = actual difference (mirrors tax-invoice fix)
	    if ($actualRoundOff != 0) {
	        $pushSummaryRow('Rounding Off:', money_format_india($actualRoundOff, 2));
	    }

	    $summaryBoxHeight = $summaryInnerTop + $summaryInnerBottom
	                      + (count($summaryRows) * $summaryRowHeight)
	                      + $summaryAmountHeight;

	    $pdf->SetFillColor(250, 250, 250);
	    $boxTop = $pdf->GetY();
	    $pdf->Rect($x_right, $boxTop, $rightWidth, $summaryBoxHeight, 'F');
	    $pdf->Rect($x_right, $boxTop, $rightWidth, $summaryBoxHeight, 'D');

	    $summary_end_y = $boxTop + $summaryBoxHeight;
	    $summaryY      = $boxTop + $summaryInnerTop;
	    $pdf->SetXY($x_right + 2, $summaryY);
	    $pdf->SetTextColor(0, 0, 0);

	    foreach ($summaryRows as $summaryRow) {
	        $printSummaryRow($summaryRow['label'], $summaryRow['value'], $summaryRow['options']);
	    }

	    /* Proforma Invoice Amount — whole rupee (CHANGE #4) */
	    $pdf->SetX($x_right + 2);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($rightWidth - 4, $summaryAmountHeight, 'Proforma Invoice Amount:', 0, 0, 'L', 0);
	    $pdf->SetFont('helvetica', '', 9);
	    $pdf->Cell(0, $summaryAmountHeight, money_format_india($finalGrandTotal, 2), 0, 1, 'R', 0);

	    /* ── Bank Details & Remark (unchanged layout) ── */
	    $bank_start_y  = $hsn_end_y + 1;
	    $bankWidth     = 70;
	    $remarkWidth   = $leftWidth - $bankWidth;

	    $pdf->SetXY($x, $bank_start_y);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($bankWidth, 4, 'Bank Details:', 0, 1, 'L');

	    $bankHtml  = '<b>Company Name:</b> ' . ($bankData['fld_bank_ac_holder'] ?? '') . '<br>';
	    $bankHtml .= '<b>Bank Name:</b> '    . ($bankData['fld_bank_name']      ?? '') . '<br>';
	    $bankHtml .= '<b>Branch :</b> '      . ($bankData['fld_branch_name']    ?? '') . '<br>';
	    $bankHtml .= '<b>Account No:</b> '   . ($bankData['fld_account_no']     ?? '') . '<br>';
	    $bankHtml .= '<b>IFSC Code:</b> '    . ($bankData['fld_ifsc_code']      ?? '') . '<br>';

	    $pdf->SetFont('helvetica', '', 8);
	    $pdf->writeHTMLCell($bankWidth, 0, $x, '', $bankHtml, 0, 1, false, true, 'L');
	    $bankEndY = $pdf->GetY();

	    $remark = isset($proforma_invoice['fld_remark']) ? trim($proforma_invoice['fld_remark']) : '';
	    if (!empty($remark)) {
	        $pdf->SetXY($x + $bankWidth, $bank_start_y);
	        $pdf->SetFont('helvetica', 'B', 9);
	        $pdf->Cell($remarkWidth, 4, 'Remark:', 0, 1, 'L');
	        $pdf->SetFont('helvetica', '', 8);
	        $pdf->SetX($x + $bankWidth);
	        $pdf->MultiCell($remarkWidth, 3.5, $remark, 0, 'L');
	        $remarkEndY = $pdf->GetY();
	    } else {
	        $remarkEndY = $bank_start_y;
	    }

	    $nextY = max($bankEndY, $remarkEndY, $summary_end_y) + 2;
	    $pdf->SetXY($x, $nextY);

	    /* ── Amount in Words ── */
	    $amtWordsY = max($pdf->GetY() + 1, $summary_end_y + 1);
	    $pdf->SetXY($x, $amtWordsY);
	    $pdf->SetFillColor(250, 250, 250);
	    $pdf->Rect($x, $amtWordsY, 194, 7, 'F');
	    $pdf->Rect($x, $amtWordsY, 194, 7, 'D');
	    $pdf->SetXY($x + 2, $amtWordsY + 1);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell(30, 4, 'Amount in Words:', 0, 0, 'L');
	    $pdf->SetFont('helvetica', '', 9);
	    $pdf->Cell(0, 4, ucwords($amountInWords) . ' Only', 0, 1, 'L');

	    /* ════════════════════════════════════════════════════════════════
	       BOTTOM SECTION (unchanged — terms, signature, etc.)
	       ════════════════════════════════════════════════════════════════ */
	    $y_bottom = $pdf->GetY() + 2;
	    $pdf->SetXY($x, $y_bottom);
	    $pdf->SetFillColor(245, 245, 245);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell(194, 5, ' Terms & Conditions', 0, 1, 'L', 1);

	    if (!empty($terms_condition)) {
	        $pdf->SetFont('helvetica', '', 8);
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->writeHTMLCell(194, 0, $x, $pdf->GetY(), $terms_condition, 0, 1, false, true, 'L', true);
	    }

	    $pdf->SetXY($x, $pdf->GetY() + 1.5);
	    $pdf->SetDrawColor(31, 56, 100);
	    $pdf->SetLineWidth(1.5);
	    $pdf->Line($x, $pdf->GetY(), $x + 194, $pdf->GetY());
	    $pdf->SetDrawColor(240, 126, 27);
	    $pdf->SetLineWidth(0.2);

	    $currentY    = $pdf->GetY();
	    $spaceNeeded = 46;
	    if ($currentY + $spaceNeeded > $pdf->getPageHeight() - 10) {
	        $pdf->AddPage();
	        $this->draw_header(
	            $pdf,
	            $this->currentHeaderData['company'],
	            $this->currentHeaderData['proforma_invoice'],
	            $this->currentHeaderData['dealer']
	        );
	        $headerEndY = $pdf->GetY();
	        if ($headerEndY < 50) {
	            $pdf->SetY(46);
	        } else {
	            $pdf->SetY($headerEndY + 3);
	        }
	    }

	    $pageWidth    = $pdf->getPageWidth();
	    $rightMargin  = 8;
	    $imagesY      = $pdf->GetY() + 3;
	    $stampWidth   = 20;
	    $stampHeight  = 20;
	    $signatureWidth  = 20;
	    $signatureHeight = 15;
	    $gap          = 5;
	    $stampX       = $x;
	    $signatureX   = $stampX + $stampWidth + $gap;

	    $signFile = $this->currentHeaderData['proforma_invoice']['fld_sign_photo'] ?? '';
	    $signPath = 'uploads/SignaturePhoto/' . $signFile;

	    if (!empty($signFile) && file_exists($signPath)) {
	        $pdf->Image($signPath, $stampX, $imagesY, $signatureWidth, $signatureHeight);
	    }
	    if (file_exists('uploads/stamp.png')) {
	        $pdf->Image('uploads/stamp.png', $signatureX, $imagesY, $stampWidth, $stampHeight);
	    }

	    $maxImageHeight = max($stampHeight, $signatureHeight);
	    $pdf->SetY($imagesY + $maxImageHeight + 3);

	    $pdf->SetXY($x, $pdf->GetY());
	    $pdf->SetFont('helvetica', 'B', 9);
	    $organization = $this->currentHeaderData['company']['fld_org_name'] ?? '';
	    $pdf->Cell(194, 4, 'For ' . $organization . ',', 0, 1, 'L');

	    $pdf->SetXY($x, $pdf->GetY() + 2);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $employeeName = $this->currentHeaderData['proforma_invoice']['sales_ex_name'] ?? '';
	    $pdf->Cell(194, 4, $employeeName, 0, 1, 'L');

	    $pdf->SetXY($x, $pdf->GetY());
	    $pdf->SetFont('helvetica', '', 9);
	    $employeeDesignation = $this->currentHeaderData['proforma_invoice']['sales_ex_designation'] ?? '';
	    $pdf->Cell(194, 4, $employeeDesignation, 0, 1, 'L');

	    $employeeMobile = $this->currentHeaderData['proforma_invoice']['sales_ex_mobile'] ?? '';
	    if (!empty($employeeMobile)) {
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->Cell(194, 4, 'Mobile: ' . $employeeMobile, 0, 1, 'L');
	    }

	    $employeeEmail = $this->currentHeaderData['proforma_invoice']['sales_ex_email'] ?? '';
	    if (!empty($employeeEmail)) {
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->Cell(194, 4, 'Email: ' . $employeeEmail, 0, 1, 'L');
	    }
	}
	private function draw_footer_new($pdf, $company, $totalAmount, $product_mast, $product_det, $terms_condition = '', $hasDiscount = false, $bankData = [], $proforma_invoice)
	{
	    $x = 8;

	    if ($pdf->GetY() + 100 > $pdf->getPageHeight() - 10) {
	        $pdf->AddPage();
	        // $this->draw_header(
	        //     $pdf,
	        //     $this->currentHeaderData['company'],
	        //     $this->currentHeaderData['proforma_invoice'],
	        //     $this->currentHeaderData['dealer']
	        // );
	    }

	    $y_start = $pdf->GetY() + 3;

	    /* ── Master totals ── */
	    $subTotal               = round((float)($product_mast[0]['fld_total_amt']              ?? 0), 2);
	    $discountPerc           = round((float)($product_mast[0]['fld_discount_per']           ?? 0), 2);
	    $discountAmt            = round((float)($product_mast[0]['fld_discount']               ?? 0), 2);
		$packingTransportationAmt = round((float)($product_mast[0]['fld_packing_forwarding_amt'] ?? 0), 2);
	    $tds                    = round((float)($product_mast[0]['fld_tds']                    ?? 0), 2);
	    $tds_per                = round((float)($product_mast[0]['fld_tds_per']                ?? 0), 2);
	    $roundOff               = round((float)($product_mast[0]['fld_round_off']              ?? 0), 2);
	    $invoiceAmt             = round((float)($product_mast[0]['fld_grand_total']            ?? 0), 2);
	    $totalBeforeRound       = round((float)($product_mast[0]['fld_sub_total2']             ?? 0) - $tds, 2);
	    $dbIgst                 = round((float)($product_mast[0]['fld_igst_amt']               ?? 0), 2);
	    $dbCgst                 = round((float)($product_mast[0]['fld_cgst_amt']               ?? 0), 2);
	    $dbSgst                 = round((float)($product_mast[0]['fld_sgst_amt']               ?? 0), 2);

	    /* ── State / GST setup ── */
	    $software_param  = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
	    $global_gst_perc = !empty($product_mast[0]['fld_gst_per']) && isset($product_mast[0]['fld_gst_per'])
	                       ? (float)$product_mast[0]['fld_gst_per'] : (float)$software_param[0]['fld_gst_percentage'];
		
	    $depot_state_code  = trim($this->currentHeaderData['company']['fld_state'] ?? '');
	    $dealer_state_code = trim($this->currentHeaderData['dealer']['fld_gst_state_code'] ?? '');
	    $is_same_state     = !empty($depot_state_code) && !empty($dealer_state_code)
	                         && strtolower((string)$depot_state_code) == strtolower((string)$dealer_state_code);

	    /* ════════════════════════════════════════════════════════════════════
	       DISTRIBUTED HSN CALCULATIONS  (mirrors tax-invoice logic exactly)
	       ════════════════════════════════════════════════════════════════════

	       withDisAmt = subTotal - discountAmt

	       Per HSN group:
	         1. distributed_taxable = Σ fld_total_amt  for that HSN
	         2. global_dis          = distributed_taxable / subTotal * discountAmt
	         3. less_dis            = distributed_taxable - global_dis
	         4. distribute_pf       = less_dis / withDisAmt * packingTransportationAmt
	         taxable_value          = distributed_taxable + distribute_pf

	       GST on taxable_value:
	         same state  → CGST = SGST = taxable_value * (gst/2) / 100
	         diff state  → IGST = taxable_value * gst / 100
	    ════════════════════════════════════════════════════════════════════ */
	    $withDisAmt = round($subTotal - $discountAmt, 2);

	    /* ── Prefer saved HSN distributed JSON (same as Tax Invoice PDF) ── */
	    $hsnDistributed = [];
	    if (!empty($product_mast[0]['fld_hsn_distributed_json'])) {
	        $decoded = json_decode($product_mast[0]['fld_hsn_distributed_json'], true);
	        if (is_array($decoded)) {
	            $hsnDistributed = $decoded;
	        }
	    }

	    // If saved JSON contains blank/N/A HSN keys, rebuild from product rows
	    // so PDF summary shows actual HSN codes.
	    if (!empty($hsnDistributed)) {
	        $hasInvalidHsnKey = false;
	        foreach ($hsnDistributed as $hsnKey => $row) {
	            $normalizedKey = strtoupper(trim((string)$hsnKey));
	            if ($normalizedKey === '' || $normalizedKey === 'N/A' || $normalizedKey === '-') {
	                $hasInvalidHsnKey = true;
	                break;
	            }
	        }
	        if ($hasInvalidHsnKey) {
	            $hsnDistributed = [];
	        }
	    }

	    /* Fallback: compute distributed values if JSON missing (legacy records) */
	    if (empty($hsnDistributed)) {
	        $hsnRaw = [];
	        foreach ($product_det as $prod) {
	            $hsn = trim((string)($prod['fld_hsn_code'] ?? ''));
	            if ($hsn === '') {
	                $hsn = '-';
	            }
	            $lineAmt = (float)($prod['fld_total_amt'] ?? 0);
	            if (!isset($hsnRaw[$hsn])) $hsnRaw[$hsn] = 0.0;
	            $hsnRaw[$hsn] += $lineAmt;
	        }

	        foreach ($hsnRaw as $hsn => $distTaxable) {
	            $distributedTaxable = round($distTaxable, 2);
	            $globalDis          = ($subTotal > 0)   ? round($distributedTaxable / $subTotal * $discountAmt, 2) : 0.0;
	            $lessDis            = round($distributedTaxable - $globalDis, 2);
	            $distributePF       = ($withDisAmt > 0) ? round($lessDis / $withDisAmt * $packingTransportationAmt, 2) : 0.0;
	            $hsnTaxableValue    = round($distributedTaxable + $distributePF, 2);

	            $hsnDistributed[$hsn] = [
	                'distributed_taxable' => $distributedTaxable,
	                'global_dis'          => $globalDis,
	                'less_dis'            => $lessDis,
	                'distribute_pf'       => $distributePF,
	                'taxable_value'       => $hsnTaxableValue,
	            ];
	        }
	    }

	    /* Ensure GST fields exist in HSN json for display */
	    $halfRate = $global_gst_perc / 2;
	    foreach ($hsnDistributed as $hsn => $row) {
	        $tv = (float)($row['taxable_value'] ?? 0);
	        if ($is_same_state) {
	            $hsnCgst = isset($row['cgst_amt']) ? (float)$row['cgst_amt'] : round(($tv * $halfRate) / 100, 2);
	            $hsnSgst = isset($row['sgst_amt']) ? (float)$row['sgst_amt'] : $hsnCgst;
	            $hsnDistributed[$hsn]['cgst_rate'] = isset($row['cgst_rate']) ? (float)$row['cgst_rate'] : $halfRate;
	            $hsnDistributed[$hsn]['sgst_rate'] = isset($row['sgst_rate']) ? (float)$row['sgst_rate'] : $halfRate;
	            $hsnDistributed[$hsn]['igst_rate'] = 0;
	            $hsnDistributed[$hsn]['cgst_amt']  = round($hsnCgst, 2);
	            $hsnDistributed[$hsn]['sgst_amt']  = round($hsnSgst, 2);
	            $hsnDistributed[$hsn]['igst_amt']  = 0;
	            $hsnDistributed[$hsn]['total_tax'] = isset($row['total_tax']) ? (float)$row['total_tax'] : round($hsnCgst + $hsnSgst, 2);
	        } else {
	            $hsnIgst = isset($row['igst_amt']) ? (float)$row['igst_amt'] : round(($tv * $global_gst_perc) / 100, 2);
	            $hsnDistributed[$hsn]['cgst_rate'] = 0;
	            $hsnDistributed[$hsn]['sgst_rate'] = 0;
	            $hsnDistributed[$hsn]['igst_rate'] = isset($row['igst_rate']) ? (float)$row['igst_rate'] : $global_gst_perc;
	            $hsnDistributed[$hsn]['cgst_amt']  = 0;
	            $hsnDistributed[$hsn]['sgst_amt']  = 0;
	            $hsnDistributed[$hsn]['igst_amt']  = round($hsnIgst, 2);
	            $hsnDistributed[$hsn]['total_tax'] = isset($row['total_tax']) ? (float)$row['total_tax'] : round($hsnIgst, 2);
	        }
	    }

	    /* Use GST + totals from DB (do not recalculate in PDF) */
	    $usedGstAmt      = $is_same_state ? round($dbCgst + $dbSgst, 2) : round($dbIgst, 2);
	    $recalcCgst      = $dbCgst;
	    $recalcSgst      = $dbSgst;
	    $recalcIgst      = $dbIgst;
	    $finalGrandTotal = $invoiceAmt;
	    $actualRoundOff  = $roundOff;

	    /* ── derived display values ── */
	    $taxableAmt    = round($withDisAmt + $packingTransportationAmt, 2); // for summary label
	    $amountInWords = function_exists('amount_in_words') ? amount_in_words($finalGrandTotal) : '';

	    /* ════════════════════════════════════════════════════════════════
	       LEFT COLUMN: HSN SUMMARY
	       ════════════════════════════════════════════════════════════════ */
	    $leftWidth  = 120;
	    $rightWidth = 74;
	    $y_content_start = $y_start;

	    $pdf->SetXY($x, $y_content_start);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($leftWidth, 7, ' HSN SUMMARY', 1, 1, 'L', 0);

	    $pdf->SetFillColor(245, 245, 245);
	    $pdf->SetFont('helvetica', 'B', 8);
	    $pdf->Cell(22, 7, 'HSN Code',      1, 0, 'C', 1);
	    $pdf->Cell(28, 7, 'Taxable Value', 1, 0, 'C', 1);
	    $pdf->Cell(10, 7, 'GST%',          1, 0, 'C', 1);

	    if ($is_same_state) {
	        $pdf->Cell(12, 7, 'CGST%',    1, 0, 'C', 1);
	        $pdf->Cell(18, 7, 'CGST Amt', 1, 0, 'C', 1);
	        $pdf->Cell(12, 7, 'SGST%',    1, 0, 'C', 1);
	        $pdf->Cell(18, 7, 'SGST Amt', 1, 1, 'C', 1);
	    } else {
	        $pdf->Cell(15, 7, 'IGST%',    1, 0, 'C', 1);
	        $pdf->Cell(45, 7, 'IGST Amt', 1, 1, 'C', 1);
	    }

	    $pdf->SetFont('helvetica', '', 8);

	    /* ── HSN detail rows ── */
	    $calcCgstTotal     = 0.0;
	    $calcSgstTotal     = 0.0;
	    $calcIgstTotal     = 0.0;
	    $grandTotalTaxable = 0.0;
	    $rowHeight         = 6;

	    foreach ($hsnDistributed as $hsn => $data) {
	        $tv       = $data['taxable_value'];
	        $halfRate = $global_gst_perc / 2;

	        if ($is_same_state) {
	            $hsnCgst = round(($tv * $halfRate) / 100, 2);
	            $hsnSgst = $hsnCgst;
	            $calcCgstTotal     += $hsnCgst;
	            $calcSgstTotal     += $hsnSgst;
	        } else {
	            $hsnIgst = round(($tv * $global_gst_perc) / 100, 2);
	            $calcIgstTotal += $hsnIgst;
	        }
	        $grandTotalTaxable += $tv;

	        $pdf->Cell(22, $rowHeight, $hsn,                               1, 0, 'C');
	        $pdf->Cell(28, $rowHeight, money_format_india($tv, 2),         1, 0, 'R');
	        $pdf->Cell(10, $rowHeight, number_format($global_gst_perc, 2), 1, 0, 'C');

	        if ($is_same_state) {
	            $pdf->Cell(12, $rowHeight, number_format($halfRate, 2),         1, 0, 'C');
	            $pdf->Cell(18, $rowHeight, money_format_india($hsnCgst, 2),     1, 0, 'R');
	            $pdf->Cell(12, $rowHeight, number_format($halfRate, 2),         1, 0, 'C');
	            $pdf->Cell(18, $rowHeight, money_format_india($hsnSgst, 2),     1, 1, 'R');
	        } else {
	            $pdf->Cell(15, $rowHeight, number_format($global_gst_perc, 2),  1, 0, 'C');
	            $pdf->Cell(45, $rowHeight, money_format_india($hsnIgst, 2),     1, 1, 'R');
	        }
	    }

	    /* round running totals before printing total row */
	    $calcCgstTotal     = round($calcCgstTotal, 2);
	    $calcSgstTotal     = round($calcSgstTotal, 2);
	    $calcIgstTotal     = round($calcIgstTotal, 2);
	    $grandTotalTaxable = round($grandTotalTaxable, 2);

	    $pdf->SetFont('helvetica', 'B', 8);
	    $pdf->SetFillColor(240, 240, 240);
	    if ($is_same_state) {
	        $pdf->Cell(22, $rowHeight, 'TOTAL',                                   1, 0, 'R', 1); // HSN col
	        $pdf->Cell(28, $rowHeight, money_format_india($grandTotalTaxable, 2), 1, 0, 'R', 1); // Taxable Value total
	        $pdf->Cell(10, $rowHeight, '',                                        1, 0, 'C', 1); // GST% blank
	        $pdf->Cell(12, $rowHeight, '',                                        1, 0, 'C', 1); // CGST% blank
	        $pdf->Cell(18, $rowHeight, money_format_india($calcCgstTotal, 2),     1, 0, 'R', 1); // CGST Amt
	        $pdf->Cell(12, $rowHeight, '',                                        1, 0, 'C', 1); // SGST% blank
	        $pdf->Cell(18, $rowHeight, money_format_india($calcSgstTotal, 2),     1, 1, 'R', 1); // SGST Amt
	    } else {
	        $pdf->Cell(22, $rowHeight, 'TOTAL',                                   1, 0, 'R', 1); // HSN col
	        $pdf->Cell(28, $rowHeight, money_format_india($grandTotalTaxable, 2), 1, 0, 'R', 1); // Taxable Value total
	        $pdf->Cell(10, $rowHeight, '',                                        1, 0, 'C', 1); // GST% blank
	        $pdf->Cell(15, $rowHeight, '',                                        1, 0, 'C', 1); // IGST% blank
	        $pdf->Cell(45, $rowHeight, money_format_india($calcIgstTotal, 2),     1, 1, 'R', 1); // IGST Amt
	    }

	    $hsn_end_y = $pdf->GetY();

	    /* ════════════════════════════════════════════════════════════════
	       RIGHT COLUMN: PROFORMA INVOICE SUMMARY
	       ════════════════════════════════════════════════════════════════ */
	    $x_right        = $x + $leftWidth;
	    $y_summary_start = $y_content_start;
	    $pdf->SetXY($x_right, $y_summary_start);

	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($rightWidth, 6, ' PROFORMA INVOICE SUMMARY', 1, 1, 'L', 0);

	    $summaryRowHeight    = 6;
	    $summaryAmountHeight = 7;
	    $summaryInnerTop     = 1;
	    $summaryInnerBottom  = 1;
	    $summaryRows         = [];

	    $pushSummaryRow = function($label, $value, $options = []) use (&$summaryRows) {
	        $summaryRows[] = ['label' => $label, 'value' => $value, 'options' => $options];
	    };

	    $printSummaryRow = function($label, $value, $options = []) use ($pdf, $x_right, $rightWidth, $summaryRowHeight) {
	        $pdf->SetX($x_right + 2);
	        $pdf->SetFont('helvetica', 'B', 8);
	        $pdf->Cell($rightWidth - 4, $summaryRowHeight, $label, 0, 0, 'L');
	        $pdf->SetFont('helvetica', '', 8);
	        if (!empty($options['color'])) {
	            $color = $options['color'];
	            $pdf->SetTextColor($color[0], $color[1], $color[2]);
	        }
	        $pdf->Cell(0, $summaryRowHeight, $value, 0, 1, 'R');
	        if (!empty($options['color'])) {
	            $pdf->SetTextColor(0, 0, 0);
	        }
	    };

	    $formatPercent = function($value) {
	        $formatted = number_format($value, 2);
	        $formatted = rtrim(rtrim($formatted, '0'), '.');
	        return $formatted === '' ? '0' : $formatted;
	    };

	    /* Build summary rows using distributed GST values */
	    $pushSummaryRow('Sub Total:', money_format_india($subTotal, 2));

	    if ($discountAmt > 0 || $discountPerc > 0) {
	        $discountLabel = 'Discount (' . money_format_india($discountPerc, 2) . '%):';
	        $pushSummaryRow($discountLabel, money_format_india($discountAmt, 2));
	    }

	    if ($packingTransportationAmt > 0) {
	        $pushSummaryRow('Packing And Transportation:', number_format($packingTransportationAmt, 2));
	    }

	    // "With Dis Amt" mirrors tax-invoice CHANGE #2
	    $pushSummaryRow('With Dis Amt:', money_format_india($withDisAmt, 2));

	    // GST from distributed calculation (CHANGE #3)
	    if ($is_same_state) {
	        if ($recalcCgst > 0) {
	            $cgstLabel = 'CGST (' . $formatPercent($global_gst_perc / 2) . '%):';
	            $pushSummaryRow($cgstLabel, money_format_india($recalcCgst, 2));
	        }
	        if ($recalcSgst > 0) {
	            $sgstLabel = 'SGST (' . $formatPercent($global_gst_perc / 2) . '%):';
	            $pushSummaryRow($sgstLabel, money_format_india($recalcSgst, 2));
	        }
	    } else {
	        if ($recalcIgst > 0) {
	            $igstLabel = 'IGST (' . $formatPercent($global_gst_perc) . '%):';
	            $pushSummaryRow($igstLabel, money_format_india($recalcIgst, 2));
	        }
	    }

	    if ($tds > 0 || $tds_per > 0) {
	        $TDSLabel = 'TDS (' . number_format($tds_per, 2) . '%):';
	        $pushSummaryRow($TDSLabel, number_format($tds, 2));
	    }

	    $pushSummaryRow('Sub Total:', money_format_india($totalBeforeRound, 2));

	    // Rounding Off = actual difference (mirrors tax-invoice fix)
	    if ($actualRoundOff != 0) {
	        $pushSummaryRow('Rounding Off:', money_format_india($actualRoundOff, 2));
	    }

	    $summaryBoxHeight = $summaryInnerTop + $summaryInnerBottom
	                      + (count($summaryRows) * $summaryRowHeight)
	                      + $summaryAmountHeight;

	    $pdf->SetFillColor(250, 250, 250);
	    $boxTop = $pdf->GetY();
	    $pdf->Rect($x_right, $boxTop, $rightWidth, $summaryBoxHeight, 'F');
	    $pdf->Rect($x_right, $boxTop, $rightWidth, $summaryBoxHeight, 'D');

	    $summary_end_y = $boxTop + $summaryBoxHeight;
	    $summaryY      = $boxTop + $summaryInnerTop;
	    $pdf->SetXY($x_right + 2, $summaryY);
	    $pdf->SetTextColor(0, 0, 0);

	    foreach ($summaryRows as $summaryRow) {
	        $printSummaryRow($summaryRow['label'], $summaryRow['value'], $summaryRow['options']);
	    }

	    /* Proforma Invoice Amount — whole rupee (CHANGE #4) */
	    $pdf->SetX($x_right + 2);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($rightWidth - 4, $summaryAmountHeight, 'Proforma Invoice Amount:', 0, 0, 'L', 0);
	    $pdf->SetFont('helvetica', '', 9);
	    $pdf->Cell(0, $summaryAmountHeight, money_format_india($finalGrandTotal, 2), 0, 1, 'R', 0);

	    /* ── Bank Details & Remark (unchanged layout) ── */
	    $bank_start_y  = $hsn_end_y + 1;
	    $bankWidth     = 70;
	    $remarkWidth   = $leftWidth - $bankWidth;

	    $pdf->SetXY($x, $bank_start_y);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell($bankWidth, 4, 'Bank Details:', 0, 1, 'L');

	    $bankHtml  = '<b>Company Name:</b> ' . ($bankData['fld_bank_ac_holder'] ?? '') . '<br>';
	    $bankHtml .= '<b>Bank Name:</b> '    . ($bankData['fld_bank_name']      ?? '') . '<br>';
	    $bankHtml .= '<b>Branch :</b> '      . ($bankData['fld_branch_name']    ?? '') . '<br>';
	    $bankHtml .= '<b>Account No:</b> '   . ($bankData['fld_account_no']     ?? '') . '<br>';
	    $bankHtml .= '<b>IFSC Code:</b> '    . ($bankData['fld_ifsc_code']      ?? '') . '<br>';

	    $pdf->SetFont('helvetica', '', 8);
	    $pdf->writeHTMLCell($bankWidth, 0, $x, '', $bankHtml, 0, 1, false, true, 'L');
	    $bankEndY = $pdf->GetY();

	    $remark = isset($proforma_invoice['fld_remark']) ? trim($proforma_invoice['fld_remark']) : '';
	    if (!empty($remark)) {
	        $pdf->SetXY($x + $bankWidth, $bank_start_y);
	        $pdf->SetFont('helvetica', 'B', 9);
	        $pdf->Cell($remarkWidth, 4, 'Remark:', 0, 1, 'L');
	        $pdf->SetFont('helvetica', '', 8);
	        $pdf->SetX($x + $bankWidth);
	        $pdf->MultiCell($remarkWidth, 3.5, $remark, 0, 'L');
	        $remarkEndY = $pdf->GetY();
	    } else {
	        $remarkEndY = $bank_start_y;
	    }

	    $nextY = max($bankEndY, $remarkEndY, $summary_end_y) + 2;
	    $pdf->SetXY($x, $nextY);

	    /* ── Amount in Words ── */
	    $amtWordsY = max($pdf->GetY() + 1, $summary_end_y + 1);
	    $pdf->SetXY($x, $amtWordsY);
	    $pdf->SetFillColor(250, 250, 250);
	    $pdf->Rect($x, $amtWordsY, 194, 7, 'F');
	    $pdf->Rect($x, $amtWordsY, 194, 7, 'D');
	    $pdf->SetXY($x + 2, $amtWordsY + 1);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell(30, 4, 'Amount in Words:', 0, 0, 'L');
	    $pdf->SetFont('helvetica', '', 9);
	    $pdf->Cell(0, 4, ucwords($amountInWords) . ' Only', 0, 1, 'L');

	    /* ════════════════════════════════════════════════════════════════
	       BOTTOM SECTION (unchanged — terms, signature, etc.)
	       ════════════════════════════════════════════════════════════════ */
	    $y_bottom = $pdf->GetY() + 2;
	    $pdf->SetXY($x, $y_bottom);
	    $pdf->SetFillColor(245, 245, 245);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $pdf->Cell(194, 5, ' Terms & Conditions', 0, 1, 'L', 1);

	    if (!empty($terms_condition)) {
	        $pdf->SetFont('helvetica', '', 8);
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->writeHTMLCell(194, 0, $x, $pdf->GetY(), $terms_condition, 0, 1, false, true, 'L', true);
	    }

	    $pdf->SetXY($x, $pdf->GetY() + 1.5);
	    $pdf->SetDrawColor(31, 56, 100);
	    $pdf->SetLineWidth(1.5);
	    $pdf->Line($x, $pdf->GetY(), $x + 194, $pdf->GetY());
	    $pdf->SetDrawColor(240, 126, 27);
	    $pdf->SetLineWidth(0.2);

	    $currentY    = $pdf->GetY();
	    $spaceNeeded = 46;
	    if ($currentY + $spaceNeeded > $pdf->getPageHeight() - 10) {
	        $pdf->AddPage();
	        // $this->draw_header(
	        //     $pdf,
	        //     $this->currentHeaderData['company'],
	        //     $this->currentHeaderData['proforma_invoice'],
	        //     $this->currentHeaderData['dealer']
	        // );
	        $headerEndY = $pdf->GetY();
	        if ($headerEndY < 50) {
	            $pdf->SetY(46);
	        } else {
	            $pdf->SetY($headerEndY + 3);
	        }
	    }

	    $pageWidth    = $pdf->getPageWidth();
	    $rightMargin  = 8;
	    $imagesY      = $pdf->GetY() + 3;
	    $stampWidth   = 20;
	    $stampHeight  = 20;
	    $signatureWidth  = 20;
	    $signatureHeight = 15;
	    $gap          = 5;
	    $stampX       = $x;
	    $signatureX   = $stampX + $stampWidth + $gap;

	    $signFile = $this->currentHeaderData['proforma_invoice']['fld_sign_photo'] ?? '';
	    $signPath = 'uploads/SignaturePhoto/' . $signFile;

	    if (!empty($signFile) && file_exists($signPath)) {
	        $pdf->Image($signPath, $stampX, $imagesY, $signatureWidth, $signatureHeight);
	    }
	    if (file_exists('uploads/stamp.png')) {
	        $pdf->Image('uploads/stamp.png', $signatureX, $imagesY, $stampWidth, $stampHeight);
	    }

	    $maxImageHeight = max($stampHeight, $signatureHeight);
	    $pdf->SetY($imagesY + $maxImageHeight + 3);

	    $pdf->SetXY($x, $pdf->GetY());
	    $pdf->SetFont('helvetica', 'B', 9);
	    $organization = $this->currentHeaderData['company']['fld_org_name'] ?? '';
	    $pdf->Cell(194, 4, 'For ' . $organization . ',', 0, 1, 'L');

	    $pdf->SetXY($x, $pdf->GetY() + 2);
	    $pdf->SetFont('helvetica', 'B', 9);
	    $employeeName = $this->currentHeaderData['proforma_invoice']['sales_ex_name'] ?? '';
	    $pdf->Cell(194, 4, $employeeName, 0, 1, 'L');

	    $pdf->SetXY($x, $pdf->GetY());
	    $pdf->SetFont('helvetica', '', 9);
	    $employeeDesignation = $this->currentHeaderData['proforma_invoice']['sales_ex_designation'] ?? '';
	    $pdf->Cell(194, 4, $employeeDesignation, 0, 1, 'L');

	    $employeeMobile = $this->currentHeaderData['proforma_invoice']['sales_ex_mobile'] ?? '';
	    if (!empty($employeeMobile)) {
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->Cell(194, 4, 'Mobile: ' . $employeeMobile, 0, 1, 'L');
	    }

	    $employeeEmail = $this->currentHeaderData['proforma_invoice']['sales_ex_email'] ?? '';
	    if (!empty($employeeEmail)) {
	        $pdf->SetXY($x, $pdf->GetY());
	        $pdf->Cell(194, 4, 'Email: ' . $employeeEmail, 0, 1, 'L');
	    }
	}

    private function draw_image_centric_products($pdf, $products) {
    	$x = 8;
    	$imageWidth = 95; // Half page width for image
    	$detailsWidth = 99; // Half page width for details	
    	$imageHeight = 50; // Height for product image
    	$rowSpacing = 3; // Space between products
    	
    	// Get global GST percentage as fallback
    	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
    	$global_gst_perc = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? floatval($software_param[0]['fld_gst_percentage']) : 18;
    	
    	// Get state codes for GST calculation
    	$depot_state_code  = trim($this->currentHeaderData['company']['fld_gst_state_code'] ?? '');
    	$dealer_state_code = trim($this->currentHeaderData['dealer']['fld_gst_state_code'] ?? '');
    	
    	$is_same_state = ($depot_state_code == $dealer_state_code);
		
    	
    	$sr = 1;
    	foreach ($products as $prod) {
    		// Check if we need a new page
    		if ($pdf->GetY() + $imageHeight + 20 > $pdf->getPageHeight() - 10) {
    			$pdf->AddPage();
    			$this->draw_header(
    				$pdf,
    				$this->currentHeaderData['company'],
    				$this->currentHeaderData['proforma_invoice'],
    				$this->currentHeaderData['dealer']
    			);
    		}
    		
    		$startY = $pdf->GetY();
    		
    		// Get GST percentage
    		$gst_perc = floatval($prod['fld_gst_perc'] ?? 0);
    		if ($gst_perc <= 0) {
    			$gst_perc = floatval($prod['pm_gst_percentage'] ?? 0);
    			if ($gst_perc <= 0) {
    				$gst_perc = $global_gst_perc;
    			}
    		}
    		
    		// Calculate taxable amount
    		$taxable_amt = floatval($prod['fld_taxable_amt'] ?? 0);
    		if ($taxable_amt <= 0) {
    			$qty = floatval($prod['fld_qty'] ?? 0);
    			$rate = floatval($prod['fld_rate'] ?? 0);
    			$total_amt = floatval($prod['fld_total_amt'] ?? 0);
    			$disc_perc = floatval($prod['fld_disc_perc'] ?? 0);
    			$disc_amt = floatval($prod['fld_disc_amt'] ?? 0);
    			
    			$amount = $qty * $rate;
    			if ($disc_amt > 0) {
    				$taxable_amt = $amount - $disc_amt;
    			} else if ($disc_perc > 0) {
    				$taxable_amt = $amount - ($amount * $disc_perc / 100);
    			} else {
    				$taxable_amt = $amount;
    			}
    			
    			if ($taxable_amt <= 0 && $total_amt > 0) {
    				$taxable_amt = $total_amt;
    			}
    		}
    		
    		// Calculate GST amounts
    		$gst_amt = ($taxable_amt * $gst_perc / 100);
    		$cgst_amt = $sgst_amt = $igst_amt = 0;
    		if ($is_same_state) {
    			$cgst_amt = $gst_amt / 2;
    			$sgst_amt = $gst_amt / 2;
    		} else {
    			$igst_amt = $gst_amt;
    		}
    		
    		// ========== RIGHT COLUMN: PRODUCT DETAILS (Draw content first to calculate height) ==========
    		$detailsX = $x + $imageWidth + 2;
    		$detailsY = $startY + 3;
    		$contentStartY = $detailsY;
    		$tempY = $detailsY;
    		$pdf->SetXY($detailsX + 3, $tempY);
    		
    		// Product Name (Bold, Large) - Use MultiCell for wrapping
    		$pdf->SetFont('helvetica', 'B', 12);
    		$pdf->SetTextColor(220, 80, 80);
    		$productName = $prod['fld_product_name'] ?? 'N/A';
    		$pdf->MultiCell($detailsWidth - 6, 5, $productName, 0, 'L', 0, 1);
    		$pdf->SetTextColor(0, 0, 0);
    		
    		$tempY = $pdf->GetY() + 1;
    		$pdf->SetXY($detailsX + 3, $tempY);
    		
    		// Product Details in column format
    		$pdf->SetFont('helvetica', '', 9);
    		$lineHeight = 4.5;
    		$labelWidth = 30;
    		$valueWidth = $detailsWidth - 6 - $labelWidth;
    		
    		// Helper function to write label-value pair with wrapping
    		$writeDetailLine = function($label, $value, $useMultiCell = false) use (&$pdf, &$detailsX, &$tempY, $lineHeight, $labelWidth, $valueWidth) {
    			$pdf->SetXY($detailsX + 3, $tempY);
    			$pdf->SetFont('helvetica', 'B', 9);
    			$pdf->Cell($labelWidth, $lineHeight, $label, 0, 0, 'L');
    			$pdf->SetFont('helvetica', '', 9);
    			if ($useMultiCell || strlen($value) > 25) {
    				$pdf->MultiCell($valueWidth, $lineHeight, $value, 0, 'L', 0, 1);
    				$tempY = $pdf->GetY() + 1;
    			} else {
    				$pdf->Cell($valueWidth, $lineHeight, $value, 0, 1, 'L');
    				$tempY = $pdf->GetY() + 1;
    			}
    		};
    		
    		// Category
    		if (!empty($prod['fld_product_group_name'])) {
    			$writeDetailLine('Category:', $prod['fld_product_group_name'], true);
    		}
    		
    		// HSN Code
    		if (!empty($prod['fld_hsn_code'])) {
    			$writeDetailLine('HSN Code:', $prod['fld_hsn_code'], false);
    		}
    		
    		// Model No
    		if (!empty($prod['fld_model_no'])) {
    			$writeDetailLine('Model:', $prod['fld_model_no'], true);
    		}
    		
    		// Item Code
    		if (!empty($prod['fld_item_code'])) {
    			$writeDetailLine('Item Code:', $prod['fld_item_code'], true);
    		}
    		
    		// Quantity
    		$qtyText = number_format($prod['fld_qty'] ?? 0, 2) . ' ' . ($prod['fld_unit'] ?? 'NOS');
    		$writeDetailLine('Quantity:', $qtyText, false);
    		
    		// Rate
    		$writeDetailLine('Rate:', money_format_india($prod['fld_rate'] ?? 0), false);
    		
    		// Discount
    		$disc_perc = floatval($prod['fld_disc_perc'] ?? 0);
    		$disc_amt = floatval($prod['fld_disc_amt'] ?? 0);
    		$discParts = [];
    		if ($disc_perc > 0) {
    			$discParts[] = number_format($disc_perc, 2) . '%';
    		}
    		if ($disc_amt > 0) {
    			$discParts[] = money_format_india($disc_amt);
    		}
    		$discText = !empty($discParts) ? implode(' / ', $discParts) : '-';
    		$writeDetailLine('Discount:', $discText, false);
    		
    		// Taxable Amount
    		$writeDetailLine('Taxable Amt:', money_format_india($taxable_amt), false);
    		
    		// GST
    		$pdf->SetXY($detailsX + 3, $tempY);
    		$pdf->SetFont('helvetica', 'B', 9);
    		$pdf->Cell($labelWidth, $lineHeight, 'GST (' . number_format($gst_perc, 2) . '%):', 0, 0, 'L');
    		$pdf->SetFont('helvetica', '', 9);
    		if ($is_same_state) {
    			$gstText = 'CGST: ' . money_format_india($cgst_amt) . ' | SGST: ' . money_format_india($sgst_amt);
    			$pdf->MultiCell($valueWidth, $lineHeight, $gstText, 0, 'L', 0, 1);
    		} else {
    			$pdf->Cell($valueWidth, $lineHeight, 'IGST: ' . money_format_india($igst_amt), 0, 1, 'L');
    		}
    		$tempY = $pdf->GetY() + 1;
    		
    		// Total Amount
    		$pdf->SetXY($detailsX + 3, $tempY);
    		$pdf->SetFont('helvetica', 'B', 10);
    		$pdf->SetTextColor(220, 80, 80);
    		$pdf->Cell($labelWidth, $lineHeight + 1, 'Total:', 0, 0, 'L');
    		$pdf->SetFont('helvetica', 'B', 11);
    		$pdf->Cell($valueWidth, $lineHeight + 1, money_format_india($prod['fld_grand_total'] ?? $prod['fld_total_amt'] ?? 0), 0, 1, 'L');
    		$pdf->SetTextColor(0, 0, 0);
    		
    		// Calculate actual content height
    		$contentEndY = $pdf->GetY() + 2;
    		$actualContentHeight = $contentEndY - $startY;
    		$detailsY = $tempY; // Update detailsY for reference
    		
    		// Use the larger of minimum height or actual content height
    		$finalBoxHeight = max($imageHeight, $actualContentHeight);
    		
    		// ========== LEFT COLUMN: PRODUCT IMAGE (Draw with calculated height) ==========
    		$pdf->SetXY($x, $startY);
    		
    		// Image container with border
    	$pdf->SetDrawColor(220, 80, 80);
    		$pdf->SetLineWidth(0.5);
    		$pdf->Rect($x, $startY, $imageWidth, $finalBoxHeight, 'D');
    		
    		// Product image
    		if (!empty($prod['image_path']) && file_exists($prod['image_path'])) {
    			// Center the image in the box
    			$imgX = $x + 2;
    			$imgY = $startY + 2;
    			$imgW = $imageWidth - 4;
    			$imgH = $finalBoxHeight - 4;
    			// Suppress PNG iCCP profile warnings
    			@$pdf->Image($prod['image_path'], $imgX, $imgY, $imgW, $imgH, '', '', '', false, 300, '', false, false, 0, false, false, false);
    		} else {
    			// No image placeholder - center vertically
    			$pdf->SetXY($x, $startY + ($finalBoxHeight / 2) - 3);	
    			$pdf->SetFont('helvetica', '', 10);
    			$pdf->SetTextColor(150, 150, 150);
    			$pdf->Cell($imageWidth, 6, 'No Image Available', 0, 1, 'C');
    			$pdf->SetTextColor(0, 0, 0);
    		}
    		
    		// ========== Draw Details Box Border (with calculated height) ==========
    	$pdf->SetDrawColor(220, 80, 80);
    		$pdf->SetLineWidth(0.5);
    		$pdf->Rect($detailsX, $startY, $detailsWidth, $finalBoxHeight, 'D');
    		
    		// Move to next product position
    		$pdf->SetXY($x, $startY + $finalBoxHeight + $rowSpacing);
    		$sr++;
    	}
    	
    	$pdf->Ln(2);
    }

    public function generate_invoice_detailed($id = "") {
    	ob_start();
	    error_reporting(E_ALL & ~E_WARNING);
	    if (ob_get_length()) {
	        ob_end_clean();
	    }

		$this->billToRendered = false;

	$optionsInput = $this->input->get(NULL, TRUE);
	$options = [
		'description' => array_key_exists('include_description', $optionsInput),
		'kld'         => array_key_exists('include_kld', $optionsInput),
		'article'     => array_key_exists('include_article', $optionsInput),
	];
	$options['photo'] = true;

	if (array_key_exists('include_none', $optionsInput)) {
		$options['description'] = false;
		$options['kld'] = false;
		$options['article'] = false;
	} elseif (empty($optionsInput)) {
		$options['description'] = true;
		$options['kld'] = true;
		$options['article'] = true;
	}

    	$this->load->library('Pdf'); 
    	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    	$pdf->SetCreator('Jyoti Chemicals');
    	$pdf->SetAuthor('Jyoti Chemicals');
    	$pdf->SetTitle('Proforma Invoice - Detailed');

    	$pdf->setPrintHeader(false);
    	$pdf->setPrintFooter(false);

    	$pdf->SetMargins(8, 8, 8);
    	$pdf->SetAutoPageBreak(TRUE, 15);

    	$pdf->AddPage();

        // --- Fetch Proforma Invoice Data ---
    	$id = base64_decode($id);

    	$this->db->join('tbl_admin as a', 'a.fld_id = qm.fld_created_by and a.fld_isdeleted = 0', 'left');
    	$this->db->join('tbl_designation_master as dm', 'dm.fld_designation_id = a.fld_designation_id and dm.fld_isdeleted = 0', 'left');
    	$proforma_invoice_data = $this->Master_model->getRecords(
    		'tbl_proforma_invoice_master as qm',
    		array('qm.fld_proforma_invoice_id' => $id, 'qm.fld_isdeleted' => 0),
    		'qm.fld_proforma_invoice_id, qm.fld_proforma_invoice_no, qm.fld_proforma_invoice_date, qm.fld_created_date, qm.fld_shipping_address, a.fld_adm_name as created_by_name, a.fld_mobile_no as employee_mobile, a.fld_email as employee_email, dm.fld_designation_name as employee_designation, qm.fld_dealer_id, qm.fld_terms_condition, qm.fld_po_no, qm.fld_po_mode, a.fld_sign_photo, qm.fld_po_date'
    	);

		// echo "<pre>";
		// print_r($proforma_invoice_data);
		// echo "</pre>";die;

    	if (!empty($proforma_invoice_data)) {
    		$proforma_invoice = $proforma_invoice_data[0];
    	} else {
    		show_error('Proforma Invoice not found');
    		return;
    	}

        // --- Fetch Company Data ---
    	$company_data = $this->Master_model->getRecords(
    		'tbl_organization_master AS om',
    		array('om.fld_isdeleted !=' => 1),
    		'om.fld_org_id, om.fld_cin, om.fld_org_name, om.fld_org_address, om.fld_gst_no, om.fld_pan_no, om.fld_email, om.fld_org_contact, om.fld_logo, om.fld_state as fld_state_code, om.fld_bank_name, om.fld_account_no, om.fld_ifsc_code, om.fld_iso_details, om.fld_website'
    	);

    	if (!empty($company_data)) {
    		$company = $company_data[0];
    		$company['logo_path'] = !empty($company['fld_logo']) ? FCPATH . 'uploads/' . $company['fld_logo'] : '';
    		
    		// Determine if fld_state_code is a GST code (2 digits) or state_id
    		$org_state_code = trim($company['fld_state_code'] ?? '');
    		if (!empty($org_state_code)) {
    			if (is_numeric($org_state_code) && strlen($org_state_code) <= 2) {
    				$company['fld_gst_state_code'] = $org_state_code;
    			} else {
    				$state_check = $this->Master_model->getRecords(
    					'tbl_state_master',
    					array('fld_state_id' => $org_state_code, 'fld_isdeleted' => 0),
    					'fld_gst_code'
    				);
    				if (!empty($state_check) && !empty($state_check[0]['fld_gst_code'])) {
    					$company['fld_gst_state_code'] = $state_check[0]['fld_gst_code'];
    				} else {
    					$company['fld_gst_state_code'] = $org_state_code;
    				}
    			}
    		} else {
    			$company['fld_gst_state_code'] = '';
    		}
    	} else {
    		$company = array(
    			'fld_org_name' => 'Company Name',
    			'fld_org_address' => 'Company Address',
    			'fld_gst_no' => '',
    			'fld_pan_no' => '',
    			'fld_email' => '',
    			'fld_org_contact' => '',
    			'fld_state_code' => '',
    			'fld_gst_state_code' => '',
    			'fld_bank_name' => '',
    			'fld_account_no' => '',
    			'fld_ifsc_code' => '',
    			'fld_iso_details' => '',
    			'fld_website' => '',
    			'logo_path' => ''
    		);
    	}

		// echo '<pre>';print_r($company);die;

        // --- Fetch Customer Data ---
    	$dealer_id = $proforma_invoice['fld_dealer_id'];
    	$this->db->select('dm.fld_dealer_name, dm.fld_dealer_address, dm.fld_gst_no, dm.fld_mobile_no, sm.fld_state_id AS fld_gst_state_code, sm.fld_state_name, dist.fld_dist_name, tal.fld_taluka_name');
    	$this->db->from('tbl_dealer_master AS dm');
    	$this->db->join('tbl_state_master AS sm', 'sm.fld_state_id = dm.fld_state_id AND sm.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_dist_master AS dist', 'dist.fld_dist_id = dm.fld_dist_id AND dist.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_taluka_master AS tal', 'tal.fld_taluka_id = dm.fld_taluka_id AND tal.fld_isdeleted = 0', 'LEFT');
    	$this->db->where('dm.fld_dealer_id', $dealer_id);
    	$this->db->where('dm.fld_isdeleted', 0);
    	$dealer_query = $this->db->get();
    	$dealer_data = $dealer_query->result_array();

    	if (!empty($dealer_data)) {
    		$dealer = $dealer_data[0];
    	} else {
    		show_error('Customer not found');
    		return;
    	}

        // Save header data for repeating
    	$this->currentHeaderData = [
    		'company' => $company,
    		'proforma_invoice' => $proforma_invoice,
    		'dealer' => $dealer
    	];
    	
    	// Store employee data in proforma_invoice array for Sales Executive section
    	$this->currentHeaderData['proforma_invoice']['created_by_name'] = $proforma_invoice['created_by_name'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_mobile'] = $proforma_invoice['employee_mobile'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_email'] = $proforma_invoice['employee_email'] ?? '';
    	$this->currentHeaderData['proforma_invoice']['employee_designation'] = $proforma_invoice['employee_designation'] ?? '';

    	$software_param = $this->Master_model->getRecords('tbl_software_parameter', array('fld_isdeleted !=' => 1));
    	$options['global_gst_perc'] = !empty($software_param) && isset($software_param[0]['fld_gst_percentage']) ? floatval($software_param[0]['fld_gst_percentage']) : 18;

        // --- Get Product Details with extended data ---
    	$this->db->select('epd.fld_proforma_invoice_details_id, epd.fld_product_group_id, epd.fld_product_master_id, epd.fld_qty, epd.fld_total_amt, epd.fld_unit, epd.fld_rate, epd.fld_disc_perc, epd.fld_disc_amt, epd.fld_gst_perc, epd.fld_taxable_amt, epd.fld_grand_total, pm.fld_product_name, pm.fld_hsn_code, pm.fld_item_code, pm.fld_model_no, pm.fld_prod_image, pm.fld_product_description, pm.fld_article_drawing, pm.fld_upload_kld, pm.fld_weight, pm.fld_conversion_weight, pm.fld_moq, pm.fld_remark, pm.fld_packing_qty_1, pm.fld_packing_material_1, pm.fld_packing_qty_2, pm.fld_packing_material_2, pm.fld_price_excl_gst, pm.fld_product_price, pg.fld_product_group_name, pm.fld_gst_percentage AS pm_gst_percentage');
    	$this->db->from('tbl_proforma_invoice_details AS epd');
    	$this->db->join('tbl_product_master AS pm', 'pm.fld_product_master_id = epd.fld_product_master_id AND pm.fld_isdeleted = 0', 'LEFT');
    	$this->db->join('tbl_product_category_master AS pg', 'pg.fld_product_group_id = epd.fld_product_group_id AND pg.fld_isdeleted = 0', 'LEFT');
    	$this->db->where(['epd.fld_proforma_invoice_id' => $id, 'epd.fld_isdeleted' => 0]);
    	$product_det = $this->db->get()->result_array();

    	// Fetch customer product details for each product
    	$this->fetch_customer_product_details($product_det, $dealer_id);

    	foreach ($product_det as &$prod) {
    		$prod['product_image_path'] = !empty($prod['fld_prod_image']) ? FCPATH . 'uploads/product_image/' . $prod['fld_prod_image'] : '';
    		$prod['product_image_url']  = !empty($prod['fld_prod_image']) ? base_url('uploads/product_image/' . $prod['fld_prod_image']) : '';
    		$prod['kld_path']          = !empty($prod['fld_upload_kld']) ? FCPATH . 'uploads/product_kld/' . $prod['fld_upload_kld'] : '';
    		$prod['kld_url']           = !empty($prod['fld_upload_kld']) ? base_url('uploads/product_kld/' . $prod['fld_upload_kld']) : '';
    		$prod['article_path']      = !empty($prod['fld_article_drawing']) ? FCPATH . 'uploads/product_article_drawing/' . $prod['fld_article_drawing'] : '';
    		$prod['article_url']       = !empty($prod['fld_article_drawing']) ? base_url('uploads/product_article_drawing/' . $prod['fld_article_drawing']) : '';
    	}
    	unset($prod);

    	// Check if any product has discount
    	$hasDiscount = false;
    	foreach ($product_det as $prod) {
    		if (!empty($prod['fld_disc_perc']) && floatval($prod['fld_disc_perc']) > 0) {
    			$hasDiscount = true;
    			break;
    		}
    	}

    	$this->draw_header($pdf, $company, $proforma_invoice, $dealer);

    	$options['image_exts'] = ['jpg','jpeg','png','gif','bmp','webp'];
    	$this->draw_detailed_product_sections($pdf, $product_det, $options);

    	$piMastFields = 'fld_total_amt,fld_discount_per,fld_discount,fld_igst_amt,fld_cgst_amt,fld_sgst_amt,fld_sub_total2,fld_round_off,fld_grand_total,fld_packing_forwarding_amt,fld_transportation_amt, fld_gst_per';
    	if ($this->db->field_exists('fld_hsn_distributed_json', 'tbl_proforma_invoice_master')) {
    		$piMastFields .= ', fld_hsn_distributed_json';
    	}
    	$product_mast = $this->Master_model->getRecords('tbl_proforma_invoice_master', array('fld_isdeleted'=>0,'fld_proforma_invoice_id' => $id), $piMastFields);

    	$terms_condition = !empty($proforma_invoice['fld_terms_condition']) ? $proforma_invoice['fld_terms_condition'] : '';
    	$this->draw_footer($pdf,$company,0,$product_mast,$product_det,$terms_condition,$hasDiscount);

    	$pdf->Output('Proforma_Invoice-Detailed.pdf', 'I');
    }

	private function draw_detailed_product_sections($pdf, $products, $options) {
		$headerData = $this->currentHeaderData ?? [];
		$companyState = strtolower(trim($headerData['company']['fld_gst_state_code'] ?? ''));
		$dealerState  = strtolower(trim($headerData['dealer']['fld_gst_state_code'] ?? ''));
		$isSameState  = (!empty($companyState) && !empty($dealerState) && $companyState === $dealerState);

		$imageExts    = $options['image_exts'] ?? ['jpg','jpeg','png','gif','bmp','webp'];
		$defaultGst   = isset($options['global_gst_perc']) ? floatval($options['global_gst_perc']) : 18;

		foreach ($products as $index => $prod) {
			$this->ensurePageSpace($pdf, 85);

			$pdf->SetFillColor(220, 80, 80);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('helvetica', 'B', 11);
			$title = 'Product ' . ($index + 1) . ': ' . ($prod['fld_product_name'] ?? 'N/A');
			$pdf->Cell(194, 7, $title, 0, 1, 'L', 1);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->Ln(2);

			$this->render_product_summary_block($pdf, $prod, $imageExts, $isSameState, $defaultGst);
			$pdf->Ln(2);

			$rawDescription = $prod['fld_product_description'] ?? '';
			$hasDescription = strlen(trim(strip_tags($rawDescription))) > 0;

			if (!empty($options['description']) && $hasDescription) {
				$this->ensurePageSpace($pdf, 30);
				$pdf->SetFont('helvetica', 'B', 9);
				$pdf->SetTextColor(220, 80, 80);
				$pdf->Cell(194, 6, 'Product Specifications', 0, 1, 'L');
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('helvetica', '', 8.5);
				$pdf->writeHTMLCell(
					194,
					0,
					$pdf->GetX(),
					$pdf->GetY(),
					$rawDescription,
					0,
					1,
					0,
					true,
					'',
					true
				);
				$pdf->Ln(2);
			}

			$hasKld = !empty($prod['kld_path']) && file_exists($prod['kld_path']);
			if (!empty($options['kld']) && $hasKld) {
				$this->render_media_fullwidth($pdf, 'KLD Diagram', $prod['kld_path'], $imageExts);
			}

			$hasArticle = !empty($prod['article_path']) && file_exists($prod['article_path']);
			if (!empty($options['article']) && $hasArticle) {
				$this->render_media_fullwidth($pdf, 'Article Drawing', $prod['article_path'], $imageExts);
			}

			$pdf->Ln(4);
		}
	}

	private function render_product_summary_block($pdf, $prod, $imageExts, $isSameState, $defaultGst) {
		$x = 8;
		$imageWidth = 95;
		$detailsWidth = 99;
		$gap = 2;
		$minHeight = 60;
		$padding = 2;

		$this->ensurePageSpace($pdf, $minHeight + 10);

		$startY = $pdf->GetY();
		$detailsX = $x + $imageWidth + $gap;
		$tempY = $startY + 4;

		$qty = floatval($prod['fld_qty'] ?? 0);
		$rate = floatval($prod['fld_rate'] ?? 0);
		$discPerc = floatval($prod['fld_disc_perc'] ?? 0);
		$discAmt  = floatval($prod['fld_disc_amt'] ?? 0);
		$taxableAmt = floatval($prod['fld_taxable_amt'] ?? 0);
		$totalAmt   = floatval($prod['fld_total_amt'] ?? 0);

		if ($taxableAmt <= 0) {
			$amount = $qty * $rate;
			if ($discAmt > 0) {
				$taxableAmt = $amount - $discAmt;
			} elseif ($discPerc > 0) {
				$taxableAmt = $amount - ($amount * $discPerc / 100);
			} else {
				$taxableAmt = $amount;
			}
			if ($taxableAmt <= 0 && $totalAmt > 0) {
				$taxableAmt = $totalAmt;
			}
		}

		$gstPerc = floatval($prod['fld_gst_perc'] ?? 0);
		if ($gstPerc <= 0) {
			$gstPerc = floatval($prod['pm_gst_percentage'] ?? 0);
			if ($gstPerc <= 0) {
				$gstPerc = $defaultGst;
			}
		}

		$gstAmt = ($taxableAmt * $gstPerc) / 100;
		$cgstAmt = $sgstAmt = $igstAmt = 0;
		if ($isSameState) {
			$cgstAmt = $gstAmt / 2;
			$sgstAmt = $gstAmt / 2;
		} else {
			$igstAmt = $gstAmt;
		}

	// Calculate grand total: always use taxable + GST for accurate item-wise total
	$grandTotal = floatval($prod['fld_grand_total'] ?? 0);
	// Only use stored grand_total if it's greater than taxable amount (meaning it includes GST)
	if ($grandTotal > 0 && $grandTotal > $taxableAmt) {
		// Use stored value if it appears to include GST
	} else {
		// Calculate: taxable amount + GST
		if ($isSameState) {
			$grandTotal = $taxableAmt + $cgstAmt + $sgstAmt;
		} else {
			$grandTotal = $taxableAmt + $igstAmt;
		}
	}

		$pdf->SetXY($detailsX + 3, $tempY);
		$pdf->SetFont('helvetica', 'B', 12);
		$pdf->SetTextColor(220, 80, 80);
		$pdf->MultiCell($detailsWidth - 6, 5, $prod['fld_product_name'] ?? 'N/A', 0, 'L', 0, 1);
		$pdf->SetTextColor(0, 0, 0);

		$tempY = $pdf->GetY() + 1;
		$lineHeight = 4.5;
		$labelWidth = 32;
		$valueWidth = $detailsWidth - 6 - $labelWidth;

		$writeDetailLine = function($label, $value, $wrap = false) use (&$pdf, &$detailsX, &$tempY, $lineHeight, $labelWidth, $valueWidth) {
			if ($value === '' || $value === null) {
				return;
			}
			$pdf->SetXY($detailsX + 3, $tempY);
			$pdf->SetFont('helvetica', 'B', 9);
			$pdf->Cell($labelWidth, $lineHeight, $label, 0, 0, 'L');
			$pdf->SetFont('helvetica', '', 9);
			if ($wrap || strlen($value) > 30) {
				$pdf->MultiCell($valueWidth, $lineHeight, $value, 0, 'L', 0, 1);
				$tempY = $pdf->GetY() + 1;
			} else {
				$pdf->Cell($valueWidth, $lineHeight, $value, 0, 1, 'L');
				$tempY = $pdf->GetY() + 1;
			}
		};

		$writeDetailLine('Category:', $prod['fld_product_group_name'] ?? '-', true);
		$writeDetailLine('HSN Code:', $prod['fld_hsn_code'] ?? '-', false);
		$writeDetailLine('Item Code:', $prod['fld_item_code'] ?? '-', true);
		$writeDetailLine('Model:', $prod['fld_model_no'] ?? '-', true);
		$qtyText = number_format($qty, 2) . ' ' . ($prod['fld_unit'] ?? 'NOS');
		$writeDetailLine('Quantity:', $qtyText, false);
		$writeDetailLine('Rate:', money_format_india($rate), false);

		$discParts = [];
		if ($discPerc > 0) {
			$discParts[] = number_format($discPerc, 2) . '%';
		}
		if ($discAmt > 0) {
			$discParts[] = money_format_india($discAmt);
		}
		$discDisplay = !empty($discParts) ? implode(' / ', $discParts) : '-';
		$writeDetailLine('Discount:', $discDisplay, false);
		$writeDetailLine('Taxable Amt:', money_format_india($taxableAmt), false);

		$pdf->SetXY($detailsX + 3, $tempY);
		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->Cell($labelWidth, $lineHeight, 'GST (' . number_format($gstPerc, 2) . '%):', 0, 0, 'L');
		$pdf->SetFont('helvetica', '', 9);
		if ($isSameState) {
			$gstText = 'CGST: ' . money_format_india($cgstAmt) . ' | SGST: ' . money_format_india($sgstAmt);
			$pdf->MultiCell($valueWidth, $lineHeight, $gstText, 0, 'L', 0, 1);
		} else {
			$pdf->Cell($valueWidth, $lineHeight, 'IGST: ' . money_format_india($igstAmt), 0, 1, 'L');
		}
		$tempY = $pdf->GetY() + 1;

		$pdf->SetXY($detailsX + 3, $tempY);
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->SetTextColor(220, 80, 80);
		$pdf->Cell($labelWidth, $lineHeight + 1, 'Total:', 0, 0, 'L');
		$pdf->SetFont('helvetica', 'B', 11);
		$pdf->Cell($valueWidth, $lineHeight + 1, money_format_india($grandTotal), 0, 1, 'L');
		$pdf->SetTextColor(0, 0, 0);

		$contentEndY = $pdf->GetY() + 2;
		$detailsHeight = $contentEndY - $startY;
		$finalHeight = max($minHeight, $detailsHeight);

		$imagePath = $prod['product_image_path'] ?? '';
		$ext = strtolower(pathinfo((string)$imagePath, PATHINFO_EXTENSION));
		if (!empty($imagePath) && file_exists($imagePath) && in_array($ext, $imageExts)) {
			$imgDims = @getimagesize($imagePath);
			$availableHeight = $finalHeight - (2 * $padding);
			$availableHeight = max($availableHeight, $minHeight - (2 * $padding));
			$imgHeight = $availableHeight;
			if ($imgDims && $imgDims[0] > 0) {
				$ratio = $imgDims[1] / $imgDims[0];
				$imgHeight = min($availableHeight, ($imageWidth - 2 * $padding) * $ratio);
			}
			// Suppress PNG iCCP profile warnings
			@$pdf->Image(
				$imagePath,
				$x + $padding,
				$startY + $padding,
				$imageWidth - (2 * $padding),
				$imgHeight,
				'',
				'',
				'',
				false,
				300,
				'',
				false,
				false,
				0,
				false,
				false,
				false
			);
		} else {
			$pdf->SetXY($x, $startY + ($finalHeight / 2) - 3);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->SetTextColor(120, 120, 120);
			$pdf->Cell($imageWidth, 6, 'Product image not available', 0, 0, 'C');
			$pdf->SetTextColor(0, 0, 0);
		}

    	$pdf->SetDrawColor(220, 80, 80);
		$pdf->SetLineWidth(0.5);
		$pdf->Rect($x, $startY, $imageWidth, $finalHeight, 'D');
		$pdf->Rect($detailsX, $startY, $detailsWidth, $finalHeight, 'D');

		$pdf->SetXY($x, $startY + $finalHeight + 3);
	}

	private function render_media_fullwidth($pdf, $title, $filePath, $imageExts) {
		$x = 8;
		$width = 194;
		$padding = 3;
		$minBoxHeight = 50;
		$maxImageHeight = 125;
		$maxImageWidth  = $width - (2 * $padding);

		$isImage = false;
		$imageWidth = 0;
		$imageHeight = 0;
		$ext = '';

		if (!empty($filePath) && file_exists($filePath)) {
			$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
			if (in_array($ext, $imageExts)) {
				$imgDims = @getimagesize($filePath);
				if ($imgDims && $imgDims[0] > 0 && $imgDims[1] > 0) {
					$ratio = $imgDims[1] / $imgDims[0];
					$imageWidth  = $maxImageWidth;
					$imageHeight = $imageWidth * $ratio;
					if ($imageHeight > $maxImageHeight) {
						$imageHeight = $maxImageHeight;
						$imageWidth  = $imageHeight / $ratio;
					}
				} else {
					$imageWidth  = $maxImageWidth;
					$imageHeight = $maxImageHeight - (2 * $padding);
				}
				$isImage = true;
			}
		}

		$estimatedHeight = $minBoxHeight;
		if ($isImage) {
			$estimatedHeight = max($minBoxHeight, $imageHeight + (2 * $padding));
		}

		$this->ensurePageSpace($pdf, $estimatedHeight + 12);

		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->SetTextColor(220, 80, 80);
		$pdf->Cell($width, 6, $title, 0, 1, 'L');
		$pdf->SetTextColor(0, 0, 0);

		$startY = $pdf->GetY();
		$boxHeight = $minBoxHeight;
		$contentWritten = false;

		if ($isImage) {
			$imgX = $x + ($width - $imageWidth) / 2;
			$imgY = $startY + $padding;
			// Suppress PNG iCCP profile warnings
			@$pdf->Image(
				$filePath,
				$imgX,
				$imgY,
				$imageWidth,
				$imageHeight,
				'',
				'',
				'',
				false,
				300,
				'',
				false,
				false,
				0,
				false,
				false,
				false
			);
			$boxHeight = max($minBoxHeight, $imageHeight + (2 * $padding));
			$contentWritten = true;
		} elseif (!empty($filePath) && file_exists($filePath)) {
			$pdf->SetXY($x + 3, $startY + 8);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->MultiCell($width - 6, 5, 'File attached: ' . htmlspecialchars(basename($filePath), ENT_QUOTES, 'UTF-8'), 0, 'L', 0, 1);
			$boxHeight = max($pdf->GetY() - $startY + 10, $minBoxHeight);
			$contentWritten = true;
		}

		if (!$contentWritten) {
			$pdf->SetXY($x + 3, $startY + 12);
			$pdf->SetFont('helvetica', '', 9);
			$pdf->SetTextColor(120, 120, 120);
			$pdf->Cell($width - 6, 6, 'Not available', 0, 0, 'C');
			$pdf->SetTextColor(0, 0, 0);
		}

    	$pdf->SetDrawColor(220, 80, 80);
		$pdf->SetLineWidth(0.5);
		$pdf->Rect($x, $startY, $width, $boxHeight, 'D');

		$pdf->SetY($startY + $boxHeight + 3);
	}

	private function ensurePageSpace($pdf, $requiredHeight = 25) {
		if ($pdf->GetY() + $requiredHeight > ($pdf->getPageHeight() - 20)) {
			$pdf->AddPage();
			if (!empty($this->currentHeaderData)) {
				$this->draw_header($pdf, $this->currentHeaderData['company'], $this->currentHeaderData['proforma_invoice'], $this->currentHeaderData['dealer']);
			}
		}
	}
}

?>





