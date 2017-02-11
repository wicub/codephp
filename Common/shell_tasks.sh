#!/bin/bash
#文件名：shell_tasks.sh
#用途：处理TASKS接口相关任务
#作者：charles.jia
###########################################################
#本地地址
#phpdir=/usr/local/php/bin/php
#211php路径
phpdir=/opt/php/bin/php

#写入脚本执行日志，通过日志可以清楚脚本执行的次数及执行时间
#每个月生成一个日志文件.
date=$(date "+%Y-%m-%d___%H:%M:%S")
logfilename="day-"$(date "+%Y-%m").log
savepath=/home/www/wst3/lv/app/Runtime/Logs/Tasks

DOMAIN=/home/www/wst3/lv/app

#同步相关Redis内容
cd $DOMAIN
#php cli.php TaskIndex/doTaskData  >> $savepath/$logfilename
$phpdir cli.php TaskIndex/doTaskData  >> $savepath/$logfilename
#echo $date" 执行了Redis命令:TaskIndex/doTaskData" >> $savepath/$logfilename
