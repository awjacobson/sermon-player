<?php
/*
Plugin Name: Sermon Player
Description: Renders a page for listening to sermons.
Version: 1.0.5
Author: Aaron Jacobson
Author URI: http://www.aaronjacobson.com
License:     GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2016 Aaron Jacobson (email : awjacobson@aaronjacobson.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// if this file is called directly, abort
if(!defined('WPINC')) {
    die;
}

include_once('metabox.php');
$sermonplayer_metabox = new SermonPlayerMetabox();
$sermonplayer_messages = null;

if(substr($_SERVER['REQUEST_URI'], 0, 8) === '/listen/')
{
    require_once($_SERVER['DOCUMENT_ROOT']."/../api/dbconfig.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/../api/repository/messageDao.php");
    $sermonplayer_messages = sermonplayer_getmessages($_REQUEST["sermon"]);

    add_shortcode("sermonplayer", "sermonplayer");
    add_filter('language_attributes', 'sermonplayer_doctype_opengraph');
    add_action('wp_enqueue_scripts', 'sermonplayer_register_scripts');
    add_action('wp_head','sermonplayer_build_head');
}

function sermonplayer() {
	global $sermonplayer_messages;
    $messageId = $_REQUEST["sermon"];

    if(isset($messageId) && $messageId === "archives") {
        $html = '<div class="content"><div class="episodes">' . renderArchives($sermonplayer_messages) . '</div></div>';
    }
    else {
        $html = renderContent($sermonplayer_messages);
    }

    echo $html;
}

/**
 * https://codex.wordpress.org/Function_Reference/register_post_type
 */
function sermonplayer_register_post_type() {
    global $sermonplayer_metabox;
    register_post_type("sermon", [
        "labels" => [
            "name" => __("Sermons"),
            "singular_name" => __("Sermon"),
            "description" => "",
        ],
        "public" => true,
        "menu_icon" => 'dashicons-controls-volumeon',
        "has_archive" => true,
        "rewrite" => [
            "slug" => "listen"
        ],
        'register_meta_box_cb' => array($sermonplayer_metabox, 'register')
    ]);
}
add_action("init", "sermonplayer_register_post_type");

function sermonplayer_getmessages($messageId) {
    global $cornerstone_dbHost, $cornerstone_dbUserLogin, $cornerstone_dbPassword, $cornerstone_dbName;
    $dao = new MessageDao($cornerstone_dbHost, $cornerstone_dbUserLogin, $cornerstone_dbPassword, $cornerstone_dbName);

    if(isset($messageId)) {
        if($messageId == "archives") {
            $messages = $dao->GetAllMessagesForListeners();
        }
        else {
            $messages = array($dao->GetMessageById($messageId));
            $episodes = $dao->GetLatest(4);
            foreach ($episodes as &$message) {
                if($message->id != $messageId) {
                    array_push($messages, $message);
                }
            }
        }
    }
    else {
        $messages = $dao->GetLatest(4);
    }
    return $messages;
}

function sermonplayer_doctype_opengraph($output) {
    return $output . '
    xmlns:og="http://opengraphprotocol.org/schema/"
    xmlns:fb="http://www.facebook.com/2008/fbml"';
}

/**
 * Write to the head section.
 */
function sermonplayer_build_head() {
    global $sermonplayer_messages;

    if(!isset($sermonplayer_messages)) {
        return;
    }
    $message = $sermonplayer_messages[0];
    $url = "http://www.cornerstonejeffcity.org/listen/?sermon=" . $message->id;
    $messageDate = date("F j, Y",strtotime($message->date));
    $title = htmlspecialchars($message->title,ENT_COMPAT);
    $description = htmlspecialchars($message->description,ENT_COMPAT);
    $image = sermonplayer_getImageUrl($message, true);
    $html = <<<HTML
<meta property="og:url" content="$url"/>
<meta property="og:type" content="article" />
<meta property="og:title" content="$title" />
<meta property="og:description" content="$messageDate - $description" />
<meta property="og:image" content="$image" />
<meta property="og:image:width" content="200" />
<meta property="og:image:height" content="200" />
HTML;
    echo $html;
}

function sermonplayer_register_scripts() {
    wp_enqueue_script('facebook-jssdk', '//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8', null, false, false);
    wp_enqueue_style('sermon-player-style', plugin_dir_url( __FILE__ ) . 'public/css/style.css');
}

function renderContent($messages) {
    $message = $messages[0];
    $player = renderPlayer($message);
    $episodes = renderEpisodes(array_slice($messages, 1, 3));
    $message = $messages[0];
    $html=<<<HTML
<div id="fb-root"></div>
<div class="content">
$player
$episodes
</div>
HTML;
    return $html;
}

function sermonplayer_renderFacebookLikeButton($messageId) {
    $html=<<<HTML
<div class="fb-like"
    data-href="http://www.cornerstonejeffcity.org/listen/?sermon=$messageId"
    data-layout="button_count"
    data-action="like"
    data-size="large"
    data-show-faces="false"
    data-share="true">
</div>
HTML;
    return $html;
}

function sermonplayer_renderFacebookShareButton($messageId) {
    $html=<<<HTML
<div class="fb-share-button"
    data-href="http://www.cornerstonejeffcity.org/listen/?sermon=$messageId"
    data-layout="button"
    data-size="large"
    data-mobile-iframe="true">
    <a class="fb-xfbml-parse-ignore"target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fwww.cornerstonejeffcity.org%2Flisten%2F%3Fsermon%3D$messageId&amp;src=sdkpreparse">Share</a>
</div>
HTML;
    return $html;
}

function sermonplayer_getImageUrl($message, $isThumbnail = null) {
    $imageUrl = plugin_dir_url( __FILE__ ) . "public/images/";
    if($message->speaker == "Brian Credille") {
        $imageUrl .= "brian_credille";
    }
    else if($message->speaker == "Andy Vecellio") {
        $imageUrl .= "andy_vecellio";
    }
    else if($message->speaker == "Mark Ritchey") {
        $imageUrl .= "mark_ritchey";
    }
    else if($message->speaker == "Bill Campbell") {
        $imageUrl .= "bill_campbell";
    }
    else {
        $imageUrl .= "cornerstone";
    }
    if(isset($isThumbnail) && $isThumbnail == true) {
        $imageUrl .= "_thumbnail";
    }
    return $imageUrl.".jpg";
}

function renderPlayer($message) {
    $imageUrl = sermonplayer_getImageUrl($message);
    $imageAlt = $message->speaker;
    $messageTitle = htmlspecialchars($message->title,ENT_COMPAT);
    $messageDate = date("F j, Y",strtotime($message->date));
    $messageService = htmlspecialchars($message->service,ENT_COMPAT);
    $description = renderDescription($message);
    $bibleRefs = renderBibleRefs($message);
    $facebookButton = sermonplayer_renderFacebookLikeButton($message->id);
    $player_attr = array(
        'src' => "http://www.cornerstonejeffcity.org/messages/".$message->file,
        'loop' => '',
        'autoplay' => '',
        'preload' => 'none'
    );
    $player = wp_audio_shortcode($player_attr);
    $html=<<<HTML
<div class="player">
	<div class="episodeMeta">
		<img src="$imageUrl" alt="$imageAlt"/>
		<div class="overlay">
			<h2>$messageTitle</h2>
			<div class="liveDate">$messageDate - $messageService</div>
		</div>
	</div>
    $player
	$description
	$bibleRefs
	$facebookButton
    </div>
</div>
HTML;
    return $html;
}

function renderDescription($message) {
    $messageDescription = htmlspecialchars($message->description,ENT_COMPAT);
    $html=<<<HTML
<div class="description">$messageDescription</div>
HTML;
    return $html;
}

function renderBibleRefs($message) {
    if(!isset($message) || empty($message->reference)) {
        return "";
    }
    $messageBibleRefsUrl = "http://www.biblestudytools.com/search/?q=" . urlencode($message->reference) . "&t=nkjv";
    $messageBibleRefsText = htmlspecialchars($message->reference,ENT_COMPAT);
    $html=<<<HTML
<div class="bibleRefs">
	<span>References:</span>
	<a href="$messageBibleRefsUrl" target="_blank">$messageBibleRefsText</a>
</div>
HTML;
    return $html;
}

function renderEpisodes($messages) {
    $episodes = "";
    foreach ($messages as &$message) {
        $episodes .= renderEpisode($message);
    }
    $html=<<<HTML
<div class="episodes">
$episodes
	<div class="moreEpisodesContainer">
		<a href="http://www.cornerstonejeffcity.org/new/listen.php">More Episodes</a>
	</div>
</div>
HTML;
    return $html;
}

function renderEpisode($message) {
    $messageTitle = htmlspecialchars($message->title,ENT_COMPAT);
    $messageDate = date("F j, Y",strtotime($message->date));
    $messageService = htmlspecialchars($message->service,ENT_COMPAT);
    $sermonUrl = "http://www.cornerstonejeffcity.org/listen/?sermon=" . $message->id;
    $html=<<<HTML
<a class="group" href="$sermonUrl">
	<div class="playLink"><i class="fa fa-play"></i></div>
	<div class="details">
		<div class="title">$messageTitle</div>
		<span>$messageDate - $messageService</span>
	</div>
</a>
HTML;
    return $html;
}

function renderArchives($messages) {
    $html = "";
    $year = "";
    foreach ($messages as &$message) {
        $messageYear = date("Y",strtotime($message->date));
        if($year != $messageYear) {
            if($year != "") {
                $html .= "</details>";
            }
            $html .= "<details><summary>" . $messageYear . "</summary>";
            $year = $messageYear;
        }
        $html .= renderArchive($message);
    }
    $html .= "</details>";
    return $html;
}

function renderArchive($message) {
    $messageTitle = htmlspecialchars($message->title,ENT_COMPAT);
    $messageDate = date("F j, Y",strtotime($message->date));
    $messageService = htmlspecialchars($message->service,ENT_COMPAT);
    $messageSpeaker = htmlspecialchars($message->speaker,ENT_COMPAT);
    $sermonUrl = "http://www.cornerstonejeffcity.org/listen/?sermon=" . $message->id;
    $html=<<<HTML
<a class="group" href="$sermonUrl">
	<div class="playLink"><i class="fa fa-play"></i></div>
	<div class="details">
		<div class="title">$messageTitle</div>
		<span>$messageDate - $messageService - $messageSpeaker</span>
	</div>
</a>
HTML;
    return $html;
}
?>