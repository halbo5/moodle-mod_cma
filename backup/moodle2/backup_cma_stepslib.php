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
 * @package    mod_cma
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_cma_activity_task
 */

/**
 * Define the complete cma structure for backup.
 */
class backup_cma_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $cma = new backup_nested_element('cma', array('id'), array(
            'name', 'intro', 'introformat', 'previous',
            'timeopen', 'timeclose', 'activity', 'completion', 'activitycon',
            'activitydiv', 'activityass', 'activityacc', 'timecreated', 'timemodified'));

        $points = new backup_nested_element('points');

        $point = new backup_nested_element('point', array('id'), array(
            'userid', 'word1', 'word2', 'word3', 'word4', 'word5', 'word6', 'word7', 'word8', 'word9',
            'word10', 'word11', 'word12', 'wor13', 'word14', 'word15', 'word16', 'word17', 'word18',
            'word19', 'word20', 'word21', 'word22', 'word23', 'word24', 'word25', 'word26', 'word27',
            'word28', 'word29', 'word30', 'word31', 'word32', 'word33', 'word34', 'word35', 'word36',
            'ec', 'ca', 'ea', 'obr', 'type', 'timemodified'));

        // Build the tree
        $cma->add_child($points);
        $points->add_child($point);

        // Define sources
        $cma->set_source_table('cma', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $point->set_source_table('cma_points', array('cmaid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $point->annotate_ids('user', 'userid');

        // Define file annotations
        $cma->annotate_files('mod_cma', 'intro', null); // This file area hasn't itemid

        // Return the root element (cma), wrapped into standard activity structure
        return $this->prepare_activity_structure($cma);
    }
}
