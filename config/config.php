<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kathmandu');

define('SITE_NAME', 'CareTrack');

define('SITE_URL', 'http://localhost/caretrack');

define('UPLOAD_PATH', '../uploads/complaints/');

define('PROFILE_PATH', '../uploads/profiles/');