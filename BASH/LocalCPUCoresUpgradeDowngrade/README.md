#Add/Remove CPU Cores on a virtual server using GleSYS API

Author: [Jasper Metselaar](/formmailer)

This BASH-script will add or remove CPU cores for a Glesys virtual server using the API.

The script will add a CPU core automatically when the CPU load has been 1.0 or more per core during the past 15 minutes.

A core will be removed when the CPU usage drops. (below the number of cores minus 1.0.)

You will be notified by mail when cores have been added or removed.

This script should be run as a cron-job (typically every 5 to ten minutes) to upgrade and downgrade a server depending on 
the current needs. 
