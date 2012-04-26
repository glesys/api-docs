#!/bin/bash
TOTAL=`free | grep Mem: | awk '{print $2}'`
USED=`free | grep Mem: | awk '{print $3}'`
PERCENTAGE=$(((USED*100)/TOTAL));
if [ $PERCENTAGE -gt 90 ]; then
        /usr/bin/curl -X POST -d serverid=vz123456\&memorysize=2048 -k --basic -u clXXXXX:API-KEY https://api.glesys.com/server/edit/
fi;
