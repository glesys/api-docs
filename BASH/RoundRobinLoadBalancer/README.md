#Basic Round Robin load blancing with GleSYS API

Author: [Emil Andersson](/emil-nasso)

One of the easiest ways to loadbalance a webservice is by using [DNS Round Robin](http://en.wikipedia.org/wiki/Round-robin_DNS). In its simplest form its just two servers with their own ip addresses. The domain in use has two A-records with identical hosts but these to different ip addresses. The traffic will then be distributed between these two servers.

One of the problem with this simple method is that if one of the web servers goes down, half of the users wont be able to use the service.

This basic BASH-script tries to solve this problem. This script should be seen as an example for what can be done using GleSYS API. It is not intended to be used in an production environment.

The script is run on both the webservers. To check if the other server is still working a page is requested and if a predetermined string is found it is regarded as up. If the string is not found on the page, the server is regarded as down.

* If the other server is up, nothing happends.
* If the other server is down, this server updates the DNS-records and removes the server that is not working from the records. It the continues polling the downed server.
    * If the server is still down, nothing happens.
    * If the comes up again the records is reset to it original state (one record per server).

For this to work the script has to be run on both servers and the `THIS` and `THAT` variables has to be set so that the servers are referencing each other.

Feel free to add to this example but think both two and three times before using it in production. :)

