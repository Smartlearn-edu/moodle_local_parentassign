<h1 align="center">Moodle Local Plugin: Parent Assignment</h1>

<p align="center">
  <a href="https://github.com/Smartlearn-edu/moodle_local_parentassign/actions"><img src="https://github.com/Smartlearn-edu/moodle_local_parentassign/actions/workflows/ci.yml/badge.svg" alt="Moodle Plugin CI"></a>
  <a href="https://moodle.org"><img src="https://img.shields.io/badge/Moodle-4.1%2B-orange.svg" alt="Moodle 4.1+"></a>
  <a href="https://opensource.org/licenses/GPL-3.0"><img src="https://img.shields.io/badge/License-GPL%20v3-blue.svg" alt="License: GPL v3"></a>
</p>

This Moodle local plugin automatically assigns a **Parent** role to a user based on custom profile fields (`parent_email`, `parent_name`) located within the student's account.

If the specified parent account does not exist in the system, the plugin automatically creates one, generates a secure password, and emails the credentials directly to the parent!

---

## ✨ Features

* 🚀 **Event-Driven Assignment:** Instantly acts the exact moment a student account is created or updated.
* 🛡️ **Built-in Security:** Auto-generates cryptographically secure passwords for new parents.
* 📧 **Moodle Native Communications:** Standard welcome emails dispatch automatically containing the randomly generated temporary credentials.
* 🔒 **Forced Reset:** Mandatory password reset for parent accounts during their first login ensures absolute account privacy.
* ⚙️ **Fallback Scheduled Tasks:** Periodically sweeps for missed users, ideal for massive bulk uploads via the scheduled task system (`\local_parentassign\task\process_parents`).

## 📋 Prerequisites

Before installing the plugin, ensure the following setup is configured on your Moodle site:
1. **Moodle Version:** `4.1` or later.
2. **User Profile Fields:** Navigate to *Site Administration > Users > Accounts > User profile fields* and create two new Text Input fields:
   * Shortname: `parent_email`
   * Shortname: `parent_name`
3. **Parent Role:** A valid system role with the shortname `parent`.

## 📦 Installation

1. Copy the `local_parentassign` directory into the `local/` folder of your Moodle installation:
   ```bash
   git clone https://github.com/Smartlearn-edu/moodle_local_parentassign.git local/parentassign
   ```
2. Navigate to **Site administration > Notifications** as an administrator to invoke the installation process, or run the following CLI command:
   ```bash
   php admin/cli/upgrade.php
   ```

## 🚀 How it Works

The lifecycle of the plugin guarantees seamless parent onboarding:

1. **Student Registration:** An administrator, teacher, or external system registers a student, ensuring the `parent_email` and `parent_name` profile fields are populated.
2. **Account Synchronization:** The plugin intercepts the user creation event. If the parent's email already maps to a Moodle user, it links the student to them via the `parent` role.
3. **Auto-Provisioning:** If the parent account doesn't exist, an account is created on-the-fly using their `parent_name` and `parent_email`. 
4. **Welcome Email:** The parent immediately receives Moodle's standard welcome email containing their dynamically generated temporary 12-character password.
5. **Secure Authentication:** The parent logs in and is forced to configure a permanent password securely.

## 🔏 Privacy

Fully compliant with Moodle's 4.5+ Privacy API (`\local_parentassign\privacy\provider` implements `\core_privacy\local\metadata\null_provider`). 
This plugin stores absolutely no personal tracking data natively. It strictly maps existing core user entity data to facilitate architectural role assignments.

## 📄 License

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).
