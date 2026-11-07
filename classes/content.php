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
 * What the block shows.
 *
 * @package    block_oksigeniaayvurenda
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_oksigeniaayvurenda;

/**
 * What the block shows: the library's own view of the personal area (\local_oksigeniaayvurenda\local\dashboard), in
 * the words of each centre's skin.
 */
final class content {
    /**
     * The block of a person, or nothing for whoever has nothing to do with any library.
     *
     * @param int $userid
     * @param int|null $now
     * @return array|null
     */
    public static function for_user(int $userid, ?int $now = null): ?array {
        return \local_oksigeniaayvurenda\local\dashboard::for_user($userid, $now);
    }
}
