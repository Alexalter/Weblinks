<?php

/**
 * Zikula Application Framework
 *
 * Weblinks
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */
use \Weblinks_Entity_Link as Link;

class Weblinks_Api_Admin extends Zikula_AbstractApi
{

    /**
     * get available admin panel links
     */
    public function getlinks()
    {
        $links = array();

        if (SecurityUtil::checkPermission('Weblinks::', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'admin', 'view'),
                'text' => $this->__('Overview'),
                'class' => 'z-icon-es-view');
        }
        if (SecurityUtil::checkPermission('Weblinks::Category', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'admin', 'catview'),
                'text' => $this->__('Categories administration'),
                'class' => 'z-icon-es-cubes');
        }
        if (SecurityUtil::checkPermission('Weblinks::Link', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'admin', 'linkview'),
                'text' => $this->__('Links administration'),
                'class' => 'z-icon-es-view',
                'links' => array(
                    array('url' => ModUtil::url('Weblinks', 'admin', 'listbrokenlinks'),
                        'text' => $this->__('Broken links')),
                    array('url' => ModUtil::url('Weblinks', 'admin', 'listmodrequests'),
                        'text' => $this->__('Modification requests')),
                ));
        }
        if (SecurityUtil::checkPermission('Weblinks::', '::', ACCESS_ADMIN)) {
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'admin', 'getconfig'),
                'text' => $this->__('Settings'),
                'class' => 'z-icon-es-config');
        }
        if (SecurityUtil::checkPermission('Weblinks::Link', '::', ACCESS_EDIT)) {
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'admin', 'help'),
                'text' => $this->__('Help'),
                'class' => 'z-icon-es-help');
            $links[] = array(
                'url' => ModUtil::url('Weblinks', 'user', 'view'),
                'text' => $this->__('User Link-Index'),
                'class' => 'z-icon-es-url');
        }
        return $links;
    }

    /**
     * add/modify a category
     */
    public function editcategory($category)
    {
        // Argument check
        if (!isset($category['parent_id']) || !is_numeric($category['parent_id']) || !isset($category['title'])) {
            return LogUtil::registerArgsError();
        }

        // Security check
        if (!SecurityUtil::checkPermission('Weblinks::Category', "::", ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        if ($this->entityManager->getRepository('Weblinks_Entity_Category')->exists($category)) {
            return LogUtil::registerError($this->__('ERROR: The category title exists on this level.'));
        }

        if (isset($category['cat_id'])) {
            $catEntity = $this->entityManager->find('Weblinks_Entity_Category', $category['cat_id']);
        } else {
            $catEntity = new Weblinks_Entity_Category();
        }
        
        try {
            $catEntity->merge($category);
            $this->entityManager->persist($catEntity);
            $this->entityManager->flush();
        } catch (Zikula_Exception $e) {
            return LogUtil::registerError($this->__("ERROR: The category was not created: " . $e->getMessage()));
        }

        return true;
    }

    /**
     * delete a category
     */
    public function delcategory($args)
    {
        // Argument check
        if (!isset($args['cid']) || !is_numeric($args['cid'])) {
            return LogUtil::registerArgsError();
        }

        // Security check
        if (!SecurityUtil::checkPermission('Weblinks::Category', "::", ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }
        
        $cat = $this->entityManager->find('Weblinks_Entity_Category', $args['cid']);
        $this->recursiveCategoryRemoval($cat);
        
        return true;
    }
    
    /**
     * Private function to handle recursive category removal
     * @param Weblinks_Entity_Category $cat 
     */
    private function recursiveCategoryRemoval(Weblinks_Entity_Category $cat)
    {
        $cid = $cat->getCat_id();
        // is category a parent?
        $children = $this->entityManager->getRepository('Weblinks_Entity_Category')->findBy(array('parent_id' => $cid));
        if (isset($children)) {
            foreach ($children as $child) {
                $this->recursiveCategoryRemoval($child);
            }
        }
        // remove the links in the category
        $links = $this->entityManager->getRepository('Weblinks_Entity_Link')->findBy(array('category' => $cid));
        if (isset($links)) {
            foreach ($links as $link) {
                $this->entityManager->remove($link);
            }
        }
        // remove the category
        $this->entityManager->remove($cat);
        $this->entityManager->flush();
    }

    /**
     * edit/create a link
     */
    public function editlink($link)
    {
        // Argument check
        if (!isset($link['cat_id']) || !isset($link['title']) || !isset($link['url'])) {
            return LogUtil::registerArgsError();
        }
        unset($link['new']);

        // Security check
        $accessLevel = isset($link['lid']) ? ACCESS_EDIT : ACCESS_ADD;
        if (!SecurityUtil::checkPermission('Weblinks::Link', "::", $accessLevel)) {
            return LogUtil::registerPermissionError();
        }

        if (isset($link['lid'])) {
            $linkEntity = $this->entityManager->find('Weblinks_Entity_Link', $link['lid']);
            // TODO: check here for Link perms?
        } else {
            $linkEntity = new Weblinks_Entity_Link();
        }

        try {
            $linkEntity->merge($link);
            $linkEntity->setStatus(Link::ACTIVE);
            $linkEntity->setCategory($this->entityManager->find('Weblinks_Entity_Category', $link['cat_id']));
            $this->entityManager->persist($linkEntity);
            $this->entityManager->flush();
        } catch (Zikula_Exception $e) {
            return LogUtil::registerError($this->__("ERROR: The link was not created: " . $e->getMessage()));
        }

        return $linkEntity->getLid();
    }

    /**
     * check links
     */
    public function validateLinksByCategory($args)
    {
        // Argument check
        if (!isset($args['cid']) || !is_numeric($args['cid'])) {
            return LogUtil::registerArgsError();
        }

        if ((int)$args['cid'] == 0) {
            // assume ADMIN access
            $checkcatlinks = $this->entityManager->getRepository('Weblinks_Entity_Link')->getLinks();
        } else {
            $checkcatlinks = Weblinks_Util::checkCategoryPermissions($this->entityManager->getRepository('Weblinks_Entity_Link')->getLinks(Link::ACTIVE, ">=", $args['cid']), ACCESS_EDIT);
        }


        // put items into result array.
        $links = array();
        foreach ($checkcatlinks as $link) {
            
            // TODO: check Link perms here?
            $links[] = array('lid' => $link['lid'],
                'title' => $link['title'],
                'url' => $link['url'],
                'fp' => Weblinks_Util::validateLink($link));
        }

        // return array
        return $links;
    }
    
    /**
     * get links with modrequest
     */
    public function modrequests()
    {
        // get the links from the db
        $modifiedLinks = Weblinks_Util::checkCategoryPermissions($this->entityManager->getRepository('Weblinks_Entity_Link')->findBy(array('status' => Link::ACTIVE_MODIFIED)), ACCESS_EDIT);

        // put items into result array.
        $modrequests = array();
        foreach ($modifiedLinks as $link) {
            
            // TODO: should process for Link permissions here

            if ($link->getModifysubmitter() != System::getVar('anonymous')) {
                // ewww. forced to use DBUtil...
                $email = DBUtil::selectObjectByID('users', $link->getModifysubmitter(), 'uname');
            }
            $modifiedContent = $link->getModifiedContent();
            $modifiedCategory = $this->entityManager->find('Weblinks_Entity_Category', $modifiedContent['cat_id']);

            $modrequests[] = array(
                'lid' => $link->getLid(),
                'title' => $modifiedContent['title'],
                'url' => $modifiedContent['url'],
                'description' => $modifiedContent['description'],
                'cid' => $modifiedCategory->getCat_id(),
                'cidtitle' => $modifiedCategory->getTitle(),
                'origtitle' => $link->getTitle(),
                'origurl' => $link->getUrl(),
                'origdescription' => $link->getDescription(),
                'origcid' => $link->getCat_id(),
                'origcidtitle' => $link->getCategory()->getTitle(),
                'submitter' => $link->getModifysubmitter(),
                'submitteremail' => $email['email'],
                'owner' => $link->getName(),
                'owneremail' => $link->getEmail());
        }

        // return array
        return $modrequests;
    }

}