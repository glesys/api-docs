<?
$account = "clXXXXX";
$apikey  = "secret";
$invoicesJson = file_get_contents("https://$account:$apikey@api.glesys.com/invoice/list/format/json");
$invoices = json_decode($invoicesJson,true);
print '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<rss version="2.0">
<channel>
        <title>GleSYS fakturor</title>
        <description>En list över alla fakturor för konto <?=$account?></description>
        <link>http://www.glesys.se/</link>
        <lastBuildDate><? 
                $timestamp = strtotime($invoices['response']['invoices'][0]['invoicedate']);
                $rss_datetime = date(DATE_RFC2822, $timestamp);
                print $rss_datetime;?></lastBuildDate>
        <pubDate>Mon, 19 Dec 2011 08:45:00 +0000</pubDate>
        <ttl>1800</ttl>
 

        <? foreach($invoices['response']['invoices'] as $invoice): ?>
        <item>
                <title>Faktura <?=$invoice['invoicenumber']?></title>
                <description>
                Förfaller: <?=$invoice['duedate']?>
                Belopp: <?=$invoice['total']?> <?=$invoice['currency']?>
                </description>
                <link><?=htmlentities($invoice['url'])?></link>
                <guid><?=htmlentities($invoice['url'])?></guid>
                <pubDate><?
                        $timestamp = strtotime($invoice['invoicedate']);
                        $rss_datetime = date(DATE_RFC2822, $timestamp);
                        print $rss_datetime;?></pubDate>
        </item>
        <? endforeach; ?>

</channel>
</rss>
