<?php
namespace local_parentassign\task;

defined('MOODLE_INTERNAL') || die();

class process_parents extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('pluginname', 'local_parentassign');
    }

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
