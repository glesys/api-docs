#!/bin/bash
#This script uses hardcoded recordIDs for the domain records (the RECORD1 and RECORD2 variables). Look these values up using this curl command::
#/usr/bin/curl -X POST -d domain=example.com -k --basic -u clXXXXX:API-KEY https://api.glesys.com/domain/list_records
THIS="10.0.0.1"
THAT="10.0.0.2"
URL="http://$THAT/blog/testblog"
SEARCH="Some text on the site"
RECORD1="XXXXX"
RECORD2="YYYYY"
ACCOUNT="CL12345"
APIKEY="SECRET"

getStatus() { /usr/bin/wget -O - $URL 2> /dev/null | grep "$SEARCH" &>/dev/null; echo "$?"; } 
setRecords(){
        echo "sätter records till $1 och $2";
        /usr/bin/curl -X POST -d record_id=$RECORD1\&data=$1 -k --basic -u $ACCOUNT:$APIKEY https://api.glesys.com/domain/update_record
        /usr/bin/curl -X POST -d record_id=$RECORD2\&data=$2 -k --basic -u $ACCOUNT:$APIKEY https://api.glesys.com/domain/update_record
}

while :
do
        STATUS=$(getStatus)
        if [ $STATUS -eq 1 ]; then
                echo "The page is down!";
                setRecords "$THIS" "$THIS"
                while [ $STATUS -eq 1 ]; do
                        sleep 20
                        STATUS=$(getStatus)
                        echo "still down"
                done
                setRecords "$THIS" "$THAT"
        fi
        echo "The page is up!";
        sleep 60
done
