<?php
    /**
     * @class  livexe
     * @author zero (zero@nzeo.com)
     * @brief  livexe 모듈의 high class
     **/

    class livexe extends ModuleObject {

        var $search_option = array('title','content','title_content','homepage','tag'); ///< 검색 옵션

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }

    }
?>
