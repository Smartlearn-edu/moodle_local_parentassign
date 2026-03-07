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
 * Local parent assign process parents task.
 *
 * @package     local_parentassign
 * @copyright   2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_parentassign\task;

/**
 * Scheduled task to process parent assignments.
 *
 * @package    local_parentassign
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_parents extends \core\task\scheduled_task
{
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_parentassign');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting parent assignment task...');

        // Get the field ID for parent_email.
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => \local_parentassign\manager::PARENT_EMAIL_FIELD]);

        if (!$fieldid) {
            mtrace('Parent email field not found. Exiting.');
            return;
        }

        // Get all users with a non-empty parent email.
        // This could be large, so we should use a recordset.
        $sql = "SELECT userid, data
                  FROM {user_info_data}
                 WHERE fieldid = :fieldid AND data IS NOT NULL AND data <> ''";

        $rs = $DB->get_recordset_sql($sql, ['fieldid' => $fieldid]);

        foreach ($rs as $record) {
            mtrace("Processing user {$record->userid}...");
            try {
                \local_parentassign\manager::assign_parent($record->userid);
            } catch (\Exception $e) {
                mtrace("Error processing user {$record->userid}: " . $e->getMessage());
            }
        }

        $rs->close();
        mtrace('Parent assignment task complete.');
    }
}
