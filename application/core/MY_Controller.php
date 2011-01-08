<?php
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
 * @version 2.0
 * @author Joost van Veen
 * @copyright Accent Interactive
 */

/**
 * Global controller, intended for extension by all other controllers. Contains cache settings, etc.
 * @package Core
 * @subpackage Libraries
 */
class MY_Controller extends Controller
{

    /**
     * The data to pass to the view.
     * @var array
     */
    public $data = array();

    /**
     * An object that holds Zend_Cache.
     * We use Zend_Cache because it is much more powerful than CI_Cache.
     * @var object
     */
    public $cache;

    /**
     * Constructor
     */
    public function MY_Controller ()
    {
        parent::__construct();
        
        // Set default header
        header('Content-type: text/html; charset=UTF-8');
        
        // Load firephp if enabled
        config_item('firephp_enabled') == FALSE || $this->load->library('firephp');
        $this->_init_profiler();
        
        // Show profiler in development environment, but not in ajax calls
        C_ENVIRONMENT != 'development' || $this->output->enable_profiler(TRUE);
        $this->is_ajax() == FALSE || $this->output->enable_profiler(FALSE);
        
        log_message('debug', "MY_Controller Initialized");
    }

    /**
     * A helper method to check if a request has been
     * made through XMLHttpRequest (AJAX) or not 
     * Thanks to Jamie Rumbelow :)
     *
     * @return bool
     * @author Jamie Rumbelow
     */
    protected function is_ajax ()
    {
        return (get_index($_SERVER, 'HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') ? TRUE : FALSE;
    }
}
