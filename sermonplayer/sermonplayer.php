<?php
/*
Plugin Name: Sermon Player
Description: Renders a page for listening to sermons.
Version: 1.0.4
Author: Aaron Jacobson
Author URI: http://www.aaronjacobson.com
*/

//tell wordpress to register the shortcodes
add_shortcode("sermonplayer", "sermonplayer");

require_once($_SERVER['DOCUMENT_ROOT']."/../api/dbconfig.php");
require_once($_SERVER['DOCUMENT_ROOT']."/../api/repository/messageDao.php");

function sermonplayer() {
    $messageId = $_REQUEST["sermon"];

    global $cornerstone_dbHost, $cornerstone_dbUserLogin, $cornerstone_dbPassword, $cornerstone_dbName;
    $dao = new MessageDao($cornerstone_dbHost, $cornerstone_dbUserLogin, $cornerstone_dbPassword, $cornerstone_dbName);
    if(isset($messageId)) {
        $messages = array($dao->GetMessageById($messageId));
        $episodes = $dao->GetLatest(4);
        foreach ($episodes as &$message) {
            if($message->id != $messageId) {
                array_push($messages, $message);
            }
        }
    }
    else {
        $messages = $dao->GetLatest(4);
    }

    sermonplayer_register_scripts();
    $html = renderContent($messages);
    echo $html;
}

function sermonplayer_register_scripts() {
    wp_enqueue_script('jplayer-script', plugin_dir_url( __FILE__ ) . 'public/js/jquery.jplayer.min.js');
    wp_enqueue_style('sermon-player-style', plugin_dir_url( __FILE__ ) . 'public/css/style.css');
    wp_enqueue_style('jplayer-style', plugin_dir_url( __FILE__ ) . 'public/css/jplayer.min.css');
}

function renderContent($messages) {
    $message = $messages[0];
    $script = renderScript($message);
    $player = renderPlayer($message);
    $episodes = renderEpisodes(array_slice($messages, 1, 3));
    $message = $messages[0];
    $html=<<<HTML
$script
<div class="content">
$player
$episodes
</div>
HTML;
    return $html;
}

function renderScript($message) {
    $messageFile = "http://www.cornerstonejeffcity.org/messages/".$message->file;
    $html=<<<HTML
<script type="text/javascript">
	jQuery(document).ready(function($){
		$("#jquery_jplayer_1").jPlayer({
			ready: function (event) {
				var plyr = $(this);
				plyr.jPlayer("setMedia", {
					mp3: "$messageFile"
				});
			},
			cssSelectorAncestor: "#jp_container_1",
			swfPath: "/js",
			supplied: "mp3",
			useStateClassSkin: true,
			autoBlur: false,
			smoothPlayBar: true,
			keyEnabled: true,
			remainingDuration: true,
			toggleDuration: true
		});
	});
</script>
HTML;
    return $html;
}

function renderPlayer($message) {
    $imageUrl = WP_PLUGIN_URL."/sermonplayer/public/images/brian.jpg";
    $imageAlt = "Brian Credille";
    $messageTitle = htmlspecialchars($message->title,ENT_COMPAT);
    $messageDate = date("F j, Y",strtotime($message->date));
    $messageService = htmlspecialchars($message->service,ENT_COMPAT);
    $description = renderDescription($message);
    $bibleRefs = renderBibleRefs($message);

    $html=<<<HTML
<div class="player">
	<div class="episodeMeta">
		<img src="$imageUrl" alt="$imageAlt"/>
		<div class="overlay">
			<h2>$messageTitle</h2>
			<div class="liveDate">$messageDate - $messageService</div>
		</div>
	</div>
	<div id="jquery_jplayer_1" class="jp-jplayer"></div>
	<div id="jp_container_1" class="jp-audio">
		<div class="jp-type-single">
			<div class="jp-gui jp-interface">
				<ul class="jp-controls">
					<svg class="jp-play">
						<polygon points="0,0 18,10 0,20"  />
					</svg>
					<svg class="jp-pause">
						<line x1="4" y1="2" x2="4" y2="18" />
						<line x1="14" y1="2" x2="14" y2="18" />
					</svg>
				</ul>
				<div class="jp-progress">
					<div class="jp-seek-bar">
						<div class="jp-play-bar"></div>
					</div>
				</div>
				<div class="jp-time-holder">
					<div class="jp-current-time"></div>
				</div>
				<div class="jp-time-holder">
					<div class="jp-total-time"></div>
				</div>
				<div class="jp-volume-bar">
					<div class="jp-volume-bar-value"></div>
				</div>
				<div class="jp-no-solution">
					<span>Update Required</span>
					To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
				</div>
			</div>
		</div>
	</div>
	$description
	$bibleRefs
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
?>