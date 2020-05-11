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
 * Block escapecell instance setting.
 *
 * @package     block_escapecell
 * @copyright   marc.leconte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Block Escape Cell instance setting.
 *
 * @package    block_escapecell
 * @copyright  2020 marc.leconte
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_escapecell_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('select', 'config_showscore',
                            get_string('hidescore', 'block_escapecell'),
                            array(1 => get_string('yes'), 0 => get_string('no')));
        $mform->setDefault('config_showscore', 2);
        $mform->setType('config_showscore', PARAM_RAW);
    }
}
