<?php
function debug($s)
{
    global $LOG_FILE;
    $pid=getmypid();
    $d=date("Y-m-d H:i:s");
    $d="\r\n$d:[$pid]:$s";
    file_put_contents($LOG_FILE, $d,FILE_APPEND);
    echo "$d";
}
//-------------------------------------------------------------------------------
function curl_get_content($url,$header_only=false,$referer="",$addional_header=array())
{
    global $cookie_filename,$PROXY_SERVER,$CURLOPT_TIMEOUT;
    if($cookie_filename=="" )    
        $cookie_filename=tempnam(sys_get_temp_dir(),"cookie");
    $ch = curl_init();
    $useragent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36";
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_filename); 
    curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_filename); 
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);    
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    if($header_only)
    {
        curl_setopt($ch, CURLOPT_NOBODY,true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
        
    }else
    {
        
    }
    //---------------------------------------------------------------------
    $header=array();
    $header[] = "Connection: keep-alive"; 
    $header[] = "Upgrade-Insecure-Requests: 1"; 
    $header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.87 Safari/537.36"; 
    $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3"; 
    $header[] = "Accept-Encoding: gzip, deflate"; 
    $header[]="Accept-Language: en-US,en;q=0.9";

    foreach($addional_header as $head)
    {
        $header[] =$head;
    }
    
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate'); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
    if($CURLOPT_TIMEOUT==-1)
        curl_setopt($ch,CURLOPT_TIMEOUT,0);
    else
        curl_setopt($ch,CURLOPT_TIMEOUT,120);
    
    if(isset($PROXY_SERVER) && $PROXY_SERVER<>"")
    {
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_SERVER);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
//-------------------------------------------------------------------------------
function curl_post_content($url,$fields=array(),$header_only=false,$addional_header=array(),$follow_location=true,$set_referrer=true,$auth="")
{
    global $cookie_filename,$PROXY_SERVER,$CURLOPT_TIMEOUT;
    $fields_string="";
    
    if($cookie_filename=="" )    
        $cookie_filename=tempnam(sys_get_temp_dir(),"cookie");
    //url-ify the data for the POST
    if (is_array($fields))
    {
        foreach($fields as $key=>$value) 
        { 
            if(strpos($value,"%")===false)
                $fields_string .= urlencode($key).'='.urlencode($value).'&'; 
            else
                $fields_string .= urlencode($key).'='.urlencode($value).'&'; 
        }
        $fields_string=rtrim($fields_string,'&');
    }else
    {
        $fields_string=$fields;
    }
    $ch = curl_init();
    $useragent="Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET4.0C; .NET4.0E)";
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
    curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_filename); 
    curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_filename); 
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,$follow_location);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    
    if($CURLOPT_TIMEOUT==-1)
        curl_setopt($ch,CURLOPT_TIMEOUT,0);
    else
        curl_setopt($ch,CURLOPT_TIMEOUT,60);
    
    if($set_referrer)    
    {
        curl_setopt($ch, CURLOPT_REFERER, $url);
    }
    if($auth!="")
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth); //Your credentials goes here
    }
    //---------------------------------------------------------------------
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,"; 
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"; 
    $header[] = "Cache-Control: max-age=0"; 
    $header[] = "Connection: keep-alive"; 
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7"; 
    $header[] = "Accept-Language: en-us,en;q=0.5"; 
    $header[] = "Accept-Encoding: gzip,deflate,sdch"; 
    
    foreach($addional_header as $head)
    {
        $header[] =$head;
    }
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
    if($header_only)
    {
        curl_setopt($ch, CURLOPT_NOBODY,true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        
    }else
    {
        
    }
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    if(isset($PROXY_SERVER) && $PROXY_SERVER<>"")
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_SERVER);
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
    return $result;
}
//-------------------------------------------------------------------------------
function shutdown()
{
    debug( 'Program exit normally');
    
}

?>