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

namespace block_oksigeniaayvurenda;

/**
 * Tests for what the library block shows.
 *
 * @package    block_oksigeniaayvurenda
 * @category   test
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_oksigeniaayvurenda\content
 */
#[\PHPUnit\Framework\Attributes\CoversClass(content::class)]
final class content_test extends \advanced_testcase {
    /**
     * A reader sees their books out, the late ones marked, with the ways in; someone who is no reader sees nothing.
     */
    public function test_for_user(): void {
        global $DB, $OUTPUT;
        $this->resetAfterTest();
        /** @var \local_oksigeniaayvurenda_generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('local_oksigeniaayvurenda');
        $library = $gen->create_library();
        $now = time();
        $ana = $this->getDataGenerator()->create_user();
        $reader = $gen->create_reader(['entityid' => $library->entityid, 'userid' => $ana->id, 'readertype' => 'student']);
        $record = $gen->create_record(['entityid' => $library->entityid, 'title' => 'El faro dormido']);
        $item = $gen->create_item(['entityid' => $library->entityid, 'libraryid' => $library->id, 'recordid' => $record->id]);
        $gen->create_loan(['libraryid' => $library->id, 'itemid' => $item->id, 'readerid' => $reader->id,
            'timedue' => $now - DAYSECS]);

        $data = content::for_user((int) $ana->id, $now);
        $this->assertCount(1, $data['libraries']);
        $loan = $data['libraries'][0]['loans'][0];
        $this->assertSame('El faro dormido', $loan['title']);
        $this->assertTrue($loan['late']);
        $html = $OUTPUT->render_from_template('block_oksigeniaayvurenda/content', $data);
        $this->assertStringContainsString('block-oksigeniaayvurenda-late', $html);
        $this->assertStringContainsString('card.php', $html);
        // The button says its word, not the address of the card.
        $this->assertStringContainsString('>' . get_string('card', 'block_oksigeniaayvurenda') . '</a>', $html);
        // Renewing from here only where the library lets its readers.
        $this->assertStringNotContainsString('renew.php', $html);
        \local_oksigeniaayvurenda\local\holds::save_settings((int) $library->entityid, ['renewonline' => true]);
        $html = $OUTPUT->render_from_template('block_oksigeniaayvurenda/content', content::for_user((int) $ana->id, $now));
        $this->assertStringContainsString('renew.php', $html);
        $this->assertStringContainsString('>' . get_string('renew', 'block_oksigeniaayvurenda') . '</button>', $html);
        // Someone who is no reader but may use the library gets its way in; the admin, its desk too.
        $teacher = $this->getDataGenerator()->create_user();
        $visit = content::for_user((int) $teacher->id, $now);
        $this->assertSame([], $visit['libraries']);
        $this->assertStringContainsString('index.php', $visit['visits'][0]['home']);
        $this->assertSame('', $visit['visits'][0]['desk']);
        $this->assertStringContainsString('desk.php', content::for_user((int) get_admin()->id, $now)['visits'][0]['desk']);
        // A library getting ready for its staff only: neither its reader nor anyone else sees it; its admin does.
        \local_oksigeniaayvurenda\local\access::set_closed((int) $library->entityid, true);
        $this->assertNull(content::for_user((int) $ana->id, $now));
        $this->assertNull(content::for_user((int) $teacher->id, $now));
        $this->assertNotEmpty(content::for_user((int) get_admin()->id, $now)['visits']);
        \local_oksigeniaayvurenda\local\access::set_closed((int) $library->entityid, false);
        // Without the right to look at the library, nothing.
        $users = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('local/oksigeniaayvurenda:view', CAP_PROHIBIT, $users->id, \context_system::instance()->id, true);
        $this->assertNull(content::for_user((int) $this->getDataGenerator()->create_user()->id, $now));
    }
}
