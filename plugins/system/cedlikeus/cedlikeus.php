<?php
/**
 * @package     CedLikeUs
 * @subpackage  com_cedlikeus
 * http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL 3.0</license>
 * @copyright   Copyright (C) 2013-2016 galaxiis.com All rights reserved.
 * @license     The author and holder of the copyright of the software is Cédric Walter. The licensor and as such issuer of the license and bearer of the
 *              worldwide exclusive usage rights including the rights to reproduce, distribute and make the software available to the public
 *              in any form is Galaxiis.com
 *              see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


jimport('joomla.plugin.plugin');

class plgSystemCedLikeUs extends JPlugin
{

    const CACHE_DIR = "/cache/cedlikeus";

    function plgSystemCedLikeUs(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onBeforeRender()
    {
        //Do not run in admin area and non HTML  (rss, json, error)
        $app = JFactory::getApplication();
        if ($app->isAdmin() || JFactory::getDocument()->getType() !== 'html')
        {
            return true;
        }
        $document = JFactory::getDocument();
        $docType = $document->getType();
        if ($docType == 'html') {

            $style = $this->params->get('style', 'red');
            $document->addStyleSheet(JURI::root(true) . "/media/plg_system_cedlikeus/css/$style.css?v=1.3.1");

            $counterFont = $this->params->get('counterFont', 'Roboto+Condensed:700');
            $document->addStyleSheet('//fonts.googleapis.com/css?family=' . $counterFont);

            JHtml::_('jquery.framework');

            $storageType = $this->params->get('storage-type', 'cookie');

            $storage = 'cedlikeus' . md5(JURI::root());
            if ($storageType == 'html5') {
                $this->addHtml5Handler($storage);
            } else {
                $this->addJavascriptHandler($storage);
            }
        }
    }

    public function onAfterRender()
    {
        $this->loadLanguage();
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }

        $document = JFactory::getDocument();
        $docType = $document->getType();
        if ($docType == 'html') {
            $facebookAccount = $this->params->get('facebook-account', 'Galaxiiscom');

            $html = array();
            $html[] = '<div class="cedlikeus cedlikeusfacebook" style="">';
            $html[] = '<div class="h1">' . JText::_('PLG_SYSTEM_CEDLIKEUS_KEEP') . '</div>';
            $html[] = '<div class="h2">' . JText::_('PLG_SYSTEM_CEDLIKEUS_LIKE') . '</div>';
            $html[] = '<div class="close icon-close">' . JText::_('PLG_SYSTEM_CEDLIKEUS_CLOSE') . '</div>';
            $html[] = '<div class="cedlikeus__fb">';
            $html[] = '<iframe scrolling="no" frameborder="0"
        allowtransparency="true"
        style="border:none;
        overflow:hidden;
        width:85px;
        height:20px;
        vertical-align:middle;"
	src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2F' . $facebookAccount . '&amp;send=&amp;layout=button_count&amp;width=85&amp;show_faces=1&amp;action=like&amp;locale=en_US&amp;colorscheme=light&amp;font&amp;height=20"></iframe></div>';
            $html[] = '</div>';

            $body = JFactory::getApplication()->getBody();
            $body = str_replace('</body>', implode('', $html) . '</body>', $body);
            JFactory::getApplication()->setBody($body);
        }
    }

    /**
     * @param $storage
     * @internal param $document
     */
    private function addJavascriptHandler($storage)
    {
        $likingExpiration = $this->params->get('expiration-liking', '365');
        $remindExpiration = $this->params->get('remind-not-liking', '2');

        $cacheId = "js-" . md5($likingExpiration . $remindExpiration . $storage) . ".js";
        if (!$this->cacheEntryExist($cacheId)) {
            $content = "
            jQuery(document).ready(function(){
                function setCedLikeUsCookie(cname, cvalue, exdays) {
                    var d = new Date();
                    d.setTime(d.getTime() + (exdays*24*60*60*1000));
                    var expires = 'expires='+d.toUTCString();
                    document.cookie = cname + '=' + cvalue +'; ' + expires;
                }

                function getCedLikeUsCookie(cname) {
                    var name = cname + '=';
                    var ca = document.cookie.split(';');
                    for(var i=0; i<ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0)==' ') c = c.substring(1);
                        if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
                    }
                    return '';
                }

                if(getCedLikeUsCookie('$storage')){
                    jQuery('.cedlikeus').remove();
                } else {
                    jQuery('.cedlikeus').show();

                    jQuery('html').click(function() {
                        jQuery('.cedlikeus').remove();

                        if(!getCedLikeUsCookie('$storage')){
                          setCedLikeUsCookie('$storage', 'any', $likingExpiration);
                        }
                    });

                    jQuery('.icon-close').click(function(){
                        jQuery('.cedlikeus').remove();

                         if(!getCedLikeUsCookie('$storage')){
                          setCedLikeUsCookie('$storage', 'any', $remindExpiration);
                        }
                    })
                }
            });";
            $this->writeContentToCache($cacheId, $content);
        }

        $document = JFactory::getDocument();
        $document->addScript(JUri::root(true) . "/cache/cedlikeus/" . $cacheId);
    }

    /**
     * @param $storage
     * @internal param $document
     */
    private function addHtml5Handler($storage)
    {
        $cacheId = "html5-" . md5($storage) . ".js";
        if (!$this->cacheEntryExist($cacheId)) {
            $content = "
                        jQuery(document).ready(function(){
                            if(typeof(Storage) !== 'undefined') {
                                //localStorage.removeItem('$storage');
                                if(localStorage.$storage){
                                  jQuery('.cedlikeus').remove();
                                } else {
                                    jQuery('.cedlikeus').show();

                                    jQuery('html').click(function() {
                                        jQuery('.cedlikeus').remove();

                                        if(!localStorage.$storage){
                                          localStorage.$storage = 1;
                                        }
                                    });

                                    jQuery('.icon-close').click(function(){
                                        jQuery('.cedlikeus').remove();

                                        if(!localStorage.$storage){
                                          localStorage.$storage = 1;
                                        }
                                    })
                                }
                            }
                        });";
            $this->writeContentToCache($cacheId, $content);
        }
        $document = JFactory::getDocument();
        $document->addScript(JUri::root() . "/cache/cedlikeus/" . $cacheId);
    }

    /**
     * @param $cacheId
     */
    private function writeContentToCache($cacheId, $content)
    {
        JFile::write(JPATH_ROOT . self::CACHE_DIR . "/" . $cacheId, $content);
    }

    /**
     * @param $cacheId
     * @return string
     */
    private function cacheEntryExist($cacheId)
    {
        $exist = JFolder::exists(JPATH_ROOT . self::CACHE_DIR);

        // joomla cache do not provide a way to access cache entry file name, maybe a subclass of file storage
        if (!$exist) {
            JFolder::create(JPATH_ROOT . self::CACHE_DIR);
        }

        $file = JPATH_ROOT . self::CACHE_DIR . "/" . $cacheId;

        return JFile::exists($file);
    }


}
