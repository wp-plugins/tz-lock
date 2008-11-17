<?php
/*
Plugin Name: TZ-Lock Plugin
Plugin URI: http://blog.zvitek.net/index.php/tz-lock-wordpress-plugin/
Description: This plugin will enable you to protect only desired part of your post or comment (like pictures or a video for example).
Version: 0.5
Author: Tine Zorko
Author URI: mailto:tine.zorko@gmail.com

CHANGE LOG

Version 0.2 - Released 5.2.2008

- Now works well in IE
- JS added for advanced form submition

Version 0.3 - Released 21.3.2008

- No absolute path referencing (ABSPATH and PLUGINDIR defined constants used instead)
- Download folder changed to http://www.zvitek.net/downloads/tzplugin.zip
- Tested with the latest Wordpress MU platform

Version 0.4 - Released 8.4.2008

- no relative referencing in process***.php files

Version 0.5 - Released 17,11,2009

- WP database prefix attached to the table name

*/

define("TZLOCK_REGEXP", "/\[lock=(\w+)\](.*?)\[\/lock\]/ims");
define("TZLOCK_VERSION", "0.2");

$GLOBALS["tzlock"] = array();

function tzLockReplace($match)
{
  $lockid   = $match[1];
  $content  = $match[2];
  $pass     = $_COOKIE["tzlock_" . COOKIEHASH . "_" . $lockid];
  $GLOBALS["tzlock"]["lockid"]++; 
  if (isset($pass) && ($dbrow = $GLOBALS["wpdb"]->get_row("SELECT * FROM " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin WHERE lock_id='$lockid' AND lock_pass = '$pass'")));
    if ($dbrow) return $content;
  return getUnlockForm($lockid, $GLOBALS["tzlock"]["lockid"]);
}

function tzLockFilter($content) 
{
  return preg_replace_callback(TZLOCK_REGEXP, "tzLockReplace", $content);
}

function activateTZLock()
{
  $tblname = $GLOBALS["wpdb"]->prefix . "tzlock_plugin";
  $old_tblname = "tzlock_plugin";
  
  $GLOBALS["wpdb"]->show_errors();
  $GLOBALS["wpdb"]->flush();

  //check if old name exists, then rename
  if($GLOBALS["wpdb"]->get_var("SHOW TABLES LIKE '$old_tblname'"))
  {
    $GLOBALS["wpdb"]->query("RENAME TABLE $old_tblname TO $tblname;");
  } else
  {
    $sql = "CREATE TABLE $tblname 
    (
      lock_id    VARCHAR(64) NOT NULL,
      lock_pass  BINARY(41)  NOT NULL,
      PRIMARY KEY  (lock_id)
    ) COMMENT = 'tzlock plugin';";
    $GLOBALS["wpdb"]->query($sql);
  }
}

function deactivateTZLock()
{
  $sql = "DROP TABLE " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin";
  //do not drop ..
}

function addTZLockAdminMenu()
{
  add_options_page
  (
    "TZ-Lock administration",
    "TZ-Lock",
    "manage_options",
    __FILE__,
    "displayTZLockAdminContent"
  );
}

function displayTZLockAdminContent()
{
?>
<div class="wrap">
  <h2>TZ-Lock Administration</h2>
  <form name="tzlock_admin" action="<?=bloginfo('url') . '/' . PLUGINDIR . '/tzlock/process_admin.php'?>" method="POST">
  <input type="hidden" name="CONFIG" value="<?=ABSPATH . 'wp-config.php'?>"/>
  <table cellspacing="2" cellpadding="5" class="editform" align="center">
    <tr><td colspan="2"><h3>Novo geslo</h3></td></tr>
    <tr><td align="right">Oznaka gesla:</td><td><input type="text" name="lockid" value=""/></td></tr>
    <tr><td align="right">Geslo:</td><td><input type="password" name="pass" value=""/></td></tr>
    <tr><td colspan="2" align="right"><button type="button" onClick="tzlock_submit('tzlock_admin', 'add')">Dodaj</button></td></tr>
<?php
    
  $sql = "SELECT * FROM " . $GLOBALS["wpdb"]->prefix . "tzlock_plugin";
  $results = $GLOBALS["wpdb"]->get_results($sql);
  if ($results)
  {
?>
    <tr><td colspan="2"><h3>Obstoječa gesla</h3></td></tr>
    <tr><td colspan="2"><table border="0" cellspacing="0" cellpadding="4" width="100%">
<?php
    $i = 0;
    foreach($results as $row)
    {
      $class = $i%2 == 0 ? " class=\"alternate\"" : "";
?>    
      <tr><td width="100%"<?=$class?>><?=$row->lock_id?></td><td<?=$class?>><button type="button" onClick="tzlock_submit('tzlock_admin', 'del_<?=$row->lock_id?>')">Briši</button></td></tr>
<?php
      $i++;
    }
?>
    </td></tr></table>
<?php
  }
?>  
  </table>
  <input type="hidden" name="action"/>
  </form>
</div>
<?
}

function getUnlockForm
(
  $lockid,
  $seq,
  $message = "Ta del vsebine je zaklenjen!", 
  $prompt = "Geslo: ",
  $action = "Odkleni"
)
{
  $formName = "tzlock_" . $lockid . "_" . $seq;
  ob_start();
?>

<form method="post" name="<?=$formName?>" action="<?=bloginfo('url') . '/' . PLUGINDIR . '/tzlock/process.php'?>">
<input type="hidden" name="CONFIG" value="<?=ABSPATH . 'wp-config.php'?>"/>
<table border="0" class="tzlock_form" style="border: 1px solid #DDD" align="center">  
  <tr><td colspan="3"><?=$message?></td></tr>
  <tr><td>
    <label for="pass"><?=$prompt?></label>
  </td><td>
    <input type="password" name="pass" value=""/>
  </td><td>
    <a href="javascript:tzlock_submit('<?=$formName?>', 'auth')"><img src="<?=bloginfo('url') . '/' . PLUGINDIR . '/tzlock/unlock.gif'?>" border="0"/></a>
  </td></tr>
</table>
<input type="hidden" name="lockid" value="<?=$lockid?>"/>
<input type="hidden" name="action"/>
</form>

<?php
  $ret = ob_get_contents(); ob_end_clean();
  return $ret;
}

function addTZLockHeader()
{
  echo "<!-- Start Of Script Generated By TZ-Lock version " . TZLOCK_VERSION . " -->\n";
  wp_register_script("tzlock", "/" . PLUGINDIR . "/tzlock/tzlock.js", false, TZLOCK_VERSION);
  wp_print_scripts(array("tzlock"));
}

add_filter("the_content", "tzLockFilter");
add_filter("comment_text", "tzLockFilter");

register_activation_hook(__FILE__, "activateTZLock");
register_deactivation_hook(__FILE__, "deactivateTZLock");

add_action("admin_menu", "addTZLockAdminMenu");
add_action("wp_head", "addTZLockHeader");
add_action("admin_head", "addTZLockHeader");
?>