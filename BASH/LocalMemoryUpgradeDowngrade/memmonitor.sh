#!/bin/bash
free -m > /tmp/FREE.txt
TOTAL=$(grep Mem: /tmp/FREE.txt | awk '{print $2}')
USED=$(grep buffers/cache /tmp/FREE.txt | awk '{print $3}')
FREE=$(grep buffers/cache /tmp/FREE.txt | awk '{print $4}')
PERCENTAGE=$(((USED*100)/TOTAL))
if [ $PERCENTAGE -gt 90 ]; then
	if [ $TOTAL -ge 16384 ]; then
		ADDMEM=0 
	elif  [ $TOTAL -ge 12288 ]; then
		ADDMEM=2048	
	elif  [ $TOTAL -ge 4096 ]; then
		ADDMEM=1024 	
	elif [ $TOTAL -ge 1024 ]; then
		ADDMEM=512 
	else 
		ADDMEM=256
	fi		
        NEWMEM=$((TOTAL+ADDMEM));
		/usr/bin/curl -X POST -d serverid=vz1234567\&memorysize=$NEWMEM -k --basic -u cl12345:[api-key] https://api.glesys.com/server/edit/
        
			if [ $ADDMEM -eq 0 ]; then 
				echo -e "No more memory could be added.\n"
			else
				( echo -e "Increasing memory to $NEWMEM MB.\n"
				free -m) | mail -s "Server $HOSTNAME is low on memory: $PERCENTAGE% used - RAM report" user@mail.com
			fi
         
fi

if  [ $TOTAL -ge 14336 ]; then
	REMMEM=2048
elif  [ $TOTAL -ge 5120 ]; then
	REMMEM=1024		
elif [ $TOTAL -ge 1536 ]; then
	REMMEM=512
elif [ $TOTAL -gt 256 ]; then
	REMMEM=256
else 
	REMMEM=0
fi

if [ $FREE -gt $((REMMEM+50)) ]; then
        NEWMEM=$((TOTAL-REMMEM));
		/usr/bin/curl -X POST -d serverid=vz1234567\&memorysize=$NEWMEM -k --basic -u cl12345:[api-key] https://api.glesys.com/server/edit/
        	if [ $REMMEM -eq 0 ]; then
				echo -e "No more memory could be removed.\n"
			else
			(	echo -e "Decreasing memory to $NEWMEM MB.\n"
				         free -m
			) | mail -s "Server $HOSTNAME has too much memory: $PERCENTAGE% used - RAM report" user@mail.com
			fi
fi