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

class cmatest implements \renderable, \templatable {

    public function export_for_template(\renderer_base $output) {
        global $data, $PAGE;
        // Get fields for thead, we don't want planname and scaleid.
        if (!isset($data)) {
            $data = new StdClass();
        }
        $words = $this->get_words();
        for ($p = 0; $p <= 8; $p++) {
            $i = $p + 1;
            $data->pages[$p] = array('pagenum' => $i,
                                                        'pagesuiv' => $i + 1,
                                                        'mot1' => $words[$i * 4 - 3],
                                                        'mot2' => $words[$i * 4 - 2],
                                                        'mot3' => $words[$i * 4 - 1],
                                                        'mot4' => $words[$i * 4],
                                                        'idmot1' => $i * 4 - 3,
                                                        'idmot2' => $i * 4 - 2,
                                                        'idmot3' => $i * 4 - 1,
                                                        'idmot4' => $i * 4);
        }
        $actionurl = new \moodle_url('/mod/cma/view.php', array('id' => $PAGE->cm->id));
        $data->actionurl = $actionurl;
        $data->cmid = $PAGE->cm->id;
        $resulturl = new \moodle_url('/mod/cma/view.php', array('id' => $PAGE->cm->id, 'test' => '0'));
        $data->resulturl = $resulturl;
        return $data;
    }

    protected function get_words() {
        for ($i = 1; $i <= 36; $i++) {
            $words[$i] = get_string('word'.$i, 'mod_cma');
        }
        return $words;
    }
}