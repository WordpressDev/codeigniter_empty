<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Status: final
 * 
 * LICENSE AND COPYRIGHT
 * 
 * @category Accent_Interactive
 * @version 1.2
 * @author Joost van Veen
 * @copyright Accent Interactive
 */
  
/**
 * Override some of CI_Upload methods
 * @package Core
 * @subpackage Libraries
 */
class MY_Upload extends CI_Upload
{

    /**
	 * Rewrite the filetype check function. Newer browser versions dump in new
	 * kinds of mime types and FF3 wraps mime types in \".
	 *
	 * Verify that the filetype is allowed
	 *
	 * @access	public
	 * @return	bool
	 */
    function is_allowed_filetype()
    {
        // This is the custom part of the function.
        $remove = array("\\", '"');
        foreach ($remove as $needle) {
            $this->file_type = str_replace($needle, '', $this->file_type);
        }

        // This is the original function.
        if (count($this->allowed_types) == 0 || ! is_array($this->allowed_types))
        {
            $this->set_error('upload_no_file_types');
            return FALSE;
        }

        foreach ($this->allowed_types as $val)
        {
            $mime = $this->mimes_types(strtolower($val));

            if (is_array($mime))
            {
                if (in_array($this->file_type, $mime, TRUE))
                {
                    return TRUE;
                }
            }
            else
            {
                if ($mime == $this->file_type)
                {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

}
