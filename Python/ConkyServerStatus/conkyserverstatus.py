#!/usr/bin/python
# -*- coding: utf-8 -*-
# coding: UTF-8

import json, codecs, os, sys, datetime
import xml.etree.ElementTree as ET
import httplib2
import ConfigParser


config = ConfigParser.ConfigParser()
config.readfp(open('glesys.conf'))

__APIKEY__ = config.get('ConkyServerStatus', 'apikey')
__APIUSER__ = config.get('ConkyServerStatus', 'apiuser')
__APISERVER__ = config.get('ConkyServerStatus', 'apiserver')
__APIURL__ = config.get('ConkyServerStatus', 'apiurl')

#print __APIKEY__, __APISERVER__, __APIUSER__
#print __APIURL__

# For some reason ssl_certification is failing at the moment..
try:
	h = httplib2.Http(disable_ssl_certificate_validation=True) 
	h.add_credentials(__APIUSER__, __APIKEY__)
	resp, content = h.request(__APIURL__+"/serverid/"+__APISERVER__)		
except:
	print "No connection"
	exit
	
sys.stdout = codecs.getwriter('utf-8')(sys.stdout)	



#print resp
#print content
#print json.dumps(data)
tree = ET.fromstring(content)

for server in tree.findall("server"):
	print "State:", server.find("state").text
	cpu = server.find("cpu")
	print "CPUs:", cpu.find("usage").text + " / " + cpu.find("max").text, cpu.find("unit").text
	memory = server.find("memory")
	print "Memory:", memory.find("usage").text + " / " + memory.find("max").text, memory.find("unit").text
	transfer = server.find("transfer")
	print "Transfer:", transfer.find("usage").text + " / " + transfer.find("max").text, transfer.find("unit").text
	
	uptime = int(server.find("uptime").find("current").text)
	d = datetime.timedelta(seconds=uptime)
	print "Uptime:", d
	
	

#print "Bandwidth:", "${execbar
