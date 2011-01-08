<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Status: final
 * 
 * LICENSE AND COPYRIGHT
 * 
 * The intellectual property rights and copyright to this product belong to 
 * Accent Interactive, The Netherlands. None of this code may be edited, shared,
 * sold, copied or reproduced without Accent Interactive's prior written consent.
 * 
 * Anybody who edits this product, or adds to it, whether or not in commision 
 * for Accent Interactive, automatically and exclusively transfers full 
 * ownership of all and any intellectual rights and copyrights for these 
 * additions or alterations to Accent Interactive.
 * 
 * If the editor makes use of external code of contractors who can claim any 
 * kind of intellectual ownership regarding the added or altered code in this 
 * document, then the editor will secure that these rights will comply fully to
 * the clause mentioned above.
 * 
 * Accent Interactive's clients may recieve a non-unique, non-transferable license
 * to use this product.
 * 
 * @category Accent_Interactive
 * @version 3.0
 * @author Joost van Veen
 * @copyright Accent Interactive
 */

/**
 * - create an array of all elements that will be used in a form
 * - populate these form elements with initial values
 * - set CI validation rules for all form elements
 * - prep the form data before and after validation
 * - validate the form and store errors
 * - Repopulate all form elements after post
 * - create an array containing the POST values for all form elements that 
 * should be processed. 
 * - Provide the HTML output for every form element
 * - Provide the HTML output for the form as a whole
 * - Provide the HTML output for any validation errors that occurred
 * 
 * Typical usage:
 * $elements = array():
 * $elements['hidden_field'] = array(
 *     'type' => 'hidden', 
 *     'value' => 'This value is not in $this->autoform->get_form_results()', 
 *     'ignore' => TRUE);
 * $elements['text'] = array(
 *    'type' => 'text',
 *    'label' => 'Login',
 *    'attributes' => array(
 *        'onclick' => "this.value= '';", 
 *        'class' => 'some_class', 
 *        'maxlength' => 16), 
 *    'value' => '', 
 *    'validation' => 'required|max_length[16]|alpha_dash|trim|xss_clean', 
 *    'append' => 'Alphanumeric characters, underscores and dashes only');
 * $elements['password'] = array(
 *    'type' => 'password', 
 *    'label' => 'Password', 
 *    'validation' => 'required|max_length[16]|alpha_dash|trim|xss_clean');
 * // Submit button
 * $elements['submit'] = array(
 *    'type' => 'submit', 
 *    'value' => 'Log in', 
 *    'attributes' => array(
 *        'onclick' => 'alert("Form was submitted");'), 
 *    'ignore' => TRUE);
 *    
 * $this->load->library('autoform');
 * $this->autoform->initiate($elements, array('form_id' => 'login_form', 'action', uri_string()));
 * if ($this->autoform->validate() == TRUE) {
 *      $this->data['formresults'] = $this->autoform->get_form_results();
 *      // Do something with form results
 * }
 * $this->data['form'] = $this->autoform->get_form_html();
 * // Show form in view like so: echo $form; 
 * 
 * @package Core
 * @subpackage Libraries
 * @uses MY_Form_validation, filter_helper
 */
class Autoform
{

    /**
     * Should we show individual errors or not?
     * @var boolean
     */
    public $show_individual_errors = TRUE;

    /**
     * Location for css
     * @var string
     */
    public $css_folder = '';

    /**
     * Location for javascript
     * @var string
     */
    public $javascript_folder = '';

    /**
     * The function name we will use for opening the form, 
     * i.e. form_open or form_open_multipart
     * @author Joost van Veen
     * @var string
     */
    private $_form_open_call = 'form_open';

    /**
     * The form ID. This will be part of the form, as a hidden field. It is used 
     * to check whether the form was posted.
     * @author Joost van Veen
     * @var string
     */
    private $_form_id = 'form_1';

    /**
     * Whether or not the was posted
     * @author Joost van Veen 
     * @var boolean
     */
    private $_is_posted = FALSE;

    /**
     * The URL this form is sent to; i.e. the action attribute.
     * @author Joost van Veen
     * @var string
     */
    private $_action = '';

    /**
     * Instance of Codeigniter master object
     * @author Joost van Veen
     * @var object
     */
    private $_ci;

    /**
     * String to score all validation errors.
     * @author Joost van Veen
     * @var string
     */
    private $_validation_errors = '';

    /**
     * Array of all elements that are part of this form. This array is divided 
     * into two seperate keys:
     * $this->_form_elements['hidden']
     * $this->_form_elements['visible']
     * 
     * The advantage to this approach is that we process hidden fields in a 
     * different way than vidible fields; for instance by showing them in a 
     * different HTML wrapper.
     * @author Joost van Veen
     * @var array
     */
    private $_form_elements = array('hidden' => array(), 
        'visible' => array());

    /**
     * All the fields that are passed to the form class on creation
     * @author Joost van Veen
     * @var array
     */
    private $_form_fields = array();

    /**
     * Array of fields that need to be processed and their values
     * @author Joost van Veen
     * @var array
     */
    private $_form_results = array();

    /**
     * The metadata for this form, being action, id, method, etc.
     * @author Joost van Veen
     * @var array
     */
    private $_metadata = array();

    /**
     * Var that tells us whether the form was succesfully validated or not.
     * @author Joost van Veen
     * @var boolean
     */
    private $_valid = FALSE;

    /**
     * Will hold the HTML template to wrap form elements in.
     * @author Joost van Veen
     * @var array
     */
    public $form_template = array();

    /**
     * The indent to use in HTML form return
     * @author Joost van Veen
     * @var string
     */
    public $indent = '    ';

    /**
     * Get the CI master object, load helpers and libraries.
     * @author Joost van Veen
     * @return void
     */
    public function __construct ()
    {
        // Instantiate the CI libraries so we can work with them
        $this->_ci = & get_instance();
        
        // Set a default action URI for this form
        $this->_action = C_ROOT_URL . substr($this->_ci->uri->uri_string(), 1);
        $this->javascript_folder = C_ROOT_URL . 'files/jscripts/system/';
        $this->css_folder = C_ROOT_URL . 'files/css/system/';
        
        // Load validation library if necessary.
        // This will also automatically load the CI form helper.
        if (! isset($this->_ci->form_validation)) {
            $this->_ci->load->library('form_validation');
        }
        
        // Load form helper if it hasn't been loaded yet
        if (function_exists('form_open') == FALSE) {
            $this->_ci->load->helper('form');
        }
        
        // Set default HTML template
        $this->form_template['before_visible'] = '<table cellpadding="0" cellspacing="5">' . "\n";
        $this->form_template['after_visible'] = '</table>' . "\n";
        $this->form_template['before_label'] = '    <tr><td valign="top" class="label">';
        $this->form_template['after_label'] = '</td>' . "\n";
        $this->form_template['before_input'] = '    <td valign="top" class="input">';
        $this->form_template['after_input'] = '</td></tr>' . "\n";
        
        // TODO: datepicker date locale in config file?
        

        // Set default error delimiters
        $this->_ci->form_validation->set_error_delimiters('<p class="error">', '</p>');
        
        log_message('debug', "Autoform library Initialized");
    }

    /**
     * - Set up formelement array
     * - Set validation rules
     * - Prep form values
     * 
     * @author Joost van Veen
     * @param array $form_data
     * @param array $form_meta_data
     * @return void
     */
    public function initiate ($form_elements, $form_meta_data = array())
    {
        
        $this->_metadata = $form_meta_data;
        $this->_form_fields = $form_elements;
        
        // Set the form id if it was passed. If not, we'll stick to the default value.
        if (isset($form_meta_data['form_id']) && $form_meta_data['form_id'] != '') {
            $this->_form_id = $form_meta_data['form_id'];
        }
        
        if (isset($form_meta_data['action'])) {
            $this->_action = $form_meta_data['action'];
        }
        
        // Some default metadata
        $this->_metadata['form_id'] = $this->_form_id;
        $this->_metadata['class'] = isset($form_meta_data['class']) ? $form_meta_data['class'] . ' autoform': 'autoform';
        $this->_metadata['use_text_area_javascript'] = FALSE;
        $this->_metadata['use_tiny_mce_javascript'] = FALSE;
        $this->_metadata['use_datepicker_javascript'] = FALSE;
        $this->_metadata['use_timepicker_javascript'] = FALSE;
        
        // Prep the elements array
        $this->_prep_elements_array();
        
        // Set CI validation rules
        $this->_add_rules();
        
        // Prep POST values before validation
        if ($this->_ci->input->post('form_id')) {
            $this->_prep_before_validation();
            $this->_is_posted = TRUE;
        }
    
    }

    /**
     * - Validate the form
     * - Return the entire form object
     * 
     * @author Joost van Veen
     * @return object
     */
    public function validate ()
    {
        
        // Validate form using CI Form_validation class
        $this->_valid = $this->_ci->form_validation->run();
        
        // Prep POST values after validation
        $this->_prep_after_validation();
        
        // Store POST values in $this->_form_results if form validated
        if ($this->_valid == TRUE) {
            
            foreach ($this->_form_fields as $id => $element) {
                
                // Only add data to $this->_form_results that need to be processed
                if ($element['process'] == TRUE) {
                    
                    switch ($element['type']) {
                        case 'datepicker':
                            
                            // Convert datepicker value before adding it to array form_results
                            if ($element['format'] == 'date') {
                                $result = filt_date_human_to_mysql($this->_ci->input->post($id));
                            }
                            elseif ($element['format'] == 'unix') {
                                $result = filt_date_human_to_unix($this->_ci->input->post($id));
                            }
                            break;
                        case 'datetimepicker':
                            
                            // Convert datetimepicker value before adding it to array form_results
                            if ($element['format'] == 'datetime') {
                                $result = filt_datetime_human_to_mysql($this->_ci->input->post($id));
                            }
                            elseif ($element['format'] == 'unix') {
                                $result = filt_datetime_human_to_unix($this->_ci->input->post($id));
                            }
                            break;
                        default:
                            $result = $this->_ci->input->post($id);
                            break;
                    }
                    
                    $this->_form_results[$id] = $result;
                }
            }
        }
        else {
            $this->_validation_errors = validation_errors();
        }
        
        // Construct the inputs for this form, i.e. <input>, <textarea>, etc.
        // and add tehm to $this->_form_elements
        $this->_set_inputs();
        
        // Return validation result
        return $this->_valid;
    }

    /**
     * Return TRUE if the form was posted and validated succesfully. 
     * @author Joost van Veen
     * @return boolean
     */
    public function is_valid ()
    {
        return $this->_valid;
    }

    /**
     * Return a HTML that contains the entire form. Wrappers are defined in 
     * array $this->form_template. This method is available to quickly echo a form.
     * 
     * If you need more control, loop through the form elements in your view by 
     * hand, like so:
     * $form_elements = $this->forms->get_visible_form_elements;
     * echo $this->forms->form_open();
     * echo $form_elements['address']['label'] . $form_elements['postcode']['input'] . ' ' . $form_elements['address']['input'] . $form_elements['address']['input'] . $form_elements['address']['input'];
     * echo $this->forms->form_close();
     * 
     * @author Joost van Veen
     * @return string
     */
    public function get_form_html ()
    {
        $retval = "\n";
        // Add any javascript
        $retval .= $this->get_javascript() . "\n";
        
        // Add form open tag and hidden fields
        $retval .= $this->indent . $this->get_form_open() . "\n";
        
        // Add form elements, wrapped in HTML template
        $retval .= $this->form_template['before_visible'];
        foreach ($this->_form_elements['visible'] as $id => $element) {
            
            // Label
            $retval .= $this->form_template['before_label'];
            $retval .= get_index($element, 'label');
            $retval .= $this->form_template['after_label'];
            
            // Input
            $retval .= $this->form_template['before_input'];
            $retval .= get_index($element, 'input');
            $retval .= get_index($element, 'error');
            $retval .= $this->form_template['after_input'];
        }
        $retval .= $this->form_template['after_visible'];
        
        // Add form closing tag
        $retval .= $this->indent . form_close() . "\n";
        return $retval;
    }

    /**
     * Returns the necessary javascript for this form.
     * @author Joost van Veen
     * @return string
     */
    public function get_javascript ($including_jquery = FALSE)
    {
        
        $retval = "\n";
        $retval .= $including_jquery == TRUE ? $this->indent . load_jquery(NULL, TRUE) . "\n" : '';
        $retval .= $this->_metadata['use_tiny_mce_javascript'] == TRUE ? $this->indent . $this->_set_ini_tiny_mce_javascript() . "\n" : '';
        $retval .= $this->_metadata['use_text_area_javascript'] == TRUE ? $this->indent . $this->_text_area_javascript() . "\n" : '';
        $retval .= $this->_metadata['use_datepicker_javascript'] == TRUE ? $this->_load_datepicker() . "\n" : '';
        $retval .= $this->_metadata['use_timepicker_javascript'] == TRUE ? $this->_load_timepicker() . "\n" : '';
        return $retval;
    }

    /**
     * Return the HTML code to open a form. 
     * If there is a file upload field in this form it will set the enctype
     * If there are any hidden fields in this form they wille be returned as well
     * @author Joost van Veen
     * @param array $element
     * @param array $attributes
     * @return string
     */
    public function get_form_open ($action = '', $attributes = array())
    {
        $action = $action == '' ? $this->_action : $action;
        $attributes['method'] = isset($attributes['method']) ? $attributes['method'] : 'post';
        $attributes['class'] = $this->_metadata['class'];
        $attributes['id'] = $this->_metadata['form_id'];
        $function = $this->_form_open_call;
        return $function($action, $attributes, $this->_form_elements['hidden']);
    }

    /**
     * Returns the action for this form
     * @author Joost van Veen
     * @return string
     */
    public function get_action ()
    {
        return $this->_action;
    }

    /**
     * Sets the action for this form
     * @author Joost van Veen
     * @param string $_action
     */
    public function set_action ($action)
    {
        $this->_action = $action;
    }

    /**
     * Returns all the form elements for this form, as an associative array, 
     * divided into hidden elements and visible elements.
     * $retval['hidden'] = array();
     * $retval['visible'] = array();
     * @author Joost van Veen
     * @return array
     */
    public function get_form_elements ()
    {
        return $this->_form_elements;
    }

    /**
     * Returns the visible elements for this form, as an associative array.
     * @author Joost van Veen
     * @return array
     */
    public function get_visible_form_elements ()
    {
        return $this->_form_elements['visible'];
    }

    /**
     * Returns the hidden elements for this form, as an associative array.
     * @author Joost van Veen
     * @return array
     */
    public function get_hidden_form_elements ()
    {
        return $this->_form_elements['hidden'];
    }

    /**
     * Return an array for a form element, e.g.
     * array($element['label'] => 'My label', 
     * $element['input'] => '<input type="text" name="my_name" id="my_name" value="Some value" />', 
     * $element['error'] => 'My label is required');
     * 
     * If $attribute is passed, return the index for that attaribute, e.g.:
     * If attribute == 'label', the returned string for a login field could be 
     * 'Login<span class="required">*</span>'
     * 
     * Return an empty string if element does not exist.
     * 
     * @author Joost van Veen
     * @param string $element_id
     * @param string $attribute Optional
     * @return mixed
     */
    public function get_element ($element_id, $attribute = '')
    {
        if (isset($this->_form_elements['visible'][$element_id])) {
            $retval = $this->_form_elements['visible'][$element_id];
        }
        elseif (isset($this->_form_elements['hidden'][$element_id])) {
            $retval = $this->_form_elements['hidden'][$element_id];
        }
        else {
            $retval = array();
        }
        
        if ($attribute != '') {
            $retval = is_array($element) && isset($element[$attribute]) ? $element[$attribute] : '';
        }
        return $retval;
    }

    /**
     * Returns the validation errors for this form.
     * @author Joost van Veen
     * @return string
     */
    public function get_validation_errors ()
    {
        return $this->_validation_errors;
    }

    /**
     * Returns the form results as a key=>value pair array
     * @author Joost van Veen
     * @return array
     */
    public function get_form_results ()
    {
        return $this->_form_results;
    }

    /**
     * Returns the metadata for this form.
     * @author Joost van Veen
     * @return array
     */
    public function get_metadata ()
    {
        return $this->_metadata;
    }

    /**
     * Prep the array of elements. This means we set element's id's to be the 
     * same as names, we sanitize validation settings per element and place the 
     * element in the proper index, such as $this->_form_elements['hidden']
     * 
     * The index used for the form elements is their id:
     * e.g. $this->_form_elements['hidden']['date_created'] = $element;
     * e.g. $this->_form_elements['visible']['login'] = $element;
     * @author Joost van Veen
     * @param array $form_elements
     * @return void
     */
    private function _prep_elements_array ()
    {
        // First, we'll add the form id as a hidden field.
        $this->_form_elements['hidden']['form_id'] = $this->_form_id;
        
        foreach ($this->_form_fields as $id => $element) {
            
            if (! isset($element['type'])) {
                show_error('You did not set a type for element \'' . $id . '\'');
            }
            
            // Set element metadata
            $this->_form_fields[$id]['process'] = isset($element['ignore']) && $element['ignore'] == TRUE ? FALSE : TRUE;
            $this->_form_fields[$id]['multiple'] = isset($element['multiple']) && $element['multiple'] == TRUE ? TRUE : FALSE;
            if ($element['type'] == 'multiselect' || $element['type'] == 'checkboxgroup') {
                $this->_form_fields[$id]['multiple'] = TRUE;
            }
            $this->_form_fields[$id]['value'] = isset($element['value']) ? $element['value'] : '';
            $this->_form_fields[$id]['validation'] = isset($element['validation']) ? $element['validation'] : '';
            $this->_form_fields[$id]['prepend'] = isset($element['prepend']) ? '<span class="prepend">' . $element['prepend'] . '</span>' : '';
            $this->_form_fields[$id]['append'] = isset($element['append']) ? '<span class="append">' . $element['append'] : '</span>';
            
            // Set element attributes
            $this->_form_fields[$id]['attributes'] = isset($element['attributes']) ? $element['attributes'] : array();
            $this->_form_fields[$id]['attributes']['name'] = $id;
            $this->_form_fields[$id]['attributes']['name'] .= $this->_form_fields[$id]['multiple'] == TRUE ? '[]' : '';
            $this->_form_fields[$id]['attributes']['id'] = $id;
            
            // Add the element to the 'visible' or 'hidden' array
            if ($element['type'] == 'hidden') {
                $this->_form_elements['hidden'][$id] = $element['value'];
                $this->_form_fields[$id]['type'] = 'hidden';
            }
            else {
                // Set element display data
                $this->_form_elements['visible'][$id]['label'] = isset($element['tooltip']) && $element['tooltip'] != '' ? '<a href="#" id="tooltip_' . $id . '" class="tooltip" title="' . $element['tooltip'] . '">' . img('files/gfx/system/help.png') . '</a><script type="text/javascript">$(\'#tooltip_' . $id . '\').tooltip({cssClass:"tooltip-grey"});</script>' : '';
                $this->_form_elements['visible'][$id]['label'] .= isset($element['label']) ? $element['label'] : '&nbsp;';
                $this->_form_elements['visible'][$id]['label'] .= strstr($this->_form_fields[$id]['validation'], 'required') ? '<span class="required">*</span>' : '';
                
                $this->_form_elements['visible'][$id]['prepend'] = isset($element['prepend']) ? $element['prepend'] : '';
                $this->_form_elements['visible'][$id]['append'] = isset($element['append']) ? $element['append'] : '';
                $this->_form_elements['visible'][$id]['error'] = isset($element['error']) ? $element['error'] : '';
            }
            
            // Adjust form_open tag if we have a file upload field
            if ($element['type'] == 'file' || $element['type'] == 'image') {
                $this->_form_open_call = 'form_open_multipart';
            }
        }
    }

    /**
     * Add form elements to CI validation rules 
     * @author Joost van Veen
     * @param array $element
     * @return void
     */
    private function _add_rules ()
    {
        foreach ($this->_form_fields as $id => $element) {
            
            // Prep rule data
            if (! isset($element['friendly_name']) || $element['friendly_name'] == '') {
                if ((! isset($element['friendly_name']) || $element['friendly_name'] == '') && isset($element['label'])) {
                    $element['friendly_name'] = $element['label'];
                }
                else {
                    $element['friendly_name'] = $id;
                }
            }
            
            // Set some magic validation rules defined in MY_Form_validation
            if ($element['type'] == 'datepicker' || $element['type'] == 'datetimepicker') {
                $element['validation'] .= strstr($element['validation'], 'required') ? '|required_date' : '';
            }
            if ($element['type'] == 'datepicker') {
                $element['validation'] .= '|valid_datepicker';
            }
            if ($element['type'] == 'datetimepicker') {
                $element['validation'] .= '|valid_datetimepicker';
            }
            
            // Load sessions if we have a captcha
            if ($element['type'] == 'captcha' && ! isset($this->_ci->session)) {
                $this->_ci->load->library('session');
            }
            
            // Set rule for this element
            $this->_ci->form_validation->set_rules($id, $element['friendly_name'], $element['validation']);
        }
    }

    /**
     * Construct the inputs for this form and store them in $this->_form_elements.
     * @author Joost van Veen
     * @return void
     */
    private function _set_inputs ()
    {
        
        // Every field type maps to a private function in this class. Mapping is
        // set in this array.
        $field_mapping = array();
        $field_mapping['hidden'] = '_form_hidden';
        $field_mapping['input'] = '_form_input';
        $field_mapping['text'] = '_form_input';
        $field_mapping['password'] = '_form_password';
        $field_mapping['file'] = '_form_upload';
        $field_mapping['upload'] = '_form_upload';
        $field_mapping['image'] = '_form_image';
        $field_mapping['textarea'] = '_form_textarea';
        $field_mapping['select'] = '_form_dropdown';
        $field_mapping['dropdown'] = '_form_dropdown';
        $field_mapping['multiselect'] = '_form_multiselect';
        $field_mapping['checkbox'] = '_form_checkbox';
        $field_mapping['submit'] = '_form_submit';
        $field_mapping['label'] = '_form_label';
        $field_mapping['reset'] = '_form_reset';
        $field_mapping['button'] = '_form_button';
        $field_mapping['radiogroup'] = '_form_radiogroup';
        $field_mapping['checkboxgroup'] = '_form_radiogroup';
        $field_mapping['datepicker'] = '_form_datepicker';
        $field_mapping['datetimepicker'] = '_form_datetimepicker';
        $field_mapping['captcha'] = '_form_captcha';
        
        // Set inputs.
        foreach ($this->_form_fields as $id => $element) {
            
            if ($element['type'] != 'hidden') {
                $this->_form_elements['visible'][$id]['error'] = $this->show_individual_errors == TRUE ? form_error($id) : '';
                
                // Set input. Check if we have a function mapping value for this field.
                if (isset($field_mapping[$element['type']])) {
                    
                    // Check if the mapped method exists and run it.
                    if (method_exists($this, $field_mapping[$element['type']])) {
                        
                        if (form_error($id) != '') {
                            // A validation error occured. Adjust the input's class.
                            if (isset($element['attributes']['class']) && $element['attributes']['class'] != '') {
                                $element['attributes']['class'] .= ' inputerror';
                            }
                            else {
                                $element['attributes']['class'] = 'inputerror';
                            }
                        }
                        // Store the actual input in 'hidden' or 'visible' array
                        $this->_form_elements['visible'][$id]['input'] = $element['prepend'] . $this->$field_mapping[$element['type']]($element) . $element['append'];
                        ;
                    }
                    else {
                        // The mapped function does not exist.
                        $this->_form_elements['visible'][$id]['input'] = '';
                        show_error('The mapped function \'' . $field_mapping[$element['type']] . '\' for field \'' . $id . '\' does not exist');
                    }
                }
                else {
                    // The input type is not available in the mapping array
                    show_error('The input type \'' . $element['type'] . '\' for field \'' . $id . '\' was not mapped to a form_helper function');
                }
            }
        }
    }

    /**
     * This pre function is called before validation. Any necessary pre-validation 
     * prepping is done here.
     * @author Joost van Veen
     * @return array
     */
    private function _prep_before_validation ()
    {
        foreach ($this->_form_fields as $key => $field) {
            // If we have a datetimepicker field we have to collect both the date and
            // time values and merge them into a single value.
            if ($field['type'] == 'datetimepicker') {
                $_POST[$key] = $this->_ci->input->post('datepicker_' . $key) . ' ' . $this->_ci->input->post('timepicker_' . $key);
            }
        }
    
    }

    /**
     * This pre function is called after validation. Any necessary post-validation 
     * prepping is done here.
     * @author Joost van Veen
     * @return array
     */
    private function _prep_after_validation ()
    {}

    /**
     * Return the HTML code for a hidden field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_hidden ($element)
    {
        return form_hidden($element['attributes']['name'], $element['value']);
    }

    /**
     * Return the HTML code for a text input field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_input ($element)
    {
        $element['attributes']['value'] = $this->_is_posted == TRUE ? $this->_ci->input->post($element['attributes']['name']) : $element['value'];
        return form_input($element['attributes']);
    }

    /**
     * Return the HTML code for a password input field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_password ($element)
    {
        $element['attributes']['value'] = $this->_is_posted == TRUE ? '' : $element['value'];
        return form_password($element['attributes']);
    }

    /**
     * Return the HTML code for a file upload field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_upload ($element)
    {
        $data = $element['attributes'];
        return form_upload($data);
    }

    /**
     * Return the HTML code for a image upload field, including image thumbnail 
     * and 'delete' checkbox.
     * 
     * NOTE: the img_delete[] checkbox will not be in the form results array! Check 
     * for it in the $_POST array to see if you need to delete an image.
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_image ($element)
    {
        $retval = '';
        $data = $element['attributes'];
        $retval .= form_upload($data);
        if (isset($element['value']) && $element['value'] != '') {
            // We have an image. Show it.
            $retval .= '<div class="image"><img src="' . $element['value'] . '" alt="' . $element['value'] . '" class="img" /></div>';
            $name = 'delete_image';
            $checkbox = array(
                'name' => $name, 
                'id' => $name . $element['attributes']['name'], 
                'value' => $element['value']);
            $retval .= '<div class="image">' . form_checkbox($checkbox) . '<label for="' . $name . $element['attributes']['name'] . '">Verwijder afbeelding</label></div>';
        }
        
        return $retval;
    }

    /**
     * Return the HTML code for a text area
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_textarea ($element)
    {
        // Do we need max_length javascript?
        if (isset($element['attributes']['max_length'])) {
            $this->_metadata['use_text_area_javascript'] = TRUE;
            $element['attributes']['onkeydown'] = "textCounter(" . $element['attributes']['id'] . ",remLen_" . $element['attributes']['id'] . "," . $element['attributes']['max_length'] . ")";
            $element['attributes']['onkeyup'] = "textCounter(" . $element['attributes']['id'] . ",remLen_" . $element['attributes']['id'] . "," . $element['attributes']['max_length'] . ")";
        }
        
        // Do we need TinyMce javascript?
        if (isset($element['attributes']['class']) && $element['attributes']['class'] == 'tiny_mce') {
            $this->_metadata['use_tiny_mce_javascript'] = TRUE;
        }
        
        $element['attributes']['value'] = $this->_is_posted == TRUE ? str_replace('&lt;', '<', str_replace('&gt;', '>', $this->_ci->input->post($element['attributes']['name']))) : $element['value'];
        $retval = '';
        $retval .= form_textarea($element['attributes']);
        if (isset($element['attributes']['max_length'])) {
            $sparechars = $element['attributes']['max_length'] - strlen($element['attributes']['value']);
            $retval .= '<input class="remlen" readonly="readonly" type="text" name="remLen_' . $element['attributes']['name'] . '" id="remLen_' . $element['attributes']['name'] . '" size="4" maxlength="4" value="' . $sparechars . '" />';
        }
        
        return $retval;
    }

    /**
     * Return the HTML code for a select field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_dropdown ($element)
    {
        $id = $element['attributes']['name'];
        unset($element['attributes']['name']);
        
        $extra = isset($element['extra']) ? $element['extra'] : '';
        if (isset($element['attributes'])) {
            foreach ($element['attributes'] as $key => $value) {
                $extra .= ' ' . $key . '="' . $value . '"';
            }
        }
        
        $value = $this->_is_posted == TRUE ? $this->_ci->input->post($id) : $element['value'];
        
        $options = isset($element['options']) ? $element['options'] : array();
        return form_dropdown($id, $options, $value, $extra);
    }

    /**
     * Return the HTML code for a multiplke select field
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_multiselect ($element)
    {
        $id = $element['attributes']['name'];
        unset($element['attributes']['name']);
        
        $extra = isset($element['extra']) ? $element['extra'] : '';
        if (isset($element['attributes'])) {
            foreach ($element['attributes'] as $key => $value) {
                $extra .= ' ' . $key . '="' . $value . '"';
            }
        }
        
        $value = $this->_is_posted == TRUE ? $this->_ci->input->post($element['attributes']['id']) : $element['value'];
        
        $options = isset($element['options']) ? $element['options'] : array();
        return form_multiselect($id, $options, $value, $extra);
    }

    /**
     * Return the HTML code for a single checkbox
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_checkbox ($element)
    {
        $value = $this->_is_posted == TRUE ? $this->_ci->input->post($element['attributes']['name']) : $element['value'];
        $element['attributes']['value'] = $element['checkvalue'];
        if ($value == $element['checkvalue']) {
            $element['attributes']['checked'] = TRUE;
        }
        $checklabel = isset($element['checklabel']) ? '&nbsp;' . form_label($element['checklabel'], $element['attributes']['name']) : '';
        return form_checkbox($element['attributes']) . $checklabel;
    }

    /**
     * Return the HTML code for a submit button
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_submit ($element)
    {
        $data = $element['attributes'];
        if (isset($element['value']) && $element['value'] != '') {
            $data['value'] = $element['value'];
        }
        return form_submit($data);
    }

    /**
     * Return the HTML code for a label
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_label ($text, $id, $attributes = array())
    {
        return form_label($text, $id, $attributes);
    }

    /**
     * Return the HTML code for a reset button
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_reset ($element)
    {
        $data = $element['attributes'];
        if (isset($element['value']) && $element['value'] != '') {
            $data['value'] = $element['value'];
        }
        return form_reset($data);
    }

    /**
     * Return the HTML code for a regular button
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_button ($element)
    {
        $data = $element['attributes'];
        if (isset($element['value']) && $element['value'] != '') {
            $data['value'] = $element['value'];
        }
        return form_button($data);
    }

    /**
     * Return the HTML code for a group of radio buttons
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_radiogroup ($element)
    {
        // Set the CI form helper function that we will use
        if ($element['type'] == 'radiogroup') {
            $function_call = 'form_radio';
        }
        else {
            $function_call = 'form_checkbox';
        }
        
        $retval = '';
        $radios = '';
        $name = $element['attributes']['name'];
        $id = $element['attributes']['id'];
        unset($element['attributes']['name'], $element['attributes']['id']);
        $value = $this->_is_posted == TRUE ? $this->_ci->input->post($id) : $element['value'];
        if (! is_array($value)) {
            $value = array(
                $value);
        }
        
        foreach ($element['options'] as $option => $label) {
            $data = array();
            $data['id'] = $id . '_' . $option;
            $data['name'] = $name;
            $data['value'] = $option;
            $data['checked'] = in_array($data['value'], $value) ? TRUE : FALSE;
            $radios .= '<p class="option">' . $function_call($data);
            $radios .= form_label($label, $data['id']) . '</p>';
        }
        
        if (isset($element['fieldset'])) {
            $retval .= form_fieldset($element['fieldset'], $element['attributes']);
            $retval .= $radios;
            $retval .= form_fieldset_close();
        }
        else {
            $retval .= $radios;
        }
        
        return $retval;
    }

    /**
     * Return the HTML code for a datepicker
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_datepicker ($element)
    {
        
        $this->_metadata['use_datepicker_javascript'] = TRUE;
        
        $default_mysql_format = 'date';
        $datepicker_format = 'd-m-Y';
        $format = isset($element['format']) ? $element['format'] : $default_mysql_format;
        
        // Set value in the datetimepicker locale (d-m-Y)
        if ($this->_is_posted == FALSE) {
            
            //Convert the initial value to d-m-Y H:i
            if ($format == 'date') {
                $conversion_function = 'filt_date_mysql_to_human';
            }
            elseif ($format == 'unix') {
                $conversion_function = 'filt_date_unix_to_human';
            }
            else {
                show_error('No conversion function is set for default MYSQL format \'' . $format . '\'');
            }
            $element['attributes']['value'] = $conversion_function($element['value']);
        }
        else {
            $element['attributes']['value'] = $this->_ci->input->post($element['attributes']['name']);
        }
        
        $retval = '';
        $retval .= form_input($element['attributes']) . ' (dd-mm-jjjj)';
        $retval .= $this->_datepicker_javascript($element['attributes']['name']);
        
        $retval .= ' <span class="datepickerlink"><a href="#" onclick="document.getElementById(\'' . $element['attributes']['name'] . '\').value=\'00-00-0000\';return false;">Zet op 00-00-0000</a></span>';
        
        return $retval;
    }

    /**
     * A datetimepicker consists of two fields: a datepicker 'datepicker_FIELDNAME' 
     * and a timepicker 'timepicker_FIELDNAME'.
     * 
     * Their combined value is stored in a database in DATE or TIMESTAMP format.
     * To facilitate this, the combined POST value is merged before validation.
     * Return the HTML code for a datepicker
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_datetimepicker ($element)
    {
        $this->_metadata['use_datepicker_javascript'] = TRUE;
        $this->_metadata['use_timepicker_javascript'] = TRUE;
        
        $default_mysql_format = 'datetime';
        $id = $element['attributes']['name'];
        $format = isset($element['format']) ? $element['format'] : $default_mysql_format;
        $datepicker_format = 'd-m-Y H:i';
        $datepicker_name = 'datepicker_' . $id;
        $timepicker_name = 'timepicker_' . $id;
        $datepicker_value = '0000-00-00';
        $timepicker_value = '00:00';
        $retval = '';
        if (isset($element['validation']) && strstr($element['validation'], 'required')) {
            $element['validation'] .= '|required_date';
        }
        
        // Set value in the datetimepicker locale (d-m-Y H:i)
        if ($this->_is_posted == FALSE) {
            
            //Convert the initial value to d-m-Y H:i
            if ($format == 'datetime') {
                $conversion_function = 'filt_datetime_mysql_to_human';
            }
            elseif ($format == 'unix') {
                $conversion_function = 'filt_datetime_unix_to_human';
            }
            else {
                show_error('No conversion function is set for default MYSQL format \'' . $format . '\'');
            }
            $value = $conversion_function($element['value']);
        }
        else {
            $value = $this->_ci->input->post($id);
        }
        
        // Extract datepicker and timepicker values from $value
        $datepicker_value = substr($value, 0, 10);
        $timepicker_value = substr($value, 11);
        
        // Set up datepicker
        $element['attributes']['name'] = $datepicker_name;
        $element['attributes']['id'] = $datepicker_name;
        $element['attributes']['value'] = $datepicker_value;
        $retval .= form_input($element['attributes']);
        $retval .= $this->_datepicker_javascript($element['attributes']['name']);
        
        // Set up timepicker
        $element['attributes']['name'] = $timepicker_name;
        $element['attributes']['id'] = $timepicker_name;
        $element['attributes']['value'] = $timepicker_value;
        $element['attributes']['size'] = 5;
        
        $retval .= '&nbsp;' . form_input($element['attributes']);
        $retval .= "<script type=\"text/javascript\">$('#" . $timepicker_name . "').timeEntry({spinnerImage: ''});</script>";
        $retval .= ' (dd-mm-jjjj uu:mm)';
        $retval .= ' <span class="datepickerlink"><a href="#" onclick="document.getElementById(\'' . $datepicker_name . '\').value=\'00-00-0000\';document.getElementById(\'' . $timepicker_name . '\').value=\'00:00\';return false;">Zet op 00-00-0000</a></span>';
        
        return $retval;
    }

    /**
     * @author Joost van Veen
     * @param string $value
     * @param string $mysql_format
     * @param string $datepicker_format
     */
    private function _convert_mysql_to_datepicker ($value, $mysql_format = 'date', $datepicker_format = 'd-m-Y')
    {
        
        $retval = '';
        
        switch ($mysql_format) {
            case 'date':
            case 'datetime':
                $retval = date($datepicker_format, strtotime($value));
                break;
            case 'timestamp':
            case 'unix':
                $retval = date($datepicker_format, $value);
                break;
            default:
                show_error('This MYSQL date format is not registered for conversion');
                break;
        }
        
        return $retval;
    }

    /**
     * Return the HTML code for a captcha input and image
     * @author Joost van Veen
     * @param array $element
     * @return string
     */
    private function _form_captcha ($element)
    {
        
        // Create captcha string and image
        $max_length = isset($element['attributes']['max_length']) ? $element['attributes']['max_length'] : 5;
        if (isset($element['attributes']['class']) && ! strstr($element['attributes']['class'], 'captcha')) {
            $element['attributes']['class'] .= ' captcha';
        }
        else {
            $element['attributes']['class'] = 'captcha';
        }
        $retval = '';
        $captcha = $this->_set_captcha($max_length);
        $retval .= form_input($element['attributes']);
        $retval .= '&nbsp;';
        $retval .= $captcha['image'];
        
        // Store captcha string in session.
        $newdata = array(
            'captcha_word' => $captcha['word']);
        $this->_ci->session->set_userdata($newdata);
        
        return $retval;
    }

    /**
     * Return an array containing a captcha word and image, using the CI captcha helper
     * @author Joost van Veen
     * @param integer $num_chars - Number of characters
     * @return array
     */
    private function _set_captcha ($num_chars)
    {
        
        // Load the captcha helper if it hasn't been loaded already.
        if (! function_exists('create_captcha')) {
            $this->_ci->load->helper('captcha');
        }
        
        // Set captcha config and call helper function
        $data['word'] = strtoupper($this->_create_word($num_chars));
        $data['img_path'] = C_ROOT_PATH . 'files' . C_DS . 'gfx' . C_DS . 'captcha' . C_DS;
        $data['img_url'] = C_ROOT_URL . 'files/gfx/captcha/';
        $data['font_path'] = realpath(APPPATH . 'fonts' . C_DS . 'ariblk.ttf');
        $data['img_width'] = 150;
        $data['img_height'] = 30;
        $data['expiration'] = 7200;
        $captcha = create_captcha($data);
        
        return $captcha;
    }

    /**
     * Create a random captcha word
     * @author Joost van Veen
     * @param integer $str_length The maximum number of characters
     * @return string - The word
     */
    private function _create_word ($str_length = "8")
    {
        
        // Possible characters to use in captcha
        $var_possible = "123456789bcdfghjkmnpqrstvwxyz";
        
        $word = '';
        $i = 0;
        while (($i < $str_length) && (strlen($var_possible) > 0)) {
            // Not yet reached required length for password
            $i ++;
            // get rand character from possibles
            $var_character = substr($var_possible, mt_rand(0, strlen($var_possible) - 1), 1);
            // delete selected char from possible choices
            $var_possible = preg_replace("/$var_character/", "", $var_possible);
            $word .= $var_character;
        
        }
        return $word;
    }

    /**
     * Return javascript call for datepicker
     * @author Joost van Veen
     * @retrun string
     */
    private function _load_datepicker ()
    {
        $retval = '';
        $retval .= $this->indent . '<link rel="stylesheet" href="' . $this->css_folder . 'jquery-ui-1.8.1.custom.css" type="text/css" media="screen" />' . "\n";
        $retval .= $this->indent . '<script type="text/javascript" src="' . $this->javascript_folder . 'datepicker_config.js"></script>' . "\n";
        $retval .= $this->indent . '<script type="text/javascript" src="' . $this->javascript_folder . 'jquery-ui-1.8.1.custom.min.js"></script>' . "\n";
        return $retval;
    }

    /**
     * Return javascript call for timepicker
     * @author Joost van Veen
     * @retrun string
     */
    private function _load_timepicker ()
    {
        $retval = '';
        $retval .= $this->indent . '<script type="text/javascript" src="' . $this->javascript_folder . 'jquery.timeentry.pack.js"></script>' . "\n";
        $retval .= $this->indent . '<script type="text/javascript" src="' . $this->javascript_folder . 'jquery.timeentry-nl.js"></script>' . "\n";
        return $retval;
    }

    /**
     * Return javascript to attach a jquery datepicker to a certain #id
     * @author Joost van Veen
     * @param string $id
     * @param string $class
     * @return string
     */
    private function _datepicker_javascript ($id, $class = 'datepicker')
    {
        return "<script type=\"text/javascript\">
        $('#" . $id . "')." . $class . "({
            showOn: 'both', 
            changeMonth: true,
            changeYear: true, 
            showButtonPanel: true, 
            showOtherMonths: true,
            showWeek: true,
            buttonImage: '" . C_ROOT_URL . "files/gfx/system/calendar.gif', 
            buttonImageOnly: true
        });</script>";
    }

    /**
     * return javascript to display remaining characters for text area feedback.
     * @author Joost van Veen
     * @return String
     */
    private function _text_area_javascript ()
    {
        return "<script type=\"text/javascript\">function textCounter(field,cntfield,maxlimit) {if (field.value.length > maxlimit){field.value = field.value.substring(0, maxlimit);} else{ cntfield.value = maxlimit - field.value.length; } } function textCounterNoFeedback(field,maxlimit) {if (field.value.length > maxlimit){field.value = field.value.substring(0, maxlimit);} }</script>";
    }

    /**
     * Return a tinyMCE ini script
     * @author Joost van Veen
     * @param $source
     * @param $images
     */
    private function _set_ini_tiny_mce_javascript ($source = '', $images = '')
    {
        
        $path = C_ROOT_URL;
        $tiny_ini = '
    <!-- TinyMCE -->
    <script language="javascript" type="text/javascript" src="' . $this->javascript_folder . 'tiny_mce/jquery.tinymce.js"></script>
    <script type="text/javascript">
    $().ready(function() {
        $(\'textarea.tiny_mce\').tinymce({
            
            // Location of TinyMCE script
            script_url : "' . $this->javascript_folder . 'tiny_mce/tiny_mce.js",
            
            // Theme options
            theme : "advanced",
            plugins : "table,searchreplace,contextmenu,paste,advimage,fullscreen,media",
            language : "nl",
            editor_selector : "tiny_mce",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_buttons1 : "preview,separator,bold,italic,underline,hr,separator,bullist,numlist,outdent,indent,separator,undo,redo,separator,justifyleft,justifycenter,justifyright,charmap,code",
            theme_advanced_buttons2 : "removeformat,cut,copy,paste,pastetext,pasteword,selectall,separator,search,replace,separator,link,unlink,separator,media,separator,fullscreen",
            theme_advanced_buttons3 : "tablecontrols,formatselect",
            theme_advanced_statusbar_location : "bottom",
            theme_advanced_resizing : true,
            
            // Example content CSS
            // @TODO replace tiny mce content CSS with site CSS
            content_css : "' . $this->css_folder . 'tiny_mce.css",
            
            inline_styles : true,
            auto_cleanup_word : true,
            paste_use_dialog : false,
            paste_auto_cleanup_on_paste : true,
            paste_convert_headers_to_strong : false,
            extended_valid_elements : "iframe[src|class|width|height|name|align]",
            paste_strip_class_attributes : "all",
            force_br_newlines : false

        });
    });

    </script>
    <!-- /TinyMCE -->';
        
        return $tiny_ini;
    }
}