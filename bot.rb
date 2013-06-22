#! /usr/bin/env ruby
# coding: utf-8

require 'nokogiri'
require 'open-uri'
require 'pp'
require 'shorturl'
require 'twitter'
require 'yaml'

EXEC_ENV = 'production'

base_path   = File.expand_path(File.dirname(__FILE__));
config_file = base_path + '/config.yml'
config      = YAML.load_file(config_file)[EXEC_ENV]

Twitter.configure do |t|
  t.consumer_key       = config['consumer_key']
  t.consumer_secret    = config['consumer_secret']
  t.oauth_token        = config['oauth_token']
  t.oauth_token_secret = config['oauth_token_secret']
end

# NOTE: サーバー時間がUTCなので-9時間する
scrape_date = Time.now
#scrape_date = scrape_date - 9 * 60
japan_format_date = scrape_date.strftime("%Y年%m月%d日")
#pp japan_format_date
ymd_format_date   = scrape_date.strftime("%Y-%m-%d")
#pp scrape_date

weekdays = ['日','月','火','水','木','金','土']

uri  = "http://www.fujisan.co.jp/GetTOCListByDate.asp?date=#{ymd_format_date}"
page = URI.parse(uri).read

doc = Nokogiri::HTML(page, uri, 'Shift_JIS')
doc.search('//td/a').each do |tag|
  if tag[:href].to_s.index('http')
#      pp tag[:href]
#      pp tag.content
#      puts '---'

    # TODO: アフィリURL生成
    url           = ShortURL.shorten(tag[:href], :tinyurl)
    magazine_name = tag.content

#   sleep(1)
#   Twitter.update(magazine_name)
    puts "#{magazine_name} #{url} が #{japan_format_date}（#{weekdays[scrape_date.wday]}）に発売されるよ！"
  end
end

# fujisanのアフィリリンクを作る
def make_affiliate_url(magazine_name)
end
