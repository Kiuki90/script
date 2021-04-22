<?php
/*
        Autore  : Gian Marco Chiuchiolo
        Azienda : Neikos Srl

        Oggetto: Script per la manutenzione server

        Descrizione:
            Script per la gestione della verifica dello spazio disponibile
            sulle varie partizioni.
*/

// Array delle partizioni
$arrayPartizioni[0] = "/";

// Parametri di configurazione
$server = "linode19.neikos.it"; // Nome del server
$percMax = 90; // Percentuale limite
$mail = "support@neikos.it"; // e-mail di destinazione e mittente
$subject = "Spazio quasi terminato su $server";

// Parametri globali da non cambiare.
$headers = "From: $mail\r\n" .
           'X-Mailer: PHP/' . phpversion() . "\r\n" .
           "MIME-Version: 1.0\r\n";// .
$message  = "Gentile amministratore,\n\r";
$message  .= "su \"$server\" le seguenti partizioni hanno superato il $percMax% dello spazio disponibile:\n\r";
$inviaMail = false;
$numPartizioni = count($arrayPartizioni);
$numFuoriQuota=0;

// Inizio logica script
for($i=0;$i<$numPartizioni;$i++){
        $risExec =  exec("df ".$arrayPartizioni[$i]);
        list($dev, $spazioTotale, $spazioUsato, $spazioDiponibile,
                 $percentuale, $mountPoint) = sscanf($risExec,"%s %s %s %s %s %s");

        $percentuale = explode("%",$percentuale);
        $percentuale = (int)$percentuale[0];
        if($percentuale >= $percMax){
           $numFuoriQuota++;
           $message .= "    ";
           $message .= "$numFuoriQuota) \"$mountPoint\" - Percentuale usata($percentuale%) - Spazio usato($spazioUsato) - Spazio
disponibile($spazioDiponibile)\n\r";
           $inviaMail = true;
        }
}

if($inviaMail){
   $message .= "\n\r\n\rScript eseguito il ".date("d/m/Y")." alle ore ".date("H:m:s");
   $message .= "\n\rE-mail generata automaticamente dallo script \"check_quota_dischi\" su \"$server\"";
   mail($mail, $subject, $message, $headers);
}
?>

