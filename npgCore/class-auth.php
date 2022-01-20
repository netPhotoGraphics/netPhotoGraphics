<?php

/**
 * USER credentials handlers
 *
 * Class Plugins may override the stand class-auth authentication library. See for example
 * LDAP_auth, an authentication plugin that authenticates via an LDAP server.
 *
 *
 * Replacement libraries must implement two classes:
 * 		"npg_Authority" class: Provides the methods used for user authorization and management
 * 			store an instantiation of this class in $_authority.
 *
 * 		"npg_Administrator" class: supports the basic needs for object manipulation of administrators.
 *
 * (You can include the <code>lib-auth.php</code> script and extend/overwrite _Authority
 * and _Administrator class methods if that suits your needs.)
 *
 * The global $_current_admin_obj represents the current admin.
 *
 * The following elements need to be present in any alternate implementation in the
 * array returned by getAdministrators().
 *
 * 		In particular, there should be array elements for:
 * 				'id' (unique), 'valid' , 'user' (unique),	'pass',	'name', 'email', 'rights',
 * 				'group', 'other_credentials', 'lastloggedin' and 'date
 *
 * 		So long as all these indices are populated it should not matter when and where
 * 		the data is stored.
 *
 * 		Administrator class methods are required for these elements as well.
 *
 * 		The getRights() method must define at least the rights defined by the method in
 * 		this library.
 *
 * 		The checkAuthorization() method should promote the "most privileged" Admin to
 * 		ADMIN_RIGHTS to insure that there is some user capable of adding users or
 * 		modifying user rights.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
require_once(__DIR__ . '/lib-auth.php');

class npg_Authority extends _Authority {

}

class npg_Administrator extends _Administrator {

}
