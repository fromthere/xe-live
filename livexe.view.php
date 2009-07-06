<?php
    /**
     * @class  livexeView
     * @author zero (zero@nzeo.com)
     * @brief  livexe 모듈의 View class
     **/

    class livexeView extends livexe {

        /**
         * @brief 초기화
         **/
        function init() {
            /**
             * 기본 모듈 정보들 설정 (list_count, page_count는 게시판 모듈 전용 정보이고 기본 값에 대한 처리를 함)
             **/
            if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
            if($this->module_info->page_count) $this->page_count = $this->module_info->page_count;
            if(!$this->module_info->popular_tag_period) $this->module_info->popular_tag_period = 30;

            /**
             * 스킨 경로를 미리 template_path 라는 변수로 설정함
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path) || !$this->module_info->skin) {
                $this->module_info->skin = 'xe_default';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);

            /** 
             * 전반적으로 사용되는 javascript, JS 필터 추가
             **/
            Context::addJsFile($this->module_path.'tpl/js/livexe.js');
            if($this->grant->insert_rss) {
                Context::addJsFilter($this->module_path.'tpl/filter', 'get_rss.xml');
                Context::addJsFilter($this->module_path.'tpl/filter', 'insert_rss.xml');
            }
        }

        /**
         * @brief 목록 및 선택된 글 출력
         **/
        function dispLivexeContent () {
            $oLivexeController = &getController('livexe');

            // 통계 추출
            $status_args->module_srl = $this->module_info->module_srl;
            $output = executeQuery('livexe.getRSSCount', $status_args);
            $status->rss = $output->data->count;
            $output = executeQuery('livexe.getDocumentCount', $status_args);
            $status->document = $output->data->count;
            $output = executeQuery('livexe.getTagCount', $status_args);
            $status->tag = $output->data->count;
            Context::set('status', $status);

            /**
             * 인기 태그 추출
             * 캐시 파일을 이용해서 5분마다 캐싱
             **/
            $tags = $oLivexeController->makeTagCache($this->module_srl, 15, $this->module_info->popular_tag_period);
            Context::set('tags', $tags);

            // 최고 인기 태그 탭 생성
            $popular_tab = array();
            if(count($tags)) {
                foreach($tags as $key => $val) {
                    $_v[$val->tag] = $val->count;
                }
                arsort($_v);
                if(count($_v)) {
                    $idx = 0 ;
                    foreach($_v as $tag => $count) {
                        unset($p_args);
                        $p_args->module_srl = $this->module_srl;
                        $p_args->tag = $tag;
                        $p_args->sort_index = 'documents.regdate';
                        $p_args->order_type = 'desc';
                        $p_args->page = 1;
                        $p_args->list_count = 5;
                        $output = executeQueryArray('livexe.getLiveDocumentList', $p_args);
                        $popular_tab[$tag] = $output->data;
                        if($output->data) {
                            foreach($output->data as $obj) {
                                if($obj->thumbnail && !$popular_tab[$tag][0]) {
                                    $popular_tab[$tag][0] = $obj;
                                    break;
                                }
                            }
                        }
                        $idx++;
                        if($idx > 3) break;
                    }
                }
            }
            Context::set('popular_tab', $popular_tab);

            /**
             * 썸네일 추출
             **/
            $thumbnail_args->module_srl = $this->module_srl;
            $thumbnail_args->order_type = 'desc';
            $output = executeQueryArray('livexe.getThumbnails',$thumbnail_args);
            Context::set('thumbnails', $output->data);

            
            /**
             * 최근 등록된 RSS 추출
             **/
            $rss_args->module_srl = $this->module_srl;
            $rss_args->sort_index = 'regdate';
            $rss_args->order_type = 'desc';
            $rss_args->list_count = 30;
            $output = executeQueryArray('livexe.getRssList', $rss_args);
            Context::set('latest_rss_list', $output->data);
            Context::set('latest_rss_count', $output->total_count);

            /**
             * 로그인 사용자의 RSS 추출
             **/
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $rss_args->member_srl = $logged_info->member_srl;
                $rss_args->list_count = 999999999;
                $output = executeQueryArray('livexe.getRssList', $rss_args);
                Context::set('own_rss_list', $output->data);
            }

            /** 
             * 사이트 내 검색
             **/
            $search_keyword = Context::get('search_keyword');
            $search_target = Context::get('search_target');

            /**
             * 목록이 노출될때 같이 나오는 검색 옵션을 정리하여 스킨에서 쓸 수 있도록 context set
             * 확장변수에서 검색 선택된 항목이 있으면 역시 추가
             **/
            // 템플릿에서 사용할 검색옵션 세팅 (검색옵션 key값은 미리 선언되어 있는데 이에 대한 언어별 변경을 함)
            foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);
            Context::set('search_option', $search_option);

            // 목록을 구하기 위한 대상 모듈/ 페이지 수/ 목록 수/ 페이지 목록 수에 대한 옵션 설정
            $args->module_srl = $this->module_srl; 
            $args->page = Context::get('page');
            $args->list_count = $this->list_count; 
            $args->page_count = $this->page_count; 
            $args->sort_index = 'documents.regdate';
            $args->order_type = 'desc';

            switch($search_target) {
                case 'tag' :
                        $args->tag = $search_keyword;
                    break;
                case 'title' :
                        $args->title = $search_keyword;
                    break;
                case 'content' :
                        $args->content = $search_keyword;
                    break;
                case 'homepage' :
                        $args->homepage= $search_keyword;
                    break;
                case 'rss_srl' :
                        $rss_args->livexe_rss_srl = $search_keyword;
                        $rss_output = executeQuery('livexe.getRssUrl', $rss_args);
                        if($rss_output->data) {
                            Context::set('selected_site', $rss_output->data);
                            $args->rss_srl = $search_keyword;
                        }
                    break;
            }
            $output = executeQueryArray('livexe.getLiveDocumentList', $args);

            // 일반 글을 구해서 context set
            Context::set('document_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }

        /**
         * @brief 등록된 RSS 주소들의 item들을 crawling
         * cron을 이용하지 않고 웹으로 crawling 하는 페이지
         **/
        function dispLivexeCrawler() {
            $oLivexeController = &getController('livexe');

            $status = $oLivexeController->doCrawl();

            $status_args->module_srl = $this->module_info->module_srl;
            $output = executeQuery('livexe.getRSSCount', $status_args);
            $status['total_rss'] = $output->data->count;

            Context::set('status', $status);

            $this->setTemplatePath($this->module_path."tpl");
            $this->setTemplateFile("crawler");
        }

    }
?>
