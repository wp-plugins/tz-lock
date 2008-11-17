<?php

require_once($_POST["CONFIG"]);

$lockid   = $_POST["lockid"];
$pass = get_magic_quotes_gpc() ? stripslashes($_POST['pass']) : $_POST['pass'];

if ($dbrow = $GLOBALS["wpdb"]->get_row("SELECT * FROM " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin WHERE lock_id='$lockid' AND lock_pass = PASSWORD('$pass')"))
{
  // 10 days
  setcookie("tzlock_" . COOKIEHASH . "_" . $lockid, $dbrow->lock_pass, time() + 7200, COOKIEPATH);
}

wp_safe_redirect(wp_get_referer());
?>