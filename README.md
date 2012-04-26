***The GleSYS API is currently in BETA-state***

#What is this?
The is the place to go for [documentation](/glesys/api/wiki), [examples](/glesys/api/) and [support/feedback](/glesys/api/issues) for [GleSYS](http://www.glesys.se) API. To activate the API on your account and to create API-keys, login at [GleSYS Control Panel](https://customer.glesys.com). In the early stages of our beta you have to send an email to [support@glesys.se](mailto:support@glesys.se) so that we can activate the API on you account for you.

#Where can I find documentation?
The [documentation](/glesys/api/wiki) can be found right here on github in our [wiki](/glesys/api/wiki). It comes in the form of an [introduction](/glesys/api/wiki/Api-Introduction) and a full [documentation of all functions available in GleSYS API](/glesys/api/wiki/API-Documentation). This documentation has been automatically generated from the API.

#Where can I find examples?
The examples are available right here on our [github repo](/glesys/api/). They are broken up by scripting/programming language and if you browse around in the repository you will find that each example has its own description.

Please feel free to contribute with more examples. You could either do a pull-request or send us an email to [support@glesys.se](mailto:support@glesys.se)

Remember that all these examples are just examples. If you want to use any of these scripts in an production environment, its on your own responsibility. 

##Current examples
###BASH
[All bash examples](https://github.com/GleSYS/API/tree/master/BASH)
* [LocalMemUpgrade](https://github.com/GleSYS/API/tree/master/BASH/LocalMemUpgrade) - Upgrade memory on a local server based on memoryusage.
* [RemoteMemUpgrade](https://github.com/GleSYS/API/tree/master/BASH/RemoteMemUpgrade) - Upgrade memory on a remote server based on memoryusage.
* [RoundRobinLoadBalancer](https://github.com/GleSYS/API/tree/master/BASH/RoundRobinLoadBalancer) - Basic round robin load balancer with availability monitoring.

###PHP
[All PHP examples](https://github.com/GleSYS/API/tree/master/PHP)
* [InvoiceRSS](https://github.com/GleSYS/API/tree/master/PHP/InvoiceRSS) - List invoices in a RSS-feed.
* [MiniControlpanel](https://github.com/GleSYS/API/tree/master/PHP/MiniControlpanel) - A miniature control panel.

###Ruby
[All Ruby examples](https://github.com/GleSYS/API/tree/master/Ruby)
* [CreateDestroyList](https://github.com/GleSYS/API/tree/master/Ruby/CreateDestroyList) - Create, Destroy and List servers.

#Are there any frameworks available?
If you are looking for a framework to manage your GleSYS virtual servers take a look at the following projects:

* [fog](http://fog.io) - The Ruby cloud services library.
* [jclouds](http://www.jclouds.org/) - API abstractions as java and clojure libraries.

If you know about any other frameworks that support the GleSYS API, please let us know!

#Where can I get support?
You can either [open a new issue here on github](/glesys/api/issues) or send us an email at [support@glesys.se](mailto:support@glesys.se)

