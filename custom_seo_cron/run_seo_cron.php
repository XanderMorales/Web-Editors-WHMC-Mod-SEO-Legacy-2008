<?php
// security stuff!!!

if( ! isset($_SERVER['USER'])) { reportError('USER ERROR - HACK ATEMPT 1?'); }
if($_SERVER['USER'] != 'root') { reportError('USER ERROR - HACK ATEMPT 2?'); }

$fp = fopen("/var/www/vhosts/webeditors.com/httpdocs/var/tmp/seo-cron-job.txt", "w");

// do an exclusive lock
if (flock($fp, LOCK_EX))
{
    // connect to db to check which crons are ready to run!
    $db_connection = mysql_connect('localhost', 'fdsafsda', 'fdsfds');
    mysql_select_db ('webeditors_billing');

    $today = date('j');
    $query = "SELECT * FROM mod_seo_reports WHERE cron_day = '$today' AND cron_status = 'Pending' LIMIT 1";
    $result = mysql_query($query);

    $id = NULL;
    while($data = mysql_fetch_assoc($result))
    {
        $cron_day = $data['cron_day'];
        $project_id = $data['project_id'];
        $id = $data['id'];
        $domain = $data['domain'];

        // ftruncate($fp, 0);  // truncate file
        $date_time = date('l \t\h\e jS \of F Y \a\t h:i:s A');
        fwrite($fp, "Cron started at $date_time for $domain\n");
        
        $to      = 'alex@webeditors.com';
        $subject = 'Crawler Request Cron';
        $message = "Preparing to Run for $domain";
        $headers = 'From: alex@webeditors.com' . "\r\n" .
        'Reply-To: alex@webeditors.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
        mail($to, $subject, $message, $headers);
        
        $command = 'php /var/www/vhosts/webeditors.com/httpdocs/client-portal/custom_seo_cron/crawler.php ' . $project_id . ' ' . 'Monthly';
        exec($command);
    }
    
    // close db connection!
    mysql_close($db_connection);
    
    // re-open db connection... incase it took a while fo the loop to run
    $db_connection = mysql_connect('localhost', 'webdev', '86753Oh9');
    mysql_select_db ('webeditors_billing');
    
    $query2 = "UPDATE mod_seo_reports SET cron_status = 'Completed' WHERE id = '$id'";
    $result2 = mysql_query($query2);
    
    $query3 = "UPDATE mod_projects SET phase = 'Reporting' WHERE id = '$project_id'";
    $result3 = mysql_query($query3);
    
    // check yesterdays cron. and change the flag in the database!
    $yesterday = date('j') - 1;
    $query4 = "UPDATE mod_seo_reports SET cron_status = 'Pending' WHERE cron_day = '$yesterday' AND cron_status = 'Completed'";
    $result4 = mysql_query($query4);
    
    // close db connection again!
    mysql_close($db_connection);
    
    //$completed_date_time = date('l \t\h\e jS \of F Y \a\t h:i:s A');
    flock($fp, LOCK_UN); // release the lock
}

fclose($fp);


/**
* put your comment there...
* 
* @param mixed $err
*/
function reportError($err)
{
    $to      = 'alexander@webeditors.com';
    $subject = 'SEO Cron Error';
    $message = $err;
    $headers = 'From: alex@webeditors.com' . "\r\n" .
    'Reply-To: alex@webeditors.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    return true;
}
