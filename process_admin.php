<?php

require($_POST["CONFIG"]); //wp-config file

$action = $_POST["action"];
$id   = $_POST["lockid"];
$pass = get_magic_quotes_gpc() ? stripslashes($_POST['pass']) : $_POST['pass'];

if ($action == "add")
{
  if ($GLOBALS["wpdb"]->query("INSERT INTO " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin VALUES ('$id', PASSWORD('$pass'))") === FALSE)
  {
    /*TODO: error handling (already exist or empty password)*/
  };
}
else if (substr($action, 0, 4) == "del_")
{
  $id = substr($action, 4);
  if ($GLOBALS["wpdb"]->query("DELETE FROM " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin WHERE lock_id = '$id'") === FALSE)
  {
    /*TODO: error handling*/  
  }
}

wp_safe_redirect(wp_get_referer());
?>