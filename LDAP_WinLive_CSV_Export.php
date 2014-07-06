<?php
/////////////////////////////////////////////////////////////////
// Script for exporting LDAP -> WindowsLive addressbook
/////////////////////////////////////////////////////////////////

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
$cn_filter = array("cn","mail","homePhone","mobile","title");

echo "Имя;Адрес электронной почты;Домашний телефон;Рабочий телефон;Должность;Город\n";

// Find all childe organizationalUnit's from $base_dn
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

	$ou_attrs = ldap_get_attributes($conn,$ou_entry);

	// Find all child inetOrgPerson's from current organizationalUnit
	$cn_read = ldap_search($conn, $dn, "(objectClass=inetOrgPerson)", $cn_filter);
	ldap_sort ($conn, $cn_read, "cn");
	$cn_entry = ldap_first_entry( $conn, $cn_read );

	while ($cn_entry)
	{
		$cn_attrs = ldap_get_attributes($conn,$cn_entry);

		for($p = 0; $p < count($cn_filter); $p++)
		{
			if(isset( $cn_attrs[ $cn_filter[$p] ] ))
				echo trim($cn_attrs[ $cn_filter[$p] ][0], " ");

			echo ";";
		}

		echo $ou_attrs["ou"][0]."\n";

		$cn_entry = ldap_next_entry($conn, $cn_entry);
	}
	/////////////////////////////////////////////////////

	$ou_entry = ldap_next_entry($conn, $ou_entry);
}

ldap_close($conn);

?>
