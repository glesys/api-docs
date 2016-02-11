#!/usr/bin/env bash

###########
#
# - dependencies: curl xmlstarlet (debian: apt-get install curl xmlstarlet)
# - GleSYS API credentials (DOMAIN permissions)
#   syntax:
#   echo "export USER=CL12345" > /etc/ssl/private/.glesys-credentials
#   echo "export KEY=KEY_GOES_HERE" >> /etc/ssl/private/.glesys-credentials
#
###########

DEPS=`whereis xmlstarlet`
if [ "$?" -ne 0 ]; then
        echo "install xmlstarlet"
        exit 1
fi
DEPS=`whereis curl`
if [ "$?" -ne 0 ]; then
        echo "install curl"
        exit 1
fi

set -e
set -u
set -o pipefail
umask 077

#Load GleSYS Credentials
. /etc/ssl/private/.glesys-credentials

#split domain
FQDN=$2
DOMAIN=`echo $2 |rev |cut -d '.' -f1-2 |rev`
SUBDOMAIN=`echo $2 |rev |cut -d '.' -f3- |rev`
DONE="no"
#Functions
function validate_xml {
#Check if API call got status 200 (OK)
STATUSCODE=`xmlstarlet sel -t -v "/response/status/code" /tmp/api-log.xml`
if [ "$STATUSCODE" -ne 200 ]; then
        ERRORCODE=`xmlstarlet sel -t -v "/response/status/text" /tmp/api-log.xml`
        echo "Error: $ERRORCODE"
        exit 1
fi
}

if [[ "$1" = "deploy_challenge" ]]; then
        ##Create TXT-Record for LetsEncrypt
        curl -sS -X POST --data-urlencode domainname="$DOMAIN" --data-urlencode host="_acme-challenge.$FQDN." --data-urlencode type="TXT" --data-urlencode data="$4" --data-urlencode ttl="300" -k --basic -u $USER:$KEY https://api.glesys.com/domain/addrecord/ > /tmp/api-log.xml
        ##Run function to validate the response
        validate_xml
        DONE="yes"
fi

if [[ "$1" = "clean_challenge" ]]; then
        #API call to retrieve list of records for the domain.
        curl -sS -X POST --data-urlencode domainname="$DOMAIN" -k --basic -u $USER:$KEY https://api.glesys.com/domain/listrecords/ > /tmp/api-log.xml
        #Run function to validate the response
        validate_xml
                #remove TXT records created by this script
                for i in `xmlstarlet sel -t -c /response/records/item[host="'"_acme-challenge.$FQDN".'"] -f /tmp/api-log.xml |grep "<recordid>" |grep -o '[0-9]\+'`
                do
                        curl -sS -X POST --data-urlencode recordid="$i" -k --basic -u $USER:$KEY https://api.glesys.com/domain/deleterecord/ > /tmp/api-log.xml
                        validate_xml
                done
        DONE="yes"
fi

if [[ "$1" = "deploy_cert" ]]; then
    # do nothing for now
    # but can be replaced by for example reload apache2
    # systemctl reload apache2
    DONE="yes"
fi

rm -f /tmp/api-log.xml

if [[ ! "$DONE" = "yes" ]]; then
    echo Unkown hook "$1"
    exit 1
fi

exit 0
