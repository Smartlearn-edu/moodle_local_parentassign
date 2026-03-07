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
 * Local parent assign manager class.
 *
 * @package     local_parentassign
 * @copyright   2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_parentassign;

/**
 * Manager class for parent assignment logic.
 *
 * @package    local_parentassign
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager
{

    /** @var string Profile field for parent email. */
    const PARENT_EMAIL_FIELD = 'parent_email';

    /** @var string Profile field for parent name. */
    const PARENT_NAME_FIELD = 'parent_name';

    /** @var string Shortname of the parent role. */
    const PARENT_ROLE_SHORTNAME = 'parent';

    /**
     * Assigns a parent to the user based on custom profile fields.
     *
     * @param int $userid The student user ID.
     * @return void
     */
    public static function assign_parent($userid)
    {
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

        $parentemail = self::get_profile_field_value($userid, self::PARENT_EMAIL_FIELD);
        $parentname = self::get_profile_field_value($userid, self::PARENT_NAME_FIELD);

        if (empty($parentemail)) {
            return; // No parent email specified.
        }

        // Clean email.
        $parentemail = clean_param($parentemail, PARAM_EMAIL);
        if (!validate_email($parentemail)) {
            return; // Invalid email.
        }

        // Check if parent user exists.
        $parentuser = $DB->get_record('user', ['email' => $parentemail, 'deleted' => 0]);

        if (!$parentuser) {
            $parentuser = self::create_parent_user($parentemail, $parentname);
        }

        if ($parentuser) {
            self::assign_role($user->id, $parentuser->id);
        }
    }

    /**
     * Helper to get profile field value.
     *
     * @param int $userid The user ID.
     * @param string $shortname The profile field shortname.
     * @return mixed The field value.
     */
    private static function get_profile_field_value($userid, $shortname)
    {
        global $DB;
        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid AND f.shortname = :shortname";
        return $DB->get_field_sql($sql, ['userid' => $userid, 'shortname' => $shortname]);
    }

    /**
     * Creates a new parent user.
     *
     * @param string $email The parent email.
     * @param string $name The parent name.
     * @return \core_user|\stdClass|null The created user object or null on failure.
     */
    private static function create_parent_user($email, $name)
    {
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

        $temppassword = generate_password(12);

        $user = new \stdClass();
        $user->auth = 'manual';
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->username = $email; // Use email as username.
        $user->password = $temppassword;
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->lang = $CFG->lang;

        try {
            $userid = user_create_user($user, true, false);
            $newuser = \core_user::get_user($userid);

            // Force password change on first login.
            set_user_preference('auth_forcepasswordchange', 1, $newuser);

            // Email the temporary password to the parent.
            $site = get_site();
            $supportuser = \core_user::get_support_user();

            $data = new \stdClass();
            $data->firstname = $newuser->firstname;
            $data->lastname  = $newuser->lastname;
            $data->sitename  = format_string($site->fullname);
            $data->username  = $newuser->username;
            $data->newpassword = $temppassword;
            $data->link      = $CFG->wwwroot . '/login/';
            $data->signoff   = generate_email_signoff();

            $message = get_string('newusernewpasswordtext', '', $data);
            $subject = get_string('newusernewpasswordsubject', '', format_string($site->fullname));

            email_to_user($newuser, $supportuser, $subject, $message);

            return $newuser;
        } catch (\Exception $e) {
            debugging('Error creating parent user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Assigns the parent role in the student's context.
     *
     * @param int $studentid The student user ID.
     * @param int $parentid The parent user ID.
     * @return void
     */
    private static function assign_role($studentid, $parentid)
    {
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
