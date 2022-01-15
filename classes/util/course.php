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
 * A utility course class.
 *
 * @package    theme_moodlemoot
 * @copyright  2020 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodlemoot\util;

use cm_info;
use completion_info;
use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * A utility course class
 *
 * @package    theme_moodlemoot
 * @copyright  2020 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {
    protected $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    public function get_sections_and_actvities($currentcoursemodule = null  ) {
        $sections = course_get_format($this->course)->get_sections();

        $data = [];
        foreach ($sections as $section) {
            if (!$section->visible) {
                continue;
            }

            $active = false;
            if ($currentcoursemodule && $currentcoursemodule->section == $section->id) {
                $active = true;
            }

            $sectionactivities = $this->get_section_coursemodule_list($this->course, $section, $currentcoursemodule);
            $progress = $this->calculate_section_progress($sectionactivities);

            $data[] = [
                'id' => $section->id,
                'name' => get_section_name($this->course->id, $section),
                'active' => $active,
                'activities' => $sectionactivities,
                'progress' => $progress,
                'iscompleted' => ($progress == 100) ? true : false,
                'available' => $section->available,
                'availableinfo' => $this->format_info($section->availableinfo, $this->course)
            ];
        }

        return $data;
    }

    protected function calculate_section_progress($sectionactivities) {
        $totalactivities = count($sectionactivities);

        if ($totalactivities === 0) {
            return 0;
        }

        $totalcompleted = 0;
        foreach ($sectionactivities as $activity) {
            if ($activity['completed']) {
                $totalcompleted++;
            }
        }

        if ($totalcompleted === 0) {
            return 0;
        }

        return (int)(($totalcompleted * 100) / $totalactivities);
    }

    public function get_section_coursemodule_list($course, $section, $currentcoursemodule = null) {
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info($section->section);

        $completioninfo = new completion_info($course);

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $sectionmodules = [];
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($module = $this->get_cm_list_item($mod,
                    $completioninfo, $currentcoursemodule)) {

                    if ($module['deletioninprogress'] == 1 || $module['visible'] == 0) {
                        continue;
                    }

                    if ($module['modname'] == 'label') {
                        continue;
                    }

                    if ($module['modname'] == 'url') {
                        $module['url']->params(['forceview' => 1]);
                    }

                    $sectionmodules[] = $module;
                }
            }
        }

        return $sectionmodules;
    }

    public function get_cm_list_item(cm_info $mod, &$completioninfo, $currentcoursemodule = null) {
        if (!$this->can_view_activity($mod)) {
            return null;
        }

        $completed = $this->course_section_cm_completion($this->course, $completioninfo, $mod);
        list($available, $availableinfo) = $this->course_section_cm_availability($mod);

        $active = false;
        if ($currentcoursemodule && $currentcoursemodule->id == $mod->id) {
            $active = true;
        }

        return [
            'id' => $mod->id,
            'url' => $mod->url,
            'name' => $mod->name,
            'modname' => $mod->modname,
            'instance' => $mod->instance,
            'active' => $active,
            'completed' => $completed,
            'available' => $available,
            'availableinfo' => $availableinfo,
            'visible' => $mod->visible,
            'deletioninprogress' => $mod->deletioninprogress
        ];
    }

    public function can_view_activity($mod) {
        if (!$mod->is_visible_on_course_page() || !$mod->visible) {
            $modcontext = context_module::instance($mod->id);
            $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
            if ($canviewhidden && !$mod->visible && $mod->get_section_info()->visible) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns null
     * If completion is completed returns true, false otherwise
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @return string
     *
     * @throws \coding_exception
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod) {
        global $USER;

        $istrackeduser = $completioninfo->is_tracked_user($USER->id);

        if (!$istrackeduser || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return null;
        }

        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }

        $completion = $completioninfo->is_enabled($mod);

        if ($completion == COMPLETION_TRACKING_NONE) {
            return null;
        }

        $completiondata = $completioninfo->get_data($mod, true);
        if ($completion == COMPLETION_TRACKING_MANUAL || $completion == COMPLETION_TRACKING_AUTOMATIC) {
            switch($completiondata->completionstate) {
                case COMPLETION_COMPLETE:
                case COMPLETION_COMPLETE_PASS:
                    return true;
                    break;
            }
        }

        return false;
    }

    /**
     * Return true if the activity is available for the user, false otherwise
     *
     * @param cm_info $mod
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function course_section_cm_availability(cm_info $mod) {
        if (!$mod->uservisible && !empty($mod->availableinfo)) {
            // this is a student who is not allowed to see the module but might be allowed
            // to see availability info (i.e. "Available from ...")
            $formattedinfo = $this->format_info($mod->availableinfo, $mod->get_course());

            return [false, $formattedinfo];
        }

        // this is a teacher who is allowed to see module but still should see the
        // information that module is not available to all/some students
        $modcontext = context_module::instance($mod->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
        if ($canviewhidden && !$mod->visible && $mod->get_section_info()->visible) {
            // This module is hidden but current user has capability to see it.
            // Do not display the availability info if the whole section is hidden.
            return [true, get_string('hiddenfromstudents')];
        }

        if ($mod->is_stealth()) {
            // This module is available but is normally not displayed on the course page
            // (this user can see it because they can manage it).
            $output = [true, get_string('hiddenoncoursepage')];
        }

        return [true, null];
    }

    /**
     * Formats the $cm->availableinfo string for display. This includes
     * filling in the names of any course-modules that might be mentioned.
     * Should be called immediately prior to display, or at least somewhere
     * that we can guarantee does not happen from within building the modinfo
     * object.
     *
     * @param \renderable|string $inforenderable Info string or renderable
     * @param int|\stdClass $courseorid
     * @return string Correctly formatted info string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function format_info($inforenderable, $courseorid) {
        global $PAGE;

        // Use renderer if required.
        if (is_string($inforenderable)) {
            $info = $inforenderable;
        } else {
            $renderer = $PAGE->get_renderer('core', 'availability');
            $info = $renderer->render($inforenderable);
        }

        // Don't waste time if there are no special tags.
        if (strpos($info, '<AVAILABILITY_') === false) {
            return $info;
        }

        // Handle CMNAME tags.
        $modinfo = get_fast_modinfo($courseorid);
        $context = \context_course::instance($modinfo->courseid);
        $info = preg_replace_callback('~<AVAILABILITY_CMNAME_([0-9]+)/>~',
            function($matches) use($modinfo, $context) {
                $cm = $modinfo->get_cm($matches[1]);
                if ($cm->has_view() and $cm->uservisible) {
                    // Help student by providing a link to the module which is preventing availability.
                    return format_string($cm->name, true, array('context' => $context));
                } else {
                    return format_string($cm->name, true, array('context' => $context));
                }
            }, $info);

        return $info;
    }
}
