<?php
/**
 * @package GleSYS API
 *
 * @copyright (c) 2015 Jari (tumba25) Kanerva <jari@tumba25.net> http://www.tumba25.com
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 */

if (!class_exists('glesys_api'))
{
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	$path = dirname(__FILE__);
	require("$path/glesys_api.$phpEx");
}

class glesys_server extends glesys_api
{
	/**
	 * Get a list of all servers on this account.
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_list()
	{
		$success = $this->api_request('server/list');
		return($success);
	}

	/**
	 * Get detailed information about a server such as hostname, hardware configuration (cpu, memory and disk),
	 * ip adresses, cost, transfer, os and more.
	 * 'state' in the output will be set to null if the input parameter 'includestate' is set to false.
	 * If it is set to true it will return 'running', 'stopped' or 'locked'.
	 * The default value for 'includestate' is false, for performance reasons.
	 *
	 * @param string $serverid required.
	 * @param bool $includestate optional.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_details($serverid, $includestate = false)
	{
		$args = array(
			'serverid'		=> $serverid,
			'includestate'	=> $this->gen_int($includestate),
		);

		$success = $this->api_request('server/details', $args);
		return($success);
	}

	/**
	 * Get status information about a server.
	 * This information may contain on/off-status, cpu/memory/hdd-usage, bandwidth consumption and uptime.
	 * Not all information is supported by all platforms.
	 *
	 * @param string $serverid required.
	 * @param string $statustype optional.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_status($serverid, $statustype = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if ($statustype)
		{
			$args['statustype'] = $statustype;
		}

		$success = $this->api_request('server/status', $args);
		return($success);
	}

	/**
	 * Reboots a server. Does the same thing as server/stop with type set to reboot
	 *
	 * @param string $serverid required.
	 * @param string $transactionstarttime optional.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_reboot($serverid, $transactionstarttime = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if ($transactionstarttime)
		{
			$args['transactionstarttime'] = $transactionstarttime;
		}

		$success = $this->api_request('server/reboot', $args);
		return($success);
	}

	/**
	 * Shutdown, power off or reboot a server. Not all shutdown types are supported on all platforms.
	 * Only Xen supports a hard power off.
	 *
	 * @param string $serverid required.
	 * @param string $type optional
	 * @param string $transactionstarttime optional.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_stop($serverid, $type = false, $transactionstarttime = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if ($type)
		{
			$args['type'] = $type;
		}

		if ($transactionstarttime)
		{
			$args['transactionstarttime'] = $transactionstarttime;
		}

		$success = $this->api_request('server/stop', $args);
		return($success);
	}

	/**
	 * Power up (boot) a server.
	 *
	 * @param string $serverid required.
	 * @param string $bios optional
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_start($serverid, $bios = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if ($bios)
		{
			$args['bios'] = $bios;
		}

		$success = $this->api_request('server/start', $args);
		return($success);
	}

	/**
	 * Create a new virtual server.
	 * An Xen server needs an ip-address when created.
	 * If you use the word 'any' as an argument, instead of an ip address, one will be choosen for you, automatically.
	 * If you dont supply the ip argument, one will be choosen for you.
	 *
	 * @param $data = array(
	 * 	 'datacenter'	=> required
	 * 	 'platform'		=> required
	 * 	 'hostname'		=> required
	 * 	 'templatename'	=> required
	 * 	 'disksize'		=> required
	 * 	 'memorysize'	=> required
	 * 	 'cpucores'		=> required
	 * 	 'rootpassword'	=> required
	 * 	 'transfer'		=> required
	 * 	 'description'	=> optional
	 * 	 'ip'			=> optional
	 * 	 'ipv6'			=> optional
	 * );
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_create($data)
	{
		$success = $this->api_request('server/create', $data);
		return($success);
	}

	/**
	 * Destroy a server and remove all files on it.
	 * This change is final and cannot be undone.
	 * You will NOT be asked for confirmation.
	 *
	 * @param string $serverid required.
	 * @param string $keepip required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_destroy($serverid, $keepip)
	{
		$args = array(
			'serverid'	=> $serverid,
			'keepip'	=> $keepip,
		);

		$success = $this->api_request('server/destroy', $args);
		return($success);
	}

	/**
	 * Edit the configuration of a server.
	 * You can change such parameters as amount of cpu cores, memory, hdd and transfer.
	 * Most arguments are optional so you can change all, none or just a few of the parameters at a time.
	 *
	 * @param $serverid required
	 * @param $data = array(
	 * 	 'disksize'		=> optional
	 * 	 'memorysize'	=> optional
	 * 	 'cpucores'		=> optional
	 * 	 'transfer'		=> optional
	 * 	 'hostname'		=> optional
	 * 	 'description'	=> optional
	 * );
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_edit($serverid, $data = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('server/edit', $args);
		return($success);
	}

	/**
	 * Create an copy (clone) of another server. This copies all files on the server.
	 * It will not boot the clone when the cloning is done. This has to be done manually.
	 * The ip-adresses are not moved from the original servers.
	 * The clone will not have any ip-address so if one is needed it should be added by the ip/add-function.
	 * Cloning is only supported on the OpenVZ-platform.
	 *
	 * @param $serverid required
	 * @param $hostname required
	 * @param $data = array(
	 * 	 'disksize'		=> optional
	 * 	 'memorysize'	=> optional
	 * 	 'cpucores'		=> optional
	 * 	 'transfer'		=> optional
	 * 	 'description'	=> optional
	 * 	 'datacenter'	=> optional
	 * );
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_clone($serverid, $hostname, $data = false)
	{
		$args = array(
			'serverid'	=> $serverid,
			'hostname'	=> $hostname,
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('server/clone', $args);
		return($success);
	}

	/**
	 * Only for OpenVZ. Get the beancounter limits for a server.
	 * The limits lets you know if you have exceeded any of the limits that are set for your server.
	 * These are limitations such as allocated memory, open files and more.
	 * More information about the parameters returned by this function can be found at http://wiki.openvz.org/UBC
	 * You can reset the failcount of a limit using the function server/resetlimit.
	 *
	 * @param string $serverid required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_limits($serverid)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		$success = $this->api_request('server/limits', $args);
		return($success);
	}

	/**
	 * Only for OpenVZ. Resets the failcount for a beancounters limit to zero.
	 * See the documentation for server/limits for more information.
	 *
	 * @param string $serverid required.
	 * @param string $type required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_resetlimit($serverid, $type)
	{
		$args = array(
			'serverid'	=> $serverid,
			'type'		=> $type,
		);

		$success = $this->api_request('server/resetlimit', $args);
		return($success);
	}

	/**
	 * Get all the connection information you need to be able to connect to an server with VNC.
	 * This gives you console access and could be useful if you have locked yourself out using a firewall, or in a number of other situations.
	 *
	 * @param string $serverid required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_console($serverid)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		$success = $this->api_request('server/console', $args);
		return($success);
	}

	/**
	 * Reset the root-password of a OpenVZ-server to a password of your choice.
	 *
	 * @param string $serverid required.
	 * @param string $rootpassword required.
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_resetpassword($serverid, $rootpassword)
	{
		$args = array(
			'serverid'		=> $serverid,
			'rootpassword'	=> $rootpassword,
		);

		$success = $this->api_request('server/resetpassword', $args);
		return($success);
	}

	/**
	 * Get a list of all operating system templates that are available when creating a server using the server/create-function.
	 *
	 * @param none
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_templates()
	{
		$success = $this->api_request('server/templates');
		return($success);
	}

	/**
	 * Get a list of all operating system templates that are available when creating a server using the server/create-function.
	 *
	 * @param string $serverid optional
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_allowedarguments($serverid = false)
	{
		$args = array();

		if (!empty($serverid))
		{
			$args['serverid'] = $serverid;
		}

		$success = $this->api_request('server/allowedarguments', $args);
		return($success);
	}

	/**
	 * Return resource usage over time for server.
	 *
	 * @param $args = array(
	 *   'serverid'		=> required
	 *   'resource'		=> required
	 *   'resolution'	=> required
	 * );
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_resourceusage($args)
	{
		$success = $this->api_request('server/resourceusage', $args);
		return($success);
	}

	/**
	 * List all costs for this server such as cpu-, memory-, license- and managed hosting-costs and more.
	 *
	 * @param string $serverid required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_costs($serverid)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		$success = $this->api_request('server/costs', $args);
		return($success);
	}

	/**
	 * List all isos available for mounting in a virtual machine.
	 *
	 * @param string $serverid required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_listiso($serverid)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		$success = $this->api_request('server/listiso', $args);
		return($success);
	}

	/**
	 * Mount one of the isos listed in server/listiso on a virtual machine. This currently only supports vmware-servers.
	 *
	 * @param string $serverid required
	 * @param string $isofile optional
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_mountiso($serverid, $isofile = false)
	{
		$args = array(
			'serverid'	=> $serverid,
		);

		if (!empty($isofile))
		{
			$args['isofile'] = $isofile;
		}

		$success = $this->api_request('server/mountiso', $args);
		return($success);
	}

	/**
	 * Add a iso for future usage with server/mountiso.
	 * Upload the iso to an GleSYS archive volume and provide the path to the iso together
	 * with the username and password to the archive volume.
	 *
	 * @param $args = array(
	 *   'archiveusername'	=> required
	 *   'archivepassword'	=> required
	 *   'archivepath'		=> required
	 * );
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_api->fetch_response().
	 */
	public function server_addiso($args)
	{
		$success = $this->api_request('server/addiso', $args);
		return($success);
	}
}
