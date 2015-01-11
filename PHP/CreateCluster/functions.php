<?php

include('php-restclient-master/restclient.php');

function get_reserved_ip()
{
	$data = array(
		'used'=>0	
	);
	$ips = _call('ip/listown',$data);

	if(count($ips->response->iplist) < 1) {	
		return false;
	} else {
		$ip = $ips->response->iplist[0];
		return $ip;
	}
}

function create_loadbalancer($ip, $datacenter)
{
	$data = array('name'=>'Demo-LB','datacenter'=>$datacenter,'ipaddress'=>$ip);
	$lb = _call('loadbalancer/create',$data);

	return $lb->response->loadbalancer;
}

function create_server($datacenter, $hostname, $platform = 'OpenVZ', $template = 'Debian 7.0 64-bit', $disk = '10', $memory = '1024', $cpu = '1', $rootpassword = 'Test123!')
{
	$data = array(
			'datacenter' => $datacenter,
			'platform'=>$platform,
		 	'hostname' =>$hostname,
			'templatename'=>$template,
			'disksize'=>$disk,
			'memorysize'=>$memory,
			'cpucores'=>$cpu,
			'rootpassword'=>$rootpassword
		);
	
	$server = _call('server/create',$data);

	return $server;

}

function add_backend_frontend( $name, $loadbalancerid, $mode, $stickysession, $port)
{

	//Create backend
		$data = array(
			'name' => $loadbalancerid."-be",
			'loadbalancerid'=>$loadbalancerid,
			'mode'=>$mode,
			'stickysession'=>$stickysession
		);
		$backend = _call('loadbalancer/addbackend',$data);
	//Create frontend
		$data = array(
			'name' => $loadbalancerid."-fe",
                        'loadbalancerid'=>$loadbalancerid,
			'port' => $port,
			'backendname'=>$loadbalancerid."-be"
		);
		$frontend =  _call('loadbalancer/addfrontend',$data);


		$output['backend'] = $backend;
		$output['frontend'] = $frontend;
		return $output;

}

function add_targets($loadbalancerid, $backendname, $targets, $port)
{
	$i = 0;

	foreach($targets as $target) {

		$data = array(
			'loadbalancerid' => $loadbalancerid,
			'backendname' => $backendname,
			'ipaddress' => $target,
			'port' => $port,
			'name' => $target,
			'weight' => 1
		);

		$target_data[$i] =  _call('loadbalancer/addtarget',$data);
		$i++;
		echo "Target with IP: ".$target." was added to LB\n";
	}

	return $target_data;
}

function _call($end_point, $payload)
{

	global $user;
        global $key;

        $api = new RestClient(array(
                'base_url' => 'https://'.$user.':'.$key.'@api.glesys.com'
        ));
        $data = array(
                'used'=>0
        );

        $result = $api->post($end_point.'/format/json',$payload);
        return $result->decode_response();
}
