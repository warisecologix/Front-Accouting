<?php

namespace FAAPI;
include_once("../../admin/db/company_db.inc");
include_once("../../admin/db/maintenance_db.inc");

class Company
{

    function handle_submit()
    {

        global $db_connections, $def_coy, $tb_pref_counter, $db,
               $comp_subdirs, $path_to_root, $Mode;
        $_POST['host'] = 'localhost';
        $_POST['port'] = 3306;
        $_POST['dbuser'] = 'root';
        $_POST['dbpassword'] = '';
        $_POST['dbname'] = 'frontaccounting';
        $_POST['collation'] = 'utf8_xx';
        $_POST['tbpref'] = $tb_pref_counter;
        $_POST['coa'] = 'en_US-demo.sql';
        $_POST['selected_id'] = -1;

        $selected_id = -1;


        $error = false;

        if ($selected_id == -1) {
            $selected_id = count($db_connections);

        }

        $new = !isset($db_connections[$selected_id]);


        $db_connections[$selected_id]['name'] = $_POST['name'];


        if ($new) {

            $db_connections[$selected_id]['host'] = $_POST['host'];
            $db_connections[$selected_id]['port'] = $_POST['port'];
            $db_connections[$selected_id]['dbuser'] = $_POST['dbuser'];
            $db_connections[$selected_id]['dbpassword'] = html_entity_decode($_POST['dbpassword'], ENT_QUOTES,
                $_SESSION['language']->encoding == 'iso-8859-2' ? 'ISO-8859-1' : $_SESSION['language']->encoding);
            $db_connections[$selected_id]['dbname'] = $_POST['dbname'];
            $db_connections[$selected_id]['collation'] = $_POST['collation'];

            if (is_numeric($_POST['tbpref'])) {
                $db_connections[$selected_id]['tbpref'] = $_POST['tbpref'] == 1 ?
                    $tb_pref_counter . "_" : '';
            } else if ($_POST['tbpref'] != "") {

                $db_connections[$selected_id]['tbpref'] = $_POST['tbpref'];

            } else {
                $db_connections[$selected_id]['tbpref'] = "";
            }

            $db_connections[$selected_id]['tbpref'] = $tb_pref_counter;
            $conn = $db_connections[$selected_id];
            if (($db = db_create_db($conn)) === false) {
                api_error(412, 'Error creating Database ');
                $error = true;
            } else {
                if (strncmp(db_get_version(), "5.6", 3) >= 0) {
                    db_query("SET sql_mode = ''");
                }
                if (!db_import_api($path_to_root . '/sql/' . get_post('coa'), $conn, $selected_id)) {
                    api_error(412, 'Cannot create new company due to bugs in sql file.');
                    $error = true;
                } else {
                    if (!isset($_POST['admpassword']) || $_POST['admpassword'] == "") {
                        $_POST['admpassword'] = "password";
                        update_admin_password_api($conn, md5($_POST['admpassword']));
                    } else {
                        update_admin_password_api($conn, md5($_POST['admpassword']));
                    }
                }
            }
            if (isset($_POST['username']) || $_POST['username'] != "") {
                update_admin_user_id($conn, $_POST['username']);
            }
            if ($error) {
                remove_connection($selected_id);
                return false;
            }
        }

        $error = write_config_db_api($new);

        if ($error == -1) {
            api_error(412, 'Cannot open the configuration file');
        } else if ($error == -2) {
            api_error(412, 'Cannot write to the configuration file ');
        } else if ($error == -3) {
            api_error(412, 'The configuration file is not writable. Change its permissions so it is, then re-run the operation.');
        }
        if ($error != 0) {
            return false;
        }

        if ($new) {
            $comp_subdirs = array('images', 'pdf_files', 'backup', 'js_cache', 'reporting', 'attachments');
            create_comp_dirs(company_path($selected_id), $comp_subdirs);
            $exts = get_company_extensions();
            write_extensions($exts, $selected_id);
        }
        $company_id = $tb_pref_counter;
        $company_id = ($company_id - 1);
        $status = ['message' => 'New company has been created', 'prefix' => $company_id];


        api_success_response($status);
        $Mode = 'RESET';
        return true;
    }

    function company_update()
    {
        update_company_prefs(
            $this->get_post( array('coy_name','coy_no','gst_no','phone','curr_default'))
        );

    }
    function get_post($name, $dflt='')
    {

        if (is_array($name)) {
            $ret = array();
            foreach($name as $key => $dflt)
                if (!is_numeric($key)) {
                    $ret[$key] = is_numeric($dflt) ? input_num($key, $dflt) : get_post($key, $dflt);
                } else {
                    $ret[$dflt] = get_post($dflt, null);
                }
            return $ret;
        } else
            return is_float($dflt) ? input_num($name, $dflt) :
                ((!isset($_POST[$name]) /*|| $_POST[$name] === ''*/) ? $dflt : $_POST[$name]);
    }

}
