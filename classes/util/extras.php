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
 * Custom moodlemoot extras functions
 *
 * @package    theme_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodlemoot\util;

use dml_exception;
use format_moodlemoot\manager;
use theme_config;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to get some extras info in Moodle.
 *
 * @package    theme_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extras {
    /**
     * Get the list of visible courses
     *
     * @return array
     *
     * @throws dml_exception
     */
    public static function get_courseslist_select() {
        global $DB;

        $sql = 'SELECT id, shortname FROM {course} WHERE visible = 1 AND id > 1 ORDER BY id DESC';
        $courses = $DB->get_records_sql($sql);

        if (empty($courses)) {
            return [];
        }

        $coursesmenu = [];
        foreach ($courses as $course) {
            $coursesmenu[$course->id] = $course->shortname;
        }

        return $coursesmenu;
    }

    public static function get_currentedition_infos() {
        global $DB;

        $theme = theme_config::load('moodlemoot');

        $courseid = $theme->settings->currentedition;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category, fullname, shortname, summary', MUST_EXIST);

        $formatoptions = $DB->get_records('course_format_options', ['courseid' => $courseid, 'format' => 'moodlemoot'], '', 'name, value');

        foreach ($formatoptions as $key => $value) {
            $course->$key = $value->value;
        }

        $coursemanager = new manager($course);

        $course->courseheader = $coursemanager->get_courseheader_url();

        return $course;
    }

    public static function get_total_site_users() {
        global $DB;

        return $DB->count_records('user', array('deleted' => 0, 'suspended' => 0)) - 1;
    }

    public static function get_total_editions() {
        return 19;
    }

    /**
     * Returns the first course's summary issue
     *
     * @param $course
     * @param $courselink
     *
     * @return string
     */
    public static function get_course_summary_image($course, $courselink) {
        global $CFG;

        $contentimage = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimage = \html_writer::link($courselink, \html_writer::empty_tag('img', array(
                    'src' => $url,
                    'alt' => $course->fullname,
                    'class' => 'card-img-top w-100')));
                break;
            }
        }

        if (empty($contentimage)) {
            $url = $CFG->wwwroot . "/theme/moodlemoot/pix/default_course.jpg";

            $contentimage = \html_writer::link($courselink, \html_writer::empty_tag('img', array(
                'src' => $url,
                'alt' => $course->fullname,
                'class' => 'card-img-top w-100')));
        }

        return $contentimage;
    }

    /**
     * Returns the user picture
     *
     * @param null $userobject
     * @param int $imgsize
     *
     * @return \moodle_url
     * @throws \coding_exception
     */
    public static function get_user_picture($userobject = null, $imgsize = 100) {
        global $USER, $PAGE;

        if (!$userobject) {
            $userobject = $USER;
        }

        $userimg = new \user_picture($userobject);

        $userimg->size = $imgsize;

        return $userimg->get_url($PAGE);
    }
}
