<?php
namespace local_parentassign;

defined('MOODLE_INTERNAL') || die();

class manager {

    const PARENT_EMAIL_FIELD = 'parent_email';
    const PARENT_NAME_FIELD = 'parent_name';
    const PARENT_ROLE_SHORTNAME = 'parent'; // Assuming 'parent' is the shortname

    /**
     * Assigns a parent to the user based on custom profile fields.
     *
     * @param int $userid The student user ID.
     * @return void
     */
    public static function assign_parent($userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Load profile data.
        profile_load_data($user);

        // Get custom fields.
        // Note: profile_load_data puts data into $user object with 'profile_field_' prefix usually,
        // but for direct access it's safer to use profile_user_record or check the data structure.
        // Actually, profile_load_data attaches it to the user object.
        // Let's check how to access it reliably.
        // A safer way is to query the data directly if we know the shortname.
        
        $parent_email = self::get_profile_field_value($userid, self::PARENT_EMAIL_FIELD);
        $parent_name = self::get_profile_field_value($userid, self::PARENT_NAME_FIELD);

        if (empty($parent_email)) {
            return; // No parent email specified.
        }

        // Clean email.
        $parent_email = clean_param($parent_email, PARAM_EMAIL);
        if (!validate_email($parent_email)) {
            return; // Invalid email.
        }

        // Check if parent user exists.
        $parent_user = $DB->get_record('user', ['email' => $parent_email, 'deleted' => 0]);

        if (!$parent_user) {
            $parent_user = self::create_parent_user($parent_email, $parent_name);
        }

        if ($parent_user) {
            self::assign_role($user->id, $parent_user->id);
        }
    }

    /**
     * Helper to get profile field value.
     */
    private static function get_profile_field_value($userid, $shortname) {
        global $DB;
        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid AND f.shortname = :shortname";
        return $DB->get_field_sql($sql, ['userid' => $userid, 'shortname' => $shortname]);
    }

    /**
     * Creates a new parent user.
     */
    private static function create_parent_user($email, $name) {
        global $CFG;
        
        // Split name into first and last.
        $parts = explode(' ', trim($name));
        $firstname = array_shift($parts);
        $lastname = implode(' ', $parts);
        if (empty($firstname)) {
            $firstname = 'Parent';
        }
        if (empty($lastname)) {
            $lastname = 'User';
        }

        $user = new \stdClass();
        $user->auth = 'manual';
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->username = $email; // Use email as username.
        $user->password = 'TempPass123!'; // Should be randomized and forced change.
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->lang = $CFG->lang;

        try {
            $userid = user_create_user($user, false, false);
            return \core_user::get_user($userid);
        } catch (\Exception $e) {
            debugging('Error creating parent user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Assigns the parent role in the student's context.
     */
    private static function assign_role($studentid, $parentid) {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::PARENT_ROLE_SHORTNAME]);
        if (!$role) {
            debugging('Parent role not found: ' . self::PARENT_ROLE_SHORTNAME);
            return;
        }

        $context = \context_user::instance($studentid);

        if (!user_has_role_assignment($parentid, $role->id, $context->id)) {
            role_assign($role->id, $parentid, $context->id);
        }
    }
}
