<?php
// Discord Clone - Çıkış
require_once '../includes/config.php';
require_once '../includes/auth.php';

logoutUser();
redirect('../index.php');
