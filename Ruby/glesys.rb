# GleSYS API
#
# Author: Marcel Asio
# http://github.com/asio
#
# Example for the Glesys API

require "net/http"
require "net/https"
require "json"

# module used for implementing convenience methods for requests
module GleSYS
  extend self

  # Replace these values with your own
  API_ID       = "USERNAME"
  API_KEY      = "API_KEY"

  BASE_URL = "https://api.glesys.com"
  FORMAT   = "json"

  # wrapper for request and the parse_response
  def request_and_parse(action, data = {})
    parse_response(request(action, data))
  end

  private

  # make a request and returns body
  def request(action, data = {})
    url = URI.parse("#{BASE_URL}/#{self.class::MODULE}/#{action}")
    data[:format] = FORMAT

    http         = Net::HTTP.new(url.host, url.port)
    http.use_ssl = true

    req = Net::HTTP::Post.new(url.path)
    req.basic_auth(API_ID, API_KEY)
    req.set_form_data(data, "&")

    http.request(req).body
  end

  # parses the response body from request and returns a ruby object, on fail it prints to stdout
  def parse_response(body)
    res = JSON.parse(body)
    if res["response"]["status"]["code"] == 200
      res["response"].select { |k| !["status", "debug"].include?(k) }.values.first
    else
      puts "-------------FAILED------------------"
      puts res["response"]["status"]["text"]
      puts res["response"]["debug"]
      puts "-------------------------------------"
    end
  end
end
