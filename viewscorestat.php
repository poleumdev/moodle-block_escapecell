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
 * Block escapecell stat score page.
 *
 * @package     block_escapecell
 * @copyright   marc.leconte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$niv = required_param('niv', PARAM_INT);

$nobonus = required_param('nobonus', PARAM_INT);
$bonus1 = required_param('bonus1', PARAM_INT);
$bonus2 = required_param('bonus2', PARAM_INT);
$bonus3 = required_param('bonus3', PARAM_INT);
$nbstudents = required_param('nbstudents', PARAM_INT);

// Determine course and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = CONTEXT_COURSE::instance($courseid);

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->set_url('/blocks/escapecell/viewscoreniv.php', array('courseid' => $courseid, 'niv' => $niv));
$PAGE->set_context($context);
$title =  "Représentation proportionnelle des scores du niveau " . $niv;
//statistique des scores du niveau " . $niv;//get_string('stdnivnotpassed', 'block_escapecell', $niv);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('report');

// Check user is logged in and capable of accessing the Overview.
require_login($course, false);

$tabvalues = computstat($courseid, $niv, $nobonus, $bonus1, $bonus2, $bonus3);
// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_escapecell');
echo '<div style="display:none">';
echo '<img id="legend" src="/blocks/escapecell/pix/legende.png">';
echo '</div>';

echo '<canvas id="panelfleur" width="730" height="500" style="display:inline-block;"></canvas>';
echo $OUTPUT->heading("Statistique des scores du niveau " . $niv);
echo '<canvas id="canvas1" width="650" height="400" style="display:inline-block;" ></canvas>';

echo "<script>
function test(datalist) {
    var image = document.getElementById('legend');
    image.onload = function() {
        var imagePieces = new Array();
        var widthOfOnePiece = 120;
        var heightOfOnePiece= 316;

        for(var x = 0; x < 10; ++x) {
            var canvas = document.createElement('canvas');
            canvas.width = 60;//widthOfOnePiece;
            canvas.height = 158;//heightOfOnePiece;
            var context = canvas.getContext('2d');
            context.drawImage(image, x * widthOfOnePiece, 0, widthOfOnePiece, heightOfOnePiece, 0, 0, canvas.width, canvas.height);
            imagePieces.push(canvas);
        }
        fleurs(datalist, imagePieces);
    }
}


function fleurs(datalist, tabimg) {
    var canvas = document.getElementById('panelfleur');
    var ctx = canvas.getContext('2d');

    var total = 0;
    for(x=0; x < datalist.length; x++) { total += datalist[x]; };
    var nbsiege = 0;
    for (fl = datalist.length; fl >= 0; --fl) {
        var pc = Math.floor((datalist[fl] / total) * 20);
        if (!isNaN(pc)) {
            nbsiege += pc;
        }
    }

    var parligne = new Array(2,3,4,5,6);
    var py = 0;
    var lig = 0;
    var nombre = 4;
    
    var px = 50 * nombre;
    // Des meilleurs score vers les moins bons.
    for (fl = datalist.length; fl >= 0; --fl) {
        var pc = Math.floor((datalist[fl] / total) * 20);
        if (!isNaN(pc)) {
            for  (tr = 0 ; tr < pc; tr ++) {
                ctx.drawImage(tabimg[fl], px, py);
                px = px + 100;//120;
                nbsiege--;
                if (px >= 600 - nombre * 50) {
                    lig = lig + 1;
                    var plibre = parligne[lig] - nbsiege;
                    if (plibre > 0) {
                        var cnb = 4 - Math.floor(plibre / 2);
                        if (((cnb - nombre) % 2) == 0) nombre = cnb-1;
                        else nombre = cnb;
                    } else {
                        nombre--;
                    }
                    px = 50 * nombre;
                    py = py + 70;//90;
                }
            }
        }
    }
}

function pie(ctx, w, h, datalist) {
  var radius = h / 2 - 50;
  var centerx = w / 2;
  var centery = h / 2;
  var total = 0;
  for(x=0; x < datalist.length; x++) { total += datalist[x]; };
  var lastend=0;
  var offset = Math.PI / 2;
  for(x=0; x < datalist.length; x++) {
    var thispart = datalist[x];
    var alph = 0;
    if (x == 0 || x == 1) {
        alph = lastend + Math.PI * (thispart / total);
    } else if (x==2 || x == 6) {
        var sstot = thispart + datalist[x + 1] + datalist[x + 2] + datalist[x + 3];
        alph = lastend + Math.PI * (sstot / total);
    }

    if (alph != 0) {
        var tx = Math.cos(alph - offset) * 18;
        var ty = Math.sin(alph - offset) * 18;
        ctx.translate(tx, ty);
    }
    ctx.beginPath();
    ctx.fillStyle = colist[x];
    ctx.moveTo(centerx,centery);
    var arcsector = Math.PI * (2 * thispart / total);
    ctx.arc(centerx, centery, radius, lastend - offset, lastend + arcsector - offset, false);
    ctx.lineTo(centerx, centery);
    ctx.fill();
    ctx.closePath();
    if (x == 0 || x == 1 || x ==5 || x==10) {
        ctx.setTransform(1, 0, 0, 1, 0, 0);
    }
    lastend += arcsector;
  }
}


var datalist= new Array(".$tabvalues[0].",".$tabvalues[1].",".$tabvalues[2].",".$tabvalues[3].
                    ",".$tabvalues[4].",".$tabvalues[5].",".$tabvalues[6].",".$tabvalues[7].
                    ",".$tabvalues[8].",".$tabvalues[9].",0);

var colist = new Array('rgb(0, 113, 162)', 'rgb(22, 118, 51)',
            'rgb(185, 175, 0)', 'rgb(255, 242, 0)', 'rgb(255, 245, 70)', 'rgb(255, 250, 140)',
            'rgb(182, 14, 22)', 'rgb(237, 28, 36)', 'rgb(242, 94, 101)', 'rgb(248, 158, 163)');
var canvas = document.getElementById('canvas1');
var ctx = canvas.getContext('2d');
pie(ctx, canvas.width -200, canvas.height, datalist);

var total = 0;
var nblig = 0;
for(x=0; x < datalist.length; x++) {
    total += datalist[x];
    if (datalist[x] > 0) {
        nblig++;
    }
};

ctx.font = '16px serif';
var posx = 410;
var datatxt = new Array('Non participant','QCM uniquement','Jeu uniquement sans bonus',
'Jeu uniquement un bonus', 'Jeu uniquement deux bonus', 'Jeu uniquement trois bonus',
'Jeu + QCM sans bonus', 'Jeu + QCM un bonus', 'Jeu + QCM deux bonus', 'Jeu + QCM trois bonus');
var interlig = 18;
var posy = canvas.height - nblig * interlig;
var ligvisible = 0;
for(lig=0; lig < datatxt.length; lig++) {
    if (datalist[lig] > 0) {
        ctx.beginPath();
        ctx.fillStyle = colist[lig];
        ctx.fillRect(posx -20, posy + ligvisible * interlig -15, 40, 15);

        ctx.fillStyle = 'black';
        var pc = '' + Math.round((datalist[lig] / total) * 10000) / 100;
        ctx.textAlign = 'center';
        ctx.fillText(pc, posx, posy + ligvisible * interlig, 50);
        ctx.textAlign = 'left';
        ctx.fillText(datatxt[lig], posx + 25, posy + ligvisible * interlig);

        ctx.closePath();
        ligvisible++;
    }
}
window.onload = test(datalist);
</script>";
$params = array('courseid' => $courseid);
$urltotscore = new moodle_url('/blocks/escapecell/viewscore.php', $params);
$labeltotscore = 'Retour';//get_string('totscore', 'block_escapecell');
$optionsbtn = array('class' => 'overviewButton');
echo '<br/><br/><center>';
echo $OUTPUT->single_button($urltotscore, $labeltotscore, 'get', $optionsbtn);
echo '</center>';

/*
bleue : 0,113,162
vert :22,118,51
jaune :185,175,0 / 255,242,0 / 255, 245, 70 / 255,250,140
rouge : 182,14,22 /237,28,36 /242,94,101 /248,158,163



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
*/

echo $OUTPUT->container_end();
echo $OUTPUT->footer();