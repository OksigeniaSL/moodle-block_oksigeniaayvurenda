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
     * A reader sees their books out, the late ones in words, with the ways in; with nothing out, what the library tells
     * them; someone who is no reader gets the way in, and the desk where they lend; nothing for anyone else.
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
        $loan = $gen->create_loan(['libraryid' => $library->id, 'itemid' => $item->id, 'readerid' => $reader->id,
            'timedue' => $now - 3 * DAYSECS]);

        $data = content::for_user((int) $ana->id, $now);
        $this->assertCount(1, $data['centres']);
        $books = array_values(array_filter(array_column($data['centres'][0]['sections'], 'books')))[0];
        $this->assertSame('El faro dormido', $books['items'][0]['title']);
        $this->assertNotSame('', $books['items'][0]['late']);
        $html = $OUTPUT->render_from_template('block_oksigeniaayvurenda/content', $data);
        $this->assertStringContainsString('ayvb-due late', $html);
        $this->assertStringContainsString('card.php', $html);
        // Renewing from here only where the library lets its readers, and never a late one.
        $this->assertStringNotContainsString('renew.php', $html);
        $DB->set_field('local_oksigeniaayvurenda_loan', 'timedue', $now + 3 * DAYSECS, ['id' => $loan->id]);
        \local_oksigeniaayvurenda\local\holds::save_settings((int) $library->entityid, ['renewonline' => true]);
        $html = $OUTPUT->render_from_template('block_oksigeniaayvurenda/content', content::for_user((int) $ana->id, $now));
        $this->assertStringContainsString('renew.php', $html);
        $this->assertStringContainsString('ayvb-renew', $html);
        // With nothing out, what came last to the library, instead of «you have nothing».
        $DB->set_field('local_oksigeniaayvurenda_loan', 'timereturned', $now, ['id' => $loan->id]);
        $sections = content::for_user((int) $ana->id, $now)['centres'][0]['sections'];
        $this->assertNotEmpty(array_filter(array_column($sections, 'tell')));
        // Someone who is no reader but may use the library gets its way in, without a desk; the admin, its desk too.
        $teacher = $this->getDataGenerator()->create_user();
        $visit = content::for_user((int) $teacher->id, $now)['centres'][0];
        $this->assertStringContainsString('index.php', $visit['home']);
        $this->assertSame('', $visit['card']);
        $this->assertEmpty(array_filter(array_column($visit['sections'], 'desk')));
        $admin = content::for_user((int) get_admin()->id, $now)['centres'][0];
        $desk = array_values(array_filter(array_column($admin['sections'], 'desk')))[0];
        $this->assertStringContainsString('desk.php', $desk['libraries'][0]['desk']);
        // Whoever keeps a classroom library gets its desk, though it hangs from another.
        $room = $gen->create_library(['entityid' => $library->entityid, 'name' => 'Aula de 3.º', 'kind' => 'classroom',
            'parentid' => $library->id]);
        $DB->insert_record('local_oksigeniaayvurenda_keeper', ['libraryid' => $room->id, 'userid' => $teacher->id,
            'timecreated' => $now]);
        \cache::make('local_oksigeniaayvurenda', 'access')->purge();
        $gen->create_reader(['entityid' => $library->entityid, 'userid' => $teacher->id, 'readertype' => 'teacher']);
        \cache::make('local_oksigeniaayvurenda', 'access')->purge();
        $keeper = content::for_user((int) $teacher->id, $now)['centres'][0];
        $this->assertNotSame('', $keeper['card'], 'a keeper is a reader too');
        $desk = array_values(array_filter(array_column($keeper['sections'], 'desk')))[0];
        $this->assertSame(['Aula de 3.º'], array_column($desk['libraries'], 'name'));
        // A library getting ready for its staff only: neither its reader nor anyone else sees it; its admin does.
        \local_oksigeniaayvurenda\local\access::set_closed((int) $library->entityid, true);
        $this->assertNull(content::for_user((int) $ana->id, $now));
        $this->assertNull(content::for_user((int) $teacher->id, $now));
        $this->assertNotEmpty(content::for_user((int) get_admin()->id, $now)['centres']);
        \local_oksigeniaayvurenda\local\access::set_closed((int) $library->entityid, false);
        // Without the right to look at the library, nothing.
        $users = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('local/oksigeniaayvurenda:view', CAP_PROHIBIT, $users->id, \context_system::instance()->id, true);
        $this->assertNull(content::for_user((int) $this->getDataGenerator()->create_user()->id, $now));
    }
}
