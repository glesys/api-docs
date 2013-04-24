#!/bin/bash

CORES=$(grep 'model name' /proc/cpuinfo | wc -l)
LOAD=$(uptime | grep "load average" | awk '{print $12}' | sed 's/\..*//g')
if [ $LOAD -ge $CORES ]; then
        NEWCORES=$((CORES+1));
		/usr/bin/curl -X POST -d serverid=vz1234567\&cpucores=$NEWCORES -k --basic -u cl12345:[api-key] https://api.glesys.com/server/edit/
        (
         echo -e "Adding a core. Server has now $NEWCORES cores.\n"
         uptime
        ) | mail -s "High CPU on server $HOSTNAME: average load > $CORES - CPU report" user@mail.com
fi

if [ $LOAD -lt $CORES ] && [ $CORES -gt 1 ]; then
        NEWCORES=$((CORES-1));
		/usr/bin/curl -X POST -d serverid=vz1234567\&cpucores=$NEWCORES -k --basic -u cl12345:[api-key] https://api.glesys.com/server/edit/
        (
         echo -e "Removing a core. Server has now $NEWCORES cores.\n"
         uptime
        ) | mail -s "Server $HOSTNAME has to many cores: average load < $CORES - CPU report" user@mail.com
fi