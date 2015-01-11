<?php
include('config.php');
include('functions.php');

//Start by getting reserved IPs
$ip = get_reserved_ip();
if(!$ip){
	echo "No IPs available!, you need at least one IP reserved and unused.\n";
	die();
}

if($ip){
	echo "IP: ".$ip->ipaddress." will be used for the loadbalancer\n";
}

//Create loadbalancer
$lb = create_loadbalancer($ip->ipaddress,$ip->datacenter);
$loadbalancerid = $lb->loadbalancerid;

if($loadbalancerid){
	echo "A loadbalancer with id: ".$loadbalancerid." was created\n";
}else{
	echo "Loadbalancer could not be created\n";
}

//Create servers

while( $i < $numservers ){
		$servername = $clustername."-".$i;
		$server = create_server($ip->datacenter,$servername);
// Create IP Array here
		$server_ip[$i] = $server->response->server->iplist[0]->ipaddress;
		echo "A server with name: ".$servername." was created.\n";
	$i++;
}

//Add servers to loadbalancer
$loadbalancer_data = add_backend_frontend($clustername,$loadbalancerid,$mode,$stickysession,$port);

echo "Created Loadbalancer frontend and backend\n";

//Add targets to LB
echo "Adding targets to Loadbalancer\n";

add_targets($loadbalancerid, $loadbalancerid."-be", $server_ip, $port);
