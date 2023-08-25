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
#   echo "export USER=CL12345" > /etc/ssl/private/.glesys-credentials
#   echo "export KEY=KEY_GOES_HERE" >> /etc/ssl/private/.glesys-credentials
#   chmod 600 /etc/ssl/private/.glesys-credentials
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

request_failure() {
    local STATUSCODE="${1}" REASON="${2}" REQTYPE="${3}" HEADERS="${4}"

    # This hook is called when an HTTP request fails (e.g., when the ACME
    # server is busy, returns an error, etc). It will be called upon any
    # response code that does not start with '2'. Useful to alert admins
    # about problems with requests.
    #
    # Parameters:
    # - STATUSCODE
    #   The HTML status code that originated the error.
    # - REASON
    #   The specified reason for the error.
    # - REQTYPE
    #   The kind of request that was made (GET, POST...)
    # - HEADERS
    #   HTTP headers returned by the CA

    # Simple example: Send mail to root
    # printf "Subject: HTTP request failed failed!\n\nA http request failed with status ${STATUSCODE}!" | sendmail root
}

deploy_challenge () {
    _parse_domains $@

    # Create TXT records for all ACME challenges.
    for domain in "${domains[@]}"; do
        read DOMAIN FQDN CHALLENGE <<< "$domain"
        glesys_api addrecord domainname=$DOMAIN \
            host=_acme-challenge.$FQDN. type=TXT ttl=300 data=$CHALLENGE
    done

    # Wait for settings to apply on the endpoint.
    sleep 2
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
}

sync_cert() {
    local KEYFILE="${1}" CERTFILE="${2}" FULLCHAINFILE="${3}" CHAINFILE="${4}" REQUESTFILE="${5}"

    # This hook is called after the certificates have been created but before
    # they are symlinked. This allows you to sync the files to disk to prevent
    # creating a symlink to empty files on unexpected system crashes.
    #
    # This hook is not intended to be used for further processing of certificate
    # files, see deploy_cert for that.
    #
    # Parameters:
    # - KEYFILE
    #   The path of the file containing the private key.
    # - CERTFILE
    #   The path of the file containing the signed certificate.
    # - FULLCHAINFILE
    #   The path of the file containing the full certificate chain.
    # - CHAINFILE
    #   The path of the file containing the intermediate certificate(s).
    # - REQUESTFILE
    #   The path of the file containing the certificate signing request.

    # Simple example: sync the files before symlinking them
    # sync "${KEYFILE}" "${CERTFILE} "${FULLCHAINFILE}" "${CHAINFILE}" "${REQUESTFILE}"
}

deploy_cert () {
    ######## GleSYS Specific ############################################
    echo "You should restart or reload the service that handel the SSL certs"
    #
    # Uncomment what matches servers setup.
    #
    # Apache2
    #/etc/init.d/apache2 restart
    #
    # Nginx
    #/etc/init.d/nginx restart
    #
    # HaProxy
    #cat $5 > /etc/haproxy/ssl/$2.pem
    #cat $3 >> /etc/haproxy/ssl/$2.pem
    #chmod 600 /etc/haproxy/ssl/$2.pem
    #/etc/init.d/haproxy reload
    #
    # Postfix
    #/etc/init.d/postfix reload
    #
    #####################################################################
}

invalid_challenge() {
    local DOMAIN="${1}" RESPONSE="${2}"

    # This hook is called if the challenge response has failed, so domain
    # owners can be aware and act accordingly.
    #
    # Parameters:
    # - DOMAIN
    #   The primary domain name, i.e. the certificate common
    #   name (CN).
    # - RESPONSE
    #   The response that the verification server returned
    # Simple example: Send mail to root
    # printf "Subject: Validation of ${DOMAIN} failed!\n\nOh noez!" | sendmail root
}

exit_hook () {
    # - You might want to restart your web server here or
    # - Truncate log file, since no errors occured
    # Uncomment the next line if you want to remove the logfile at this point
    # rm $LOGFILE
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
