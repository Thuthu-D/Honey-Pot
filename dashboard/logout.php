<?php
require_once __DIR__ . '/../config.php';
session_name(HP_SESSION_NAME);
session_start();
session_destroy();
header('Location: login.php');
exit;
