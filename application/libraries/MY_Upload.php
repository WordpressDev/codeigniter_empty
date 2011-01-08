<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
