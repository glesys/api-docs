#!/bin/bash
USER=clXXXXX
APIKEY=XX
SERVERID=vzXXXXXX
SERVERSTATUS=`/usr/bin/curl --silent -X POST -d serverid=$SERVERID\&statustype=memory -k --basic -u $USER:$APIKEY https://api.glesys.com/server/status/`
USED=`echo "$SERVERSTATUS" | grep 'usage' | sed 's/[^0-9]//g'`
TOTAL=`echo "$SERVERSTATUS" |grep 'max' | sed 's/[^0-9]//g'`
#echo "$USED : $TOTAL"
if [ $TOTAL -eq 256 ] && [ $USED -gt 200 ]; then
        echo "Upgrading"
        /usr/bin/curl --silent -X POST -d serverid=$SERVERID\&memorysize=1024 -k --basic -u $USER:$APIKEY https://api.glesys.com/server/edit/ > /dev/null
fi;

if [ $TOTAL -eq 1024 ] && [ $USED -lt 150 ]; then
        echo "Downgrading"
        /usr/bin/curl --silent -X POST -d serverid=$SERVERID\&memorysize=256 -k --basic -u $USER:$APIKEY https://api.glesys.com/server/edit/ > /dev/null
fi;

