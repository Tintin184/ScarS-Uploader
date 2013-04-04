<?php
// start/load the PHP session
session_start();

// show empty page if a session doesn't exist or if a site or id wasn't supplied
if (empty($_SESSION["user"]) || empty($_GET["site"]))
    die();

// include the settings
include_once("./settings.php");

// show empty page if the site doesn't exist in the sites array
if (!array_key_exists($_GET["site"], $sites))
    die();

// get the categories for the site and sort them if needed
$categories = $sites[$_GET["site"]];
if ($_GET["catsort"])
        sort($categories);

// build category options
$cats = "<option value=\"\">Select a category</option>";
foreach ($categories as $key => $val)
        $cats .= "<option value=\"" . str_replace("\"", "\\\"", $val) . "\">" . htmlspecialchars($val) . "</option>";

// add the hide option
$cats .= "                            <option value=\"hide\">Hide release</option>\n";

// return category options
echo $cats;
?>
