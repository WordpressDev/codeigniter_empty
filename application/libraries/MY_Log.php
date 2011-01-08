<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Status: final
 * 
 * @category Accent_Interactive
 * @version 1.0
 * @author Joost van Veen
 * @copyright Accent Interactive
 */
  
/**
 * This class extends the CI_Log class. It is used to alter some of the default
 * functions in this class.
 * @package Core
 * @subpackage Libraries
 */
class MY_Log extends CI_Log
{

    // We added a level 'NOTICE', to be able to log debug messages without the 
    // files being cluttered by all the CI Session Class Initialized messages.
    // IMPORTANT: as a consequence, we need to define the log array in config.php like so:
    /* |   0 = Disables logging, Error logging TURNED OFF
      * |   1 = Error Messages (including PHP errors)
      * |   2 = Notice - this is a custom type of logging threshold!!!
      * |   3 = Debug Messages
      * |   4 = Informational Messages
      * |   5 = All Messages - This is 4 in a default CI setup!!!
     */
    var $_levels = array('ERROR' => '1', 'NOTICE' => '2', 'DEBUG' => '3', 'INFO' => '4', 'ALL' => '5');

    function __construct ()
    {
        parent::__construct();
    }
}