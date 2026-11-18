<?php
require_once __DIR__.'/../config.php';
require_login();
csrf_check();
$branchId = selected_branch_id($pdo);
