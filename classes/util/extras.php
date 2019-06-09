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

use core_competency\api as competency_api;

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

        $courses = enrol_get_users_courses($user->id, true, '*', 'visible DESC, fullname ASC, sortorder ASC');

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
            $contactimage = $iscontact ? 'slicon-user-unfollow' : 'slicon-user-follow';
            $headerbuttons = [
                [
                    'title' => get_string('sendmessage', 'core_message'),
                    'url' => new \moodle_url('/message/index.php', array('id' => $user->id)),
                    'icon' => 'fa fa-comment-o',
                    'class' => 'btn btn-block btn-outline-primary'
                ],
                [
                    'title' => get_string($contacttitle, 'theme_moodlemoot'),
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
}
