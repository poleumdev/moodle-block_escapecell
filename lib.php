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
 * Library of interface functions and constants.
 *
 * @package     block_escapecell
 * @copyright   marc.leconte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * All students enrolled in the course.
 *
 * @param int $courseid The course ID where the students are registered.
 * @return \stdClass Standard Moodle DB object with the ID, firstname and lastname
 * for each student enrolled in the course.
 */
function liststudent($courseid) {
    global $DB;
    $result = $DB->get_record('role', array('shortname' => 'student'), "id");
    $studentroleid = $result->id;

    $request = "
                SELECT DISTINCT u.id, u.firstname, u.lastname
                FROM {user} u
                JOIN {role_assignments} ra
                    ON u.id = ra.userid
                JOIN {context} cx
                    ON ra.contextid = cx.id
                JOIN {course} c
                    ON cx.instanceid = c.id
                    AND cx.contextlevel = 50
                WHERE 1=1
                    AND c.id = ?
                    AND ra.roleid = ?
                ORDER BY u.lastname
                ";
    $result = $DB->get_records_sql($request, array($courseid, $studentroleid));
    return $result;
}

/**
 * Calculate the distribution of students by level and bonus.
 *
 * @param sdtClass[] $students All students with their scores.
 * @return table containing the distribution of students by score.
 **/
function computerepart($students) {
    $repart = array();
    $tot = count($students);
    for ($i = 1; $i < 10; $i++) {
        $repart[$i] = [0, 0, 0, 0, $tot];
    }

    foreach ($students as $student) {
        foreach ($student->score as $key => $value) {
            $repart[$key][$value] = $repart[$key][$value] + 1;
            $repart[$key][4] = $repart[$key][4] - 1;
        }
    }
    return $repart;
}

/**
 * Calculates the score value according to the bonuses obtained per level.
 * Score = sum (bonus * 4).
 *
 * @param sdtClass[] $students All students with their bonus per level.
 * @return the table of scores sorted from the best to the worst.
 * clt[userid] = scoreValue.
 **/
function computescore($students) {
    $clt = array();
    foreach ($students as $student) {
        $totpoint = 0;
        foreach ($student->score as $key => $value) {
            $totpoint += $key * ($value + 1);
        }
        $clt[$student->id] = $totpoint;
    }
    arsort($clt);
    return $clt;
}

function computescore2($students, $courseid) {
    global $DB;
    $qcmmod = $DB->get_record('modules', ['name' => 'quiz'], $fields = 'id');
    $qcmmodid = $qcmmod->id;

    $reqqcm = "SELECT sum(finalgrade) as sumpts
                 FROM {grade_grades}
                WHERE userid = ?
                  AND itemid IN (SELECT id FROM {grade_items}
                                  WHERE iteminstance IN (SELECT instance
                                                           FROM {course_modules}
                                                          WHERE course = ?
                                                            and module = ?
                                                        )
                                )";

    $clt = array();
    foreach ($students as $student) {
        $totpoint = 0;
        foreach ($student->score as $key => $value) {
            $totpoint += $value + 6;
        }
        // Sum of QUIZ points for 1 student in this course.
        $totqcm = $DB->get_record_sql($reqqcm, array($student->id, $courseid, $qcmmodid));

        $clt[$student->id] = $totpoint + intval($totqcm->sumpts);
    }
    arsort($clt);
    return $clt;
}

/**
 * Reads all the scores obtained by the player and Keeps only the best bonus per level.
 *
 * @param int $userid The player ID.
 * @return The table of scores obtained, i.e. the number of bonuses per level.
 * $bonus[level] = nb of bonus.
 **/
function getscores($userid) {
    global $DB;
    $scores = $DB->get_records('escapecell_score', array('userid' => $userid));
    $bonus = array();
    foreach ($scores as $score) {
        if (!isset($bonus[$score->niveau])) {
            $bonus[$score->niveau] = 0;
        }
        if ($bonus[$score->niveau] < $score->score) {
            $bonus[$score->niveau] = $score->score;
        }
    }
    return $bonus;
}

/**
 * Liste les identifiants des etudiants qui ont passé un niveau du jeu.
 */
function getstudentbyniv($niv) {
    global $DB;
    $scores = $DB->get_records('escapecell_score', array('niveau' => $niv));
    $std = array();
    foreach ($scores as $score) {
        $std[$score->userid] = 1;
    }
    return $std;
}

/**
 * Tests whether the current user is registered as a learner in the course.
 *
 * @param int $courseid The identifier of the course being tested
 * @return true if the user is an student or false in other case.
 **/
function isstudent($courseid) {
    global $USER;

    $result = liststudent($courseid);
    $student = false;
    foreach ($result as $enreg) {
        if ($USER->id == $enreg->id) {
            $student = true;
        }
    }
    return $student;
}

/**
 * Etablit la correspondance entre QCM et niveau escapecell.
 * return un tableau tab[idsection] = [
 * elementscore {
 *      instance : val,
 *      type : « QCM » / « Escapecell »
 *     }
 * ]
 */
function getquizescapecell() {
    global $DB, $COURSE;
    $qcmmod = $DB->get_record('modules', ['name' => 'quiz'], $fields = 'id');
    $qcmmodid = $qcmmod->id;
    $escapecellmod = $DB->get_record('modules', ['name' => 'escapecell'], $fields = 'id');
    $escapecellmodid = $escapecellmod->id;

    $sql = "select gr.id as grid, instance, section
              from {course_modules} cm
              join {grade_items} gr on gr.iteminstance = cm.instance
             where course = ? and module = ? and gr.courseid = ?";
    $qcms = $DB->get_records_sql($sql, array($COURSE->id, $qcmmodid, $COURSE->id));

    $correspondance = array();
    foreach ($qcms as $qcm) {
        if (isset($correspondance[$qcm->section])) {
            $tabelt = $correspondance[$qcm->section];
        } else {
            $tabelt = array();
        }
        $eltscore = new stdClass();
        $eltscore->instance = $qcm->instance;
        $eltscore->type = 'QCM';
        $eltscore->iteminstance = $qcm->grid;
        $tabelt[] = $eltscore;
        $correspondance[$qcm->section] = $tabelt;
    }
    $cells = $DB->get_records('course_modules',
                        ['module' => $escapecellmodid, 'course' => $COURSE->id],
                        $fields = 'section, instance');
    foreach ($cells as $cell) {
        if (isset($correspondance[$cell->section])) {
            $tabelt = $correspondance[$cell->section];
        } else {
            $tabelt = array();
        }
        $eltscore = new stdClass();
        $eltscore->instance = $cell->instance;
        $eltscore->type = 'CELL';
        $eltscore->niveau = $DB->get_field('escapecell', 'startlevel', ['id' => $cell->instance]);
        $tabelt[] = $eltscore;
        $correspondance[$cell->section] = $tabelt;
    }
    return $correspondance;
}

function getlevel($correspond, $userid) {
    $tabresultat = geteltunjoueur($correspond, $userid);
    $tabresultat = fusionsection($correspond, $tabresultat);
    return $tabresultat;
}

/**
 * fusionne la structure resultat
 * en placant dans la meme structure les qcm et niveau
 * correspondant.
 * La structure resultat contient au depart
 *  soit id = $qcm->itemid;
         note = $qcm->finalgrade;
         maxi = $qcm->rawgrademax;
         timemodified = $qcm->timemodified;
    soit id = $lvl->jeux;
         bonus = $lvl->score;
         niveau = $lvl->niveau;
         timemodified = $lvl->timemodified;
 */
function fusionsection($correspond, $tabresultat) {
    $ret = array();
    foreach ($tabresultat as $resultat) {
        if (isset($resultat->note) && !isset($resultat->delete)) { // QCM.
            $elt = getniveaucorr($correspond, $resultat->id);
            if ($elt != null) {
                // Val defauts.
                $resultat->rp_jeux = $elt->instance;
                $resultat->rp_bonus = -1;
                $resultat->rp_niveau = $elt->niveau;

                foreach ($tabresultat as $rech) {
                    if ($rech->id == $elt->instance && isset($rech->bonus)
                        && $rech->timemodified > $resultat->timemodified) {
                        $resultat->rp_bonus = $rech->bonus;
                        $rech->delete = 1;
                    }
                }
            }
        } else if (isset($resultat->niveau) && !isset($resultat->delete)) {
            $elt = getqcmcorr($correspond, $resultat->id);

            if ($elt != null) {
                $resultat->rp_id = $elt->iteminstance;
                $resultat->rp_note = -1;
                $resultat->rp_maxi = -1;

                foreach ($tabresultat as $rech) {
                    if ($rech->id == $elt->iteminstance && isset($rech->note)
                        && $rech->timemodified > $resultat->timemodified) {
                        $resultat->rp_note = $rech->note;
                        $resultat->rp_maxi = $rech->maxi;
                        $rech->delete = 1;
                    }
                }
            }
        }

        if (isset($resultat->delete) && $resultat->delete != 1) {
            $ret[] = $resultat;
        }
        if (!isset($resultat->delete)) {
            $ret[] = $resultat;
        }
    }
    return $ret;
}

function getqcmcorr($correspond, $levelid) {
    foreach ($correspond as $tabitem) {
        $trouve = false;
        foreach ($tabitem as $item) {
            if ($item->type == 'CELL' && $item->instance == $levelid) {
                $trouve = true;
            }
        }
        // On prend le premier elt de type='QCM'.
        if ($trouve) {
            foreach ($tabitem as $item) {
                if ($item->type == 'QCM') {
                    return $item;
                }
            }
        }
    }
    return null;
}

function getniveaucorr($correspond, $qcmid) {
    foreach ($correspond as $tabitem) {
        $trouve = false;
        foreach ($tabitem as $item) {
            if ($item->type == 'QCM' && $item->iteminstance == $qcmid) {
                $trouve = true;
            }
        }
        // On prend le premier elt de type='CELL'.
        if ($trouve) {
            foreach ($tabitem as $item) {
                if ($item->type == 'CELL') {
                    return $item;
                }
            }
        }
    }
    return null;
}

/**
 * Tout les resultats d'un joueur par ordre de passage.
 * les resultats QCM et EscapeCell sont dissociés.
 */
function geteltunjoueur($correspond, $userid) {
    global $DB;
    $res = array();
    foreach ($correspond as $tabitem) {
        foreach ($tabitem as $item) {
            if (isset($item->iteminstance)) { // Quiz.
                $qcms = $DB->get_records('grade_grades',
                        ['itemid' => $item->iteminstance, 'userid' => $userid],
                        $fields = 'finalgrade, rawgrademax, timemodified,itemid');
                foreach ($qcms as $qcm) {
                    if (isset($qcm->finalgrade)) {
                        $result = new stdClass();
                        $result->id = $qcm->itemid;
                        $result->note = $qcm->finalgrade;
                        $result->maxi = $qcm->rawgrademax;
                        $result->timemodified = $qcm->timemodified;
                        $res[$result->timemodified] = $result;
                    }
                }
            } else {
                $lvls = $DB->get_records('escapecell_score',
                        ['jeux' => $item->instance, 'userid' => $userid],
                        $fields = 'niveau, score, timemodified, jeux');
                foreach ($lvls as $lvl) {
                    if (isset($lvl->niveau)) {
                        $result = new stdClass();
                        $result->id = $lvl->jeux;
                        $result->bonus = $lvl->score;
                        $result->niveau = $lvl->niveau;
                        $result->timemodified = $lvl->timemodified;
                        $res[$result->timemodified] = $result;
                    }
                }
            }
        }
    }
    ksort($res);
    return $res;
}