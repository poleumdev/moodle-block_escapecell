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

// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = CONTEXT_COURSE::instance($courseid);

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->set_url('/blocks/escapecell/viewscore.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$title = get_string('totscore', 'block_escapecell');
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
$students = array();
foreach ($lststudents as $student) {
    $stud = new stdClass();
    $stud->id = $student->id;
    $stud->name = $student->lastname;
    $stud->firstname = $student->firstname;
    $stud->score = getscores($stud->id);
    $students[$stud->id] = $stud;
}
$datas = computerepart($students);
$tablerepart = array();
$lastniv = 7;
if (isset ($CFG->escapecell_max_level)) {
    $lastniv = $CFG->escapecell_max_level + 1;
}
for ($i = 1; $i < $lastniv; $i++) {
    $row = new stdClass();
    $row->level = $i;
    if ($datas[$i][3] > 0) {
        $row->bonus3 = $datas[$i][3];
    }
    if ($datas[$i][2] > 0) {
        $row->bonus2 = $datas[$i][2];
    }
    if ($datas[$i][1] > 0) {
        $row->bonus1 = $datas[$i][1];
    }
    if ($datas[$i][0] > 0) {
        $row->nobonus = $datas[$i][0];
    }
    if ($datas[$i][4] > 0) {
        $row->noplayer = $datas[$i][4];
    }
    $tablerepart[] = $row;
}
$clt = computescore2($students, $courseid);
$tabclt = array();
$titpodium = '';

$indice = 1;
foreach ($clt as $key => $value) {
    $clsmnt = new stdClass();
    $clsmnt->rang = $indice;
    $clsmnt->name = $students[$key]->name . ' ' . $students[$key]->firstname;
    $clsmnt->pts = $value;
    if ($value > 0) {
        $tabclt[] = $clsmnt;
    }
    $indice ++;
}

if (count($tabclt) > 0) {
    $titpodium = get_string('titpodium', 'block_escapecell');
}

asort($clt);
$tabparticip = array();
foreach ($clt as $key => $value) {
    $noplayer = new stdClass();
    if ($value == 0) {
        $noplayer->namenp = $students[$key]->name . ' ' . $students[$key]->firstname;
        $tabparticip[] = $noplayer;
    } else {
        break;
    }
}
$titparticip = '';
$notall = false;
if (count($tabparticip) > 0) {
    $titparticip = get_string('titparticip', 'block_escapecell');
    $notall = true;
}
// Render via template.
echo $renderer->render_from_template('block_escapecell/scores', array(
        'courseid' => $courseid,
        'scores' => $tablerepart,
        'collevel'  => get_string('collevel', 'block_escapecell'),
        'colbonus3' => get_string('colbonus3', 'block_escapecell'),
        'colbonus2' => get_string('colbonus2', 'block_escapecell'),
        'colbonus1' => get_string('colbonus1', 'block_escapecell'),
        'colnobonus' => get_string('colnobonus', 'block_escapecell'),
        'colnoplay' => get_string('colnoplay', 'block_escapecell'),
        'nbstudents' => count($students),
        'titnbstudent' => get_string('titnbstudent', 'block_escapecell'),
        'titpodium' => $titpodium,
        'thebest' => $tabclt,
        'titparticip' => $titparticip,
        'tabparticip' => $tabparticip,
        'notall' => $notall
       ), null);

echo $OUTPUT->container_end();
echo $OUTPUT->footer();