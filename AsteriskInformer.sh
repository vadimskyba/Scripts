#!/bin/bash
#
# Asterisk phone voice informer script.
# This script should be running from crontab every 5 minutes. Crontab example:
# */5 *   *   *   *   /root/scripts/informer.sh
#
# Input data:
#    PHONELIST - txt file list of telephone numbers to dial
#    MESSAGE - mp3 audio file that should be played
#    SRC_DIR - directory to find PHONELIST and MESSAGE
#    PROCESSING_DIR - directory to move PHONELIST and MESSAGE files here while dial processing
#    HISTORY_DIR - directory to move completed PHONELIST and MESSAGE files here
#    SPOOL_DIR - default Asterisk spool directory


PHONELIST="informer.txt"
MESSAGE="informer.mp3"
SRC_DIR="/home/ftp/obmen/informer"

PROCESSING_DIR=$SRC_DIR/processing
HISTORY_DIR=$SRC_DIR/history
SPOOL_DIR="/var/spool/asterisk"
TIME=`date "+%Y-%m-%d_%H-%M-%S"`
logfile="/tmp/informer_$TIME.log"
pause=5

file_in_spool=no

if [ -f $SRC_DIR/$PHONELIST ] && [ -f $SRC_DIR/$MESSAGE ]
then

	#check if files not presents at $PROCESSING_DIR
	if [ -f $PROCESSING_DIR/$PHONELIST ] || [ -f $PROCESSING_DIR/$MESSAGE ]; then
		echo "The folder $PROCESSING_DIR is not empty. Try again later."
		return
	fi

	# Create $PROCESSING_DIR if it does not created
	mkdir $PROCESSING_DIR
	mv $SRC_DIR/$PHONELIST $PROCESSING_DIR
	mv $SRC_DIR/$MESSAGE $PROCESSING_DIR

	echo "---- Begin informer list - $TIME" > $logfile

	while read number; do

		echo	"Channel: Local/$number@prozvon-dialer
MaxRetries: 0
RetryTime: 5
WaitTime: 30
Context: prozvon-informer
Extension: 2222
Callerid: 2222
Priority: 1" > $SPOOL_DIR/tmp/$TIME_$number

		file_in_spool=no

		# do not do the next dial while outgoing spool will not empty
		while [ "$?" -eq "0" ]; do
			count_files ()
			{
				# get the number of files in outgoing
				count_f=`ls $SPOOL_DIR/outgoing | wc -l`

				if [ "$count_f" -eq "0" ]; then

					if [ $file_in_spool = "no" ]; then

				                mv $SPOOL_DIR/tmp/$TIME_$number $SPOOL_DIR/outgoing
				                echo "`date "+%H-%M-%S"`: $number - calling" >> $logfile

						file_in_spool=yes
						return 0
					fi

					echo "`date "+%H-%M-%S"`: $number - hangup" >> $logfile
					return 1

				else
#					echo "`date "+%H-%M-%S"`: sleep 5" >> $logfile
					sleep $pause
					return 0
				fi
			}

			count_files
		done

	done < $PROCESSING_DIR/$PHONELIST

	mkdir $HISTORY_DIR
	mkdir $HISTORY_DIR/$TIME
	mv $PROCESSING_DIR/$PHONELIST $HISTORY_DIR/$TIME
	mv $PROCESSING_DIR/$MESSAGE $HISTORY_DIR/$TIME

	echo "---- End informer list" >> $logfile
	mv $logfile $HISTORY_DIR/$TIME/log.txt
else
	echo "informer.txt or informer.mp3 are not found"
fi
