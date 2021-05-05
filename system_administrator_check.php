<?php
//
// Script per monitorare il Cloud Server Ares
// # (c) 2015 Gian Marco Chiuchiolo
//

// Creazione Array
$checkram = array();
$checkprocessi= array();
$checkspace = array();
$checkusers = array();

// Controllo RAM disponibile
exec("/usr/bin/free -m ", $checkram);

// Controllo Processi in esecuzione
exec("/bin/ps aux", $checkprocessi);

// Controllo Spazio Disponibile
exec("/bin/df -H", $checkspace);

// Controllo Utenti connessi
exec("/etc/alternatives/w", $checkusers);


// Implosione array RAM in stringa
$showram = implode("\n", $checkram);

// Implosione array Processi in stringa
$showprocessi = implode("\n", $checkprocessi);

// Implosione array Space in stringa
$showspace = implode("\n", $checkspace);

// Implosione array Utenti in stringa
$showusers = implode("\n", $checkusers);


// Invio Mail
$to      = 'admin@kiuki.it';
$subject = 'SYSTEM ADMINISTRATOR CHECK';
$message = "Controlli di sistema per monitorare il corretto funzionamento del Cloud Server:\n\n" .
            "Controllo RAM:\n" . $showram . "\n\n" .
            "Controllo SPAZIO:\n" . $showspace . "\n\n" .
            "Controllo UTENTI:\n" . $showusers . "\n\n" .
            "Controllo PROCESSI:\n" . $showprocessi . "\n\n" ;

$headers = 'From: admin@kiuki.it' . "\r\n" .
'Reply-To: admin@kiuki.it' . "\r\n" .
'X-Mailer: PHP/' . phpversion();

$headers = 'MIME-Version: 1.0' . "\r\n";
//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: support <admin@kiuki.it>' . "\r\n";
$headers .= 'Reply-To: admin@kiuki.it' . "\r\n" .
$headers .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
$headers .= rtrim(chunk_split(base64_encode($message)));

mail($to, $subject, "", $headers);

// Fine
