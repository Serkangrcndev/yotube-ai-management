<?php
session_start();
require_once 'includes/auth.php';

$auth = new GoogleAuth();
$auth->logout();
?> 