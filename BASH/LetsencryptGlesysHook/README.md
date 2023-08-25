# letsencrypt-glesys-hook (DNS-01 and the GleSYS API).
How to set up letsencrypt via DNS (glesys-dns01) Works for wildcard

## Dependencies
- dehydrated (apt-get install dehydrated)
- curl
- xmlstarlet (apt-get install curl xmlstarlet)
- GleSYS API credentials (DOMAIN permissions for list, add, remove records)
  (in case you want to upload the cert to load-balancer you will need permissions to the LB too)

## Instructions

- Create an API key for the GleSYS API in the control panel as described above
- echo "export USER=CL12345" > /etc/ssl/private/.glesys-credentials
- echo "export KEY=KEY_GOES_HERE" >> /etc/ssl/private/.glesys-credentials
- echo "export LOADBALANSERID=lb1234567" >> /etc/ssl/private/.glesys-credentials (in case you use load-balancer)
- chmod 600 /etc/ssl/private/.glesys-credentials
- cd /etc/dehydrated
- wget https://raw.githubusercontent.com/glesys/api-docs/master/BASH/LetsencryptGlesysHook/glesys-dns-01-hook.sh
- wget https://raw.githubusercontent.com/glesys/api-docs/master/BASH/LetsencryptGlesysHook/glesys-dns-01-lbl-hook.sh
- wget https://raw.githubusercontent.com/lukas2511/dehydrated/master/dehydrated
- wget -q https://raw.githubusercontent.com/glesys/api-docs/master/BASH/LetsencryptGlesysHook/config -O /etc/dehydrated/config
- chmod 700 /etc/dehydrated/glesys-dns-01-hook.sh /etc/dehydrated/glesys-dns-01-lbl-hook.sh /etc/dehydrated/dehydrated
- edit your /etc/dehydrated/config to include the hook you want to use
  * glesys-dns-01-hook.sh # if you don't have a load-balancer # Active by default
  * glesys-dns-01-lbl-hook.sh # to be upload the cert direct to the load-balancer
- echo "example.com *.example.com" > /etc/dehydrated/domains.txt # your domain here!
- ./dehydrated --register --accept-terms
- ./dehydrated -c
