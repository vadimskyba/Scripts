<!--
Скрипт выполнения удаленных комманд Nagios.
На клиенте должен быть установлен Nagios Remote Plugin Executor и настроен список клиентских комманд.
Результат выполнения комманд отображается на этой же странице.
-->

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; Charset=UTF-8">
	<title>Nagios - Выполнение удаленных комманд</title>

	<script type="text/javascript">
		function validate(form)
		{
		   var ip = form['ip'].value;
		   var command = form['command'].value;

		   if(ip == "")
		   {
		      alert("Введите IP-адрес");
		      return false;
		   }

		   if(command == "")
		   {
		      alert("Выберите комманду для выполнения");
		      return false;
		   }

		   return confirm("Вы уверены, что хотите выполнить комманду Nagios?\nIP-адрес: "+ip+"\nКомманда: "+command);
		}
	</script>
</head>
<body>
	<h2>Выполнение удаленных комманд Nagios</h2><hr>

	<form id="CommandForm" action="command.php" method="POST" onsubmit="return validate(this);">
		<p>IP-адрес:<br /><input type="TEXT" name="ip" id="ip"></p>
		<p>Комманда:<br />
			<select name="command" id="command">
				<option value="" selected disabled hidden>выбор...</option>
				<!-- Список клиентских комманд { -->
				<option value="wisma_restart">wisma_restart</option>
				<option value="snmp_restart">snmp_restart</option>
				<option value="reboot">reboot</option>
				<!-- } -->
			</select>
		</p>
			<input type="submit" value="Выполнить">
	</form>

<?php
if ( !empty($_POST['ip']) && !empty($_POST['command']))
{
    $ip = $_POST['ip'];
    $command = $_POST['command'];

    $output = exec('/usr/local/nagios/libexec/check_nrpe -H '.$ip.' -c '.$command);

    echo "IP: ".$ip."<br/>";
    echo "Command: ".$command."<br/>";
    echo "Результат: ".$output;
}
else
{
    echo "*Введите ip-адрес и комманду для выполнения";
}
?>
</body>
</html>
