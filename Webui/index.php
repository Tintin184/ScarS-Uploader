<?php
// include the settings
include_once("./settings.php");

// settings: name (string) => info (array: required (bool), default (mixed))
$_settings = array(
    "pass"      => array(
        "required" => true
    ),
    "data"      => array(
        "required" => true
    ),
    "datasort"  => array(
        "required" => false,
        "default"  => $default["datasort"]
    ),
    "dataorder" => array(
        "required" => false,
        "default"  => $default["dataorder"]
    ),
    "theme"     => array(
        "required" => false,
        "default"  => $default["theme"]
    ),
    "sizes"     => array(
        "required" => false,
        "default"  => $default["sizes"]
    ),
    "debug"     => array(
        "required" => false,
        "default"  => 0
    ),
    "sites"     => array(
        "required" => false,
        "default"  => ""
    ),
    "sitesort"  => array(
        "required" => false,
        "default"  => $default["sitesort"]
    ),
    "catsort"  => array(
        "required" => false,
        "default"  => $default["catsort"]
    )
);

// add trailing to homedir slash if needed
if (substr($homedir, -1) != "/")
    $homedir .= "/";

// function to parse the user webui file
function parse_settings($user) {
    global $homedir, $_settings;
    $settings = array("uploader" => $homedir . $user . "/upload/");

    // check if user uploader dir exists
    if (!is_dir($settings["uploader"]))
       return false;

    // check if the user's webui file exists and is readable
    if (!file_exists($settings["uploader"] . ".webui.rc") || !is_readable($settings["uploader"] . ".webui.rc"))
        return false;

    // read the file into an array
    $lines = file($settings["uploader"] . ".webui.rc", FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines) || empty($lines))
        return false;

    // parse each line
    foreach ($lines as $line) {
        // find the first equal sign
        if ($line[0] == "#" || ($pos = strpos($line, "=")) === false)
            continue;

        // get the setting name and check if it's a valid setting
        $setting = trim(substr($line, 0, $pos));
        if (array_key_exists($setting, $_settings)) {
            // get the value of the setting
            $settings[$setting] = trim(substr($line, $pos + 1));
            if (!empty($settings[$setting]))
                unset($_settings[$setting]);
        }
    }

    // look if any settings have been missed and if they are required and what their default is
    foreach ($_settings as $setting => $info)
        if ($info["required"])
            return false;
        else
            $settings[$setting] = $info["default"];

    // check if the user name is part of the data dir to make sure it's not some random dir being listed
    if (!strpos($settings["data"], $user))
        return false;

    // add trailing slash if needed
    if (substr($settings["data"], -1) != "/")
        $settings["data"] .= "/";

    // return the settings array
    return $settings;
}

// function for alphabetic sorting
function alphabetically($a, $b) {
    global $order;

    // check for hidden or uploaded releases and sort them to the bottom
    if ($a["status"] != $b["status"] && ($a["status"] == "uploaded" || $b["status"] == "uploaded" || $a["status"] == "hidden" || $b["status"] == "hidden"))
        return ($a["status"] == "uploaded" || $a["status"] == "hidden" ? 1 : 0);
    elseif (strtolower($order) == "asc" || strtolower($order) == "ascending")
        return strcmp(strtolower($b["name"]), strtolower($a["name"]));
    else
        return strcmp(strtolower($a["name"]), strtolower($b["name"]));
}

// function for sorting by latest
function latest($a, $b) {
    global $user, $order;

    // check for hidden or uploaded releases and sort them to the bottom
    if ($a["status"] != $b["status"] && ($a["status"] == "uploaded" || $b["status"] == "uploaded" || $a["status"] == "hidden" || $b["status"] == "hidden"))
        return ($a["status"] == "uploaded" || $a["status"] == "hidden" ? 1 : 0);
    elseif (strtolower($order) == "asc" || strtolower($order) == "ascending")
        return filectime($user["data"] . $a["name"]) > filectime($user["data"] . $b["name"]);
    else
        return filectime($user["data"] . $b["name"]) > filectime($user["data"] . $a["name"]);
}

// function to get the XiB from bytes
function get_XiB($bytes) {
    $sizes = array("KiB", "MiB", "GiB", "TiB");
    while ($bytes >= 1024) {
        $bytes /= 1024;
        next($sizes);
    }

    return round($bytes, 2) . " " . current($sizes);
}

// start/load the PHP session
session_start();
$user = null;
$errors = array();

// logout action: clear the cookie and session variables and reload the page
if ($_GET["action"] == "logout") {
    setcookie("user", null, time() - 3600);
    setcookie("pass", null, time() - 3600);
    unset($_COOKIE["user"], $_COOKIE["pass"], $_SESSION["user"], $_SESSION["pass"]);
    header("Location: " . str_replace("index.php", "", $_SERVER["PHP_SELF"]));
}

// check for valid session variables to authenticate with
if (!empty($_SESSION["user"]) && !empty($_SESSION["pass"]) && ($settings = parse_settings($_SESSION["user"])) !== false) {
    // check if session password is correct, if not clear the session variables
    if ($_SESSION["pass"] == sha1($settings["pass"]))
        $user = $settings;
    else
        unset($_SESSION["user"], $_SESSION["pass"]);
// if invalid session variables exist clear them
} elseif (!empty($_SESSION["user"]) || !empty($_SESSION["user"]))
    unset($_SESSION["user"], $_SESSION["pass"]);

// if no valid session has been found check for valid cookie variables to authenticate with
if ($user === null && !empty($_COOKIE["user"]) && !empty($_COOKIE["pass"]) && ($settings = parse_settings($_COOKIE["user"])) !== false) {
    // check if cookie password is correct, if not clear the cookie variables
    if ($_COOKIE["pass"] == sha1($settings["pass"])) {
        $_SESSION["user"] = $_COOKIE["user"];
        $_SESSION["pass"] = $_COOKIE["pass"];
        $user = $settings;
    } else {
        setcookie("user", null, time() - 3600);
        setcookie("pass", null, time() - 3600);
        unset($_COOKIE["user"], $_COOKIE["pass"]);
    }
// if invalid cookies exist clear them
} elseif (!empty($_COOKIE["user"]) || !empty($_COOKIE["user"])) {
    setcookie("user", null, time() - 3600);
    setcookie("pass", null, time() - 3600);
    unset($_COOKIE["user"], $_COOKIE["pass"]);
}

// post action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // when not logged in check for login action
    if ($user === null) {
        // check if the user exists
        if (!empty($_POST["username"]) && ($settings = parse_settings($_POST["username"])) !== false) {
            // check if the posted password is correct
            if ($_POST["password"] == $settings["pass"]) {
                $_SESSION["user"] = $_POST["username"];
                $_SESSION["pass"] = sha1($_POST["password"]);
                $user = $settings;

                // if the user wants to stay logged in set the cookies
                if ($_POST["remember"]) {
                    setcookie("user", $_POST["username"], 0x7fffffff);
                    setcookie("pass", sha1($_POST["password"]), 0x7fffffff);
                }

                header("Location: " . str_replace("index.php", "", $_SERVER["PHP_SELF"]));
            } else
                $invalid = true;
        } else
            $invalid = true;
    // handle calls to upload wrapper
    } else {
        // check if the file or directory exists
        if (!file_exists($user["data"] . $_POST["release"]))
            $errors[] = "<strong>" . htmlspecialchars($_POST["release"]) . "</strong> does not exist in <strong>" . htmlspecialchars($user["data"]) . "</strong>";

        // start building the uploadwrap command
        $cmd = "sudo -u " . $_SESSION["user"] . " '" . $user["uploader"] . "wrapper' webui";

        // check if a release needs to be reset
        if (empty($errors) && $_POST["reset"] == "Reset") {
            $cmd .= " reset " . escapeshellarg($user["data"] . $_POST["release"]);

            // check if the user wants to debug or execute
            if ($user["debug"])
                $errors[] = $cmd;
            else {
                exec($cmd, $unused, $ret);

                // check if the command succeeded
                if ($ret)
                    $errors[] = "reset command failed. check if <strong>" . $user["uploader"] . "wrapper</strong> and <strong>/etc/sudoers</strong> have the correct information";
                else
                    header("Location: " . str_replace("index.php", "", $_SERVER["PHP_SELF"]));
            }

        // check if a release needs to be hidden
        } elseif (empty($errors) && $_POST["category"] == "hide") {
            $cmd .= " hide " . escapeshellarg($user["data"] . $_POST["release"]);

            // check if the user wants to debug or execute
            if ($user["debug"])
                $errors[] = $cmd;
            else {
                exec($cmd, $unused, $ret);

                // check if the command succeeded
                if ($ret)
                    $errors[] = "hide command failed. check if <strong>" . $user["uploader"] . "wrapper</strong> and <strong>/etc/sudoers</strong> have the correct information";
                else
                    header("Location: " . str_replace("index.php", "", $_SERVER["PHP_SELF"]));
            }

        // upload a release
        } else {
            // check for valid site
            if (!array_key_exists($_POST["site"], $sites))
                $errors[] = "site <strong>" . htmlspecialchars($site) . "</strong> is not a valid site";

            // check for a valid category
            if (!in_array($_POST["category"], $sites[$_POST["site"]]))
                $errors[] = "category <strong>" . htmlspecialchars($_POST["category"]) . "</strong> is not a valid category";

            // check if the release has already been uploaded
            if (file_exists($user["data"] . $_POST["release"] . "/.uploaded"))
                $errors[] = "you have already uploaded <strong>" . htmlspecialchars($_POST["release"]) . "</strong>";

            // check for a (valid) piece size
            if (!empty($_POST["pieces"]) && (!is_numeric($_POST["pieces"]) || $_POST["pieces"] < 15 || $_POST["pieces"] > 28))
                $errors[] = "you entered an invalid piece size";

            if (empty($errors)) {
                $cmd .= " " . escapeshellarg("." . strtolower($_POST["site"]) . ".rc") . " " . escapeshellarg($_POST["category"]) . " " . escapeshellarg($_POST["release"]) . (empty($_POST["pieces"]) ? "" : " " . $_POST["pieces"]) . " " . escapeshellarg($_POST["tags"]);

                // check if the user wants to debug or execute
                if ($user["debug"])
                    $errors[] = $cmd;
                else {
                    exec($cmd, $unused, $ret);
                    sleep(1);

                    // check if the command succeeded
                    if ($ret)
                        $errors[] = "upload command failed. check if <strong>" . $user["uploader"] . "wrapper</strong> and <strong>/etc/sudoers</strong> have the correct information";
                    else
                        header("Location: " . str_replace("index.php", "", $_SERVER["PHP_SELF"]));
                }
            }
        }
    }
}

// if the user is logged in get the data dir contents
if ($user !== null) {
    $releases = array();
    $numerr = count($errors);

    // check if the data dir exists
    if (!is_dir($user["data"]))
        $errors[] = "<strong>" . htmlspecialchars($user["data"]) . "</strong> is not a directory";

    // check if the data dir is readable
    if (empty($errors) && !is_readable($user["data"]))
        $errors[] = "<strong>" . htmlspecialchars($user["data"]) . "</strong> is not readable";

    // open the data dir
    if (count($errors) == $numerr) {
        $handle = @opendir($user["data"]);

        if ($handle === false)
            $errors[] = "unable to open <strong>" . htmlspecialchars($user["data"]) . "</strong>";
        else {
            // start reading the contents of the data dir
            while (($release = readdir($handle)) !== false) {
                // skip hidden files and dirs
                if ($release[0] == ".")
                   continue;

                // check if there are already any upload actions done in the dir
                $status = "normal";
                $addclass = "";
                if (file_exists($user["data"] . $release . "/.hidden")) {
                    $status = "hidden";
                    $addclass = " hidden";
                } elseif (file_exists($user["data"] . $release . "/.uploaded")) {
                    $status = "uploaded";
                    $addclass = " uploaded";
                } elseif (file_exists($user["data"] . $release . "/.uploading")) {
                    $status = "uploading";
                    $addclass = " uploading";
                } elseif (file_exists($user["data"] . $release . "/.uploaderror")) {
                    $lines = file($user["data"] . $release . "/.uploaderror");
                    $status = trim($lines[0]);
                    $addclass = ($status == "login" || $status == "dupe" ? " error" : "");
                }

                // calculate the dir size if needed
                $size = 0;
                if ($user["sizes"]) {
                    $size = exec("du -s " . escapeshellarg($user["data"] . $release) . " | cut -f 1");
                    // skip dirs that empty
                    if ($size < 5)
                        continue;
                }

                // fill the array with releases
                $releases[] = array("name" => $release, "status" => $status, "size" => get_XiB($size), "addclass" => $addclass);
            }

            // close the directory handle again
            closedir($handle);
        }
    }

    // check if any special sorting is needed
    if ($_GET["a"]) {
        $sort = "alphabetically";
        $order = ($_GET["a"] == "a" ? "asc" : "desc");
    } elseif ($_GET["d"]) {
        $sort = "latest";
        $order = ($_GET["d"] == "a" ? "asc" : "desc");
    } else {
        $sort = $user["datasort"];
        $order = $user["dataorder"];
    }

    // perform the sorting
    usort($releases, $sort);

    // build the piece size dropdown
    $sizesdd = "<option value=\"\">auto</option>\n";
    for ($i = 15; $i <= 28; $i++)
        $sizesdd .= "                            <option value=\"" . $i . "\">[" . $i . "] " . get_XiB(pow(2, $i) / 1024) . "</option>\n";

    // sort the sites if needed
    if ($user["sitesort"])
        ksort($sites);

    // get the categories for the site and sort them if needed
    $categories = current($sites);
    if ($user["catsort"])
        if (is_numeric(current($categories)))
            ksort($categories);
        else
            sort($categories);

    // build the sites dropdown
    $listsites = array_map("strtolower", array_map("trim", explode(",", $user["sites"])));
    $sitesdd = "<option value=\"\">Select site</option>\n";
    foreach ($sites as $site => $categories)
        if (empty($listsites) || in_array(strtolower($site), $listsites))
            $sitesdd .= "                            <option value=\"" . str_replace("\"", "\\\"", $site) . "\">" . htmlspecialchars($site) . "</option>\n";

    // build the category dropdown
    $catsdd = "<option value=\"\">Select site</option>\n";
    $catsdd .= "                            <option value=\"hide\">Hide release</option>\n";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?php echo ($user === null ? "Login" : "ScarS' bash torrent uploader (" .$_SESSION["user"] . ")"); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?
if ($user === null) {
?>
        <link rel="stylesheet" type="text/css" href="./login.css" />
<?
} else {
?>
        <meta http-equiv="refresh" content="600" />
        <link rel="stylesheet" type="text/css" href="./<?php echo $user["theme"]; ?>.css" />
        <script type="text/javascript" src="./ajax.js"></script>
        <script type="text/javascript">
        //<![CDATA[
        var catsort = <?php echo $user["catsort"]; ?>;
        //]]>
        </script>
<?
}
?>
    </head>
    <body>
<?php
if ($user === null) {
?>
        <h1>Login:</h1>
<?php
    if ($invalid) {
?>
        <ul>
            <li>Invalid login</li>
        </ul>
<?php
    }
?>
        <div id="main">
            <form method="post" action="">
                <div class="input">
                    <label for="username">User:</label>
                    <input id="username" type="text" name="username" />
                </div>
                <div class="input">
                    <label for="password">Pass:</label>
                    <input id="password" type="password" name="password" />
                </div>
                <div class="input">
                    <label for="remember">Remember:</label>
                    <input type="checkbox" id="remember" name="remember" value="1" />
                </div>
                <div class="clear"></div>
                <div>
                    <input type="submit" value="Login" />
                </div>
            </form>
        </div>
<?php
} else {
?>
        <h1>ScarS' bash torrent uploader WebUI (<?php echo $_SESSION["user"]; ?>)</h1>

<?php
    if (!empty($errors)) {
?>
        <ul>
<?php
        foreach ($errors as $error) {
?>
            <li><?php echo $error; ?></li>
<?php
        }
?>
        </ul>
<?php
    }
?>
        <div id="main">
            <div id="top">
                <span>[<a href="?action=logout">logout</a>]</span>
                <div id="sorting">
                    <span>Sort by:</span>
                    <span>[<a href="?d=d">date descending</a>]</span>
                    <span>[<a href="?d=a">date ascending</a>]</span>
                    <span>[<a href="?a=d">alphabetically descending</a>]</span>
                    <span>[<a href="?a=a">alphabetically ascending</a>]</span>
                </div>
                <div class="clear"></div>
            </div>
<?php
    $i = 0;
    foreach ($releases as $release) {
        $esc = str_replace("\"", "\\\"", $release["name"]);
        $title = $esc . ($release["size"] ? " - " . $release["size"] : "");
        $rls = ($release["addclass"] == " error" ? "[" . $release["status"] . "] " : "") . $release["name"];
        $i++;
?>
            <div class="torrent">
                <div class="name<?php echo $release["addclass"]; ?>" title="<?php echo $title; ?>"><?php echo $rls; ?></div>
<?php
        if ($release["status"] == "normal") {
?>
                <form id="form<?php echo $i; ?>" method="post" action="">
                    <div>
                        <input type="hidden" name="release" value="<?php echo $esc; ?>" />
                        <select name="pieces" title="<?php echo $esc; ?>">
                            <?php echo $sizesdd; ?>
                        </select>
                        <select name="site" title="<?php echo $esc; ?>">
                            <?php echo $sitesdd; ?>
                        </select>
                        <select class="wide" name="category" title="<?php echo $esc; ?>">
                            <?php echo $catsdd; ?>
                        </select>
                        <input type="text" name="tags" title="tags (optional): <?php echo $esc; ?>" value="" />
                        <input type="submit" value="Upload" title="<?php echo $esc; ?>" />
                    </div>
                </form>
<?php
        } else {
?>
                <form method="post" action="?">
                    <div class="upload">
                        <input type="hidden" name="release" value="<?php echo $esc; ?>" />
                        <input type="submit" name="reset" value="Reset" title="<?php echo $esc; ?>" />
                    </div>
                </form>
<?php
        }
?>
                <div class="clear"></div>
            </div>
<?php
    }
?>
        </div>
<?php
}
?>
    </body>
</html>
