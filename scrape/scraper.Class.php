<?php

// 本番環境以外では無効にしておきましょう
error_reporting(0);

class WebScraper{
  
  // URLを設定
  var $self = "http://tech.openvista.jp/scraping/";
  
  // コンストラクタ
  function __construct($url="", $encoding="", $noQuery=""){
    
    // フォームからの入力ならリダイレクト
    if (preg_match("/index.php\?q=/", $_SERVER["REQUEST_URI"], $match)){
      header("location: ". $this->self . urldecode($_GET["q"]) . "." . urldecode($_GET["type"]) );
    }
    
    // URLを設定
    (!empty($url)) ? $this->url = $url : $this->errors[] = "URLを設定してください";
    
    // 文字エンコーディングを設定
    switch (true){
      case empty($encoding)                  : $this->errors[] = "URLの文字エンコーディングを設定してください"; break;
      case eregi("SHIFT(-|_)JIS", $encoding) : $this->encoding = "SJIS-win"; break;
      case eregi("EUC-JP", $encoding)        : $this->encoding = "eucJP-win"; break;
      case eregi("UTF-8", $encoding)         : $this->encoding = "UTF-8"; break;
      case eregi("ISO-2022-JP", $encoding)   : $this->encoding = "ISO-2022-JP"; break;
      case eregi("JIS", $encoding)           : $this->encoding = "JIS"; break;
      case eregi("ASCII", $encoding)         : $this->encoding = "ASCII"; break;
      default                                : $this->errors[] = "不明なエンコーディングです";
    }
    
    // キーワードを設定
    if (empty($noQuery)){
      if (!empty($_GET["q"])){
        $keyword = mb_convert_encoding($_GET["q"], $this->encoding, "UTF-8");
        $this->keyword = rawurlencode($keyword);
        $this->url    .= $this->keyword;
      } else{
        $this->errors[] = "検索キーワードを入力してください";
      }
    }
    
  }
  
  // HTMLを取得して、SimpleXMLObjectに変換
  function retrieve(){
    
    // HTMLをゲット
    $html = $this->getHTML();
    
    //SimpleXMLオブジェクトに変換
    $this->xml = $this->html_to_simplexml($html);
    
  }
  
  // 要素を抽出
  function pickUpElement($xpath="", $single=false){
    
    (empty($xpath)) ? $this->errors[] = "対象の要素をXPath式で指定してください" : "";
    
    // 配列をセット
    if ($single === true){
      $items = $this->xml->xpath($xpath);
    } else{
      foreach ($this->xml->xpath($xpath) as $item){
        $items[] = (string) str_replace("&amp;", "&", $item->asXML());
      }
    }
    
    return $items;
    
  }
  
  // いったん、別のエンコードに変換したキーワードを再度UTF-8に変換し直す
  function convertKeyword(){
    
    $keyword = rawurldecode($this->keyword);
    $keyword = mb_convert_encoding($keyword, "UTF-8", $this->encoding);
    
    return $keyword;
    
  }
  
  // HTMLを取得
  function getHTML(){
    
    $client = new HTTP_Client();
    $client->setDefaultHeader(array('User-Agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)'));
    $client->get($this->url);
    $result = $client->currentResponse();
    
    switch($this->encoding){
      case "SJIS-win" : $this->encoding_html = "shift_jis"; break;
      case "eucJP-win": $this->encoding_html = "euc-jp"; break;
      default         : $this->encoding_html = $this->encoding;
    }
    
    $html = $result["body"];
    $html = preg_replace("/".$this->encoding_html."/i", "utf-8", $html);
    $html = mb_convert_encoding($html, "UTF-8", $this->encoding);
    
    // コメントを削除
    $html = preg_replace("/<!--([^-]|-[^-]|--[^>])*-->/", "", $html);
    $html = str_replace("&", "&amp;", $html);
    
    if (preg_match("/<title>(.*)<\/title>/i", $html, $match)){
      $this->title = $match[1];
    }
    
    return $html;
    
  }
  
  // SimpleXMLオブジェクトに変換
  function html_to_simplexml($html_){
    
    // DOMDocumentの文字化け対策
    $html_ = preg_replace('/<title>/i',
                          '<meta http-equiv="content-type" content="text/html; charset=utf-8"><title>',
                          $html_);
    
    @$dom = new DOMDocument("1.0", "utf-8");
    @$dom->loadHTML($html_);
    
    // DOMをSimpleXMLへ変換
    $ret = simplexml_import_dom($dom);
    
    $str = $ret->asXML();
    
    // XML宣言付与
    if (true !== preg_match('/^<\\?xml version="1.0"/', $str)) {
      $str = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $str;
    }
    
    $ret = simplexml_load_string($str);
    
    return $ret;
    
  }
  
  // エラーがあった場合、エラーメッセージを含む文字列を返す
  function isError(){
    
    if (count($this->errors) > 0){
      
      foreach($this->errors as $error){
        $msg .= "<li>{$error}</li>\n";
      }
      
      echo "<ul>". $msg ."</ul>";
      
    } else{
      
      return false;
      
    }
    
  }
  
  // WIndows IE以外の場合XML宣言を出力する
  function xmlDeclaration(){
    
    echo 
    ( (
        (substr_count($_SERVER["HTTP_USER_AGENT"], "MSIE")) &&
        (substr_count($_SERVER["HTTP_USER_AGENT"], "Windows"))
      ) ?
    "" : '<?xml version="1.0" encoding="utf-8" ?>'."\n"
    );
  
  }
  
}

?>
