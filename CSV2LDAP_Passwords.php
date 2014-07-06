<?php
/////////////////////////////////////////////////////////////////
// Скрипт імпорту паролів csv -> LDAP
// Шукає акаунт по електронній адресі, і якщо тикий знайдено, змінює пароль.
//
// Приклад вхідного файла LDAPPasswords.csv:
// NAME;MAIL;PASSWORD
// ім'я1;name1@company.com;Pa120923
// ім'я2;name2@company.com;Pa876545

$csv_filename = "./LDAPPasswords.csv";

// Показувати помилки php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Показувати годину (перевірка, чи не кешується відповідь)
echo (date("Y/m/d H:i:s")).'</br>';

$array = $fields = array(); $i = 0;
$handle = @fopen($csv_filename, "r");

///////////////////////////////////////////////////////////////////////////////////////////////////////
// 1. Завантаження csv в масив
///////////////////////////////////////////////////////////////////////////////////////////////////////
if ($handle)
{
    while (($row = fgets($handle, 4096)) !== false)
    {
        $row = explode(';', $row);

        if (empty($fields))
        // Перша стрічка - назви стовпців
        {
            $fields = $row;

		    // Прибираємо пробіли спочатку та в кінці (функция trim)
            $fields = array_map('trim', $fields);

            continue;
        }

        // Додавання елементу $array[i]["NAME"], $array[i]["MAIL"], $array[i]["PASSWORD"]
        foreach ($row as $k=>$value) {
            $array[$i][ $fields[$k] ] = trim($value);
        }

        $i++;
    }

    if (!feof($handle))
    {
        echo "Помилка fgets() \n";
		exit(0);
    }

    fclose($handle);

}
else
{
    echo "Файл не знайдено";
    exit(0);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////
// 2. Пошук та зміна поролів в LDAP
///////////////////////////////////////////////////////////////////////////////////////////////////////

$ldap_host = "127.0.0.1";
$ldap_port = "389";
$base_dn = "ou=people,dc=company,dc=com";
$ldap_user ="cn=admin,dc=company,dc=com";
$ldap_pass = "password";

$conn = ldap_connect( $ldap_host, $ldap_port);
ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
$bind = ldap_bind($conn, $ldap_user, $ldap_pass);

$cn_filter = array("uid", "mail", "userPassword");

$array_without_mails = array();
$array_not_found_mails = array();
$array_duplicated_mails = array();

echo "<b>Успішно змінені паролі:</b><br>";

foreach($array as $itm)
{
    if( empty( $itm["mail"] ) )
    {
        $array_without_mails[] = $itm;
        continue;
    }

    $SearchFilter = "(&(objectClass=posixAccount)(mail=".$itm["MAIL"]."))";
    $cn_read = ldap_search($conn, $base_dn, $SearchFilter, $cn_filter);
    $info = ldap_get_entries($conn, $cn_read);

    if($info["count"] == 0)
    {
        $array_not_found_mails[] = $itm;
    }
    else if($info["count"] == 1)
    {
        // все ок, змінюємо пароль
        $cn_entry = ldap_first_entry( $conn, $cn_read );
        $cn_dn = ldap_get_dn($conn, $cn_entry);
        echo $cn_dn.' : ';

        $ldap_md5_password = '{MD5}'.base64_encode(pack("H*",md5($itm["PASSWORD"])));
        $cn_newUserPassword[ "userPassword" ] = $ldap_md5_password;

        $m_result = ldap_modify($conn, $cn_dn, $cn_newUserPassword);
        if($m_result == true)
            echo "ok<br/>";
		else
            echo "<b>ERROR!</b></br>";

    }
    else if($info["count"] > 1)
    {
        $array_duplicated_mails[] = $itm;
    }
}

ldap_close($conn);
echo "<br>";

// Вивід персон без email
if(count($array_without_mails) > 0)
{
    echo "Персони (паролі) в LDAP без email:<br>";

    foreach($array_not_found_mails as $itm)
    {
        echo $itm["uid"]."<br>";
    }

    echo "<br>";
}

// Вивід незнайдених адрес
if(count($array_not_found_mails) > 0)
{
    echo "Ненайденные в LDAP адреса:<br>";

    foreach($array_not_found_mails as $itm)
    {
        echo $itm["mail"]."<br>";
    }

    echo "<br>";
}

// Вивід дублюючих адрес
if(count($array_duplicated_mails) > 0)
{
    echo "Дублюючі в LDAP адреси:<br>";

    foreach($array_duplicated_mails as $itm)
    {
        echo $itm["mail"]."<br>";
    }

    echo "<br>";
}

echo "fin";
?>
