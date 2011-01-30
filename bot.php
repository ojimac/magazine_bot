<?php
// HTTP Request用ライブラリ
require_once "HTTP/Client.php";
// スクレイピングライブラリ
require_once "scrape/scraper.Class.php";
// URL短縮ライブラリ
require_once 'Services/ShortURL.php';
// Twitter OAuth用ライブラリ
require_once "./twitteroauth/twitteroauth/twitteroauth.php";
// 設定ファイル
require_once "bot_config.php";

// TwitterへのPOSTを行う(OAuth対応)
function send_twitter($message) {
	$consumer_key = CONSUMER_KEY;
	$consumer_secret = CONSUMER_SECRET;
	$access_token = ACCESS_TOKEN;
	$access_token_secret = ACCESS_TOKEN_SECRET;

	$twitter = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
	$message = $twitter->OAuthRequest("https://twitter.com/statuses/update.xml", "POST", array("status" => $message));

	header("Content-Type: application/xml");
	echo $message . "<br />";
}

function create_message($data) {
	//曜日対応表
	$weekdayDefines = array(
		'sunday'    => '日',
		'monday'    => '月',
		'tuesday'   => '火',
		'wednesday' => '水',
		'thursday'  => '木',
		'friday'    => '金',
		'saturday'  => '土',
	);

	$date = strtotime('now');
	$ymd = strftime('%Y年%m月%d日', $date);
	$weekday = strftime('%A', $date);

	foreach($weekdayDefines as $key => $value) {
		// マッチしたら日本の曜日に変換
		if (mb_eregi($key, $weekday)) {
			$weekday =  $value;
		}
	}

	// 取得したデータ分つぶやく
	foreach($data as $mag) {
		send_twitter($mag['name'] . ' ' . $mag['url'] . ' が' . $ymd . '(' . $weekday . ')' . ' に発売されるよ！');
	}
}

function scrape() {
	//雑誌発売日チェッカ
	$scraper = new WebScraper("http://itumag.s8.xrea.com/", "EUC-JP", true);
	$scraper->retrieve();

	$scraper = $scraper->xml->body->div{3}->ul{1};

	$data = array();
	$shortUrl = Services_ShortURL::factory('TinyURL');

	for($i=0; $i < count($scraper); $i++) {
		// 文字列に変換しないとオブジェクトのまま
		// オブジェクトのままだと配列にいれたとき値が入らない!
		// URLが長過ぎて140文字超えるとエラーになってしまうので、短縮URLに変換する
		array_push($data , array(
			'name' => (string)$scraper->li{$i}->a{0}->strong,
			'url'  => $shortUrl->shorten((string)$scraper->li{$i}->a{1}->attributes()->href),
		));
	}
	return create_message($data);
}

// 今日発売の雑誌を取りに行く!
scrape();
