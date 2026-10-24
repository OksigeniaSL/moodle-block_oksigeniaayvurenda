<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The block of Ayvurenda: in the personal area, on the site's front page or on a course, what a reader has in the
 * library (their books out and until when, the books set aside for them) and the ways in: the library, their card
 * and Ayvu rande. For someone who is no reader, nothing.
 *
 * @package    block_oksigeniaayvurenda
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oksigeniaayvurenda extends block_base {
    /**
     * Its title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_oksigeniaayvurenda');
    }

    /**
     * Where it can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['my' => true, 'site-index' => true, 'course-view' => true, 'mod' => false];
    }

    /**
     * Without settings of its own.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }

    /**
     * Its content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = (object) ['text' => '', 'footer' => ''];
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
        $data = \block_oksigeniaayvurenda\content::for_user((int) $USER->id);
        if ($data) {
            $this->content->text = $OUTPUT->render_from_template('block_oksigeniaayvurenda/content', $data);
        }
        return $this->content;
    }
}
