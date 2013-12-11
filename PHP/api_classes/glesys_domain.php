<?php
/**
 * @copyright (c) 2013 Jari (tumba25) Kanerva <jari@tumba25.net> http://www.tumba25.com
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 */

if (!class_exists('glesys_api'))
{
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	$path = dirname(__FILE__);
	require("$path/glesys_api.$phpEx");
}

class glesys_domain extends glesys_api
{
	/**
	 * Get a list of all domains on this account.
	 * The glesysnameserver-item in the returned data shows if this domain actually uses glesys nameservers or not.
	 * It looks in the domains NS-records for .namesystem.se
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_list()
	{
		$success = $this->api_request('domain/list');
		return($success);
	}

	/**
	 * Add a domain to our dns-system.
	 * Some default records will be created on the domain.
	 * If the optional argument create_records is set to 0, only NS and SOA records will be created by default.
	 *
	 * @param required string $domainname.
	 * @param optional array $args, all array parts are also optional array(
	 *   'primarynameserver'	=> (string) default = 'ns1.namesystem.se.'
	 *   'responsibleperson'	=> (string) default = 'registry.glesys.se.'
	 *   'ttl'					=> (int) seconds, default = 3600
	 *   'refresh'				=> (int) seconds, default = 10800
	 *   'retry'				=> (int) seconds, default = 2700
	 *   'expire'				=> (int) seconds. default = 1814400
	 *   'minimum'				=> (int) seconds, default = 10800
	 *   'createrecords'		=> (int) 0|1, default = 1
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_add($domainname, $data = array())
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('domain/add', $args);
		return($success);
	}

	/**
	 * Register a domain.
	 * The domain has to be added to the account first by calling domain/add.
	 * The optional argument numyears decides how many years the domain in registered for.
	 * It defaults to the lowest number of years allowed for the tld.
	 *
	 * @param array $domain_data, array(
	 *   'domainname'	=> (string) required
	 *   'email'		=> (string) required
	 *   'firstname'	=> (string) required
	 *   'lastname'		=> (string) required
	 *   'organization'	=> (string) required
	 *   'organizationnumber'	=> (string) required
	 *   'address'		=> (string) required
	 *   'city'			=> (string) required
	 *   'zipcode'		=> (string) required
	 *   'country'		=> (string) required
	 *   'phonenumber'	=> (string) required
	 *
	 *   'fax'			=> (string) optional
	 *   'numyears'		=> (string) optional
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_register($domain_data)
	{
		$domain_data['domainname'] = $this->punycode_endoce($domain_data['domainname']);

		$success = $this->api_request('domain/register', $domain_data);
		return($success);
	}

	/**
	 * Transfer a domain to GleSYS
	 *
	 * @param array $domain_data, array(
	 *   'domainname'	=> (string) required
	 *   'email'		=> (string) required
	 *   'firstname'	=> (string) required
	 *   'lastname'		=> (string) required
	 *   'organization'	=> (string) required
	 *   'organizationnumber'	=> (string) required
	 *   'address'		=> (string) required
	 *   'city'			=> (string) required
	 *   'zipcode'		=> (string) required
	 *   'country'		=> (string) required
	 *   'phonenumber'	=> (string) required
	 *
	 *   'fax'			=> (string) optional
	 *   'numyears'		=> (string) optional
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_transfer($domain_data)
	{
		$domain_data['domainname'] = $this->punycode_endoce($domain_data['domainname']);

		$success = $this->api_request('domain/transfer', $domain_data);
		return($success);
	}

	/**
	 * Renew a domain
	 *
	 * @param string $domainname required
	 * @param int $numyears required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_renew($domainname, $numyears)
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
			'numyears'		=> $numyears,
		);

		$success = $this->api_request('domain/renew', $args);
		return($success);
	}

	/**
	 * Activate/deactivate autorenewal for a domain.
	 *
	 * @param string $domainname required
	 * @param int $autorenew required (0 or 1)
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_autorenew($domainname, $autorenew)
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
			'autorenew'		=> $autorenew,
		);

		$success = $this->api_request('domain/autorenew', $args);
		return($success);
	}

	/**
	 * Gets detailed information about this domain. This is basically the information available in the domains SOA-record.
	 *
	 * @param string $domainname required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_details($domainname)
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		$success = $this->api_request('domain/details', $args);
		return($success);
	}

	/**
	 * Checks if the domain is available for registration.
	 *
	 * @param string $domainname required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_available($domainname)
	{
		$args = array(
			'search'	=> $this->punycode_endoce($domainname),
		);

		$success = $this->api_request('domain/available', $args);
		return($success);
	}

	/**
	 * Gets a full domain pricelist with all available TLDs
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_pricelist()
	{
		$success = $this->api_request('domain/pricelist');
		return($success);
	}

	/**
	 * Add a domain to our dns-system.
	 * Some default records will be created on the domain.
	 * If the optional argument create_records is set to 0, only NS and SOA records will be created by default.
	 *
	 * @param required string $domainname.
	 * @param optional array $args, all array parts are also optional array(
	 *   'primarynameserver'	=> (string) default = 'ns1.namesystem.se.'
	 *   'responsibleperson'	=> (string) default = 'registry.glesys.se.'
	 *   'ttl'					=> (int) seconds, default = 3600
	 *   'refresh'				=> (int) seconds, default = 10800
	 *   'retry'				=> (int) seconds, default = 2700
	 *   'expire'				=> (int) seconds. default = 1814400
	 *   'minimum'				=> (int) seconds, default = 10800
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_edit($domainname, $data = array())
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('domain/edit', $args);
		return($success);
	}

	/**
	 * Delete a domain from the dns system.
	 *
	 * @param string $domainname required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_delete($domainname)
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		$success = $this->api_request('domain/delete', $args);
		return($success);
	}

	/**
	 * List records for a given domain.
	 * This information includes the recordid.
	 *
	 * @param string $domainname required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_listrecords($domainname)
	{
		$args = array(
			'domainname'	=> $this->punycode_endoce($domainname),
		);

		$success = $this->api_request('domain/listrecords', $args);
		return($success);
	}

	/**
	 * Update a records dns information.
	 * You can get the record id using domain_listrecords().
	 *
	 * @param required string $recordid.
	 * @param optional array(
	 *   'ttl'	=> (int) optional
	 *   'host'	=> (string) optional
	 *   'type'	=> (string) optional
	 *   'data'	=> (string) optional
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_updaterecord($recordid, $data = array())
	{
		$args = array(
			'recordid'	=> $recordid,
		);

		if (!empty($data))
		{
			$args = array_merge($args, $data);
		}

		$success = $this->api_request('domain/updaterecord', $args);
		return($success);
	}

	/**
	 * Adds a dns record to a domain.
	 *
	 * @param array(
	 *   'domainname'	=> (string) required
	 *   'host'			=> (string) required
	 *   'type'			=> (string) required
	 *   'data'			=> (string) required
	 *
	 *   'ttl'			=> (int) optional
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_addrecord($data)
	{
		$data['domainname'] = $this->punycode_endoce($data['domainname']);

		$success = $this->api_request('domain/addrecord', $data);
		return($success);
	}

	/**
	 * Removes a dns record from a domain.
	 * You can get the record id using domain_listrecords().
	 *
	 * @param int $recordid required
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_deleterecord($recordid)
	{
		$args = array(
			'recordid'	=> $recordid,
		);

		$success = $this->api_request('domain/deleterecord', $args);
		return($success);
	}

	/**
	 * Lists the allowed arguments for some of the functions in this module such as register.
	 * Some tlds require that the registering part has connections to the country.
	 *
	 * @param void
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_allowedarguments()
	{
		$success = $this->api_request('domain/allowedarguments');
		return($success);
	}

	/**
	 * Change nameservers for a domain.
	 * Check the registrarinfo field in domain_details() to know if this can be done for a domain.
	 *
	 * @param array(
	 *   'domainname'	=> (string) required
	 *   'ns1'			=> (string) required
	 *   'ns2'			=> (string) required
	 *
	 *   'ns3'			=> (string) optional
	 *   'ns4'			=> (string) optional
	 * )
	 * @return true on success or false on failure.
	 * In both cases the response can be fetched using $glesys_domain->fetch_response().
	 */
	public function domain_changenameservers($data)
	{
		$data['domainname'] = $this->punycode_endoce($data['domainname']);

		$success = $this->api_request('domain/changenameservers', $data);
		return($success);
	}
}





























//
