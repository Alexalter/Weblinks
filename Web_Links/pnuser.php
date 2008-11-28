<?php
/**
 * Zikula Application Framework
 *
 * Web_Links
 *
 * @version $Id$
 * @copyright 2008 by Petzi-Juist
 * @link http://www.petzi-juist.de
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
* the main user function
*/
function Web_Links_user_main() //ready
{
    return Web_Links_user_view();
}

/**
* view
*/
function Web_Links_user_view() //ready
{
    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // get the main categories
    $categories = pnModAPIFunc('Web_Links', 'user', 'categories');

    // The return value of the function is checked
    if (!$categories) {
        return DataUtil::formatForDisplayHTML(_WL_NOCATS);
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    // assign various useful template variables
    $pnRender->assign('categories', $categories);
    $pnRender->assign('numrows', pnModAPIFunc('Web_Links', 'user', 'numrows'));
    $pnRender->assign('catnum', pnModAPIFunc('Web_Links', 'user', 'catnum'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_view.html');
}

function Web_Links_user_category($args)
{
    // Get parameters from whatever input we need
    $cid = (int)FormUtil::getPassedValue('cid', isset($args['cid']) ? $args['cid'] : null, 'GET');
    $orderby = FormUtil::getPassedValue('orderby', isset($args['orderby']) ? $args['orderby'] : 'titleA', 'GET');
    $startnum = (int)FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : 1, 'GET');

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    $category = pnModAPIFunc('Web_Links', 'user', 'category', array('cid' => $cid));

    $subcategory = pnModAPIFunc('Web_Links', 'user', 'subcategory', array('cid' => $cid));

    $weblinks = pnModAPIFunc('Web_Links', 'user', 'weblinks', array('cid' => $cid,
                                                                    'orderbysql' => pnModAPIFunc('Web_Links', 'user', 'orderbyin', array('orderby' => $orderby)),
                                                                    'startnum' => $startnum,
                                                                    'numlinks' => pnModGetVar('Web_Links', 'perpage')));

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('orderby', $orderby);
    $pnRender->assign('category', $category);
    $pnRender->assign('subcategory', $subcategory);
    $pnRender->assign('weblinks', $weblinks);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);
    $pnRender->assign('pager', array('numlinks' => pnModAPIFunc('Web_Links', 'user', 'countcatlinks', array('cid' => $cid)),
                                     'itemsperpage' => pnModGetVar('Web_Links', 'perpage')));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_category.html');
}

function Web_Links_user_visit($args)
{
    // Get parameters from whatever input we need.
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    // Load API.
    if (!pnModAPILoad('Web_Links', 'user')) {
        pnSessionSetVar('errormsg', _LOADFAILED);
        pnRedirect(pnModURL('Web_Links', 'user', 'view'));
    }

    // The API function is called.
    $link = pnModAPIFunc('Web_Links', 'user', 'link', array('lid' => $lid));

    // The return value of the function is checked here
    if ($link == false) {
        pnSessionSetVar('statusmsg', _WL_NOSUCHLINK);
        return false;
    }

    // The API function is called.
    $return = pnModAPIFunc('Web_Links', 'user', 'hitcountinc', array('lid' => $lid));

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::Link', '::$lid', ACCESS_READ)) {
        pnSessionSetVar('statusmsg',_MODULENOAUTH);
        pnRedirect(pnModURL('Web_Links', 'user', 'view'));
        return false;
    } else {
        // Is the URL local?
        if (eregi('^http:|^ftp:|^https:', $link['url'])) {
            pnRedirect($link['url']);
        } else {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $link['url']);
        }
    }

    // Return
    return true;

}

function Web_Links_user_search($args)
{
    // Get parameters from whatever input we need
    $query = FormUtil::getPassedValue('query', isset($args['query']) ? $args['query'] : null, 'GETPOST');
    $orderby = FormUtil::getPassedValue('orderby', isset($args['orderby']) ? $args['orderby'] : 'titleA', 'GETPOST');
    $startnum = (int)FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : 1, 'GETPOST');

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    $subcategory = pnModAPIFunc('Web_Links', 'user', 'searchcats', array('query' => $query));

    $weblinks = pnModAPIFunc('Web_Links', 'user', 'search_weblinks', array('query' => $query,
                                                                  'orderbysql' => pnModAPIFunc('Web_Links', 'user', 'orderbyin', array('orderby' =>$orderby)),
                                                                  'startnum' => $startnum,
                                                                  'numlinks' => pnModGetVar('Web_Links', 'linksresults')));

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('query', $query);
    $pnRender->assign('subcategory', $subcategory);
    $pnRender->assign('orderby', $orderby);
    $pnRender->assign('weblinks', $weblinks);
    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);
    $pnRender->assign('pager', array('numlinks' => pnModAPIFunc('Web_Links', 'user', 'countsearchlinks', array('query' => $query)),
                                     'itemsperpage' => pnModGetVar('Web_Links', 'linksresults')));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_searchresults.html');
}

function Web_Links_user_randomlink()
{
    pnRedirect(pnModURL('Web_Links', 'user', 'visit', array('lid' => pnModAPIFunc('Web_Links', 'user', 'random'))));

    return true;
}

function Web_Links_user_viewlinkdetails($args)
{
    // Get parameters from whatever input we need.
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    $linkdetails = pnModAPIFunc('Web_Links', 'user', 'link', array('lid' => $lid));

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender =& new pnRender('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);
    $pnRender->assign('linkdetails', $linkdetails);
    $pnRender->assign(pnModAPIFunc('Web_Links', 'user', 'totalcomments', array('lid' => $lid)));
    $pnRender->assign(pnModAPIFunc('Web_Links', 'user', 'votes', array('lid' => $lid)));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_details.html');
}

function Web_Links_user_newlinks($args)
{
    // Get parameters from whatever input we need.
    $newlinkshowdays = (int)FormUtil::getPassedValue('newlinkshowdays', isset($args['newlinkshowdays']) ? $args['newlinkshowdays'] : '7', 'GET');

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('mainlink', 1);
    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('newlinkshowdays', $newlinkshowdays);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_newlinks.html');
}

function Web_Links_user_newlinksdate($args)
{
    $selectdate = (int)FormUtil::getPassedValue('selectdate', isset($args['selectdate']) ? $args['selectdate'] : null, 'GET');

    $totallinks = pnModAPIFunc('Web_Links', 'user', 'totallinks', array('selectdate' => $selectdate));

    $weblinks = pnModAPIFunc('Web_Links', 'user', 'weblinksbydate', array('selectdate' => $selectdate));

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('dateview', (ml_ftime(_DATELONG, $selectdate)));
    $pnRender->assign('totallinks', $totallinks);
    $pnRender->assign('weblinks', $weblinks);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_newlinksdate.html');
}

function Web_Links_user_mostpopular($args)
{
    $ratenum = (int)FormUtil::getPassedValue('ratenum', isset($args['ratenum']) ? $args['ratenum'] : null, 'GET');
    $ratetype = FormUtil::getPassedValue('ratetype', isset($args['ratetype']) ? $args['ratetype'] : null, 'GET');

    $mostpoplinkspercentrigger = pnModGetVar('Web_Links', 'mostpoplinkspercentrigger');
    $mostpoplinks = pnModGetVar('Web_Links', 'mostpoplinks');
    $mainvotedecimal = pnModGetVar('Web_Links', 'mainvotedecimal');


    if ($ratenum != "" && $ratetype != "") {
        if (!is_numeric($ratenum)) {
            $ratenum=5;
        }
        if ($ratetype != "percent") {
            $ratetype = "num";
        }
        $mostpoplinks = $ratenum;
        if ($ratetype == "percent") {
            $mostpoplinkspercentrigger = 1;
        }
    }
    if ($mostpoplinkspercentrigger == 1) {
        $toplinkspercent = $mostpoplinks;

        $dbconn =& pnDBGetConn(true);
        $pntable =& pnDBGetTables();

        $result =& $dbconn->Execute("SELECT COUNT(*) FROM $pntable[links_links]");
        list($totalmostpoplinks) = $result->fields;

        $mostpoplinks = $mostpoplinks / 100;
        $mostpoplinks = $totalmostpoplinks * $mostpoplinks;
        $mostpoplinks = round($mostpoplinks);
        $mostpoplinks = max(1, $mostpoplinks);
    }

    $weblinks = pnModAPIFunc('Web_Links', 'user', 'weblinksmostpop', array('mostpoplinks' => $mostpoplinks));

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('mostpoplinkspercentrigger', $mostpoplinkspercentrigger);
    $pnRender->assign('toplinkspercent', $toplinkspercent);
    $pnRender->assign('totalmostpoplinks', $totalmostpoplinks);
    $pnRender->assign('mostpoplinks', $mostpoplinks);
    $pnRender->assign('weblinks', $weblinks);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_mostpopular.html');
}

function Web_Links_user_toprated($args)
{
    $ratenum = (int)FormUtil::getPassedValue('ratenum', isset($args['ratenum']) ? $args['ratenum'] : null, 'GET');
    $ratetype = FormUtil::getPassedValue('ratetype', isset($args['ratetype']) ? $args['ratetype'] : null, 'GET');

    $toplinkspercentrigger = pnModGetVar('Web_Links', 'toplinkspercentrigger');
    $toplinks = pnModGetVar('Web_Links', 'toplinks');
    $linkvotemin = pnModGetVar('Web_Links', 'linkvotemin');

    if ($ratenum != "" && $ratetype != "") {
        if (!is_numeric($ratenum)) {
            $ratenum=5;
        }
        if ($ratetype != "percent") {
            $ratetype = "num";
        }
        $toplinks = $ratenum;
        if ($ratetype == "percent") {
            $toplinkspercentrigger = 1;
        }
    }

    if ($toplinkspercentrigger == 1) {
        $toplinkspercent = $toplinks;

        $dbconn =& pnDBGetConn(true);
        $pntable =& pnDBGetTables();

        $column = &$pntable['links_links_column'];
        $sql = "SELECT COUNT(*)
                FROM $pntable[links_links]
                WHERE $column[linkratingsummary]!=0";
        $result =& $dbconn->Execute($sql);

        list($totalratedlinks) = $result->fields;
        $toplinks = $toplinks / 100;
        $toplinks = $totalratedlinks * $toplinks;
        $toplinks = round($toplinks);
    }

    $weblinks = pnModAPIFunc('Web_Links', 'user', 'weblinkstoprated', array('toplinks' => $toplinks,
                                                                             'linkvotemin' => $linkvotemin));

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_READ)) {
        $access_read_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_READ)) {
        $access_read_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('toplinkspercentrigger', $toplinkspercentrigger);
    $pnRender->assign('toplinkspercent', $toplinkspercent);
    $pnRender->assign('totalratedlinks', $totalratedlinks);
    $pnRender->assign('toplinks', $toplinks);
    $pnRender->assign('linkvotemin', $linkvotemin);
    $pnRender->assign('weblinks', $weblinks);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign('access_read_cat', $access_read_cat);
    $pnRender->assign('access_read_link', $access_read_link);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_toprated.html');
}

function Web_Links_user_brokenlink($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    if (pnUserLoggedIn()) {
        $ratinguser = pnUserGetVar('uname');
    } else {
        $ratinguser = pnConfigGetVar("anonymous");
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('ratinguser', $ratinguser);
    $pnRender->assign('anonymous', pnConfigGetVar("anonymous"));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_brokenlink.html');
}

function Web_Links_user_modifylinkrequest($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    $link = pnModAPIFunc('Web_Links', 'user', 'link', array('lid' => $lid));

    if (pnUserLoggedIn()) {
        $ratinguser = pnUserGetVar('uname');
    } else {
        $ratinguser = pnConfigGetVar("anonymous");
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('blocknow', 0);
    $pnRender->assign('blockunregmodify', pnModGetVar('Web_Links', 'blockunregmodify'));
    $pnRender->assign('link', $link);
    $pnRender->assign('ratinguser', $ratinguser);
    $pnRender->assign('anonymous', pnConfigGetVar("anonymous"));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_modifylinkrequest.html');
}

function Web_Links_user_modifylinkrequests($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'POST');
    $cat = (int)FormUtil::getPassedValue('cat', isset($args['cat']) ? $args['cat'] : null, 'POST');
    $title = FormUtil::getPassedValue('title', isset($args['title']) ? $args['title'] : null, 'POST');
    $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');
    $description = FormUtil::getPassedValue('description', isset($args['description']) ? $args['description'] : null, 'POST');
    $modifysubmitter = FormUtil::getPassedValue('modifysubmitter', isset($args['modifysubmitter']) ? $args['modifysubmitter'] : null, 'POST');

    // Confirm authorisation code
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _BADAUTHKEY);
        pnRedirect(pnModURL('Web_Links', 'user', 'view'));
        return true;
    }

    // The API function is called.
    $return = pnModAPIFunc('Web_Links', 'user', 'modifylinkrequest', array('lid' => $lid,
                                                                           'cat' => $cat,
                                                                           'title' => $title,
                                                                           'url' => $url,
                                                                           'description' => $description,
                                                                           'modifysubmitter' => $modifysubmitter));

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('blocknow', 0);
    $pnRender->assign('blockunregmodify', pnModGetVar('Web_Links', 'blockunregmodify'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_modifylinkrequests.html');
}

function Web_Links_user_brokenlinks($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'POST');
    $modifysubmitter = FormUtil::getPassedValue('modifysubmitter', isset($args['modifysubmitter']) ? $args['modifysubmitter'] : null, 'POST');

    // Confirm authorisation code
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _BADAUTHKEY);
        pnRedirect(pnModURL('Web_Links', 'user', 'view'));
        return true;
    }

    // The API function is called.
    $return = pnModAPIFunc('Web_Links', 'user', 'brockenlink', array('lid' => $lid,
                                                                     'modifysubmitter' => $modifysubmitter));

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_brokenlinks.html');
}

function Web_Links_user_addlink()
{
    $yn = $ye = "";
    if (pnUserLoggedIn()) {
        $yn = pnUserGetVar('uname');
        $ye = pnUserGetVar('email');
    }

    if (SecurityUtil::checkPermission('Web_Links::', "::", ACCESS_COMMENT) || pnModGetVar('Web_Links', 'links_anonaddlinklock') == 0) {
        $addlink = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links', false);

    $pnRender->assign('addlink', $addlink);
    $pnRender->assign('yn', $yn);
    $pnRender->assign('ye', $ye);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_addlink.html');
}

function Web_Links_user_add($args)
{
    $newlink = FormUtil::getPassedValue('newlink', isset($args['newlink']) ? $args['newlink'] : null, 'POST');

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('Web_Links', 'user', 'view'));
    }

    $link = pnModAPIFunc('Web_Links', 'user', 'add', array('title' => $newlink['title'],
                                                            'url' => $newlink['url'],
                                                            'cat' => $newlink['cat'],
                                                           'description' => $newlink['description'],
                                                           'nname' => $newlink['nname'],
                                                           'email' => $newlink['email']));

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('submit', $link['submit']);
    $pnRender->assign('text', $link['text']);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_add.html');
}

function Web_Links_user_ratelink($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Argument check
    if ((!isset($lid) || !is_numeric($lid))) {
        pnSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    $ip = pnServerGetVar("REMOTE_HOST");
    if (empty($ip)) {
       $ip = pnServerGetVar("REMOTE_ADDR");
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('displaytitle', pnModAPIFunc('Web_Links', 'user', 'displaytitle', array('lid' => $lid)));
    $pnRender->assign('ip', $ip);

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_ratelink.html');
}

function Web_Links_user_addrating($args) // noch nicht fertig
{
    $ratinglid = (int)FormUtil::getPassedValue('ratinglid', isset($args['ratinglid']) ? $args['ratinglid'] : null, 'POST');
    $ratinguser = FormUtil::getPassedValue('ratinguser', isset($args['ratinguser']) ? $args['ratinguser'] : null, 'POST');
    $rating = FormUtil::getPassedValue('rating', isset($args['rating']) ? $args['rating'] : null, 'POST');
    $ratinghost_name = FormUtil::getPassedValue('ratinghost_name', isset($args['ratinghost_name']) ? $args['ratinghost_name'] : null, 'POST');
    $ratingcomments = FormUtil::getPassedValue('ratingcomments', isset($args['ratingcomments']) ? $args['ratingcomments'] : null, 'POST');

    $passtest = "yes";

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError (pnModURL('Web_Links', 'user', 'view'));
    }

    if (pnUserLoggedIn()) {
        $ratinguser = pnUserGetVar('uname');
    } else if ($ratinguser=="outside") {
        $ratinguser = "outside";
    } else {
        $ratinguser = pnConfigGetVar("anonymous");
    }

    /* Make sure only 1 anonymous from an IP in a single day. */
    $ip = pnServerGetVar("REMOTE_HOST");
    if (empty($ip)) {
        $ip = pnServerGetVar("REMOTE_ADDR");
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('mainlink', 1);
    $pnRender->assign('lid', $ratinglid);
    $pnRender->assign('title', pnModAPIFunc('Web_Links', 'user', 'displaytitle', array('lid' => $ratinglid)));

    /* Check if Rating is Null */
    if ($rating=="--") {
        $pnRender->assign('error', "nullerror");
        $pnRender->assign('passtest', "no");
    }

    /* Check if Link POSTER is voting (UNLESS Anonymous users allowed to post) */
    if ($ratinguser != pnConfigGetVar("anonymous") && $ratinguser != "outside") {
        $column = &$pntable['links_links_column'];
        $result =& $dbconn->Execute("SELECT $column[submitter]
                                FROM $pntable[links_links]
                                WHERE $column[lid]='".(int)DataUtil::formatForStore($ratinglid)."'");
        while(list($ratinguserDB)=$result->fields) {

            $result->MoveNext();
            if ($ratinguserDB==$ratinguser) {
                $pnRender->assign('error', "postervote");
                $pnRender->assign('passtest', "no");
            }
        }
    }


    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_addrating.html');









    /* Check if REG user is trying to vote twice. */
    if ($ratinguser != pnConfigGetVar("anonymous") && $ratinguser != "outside") {
        $column = &$pntable['links_votedata_column'];
        $result =& $dbconn->Execute("SELECT $column[ratinguser] FROM $pntable[links_votedata] WHERE $column[ratinglid]='".(int)DataUtil::formatForStore($ratinglid)."'");
        while(list($ratinguserDB)=$result->fields) {

            $result->MoveNext();
            if ($ratinguserDB==$ratinguser) {
                $error = "regflood";
                completevote($error);
                $passtest = "no";
            }
        }
    }
    /* Check if ANONYMOUS user is trying to vote more than once per day. */
    if ($ratinguser == pnConfigGetVar("anonymous")){
        $yesterdaytimestamp = (time()-(86400 * $anonwaitdays));
        $ytsDB = Date("Y-m-d H:i:s", $yesterdaytimestamp);
        $column = &$pntable['links_votedata_column'];
        $result =& $dbconn->Execute("SELECT count(*)
                                FROM $pntable[links_votedata]
                                WHERE $column[ratinglid]='".(int)DataUtil::formatForStore($ratinglid)."'
                                AND $column[ratinguser]='".pnConfigGetVar("anonymous")."'
                                AND $column[ratinghostname]='".DataUtil::formatForStore($ip)."'
                                AND TO_DAYS(NOW()) - TO_DAYS($column[ratingtimestamp]) < '".DataUtil::formatForStore($anonwaitdays)."'");
        list($anonvotecount) = $result->fields;
        if ($anonvotecount >= 1) {
            $error = "anonflood";
            completevote($error);
            $passtest = "no";
        }
    }
    /* Check if OUTSIDE user is trying to vote more than once per day. */
    if ($ratinguser == "outside"){
        $yesterdaytimestamp = (time()-(86400 * $outsidewaitdays));
        $ytsDB = date("Y-m-d H:i:s", $yesterdaytimestamp);
        $column = &$pntable['links_votedata_column'];
        $result =& $dbconn->Execute("SELECT count(*) FROM $pntable[links_votedata]
                                WHERE $column[ratinglid]='".(int)DataUtil::formatForStore($ratinglid)."'
                                AND $column[ratinguser]='outside'
                                AND $column[ratinghostname]='".DataUtil::formatForStore($ip)."'
                                AND TO_DAYS(NOW()) - TO_DAYS($column[ratingtimestamp]) < '".DataUtil::formatForStore($outsidewaitdays)."'");
        list($outsidevotecount) = $result->fields;
        if ($outsidevotecount >= 1) {
            $error = "outsideflood";
            completevote($error);
            $passtest = "no";
        }
    }
    /* Passed Tests */
    if ($passtest == "yes") {
        /* All is well.  Add to Line Item Rate to DB. */
        $nextid = $dbconn->GenId($pntable['links_votedata']);
        $column = &$pntable['links_votedata_column'];
        $dbconn->Execute("INSERT INTO $pntable[links_votedata]
                            ($column[ratingdbid], $column[ratinglid],
                             $column[ratinguser], $column[rating],
                             $column[ratinghostname], $column[ratingcomments],
                             $column[ratingtimestamp])
                             VALUES ($nextid,".(int)DataUtil::formatForStore($ratinglid).", '".DataUtil::formatForStore($ratinguser)."', '".DataUtil::formatForStore($rating)."',
                             '".DataUtil::formatForStore($ip)."', '".DataUtil::formatForStore($ratingcomments)."', now())");
        /* All is well.  Calculate Score & Add to Summary (for quick retrieval & sorting) to DB. */
        /* NOTE: If weight is modified, ALL links need to be refreshed with new weight. */
        /*   Running a SQL statement with your modded calc for ALL links will accomplish this. */
        $voteresult =& $dbconn->Execute("SELECT $column[rating], $column[ratinguser],
                                        $column[ratingcomments]
                                        FROM $pntable[links_votedata]
                                        WHERE $column[ratinglid] = '".(int)DataUtil::formatForStore($ratinglid)."'");
        $totalvotesDB = $voteresult->PO_RecordCount();
        $finalrating = calculateVote($voteresult, $totalvotesDB);
        $commresult =& $dbconn->Execute("SELECT $column[ratingcomments]
                                                                        FROM $pntable[links_votedata]
                                                                        WHERE $column[ratinglid] = '".DataUtil::formatForStore($ratinglid)."'
                                                                        AND $column[ratingcomments] != ''");
        $truecomments = $commresult->PO_RecordCount();
        $column = &$pntable['links_links_column'];
        $dbconn->Execute("UPDATE $pntable[links_links]
                        SET $column[linkratingsummary] = '".DataUtil::formatForStore($finalrating)."',
                            $column[totalvotes] = '".DataUtil::formatForStore($totalvotesDB)."',
                            $column[totalcomments]= '".DataUtil::formatForStore($truecomments)."'
                         WHERE $column[lid] = '".(int)DataUtil::formatForStore($ratinglid)."'");
        $error = "none";
        completevote($error);
    }
        if ($error == "none")
    {
    completevotefooter($ratinglid, $ttitle, $ratinguser);
    }
    CloseTable();
    include('footer.php');
}

function Web_Links_user_viewlinkcomments($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    $displaytitle = pnModAPIFunc('Web_Links', 'user', 'displaytitle', array('lid' => $lid));

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('displaytitle', $displaytitle);
    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign(pnModAPIFunc('Web_Links', 'user', 'totalcomments', array('lid' => $lid)));
    $pnRender->assign('useoutsidevoting', pnModGetVar('Web_Links', 'useoutsidevoting'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_viewlinkcomments.html');
}

function Web_Links_user_outsidelinksetup($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('sitename', pnConfigGetVar('sitename'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_outsidelinksetup.html');
}

function Web_Links_user_viewlinkeditorial($args)
{
    $lid = (int)FormUtil::getPassedValue('lid', isset($args['lid']) ? $args['lid'] : null, 'GET');

    // Security check
    if (!SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $displaytitle = pnModAPIFunc('Web_Links', 'user', 'displaytitle', array('lid' => $lid));

    // permission check
    if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_ADMIN)) {
        $userpermission = "admin";
    } else if (SecurityUtil::checkPermission('Web_Links::', '::', ACCESS_COMMENT)) {
        $userpermission = "comment";
    }

    if (SecurityUtil::checkPermission('Web_Links::Category', '::', ACCESS_COMMENT)) {
        $access_comment_cat = 1;
    }

    if (SecurityUtil::checkPermission('Web_Links::Link', '::$weblinks.lid', ACCESS_COMMENT)) {
        $access_comment_link = 1;
    }

    // Create output object
    $pnRender = pnRender::getInstance('Web_Links');

    $pnRender->assign('lid', $lid);
    $pnRender->assign('displaytitle', $displaytitle);
    $pnRender->assign('userpermission', $userpermission);
    $pnRender->assign('access_comment_cat', $access_comment_cat);
    $pnRender->assign('access_comment_link', $access_comment_link);
    $pnRender->assign(pnModAPIFunc('Web_Links', 'user', 'editorial', array('lid' => $lid)));
    $pnRender->assign('useoutsidevoting', pnModGetVar('Web_Links', 'useoutsidevoting'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('weblinks_user_viewlinkeditorial.html');
}
?>