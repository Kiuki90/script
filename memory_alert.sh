#!/bin/bash

######################################
# RAM SWAP monitoring
# g.chiuchiolo 2021
######################################

#Minimum available memory limit, MB

# RAM < 4GB
THRESHOLD=4096
GBTHRESHOLD=$[THRESHOLD / 1024]

free=$(free -m|awk '/^Mem:/{print $4}')
buffers=$(free -m|awk '/^Mem:/{print $6}')
cached=$(free -m|awk '/^Mem:/{print $7}')
available=$(free -m | awk '/^-\/+/{print $4}')

message="Attualmente la RAM disponibile sul server iris.neikos.it e' meno di $GBTHRESHOLD GB! \n ----- \n Free $free""MB"" \n Buffers $buffers""MB"" \n Cached $cached""MB"" \n Available $available""MB"" \n ----- \n Valutare l'upgrade della RAM, il riavvio dei servizi o del server"

if [ $available -lt $THRESHOLD ]
    then
    echo -e $message | mail -s "IRIS: RAM Warning" -a "From: support@vs03.kiuki.it" support@kiuki.it
fi


