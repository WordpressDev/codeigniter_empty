<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Status: final
 * 
 * General helpers, such as add_alert, get_country_array, get_index, etc.
 * 
 * @category Accent_Interactive
 * @package Core
 * @subpackage Helpers
 * @version 3.0
 * @author Joost van Veen
 * @copyright Accent Interactive
 */

/**
 * Log a message to Firebug using Firephp. Returns FALSE if logging did not succeed.
 * @param string $msg
 * @param string $type (optional, defaults to info)
 * @return boolean success (FALSE if loggin was disabled in config_item('firephp_enabled'))
 * @author Joost van Veen
 */
function firephp($msg, $type = 'info'){
    
    // Do nothing if firephp lib was not loaded.
    // We will for instance never load this in prodcution environment.
    $ci = & get_instance();
    if (!isset($ci->firephp)) {
        return FALSE;
    }
    
    if (method_exists($ci->firephp, $type)) {
        $ci->firephp->$type($msg);
        return TRUE;
    }
}

function upload_errors ($field, $config)
{
    $ci = & get_instance();
    $unknown = '<em>Onbekend</em>';
    $file = get_index($_FILES, $field, array('name' => $unknown, 
        'type' => $unknown, 
        'size' => $unknown));
    
    $allowed_types = get_index($config, 'allowed_types', $unknown);
    $extension = substr($file['name'], strrpos($file['name'], '.') + 1);
    
    $max_size = get_index($config, 'max_size', $unknown);
    $file_size = $file['size'] / 1024; // Filesize in KB
    

    $msg = '';
    $msg .= $ci->upload->display_errors();
    $msg .= stristr($file['type'], $extension) ? '' : '<p>U mag de volgende typen bestanden uploaden: ' . $allowed_types . '<br />Het bestand dat u probeert te uploaden is van het type ' . $extension . ' (' . $file['type'] . ')</p>';
    $msg .= $max_size > $file_size ? '' : '<p>U mag bestanden uploaden tot ' . filt_number($max_size, 0) . '&nbsp;KB.<br />Het bestand dat u probeert te uploaden is ' . filt_number($file_size, 0) . '&nbsp;KB groot.</p>';
    return $msg;
}

/**
 * Delete one or more files from file system. You can pass an array of names or 
 * a single name to param $names. Names can include a full path if you do 
 * not set param $path.
 * @param Mixed $names
 * @param String $path (optional)
 * @global
 * @return void
 * @author Joost van Veen
 */
function delete_file ($names, $path = '')
{
    // Make sure we have an array to loop through.
    if (! is_array($names)) {
        $names = array($names);
    }
    if (count($names) == 0) {
        return FALSE;
    }
    
    foreach ($names as $name) {
        
        // Abort if we have no file
        if ($name == '') {
            continue;
        }
        
        // Delete file
        $file = $path == '' ? $name : $path . $name;
        $ci = & get_instance();
        $msg = ' URI: ' . $ci->uri->uri_string();
        $msg .= ' REFERER: ' . $_SERVER["HTTP_REFERER"];
        $msg .= ' POST: ' . print_r($_POST, TRUE);
        $msg .= ' USER: ' . $ci->data['user']['use_email'];
        if (file_exists($file) && is_file($file)) {
            log_message('notice', 'File deleted: ' . $file . $msg);
            unlink($file);
        }
        else {
            log_message('notice', 'File NOT deleted: ' . $file . '. File did not match file_exists($file) && is_file($file).' . $msg);
            $return = FALSE;
        }
    }
}

/**
 * Return or echo a nicely formatted var_dump.
 * @param mixed $var The variable to dump
 * @param string $label (Optional) Label for the dump
 * @param boolean $echo Whether to echo to screen or return as string
 * @return string The HTML string
 */
function dump ($var, $label = 'Dump', $echo = TRUE)
{
    // Store dump in variable 
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    
    // Add formatting
    $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
    $output = '<pre style="background: #FFFEEF; color: #000; border: 1px dotted #000; padding: 10px; margin: 10px 0">' . $label . ' => ' . $output . '</pre>';
    
    // Output
    if ($echo == TRUE) {
        echo $output;
    }
    else {
        return $output;
    }
}


/**
 * Return the index for an array if it exists. If not, return a set value 
 * (defaults to empty string).
 * @param sarray $array_name
 * @param mixed $index
 * @param string $default
 * @global
 * @return mixed
 */
function get_index ($array_name, $index = 0, $default = '')
{
    return isset($array_name[$index]) ? $array_name[$index] : $default;
}


/**
 * Create a random string, including a limited set of characters, for readability.
 * @param integer $length - lentgh of the string to be returned (optional, default 6)
 * @param boolean $numerics - Whether the string should contain numerics(optional, default TRUE)
 * @param boolean $lowercase - Whether the string should contain lowercase chars(optional, default TRUE)
 * @param boolean $uppercase - Whether the string should contain uppercase chars(optional, default TRUE)
 * @param boolean $symbols - Whether the string should contain symbols(optional, default FALSE)
 * @author Joost van Veen
 * @global
 * @return String - Da word!
 */
function create_random_string ($length = 6, $numerics = TRUE, $lowercase = TRUE, $uppercase = TRUE, $symbols = FALSE)
{
    $string = "";
    
    // Create an array of characters to pick from.
    // A character will be picked from every node in this array, at least 
    // once (that is, if the string length permits this)
    $charSet = array();
    $numerics == FALSE || $charSet[] = "123456789";
    $lowercase == FALSE || $charSet[] = "abcdfghjkmnpqrstvwxyz";
    $uppercase == FALSE || $charSet[] = "ABCDFGHJKMNPQRSTVWXYZ";
    $symbols == FALSE || $charSet[] = "/`~!@#$%^&\*()_+-={}|:\";\'<>?,.";
    
    $iCharset = 0;
    for ($i = 0; $i < $length; $i ++) {
        if ($iCharset >= count($charSet)) {
            $iCharset = 0;
        }
        $character = substr($charSet[$iCharset], mt_rand(0, strlen($charSet[$iCharset]) - 1), 1);
        $string .= $character;
        $iCharset ++;
    }
    
    // Now shuffe string so the order is unpredictable
    $string = str_shuffle($string);
    
    return $string; // all done
}
