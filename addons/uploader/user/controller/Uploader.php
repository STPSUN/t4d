<?php

namespace addons\uploader\user\controller;

/**
 * 上传文件
 * 
 */
class Uploader extends \web\user\controller\AddonUserBase {

    /**
     * 上传到cdn
     */
    public function uploadCdn() {
        $folder = $this->_post('folder');
        $rootPath = $this->_post('root');
        if (empty($rootPath))
            $rootPath = UPLOADFOLDER;
        if (!empty($folder))
            $savePath = $folder;
        $sync_weixin = boolval($this->_post('sync_weixin'));
        if (!empty($savePath))
            $savePath .= '/';
        $setting = config('UPLOAD_FILE_QINIU');
        $config = array('rootPath' => $rootPath, 'savePath' => $savePath, 'saveName' => array('date', 'YmdHis'),
            'maxSize' => 1024000, 'exts' => array('jpg', 'gif', 'png', 'jpeg'));
        $upload = new \addons\uploader\util\Upload($config, $setting['driver'], $setting['driverConfig']);
        $info = $upload->upload();
//        七牛返回结果
//        array(1) (
//            [file] => array(10) (
//              [name] => (string) 2017-10-09/59dad59983988.png
//              [type] => (string) image/png
//              [size] => (int) 111800
//              [key] => (string) file
//              [ext] => (string) png
//              [md5] => (string) 7196bfe7c190201de213f784716b99ed
//              [sha1] => (string) 4d0b3f1368bb2670bdcbb6561382002d24c07914
//              [savename] => (string) 59dad59983988.png
//              [savepath] => (string) 2017-10-09/
//              [url] => (string) http://oxa03xm2q.bkt.clouddn.com/2017-10-09%2F59dad59983988.png
//            )
//          )
        $json = '{"success":false,"message":"上传失败！"}';
        if (!$info) {// 上传错误提示错误信息            
            $json = '{"success":false,"message":"' . $upload->getError() . '"}';
        } else {// 上传成功
            $file = $info['file'];
            $fileName = $file['name'];
            $filePath = $file['savepath'] . $file['savename'];
            $errmsg = '';
            $media_id = '';
            $media_url = '';
            if ($sync_weixin) {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $rootPath . $filePath;
                $api = new \mp\API\Material();
                $res = $api->uploadMedia($this->getAccessToken(), 'image', $localPath);
                if (checkIsSuc($res)) {
                    $data = array();
                    $data['name'] = $fileName;
                    $data['media_id'] = $res['media_id'];
                    $data['url'] = $res['url'];
                    $media_id = $res['media_id'];
                    $media_url = $res['url'];
                    $data['update_time'] = NOW_TIME;
                    $m = new \addons\weixin\model\material\ImageModel();
                    $m->add($data);
                } else {
                    unlink($localPath);
                    $errmsg = $res['errmsg'];
                }
            }
            if (empty($errmsg)) {
                $d = '{"name":"' . $fileName . '","path":"' . $filePath . '","media_id":"' . $media_id . '","media_url":"' . $media_url . '"}';
                $json = '{"success":true,"data":' . $d . '}';
            } else {
                $json = '{"success":false,"message":"' . $errmsg . '"}';
            }
        }
        echo $json;
        exit;
    }

    /**
     * 上传图片。
     */
    public function uploadPic() {
        $folder = $this->_post('folder');
        $rootPath = $this->_post('root');
        if (empty($rootPath))
            $rootPath = UPLOADFOLDER;
        $savePath = $folder;
        if (!empty($folder))
            $savePath = $savePath . '/';
        $config = array('rootPath' => $rootPath, 'savePath' => $savePath, 'saveName' => array('date', 'YmdHis'),
            'maxSize' => 1024000, 'exts' => array('jpg', 'gif', 'png', 'jpeg'));
        
        $driver = 'Local';
        $driverConfig = array();
        $upload = new \addons\uploader\util\Upload($config, $driver, $driverConfig); // 实例化上传类
        $uploadFolder = substr($rootPath, 1);
        $uploadPath = $uploadFolder . $savePath;
        $path = $_SERVER['DOCUMENT_ROOT'] . $uploadPath;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        // 上传文件 
        $info = $upload->upload();
        $json = '{"success":false,"message":"上传失败！"' . $path . '}';
        if (!$info) {// 上传错误提示错误信息            
            $json = '{"success":false,"message":"' . $upload->getError() . '|' . $path . '"}';
        } else {// 上传成功
            $file = $info['file'];
            $fileName = $file['name'];
            $filePath = $uploadFolder . $file['savepath'] . $file['savename'];
            $localPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
            $errmsg = '';
            if (empty($errmsg)) {
                $d = '{"name":"' . $fileName . '","path":"' . $filePath . '"}';
                $json = '{"success":true,"data":' . $d . '}';
            } else {
                unlink($localPath);
                $json = '{"success":false,"message":"' . $errmsg . '"}';
            }
        }
        echo $json;
        exit;
    }

    /**
     * 上传文件。
     */
    public function uploadFile() {
        $folder = $this->_post('folder');
        $rootPath = UPLOADFOLDER;
        $saveName = $this->_post('saveName');
        $exts = $this->_post('exts');
        if (!empty($folder))
            $savePath = $folder . '/';
        $config = array('rootPath' => $rootPath, 'savePath' => $savePath, 'replace' => true);
        if (isset($saveName)) {
            $config['autoSub'] = FALSE;
            $config['saveName'] = $saveName;
        }
        if (!empty($exts)) {
            $config['exts'] = explode(',', $exts);
        }
        $driver = 'Local';
        $driverConfig = array();
        $upload = new \addons\uploader\util\Upload($config, $driver, $driverConfig); // 实例化上传类
        if ($folder != 'cert') {
            $uploadFolder = substr($rootPath, 1);
            $uploadPath = $uploadFolder . $savePath;
            $path = $_SERVER['DOCUMENT_ROOT'] . $uploadPath;
        } else
            $path = $rootPath . $savePath;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        //上传文件
        $info = $upload->upload();
        $json = '{"success":false,"message":"上传失败！"}';
        if (!$info) {// 上传错误提示错误信息            
            $json = '{"success":false,"message":"' . $upload->getError() . '"}';
        } else {// 上传成功
            $file = $info['file'];
            $fileName = $file['name'];
            if ($folder != 'cert') {
                $filePath = $uploadFolder . $file['savepath'] . $file['savename'];
                // $localPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
            } else {
                $filePath = $file['savepath'] . $file['savename'];
            }
            $d = '{"name":"' . $fileName . '","path":"' . $filePath . '"}';
            $json = '{"success":true,"data":' . $d . '}';
        }
        echo $json;
        exit;
    }

}
