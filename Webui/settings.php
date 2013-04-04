<?php
/**
 * make sure to have the following line in your /etc/sudoers file (edit with the `visudo' command) for each user:
 * www-data ALL=(user) NOPASSWD: /home/user/uploader/upload
 *
 * combining for mulitple users on a single line can be done as following:
 * www-data ALL=(user1, user2) NOPASSWD: /home/user1/uploader/upload, /home/user2/uploader/upload
 */

// path to homedir
$homedir = "/home";

// list of sites and categories: site name or abbreviation (string) => categories (array: category (string))
// note: the dropdowns have the same order as entered here unless setting catsort is true
$sites = array(
    "eXa"   => array(
        "TV/XviD", "TV/HD", "TV/Pack",
        "Movies/XviD", "Movies/DVDR", "Movies/BluRay", "Movie/Pack",
        "Games/PC", "Games/Xbox", "Games/PSX", "Games/Wii", "Games/Misc", "Games/Pack",
        "Music/MP3", "Music/FLAC", "Music/Video", "Music/Misc", "Music/Pack",
        "0day", "Ebook", "Misc"
    ),
    "TV"  => array(
        "Appz/0DAY", "Appz/Mac", "Appz/PC-ISO",
        "Game/Packs", "Games/Misc", "Games/NDS", "Games/PC-ISO", "Games/PS3", "Games/PSP", "Games/Wii", "Games/X360",
        "Documentaries", "Episodes/TV-Boxset", "BlurayTV", "Episodes/TV-DVDR", "Episodes/TV-Foreign", "Episodes/TV-XviD", "Episodes/TV-x264",
        "Movies/Boxsets", "Movies/DVDR", "Movies/Foreign", "Movies/MDVDR", "Movies/XviD", "Movies/x264",
        "Packs/Music", "Music/MP3", "Music/Video", "Retro/Music",
        "Packs/0DAY", "Packs/Ebooks", "Requests", "Ebooks"
    ),
    "TD"  => array(
        "Appz/Mac", "Appz/Misc", "Appz/PC DOX", "Appz/PC ISO",
        "Games/Handheld", "Games/PC DOX", "Games/PC ISO", "Games/PC Rip", "Games/PSX", "Games/WII", "Games/XBOX",
        "TV/DVD", "TV/HD", "TV/XviD",
        "Movies/DVD", "Movies/HD", "Movies/MP4", "Movies/XviD",
        "XXX/DVD", "XXX/HD", "XXX/XviD",
        "Music/CD", "Music/Single", "Music/Video",
        "Anime/Hentai", "Anime/Manga", "Anime/Toon"
    ),
    "AO" => array(
        "Movies|HD", "Movies|XviD", "Movies|DVDR", "Movies|Pack", "Movies|Pack-HD", "Movies|XXX",
        "TV|XviD", "TV|HD", "TV|DVDR", "TV|Pack", "TV|Pack-HD",
        "Apps|PC", "Apps|MAC", "Apps|Nix", "Apps|Mobile",
        "Games|PC", "Games|Xbox", "Games|PS3", "Games|Wii",
        "E-Books", "Misc."
    )
);

// default theme: light / dark / custom
// note: this will load light.css / dark.css / custom.css
$default["theme"] = "light";

// default show directory size on hover: 1 = yes, 0 = no
$default["sizes"] = 0;

// default directory sorting type: latest / alphabetically (string)
$default["datasort"] = "latest";

// default directory sorting order: asc[ending] / desc[ending] (string)
$default["dataorder"] = "desc";

// default sort sites alphabetically: 1 = yes, 0 = no
$default["sitesort"] = 0;

// default sort categories alphabetically: 1 = yes, 0 = no
$default["catsort"] = 0;
?>
