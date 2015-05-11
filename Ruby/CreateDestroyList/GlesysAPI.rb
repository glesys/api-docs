# GleSYS API
#
# Author: Anton Lindström
# http://github.com/antonlindstrom
#
# Example for the Glesys API 
class Glesys

  API_BASE = "https://api.glesys.com"

  # Give me the key and set format
  # 
  # Returns nothing
  def initialize(id, key)
    require 'rubygems'
    require 'net/http'
    require 'net/https'
    require 'json'

    @keyid  = id
    @apikey = key
    @format = "json"
  end

  # Accessor
  attr_reader :settings

  # Sending request to server, set data
  #
  # Returns the json response
  def request(path, post={})
      url = URI.parse("#{API_BASE}/#{path}")
      post[:format] = @format

      http = Net::HTTP.new(url.host, url.port)
      http.use_ssl = true

      req = Net::HTTP::Post.new(url.path)
      req.basic_auth @keyid, @apikey

      req.set_form_data(post, '&')
      http.request(req).body

    rescue StandardError => err
      err
  end

  # Default settings
  #
  # Returns settings hash
  def settings
    @settings = {
      :datacenter 	=> "Falkenberg",
      :platform   	=> "OpenVZ",
      :hostname     => "example#{rand(999)}.com",
      :templatename	=> "Debian 7.0 64-bit",
      :disksize   	=> "10",
      :memorysize 	=> "512",
      :cpucores   	=> "1",
      :rootpassword	=> "p4ssw0rd",
      :transfer   	=> "500"
    }
  end

  # Create a server
  def create(options = {:hostname => 'example.com'})
    settings.merge!(options)

    resp_json = request("/server/create", settings)
    resp_hash = JSON.parse(resp_json)
    unless status_code(resp_hash) == 200
      raise resp_hash['response']['status']['text']
    end
    server_id = resp_hash['response']['server']['serverid']
    ip_list   = resp_hash['response']['server']['iplist'].map{ |ip| ip["ipaddress"] }
    return {
      :server_id => server_id,
      :ip_list => ip_list
    }
  end

  # Destroy a server
  #
  # Returns serverid
  def destroy(id, keep_ip=false)
    params = {
      :serverid => id, 
      :keepip   => keep_ip ? 1 : 0
    }
    resp_json = request("/server/destroy", params)
    resp_hash = JSON.parse(resp_json)
    unless status_code(resp_hash) == 200
      raise resp_hash['response']['status']['text']
    end
    return true
  end

  # Get IP from serverid
  #
  # Returns the first IP
  def get_ip(id)
    resp_json = request("/ip/listown", :serverid => id)
    resp_hash = JSON.parse(resp_json)
    unless status_code(resp_hash) == 200
      raise resp_hash['response']['status']['text']
    end
    iplist = resp_hash['response']['iplist']

    unless iplist.first.nil?
      iplist.first['ipaddress'] 
    else
      nil
    end
  end

  # Lists all own servers
  #
  # Returns server response
  def list_servers
    resp_json = request("/server/list")
    resp_hash = JSON.parse(resp_json)
    unless status_code(resp_hash) == 200
      raise resp_hash['response']['status']['text']
    end
    resp_hash['response']['servers']
  end

  def status_code(resp)
    resp['response']['status']['code'].to_i
  end

end

require "pp"

## Define your credentials
glesys_api_key = "my_super_secret_api_key"
api = Glesys.new("clNNNNN", glesys_api_key)

## Create a server with default settings
server = api.create(:hostname => "glesys.example.com") 
#pp server

## List all servers on your account
api.list_servers.each do |server|
  puts "#{server['serverid']}\t#{api.get_ip(server['serverid'])}\t#{server['hostname']}\n"
end

## Destroy server
if api.destroy(server[:server_id])
  puts "#{server[:server_id]} has been destroyed."
end

