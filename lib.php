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
 * @package mod_page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Add CMA instance.
 * @param stdClass $data
 * @param mod_cma_mod_form $mform
 * @return int new cma instance id
 */
function cma_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    $data->timecreated = time();

    $data->id = $DB->insert_record('cma', $data);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'cma', $data->id, $completiontimeexpected);
    return $data->id;
}

/**
 * Delete cma instance.
 * @param int $id
 * @return bool true
 */
function cma_delete_instance($id) {
    global $DB;

    if (!$cma = $DB->get_record('cma', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('cma', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'cma', $id, null);

    $DB->delete_records('cma', array('id' => $cma->id));
    return true;
}

/**
 * List of features supported in CMA module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */

function cma_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function cma_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function cma_reset_userdata($data) {
    return $data; // We don't have to delete the data.
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function cma_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function cma_get_post_actions() {
    return array('update', 'add');
}



/**
 * Update CMA instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function cma_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

    $DB->update_record('cma', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'cma', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function cma_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$cma = $DB->get_record('cma', array('id' => $coursemodule->instance),
            'id, name, intro, introformat, previous, timeopen, timeclose, activity')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $cma->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('cma', $cma, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $cma       cma object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function cma_view($cma, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $cma->id
    );

    $event = \mod_cma\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('cma', $cma);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

function cma_played($cma, $course, $cm, $context) {

    // Trigger CMA test played event.
    $params = array(
        'context' => $context,
        'objectid' => $cma->id
    );

    $event = \mod_cma\event\cma_test_played::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('cma', $cma);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function cma_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_cma_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['cma'][$event->instance];

    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/cma/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

function cma_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get cma details.
    $cma = $DB->get_record('cma', array('id' => $cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false.
    if ($cma->completion) {
        $count = $DB->get_field_sql("
                                     SELECT
                                         COUNT(1)
                                     FROM
                                         {cma_points} cp
                                     WHERE
                                         cp.userid=:userid",
                                                 array('userid' => $userid));
        if ($count >= 1) {
            return true;
        } else {
            return false;
        }
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}

function mod_cma_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionplayedenabled':
                if (empty($val)) {
                    continue;
                }
                $descriptions[] = get_string('completionplayed', 'mod_cma', $val);
                break;
            default:
                break;
        }
    }
    return $descriptions;
}