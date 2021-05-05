<?php
//
// Script per mostrare le caselle che hanno raggiunto il limite orario(24H) di postfwd
// # (c) 2015 Gian Marco Chiuchiolo
// Ho inserito un cron alle 10:59, uno alle 15:59 ed uno alle 23:59.
//

// Inserire lo spazio usato nei log tra la data ed il giorno a secondo del giorno
$day= date("j");
if ($day > 0 && $day < 10) {
	$newdate = date("M  j ");
}
else $newdate = date("M j "); 

// Script di ricerca sul server
$search_pattern = "superati";
$output = array();
$result = exec("/bin/cat /var/log/mail.log | /bin/grep " . escapeshellarg($search_pattern) . " | /bin/grep -v postfix | /bin/grep '" .  $newdate . "'", $output);

// Log Completo
$log .= implode("\n", $output);

// Ricerca e creazione array di user
$matches = array();
$ris = preg_match_all("/user=([^,]*),/", $log, $matches);

// Lista utenti ed invio
$situation = array();
$result2 = exec("/usr/sbin/postfwd --dumpcache | /bin/grep 'count' | /bin/grep -v 'maxcount'", $situation);

// Ciclo dell'array per modificare le stringhe
$count_utenti = count($situation);
$values = array();
$weights = array();

for ($i=0; $i<$count_utenti; $i++)
{
        $ris_stringa = str_replace("%rate_cache -> %sasl_username=", '<tr bgcolor="DCDCDC"><td>', $situation[$i]);
        $ris_stringa = str_replace("-> %DRATE+500_86400 -> ", '</td><td align="center"><i>500</i>', $ris_stringa);
        $ris_stringa = str_replace("-> %C1000_DRATE+1000_86400 -> ", '</td><td align="center"><i>1000</i>', $ris_stringa);
        $ris_stringa = str_replace("-> %CTEST_DRATE+1_86400 -> ", '</td><td align="center"><i>1</i>', $ris_stringa);
        $ris_stringa = str_replace("-> %C2000_DRATE+2000_86400 -> ", '</td><td align="center"><i>2000</i>', $ris_stringa);
        $ris_stringa = str_replace("-> %C3000_DRATE+3000_86400 -> ", '</td><td align="center"><i>3000</i>', $ris_stringa);
        $ris_stringa = str_replace("-> %C5000_DRATE+5000_86400 -> ", '</td><td align="center"><i>5000</i>', $ris_stringa);
        $ris_stringa = str_replace("\$count    -> '", '</td><td align="center"><b> ', $ris_stringa);
        $ris_stringa = str_replace("'", "</b></i></td></tr>", $ris_stringa);

	if(preg_match_all('/\d+/', $ris_stringa, $numbers)) {
		$lastnum = end($numbers[0]);
	}

	$values[$i] = $ris_stringa;
	$weights[$i] = $lastnum;
}

// Ordinamento array
array_multisort(
	$weights, SORT_DESC, SORT_NUMERIC,
	$values
);

// Invio Mail
if ($ris > 0) {
	$email = array_unique($matches[1]);
	$email_list = implode("<br>", $email);
	$situation_list = implode("", $values);

	$to      = 'admin@kiuki.it';
        $subject = 'Postfwd: superamento limite giornaliero';
        $message = "<b>Le seguenti caselle di posta hanno raggiunto il limite di invio orario (24h) il giorno "
		 . date("d/n/o") . ":<br></b>"
		 . $email_list . "<br><br><b>Ecco la situazione, al momento, delle caselle che hanno inviato posta nelle ultime 24 ore:<br></b><table border='1' bgcolor='DCDCDC'><table bgcolor='C0C0C0'><tr><td align='center'><b>CASELLA</b></td><td align='left'><b>LIMITE</b></td><td align='right'><b>INVIATI</b></td></tr>"
		 . $situation_list . "</table>";

                 $headers = 'From: admin@kiuki.it' . "\r\n" .
                 'Reply-To: admin@kiuki.it' . "\r\n" .
                 'X-Mailer: PHP/' . phpversion();

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: support <admin@kiuki.it>' . "\r\n";
        $headers .= 'Reply-To: admin@kiuki.it' . "\r\n" .
	$headers .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
	$headers .= rtrim(chunk_split(base64_encode($message)));

        mail($to, $subject, "", $headers);
}
