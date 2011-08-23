<?php

/*
 *
 * DirectAdmin Register Driver
 *
 * Driver to register new email accounts via DirectAdmin Control Panel
 *
 * @version 1.0
 * @author Maciej Nowakowski <macnow@gmail.com>
 *
 */

include("httpsocket.php");

function da_api_connect($sock, $domain) {
  	$rcmail = rcmail::get_instance();
	if ($rcmail->config->get('da_use_ssl')=='true')
	{
		$sock->connect("ssl://".$rcmail->config->get('da_ip'), 2222);
	} else { 
		$sock->connect($rcmail->config->get('da_ip'), 2222);
	} 
	$sock->set_login($rcmail->config->get('da_login'),$rcmail->config->get('da_password'));
	$sock->query('/CMD_API_DOMAIN_OWNERS');
	$result = $sock->fetch_parsed_body();
	$domain = str_replace(".","_",$domain);
	$domain_owner = $result[$domain];
	$sock->set_login($rcmail->config->get('da_login')."|$domain_owner",$rcmail->config->get('da_password'));

}
function da_api_query($sock, $params) {
	$sock->query('/CMD_API_POP', $params);
	$result = $sock->fetch_parsed_body();
	return $result;
}
 
// check if user already exists
function rcube_register_user_exists($user = ""){

  $domain = substr($user, strpos($user, '@')+1);
  $user = substr($user, 0, strpos($user, '@'));

  $sock = new HTTPSocket;
  da_api_connect($sock, $domain);
  $result=da_api_query($sock, array(
	  'action' => 'list',
	  'domain' => $domain,
	     ));

  // true = user already exists => abort, else false
  return in_array($user,$result['list']);
}
      
// check if alias already exits
function rcube_register_alias_exists($user = ""){

  $domain = substr($user, strpos($user, '@')+1);
  $user = substr($user, 0, strpos($user, '@'));

  $sock = new HTTPSocket;
  da_api_connect($sock, $domain);
  $result=da_api_query($sock, array(
	  'action' => 'list',
	  'domain' => $domain,
	     ));

  // true = user already exists => abort, else false
  return in_array($user,$result['list']);
}

// add user
function rcube_register_new_imap_user($user = "", $pass = "", $config = array()){

  $domain = substr($user, strpos($user, '@')+1);
  $user = substr($user, 0, strpos($user, '@'));

  $sock = new HTTPSocket;
  da_api_connect($sock, $domain);
  $result=da_api_query($sock, array(
	  'action' => 'create',
	  'domain' => $domain,
	  'user' => $user,
	  'passwd' => $pass,
	  'quota' => '0'
	     ));
  // false = creating user failed => abort, else true
  if ($result['error'] != "0") return false;
  else return true;
}

?>
