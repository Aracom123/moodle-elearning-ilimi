<?php
require_once('../config.php');
require_login(); if (!is_siteadmin()) die('no');
purge_all_caches();
echo "ok"; @unlink(__FILE__);
