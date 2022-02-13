<?php
include_once(dirname(__FILE__)."/lib.php");
include_once(dirname(__FILE__)."/config.php");

$curl_content=@file_get_contents($CURL_FILE);

if(strpos($curl_content,"Cookie:")===false)
{
    debug("There is no cookie info in curl file [$CURL_FILE] - Go to your chrome browser, login to icloud photos , use Inspector , right click on /photos link -> copy as Curl");
    exit;
}
$cookie=explode("-H 'Cookie:",$curl_content)[1];

$cookie=trim(explode("\\\r",$cookie)[0]);
$cookie=trim(explode("\\\n",$cookie)[0]);
$cookie=trim(explode("\\\r",$cookie)[0]);
$cookie=trim($cookie,"'");

$start_row=0;

$get_record_count_query=<<<BLOCK

{"query":{"recordType":"HyperionIndexCountLookup","filterBy":{"fieldName":"indexCountID","comparator":"IN","fieldValue":{"value":["CPLAssetByAssetDateWithoutHiddenOrDeleted"],"type":"STRING_LIST"}}},"zoneID":{"zoneName":"PrimarySync","ownerRecordName":"XXX","zoneType":"REGULAR_CUSTOM_ZONE"}}

BLOCK;

$get_meta_data_photo_query=<<<BLOCK
{"query":{"recordType":"CPLAssetAndMasterByAddedDate","filterBy":[{"fieldName":"startRank","comparator":"EQUALS","fieldValue":{"value":11536,"type":"INT64"}},{"fieldName":"direction","comparator":"EQUALS","fieldValue":{"value":"ASCENDING","type":"STRING"}}]},"zoneID":{"zoneName":"PrimarySync","ownerRecordName":"XXX","zoneType":"REGULAR_CUSTOM_ZONE"},"desiredKeys":["addedDate","adjustmentRenderType","adjustmentType","assetDate","assetHDRType","assetSubtype","assetSubtypeV2","burstFlags","burstFlagsExt","burstId","captionEnc","codec","customRenderedValue","dataClassType","dateExpunged","duration","filenameEnc","importedBy","isDeleted","isExpunged","isFavorite","isHidden","itemType","locationEnc","locationLatitude","locationLongitude","locationV2Enc","masterRef","mediaMetaDataEnc","mediaMetaDataType","orientation","originalOrientation","recordChangeTag","recordName","recordType","remappedRef","resJPEGFullFileType","resJPEGFullFingerprint","resJPEGFullHeight","resJPEGFullRes","resJPEGFullWidth","resJPEGLargeFileType","resJPEGLargeFingerprint","resJPEGLargeHeight","resJPEGLargeRes","resJPEGLargeWidth","resJPEGMedFileType","resJPEGMedFingerprint","resJPEGMedHeight","resJPEGMedRes","resJPEGMedWidth","resJPEGThumbFileType","resJPEGThumbFingerprint","resJPEGThumbHeight","resJPEGThumbRes","resJPEGThumbWidth","resOriginalAltFileType","resOriginalAltFingerprint","resOriginalAltHeight","resOriginalAltRes","resOriginalAltWidth","resOriginalFileType","resOriginalFingerprint","resOriginalHeight","resOriginalRes","resOriginalVidComplFileType","resOriginalVidComplFingerprint","resOriginalVidComplHeight","resOriginalVidComplRes","resOriginalVidComplWidth","resOriginalWidth","resSidecarFileType","resSidecarFingerprint","resSidecarHeight","resSidecarRes","resSidecarWidth","resVidFullFileType","resVidFullFingerprint","resVidFullHeight","resVidFullRes","resVidFullWidth","resVidHDRMedRes","resVidMedFileType","resVidMedFingerprint","resVidMedHeight","resVidMedRes","resVidMedWidth","resVidSmallFileType","resVidSmallFingerprint","resVidSmallHeight","resVidSmallRes","resVidSmallWidth","timeZoneOffset","vidComplDispScale","vidComplDispValue","vidComplDurScale","vidComplDurValue","vidComplVisibilityState","videoFrameRate","zoneID"],"resultsLimit":2}

BLOCK;

$http_header=$DEFAULT_HTTP_HEADER;
$http_header[]="Cookie: ".$cookie;

$curl_cmd_result=curl_post_content($POST_API_URL,$get_record_count_query,false,$http_header);
$results=json_decode($curl_cmd_result);
if(!$results || !isset($results->records))
{
    if(!$results)
        debug("$curl_cmd_result is not a json");
    else
    {
        debug("Error: $curl_cmd_result");
        debug("There is an error with login - check your $CURL_FILE file");
    }
    exit;
}
$total_photos=($results->records[0]->fields->itemCount->value);
if($total_photos==0)
{
    debug("There is no photos or the session has expired");
    exit;
}

debug("You have $total_photos photo(s)/video(s) in your library");
$pids = array();
//======================For case the fork is not supported=======================

if(!$IS_FORK_SUPPORTED && $argv[1]=="")
{
    if($IS_WINDOWS_OS)
    {
        debug("Running on windows");
        for($i=0;$i<$MAX_JOBS;$i++)
        {
          debug("Spawning a process $i ".$_SERVER['PHP_SELF'] );
          debug(shell_exec("psexec -d cscript run_child_process.vbs"));
          sleep(1);         
        }
         print_log_and_wait_all_process_finish();
         exit;
    }else
    {
        for($i=0;$i<$MAX_JOBS;$i++)
        {
          debug("Spawning a process $i ".$_SERVER['PHP_SELF'] );
          shell_exec("php ".$_SERVER['PHP_SELF'] ." child  > /dev/null 2>/dev/null & ");
        }
        print_log_and_wait_all_process_finish();
        exit;
    }

}
//======================For case the fork is not supported=======================
$COUNTER_FILE=$TMP_FOLDER."/icloud_backup_tmp.txt";
@unlink("$COUNTER_FILE");

for($i = 0; $i < $MAX_JOBS; $i++) 
{
    if($IS_FORK_SUPPORTED)
        $pids[$i] = pcntl_fork();
    else
        $pids[$i]=0;
    sleep(1);
    if( !$pids[$i]  ) 
    {
        while(true)
        {
            $post_data=json_decode($get_meta_data_photo_query);
            if(!$post_data)
            {
                debug("Post data is invalid : $data_raw");
                break;
            }
            $fp=fopen($COUNTER_FILE,"a+");
            while ($fp && !flock($fp, LOCK_EX | LOCK_NB) ) 
            {
                //Lock not acquired, try again in:
                debug("Job ($i) is waiting for file unlock");
                sleep(1);
            }
            $start_row=(int)fread($fp,100);
            if($start_row=="")
                $start_row=0;
            ftruncate($fp, 0); 
            fwrite($fp,"".($start_row+$RESULT_LIMIT));
            fflush($fp);            // flush output before releasing the lock
            flock($fp, LOCK_UN);    // release the lock
            fclose($fp);
            if($start_row>$total_photos*5)
            {
                break;
            }
            debug("Starting from : $start_row");
           // continue;
            $post_data->query->filterBy[0]->fieldValue->value=(int)($start_row);
            $post_data->resultsLimit=$RESULT_LIMIT*2;//we multiply by 2 since the rest api will always have 2 kind of data
            global $cookie_filename;
            if(is_file($cookie_filename))
                unlink($cookie_filename);
            $http_header=$DEFAULT_HTTP_HEADER;
            $http_header[]="Cookie: ".$cookie;
            $curl_cmd_result=curl_post_content($POST_API_URL,json_encode($post_data),false,$http_header);
            $results=json_decode($curl_cmd_result);
            
            $results=json_decode($curl_cmd_result);
            if(!$results || !isset($results->records))
            {
                debug("$curl_cmd_result is not a json");
                break;
            }
            
            if(count($results->records)==0)
            {
                debug("No more records");
                //print_r($results);
                break;
            }
            debug("Found ".count($results->records)." record(s)");
            $meta_data_file=$META_DATA_DIR."/".$start_row."_".date("Y-m-d H-i-s").microtime(true).".json";
            file_put_contents($meta_data_file,$curl_cmd_result);
            backup_a_json_file($meta_data_file);
        }
        debug("Job: ($i) is  DONE");
        exit();
    }
    if(!$IS_FORK_SUPPORTED)
        break;
}
if($IS_FORK_SUPPORTED)
{
    for($i = 0; $i < $MAX_JOBS; $i++) 
    {
      pcntl_waitpid($pids[$i], $status, WUNTRACED);
    }
}
unlink("$COUNTER_FILE");
debug("Pulling database is done");

//-------------------------------------------------------------------------------
//json_file is a file containing list of file to backup , it's actually the result of an api query
function backup_a_json_file($json_file)
{
    global $META_DATA_DIR,$BACKUP_DIR,$BACKUP_DIR,$META_DATA_COMPLETED_DIR,$IS_FORK_SUPPORTED,$BACKUP_DIR_FOLDER_FORMAT_BY_MONTH;
    $curl_cmd_result=file_get_contents($json_file);
    $results=json_decode($curl_cmd_result);
    if(!$results || !isset($results->records))
    {
        debug("File: $json_file -- $curl_cmd_result is not a json - it might be already processed by others");
        return;
    }
    //debug("Processing $json_file");
    unlink($json_file);
    $cplasset_record_start_index= (int)count($results->records)/2 ;
    $counter=-1;
    foreach($results->records as $record)
    {
        $counter++;
        if($record->recordType!="CPLMaster")
            continue;
        $fileName=base64_decode($record->fields->filenameEnc->value);
        $created_date_timestamp=$record->created->timestamp;

        $cplasset_record=$results->records[$counter+$cplasset_record_start_index];
        if($cplasset_record->recordType=="CPLAsset" && $cplasset_record->fields->masterRef->value->recordName==$record->recordName)
        {
            if(isset($cplasset_record->fields->assetDate->value))
            {
                $created_date_timestamp=$cplasset_record->fields->assetDate->value;
               
            }
        }
        $created_date=date("Y-m-d H:i:s",$created_date_timestamp/1000);
        $file_change_date=date("YmdHi",$created_date_timestamp/1000);
        $output_backup_dir=$BACKUP_DIR."/".date("Y-m-d",$created_date_timestamp/1000);
        if(isset($BACKUP_DIR_FOLDER_FORMAT_BY_MONTH) && $BACKUP_DIR_FOLDER_FORMAT_BY_MONTH)
            $output_backup_dir=$BACKUP_DIR."/".date("Y-m",$created_date_timestamp/1000);
        if($fileName=="")
        {
            debug("Can't get filename");
            continue;
        }
        if(!is_dir($output_backup_dir))
            mkdir($output_backup_dir,0755,true);
        $downloadURL=$record->fields->resOriginalRes->value->downloadURL;
        $fileSize=$record->fields->resOriginalRes->value->size;
        //we need to replace space with _ , if we leave splace in filename , server will return bad request
        $downloadURL=str_replace('${f}',str_replace(" ","_",$fileName),$downloadURL);
        $fileName=get_destination_filename($output_backup_dir,$fileName,$fileSize);
        $backup_filename_path=$output_backup_dir."/".$fileName;
        if(is_file($backup_filename_path) && $fileSize==filesize($backup_filename_path))
        {
           if(!is_file($backup_filename_path.".json"))
           {
               file_put_contents($backup_filename_path.".json",json_encode($record));
           }
           //debug("$backup_filename_path exists");
           continue;
        }
        debug("Downloading $fileName :$fileSize: $downloadURL");
        $download_content=curl_get_content($downloadURL);
        $tmp_file=$backup_filename_path.".tmp";
        file_put_contents($tmp_file,$download_content);
        $download_filesize=@filesize($tmp_file);
        if($download_filesize!=$fileSize)
        {
            debug("ERROR:File is different Expected file size:$fileSize vs $download_filesize  - check $backup_filename_path  - $downloadURL");
            //debug($download_content);
            @unlink($tmp_file);
            continue;
        }
        file_put_contents($backup_filename_path.".json",json_encode($record));
        rename($tmp_file,$backup_filename_path);
        if(is_dir("/"))
            shell_exec("touch -t $file_change_date \"$backup_filename_path\"");
        else
            shell_exec("touch.exe  -t $file_change_date \"$backup_filename_path\"");
        debug("Download successfully....$backup_filename_path");
        
    }
    $completed_filename=$META_DATA_COMPLETED_DIR."/".pathinfo($json_file)["basename"];
}
//-------------------------------------------------------------------------------
function get_destination_filename($dest_folder,$recommended_filename,$filesize)
{
    $n=0;
    //debug("Calling get_destination_filename $dest_folder: $recommended_filename:$filesize");
    while(true)
    {
        $pathinfo=pathinfo($recommended_filename);
        $new_recommended_filename=($n==0)?$recommended_filename:$pathinfo["filename"]."_$n.".$pathinfo["extension"];
        $new_file_path=$dest_folder."/".$new_recommended_filename;
        //debug($new_recommended_filename);
        if( (is_file($new_file_path) && filesize($new_file_path)==$filesize) || !is_file($new_file_path) )
            return $new_recommended_filename;
        $n++;
        if($n>1000)
        {
            debug("get_destination_filename loop");
            exit;
        } 
    }
}
//-------------------------------------------------------------------------------
function print_log_and_wait_all_process_finish()
{
    global $LOG_FILE,$IS_WINDOWS_OS,$TMP_FOLDER;
    $waiting_time=0;
    $check_process_supported=true;
    while(true)
    {
        $lines    = @file($LOG_FILE);
        $lastLine = @array_pop($lines);
        if($lastLine!=$previousLine)
        {
            echo "\r\n".$lastLine;
            $previousLine=$lastLine;
            $waiting_time=0;
        }else
        {
           usleep(500000);
           $waiting_time+=500000;
        }
        /*if($waiting_time>500000*2*20 && strpos($lastLine,"is  DONE")>0) //wait for 20 seconds if we see the last line having "is DONE"
            break;*/
        if($waiting_time>500000*2*3000 && $check_process_supported!=2 ) //wait for 300s
            break;    
        if($check_process_supported)
        {
            if(!$IS_WINDOWS_OS)
            {
                $process_list=trim(shell_exec("ps aux | grep ".$_SERVER["PHP_SELF"]));
                if($process_list=="")
                    $check_process_supported=false;
                else
                    $check_process_supported=2;
                if(strpos($process_list,"php child")===false)
                    break;
            }else{
                $wmic_output_file=$TMP_FOLDER."/wmic.txt";
                $wmic_output_file=str_replace("/","\\",$wmic_output_file);
                $process_list=trim(shell_exec("WMIC /output:\"$wmic_output_file\"  PROCESS WHERE Name=\"php7.exe\"  "));
                if($process_list=="")
                    $process_list=file_get_contents($wmic_output_file);
    
                /*if($process_list!="" && strpos($process_list,"php child")===false)
                {
                    echo "Waiting";
                    sleep(5);
                    $process_list=file_get_contents($wmic_output_file);
                }*/
                $process_list=str_replace(chr(0), '', $process_list);
                if($process_list=="")
                    $check_process_supported=false;
                else
                    $check_process_supported=2;
                if(strpos($process_list,"php child")===false && $process_list!="")
                {
                    echo "Break - $process_list";
                    break;
                }
            }
        }
        
    }
    debug("No more log content - exiting...");
    debug("You can find the log at :".$LOG_FILE);
}

?>