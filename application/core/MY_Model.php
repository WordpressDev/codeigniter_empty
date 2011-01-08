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
 * Base model to provide CRUD functionality. Just extend new models from MY_Model.
 * @package Core
 * @subpackage Libraries
 */
class MY_Model extends CI_Model
{

    /**
     * The database table to use.
     * @var string
     */
    public $table_name;

    /**
     * Primary key field
     * @var string
     */
    public $primary_key = '';

    /**
     * Order by fields. Default order for this model.
     * @var string
     */
    public $order_by = '';

    /**
     * The class constructer, also tries to guess
     * the table name.
     *
     * @author Joost van Veen
     */
    public function MY_Model ()
    {
        parent::__construct();
        log_message('debug', "MY_Model Class Initialized");
    }

    protected function _set_default_sql ()
    {}

    /**
     * Return the number of records for a certain query
     * @return integer
     * @author Joost van Veen
     */
    public function count ()
    {
        $this->db->from($this->table_name);
        return $this->db->count_all_results();
    }

    /**
     * Get one record, based on ID, or get all records. You can pass a single 
     * ID, an array of IDs, or no ID (in which case the this method will return 
     * all records)
     * 
     * By deafult, this method will return an aray of records.
     * By passing $single as TRUE, it will return an associative array with the 
     * values for a single record.
     * 
     * @param integer $id An ID or an array of IDs (optional, default = 0)
     * @param boolean $single Whether to return an assoc array holding the values for a single record, or an array of records (optional, default = FALSE)
     * @return array
     * @author Joost van Veen
     */
    public function get ($id = 0, $single = FALSE)
    {
        $this->_set_default_sql();
        $single_id_passed = ((int) $id > 0 && ! is_array($id)) ? TRUE : FALSE;
        
        // If a value was passed for $id, set where parameters.
        if ($id != 0 || is_string($id)) {
            
            // $id needs to be an array, so we can loop through ids
            is_array($id) || $id = array(
                $id);
            foreach ($id as $key) {
                $key = intval($key);
                if ($key == 0) {
                    return array();
                }
                if ($single_id_passed == TRUE) {
                    $this->db->where($this->primary_key, $key);
                }
                else {
                    $this->db->or_where($this->primary_key, $key);
                }
            }
        }
        
        $single == FALSE || $this->db->limit(1);
        $method = $single == TRUE ? 'row_array' : 'result_array';
        return $this->db->get($this->table_name)->$method();
    }

    /**
     * Return records as an associative array, where the key is teh value of the 
     * first key for that record. Typical return array:
     * $return[18] = array(18 => array('id' => 18,
     * 'title' => 'Example record'),
     * 23 => array('id' => 23,
     * 'title' => 'Example record 2');
     * 
     * @param integer $id An ID or an array of IDs (optional, default = 0)
     * @param boolean $single Whether to return an assoc array holding the values for a single record, or an array of records (optional, default = FALSE)
     * @return array
     * @author Joost van Veen
     */
    public function get_assoc ($id = 0, $single = FALSE)
    {
        $result = $this->get($id, $single);
        
        $ret_array = array();
        if (count($result) > 0) {
            if ($single == FALSE) {
                $ret_array = $this->to_assocc($result);
            }
            else {
                reset($result);
                $ret_array[$result[key($result)]] = $result;
            }
        }
        
        unset($result);
        return $ret_array;
    }

    /**
     * Get one or more records by one or more fields, not being the primary key.
     *
     * @param string $key The field
     * @param string $val The value
     * @param boolean $orwhere whether or not we should use or_where
     * @return array
     * @author Joost van Veen
     */
    public function get_by ($key, $val = null, $orwhere = FALSE, $single = FALSE)
    {
        $this->_set_default_sql();
        
        if (! is_array($key)) {
            $this->db->where($key, $val);
        }
        else {
            if ($orwhere == TRUE) {
                $this->db->or_where($key);
            }
            else {
                $this->db->where($key);
            }
        }
        
        $single == FALSE || $this->db->limit(1);
        $method = $single == TRUE ? 'row_array' : 'result_array';
        return $this->db->get($this->table_name)->$method();
    }

    /**
     * Get one or more records as a key=>value pair array.
     *
     * @param string $key_field The field that holds the key
     * @param string $value_field The field that holds the value
     * @return array
     * @author Joost van Veen
     */
    public function get_key_value ($key_field, $value_field)
    {
        $ret_array = array();
        
        $this->_set_default_sql();
        $this->db->select($key_field . ', ' . $value_field);
        $result = $this->db->get($this->table_name)->result_array();
        
        if (count($result) > 0) {
            foreach ($result as $row) {
                $ret_array[$row[$key_field]] = $row[$value_field];
            }
        }
        
        return $ret_array;
    }

    /**
     * Save a record to the database. Function magically determines whether to 
     * insert or update. Returns (insert) ID.
     * @param $data
     * @param $id
     * @return integer $id
     * @author Joost van Veen
     */
    public function save ($data, $id = 0)
    {
        if ($id > 0) {
            // It's an update
            $this->db->set($data)->where($this->primary_key, $id)->update($this->table_name);
        }
        else {
            // It's an insert
            ! isset($data[$this->primary_key]) || $data[$this->primary_key] = NULL;
            $this->db->set($data);
            $this->db->insert($this->table_name);
        }
        
        return $id > 0 ? $id : $this->db->insert_id();
    }

    /**
     * Same as save, but with many records. Save records to the database. Function
     * magically determines whether to insert or update. Returns an array of 
     * (insert) IDs.
     * @param array $data
     * @param array $ids
     * @return array
     * @author Joost van Veen
     */
    public function save_many ($data, $ids = array())
    {
        
        if (count($ids) > 0) {
            // many updates
            foreach ($id as $key => $id) {
                if (! isset($data[$key])) {
                    log_message('error', 'Could not update ID ' . $id . ' in table ' . $this->table_name . '; No data given');
                    return FALSE;
                }
                else {
                    $this->db->set($data[$key])->where($this->primary_key, $id)->update($this->table_name);
                }
            }
        }
        else {
            // Many inserts
            foreach ($data as $data_row) {
                ! isset($data_row[$this->primary_key]) || $data_row[$this->primary_key] = NULL;
                $this->db->insert($this->table_name, $data_row);
                $ids[] = $this->db->insert_id();
            }
        }
        
        return $ids;
    }

    /**
     * Delete a row from the database table by the ID.
     * If ID is an array of ID's, all records containing those ID's are deleted
     * 
     * @param mixed $id_array Can be an array, or a single integer
     * @return bool
     * @author Joost van Veen
     */
    public function delete ($id_array)
    {
        // Make sure ID is an array so we can loop delete actions
        $id_array = ! is_array($id_array) ? array(
            $id_array) : $id_array;
        
        foreach ($id_array as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $this->db->where($this->primary_key, $id)->limit(1)->delete($this->table_name);
                log_message('notice', $this->db->affected_rows() . ' records deleted: ' . $this->db->last_query() . ' file ' . __FILE__ . ' line ' . __LINE__);
            }
        }
    }

    /**
     * Delete a row from the database table by the
     * key and value.
     *
     * @param string $key
     * @param string $value 
     * @return bool
     * @author Jamie Rumbelow
     */
    public function delete_by ($key, $val)
    {
        return $val != '' ? $this->db->where($key, $val)->limit(1)->delete($this->table_name) : FALSE;
        log_message('notice', $this->db->affected_rows() . ' record deleted: ' . $this->db->last_query() . ' file ' . __FILE__ . ' line ' . __LINE__);
    }

    /**
     * Delete many rows from the database table by 
     * an array of IDs passed.
     *
     * @param array $ids 
     * @return bool
     * @author Jamie Rumbelow
     */
    
    public function delete_many ($ids)
    {
        foreach ($ids as $id) {
            if ($id) {
                $this->db->where($this->primary_key, $id);
            }
        }
        
        $this->db->delete($this->table_name);
        log_message('notice', $this->db->affected_rows() . ' record deleted: ' . $this->db->last_query() . ' file ' . __FILE__ . ' line ' . __LINE__);
        return;
    }

    /**
     * Delete many rows from the database table by 
     * an array of keys and values.
     *
     * @param array $array
     * @return bool
     * @author Jamie Rumbelow
     */
    public function delete_many_by ($array)
    {
        if (! is_array($array) || count($array) == 0) {
            return FALSE;
        }
        
        foreach ($array as $key => $value) {
            $this->db->where($key, $value);
        }
        
        $this->db->delete($this->table_name);
        log_message('notice', $this->db->affected_rows() . ' records deleted: ' . $this->db->last_query() . ' file ' . __FILE__ . ' line ' . __LINE__);
        return;
    }

    /**
     * Turn a multidimensional array into an associative array, where the index 
     * equals the value of the first index. 
     * 
     * Example output:
     * array(0 => array('pag_id' => 23, 'pag_title' => 'foo'))
     * becomes
     * array(23 => array('pag_id' => 23, 'pag_title' => 'foo'))
     * @param $array
     * @return array
     * @author Joost van Veen
     */
    public function to_assocc ($array)
    {
        $ret_array = array();
        if (count($array) > 0) {
            foreach ($array as $row) {
                reset($row);
                $ret_array[$row[key($row)]] = $row;
            }
        }
        return $ret_array;
    }
}