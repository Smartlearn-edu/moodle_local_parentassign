<?php
namespace local_parentassign;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered when a user is created.
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        $userid = $event->objectid;
        manager::assign_parent($userid);
    }

    /**
     * Triggered when a user is updated.
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        $userid = $event->objectid;
        manager::assign_parent($userid);
    }
}
