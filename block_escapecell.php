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
 * Block escapecell is defined here.
 *
 * @package     block_escapecell
 * @copyright   marc.leconte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;


require_once(dirname(__FILE__).'/lib.php');
/**
 * Block Escape Cell.
 *
 * @package    block_escapecell
 * @copyright  2020 marc.leconte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_escapecell extends block_base {
    /**
     * Initializes class member variables.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_escapecell');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        global $USER, $COURSE, $OUTPUT, $CFG;
        $correspond = getquizescapecell();
        $niveaux = getlevel($correspond, $USER->id);
        $maxlevel = 6;
        if (isset ($CFG->escapecell_max_level)) {
            $maxlevel = $CFG->escapecell_max_level;
        }
        $scorestep = ($maxlevel * 15) / 4;
        if ($scorestep < 1) {
            $scorestep = 1;
        }

        $jvscript = "<script>
function maFonction(nbNiveau, level, bonus, qcm, onlyqcm, info) {
    var maxLevel = ". $maxlevel.";
    if (nbNiveau > maxLevel)
        return;
    var top = document.getElementById('parent');
    var larg = top.clientWidth;
    var offset = (larg - 213) / 2;
    console.log(larg);
    if (nbNiveau == 0) {
        var imgBase = document.createElement('img');
        imgBase.src = '/blocks/escapecell/pix/base_' + info + '.png';
        imgBase.style.position = 'absolute';
        imgBase.style.top = 300 - ((1-info) * 11) + 'px';
        imgBase.style.left= offset -12 +'px';
        top.appendChild(imgBase);
    } else {
        var haut = (270 - nbNiveau * 32) + 'px';
        var dir = 'droite';
        if (nbNiveau % 2 != 0) {
            dir = 'gauche';
        }
        if (qcm > -1) {
            var imgQcm = document.createElement('img');
            imgQcm.style.left= offset -16 +'px';
            if (nbNiveau % 2 != 0) {
                imgQcm.style.left= offset -12 +'px';
            }
            imgQcm.src = '/blocks/escapecell/pix/' + dir + '_qcm.png';
            imgQcm.style.position = 'absolute';
            imgQcm.style.top = haut;
            top.appendChild(imgQcm);
        }
        if (onlyqcm == 0) {
            var img = document.createElement('img');
            img.style.position = 'absolute';
            img.src = '/blocks/escapecell/pix/' + dir + '.png';
            if (nbNiveau % 2 == 0) {
                img.style.left= offset -16 + 'px';
            } else {
                img.style.left= offset -12 +'px';
            }
            img.style.top = haut;
            top.appendChild(img);
        }
        if (bonus > 0) {
            var imgBonus = document.createElement('img');
            imgBonus.style.left= offset -12 +'px';
            imgBonus.src = '/blocks/escapecell/pix/' + dir + '_bon_' + bonus + '.png';
            imgBonus.style.position = 'absolute';
            imgBonus.style.top = haut;
            top.appendChild(imgBonus);
        }
        if (nbNiveau == maxLevel) {
            var imgTet = document.createElement('img');
            imgTet.src = '/blocks/escapecell/pix/final' + info + '.png';
            imgTet.style.position = 'absolute';
            imgTet.style.left= offset -16 +'px';
            imgTet.style.top = (180 - nbNiveau * 32) + 'px';
            top.appendChild(imgTet);
        }
    }
}</script>";

        $style = "<style>#parent {position: relative;height:350px;}\n</style>";

        $codehtm = '<div id="parent"></div>';
        $test = "";
        $nb = 1;
        $niv = "-1";
        $bonus = "-1";
        $qcm = "-1";
        $onlyqcm = "0";

        $score = 0;
        foreach ($niveaux as $niveau) {
            $onlyqcm = "1";
            if (isset($niveau->niveau)) {
                $score += 6;
                $niv = $niveau->niveau;
            } else if (isset($niveau->rp_niveau)) {
                $niv = $niveau->rp_niveau;
            }
            if (isset($niveau->bonus)) {
                $bonus = $niveau->bonus;
                $score += $bonus;
                $onlyqcm = "0";
            }
            if (isset($niveau->rp_bonus)) {
                $bonus = $niveau->rp_bonus;
                $onlyqcm = "1";
                if ($bonus != -1) {
                    $score += 6 + $bonus;
                    $onlyqcm = "0";
                }
            }
            if (isset($niveau->note)) {
                $qcm = $niveau->note;
                $score += intval($qcm);
            }
            if (isset($niveau->rp_note)) {
                $qcm = $niveau->rp_note;
                if ($qcm != -1) {
                    $score += intval($qcm);
                }
            }
            $step = intdiv ($score, $scorestep) + 1;
            if ($step > $maxlevel) {
                $step = $maxlevel;
            }

            $test .= "maFonction(" . $nb .", ". $niv .", ".$bonus .", ". $qcm.", ". $onlyqcm .", ". $step .");";
            $nb++;
        }
        if ($score == 0) { // Without level.
            $test = "<script>maFonction(0, 0, -1, -1, -1, 0);";
        } else { // With some level.
            $test = "<script>maFonction(0, 0, -1, -1, -1, 1);" . $test;
        }
        $test .= "</script>";

        $this->content->text = "";
        $showscore = "0";
        if (!empty($this->config->showscore)) {
            $showscore = $this->config->showscore;
        }
        if ($showscore == "0") {
            $this->content->text .= "<span id='ttpts'>" . $score . " pts</span>";
        }
        $this->content->text .= $style;
        $this->content->text .= $jvscript;
        $this->content->text .= $codehtm;

        if (!isstudent($COURSE->id)) {
            $params = array('courseid' => $COURSE->id);
            $urltotscore = new moodle_url('/blocks/escapecell/viewscore.php', $params);

            $labeltotscore = get_string('totscore', 'block_escapecell');
            $optionsbtn = array('class' => 'overviewButton');

            $this->content->text .= '<br/><br/><center>';
            $this->content->text .= $OUTPUT->single_button($urltotscore, $labeltotscore, 'get', $optionsbtn);
            $this->content->text .= '</center>';
        }
        $this->content->text .= $test;
        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_escapecell');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Multiple instances are not allowed.
     */
    public function instance_allow_multiple() {
          return false;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array('all' => false, 'course-view' => true);
    }

    /**
     * Setting block. need settings.php
     */
    public function has_config() {
        return true;
    }

}
