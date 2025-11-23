<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_created',
        'callback'  => 'local_parentassign\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback'  => 'local_parentassign\observer::user_updated',
    ],
];
