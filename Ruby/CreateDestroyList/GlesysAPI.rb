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
      :datacenter => "Falkenberg",
      :platform   => "Xen",
      #:hostname   => "example.com",
      :template   => "Debian-6 x64",
      :disksize   => "10",
      :memorysize => "512",
      :cpucores   => "1",
      :rootpw     => "p4ssw0rd",
      :transfer   => "500"
    }
  end

  # Create a server
  #
  # Returns hash with serverid and ip
  def create(custom={:hostname => 'example.com'})
    settings.merge!(custom)

    resp_json = request("/server/create", settings)
    resp_hash = JSON.parse(resp_json)
    
    code      = resp_hash['response']['status']['code']
    if code == "200"
      serverid  = resp_hash['response']['server']['serverid']
      ip        = resp_hash['response']['server']['iplist'].first['ip']
    end
    { :serverid => serverid, :ip => ip}
  end

  # Destroy a server
  #
  # Returns serverid
  def destroy(id, keep_ip=0)
    resp_json = request("/server/destroy", 
      {:serverid => id, :keepip   => keep_ip})
    resp_hash = JSON.parse(resp_json)
    code = resp_hash['response']['status']['code']
    resp_hash['response']['arguments']['serverid'] if code == "200"
  end

  # Get IP from serverid
  #
  # Returns the first IP
  def get_ip(id)
    resp_json = request("/ip/listown", :serverid => id)
    resp_hash = JSON.parse(resp_json)
    iplist = resp_hash['response']['iplist']

    unless iplist.first.nil?
      iplist.first['ip'] 
    else
      "NOT ASSIGNED"
    end
  end

  # Lists all own servers
  #
  # Returns server response
  def list
    resp_json = request("/server/list")
    resp_hash = JSON.parse(resp_json)
    resp_hash['response']['servers']
  end

end

glesys_api_key = "my_super_secret_api_key"
a = Glesys.new("clNNNNN", glesys_api_key)

# Create
#server = a.create(:hostname => "glesys.example.com") 
#puts "  => #{server[:serverid]} (#{server[:ip]}) created"

# Destroy
#puts "  => #{a.destroy(server[:serverid])} destroyed."

# List
output = ""
a.list.each do |s|
  output << "#{s['serverid']}\t#{a.get_ip(s['serverid'])}\t#{s['hostname']}\n"
end
puts output