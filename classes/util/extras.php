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
use moodle_url;
use theme_config;
use core_course_list_element;

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

    /**
     * Get the current moodle moot edition infos.
     *
     * @return mixed
     *
     * @throws \coding_exception
     * @throws dml_exception
     */
    public static function get_currentedition_infos() {
        global $DB;

        $theme = theme_config::load('moodlemoot');

        $courseid = $theme->settings->currentedition;

        $course = $DB->get_record('course', ['id' => $courseid], 'id, category, fullname, shortname, summary', MUST_EXIST);

        $params = ['courseid' => $courseid, 'format' => 'moodlemoot'];
        $formatoptions = $DB->get_records('course_format_options', $params, '', 'name, value');

        foreach ($formatoptions as $key => $value) {
            $course->$key = $value->value;
        }

        $coursemanager = new manager($course);

        $course->courseheader = $coursemanager->get_courseheader_url();

        return $course;
    }

    /**
     * Get the total active site users.
     *
     * @return int
     * @throws dml_exception
     */
    public static function get_total_site_users() {
        global $DB;

        return $DB->count_records('user', array('deleted' => 0, 'suspended' => 0)) - 1;
    }

    /**
     * Get the total realized editions.
     *
     * @return int
     */
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

    /**
     * Returns all user enrolled courses with progress
     *
     * @param $user
     *
     * @return array
     */
    public static function user_courses_with_progress($user) {
        global $USER, $CFG;

        if (($USER->id !== $user->id) && !is_siteadmin($USER->id)) {
            return [];
        }

        require_once($CFG->dirroot.'/course/renderer.php');

        $chelper = new \coursecat_helper();

        $courses = enrol_get_users_courses($user->id, true, '*', 'id DESC');

        foreach ($courses as $course) {
            $course->fullname = strip_tags($chelper->get_course_formatted_name($course));

            $courseobj = new \core_course_list_element($course);
            $completion = new \completion_info($course);

            // First, let's make sure completion is enabled.
            if ($completion->is_enabled()) {
                $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id);

                if (!is_null($percentage)) {
                    $percentage = floor($percentage);
                }

                if (is_null($percentage)) {
                    $percentage = 0;
                }

                // Add completion data in course object.
                $course->completed = $completion->is_course_complete($user->id);
                $course->progress  = $percentage;
            }

            $course->link = $CFG->wwwroot."/course/view.php?id=".$course->id;

            // Summary.
            $course->summary = strip_tags($chelper->get_course_formatted_summary(
                $courseobj,
                array('overflowdiv' => false, 'noclean' => false, 'para' => false)
            ));

            $course->courseimage = self::get_course_summary_image($courseobj, $course->link);
        }

        return array_values($courses);
    }

    /**
     * Returns the buttons displayed at the page header
     *
     * @param $context
     * @param $user
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_mypublic_headerbuttons($context, $user) {
        global $USER, $CFG;

        $headerbuttons = [];

        // Check to see if we should be displaying a message button.
        if (!empty($CFG->messaging) && $USER->id != $user->id && has_capability('moodle/site:sendmessage', $context)) {
            $iscontact = !empty(\core_message\api::get_contact($USER->id, $user->id)) ? 1 : 0;
            $contacttitle = $iscontact ? 'removecontact' : 'addcontact';
            $contacturlaction = $iscontact ? 'removecontact' : 'addcontact';
            $contactimage = $iscontact ? 'fa fa-user-times' : 'fa fa-address-card';
            $headerbuttons = [
                [
                    'title' => get_string('sendmessage', 'core_message'),
                    'url' => new \moodle_url('/message/index.php', array('id' => $user->id)),
                    'icon' => 'fa fa-comment-o',
                    'class' => 'btn btn-block btn-outline-primary'
                ],
                [
                    'title' => get_string($contacttitle, 'theme_moove'),
                    'url' => new \moodle_url('/message/index.php', [
                            'user1' => $USER->id,
                            'user2' => $user->id,
                            $contacturlaction => $user->id,
                            'sesskey' => sesskey()]
                    ),
                    'icon' => $contactimage,
                    'class' => 'btn btn-block btn-outline-dark ajax-contact-button',
                    'linkattributes' => \core_message\helper::togglecontact_link_params($user, $iscontact),
                ]
            ];

            \core_message\helper::togglecontact_requirejs();
        }

        return $headerbuttons;
    }

    /**
     * Get all the issued certificates by the user.
     *
     * @param $userid
     *
     * @return array
     *
     * @throws dml_exception
     */
    public static function get_issued_certificates($userid) {
        global $DB, $USER;

        // Somente administradores podem ver os certificados dos outros usuarios.
        if ($USER->id != $userid && !is_siteadmin()) {
            return [];
        }

        $sql = 'SELECT sci.*, sc.name, c.id as courseid, c.fullname, c.shortname
                FROM {simplecertificate_issues} sci
                INNER JOIN {simplecertificate} sc ON sc.id = sci.certificateid
                INNER JOIN {course} c ON sc.course = c.id
                WHERE sci.timedeleted IS NULL AND sci.userid = :userid
                ORDER BY c.fullname, sci.timecreated';

        $params = ['userid' => $userid];

        $certificates = $DB->get_records_sql($sql, $params);

        if (empty($certificates)) {
            return [];
        }

        $fs = get_file_storage();

        $returndata = [];
        foreach ($certificates as $certificate) {
            if (!$fs->file_exists_by_hash($certificate->pathnamehash)) {
                continue;
            }

            $returndata[] = $certificate;
        }

        return $returndata;
    }

    /**
     * Group certificates by course.
     *
     * @param $certificates
     *
     * @return array
     */
    public static function group_certificates_by_course($certificates) {
        $returndata = [];

        foreach ($certificates as $certificate) {

            $certs = [$certificate];
            if (isset($returndata[$certificate->courseid])) {
                $certs = array_merge($certs, $returndata[$certificate->courseid]['certificates']);

                $returndata[$certificate->courseid]['certificates'] = $certs;

                continue;
            }

            $returndata[$certificate->courseid] = [
                'courseid' => $certificate->courseid,
                'shortname' => $certificate->shortname,
                'fullname' => $certificate->fullname,
                'certificates' => $certs
            ];
        }
        return $returndata;
    }

    /**
     * Returns the first course's summary issue
     *
     * @param $course
     * @param $courselink
     *
     * @return string
     *
     * @throws \moodle_exception
     */
    public static function get_summary_image_url($course) {
        $courselist = new core_course_list_element($course);

        foreach ($courselist->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $pathcomponents = [
                    '/pluginfile.php',
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename()
                ];
                $path = implode('/', $pathcomponents);

                return (new moodle_url($path))->out();
            }
        }

        return (new moodle_url('/theme/moodlemoot/pix/default_course.jpg'))->out();
    }
}
