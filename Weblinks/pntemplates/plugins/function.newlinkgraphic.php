<?php
/**
 * Zikula Application Framework
 *
 * Weblinks
 *
 * @version $Id: function.newlinkgraphic.php 40 2009-01-09 14:13:23Z herr.vorragend $
 * @copyright 2010 by Petzi-Juist
 * @link http://www.petzi-juist.de
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

function smarty_function_newlinkgraphic($params, &$smarty)
{
    echo "&nbsp;";
    ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $params['time'], $datetime);

    $datenow = $datetime[3]."-".$datetime[2]."-".$datetime[1];

    $startdate = time();
    $count = 0;

    while ($count <= 7) {
        $daysold = ml_ftime('%d-%m-%Y', $startdate);

        if ("$daysold" == "$datenow") {
            if ($count<=1) {
                echo "<img src=\"modules/Weblinks/pnimages/newred.gif\" width=\"34\" height=\"15\" alt=\"".DataUtil::formatForDisplay(__('New today', $dom))."\" title=\"".DataUtil::formatForDisplay(__('New today', $dom))."\" />";
            }
            if ($count<=3 && $count>1) {
                echo "<img src=\"modules/Weblinks/pnimages/newgreen.gif\" width=\"34\" height=\"15\" alt=\"".DataUtil::formatForDisplay(__('New during last 3 days', $dom))."\" title=\"".DataUtil::formatForDisplay(__('New during last 3 days', $dom))."\" />";
            }
            if ($count<=7 && $count>3) {
                echo "<img src=\"modules/Weblinks/pnimages/newblue.gif\" width=\"34\" height=\"15\" alt=\"".DataUtil::formatForDisplay(__('New this week', $dom))."\" title=\"".DataUtil::formatForDisplay(__('New this week', $dom))."\" />";
            }
        }
        $count++;
        $startdate = (time()-(86400 * $count));
    }
    return;
}