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
 * Block escapecell score page.
 *
 * @package     block_escapecell
 * @copyright   marc.leconte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$niv = required_param('niv', PARAM_INT);

// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = CONTEXT_COURSE::instance($courseid);

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->set_url('/blocks/escapecell/viewscoreniv.php', array('courseid' => $courseid, 'niv' => $niv));
$PAGE->set_context($context);
$title = get_string('stdnivnotpassed', 'block_escapecell', $niv);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

// Check user is logged in and capable of accessing the Overview.
require_login($course, false);

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_escapecell');
$renderer = new core_renderer($PAGE, null);


// Get informations.
$lststudents = liststudent($courseid);
$passed = getstudentbyniv($niv);
$students = array();
foreach ($lststudents as $student) {
    if (!isset($passed[$student->id])) {
        $stud = new stdClass();
        $stud->name = $student->lastname;
        $stud->firstname = $student->firstname;
        $students[] = $stud;
    }
}
$params = array('courseid' => $courseid);
$urltotscore = new moodle_url('/blocks/escapecell/viewscore.php', $params);

echo $renderer->render_from_template('block_escapecell/noplay', array(
        'lastname' => 'Nom',
        'firstname' => 'Prénom',
        'std' => $students,
        'urlret' => $urltotscore), null);

echo $OUTPUT->container_end();
echo $OUTPUT->footer();