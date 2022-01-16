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

$bodyattributes = $OUTPUT->body_attributes();

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'isloggedin' => isloggedin() ? true : false
];

$editionsinfo = new stdClass();
$templatecontext['wearetxt'] = get_string('wearetxt', 'theme_moodlemoot', $editionsinfo);

$slideshowcourses = \theme_moodlemoot\util\extras::get_slideshow_courses();
$hasslideshowcourses = !empty($slideshowcourses);

$templatecontext['slideshowcourses'] = $slideshowcourses;
$templatecontext['hasslideshowcourses'] = $hasslideshowcourses;

echo $OUTPUT->render_from_template('theme_moodlemoot/frontpage', $templatecontext);
