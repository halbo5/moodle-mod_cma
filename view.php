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
 * @copyright  2017 Alain Bolli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_once($CFG->dirroot.'/mod/cma/lib.php');
require_once($CFG->dirroot.'/mod/cma/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/mod/cma/classes/output/cmatest.php');
require_once($CFG->libdir.'/weblib.php');


$id      = optional_param('id', 0, PARAM_INT); // Course Module ID.
$c       = optional_param('c', 0, PARAM_INT);  // CMA instance ID.
$t        = optional_param('test', 0, PARAM_INT); // Faut-il rejouer le test ?

if ($c) {
    if (!$cma = $DB->get_record('cma', array('id' => $c))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('cma', $cma->id, $cma->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('cma', $id)) {
        print_error('invalidcoursemodule');
    }
    $cma = $DB->get_record('cma', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/cma:view', $context);

// If we have data to save.
if ($data = data_submitted()) {
    $dataobject = new StdClass();
    $ls = new StdClass();
    $dataobject->cmaid = $id;
    $dataobject->userid = $USER->id;
    $i = 1;
    foreach ($data as $key => $value) {
            $word = 'word'.$i;
            $dataobject->$word = $value;
            $i++;
    }
    $ls = cma_calc_ls($data);
    $dataobject->ec = $ls->ec;
    $dataobject->ca = $ls->ca;
    $dataobject->ea = $ls->ea;
    $dataobject->obr = $ls->or;
    $dataobject->type = $ls->type;
    $DB->insert_record('cma_points', $dataobject, $returnid = true);
    cma_played($cma, $course, $cm, $context);
}

// Check if there are results to display.
$conditions = array('userid' => $USER->id);
$sort = 'id desc';
$fields = 'id, ec, ca, ea, obr, type';
if ($t != 1) {
    if ($result = $DB->get_records('cma_points', $conditions, $sort, $fields)) {
        $key = key($result);
        $cmaresult = new \mod_cma\output\cmaresult($result[$key]);
    } else {
        $cmatest = new \mod_cma\output\cmatest();
        $t = 1;
    }
} else {
    $cmatest = new \mod_cma\output\cmatest();
}

// Completion and trigger events.
cma_view($cma, $course, $cm, $context);

$PAGE->set_url('/mod/cma/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$cma->name);
$PAGE->set_heading($course->fullname);
$PAGE->requires->js_call_amd('mod_cma/apprentissage', 'init');

$output = $PAGE->get_renderer('mod_cma');

echo $output->header();
if ($t == 1) {
    echo $output->render_cma_test($cmatest);
} else {
    echo $output->render_cma_result($cmaresult);
}

echo $output->footer();