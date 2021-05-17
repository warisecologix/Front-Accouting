<?php
namespace FAAPI;

$path_to_root = "../..";

include_once($path_to_root . "/taxes/db/item_tax_types_db.inc");
include_once($path_to_root . "/taxes/db/tax_types_db.inc");

class TaxTypes
{
    // Get TaxTypes
    public function get($rest)
    {
        $req = $rest->request();

        $page = $req->get("page");

        if ($page == null) {
            $this->taxtypes_all();
        } else {
            // If page = 1 the value will be 0, if page = 2 the value will be 1, ...
            $from = -- $page * RESULTS_PER_PAGE;
            $this->taxtypes_all($from);
        }
    }

    // Get TaxType by Id
    public function getById($rest, $id)
    {
        $taxType = get_tax_type($id);
        if (!$taxType) {
            $taxType = array();
        }
        api_success_response(json_encode(\api_ensureAssociativeArray($taxType)));
    }
    
    public function post($rest)
    {
        $name = $_POST['name'];
        $sales_gl_code = $_POST['sales_gl_code'];
        $purchasing_gl_code = $_POST['purchasing_gl_code'];
        $rate = $_POST['rate'];
        $sql = "INSERT INTO ".TB_PREF."tax_types (name, sales_gl_code, purchasing_gl_code, rate)
            VALUES (".db_escape($name).", ".db_escape($sales_gl_code)
            .", ".db_escape($purchasing_gl_code).", $rate)";
        db_query($sql, "could not add tax type");
        api_success_response(_('New tax type has been added'));

    }

    private function taxtypes_all($from = null)
    {
        if ($from == null) {
            $from = 0;
        }

        $sql = "SELECT * FROM " . TB_PREF . "tax_types LIMIT " . $from . ", " . RESULTS_PER_PAGE;

        $query = db_query($sql, "error");

        $info = array();

        while ($data = db_fetch($query, "error")) {
            $info[] = array(
                'id' => $data['id'],
                'name' => $data['name'],
                'percentage' => $data['rate']
            );
        }

        api_success_response(json_encode($info));
    }
}
