#Upgrade/downgrade the memory of a virtual server using GleSYS API

Author: [Jasper Metselaar](/formmailer)

This BASH-script upgrades (or downgrades) the memory of a local server based on the current amount and current usage.
It reads the memory usage information from the "free" command. The script doesn't just look at the free memory numbers,
but takes buffers and cache in account. 

When the server gets low on memory (90%) it will upgrade one step (256MB, 512MB, 1024MB or 2048MB, depending on the 
amount of RAM that is currently assigned). Upgrade is done using GleSYS API.
If the server doesn't use the allocated memory anymore, the script will remove RAM using the API

You will be notified by mail when memory has been added or removed.

This script should be run as a cron-job (typically every minute) to upgrade and downgrade a server depending on 
the current needs. 
