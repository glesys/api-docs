# GleSYS API
#
# Author: Marcel Asio
# http://github.com/asio
#
# Example for the Glesys API

# This file contains request method to simplify process of doing request and parsing them
require_relative "../glesys"

#Implemented some of the methods for the domain module
class GleSYS::DNS
  include GleSYS

  # request method uses this to build the url for correct module
  MODULE = "domain"

  # Returns an array of domains on the account
  def list_domains
    request_and_parse("list")
  end

  # Returns an array of records in specified domain
  def list_records(domainname)
    request_and_parse("listrecords", "domainname" => domainname)
  end

  # Update record using hostname and domain for matching
  def update_record(current_host, domain, data = nil, host = nil, type = nil, ttl = nil)
    records = list_records(domain)
    record  = records.select { |r| r['host'] == current_host }.first

    request_and_parse("updaterecord",
      "recordid" => record["recordid"],
      "data"     => data,
      "host"     => host,
      "type"     => type,
      "ttl"      => ttl
    )
  end
end
