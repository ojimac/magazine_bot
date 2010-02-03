<?php

require_once "HTTP/Client.php";
require_once "scrape/scraper.Class.php";
require_once "../../bot_config.php";

function send_twitter($message)
{

  $url = "http://twitter.com/statuses/update.xml?";
  // 自分のtwitterユーザ名
  $username = USER;
  // 自分のtwitterパスワード
  $password = PASS;

  $params = "status=". rawurlencode($message);


  $result = file_get_contents($url.$params , false, stream_context_create(array(
		"http" => array(
		"method" => "POST",
		"header" => "Authorization: Basic ". base64_encode($username. ":". $password)
				))));
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
    if(mb_eregi($key, $weekday)) {
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

  for($i=0; $i < count($scraper); $i++) {
    // 文字列に変換しないとオブジェクトのまま
    // オブジェクトのままだと配列にいれたとき値が入らない!
    array_push($data , array(
			     'name' => (string)$scraper->li{$i}->a{0}->strong,
			     'url'  => (string)$scraper->li{$i}->a{1}->attributes()->href,
			     ));
  }
  return create_message($data);
}

// 今日発売の雑誌を取りに行く!
scrape();