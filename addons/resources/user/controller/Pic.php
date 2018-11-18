<?php

namespace addons\resources\user\controller;

class Pic extends \web\user\controller\AddonUserBase {

    private $thumb_width = 200;
    private $thumb_height = 150;
    private $md_folder = '800x600';
    private $sm_folder = '200x150';

    /**
     * 图片资源文件信息
     */
    public function index() {
        $folder = $this->_get('folder');
        $checktype = $this->_get('checktype');
        $this->assign('folder', $folder);
        $this->assign('checktype', $checktype);
        $res_type = 1;
        $this->assign('res_type', $res_type);
        $is_cut = 0;
        $this->assign('is_cut', $is_cut);
        return $this->fetch('pic');
    }

    function loadList() {
        $m = new \addons\resources\model\Resources();
        $filter = '';
        $folder = $this->_get('folder');
        if ($folder != '')
            $filter = 'folder=\'' . $folder . '\'';
        $total = $m->getTotal($filter);
        $pageIndex = $this->_get('page');
        $pageSize = $this->_get('pageSize');
        if (empty($pageIndex))
            $pageIndex = 0;
        if (empty($pageSize))
            $pageSize = 1;
        $data = $m->getDataList($pageIndex, $pageSize, $filter);
        return $this->toDataGrid($total, $data);
    }

    public function upload() {
        $is_cut = $this->_post('is_cut');
        $folder = $this->_post('folder');
        $res_type = 1; //类型(1=图片,2=语音,3=视频)                
        $savePath = UPLOADFOLDER . $folder;
        $originalPath = '';
        if (!empty($folder)) {
            if ($is_cut) {
                $originalPath = $savePath . '/' . $this->md_folder . '/';
            } else
                $savePath = $savePath . '/';
        }
        if ($is_cut)
            $uploadPath = substr($originalPath, 1);
        else
            $uploadPath = substr($savePath, 1);
        $files = $this->request->file();
        foreach ($files as $file) {
            if ($is_cut)
                $ret = $file->move($originalPath, '');
            else
                $ret = $file->move($savePath);
            $json = array('success' => false, 'message' => '上传失败！');
            if (!$ret) {// 上传错误提示错误信息                            
                $json = array('success' => false, 'message' => $file->getError());
            } else {// 上传成功                  
                $fileName = $ret->getInfo()['name'];
                $filePath = $uploadPath . str_replace('\\', '/', $ret->getSaveName());
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
                $errmsg = '';
                if ($is_cut) {
                    try {
                        $this->makeThumb($localPath, $thumbPath, $fileName);
                    } catch (Exception $e) {
                        unlink($localPath);
                        $this->failData('生成缩略图失败,请重新上传!');
                    }
                }
                if (empty($errmsg)) {
                    $m = new \addons\resources\model\Resources();
                    $data = array();
                    $data['res_type'] = $res_type;
                    $data['file_name'] = $fileName;
                    $data['file_size'] = $this->formatBytes($ret->getSize());
                    $data['new_name'] = basename($ret->getSaveName());
                    $data['file_path'] = $filePath;
                    $data['folder'] = $folder;
                    $data['update_time'] = NOW_DATETIME;
                    $id = $m->add($data);
                    if ($id > -1) {
                        $d = array('id' => $id, 'name' => $fileName, 'path' => $filePath);
                        $json = array('success' => true, 'data' => $d);
                    } else {
                        unlink($localPath);
                    }
                } else {
                    unlink($localPath);
                    $json = array('success' => false, 'message' => $errmsg);
                }
            }
        }
        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }


    public function del() {
        $id = $this->_get('id');
        $m = new \addons\resources\model\Resources();
        $data = $m->getDetail($id);
        $ret = -1;
        if ($data) {
            $localPath = $_SERVER['DOCUMENT_ROOT'] . str_replace(array('./'), array('/'), $data['file_path']);
            $ret = $m->deleteData($id);
            if ($ret > 0)
                unlink($localPath);
        }
        if ($ret > 0) {
            $json = array('success' => true, 'message' => '删除成功');
        } else
            $json = array('success' => false, 'message' => '删除失败');
        return $json;
    }

    private function formatBytes($size) {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++)
            $size /= 1024;
        return round($size, 2) . $units[$i];
    }

    /**
     * 检查目录是否可写
     * @param  string   $path    目录
     * @return boolean
     */
    protected function checkPath($path) {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            $this->error = "目录 {$path} 创建失败！";
            return false;
        }
    }

    /**
     * 创建缩略图并保存
     * @param type $localPath   原文件绝对路径
     * @param type $folderPath  文件夹路径
     * @param type $fileName    图片名称
     * @return boolean
     */
    private function makeThumb($localPath, $folderPath, $fileName) {
        //打开上传的文件
        $image = \think\Image::open($localPath);
        // 检测(创建)目录
        if (false === $this->checkPath($folderPath)) {
            return false;
        }
        $thumbName = $folderPath . $fileName;
        $res = $image->thumb($this->thumb_width, $this->thumb_height)->save($thumbName);
        return $res;
    }

}
