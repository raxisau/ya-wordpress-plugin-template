#!/bin/bash

findPHP() {
    for phpLoc in /opt/cpanel/ea-php82/root/usr/bin /usr/local/bin /usr/bin; do
        if [ -d $phpLoc ]; then

            phpExeTest="${phpLoc}/php"
            if [ -f $phpExeTest ]; then
                echo $phpExeTest
                return 0
            fi
        fi
    done

    phpExeTest="$(which php)"
    echo $phpExeTest
    return 0
}

getLocation() {
    for location in GEN CAD EUR NZD AUD; do
        LOC=`echo $@ | grep "\-${location}"`
        if [ "$LOC" != "" ]; then
            echo ${location}
            return 0
        fi
    done
    echo GEN
    return 0
}

PHP=$(findPHP)
JACK=jack.php
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
START_TIME="$(date '+%Y-%m-%d %H:%M:%S')"
cd $DIR

# If no arguments then just show the current options and exit
if [ "$1" == "" ]; then
    $PHP -f ./${JACK}
    exit
fi

LOCATION=$(getLocation $@)

MYPRE="PRE"
MYDIR="yawpt"
ERRPID=$$

mkdir -p "/home/${USER}/.logs/${MYDIR}"
MYPID="/home/${USER}/.logs/${MYDIR}/${MYPRE}-${1/:/-}-${LOCATION}.pid"
MYLOG="/home/${USER}/.logs/${MYDIR}/${MYPRE}-${1/:/-}-${LOCATION}.log"
ERRLOG="/home/${USER}/.logs/${MYDIR}/${MYPRE}-${1/:/-}-${ERRPID}-${LOCATION}.log"

if [ -f $MYPID ]; then
    echo "WARNING: Lock file ($MYPID) exists failed - Checking processes"
    PROCESSES=`ps awwxf | grep $JACK | grep $1 | grep $LOCATION | wc -l`
    if [  $PROCESSES -eq 0 ]; then
        echo "WARNING: could not find any processes - cleaning up and continuing"
        rm -f -- '$MYPID'
    else
        PID=`cat $MYPID`
        echo "ERROR: Process ($1) already running ($PID) - exiting - ps awwxf | grep $PID" >> $ERRLOG 2>&1
        echo "<DB column=\"exit_code\">1</DB>"                                             >> $ERRLOG 2>&1
        echo "<DB column=\"start_time\">$START_TIME</DB>"                                  >> $ERRLOG 2>&1
        echo "<DB column=\"end_time\">$(date '+%Y-%m-%d %H:%M:%S')</DB>"                   >> $ERRLOG 2>&1
        echo "<DB column=\"job_name\">$1</DB>"                                             >> $ERRLOG 2>&1
        $PHP -f $JACK JOB:log -f $ERRLOG
        rm -f -- '$ERRLOG'
        exit 1
    fi
fi

if [ -f $MYLOG ]; then
    $PHP -f $JACK JOBS:logsave -f $MYLOG
    rm -f -- '$MYLOG'
fi

# Ensure PID file is removed on program exit and log file saved.
trap "rm -f -- '$MYPID'; $PHP -f $JACK JOB:log -f $MYLOG; rm -f -- '$MYLOG'" EXIT

# Create a file with current PID to indicate that process is running.
touch $MYPID
echo $$ >> $MYPID

echo "$(date '+%Y-%m-%d %H:%M:%S') - Script: $PHP $JACK $@, Process ID: $$, File: $MYPID" >> $MYLOG 2>&1
$PHP -f $JACK $@                                                                          >> $MYLOG 2>&1
RES=$?
echo "$(date '+%Y-%m-%d %H:%M:%S') - Script: $PHP $JACK $@, Process ID: $$, Completed"    >> $MYLOG 2>&1
echo "<DB column=\"exit_code\">$RES</DB>"                                                 >> $MYLOG 2>&1
echo "<DB column=\"start_time\">$START_TIME</DB>"                                         >> $MYLOG 2>&1
echo "<DB column=\"end_time\">$(date '+%Y-%m-%d %H:%M:%S')</DB>"                          >> $MYLOG 2>&1
echo "<DB column=\"job_name\">$1</DB>"                                                    >> $MYLOG 2>&1

