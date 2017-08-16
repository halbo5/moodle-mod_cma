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

defined('MOODLE_INTERNAL') || die;


/**
 * Calc scores and profile type.
 * @return : object $ls with 4 scores and 1 profile type.
 * @param : object $data points for each word.
 */

function cma_calc_ls($data) {
    $ls = new StdClass();
    $ls->ec = $data->word5 + $data->word9 + $data->word13 + $data->word17 + $data->word25 + $data->word29;
    $ls->ca = $data->word7 + $data->word11 + $data->word15 + $data->word19 + $data->word31 + $data->word35;
    $ls->ea = $data->word4 + $data->word12 + $data->word24 + $data->word28 + $data->word32 + $data->word36;
    $ls->or = $data->word2 + $data->word10 + $data->word22 + $data->word26 + $data->word30 + $data->word34;

    $camoinsec = $ls->ca - $ls->ec;
    $eamoinsor = $ls->ea - $ls->or;

    if ($camoinsec >= 3 && $eamoinsor >= 3) {
        $ls->type = "convergent";
    }
    if ($camoinsec >= 3 && $eamoinsor <= 2) {
        $ls->type = "assimilateur";
    }
    if ($camoinsec <= 3 && $eamoinsor <= 2) {
        $ls->type = "divergent";
    }
    if ($camoinsec <= 3 && $eamoinsor >= 3) {
        $ls->type = "accomodateur";
    }
    return $ls;
}

/**
 * Get users with student role in this course
 * @return : object $users
 */
function cma_get_users() {
    global $PAGE, $DB;
    $courseid = $PAGE->course->id;
    $role = $DB->get_record('role', array('shortname' => 'student'));
    $context = context_course::instance($courseid);
    $users = get_role_users($role->id, $context, false, '', '', false);
    return $users;
}

/**
 * Get users id (student enrolled in this course)
 * @return : object $usersid
 * @param : object $users users data
 */
function cma_get_users_id($users) {
    foreach ($users as $user) {
        $usersid[] = $user->id;
    }
    return $usersid;
}