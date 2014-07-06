<?php
/////////////////////////////////////////////////////////////////////////////////////////
// This script adds specified objectClass to all LDAP entries under specified $base_dn
///////////////////////////////////////////////////////////////////////////////////////

$ldap_host = "127.0.0.1";
$ldap_port = "389";
$base_dn = "ou=people,dc=company,dc=com";
$ldap_user ="cn=admin,dc=company,dc=com";
$ldap_pass = "password";

$conn = ldap_connect( $ldap_host, $ldap_port);
ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
$bind = ldap_bind($conn, $ldap_user, $ldap_pass);

$ou_filter = array("ou", "l", "postalAddress", "telephoneNumber");
$cn_filter = array("uid", "cn", "title", "mail", "telephoneNumber", "objectClass");

// Find all child organizationalUnit's from $base_dn
$ou_read = ldap_search($conn, $base_dn, "(objectClass=organizationalUnit)", $ou_filter);
ldap_sort ($conn, $ou_read, "ou");

$ou_entry = ldap_first_entry( $conn, $ou_read );
while ($ou_entry)
{
	$dn = ldap_get_dn($conn, $ou_entry);
	if($dn == $base_dn)
	{
		$ou_entry = ldap_next_entry($conn, $ou_entry);
		continue;
	}

	// Find all child inetOrgPerson's from current organizationalUnit
	$cn_read = ldap_search($conn, $dn, "(objectClass=inetOrgPerson)", $cn_filter);
	ldap_sort ($conn, $cn_read, "cn");
	$cn_entry = ldap_first_entry( $conn, $cn_read );
	while ($cn_entry)
	{
		$cn_attrs = ldap_get_attributes($conn,$cn_entry);
		$cn_dn = ldap_get_dn($conn, $cn_entry);

		echo $cn_dn." : ";

		//////////////// Adding objectClass
		$cn_newmail[ "objectClass" ] = array_values($cn_attrs[ "objectClass" ]);

		// First element is a "count" of elements. Deleting it
		unset($cn_newmail[ "objectClass" ][0]);

		// Reindexing array
		$cn_newmail[ "objectClass" ] = array_values($cn_newmail[ "objectClass" ]);

		// Adding AsteriskSIPUser objectClass
		$cn_newmail[ "objectClass" ][] = "AsteriskSIPUser";

		// Writing changes
		$m_result = ldap_modify($conn, $cn_dn, $cn_newmail);

		// var_dump($cn_newmail[ "mail" ]);
		///////////////////////////////////////////////

		echo ($m_result == true ? "ok<br/>" : "ERROR!</br>")

		$cn_entry = ldap_next_entry($conn, $cn_entry);
	}
	/////////////////////////////////////////////////////

	$ou_entry = ldap_next_entry($conn, $ou_entry);
}

ldap_close($conn);

echo "fin";
?>
