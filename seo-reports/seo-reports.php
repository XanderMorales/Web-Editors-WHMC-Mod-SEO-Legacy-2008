<?php
global $projectID;
$projectID = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : NULL;
if( ! $projectID ) { echo "No Project ID passed.... exiting... goodby"; exit(); }
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : NULL;

/* switch board */
switch($action)
{
	case 'list':
		listReports($projectID);
		break;
	case 'configure':
		configureReport();
		break;
	case 'configuresubmit':
		configureSubmit();
		break;
	case 'show_report':
		showReport($projectID);
		break;
	default:
		listReports($projectID);
}
/**
* 
*/
function showReport($projectID)
{
?>
<script>
$(document).ready(function () {
    $("h1").eq(0).text("SEO Reports");
    $("h2").eq(0).text("");
});
</script>
<?php
	$writeable_directory = '/home/httpd/vhosts/webeditors.com/httpdocs/media/html/seo-reports/';
    $file_name = $_REQUEST['file_name'];
    
    $TEST_DEV = 'OFF'; // OFF OR ON - Dev only!!!!!!!!!!!!!!!!
    
    
    if($TEST_DEV == 'OFF')
    {
	    if( file_exists($writeable_directory . $file_name . '.html') )
	    {
	        #header('Location: /media/html/seo-reports/' . $file_name . '.html');
			#exit();
	    }
	}
    
    
	$query = "SELECT *, DATE_FORMAT(date_time,'%l:%i:%s %p on %M %D, %Y') as mydate FROM mod_seo_reports Inner Join mod_seo_report_raw_data ON mod_seo_reports.project_id = mod_seo_report_raw_data.project_id WHERE mod_seo_reports.project_id = '$projectID' AND mod_seo_report_raw_data.id = '{$_REQUEST['raw_id']}' LIMIT 1";
	$result = mysql_query($query);

	while($data = mysql_fetch_assoc($result))
	{
		$domain = $data['domain'];
		$report_type = $data['type'];
		$report_date_time = $data['mydate'];
        $datestring = $data['date_time'];
        $db_keyword_list = $data['keywords'];
	}
    
    $data_location = '/home/httpd/vhosts/webeditors.com/httpdocs/client-portal/custom_seo_cron/data/';
    $file_data_google	= $data_location . $datestring . '-' . $domain . '-google.inc';
    $file_data_yahoo	= $data_location . $datestring . '-' . $domain . '-yahoo.inc';
    $file_data_bing		= $data_location . $datestring . '-' . $domain . '-bing.inc';
    $file_data_ask		= $data_location . $datestring . '-' . $domain . '-ask.inc';
    
    $googleDataArray	= unserialize(file_get_contents($file_data_google));
    $yahooDataArray		= unserialize(file_get_contents($file_data_yahoo));
    $bingDataArray		= unserialize(file_get_contents($file_data_bing));
    $askDataArray		=	unserialize(file_get_contents($file_data_ask));
    
	ksort($googleDataArray);
	ksort($yahooDataArray);
	ksort($bingDataArray);
	ksort($askDataArray);
	
	$keywordCount = LineStatisticsByString($db_keyword_list);
	   
	$html = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
        <title>Search Engine Ranking Report for ' . $domain .'</title>
        <script src="http://www.webeditors.com/client-portal/includes/jscript/sorttable.js"></script>
        <style type="text/css">
        body,td { font-family:verdana; font-size:12px; color:#000000; }
        .green_heading { color:#ffffff; background-color:#539e3a; font-weight:bold; font-size:16px; font-family:\'Trebuchet MS\', Arial, Helvetica, sans-serif; margin:0px; margin-bottom:3px; padding:3px; }
        .blue_heading { color:#ffffff; font-size:20px;font-family:\'Trebuchet MS\', Arial, Helvetica, sans-serif; margin:0px; padding:3px; background-color:#38599b; margin-bottom:3px; }
        a { color:#38599b; }
        a:hover { color:#38599b; text-decoration:none; }
        .sortable thead th { cursor:default; }
        .data th { text-align:left; background-color:#9cce6d; font-weight:bold; font-size:16px; font-family: \'Trebuchet MS\', Arial, Helvetica, sans-serif; color:#38599b; text-decoration:underline; }
        .data th:hover { color:#38599b; text-decoration:none;}        
        .data td { background-color:#f2f2f2; font-size:12px; font-family:arial; }
        </style>
        
        </head>
        <body>
		<table width="700" cellpadding="0" cellspacing="0" border="0" align="center">
			<tr>
				<td>
				<div style="margin-top:20px;margin-bottom:7px;">
					<div style="float:left; height:85px; color:#519e45; font-size:20px; font-weight:bold; font-family:\'Trebuchet MS\', Arial, Helvetica, sans-serif;"><img src="http://www.webeditors.com/media/images/logo.gif" border="0" alt="Web Editors SEO Reports" /><br />Search Engine Ranking Report</div>
					<div style="float:right; height:85px;"><p style="font-family:verdana; font-size:12px; color:#000000">Web Editors, Inc.<br />27537 Commerce Center Dr.<br />Suite 106<br />Temecula, CA 92590<br />951-506-0926</p></div>
				</div>
				</td>
			</tr>
			<tr>
				<td>
				<br />
					<div class="blue_heading"> Ranking Overview</div>
					Date Run: ' . $report_date_time . '<br />
					Report ID:' . $projectID . '<br />
					Report for:' . $domain . '<br />
					<br /><br />
					<div class="green_heading">Ranking Selection</div>
					' . $keywordCount . ' Keywords and one URL have been checked on 4 search engines. The first 70 result pages of each engine have been checked.

					<br /><br /><br />
                    <div class="green_heading"> Visibility Statistics</div>
    ';
    $results = '';
    $top5 = 0;
    $top10 = 0;
    $top20 = 0;
    $top30 = 0;
    $top50 = 0;
    $top70 = 0;
    $top_total = 0;
    
	#############################################
	$results .= '<div class="green_heading"><img src="http://www.webeditors.com/client-portal/images/favicon_google.jpg" /> Google.com</div>';
	list($gresults, $gtop5, $gtop10, $gtop20, $gtop30, $gtop50, $gtop70) = getEngineResults($googleDataArray, $top5, $top10, $top20, $top30, $top50, $top70, $domain);
	$results .= $gresults;
	$top5 = $gtop5;
	$top10 = $gtop10;
	$top20 = $gtop20;
	$top30 = $gtop30;
	$top50 =  $gtop50;
	$top70 = $gtop70;
    
	#############################################
	$results .= '<div class="green_heading"><img src="http://www.webeditors.com/client-portal/images/favicon_yahoo.jpg" /> Yahoo.com</div>';
	list($yresults, $ytop5, $ytop10, $ytop20, $ytop30, $ytop50, $ytop70) = getEngineResults($yahooDataArray, $top5, $top10, $top20, $top30, $top50, $top70, $domain);
	$results .= $yresults;
	$top5 = $ytop5;
	$top10 = $ytop10;
	$top20 = $ytop20;
	$top30 = $ytop30;
	$top50 =  $ytop50;
	$top70 = $ytop70;

	#############################################
	$results .= '<div class="green_heading"><img src="http://www.webeditors.com/client-portal/images/favicon_bing.jpg" /> Bing.com</div>';
	list($bresults, $btop5, $btop10, $btop20, $btop30, $btop50, $btop70) = getEngineResults($bingDataArray, $top5, $top10, $top20, $top30, $top50, $top70, $domain);
	$results .= $bresults;
	$top5 = $btop5;
	$top10 = $btop10;
	$top20 = $btop20;
	$top30 = $btop30;
	$top50 =  $btop50;
	$top70 = $btop70;
   
   #############################################
 	$results .= '<div class="green_heading"><img src="http://www.webeditors.com/client-portal/images/favicon_ask.jpg" /> Ask.com</div>';
	list($aresults, $atop5, $atop10, $atop20, $atop30, $atop50, $atop70) = getEngineResults($askDataArray, $top5, $top10, $top20, $top30, $top50, $top70, $domain);
	$results .= $aresults;
	$top5 = $atop5;
	$top10 = $atop10;
	$top20 = $atop20;
	$top30 = $atop30;
	$top50 =  $atop50;
	$top70 = $atop70;
	
	$top_total = ($top5 + $top10 + $top20 + $top30 + $top50 + $top70);
	
	#############################################
	$my_total_page1 = ($top5 + $top10);
	$my_total_page2 = $top20;
	$my_total_page3 = $top30;
	
	$keywordList = formatKeywords($db_keyword_list, $googleDataArray, $yahooDataArray, $bingDataArray, $askDataArray, $domain, $my_total_page1, $my_total_page2, $my_total_page3);
                    $html .= '
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 5 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">' . $top5 .'</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 10 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">'. $top10 . '</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 20 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">'. $top20 . '</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 30 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">'. $top30 . '</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 50 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">'. $top50 . '</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;">Listings in the top 70 positions:</td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;">'. $top70 . '</td>
                        </tr>
                        <tr>
                            <td style="font-family:verdana; font-size:12px; color:#000000; padding-right:10px;"><b>Total Listings:</b></td>
                            <td style="font-family:verdana; font-size:12px; color:#000000;"><b>'. $top_total . '</b></td>
                        </tr>
                    </table>
		<br /><br />
        <div class="green_heading">Checked Keywords</div>
        <div style="float:left; font-family:verdana; font-size:12px; color:#000000; text-align:justify; clear:both;">' . $keywordList . '</div>
	    
        
        <div style="clear:both"><br /><br /></div>
        <div style="clear:both;" class="blue_heading">Ranking Results</div><br />';

	$html_to_convert =  $html . $results . '</td></tr></table></body></html>';
	
	if($TEST_DEV == 'ON')
    {
		echo $html_to_convert;
		exit();
	}
    
    
    // create HTML FILE only if it does not exist
    #if( ! file_exists($writeable_directory . $file_name . '.html') )
    #{
        $html_file_name = $writeable_directory . $file_name . ".html";
        $output = fopen($html_file_name, 'w');
        fwrite ($output,$html_to_convert);
        fclose ($output);
    #}
    header('Location: /media/html/seo-reports/' . $file_name . '.html');
}
/**
* 
*/
function listReports($projectID)
{
?>
<form>
<table class="datatable" width="100%">
	<tr>
		<th colspan="4">SEO Report Configuration  - <a href="/client-portal/admin/addonmodules.php?module=seo-reports&id=<?=$projectID?>&action=configure"><img align="absmiddle" src="/client-portal/admin/images/edit.gif" title="Configure Report" alt="Configure Report"></a></th>
	</tr>
	<tr>
		<th>Project ID</th>
		<th>Domain Name</th>
		<th>Keywords</th>
		<th>Cron Day</th>
	</tr>
	<tr>
<?php
	$result = select_query("mod_seo_reports", "mod_seo_reports.*", "project_id = '$projectID'");
	$count = 0;
	while ($data = mysql_fetch_array($result))
    {
    	echo '<td>' . $projectID . '</td>';
    	echo '<td>' . $data['domain'] . '</td>';
    	echo '<td> total: ' . LineStatisticsByString($data['keywords']) . '<br /><textarea rows="4" cols="75">' . $data['keywords'] . '</textarea></td>';
    	echo '<td>' . $data['cron_day'] . '</td>';
        showAvailableReports($projectID, $data['domain']);
    	$count++;
	}
	if($count == 0)
	{
		echo '<td colspan="3">Nothing Configured... please <a href="/client-portal/admin/addonmodules.php?module=seo-reports&id=' . $projectID . '&action=configure">click here</a> to configure your SEO Report.</td>';
	}
?>
	</tr>
</table>
</form>
<br /><br />
<?php
}
/**
* 
*/
function showAvailableReports($projectID, $domain)
{
	echo '<table cellpadding="0" cellspacing="0" border="0"><tr><td><h3 style="margin:0px; padding:0px;">Available Reports:</h3></td></tr>';
	$query = "SELECT *, DATE_FORMAT(date_time,'%Y-%m-%d-%l:%i:%s:%p') as mydate FROM mod_seo_report_raw_data WHERE project_id = '$projectID'";
	$result = mysql_query($query);
	while($data = mysql_fetch_assoc($result))
	{
        $file_name = $data['mydate'] . '-' . $data['type'] . '-' . $domain;
		echo '<tr>';
		echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Report Name: <a target="_blank" href="/client-portal/admin/addonmodules.php?module=seo-reports&id=' . $projectID . '&action=show_report&raw_id=' . $data['id'] . '&file_name=' . $file_name . '">' . $file_name .'.html</a></td>';
		echo '</tr>';
	}
	echo '</td></tr></table></pre>';	
}
/**
* 
*/
function configureReport()
{
    global $projectID;
?>
<script>
$(document).ready(function () {
    $("h1").eq(0).text("Configure SEO Reports");
    $("h2").eq(0).text("");
});
</script>
<?php
    $query = "SELECT * FROM mod_seo_reports WHERE project_id = '$projectID'";
    $result = mysql_query($query);
    $sql_type = 'INSERT';
    
    $domain = '';
    $keywords = '';
    $cron_day = '';
    while($data = mysql_fetch_assoc($result))
    {
        $sql_type = 'UPDATE';
        $domain = $data['domain'];
        $keywords = $data['keywords'];
        $cron_day = $data['cron_day'];
    }
?>
<form action="/client-portal/admin/addonmodules.php" method="post">
<input type="hidden" name="module" value="seo-reports" />
<input type="hidden" name="id" value="<?=$projectID?>" />
<input type="hidden" name="action" value="configuresubmit" />
<input type="hidden" name="sql_type" value="<?=$sql_type?>" />
<b>Domain</b> (example: domain.com - do not include the "http://www.")
<br />
<input type="text" name="domain" value="<?=$domain?>" size="75" /><br />

<br /><b>Keywords:</b> (Insert one keyword per line)<br />
<textarea name="keywords" rows="25" cols="75"><?=$keywords?></textarea><br />

<br /><b>Cron Day</b><br />
<select name="cron_day">
<?php
    $cron_days = array('NO', 2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28);
    
    foreach($cron_days as $sel_cron_day)
    {
        if($cron_day == $sel_cron_day)
            echo '<option value="' . $sel_cron_day . '" selected="selected">' . $sel_cron_day . '</option>' . "\n";
        else
            echo '<option value="' . $sel_cron_day . '">' . $sel_cron_day . '</option>' . "\n";
    }
?>
</select><br />

<br />
<input type="submit" value="Submit">
</form>

<?php
}

/**
* 
*/
function configureSubmit()
{  
    if($_REQUEST['sql_type'] == 'UPDATE')
    {
        $query = 'UPDATE mod_seo_reports SET domain="' . $_REQUEST['domain'] . '", keywords="' . $_REQUEST['keywords'] . '", cron_day="' . $_REQUEST['cron_day'] . '" WHERE project_id="' . $_REQUEST['id'] . '"';
    }
    else
    {
        $query = 'INSERT INTO mod_seo_reports SET domain="' . $_REQUEST['domain'] . '", keywords="' . $_REQUEST['keywords'] . '", cron_day="' . $_REQUEST['cron_day'] . '", project_id="' . $_REQUEST['id'] . '"';
    }
    $result = mysql_query($query);
    header('Location: /client-portal/admin/addonmodules.php?module=projects&action=view&id=' . $_REQUEST['id']);
}

/**
* 
*/
function LineStatisticsByString($Lines, $NewLine="\n")
{ 
	$lines = explode($NewLine, $Lines);
    return count($lines);
}
/**
* put your comment there...
* 
* @param mixed $dataArray
* @param mixed $domain
* @param mixed $my_keyword
* @return mixed
*/
function engineKeywordStat($dataArray, $domain, $my_keyword)
{
	$page1 = 0;
	$page2 = 0;
    $page3 = 0;

    $count_pos = 1;
	foreach($dataArray[$my_keyword] as $pos => $link_url)
	{
		$pattern = "/^$link_url/i";
		if(strpos(strtolower($link_url), strtolower($domain)))
		{
			if($count_pos <= 10){   $page1++; }
			elseif($count_pos <= 20){   $page2++; }
			elseif($count_pos <= 30){   $page3++; }
		}
		$count_pos ++;
	}
	
	return array($page1, $page2, $page3);
}
/**
* 
*/
function formatKeywords($Lines, $googleDataArray, $yahooDataArray, $bingDataArray, $askDataArray, $domain, $my_total_page1, $my_total_page2, $my_total_page3, $NewLine="\n")
{
    $lines = explode($NewLine, $Lines);
	$return = '
	<table cellpadding="3" cellspacing="1" border="0" width="700" class="data sortable">
		<thead>
			<tr>
				<th>Keyword</th>
				<th style="text-align:right; padding-right:7px;">' . $my_total_page1 . ' Listings<br />on Page 1</th>
				<th style="text-align:right; padding-right:7px;">' . $my_total_page2 . ' Listings<br />on Page 2</th>
				<th style="text-align:right; padding-right:7px;">' . $my_total_page3 . ' Listings<br />on Page 3</th>
			</tr>
		</thead>
	';

	foreach ($lines as $keywords)
    {
    	$newstring = preg_replace("/[\n\r]/","",$keywords); 
    	$return .= "
    		<tr>
    			<td>$keywords</td>
    	";
		$page1 = 0;
		$page2 = 0;
		$page3 = 0;
		
		list($gpage1, $gpage2, $gpage3) = engineKeywordStat($googleDataArray, $domain, $newstring);
    	list($ypage1, $ypage2, $ypage3) = engineKeywordStat($yahooDataArray, $domain, $newstring);
    	list($bpage1, $bpage2, $bpage3) = engineKeywordStat($bingDataArray, $domain, $newstring);
    	list($apage1, $apage2, $apage3) = engineKeywordStat($askDataArray, $domain, $newstring);
    	
		$page1 = $gpage1 + $ypage1 + $bpage1 + $apage1;
		$page2 = $gpage2 + $ypage2 + $bpage2 + $apage2;
		$page3 = $gpage3 + $ypage3 + $bpage3 + $apage3;
		
		$no_list = '<span style="font-size:0px;">9999999999999999999999999999</span>';
		$page1 = ($page1 == 0) ? $no_list : $page1;
		$page2 = ($page2 == 0) ? $no_list : $page2;
		$page3 = ($page3 == 0) ? $no_list : $page3;
		
    	$return .= "
    			<td>$page1</td>
    			<td>$page2</td>
    			<td>$page3</td>
    		</tr>
    	";
	}
	
	$return .= '</table>';
	return $return;
}
/**
* CHECK ENGINES!!
*/
function getEngineResults($dataArray, $top5, $top10, $top20, $top30, $top50, $top70, $domain)
{
	#echo $domain ; print_r($dataArray);
	$engine_item_count = 0;
	$results = '
		<table cellpadding="3" cellspacing="1" border="0" width="100%" class="data sortable">
			<thead>
				<tr>
					<th>URL</th>
					<th>Keyword</th>
					<th style="width:30px;">Pos.</th>
					<th style="width:30px;">Page</th>
				</tr>
			</thead>
	';
	
    foreach($dataArray as $keyword => $data)
    {	
        $count_pos = 1;
        $found_item = '';
        $item_count = 0;
        
        foreach($data as $link_url)
        {
            $pattern = "/^$link_url/i";
            if(strpos(strtolower($link_url), strtolower($domain)))
            {
                if($count_pos <= 5) {       $top5++; $page = 1; }
                elseif($count_pos <= 10){   $top10++; $page = 1; }
                elseif($count_pos <= 20){   $top20++; $page = 2; }
                elseif($count_pos <= 30){   $top30++; $page = 3; }
                elseif($count_pos <= 40){   $page = 4; }
                elseif($count_pos <= 50){   $top50++; $page = 5; }
                elseif($count_pos <= 60){ 	$page = 6; }
                elseif($count_pos <= 70){   $top70++; if($count_pos > 60) { $page = 7; } }
                $found_item .= "
                	<tr>
                		<td><a href=\"$link_url\">$link_url</a></td>
                		<td>$keyword</td>
                		<td>$count_pos</td>
                		<td>$page</td>
                	</tr>
                ";
                $item_count++;
                $engine_item_count++;
            }
            $count_pos ++;
        }
        
        if($item_count > 0)
        {
            $results .= $found_item;
		}
    }
        
    if($engine_item_count == 0)
    {
        $results = '&nbsp;&nbsp;No Listings found<br /><br /><br />';
	}
	else
	{
		$results .= '</table><br /><br /><br />';
	}
	
	return array($results, $top5, $top10, $top20, $top30, $top50, $top70);
}
