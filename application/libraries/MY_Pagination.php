<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * Status: final
 * 
 * @category Accent_Interactive
 * @version 2.0
 * @author Joost van Veen
 * @copyright Accent Interactive
 */
  
/**
 * Extends CI's pagination class (http://codeigniter.com/user_guide/libraries/pagination.html)
 * It sets some variables for configuration of the pagination class dynamically,
 * depending on the URI, so we don't have to substract the offset from the URI,
 * or set $config['base_url'] and $config['uri_segment'] manually in the controller
 * 
 * Here is what is set by this extension class:
 * 1. $this->offset - the current offset
 * 2. $this->uri_segment - the URI segment to be used for pagination
 * 3. $this->base_url - the base url to be used for pagination
 * (where $this refers to the pagination class)
 *
 * The way this works is simple:
 * Drop this library in folder application/libraries
 * If we use pagination, it must ALWAYS follow the following syntax and be
 * located at the END of the URI:
 * PAGINATION_SELECTOR/offset
 * E.g. http://www.example.com/controller/action/Page/2
 *
 * The PAGINATION_SELECTOR is a special string which we know will ONLY be in the
 * URI when paging is set. Let's say the PAGINATION_SELECTOR is 'Page' (since most
 * coders never use any capitals in the URI, most of the times any string with
 * a single capital character in it will suffice). The PAGINATION_SELECTOR is
 * set in the general config file,
 * in the following index: config_item('pagination_selector')
 *
 * Example use (in controller):
 * // Set pagination and get pagination HTML
 * $this->data['pagination'] = $this->pagination->get_pagination($this->db->count_all_results('my_table'), 10);
 *
 * // Retrieve paginated results, using the dynamically determined offset
 * $this->db->limit($config['per_page'], $this->pagination->offset);
 * $query = $this->db->get('my_table');
 * @package Core
 * @subpackage Libraries
 */
class MY_Pagination extends CI_Pagination
{
    
    /**
     * Pagination offset.
     * @var integer
     */
    public $offset = 0;
    
    /**
     * Opening HTML tag for pagination string 
     * @var string
     */
    public $cur_tag_open = '&nbsp;<span class="dimmed">';
    
    /**
     * Opening HTML tag for pagination string
     * @var unknown_type
     */
    public $cur_tag_close = '</span>';
    
    /**
     * Text for link to first page
     * @var string
     */
    public $first_link = '&laquo;';
    
    /**
     * Text for link to last page
     * @var string
     */
    public $last_link = '&raquo;';
    
    /**
     * Number of links to show in pagination
     * @var integer 
     */
    public $num_links = 8;
    
    /**
     * CI Super object
     */
    private $_ci;
    
    /**
     * Pagination selector to be used in URI. Make sure to set this to a value 
     * that is never used elsewhere in the URI.
     * @var string
     */
    public $pagination_selector = 'Page';

    function MY_Pagination ()
    {
        
        parent::__construct();
        $this->_ci = & get_instance();
        log_message('debug', "MY custom Pagination Class Initialized");
        
        // Set pagination selector
        config_item('pagination_selector') == '' || $this->pagination_selector = config_item('pagination_selector');
        
        $this->_set_pagination_offset();
    }

    /**
     * Rturn HTML for pagination, based on count ($total_rows) and limit ($per_page)
     * @param integer $total_rows
     * @param integer $per_page
     * @return string
     */
    public function get_pagination ($total_rows, $per_page)
    {
        if ($total_rows > $per_page) {
            $this->initialize(array('total_rows' => $total_rows, 'per_page' => $per_page));
            return $this->create_links();
        }
    }

    /**
     * Set dynamic pagination variables in $this->_ci->data['pagvars']
     * @return void
     */
    private function _set_pagination_offset ()
    {
        
        // Store pagination offset
        if (strstr($this->_ci->uri->uri_string(), $this->pagination_selector)) {
            
            // The pagination selector is in the URI => We have pagination!
            

            // Get the segment offset for the pagination selector
            $segments = $this->_ci->uri->segment_array();
            
            // Loop through segments to retrieve pagination offset
            foreach ($segments as $key => $value) {
                
                // Find the pagination_selector and work from there
                if ($value == $this->pagination_selector) {
                    
                    // Store pagination offset
                    $this->offset = $this->_ci->uri->segment($key + 1);
                    
                    // Store pagination segment
                    $this->uri_segment = $key + 1;
                    
                    // Set base url for paging. This only works if the
                    // pagination_selector and paging offset are AT THE END of
                    // the URI!
                    $uri = $this->_ci->uri->uri_string();
                    $pos = strpos($uri, $this->pagination_selector);
                    $this->base_url = config_item('base_url') . substr($uri, 0, $pos + strlen($this->pagination_selector));
                }
            }
        }
        else {
            // Pagination selector was not found in URI string. So offset is 0
            $this->offset = 0;
            $this->uri_segment = 0;
            $this->base_url = config_item('base_url') . substr($this->_ci->uri->uri_string(), 1) . '/' . $this->pagination_selector;
        }
    }
}