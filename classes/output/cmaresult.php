<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CMA module
 *
 * @package mod_cma
 * @copyright  2017 onwards Alain Bolli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cma\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/cma/locallib.php');

class cmaresult implements \renderable, \templatable {



    public function __construct($result) {
        $this->result = $result;
    }

    public function export_for_template(\renderer_base $output) {
        global $data, $PAGE;
        // Get fields for thead, we don't want planname and scaleid.
        if (!isset($data)) {
            $data = new StdClass();
        }
        $data = $this->result;
        $data->typekey = $data->type; // L'identifiant dans get_string pour le type de profil.
        $data->type = get_string($data->type, 'mod_cma');
        $temp[0] = new StdClass();
        $temp[1] = new StdClass();
        $temp[2] = new StdClass();
        $temp[3] = new StdClass();
        $temp[0]->chartdata = $this->create_chart('all');
        $temp[0]->uniqid = uniqid();
        $temp[1]->chartdata = $this->create_chart('course');
        $temp[1]->uniqid = uniqid();
        $temp[2]->chartdata = $this->create_chart_types('all');
        $temp[2]->uniqid = uniqid();
        $temp[3]->chartdata = $this->create_chart_types('course');
        $temp[3]->uniqid = uniqid();
        $data->charts = $temp;
        $data->withtable = false;
        $testurl = new \moodle_url('/mod/cma/view.php', array('id' => $PAGE->cm->id, 'test' => '1'));
        $data->testurl = $testurl;
        return $data;
    }

    public function create_chart($who) {
        global $OUTPUT;
        $chart = new \core\chart_bar();
        if ($who == "all") {
            $serietotal = $this->get_average();
            $title = get_string('titleallaverage', 'mod_cma');
        } else {
            $serietotal = $this->get_course_average();
            $title = get_string('titlecourseaverage', 'mod_cma');
        }
        $me = get_string('me', 'mod_cma');
        $serieme = new \core\chart_series($me, [$this->result->ec, $this->result->obr, $this->result->ca, $this->result->ea]);
        $ec = get_string('ec', 'mod_cma');
        $or = get_string('or', 'mod_cma');
        $ca = get_string('ca', 'mod_cma');
        $ea = get_string('ea', 'mod_cma');
        $labels = array($ec, $or, $ca, $ea);
        $serieme->set_type(\core\chart_series::TYPE_LINE);
        $chart->add_series($serieme);
        $chart->add_series($serietotal);
        $chart->set_labels($labels);
        $chart->set_title($title);
        $chartdata = json_encode($chart);
        return $chartdata;
    }

    protected function get_average() {
        global $DB;
        $ec = $DB->get_record_sql('SELECT AVG(ec) AS ec FROM {cma_points}');
        $obr = $DB->get_record_sql('SELECT AVG(obr) AS obr FROM {cma_points}');
        $ca = $DB->get_record_sql('SELECT AVG(ca) AS ca FROM {cma_points}');
        $ea = $DB->get_record_sql('SELECT AVG(ea) AS ea FROM {cma_points}');
        $all = get_string('all', 'mod_cma');
        $serietotal = new \core\chart_series($all, [$ec->ec, $obr->obr, $ca->ca, $ea->ea]);
        return $serietotal;
    }

    protected function get_course_average() {
            global $DB;
            $users = cma_get_users();
            $usersid = cma_get_users_id($users);
            $usersid = join(",", $usersid);
            $sqlec = "SELECT AVG(ec) AS ec FROM {cma_points} WHERE userid IN (".$usersid.")";
            $sqlobr = "SELECT AVG(obr) AS obr FROM {cma_points} WHERE userid IN (".$usersid.")";
            $sqlca = "SELECT AVG(ca) AS ca FROM {cma_points} WHERE userid IN (".$usersid.")";
            $sqlea = "SELECT AVG(ea) AS ea FROM {cma_points} WHERE userid IN (".$usersid.")";
            $ec = $DB->get_record_sql($sqlec);
            $obr = $DB->get_record_sql($sqlobr);
            $ca = $DB->get_record_sql($sqlca);
            $ea = $DB->get_record_sql($sqlea);
            $coursetitle = get_string('coursetitle', 'mod_cma');
            $serietotal = new \core\chart_series($coursetitle, [$ec->ec, $obr->obr, $ca->ca, $ea->ea]);
            return $serietotal;
    }

    protected function create_chart_types($who) {
        global $OUTPUT;
        $chart = new \core\chart_bar();
        $serietotal = $this->get_types($who);
        if ($who == 'all') {
            $title = get_string('titlealltypes', 'mod_cma');
        } else {
            $title = get_string('titlecoursetypes', 'mod_cma');
        }

        $ass = get_string('assimilateur', 'mod_cma');
        $acc = get_string('accomodateur', 'mod_cma');
        $con = get_string('convergent', 'mod_cma');
        $div = get_string('divergent', 'mod_cma');
        $labels = array($acc, $ass, $con, $div);
        $chart->add_series($serietotal);
        $chart->set_labels($labels);
        $chart->set_title($title);
        $chartdata = json_encode($chart);
        return $chartdata;
    }

    protected function get_types($who) {
        global $DB;
        if ($who == 'all') {
            $users = cma_get_users();
            $usersid = cma_get_users_id($users);
            $usersid = join(",", $usersid);
            $sql = "SELECT type, COUNT(*) AS nbtype FROM {cma_points} WHERE userid IN (".$usersid.") GROUP BY type";
            $title = get_string('all', 'mod_cma');
        } else {
            $sql = "SELECT type, COUNT(*) AS nbtype FROM {cma_points} GROUP BY type";
            $title = get_string('coursetitle', 'mod_cma');
        }
        $result = $DB->get_records_sql($sql);
        $nbtype = new StdClass();
        $nbtype->assimilateur = 0;
        $nbtype->accomodateur = 0;
        $nbtype->convergent = 0;
        $nbtype->divergent = 0;

        foreach ($result as $key => $value) {
                $nbtype->$key = $value->nbtype;
        }
        $serietotal = new \core\chart_series($title, [$nbtype->accomodateur, $nbtype->assimilateur, $nbtype->convergent, $nbtype->divergent]);
        return $serietotal;
    }
}