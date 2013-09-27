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

# Bugfix for conky exec, python does not know which encoding to use and 
# will wrongly default to ascii
sys.stdout = codecs.getwriter('utf-8')(sys.stdout)	


def printMetric(metricNode, metricName):
	usage = float(metricNode.find("usage").text)
	max = float(metricNode.find("max").text)
	print metricName+": $alignr", usage, " / ", max, metricNode.find("unit").text[0:2]	
	print "${execbar echo \""+ str(usage * 100 / max) + "\"}"

tree = ET.fromstring(content)
for server in tree.findall("server"):
	print "Server name: $alignr", __APISERVER__
	print "State: $alignr", server.find("state").text
	uptime = int(server.find("uptime").find("current").text)
	d = datetime.timedelta(seconds=uptime)
	print "Uptime: $alignr", d
	print
	
	cpu = server.find("cpu")	
	print "CPUs: $alignr", cpu.find("usage").text + " / " + cpu.find("max").text, cpu.find("unit").text	
	memory = server.find("memory")	
	printMetric(memory, "Memory")	
	transfer = server.find("transfer")	
	printMetric(transfer, "Transfer")	
	
