<?php
set_time_limit(0);
ini_set('memory_limit','10G');



$BACKUP_DIR='J:\my_icloud_photos\photos';
$CURL_FILE="J:\my_icloud_photos\curl.txt";
$MAX_JOBS=2;
$BACKUP_DIR_FOLDER_FORMAT_BY_MONTH=false;//if we put all photos on the same month in the same folder, otherwise they will be put in day format

if(!is_dir($BACKUP_DIR))
    $BACKUP_DIR=dirname(__FILE__)."/photos";

if(!file_exists($CURL_FILE))
    $CURL_FILE=dirname(__FILE__)."/curl.txt";

$RESULT_LIMIT=30;
$META_DATA_DIR=sys_get_temp_dir()."/icloud_backup/meta";
$TMP_FOLDER=sys_get_temp_dir()."/icloud_backup";
$META_DATA_COMPLETED_DIR=sys_get_temp_dir()."/icloud_backup/meta_done";
$CURLOPT_TIMEOUT=-1;//we don't want to setup timeout so we can download;

$POST_API_URL="https://p102-ckdatabasews.icloud.com/database/1/com.apple.photos.cloud/production/private/records/query?remapEnums=true&ckjsBuildVersion=2204ProjectDev38&ckjsVersion=2.6.1&getCurrentSyncToken=true&clientBuildNumber=2204Project45&clientMasteringNumber=2204B36&clientId=ad3b3bd6-0a34-4dd5-87fc-bbc0d8276ea8&dsid=402506586";
$DEFAULT_HTTP_HEADER=array('Connection: keep-alive','Pragma: no-cache','Cache-Control: no-cache','sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="96", "Google Chrome";v="96"' ,'sec-ch-ua-mobile: ?0' ,'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36' ,'sec-ch-ua-platform: "macOS"' ,'content-type: text/plain' ,'Accept: */*' ,'Origin: https://www.icloud.com' ,'Sec-Fetch-Site: same-site' ,'Sec-Fetch-Mode: cors' ,'Sec-Fetch-Dest: empty' ,'Referer: https://www.icloud.com/' ,'Accept-Language: en-US,en;q=0.9');

$IS_FORK_SUPPORTED=function_exists("pcntl_fork");
$IS_WINDOWS_OS=(strpos(php_uname(),"Windows")!==false);
if(!$IS_FORK_SUPPORTED)
{
    echo "This system does not support multi process";
}

if(!is_dir($TMP_FOLDER))
{
    mkdir($TMP_FOLDER,0755,true);
}	
if(!is_dir($BACKUP_DIR))
{
    mkdir($BACKUP_DIR,0755,true);
}
if(!is_dir($META_DATA_COMPLETED_DIR))
{
    mkdir($META_DATA_COMPLETED_DIR,0755,true);
}
if(!is_dir($META_DATA_DIR))
{
    mkdir($META_DATA_DIR,0755,true);
}

$LOG_FILE=$TMP_FOLDER."/log.txt";
if(is_file($LOG_FILE))
{
    unlink($LOG_FILE);
}


$timezone = 'UTC';
if (is_link('/etc/localtime')) {
    // Mac OS X (and older Linuxes)    
    // /etc/localtime is a symlink to the 
    // timezone in /usr/share/zoneinfo.
    $filename = readlink('/etc/localtime');
    if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
        $timezone = substr($filename, 20);
    }
    if (strpos($filename, '/var/db/timezone/zoneinfo') === 0) {
        $timezone = substr($filename, strlen('/var/db/timezone/zoneinfo')+1);
    }
    
} elseif (file_exists('/etc/timezone')) {
    // Ubuntu / Debian.
    $data = file_get_contents('/etc/timezone');
    if ($data) {
        $timezone = $data;
    }
} elseif (file_exists('/etc/sysconfig/clock')) {
    // RHEL / CentOS
    $data = parse_ini_file('/etc/sysconfig/clock');
    if (!empty($data['ZONE'])) {
        $timezone = $data['ZONE'];
    }
}

date_default_timezone_set($timezone);

register_shutdown_function('shutdown');
?>