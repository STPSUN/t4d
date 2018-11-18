<?php

namespace addons\editor\user\controller;

/**
 * 编辑器
 */
class Editor extends \web\user\controller\AddonUserBase {

    public function uedit() {
        //导入设置
//        imageManagerActionName {String} [默认值："listimage"] //执行图片管理的action名称
//        imageManagerListPath {String} [默认值："/ueditor/php/upload/image/"] //指定要列出图片的目录
//        imageManagerListSize {String} [默认值：20] //每次列出文件数量
//        imageManagerUrlPrefix {String} [默认值：""] //图片访问路径前缀
//        imageManagerInsertAlign {String} [默认值："none"] //插入的图片浮动方式
//        imageManagerAllowFiles {Array}, //列出的文件类型
        $configFile = ADDONS_PATH . ADDON_NAME . '/config/ueditor.json';
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($configFile)), true);

        $action = htmlspecialchars($_GET['action']);
        switch ($action) {
            case 'config':
                $result = json_encode($CONFIG);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $config = array(
                    'pathFormat' => $CONFIG['imagePathFormat'],
                    'maxSize' => $CONFIG['imageMaxSize'],
                    'allowFiles' => $CONFIG['imageAllowFiles']
                );
                $fieldName = $CONFIG['imageFieldName'];
                $result = $this->uploadFile($fieldName, $config);
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $config = array(
                    'pathFormat' => $CONFIG['scrawlPathFormat'],
                    'maxSize' => $CONFIG['scrawlMaxSize'],
                    'allowFiles' => $CONFIG['scrawlAllowFiles'],
                    'oriName' => 'scrawl.png'
                );
                $fieldName = $CONFIG['scrawlFieldName'];
                $result = $this->uploadFile($fieldName, $config, 'base64');
                break;
            /* 上传视频 */
            case 'uploadvideo':
                $config = array(
                    'pathFormat' => $CONFIG['videoPathFormat'],
                    'maxSize' => $CONFIG['videoMaxSize'],
                    'allowFiles' => $CONFIG['videoAllowFiles']
                );
                $fieldName = $CONFIG['videoFieldName'];
                $result = $this->uploadFile($fieldName, $config);
                break;
            /* 上传文件 */
            case 'uploadfile':
                // default:
                $config = array(
                    'pathFormat' => $CONFIG['filePathFormat'],
                    'maxSize' => $CONFIG['fileMaxSize'],
                    'allowFiles' => $CONFIG['fileAllowFiles']
                );
                $fieldName = $CONFIG['fileFieldName'];
                $result = $this->uploadFile($fieldName, $config);
                break;
            /* 列出文件 */
            case 'listfile':
                $config = array(
                    'allowFiles' => $CONFIG['fileManagerAllowFiles'],
                    'listSize' => $CONFIG['fileManagerListSize'],
                    'path' => $CONFIG['fileManagerListPath'],
                );
                $result = $this->listFile($config);
                break;
            /* 列出图片 */
            case 'listimage':
                $config = array(
                    'allowFiles' => $CONFIG['imageManagerAllowFiles'],
                    'listSize' => $CONFIG['imageManagerListSize'],
                    'path' => $CONFIG['imageManagerListPath'],
                );
                $result = $this->listFile($config);
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $config = array(
                    'pathFormat' => $CONFIG['catcherPathFormat'],
                    'maxSize' => $CONFIG['catcherMaxSize'],
                    'allowFiles' => $CONFIG['catcherAllowFiles'],
                    'oriName' => 'remote.png'
                );
                $fieldName = $CONFIG['catcherFieldName'];
                $result = $this->saveRemote($fieldName, $config);
                break;
            default:
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ));
                break;
        }
        /* 输出结果 */
        if (isset($_GET['callback'])) {
            if (preg_match('/^[\w_]+$/', $_GET['callback'])) {
                echo htmlspecialchars($_GET['callback']) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }

    /**
     * 上传文件方法
     * @param type $fileField
     * @param type $config
     * @param type $type
     * @return type
     */
    private function uploadFile($fileField, $config, $type = 'upload') {
        $uploadFolder = substr(UPLOADFOLDER, 1);
        $rootPath = $uploadFolder;
        $config['pathFormat'] = $rootPath . $config['pathFormat'];
        $up = new \addons\editor\util\Uploader($fileField, $config, $type);
        $result = json_encode($up->getFileInfo());
        return $result;
    }

    /**
     * 获取文件列表。
     * @param type $config
     * @return type
     */
    private function listFile($config) {
        $allowFiles = substr(str_replace('.', '|', join('', $config['allowFiles'])), 1);
        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $config['listSize'];
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;
        /* 获取文件列表 */
        $uploadFolder = substr(UPLOADFOLDER, 1);
        $rootPath = $uploadFolder;
        $config['path'] = $rootPath . $config['path'];
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($config['path'], 0, 1) == '/' ? '' : '/') . $config['path'];
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json_encode(array(
                'state' => '未找到匹配文件',
                'list' => array(),
                'start' => $start,
                'total' => count($files)
            ));
        }
        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }
//倒序
//for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
//    $list[] = $files[$i];
//}
        /* 返回数据 */
        $result = json_encode(array(
            'state' => 'SUCCESS',
            'list' => $list,
            'start' => $start,
            'total' => count($files)
        ));
        return $result;
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param array $files
     * @return array
     */
    private function getfiles($path, $allowFiles, &$files = array()) {
        if (!is_dir($path))
            return null;
        if (substr($path, strlen($path) - 1) != '/')
            $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match('/\.(' . $allowFiles . ')$/i', $file)) {
                        $files[] = array(
                            'url' => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 抓取远程图片。
     * @param type $fieldName
     * @param type $config
     * @return type
     */
    private function saveRemote($fieldName, $config) {
        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        $uploadFolder = substr(UPLOADFOLDER, 1);
        $rootPath = $uploadFolder;
        $config['pathFormat'] = $rootPath . $config['pathFormat'];
        foreach ($source as $imgUrl) {
            $item = new \addons\editor\util\Uploader($imgUrl, $config, 'remote');
            $info = $item->getFileInfo();
            array_push($list, array(
                'state' => $info['state'],
                'url' => $info['url'],
                'size' => $info['size'],
                'title' => htmlspecialchars($info['title']),
                'original' => htmlspecialchars($info['original']),
                'source' => htmlspecialchars($imgUrl)
            ));
        }
        /* 返回抓取数据 */
        return json_encode(array(
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ));
    }

}
