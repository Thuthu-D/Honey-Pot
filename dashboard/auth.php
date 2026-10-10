<?php
require_once __DIR__ . '/../config.php';
session_name(HP_SESSION_NAME);
session_start();

if (empty($_SESSION['hp_authenticated'])) {
    header('Location: login.php');
    exit;
}
