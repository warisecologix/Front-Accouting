<?php

namespace FAAPI;
include_once("../../admin/db/company_db.inc");
include_once("../../admin/db/maintenance_db.inc");

class Company
{

    function handle_submit()
    {

        $selected_id = -1;
        global $db_connections, $def_coy, $tb_pref_counter, $db,
               $comp_subdirs, $path_to_root, $Mode;

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

            $db_connections[$selected_id]['tbpref'] = $_POST['tbpref'];
            $conn = $db_connections[$selected_id];
            if (($db = db_create_db($conn)) === false) {
                api_error(412, 'Error creating Database ');
                $error = true;
            } else {
                if (strncmp(db_get_version(), "5.6", 3) >= 0){
                    db_query("SET sql_mode = ''");
                }
                if (!db_import_api($path_to_root . '/sql/' . get_post('coa'), $conn, $selected_id)) {
                    api_error(412, 'Cannot create new company due to bugs in sql file.');
                    $error = true;
                } else {
                    if (!isset($_POST['admpassword']) || $_POST['admpassword'] == ""){
                        $_POST['admpassword'] = "password";
                        update_admin_password($conn, md5($_POST['admpassword']));
                    }
                }
            }
            if ($error) {
                remove_connection($selected_id);
                return false;
            }
        }
        $error = write_config_db($new);

        if ($error == -1)
        {
            api_error(412, 'Cannot open the configuration file');
        }
        else if ($error == -2){
            api_error(412, 'Cannot write to the configuration file ');
        }
        else if ($error == -3){
            api_error(412, 'The configuration file is not writable. Change its permissions so it is, then re-run the operation.');
        }
        if ($error != 0) {
            return false;
        }

        if ($new) {
            create_comp_dirs(company_path($selected_id), $comp_subdirs);
            $exts = get_company_extensions();
            write_extensions($exts, $selected_id);
        }
        api_success_response("New company has been created.");
        $Mode = 'RESET';
        return true;
    }

}
