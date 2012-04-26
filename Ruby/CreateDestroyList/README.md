#CreateDestroyList - GleSYS API Ruby example

Author: [Anton Lindström](/antonlindstrom)

This example contains methods to create, destroy and list servers in the Glesys Cloud.

The class is initialized and requires the neccesary files and is setting globals for the user id, api key, and response format.

Requests to the API are performed in the request method. It is using the base path of api.glesys.se and takes the url as an argument. An example is `/server/create`. All other arguments (if any) are sent in a hash (post). The key in the hash is the name of the argument and the value is the value to be sent. The settings method is an example of some of the arguments that can be supplied.

To create a server we use the `create` method where the hash called custom is supplied. The default value of hostname is `example.com`. The custom hash is then merged with the default settings so we will not have to supply all of the arguments. A request is sent and the response is parsed with JSON. The response is defined and the hash with values of serverid and ip are then returned.

`destroy` deletes a server from the account. The method takes the arguments id and keep_ip. The return value of this method is (if successful) the serverid of the server that was destroyed.

The method `get_ip` requests the IP of a server with the serverid of id. If an IP is assigned it will return that IP, otherwise it returns "NOT ASSIGNED".

To list all the servers on your account use the method `list` which will return an array of servers.

When using this script we first need to initialize the class with `api = Glesys.new(account_name, api_key)` and then use the methods within by calling `api.[method]` where method is `create`, `destroy`, `get_ip` or `list`.

To call the api for a list of all servers with their serverid, ip and hostname it is possible with this script to iterate through the servers in the api.list( `a.list.each do |s|` in the example) and assign the response to output which we afterwards writes out.
