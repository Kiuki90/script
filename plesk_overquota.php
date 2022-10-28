<?php
// ################################################################################################
//
// Script per inviare una mail al giorno per notificare tutte le caselle al momento in overquota
// # (c) 2022 Gian Marco Chiuchiolo
//
//
// NOTA - Comando su plesk
// # 95%
// plesk db "SELECT concat(mail.mail_name,'@',domains.name) AS 'Email address', mn_param.val AS 'Mailbox usage', mail.mbox_quota AS 'Mailbox max quota', Limits.value AS 'Default Limits' FROM mail LEFT JOIN mn_param ON mail.id=mn_param.mn_id LEFT JOIN domains ON mail.dom_id=domains.id LEFT JOIN Subscriptions ON domains.id=Subscriptions.object_id LEFT JOIN SubscriptionProperties ON Subscriptions.id=SubscriptionProperties.subscription_id LEFT JOIN Limits ON SubscriptionProperties.value=Limits.id WHERE mn_param.param='box_usage' AND Subscriptions.object_type='domain' AND SubscriptionProperties.name='limitsId' AND Limits.limit_name='mbox_quota' AND (mn_param.val)*100/(mail.mbox_quota) > 95" | awk '{if (NR>3) {print $2, $4, $6}}'
//
// #################################################################################################

// Variabile per 95% quota occupata + rimozione prime 3 righe (divisore + dettagli)
$overquotacommand = '/usr/sbin/plesk db "SELECT concat(mail.mail_name,\'@\',domains.name) AS \'Email address\', mail.mbox_quota AS \'Mailbox max quota\', mn_param.val AS \'Mailbox usage\', Limits.value AS \'Default Limits\' FROM mail LEFT JOIN mn_param ON mail.id=mn_param.mn_id LEFT JOIN domains ON mail.dom_id=domains.id LEFT JOIN Subscriptions ON domains.id=Subscriptions.object_id LEFT JOIN SubscriptionProperties ON Subscriptions.id=SubscriptionProperties.subscription_id LEFT JOIN Limits ON SubscriptionProperties.value=Limits.id WHERE mn_param.param=\'box_usage\' AND Subscriptions.object_type=\'domain\' AND SubscriptionProperties.name=\'limitsId\' AND Limits.limit_name=\'mbox_quota\' AND (mn_param.val)*100/(mail.mbox_quota) > 95" | awk \'{if (NR>3) {print $2, $4, $6}}\'';

// Array di caselle
$output = array();
$result = exec($overquotacommand, $output);

// Rimozione ultima riga (vuota - divisore)
array_pop($output);

// Stampa a video caselle
//print_r($output);

// Ciclo dell'array per modificare le stringhe
$count_utenti = count($output);
$values = array();
$weights = array();

// Formattare byte to KB, MB, GB
function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}

// Scorrimento ciclo per modificare l'output
for ($i=0; $i<$count_utenti; $i++) {

	// Rimozione e salvataggio quota occupata
	if (preg_match_all('/\d+/', $output[$i], $numbers)) {
		$lastnum = end($numbers[0]);
	}
	$byte_size = $lastnum;

	// Salvataggio quota occupata + rimozione
	$mb = formatSizeUnits($lastnum);
	$output[$i] = preg_replace('~\\s+\\S+$~', "", $output[$i]);

	// Rimozione e salvataggio quota size
	if (preg_match_all('/\d+/', $output[$i], $numbers)) {
                $penultimate = end($numbers[0]);
        }
	$byte_total_size = $penultimate;

	// Salvataggio quota size + rimozione
	$output[$i] = preg_replace('~\\s+\\S+$~', "", $output[$i]);
	$mbpen = formatSizeUnits($penultimate);

	// Check percentuale quota
	$over_percent = $byte_size * 100 / $byte_total_size;
	$over = "<b><span style='color: red;'>" . number_format((float)$over_percent, 2, '.', '') . "% </span></b>";

	// Output finale
 	$output[$i] = "<tr bgcolor='DCDCDC'><td align='left'>" . $output[$i] . "</td><td align='center'><b>" . $mb . "</td><td align='center'>" .  $mbpen . "</b></td><td align='center'>" . $over ."</td></tr>" ;
}


// Log Completo
$log = implode("", $output);

// Invio Mail
if (is_array($output) && count($output) > 0) {

	$situation_list = implode("<br>", $values);

        $to      = 'support@neikos.it';
        $subject = 'MAILBOX OVERQUOTA - APOLLO.NEIKOS.IT (plesk): lista caselle con spazio occupato maggiore o uguale al 95%';
        $message = "<b>Ecco la lista delle caselle di posta che al momento occupano sul server apollo.neikos.it (plesk) piu' del 95% dello spazio disponibile:<br><br></b><table border='1'><table bgcolor='C0C0C0'><tr><td align='center'><b>CASELLA</b></td><td align='center' width='160px'><b>QUOTA OCCUPATA</b></td><td align='center' width='160px'><b>QUOTA TOTALE</b></td><td align='center' width='160px'><b>QUOTA(%)</b></td></tr>" . $log . "</table></table>";
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: support <support@neikos.it>' . "\r\n";
        $headers .= 'Reply-To: support@neikos.it' . "\r\n" ;
        $headers .= 'Cc: staff@neikos.it' . ' , ' . 'pm@neikos.it' . "\r\n";
        $headers .= 'Return-path: support@neikos.it' . "\r\n" ;


	$headers .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
	$final_message = rtrim(chunk_split(base64_encode($message)));

        mail($to, $subject, $final_message, $headers);
}

