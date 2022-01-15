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

// Evita de um usuario visitante acessar diretamente as atividades dos demais topicos sem estar matriculado.
if ($this->page->cm && $this->page->course->format == 'moodlemoot') {
    if ((int)$this->page->cm->sectionnum > 0 && (!is_enrolled($this->page->context) && !is_siteadmin())) {
        redirect(new moodle_url('/course/view.php', ['id' => $this->page->course->id, 'page' => 'introduction']));
    }
}

$extraclasses = [];
if (isset($PAGE->cm->modname)) {
    $extraclasses[] = 'immersive-course-mode';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes
];

if (isset($PAGE->cm->modname)) {
    $context = context_course::instance($this->page->course->id);

    $courseutil = new \theme_moodlemoot\util\course($this->page->course);
    $sections = $courseutil->get_sections_and_actvities($this->page->cm);

    $templatecontext['sections'] = $sections;
    $templatecontext['caneditcourse'] = has_capability('moodle/course:update', $context);

    echo $OUTPUT->render_from_template('theme_moodlemoot/incourse_immersive', $templatecontext);
} else {
    echo $OUTPUT->render_from_template('theme_moodlemoot/column', $templatecontext);
}
