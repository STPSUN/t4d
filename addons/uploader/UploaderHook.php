<?php

namespace addons\uploader;

class UploaderHook extends \web\addons\controller\AddonHook {

    /**
     * 上传图片
     * @param type $data
     */
    public function uploadPIC($data) {
        if (!isset($data['sync_weixin']))
            $data['sync_weixin'] = 0;
        if (!isset($data['server']))
            $data['server'] = '';
        if (!isset($data['uploadError']))
            $data['uploadError'] = '';
        $this->assign('data', $data);
        $this->display('upload_pic');
    }

    /**
     * 上传文件
     * @param type $data
     */
    public function uploadFile($data) {
        if (!isset($data['uploadSuccess']))
            $data['uploadSuccess'] = '';
        if (!isset($data['server']))
            $data['server'] = '';
        if (!isset($data['uploadError']))
            $data['uploadError'] = '';
        $this->assign('data', $data);
        $this->display('upload_file');
    }

    /**
     * 上传cdn
     * @param type $data
     */
    public function uploadCDN($data) {
        if (!isset($data['sync_weixin']))
            $data['sync_weixin'] = 0;
        if (!isset($data['server']))
            $data['server'] = '';
        if (!isset($data['uploadError']))
            $data['uploadError'] = '';
        $this->assign('data', $data);
        $this->display('upload_cdn');
    }

}
