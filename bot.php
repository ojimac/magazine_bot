<?php
// HTTP Request用ライブラリ
require_once "HTTP/Client.php";
// HTTP Request用ライブラリ
require_once "HTTP/Request.php";
// スクレイピングライブラリ
require_once "scrape/scraper.Class.php";
// URL短縮ライブラリ
require_once 'Services/ShortURL.php';
// Twitter OAuth用ライブラリ
require_once "./twitteroauth/twitteroauth/twitteroauth.php";
// 設定ファイル
require_once "bot_config.php";

// TwitterへのPOSTを行う(OAuth対応)
function send_twitter($tweet_text) {
	$consumer_key = CONSUMER_KEY;
	$consumer_secret = CONSUMER_SECRET;
	$access_token = ACCESS_TOKEN;
	$access_token_secret = ACCESS_TOKEN_SECRET;

	$twitter = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
	$message = $twitter->OAuthRequest("https://twitter.com/statuses/update.xml", "POST", array("status" => $tweet_text));
	sleep(1);
	header("Content-Type: application/xml");
	echo $tweet_text . "\n";
}

function create_message($magazine_name, $fujisan_affiliate_url) {
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
	send_twitter($magazine_name . ' ' . $fujisan_affiliate_url . ' が' . $ymd . '(' . $weekday . ')' . ' に発売されるよ！');
}

function scrape() {
	// fujisan.co.jpからスクレイピング
	$scrape_date = date('Y-m-d');
	$scraper = new WebScraper("http://www.fujisan.co.jp/GetTOCListByDate.asp?date=$scrape_date", "Shift_JIS", true);
	$scraper->retrieve();

	// aタグの塊の一つ手前まで取ってくる
	$scrape_data = $scraper->xml->body->table->tr{1}->td->table->tr->td{1};
	$scrape_data_arr = obj2arr($scrape_data);
	$scrape_data_arr = $scrape_data_arr['a'];

	$shortUrl = Services_ShortURL::factory('TinyURL');

	foreach ($scrape_data_arr as $v) {
		$fujisan_affiliate_url = makeUrl($v);
		create_message($v, $shortUrl->shorten($fujisan_affiliate_url));
		sleep(1);
	}
	return;
}

/**
 * fujisan.ne.jpのアフィリリンクURLを作る
 */
function makeUrl($magazine_name) {

	$magazine_name = urlencode($magazine_name);

	// リクエストを行うURLの指定
	$request_url = "http://ws.fujisan.co.jp/search/keyword?query={$magazine_name}&results=1&ap=magazine_bot";

	$option = array(
		"timeout"        => "10", // タイムアウトの秒数指定
		"allowRedirects" => true, // リダイレクトの許可設定(true/false)
		"maxRedirects"   => 3,    // リダイレクトの最大回数
	);

	$http = new HTTP_Request($request_url, $option);
	$response = $http->sendRequest();

	//sleep(0.5);

	if ($response) {
		$res = $http->getResponseBody();
		$obj = simplexml_load_string($res);
		// XML -> 連想配列変換
		$arr = json_decode(json_encode($obj), true);

		if (array_key_exists('Product', $arr)) {
			$url = $arr['Product']['ProductUrl'];
		} else {
			$url = '';
		}
	} else {
		$url = '';
	}
	return $url;
}

function obj2arr($obj) {
	if (! is_object($obj)) {
		return $obj;
	}

	$arr = (array)$obj;

	foreach ($arr as &$a) {
		$a = obj2arr($a);
	}
	return $arr;
}

// 今日発売の雑誌を取りに行く!
scrape();
