<?php

// security stuff!!!
if( ! isset($_SERVER['argv'][1]) ) { reportError('NO ARG PARAM PASSED'); }
if( ! isset($_SERVER['USER'])) { reportError('USER ERROR - HACK ATEMPT 1?'); }
if($_SERVER['USER'] != 'root') { reportError('USER ERROR - HACK ATEMPT 2?'); }

global $projectID;
$projectID = $_SERVER['argv'][1];
$reportingType = isset($_SERVER['argv'][2]) ? 'Monthly' : 'Manual';

$db_connection = mysql_connect('localhost', 'fds', 'fdsfdsfsfas');
mysql_select_db ('webeditors_billing');
$query = "SELECT * FROM mod_seo_reports WHERE project_id ='$projectID'";
$result = mysql_query($query);

$domain = '';
$keywords = '';
while($data = mysql_fetch_assoc($result))
{
    $domain = $data['domain'];
    $keywords = creatKeywordArray($data['keywords']);
}
mysql_close($db_connection);

if($domain == '') { reportError('NO DOMAIN SELECTED: ' . "$projectID"); }
if($keywords == '') { reportError('NO KEYWORDS SELECTED: ' . "$projectID"); }

$engines = array(
    'google.com' => array('search?hl=en&num=70&q=%KEYWORD%&btnG=Search&aq=f&oq=&aqi='),
    'bing.com' => array('search?q=%KEYWORD%'),
    #'search.yahoo.com' => array('search?n=70&ei=UTF-8&p=%KEYWORD%'),
    'search.yahoo.com' => array('search?ei=UTF-8&p=%KEYWORD%'),
    'ask.com' => array('web?q=%KEYWORD%'),
);

$googleDataArray    = array();
$bingDataArray      = array();
$yahooDataArray     = array();
$askDataArray       = array();

// create engine/keywords multidimensional array
$engineKeywordArray = array();
$counter = 0;
foreach($engines as $name => $value)
{
    foreach($keywords as $keyword)
    {
        $engineKeywordArray[$counter] = array($name, $keyword);
        $counter ++;
    }
}
shuffle($engineKeywordArray); // shuffle the array so each request is random to each engine. A way to minimize how many times we hit each engine per request, mix it up a bit.

foreach($engineKeywordArray as $name => $value)
{
    $my_domain = $value[0];
    $my_keyword = $value[1];
    $my_keyword = preg_replace("/[\n\r]/","",$my_keyword); // remove newlines and cariage returns.
    switch($my_domain)
    {
        case 'google.com':
            $googleDataArray[$my_keyword]    = searchGoogle($my_domain, $my_keyword, $engines[$my_domain]);
            break;
        case 'bing.com':
            $bingDataArray[$my_keyword]      = searchBing($my_domain, $my_keyword, $engines[$my_domain]);
            break;
        case 'search.yahoo.com':
            $yahooDataArray[$my_keyword]     = searchYahoo($my_domain, $my_keyword, $engines[$my_domain]);
            break;
        case 'ask.com':
            $askDataArray[$my_keyword]       = searchAsk($my_domain, $my_keyword, $engines[$my_domain]);
            break;
    
    }
}

$serialize_googleDataArray =    serialize($googleDataArray);
$serialize_bingDataArray =      serialize($bingDataArray);
$serialize_yahooDataArray =     serialize($yahooDataArray);
$serialize_askDataArray =       serialize($askDataArray);

$datestring = date('Y') . '-' . date('m') . '-' . date('d') . ' ' . date('H') . ':' . date('i') . ':' . date('s'); // 2009-08-20 16:47:51
$data_location = '/var/www/vhosts/webeditors.com/httpdocs/client-portal/custom_seo_cron/data/';
$file_data_google = $data_location . $datestring . '-' . $domain . '-google.inc';
$file_data_yahoo = $data_location . $datestring . '-' . $domain . '-yahoo.inc';
$file_data_bing = $data_location . $datestring . '-' . $domain . '-bing.inc';
$file_data_ask = $data_location . $datestring . '-' . $domain . '-ask.inc';

$google_res = fopen($file_data_google,"w");
fwrite($google_res,$serialize_googleDataArray);
fclose($google_res);

$yahoo_res = fopen($file_data_yahoo,"w");
fwrite($yahoo_res,$serialize_yahooDataArray);
fclose($yahoo_res);

$bing_res = fopen($file_data_bing,"w");
fwrite($bing_res,$serialize_bingDataArray);
fclose($bing_res);

$ask_res = fopen($file_data_ask,"w");
fwrite($ask_res,$serialize_askDataArray);
fclose($ask_res);

# some reports take so long to execute we will connect again.
$db_connection = mysql_connect('localhost', 'fdsafdsa', 'fdsafdsa');
mysql_select_db ('webeditors_billing');
$query2 = "INSERT INTO mod_seo_report_raw_data set project_id = '$projectID', type='$reportingType', date_time = '$datestring'";
mysql_query($query2);
mysql_close($db_connection);

$to      = 'seoreports@webeditors.com';
$subject = 'Your Seo Report is Ready - ' . $domain;
$message = "cralwer.php executed Project id: $projectID  - Reporting Type: $reportingType  - Domain: $domain";
$headers = 'From: alex@webeditors.com' . "\r\n" .
'Reply-To: alex@webeditors.com' . "\r\n" .
'X-Mailer: PHP/' . phpversion();
mail($to, $subject, $message, $headers);

/**
*
*/
function searchAsk($name, $keyword, $value, $page = 1, $results = NULL)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    #$sleepInt = array(0);
    if($page == 1)
        $results = array();
    
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://www.' . $name . '/' . $value[0]; // build the url to query
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 
    if($page != 1)
        $url .= '&page=' . $page;
    
    #$input = @file_get_contents($url) or reportError($projectID . ' - ask... Could not access file: ' . $url); // get the HTML contents form the search engine
    $input = get_url_contents($url);
    #echo $url . "\n";
    
    #$pattern = "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]* class=\"L4\"(.*)>(.*)<\/a>/siU"; // this is the pattern we are looking for within the HMTL.
    $pattern = "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*class=\"title txt_lg\"(.*)>(.*)<\/a>/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $input, $matches); // match the pattern

    $results = array_merge($results, $matches[2]);
    
    $page ++;
    if($page < 8)
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return searchAsk($name, $keyword, $value, $page, $results);
    }
    else 
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return $results;
    }

}
/**
* put your comment there...
* 
*/
function searchYahoo($name, $keyword, $value, $page = 1, $first = 1, $results = NULL)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    if($page == 1)
        $results = array();
    
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://' . $name . '/' . $value[0]; // build the url to query
    if($page != 1)
        $url .= '&b=' . $first;
        
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 
    echo $url . "\n";
    
    #$input = @file_get_contents($url) or reportError($projectID . ' - bing... Could not access file: ' . $url); // get the HTML contents form the search engine
    $input = get_url_contents($url);

    $pattern = "/<div class=\"res\"><div><h3><a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a><\/h3><\/div><div class=\"abstr\">/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $input, $matches); // match the pattern

    $tmp_matches = array();
    foreach($matches[2] as $names=>$values)
    {
        list($bad, $good) =  split('\*\*', $values);
        array_push($tmp_matches, urldecode($good));
    }
    
    $results = array_merge($results, $tmp_matches);
    
    $page ++;
    $first = $first + 10;
    if($page < 8)
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return searchYahoo($name, $keyword, $value, $page, $first, $results);
    }
    else
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return $results;
    }
}
/**
* 
*/
function searchYahooOldsept162010($name, $keyword, $value, $b = 1, $results = NULL)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    if($b == 1)
        $results = array();
    
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://www.' . $name . '/' . $value[0]; // build the url to query
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 
    if($b != 1)
        $url .= '&b=' . $b;
    
    $input = get_url_contents($url);
    
    $pattern = "/<div class=\"res\"><div><h3><a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a><\/h3><\/div><div class=\"abstr\">/siU"; // this is the pattern we are looking for within the HMTL.

    preg_match_all($pattern, $input, $matches); // match the pattern

    $tmp_matches = array();
    foreach($matches[2] as $name=>$value)
    {
        list($bad, $good) =  split('\*\*', $value);
        array_push($tmp_matches, urldecode($good));
    }
    
    $results = array_merge($results, $tmp_matches);
    
    $b = $b + 10; // $b will equal 11,21,31,41, or 51
    if($b < 42)
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return searchYahoo($name, $keyword, $value, $b, $results);
    }
    else
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return $results;
    }
    
}
/**
* 
*/
function searchYahooOld($name, $keyword, $value)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://www.' . $name . '/' . $value[0]; // build the url to query
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 
    
    $input = @file_get_contents($url) or reportError($projectID . ' - yahoo... Could not access file: ' . $url); // get the HTML contents form the search engine  
    #echo $url . "\n";
    
    $pattern = "/<div class=\"res\"><div><h3><a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a><\/h3><\/div><div class=\"abstr\">/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $input, $matches); // match the pattern

    $arr_count = 0;
    foreach($matches[2] as $n => $v) // itterate through what matched to remove yahoo entries
    {
        if(preg_match("/yahoo.com/", $v)) // if anything matches with yahoo.com (yahoo maps shows on top!!)
        {
            unset($matches[2][$arr_count]); // unset the google entry from array
        }
        $arr_count++;
    }
    
    $arr_count = 0;
    foreach($matches[2] as $n => $v) // itterate through what matched to remove gogole map entries
    {
        if(preg_match("/$name/", $v)) // if anything matches with google.com (google maps shows on top!!)
        {
            unset($matches[2][$arr_count]); // unset the google entry from array
        }
        $arr_count++;
    }
    $argInt = $sleepInt[array_rand($sleepInt)];
    sleep($argInt);
    return $matches[2];
}
/**
* 
*/
function searchBing($name, $keyword, $value, $page = 1, $first = 1, $results = NULL)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    if($page == 1)
        $results = array();
    
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://www.' . $name . '/' . $value[0]; // build the url to query
    if($page != 1)
        $url .= '&first=' . $first;
        
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 
    #echo $url . "\n";
    
    #$input = @file_get_contents($url) or reportError($projectID . ' - bing... Could not access file: ' . $url); // get the HTML contents form the search engine
    $input = get_url_contents($url);

    $pattern = "/<div class=\"sb_tlst\"><h3><a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a><\/h3>/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $input, $matches); // match the pattern

    $results = array_merge($results, $matches[2]);
    
    $page ++;
    $first = $first + 10;
    if($page < 8)
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return searchBing($name, $keyword, $value, $page, $first, $results);
    }
    else
    {
        $argInt = $sleepInt[array_rand($sleepInt)];
        sleep($argInt);
        return $results;
    }
}
/**
* Google can query 100 results per page.
*/
function searchGoogle($name, $keyword, $value)
{
    global $projectID;
    $sleepInt = array(7,8,9,10,11,12,13,14);
    $keyword = str_replace(' ', '+', $keyword); // replace the plus signs with spaces
    $url = 'http://www.' . $name . '/' . $value[0]; // build the url to query
    $url = str_replace("%KEYWORD%", "$keyword", $url); // replace %KEYWORD% with $keyword 

    #$input = @file_get_contents($url) or reportError($projectID . ' - google... Could not access file: ' . $url); // get the HTML contents form the search engine
    $input = get_url_contents($url);
    #echo $url . "\n";
    
    $pattern = "/<h3 class=\"r\"><a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a><\/h3>/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $input, $matches); // match the pattern

    $arr_count = 0;
    foreach($matches[2] as $n => $v) // itterate through what matched to remove gogole map entries
    {
        if(preg_match("/$name/", $v)) // if anything matches with google.com (google maps shows on top!!)
        {
            unset($matches[2][$arr_count]); // unset the google entry from array
        }
        $arr_count++;
    }
    $argInt = $sleepInt[array_rand($sleepInt)];
    sleep($argInt);
    return $matches[2];
}
/**
* 
*/
function reportError($err)
{
    $to      = 'seoreports@webeditors.com';
    $subject = 'Cralwer Error';
    $message = $err;
    $headers = 'From: alex@webeditors.com' . "\r\n" .
    'Reply-To: alex@webeditors.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}
/**
* 
*/
function creatKeywordArray($Lines, $NewLine="\n")
{
    $lines = explode($NewLine, $Lines);
    $return = array();
    foreach ($lines as $keywords)
    {
        $return[] = $keywords;
    }
    return $return;
}
/**
* 
*/
function get_url_contents($url)
{
    $agents[] = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; Media Center PC 5.0)";
    $agents[] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
    $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5?";
    $agents[] = "Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.18) Gecko/20081203 Firefox/2.0.0.18";
    $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16";
    $agents[] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $agents[rand(0,(count($agents)-1))]); // I'm a browser too!
    $result = curl_exec($ch);
    curl_close($ch);

    $result = str_replace("\n", "", $result); // remove new lines
    $result = str_replace("\r", "", $result); // remove carriage returns
    return $result;
}
