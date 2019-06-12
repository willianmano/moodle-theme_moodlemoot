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
 * The columns layout for the moodlemoot theme.
 *
 * @package   theme_moodlemoot
 * @copyright 2019 Willian Mano {@link http://conecti.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Get the profile userid.
global $DB;

$userid = optional_param('id', $USER->id, PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$bodyattributes = $OUTPUT->body_attributes();

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes
];

$context = context_course::instance(SITEID);

$usercourses = \theme_moodlemoot\util\extras::user_courses_with_progress($user);
$templatecontext['hascourses'] = (count($usercourses)) ? true : false;
$templatecontext['courses'] = array_values($usercourses);
$templatecontext['totalcourses'] = count($usercourses);

$templatecontext['user'] = $user;
$templatecontext['user']->profilepicture = \theme_moodlemoot\util\extras::get_user_picture($user, 100);

$templatecontext['headerbuttons'] = \theme_moodlemoot\util\extras::get_mypublic_headerbuttons($context, $user);

$certificates = \theme_moodlemoot\util\extras::get_issued_certificates($user->id);

$groupedcertificates = [];
if ($certificates) {
    $groupedcertificates = array_values(\theme_moodlemoot\util\extras::group_certificates_by_course($certificates));
}

$templatecontext['hascoursescertificates'] = (count($certificates)) ? true : false;
$templatecontext['totalissuedcertificates'] = count($certificates);
$templatecontext['coursescertificates'] = $groupedcertificates;

echo $OUTPUT->render_from_template('theme_moodlemoot/mypublic', $templatecontext);