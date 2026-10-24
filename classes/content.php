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
 * The words of the block around what the library says a person has.
 */
final class content {
    /**
     * The block of a person, or nothing if they are no reader anywhere.
     *
     * @param int $userid
     * @param int|null $now
     * @return array|null
     */
    public static function for_user(int $userid, ?int $now = null): ?array {
        $str = fn(string $key, $a = null) => get_string($key, 'block_oksigeniaayvurenda', $a);
        $libraries = \local_oksigeniaayvurenda\local\mine::summary($userid, $now);
        if (!$libraries) {
            return null;
        }
        $many = count($libraries) > 1;
        foreach ($libraries as &$library) {
            $library['showname'] = $many;
            $library['loans'] = array_map(
                fn($loan) => $loan + ['when' => $str($loan['late'] ? 'late' : 'due', $loan['due'])],
                $library['loans']
            );
            $library['holds'] = array_map(fn($hold) => $hold + ['when' => $hold['ready'] ? $str('ready', $hold['until'])
                : ($hold['ahead'] ? $str('waitingahead', $hold['ahead']) : $str('waiting'))], $library['holds']);
            $library['hasloans'] = (bool) $library['loans'];
            $library['hasholds'] = (bool) $library['holds'];
        }
        unset($library);
        return [
            'libraries' => $libraries,
            'loansheading' => $str('loans'),
            'holdsheading' => $str('holds'),
            'none' => $str('none'),
            // Not «card» nor «spring»: those are the links of each library, and Mustache would find them first.
            'golibrarylabel' => $str('golibrary'),
            'cardlabel' => $str('card'),
            'springlabel' => $str('spring'),
        ];
    }
}
