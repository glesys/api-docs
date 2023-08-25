#!/usr/bin/env bash
#
#   An improved hook script for dehydrated and GleSYS API.
#
#   Contributions by
#   * kiranos: https://github.com/kiranos/
#   * marcusmansson: https://gitlab.com/marcusmansson/glesys-dns01
#   * abed19919: https://github.com/abdulaziz-alahmad/
#
#   Dependencies
#   ============
#   - curl and xmlstarlet (debian: apt-get install curl xmlstarlet)
#   - GleSYS API credentials (DOMAIN permissions for list, add, remove records)
#
#   Prerequisites
#   =============
#   Read README.md
#
#   echo "export USER=CL12345" > /etc/ssl/private/glesys-credentials
#   echo "export KEY=KEY_GOES_HERE" >> /etc/ssl/private/glesys-credentials
#   chmod 600 /etc/ssl/private/glesys-credentials
#
#   IMPORTANT
#   =========
#   Edit your dehydrated config and set CHAIN="yes", otherwise wildcard urls
#   will not work if you create a combo cert for both domain.tld and *.domain.tld.
#
#   CHAIN="yes"
#
#
set -e
set -o pipefail
umask 077
HOOK=$1
TIME=$(date)
LOGFILE=/var/log/glesys-dns-01.log
VERBOSE=

_cleanup () {
    rm -f /tmp/api-log.xml
}
trap _cleanup EXIT

# Tail logfile in background (if interactive)
if [[ $VERBOSE ]] && test -t 0; then
    tail --pid $$ -f $LOGFILE &
fi

# Check required bins.
hash xmlstarlet
hash curl

# Load GleSYS Credentials.
source /etc/ssl/private/.glesys-credentials

# Parse all domain challenges from argument list.
domains=();
_parse_domains () {
    shift;
    while (( "$#" )); do
        read -r FQDN _ CHALLENGE _ <<< "$@"
        shift 3
        # convert fqdn to array and get the last two slices
        x=(${FQDN//./ })
        x="${x[@]: -2:2}"
        DOMAIN=${x// /.}
        domains+=("$DOMAIN $FQDN $CHALLENGE")
    done
}

_start_log () {
    echo $TIME: "$@"
    echo $TIME: $(declare -p domains)
} >> $LOGFILE

_validate_response () {
    # Check if API call got status 200 (OK).
    STATUSCODE=$(xmlstarlet sel -t -v "/response/status/code" /tmp/api-log.xml)

    if [[ $STATUSCODE -ne 200 ]]; then
        ERRORCODE=$(xmlstarlet sel -t -v "/response/status/text" /tmp/api-log.xml)
        echo "Error in $0: $ERRORCODE"
        exit 1
    fi
}

deploy_challenge () {
    _parse_domains $@

    # Create TXT records for all ACME challenges.
    for domain in "${domains[@]}"; do
        read DOMAIN FQDN CHALLENGE <<< "$domain"
        glesys_api addrecord domainname=$DOMAIN \
            host=_acme-challenge.$FQDN. type=TXT ttl=300 data=$CHALLENGE
	echo "$CHALLENGE"
    done

    # Wait for settings to apply on the endpoint.
    sleep 2
    DONE="yes"
}

clean_challenge () {
    _parse_domains $@
    local lastdomain=""

    # For all hosts:
    for domain in "${domains[@]}"; do
        read DOMAIN FQDN CHALLENGE <<< "$domain"
        [[ $DOMAIN = $lastdomain ]] && continue
        lastdomain=$DOMAIN

        # API call to retrieve list of records for the domain.
        glesys_api listrecords domainname=$DOMAIN

        acme_records=$(xmlstarlet sel -t \
            -m "//host[contains(., '_acme-challenge')]/.." \
            -v "concat(recordid, ' ')" \
            -n /tmp/api-log.xml)

        # Remove TXT records created by this script.
        for id in $acme_records; do
            glesys_api deleterecord recordid=$id
        done
    done
    DONE="yes"
}


#  # Wait for hook script to clean the challenge and to deploy cert if used
#  [[ -n "${HOOK}" ]] && "${HOOK}" "deploy_cert" "${domain}" "${certdir}/privkey.pem" "${certdir}/cert.pem" "${certdir}/fullchain.pem" "${certdir}/chain.pem" "${timestamp}"

function validate_xml {
#Check if API call got status 200 (OK)
STATUSCODE=`xmlstarlet sel -t -v "/response/status/code" /tmp/api-log.xml`
if [ "$STATUSCODE" -ne 200 ]; then
        ERRORCODE=`xmlstarlet sel -t -v "/response/status/text" /tmp/api-log.xml`
        echo "Error: $ERRORCODE"
        exit 1
fi
}

deploy_cert () {

        #Create single PEM
        cat $5 > /etc/ssl/private/certs/$2/$2.pem
        cat $3 >> /etc/ssl/private/certs/$2/$2.pem
        chmod 600 /etc/ssl/private/certs/$2/$2.pem
        date=`date +"%F"`

        #upload cert
        cert="/etc/ssl/private/certs/$2/$2.pem"
        curl -s -X POST --data-urlencode loadbalancerid="$LOADBALANSERID" --data-urlencode certificatename="letsencrypt-$date" --data-urlencode certificate="`base64 -i $cert |tr -d '\012'`" -k --basic -u $USER:$KEY https://api.glesys.com/loadbalancer/addcertificate/ > /tmp/api-log.xml
        validate_xml

        #Get name of frontend which is listening on 443
        curl -s -X POST --data-urlencode loadbalancerid="$LOADBALANSERID" -k --basic -u $USER:$KEY https://api.glesys.com/loadbalancer/details/ > /tmp/api-log.xml
        validate_xml
        frontend=`xmlstarlet sel -t -v "/response/loadbalancer/frontends/item[port=443]/name" /tmp/api-log.xml`

        #change cert on frontend
        curl -s -X POST --data-urlencode loadbalancerid="$LOADBALANSERID" --data-urlencode frontendname="$frontend" --data-urlencode sslcertificate="letsencrypt-$date" -k --basic -u $USER:$KEY https://api.glesys.com/loadbalancer/editfrontend/ > /tmp/api-log.xml
        validate_xml
        DONE="yes"
}

unchanged_cert () {

    echo "Certificate for domain $2 is still valid - no action taken"
    DONE="yes"

}


exit_hook () {
    # - You might want to restart your web server here or
    # - Truncate log file, since no errors occured
    :
}

# Prefix all words using first argument as prefix.
_prefix () {
    local prefix=$1; shift
    printf "%s" "${@/#/$prefix}"
}





# API query parts.
CURL="curl -sS -X POST -k --basic"
API_ENDPOINT="--url https://api.glesys.com/domain"
glesys_api () {
    local METHOD=$1; shift

    # Add --data-urlencode to all arguments.
    local PARAMS=$(_prefix " --data-urlencode " $@)

    # Log query.
    echo "\n$TIME: $CURL $API_ENDPOINT/$METHOD/ $PARAMS"

    # Perform actual query in a safe manner.
    $CURL $API_ENDPOINT/$METHOD/ $PARAMS -K- <<< "-u $USER:$KEY" | tee /tmp/api-log.xml

    # Make sure it succeeded.
    _validate_response
} >> $LOGFILE

_start_log $@

# Run hook (if defined).
declare -f $HOOK > /dev/null && $HOOK $@
exit 0
