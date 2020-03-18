<?php
/*
Staff would build links in articles.

I would ask for URL;s of said links.

I would run it through this script to check links to money sites and validity of article url.
*/
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
    
echo '
<script>
$(document).ready(function () {
    $("h1").eq(0).text("Article Quality Control");
    $("h2").eq(0).text("");
});
</script>
';

$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : NULL;

switch($action)
{
    case NULL;
        showForm();
        break;
    case 'checkData';
        checkData();
        break;
}

/**
* 
*/
function showForm()
{
?>
This form checks artlice url's for valid webpages returning a 200 HTML error code (OK) and also checks for links back to the website we are writting the articles for.
<br>
<br>
<form action="/client-portal/admin/addonmodules.php" method="post">
<input type="hidden" name="module" value="article_quality_control" />
<input type="hidden" name="action" value="checkData" />
site url: (example: site.com)<br><input type="text" name="url" size="45" />
<br>
<br>
article urls:<br><textarea name="aticle_urls" cols="150" rows="15"></textarea>
<br>
<br>
<input type="submit" value="Check Articles" />
</form>
<?php
}
/**
* 
*/
function checkData()
{
    showForm();
    
    $article_urls =  convertArtcileUrlsToArray($_REQUEST['aticle_urls']);
    
    echo '<br><br><b>Results</b><br><br>';
    
    echo '<textarea name="checked_results" cols="150" rows="15">';
    foreach($article_urls as $key => $url)
    {
        ###
        $status = checkPageStatus($url);
        $html = $status[0];
        $httpcode = $status[1];
        ###
        
        if($httpcode != '200')
        {
            echo $httpcode . ' Error' . "\n";
            echo "$url\n\n";
        }
        elseif(($is_links = getHyperlinks($html, $_REQUEST['url'])) == 'NO LINKS FOUND!')
        {
            echo $is_links . "\n";
            echo "$url\n\n";
        }
    }
    echo '</textarea>';
    #print_r($artcle_urls);
}
/**
* 
*/
function getHyperlinks($html, $url)
{
    $pattern = "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU"; // this is the pattern we are looking for within the HMTL.
    preg_match_all($pattern, $html, $matches); // match the pattern
    
    $count = 0;
    foreach($matches[2] as $pos => $link_url)
    {
        $pattern = "/^$link_url/i";
        if(strpos(strtolower($link_url), strtolower($url)))
        {
            $count++;
        }
    }
    
    if($count == 0)
    {
        return 'NO LINKS FOUND!';
    }
}
/**
* We are going to check for a 200 status
*/
function checkPageStatus($url, $timeout = 10)
{
    $agents[] = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; Media Center PC 5.0)";
    $agents[] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
    $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5?";
    $agents[] = "Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.18) Gecko/20081203 Firefox/2.0.0.18";
    $agents[] = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16";
    $agents[] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1";
    
    $ch = curl_init(); // get cURL handle

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, $agents[rand(0,(count($agents)-1))]); // I'm a browser too!
    
    $html = curl_exec($ch); // just do it!
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch); // close handle

    return array($html, $httpcode);
}
/**
* 
*/
function convertArtcileUrlsToArray($urls)
{
    $arry_names = split("[\n]", $urls);
    return $arry_names;
}
