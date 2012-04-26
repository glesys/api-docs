#Mini-controlpanel
This example code contains three files:

##APIClient.php

Authors: [Magnus Johansson](/dragontoe), [Emil Andersson](/emil-nasso)

Contains functions for calling functions in the API using HTTP get or post. This relies heavily on the PHP module cURL so make sure that module is installed on your server.

##full_example.php

Author: Andreas Jönsson

An example script that allows you to make changes on a predetermined server. This makes it easy to reboot or upgrade a server for example, from a mobile web browser or from an external script. All you need is to do visit the URL of the script and fill out the password (which is defined in the script) and click on the action that you want to perform. You can also supply the action and password in the URL by appending get-variables. The implemented functions are:

* **serverlow** - Change amount of memory/cpu allocated to the server to handle low traffic.
* **serverpeak** - Change amount of memory/cpu allocated to the server to handle high (peak) traffic.
* **reboot** - Restart the server
* **details** - Get details about the server such as current hardware configuration, ip addresses and more.
* **status** - Get status information about the server such as state (running, stopped etc), cpu usage, memory usage and more.
* **memchange** - Check how much memory is used on the server and downgrade/upgrade the server as needed, automatically.
* **console** - Get all the information you need to connect to the server using VNC.

## mini_example.php

Author: Andreas Jönsson

A smaller version of full_example.php containing only the reboot-function.
