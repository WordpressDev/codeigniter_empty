<?php
define('C_UNIT_TEST', TRUE);
define('C_RAND', rand(500, 15000));
define('C_SHOW_SQL', FALSE);

ob_start();
require_once 'index.php';
ob_end_clean();

// Decide whether to show tests or not, depending on environment
if (C_ENVIRONMENT != 'development') {
    header("HTTP/1.0 404 Not Found");
    exit();
}

/**
 * Please note this file shouldn't be exposed on a live server,
 * there is no filtering of $_POST!!!!
 */
error_reporting(0);

/**
 * Configure your paths here:
 */
$test_suite = 'CodeIgniter Test Suite';
define('MAIN_PATH', realpath(dirname(__FILE__)) . '/');
define('SIMPLETEST', '../tests/simpletest/'); // Directory of simpletest
define('ROOT', MAIN_PATH); // Directory of codeigniter index.php
define('TESTS_DIR', '../tests/'); // Directory of your tests.
define('APP_DIR', '../application/'); // CodeIgniter Application directory
define('ENV', 'local');

if (! empty($_POST) or ! empty($_GET)) {
    //autorun will load failed test if no tests are present to run
    require_once SIMPLETEST . 'autorun.php';
    require_once SIMPLETEST . 'web_tester.php';
    require_once SIMPLETEST . 'mock_objects.php';
    require_once SIMPLETEST . 'extensions/my_reporter.php';
    $test = new TestSuite();
    $test->_label = $test_suite;
    
    class CodeIgniterUnitTestCase extends UnitTestCase
    {

        protected $ci;

        public function __construct ()
        {
            parent::UnitTestCase();
            $this->_ci = CI_Base::get_instance();
        }

        public function last_query ()
        {
            C_SHOW_SQL == FALSE || print('<pre>' . dump($this->_ci->db->last_query(), 'last_query()', FALSE) . '</pre>');
        }
    }
    
    class CodeIgniterWebTestCase extends WebTestCase
    {

        protected $_ci;

        public function __construct ()
        {
            parent::WebTestCase();
            $this->_ci = CI_Base::get_instance();
        }
    }
}

// Because get is removed in ci we pull it out here.
$run_all = FALSE;
if (isset($_GET['all'])) {
    $run_all = TRUE;
}

function add_test ($dir, $file, &$test)
{
    $implementation = '';
    if (file_exists(TESTS_DIR . $dir . '/' . $file)) {
        $test->addTestFile(TESTS_DIR . $dir . '/' . $file);
    }
}

//Capture CodeIgniter output, discard and load system into $CI variable
ob_start();
include (ROOT . 'index.php');
$CI = & get_instance();
ob_end_clean();

// This checks to see if setup needs to be ran.
if (! defined('ENV')) {
    redirect('home');
}
$CI->load->library('session');
$CI->load->helper('url');
$CI->load->helper('directory');
$CI->session->sess_destroy();

$url = base_url();

// Get all main tests
function read_dir ($dir)
{
    $dirs = array();
    foreach (directory_map($dir) as $dir) {
        $dirs[] = $dir;
    }
    return $dirs;
}

$controllers = read_dir(TESTS_DIR . 'controllers');
$models = read_dir(TESTS_DIR . 'models');
$views = read_dir(TESTS_DIR . 'views');
$libraries = read_dir(TESTS_DIR . 'libraries');
$bugs = read_dir(TESTS_DIR . 'bugs');
$helpers = read_dir(TESTS_DIR . 'helpers');

if ($run_all or (! empty($_POST) && ! isset($_POST['test']))) {
    $run_tests = TRUE;
    
    if (isset($_POST['controllers']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'controllers';
    }
    if (isset($_POST['models']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'models';
    }
    if (isset($_POST['views']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'views';
    }
    if (isset($_POST['libraries']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'libraries';
    }
    if (isset($_POST['bugs']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'bugs';
    }
    if (isset($_POST['helpers']) or isset($_POST['all']) or $run_all) {
        $dirs[] = TESTS_DIR . 'helpers';
    }
    
    if (! empty($dirs)) {
        foreach ($dirs as $dir) {
            $dir_files = read_dir($dir);
            
            foreach ($dir_files as $file) {
                if (false !== strpos($file, '_controller')) {
                    if (file_exists(TESTS_DIR . 'controllers/' . $file)) {
                        add_test('controllers', $file, $test);
                    }
                }
                elseif (false !== strpos($file, '_model')) {
                    if (file_exists(TESTS_DIR . 'models/' . $file)) {
                        add_test('models', $file, $test);
                    }
                }
                elseif (false !== strpos($file, '_view')) {
                    if (file_exists(TESTS_DIR . 'views/' . $file)) {
                        add_test('views', $file, $test);
                    }
                }
                elseif (false !== strpos($file, '_library')) {
                    if (file_exists(TESTS_DIR . 'libraries/' . $file)) {
                        add_test('libraries', $file, $test);
                    }
                }
                elseif (false !== strpos($file, '_bug')) {
                    if (file_exists(TESTS_DIR . 'bugs/' . $file)) {
                        add_test('bugs', $file, $test);
                    }
                }
                elseif (false !== strpos($file, '_helper')) {
                    
                    if (file_exists(TESTS_DIR . 'helpers/' . $file)) {
                        add_test('helpers', $file, $test);
                    }
                }
            }
        }
    }
}
elseif (isset($_POST['test'])) //single test
{
    $file = $_POST['test'];
    
    //autorun will load failed test if no tests are present to run
    require_once SIMPLETEST . 'autorun.php';
    require_once SIMPLETEST . 'web_tester.php';
    require_once SIMPLETEST . 'mock_objects.php';
    require_once SIMPLETEST . 'extensions/my_reporter.php';
    $test = new TestSuite();
    $test->_label = $test_suite;
    
    if (file_exists(TESTS_DIR . $file)) {
        $run_tests = TRUE;
        $test->addTestFile(TESTS_DIR . $file);
    }
}

$form_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

//display the form
include (TESTS_DIR . 'test_gui.php');