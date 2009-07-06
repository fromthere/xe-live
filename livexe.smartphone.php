<?php
    /**
     * @class  livexeSmartphone
     * @author zero (skklove@gmail.com)
     * @brief  livexe 모듈의 SmartPhone class
     **/

    class livexeSPhone extends livexe {

        function procSmartPhone(&$oSmartPhone) {
            $page_navigation = Context::get('page_navigation');
            if($page_navigation) {
                if($page_navigation->cur_page > $page_navigation->first_page) $oSmartPhone->setPrevUrl(getUrl('page',$page_navigation->cur_page-1));
                if($page_navigation->cur_page < $page_navigation->last_page) $oSmartPhone->setNextUrl(getUrl('page',$page_navigation->cur_page+1));
            }
            $oTemplate = new TemplateHandler();
            $content = $oTemplate->compile($this->module_path.'tpl', 'smartphone');
            $oSmartPhone->setContent($content);
        }
    }
?>
