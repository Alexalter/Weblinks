<?php

/**
 * Zikula Application Framework
 *
 * Weblinks
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */
function smarty_function_linkbottommenu($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('Weblinks');

    if (empty($params['cid']) || empty($params['lid'])) {
        return LogUtil::registerArgsError();
    }

    $linkbottommenu = "";

    if (SecurityUtil::checkPermission('Weblinks::', "::", ACCESS_EDIT)) {
        $linkbottommenu .= "<a href=\"" . DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'admin', 'modlink', array('lid' => (int)$params['lid']))) . "\" class=\"wl-editlink\" title=\"" . DataUtil::formatForDisplay(__('Edit this link', $dom)) . "\">" . DataUtil::formatForDisplay(__('Edit this link', $dom)) . "</a>";
    } else if (ModUtil::getVar('Weblinks', 'blockunregmodify') == 1 || SecurityUtil::checkPermission('Weblinks::Category', "::$params[cid]", ACCESS_COMMENT)) {
        $linkbottommenu .= "<a href=\"" . DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'user', 'modifylinkrequest', array('lid' => (int)$params['lid']))) . "\" class=\"wl-editlink\" title=\"" . DataUtil::formatForDisplay(__('Modify', $dom)) . "\">" . DataUtil::formatForDisplay(__('Modify', $dom)) . "</a>";
    }

    if (ModUtil::getVar('Weblinks', 'unregbroken') == 1 || SecurityUtil::checkPermission('Weblinks::Category', "::$params[cid]", ACCESS_COMMENT)) {
        $linkbottommenu .= "<a href=\"" . DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'user', 'brokenlink', array('lid' => (int)$params['lid']))) . "\" class=\"wl-brokenlink\" title=\"" . DataUtil::formatForDisplay(__('Report broken link', $dom)) . "\">" . DataUtil::formatForDisplay(__('Report broken link', $dom)) . "</a>";
    }

    if (!$params['details']) {
        $linkbottommenu .= "<a href=\"" . DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'user', 'viewlinkdetails', array('lid' => (int)$params['lid']))) . "\" class=\"wl-readmore\" title=\"" . DataUtil::formatForDisplay(__('Details', $dom)) . "\">" . DataUtil::formatForDisplay(__('Details', $dom)) . "</a>";

        // set default
//        $ezcommentscounter = "";
//
//        if (ModUtil::available('EZComments') && ModUtil::isHooked('EZComments', 'Weblinks')) {
//            $items = ModUtil::apiFunc('EZComments', 'user', 'getall', array('mod' => 'Weblinks', 'status' => 0, 'objectid' => $params['lid']));
//            $ezcommentscounter = count($items);
//
//            $linkbottommenu .= "<a href=\"".DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'user', 'viewlinkdetails', array('lid' => (int)$params['lid'])))."\" class=\"wl-commentlink\" title=\"".DataUtil::formatForDisplay(__('Add a comment', $dom))."\">";
//            if ($ezcommentscounter == "0") $linkbottommenu .= DataUtil::formatForDisplay(__('Add a comment', $dom))."</a>";
//            elseif ($ezcommentscounter == "1") $linkbottommenu .= "1 ".DataUtil::formatForDisplay(__('Comment', $dom))."</a>";
//            else $linkbottommenu .= "$ezcommentscounter ".DataUtil::formatForDisplay(__('Comments', $dom))."</a>";
//        }
    }

    if ($params['details']) {
        if (ModUtil::getVar('Weblinks', 'targetblank') == 1) {
            $target = " target=\"_blank\"";
        }
        $linkbottommenu .= "<a href=\"" . DataUtil::formatForDisplay(ModUtil::url('Weblinks', 'user', 'visit', array('lid' => (int)$params['lid']))) . "\" class=\"wl-visitlink\"" . $target . " title=\"" . DataUtil::formatForDisplay(__('Visit this web site', $dom)) . "\">" . DataUtil::formatForDisplay(__('Visit this web site', $dom)) . "</a>";
    }

    return $linkbottommenu;
}