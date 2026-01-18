<?php
require __DIR__ . '/../notification_helper.php';
$c = get_email_config();
print_r(array('enabled' => $c['enabled'], 'host' => $c['smtp']['host'], 'user' => $c['smtp']['username']));
