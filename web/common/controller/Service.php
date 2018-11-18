<?php
/**
 * ClassName: Service
 * Company: 1000web
 * @author zzz
 * @date 2018-8-14下午15:15:43
 * @version 1.0
 * Description:服务基类
 *
 * ......................我佛慈悲......................
 *                       _oo0oo_
 *                      o8888888o
 *                      88" . "88
 *                      (| -_- |)
 *                      0\  =  /0
 *                    ___/`---'\___
 *                  .' \\|     |// '.
 *                 / \\|||  :  |||// \
 *                / _||||| -卍-|||||- \
 *               |   | \\\  -  /// |   |
 *               | \_|  ''\---/''  |_/ |
 *               \  .-\__  '-'  ___/-. /
 *             ___'. .'  /--.--\  `. .'___
 *          ."" '<  `.___\_<|>_/___.' >' "".
 *         | | :  `- \`.;`\ _ /`;.`/ - ` : | |
 *         \  \ `_.   \_ __\ /__ _/   .-` /  /
 *     =====`-.____`.___ \_____/___.-`___.-'=====
 *                       `=---='
 *
 *..................佛祖开光 ,永无BUG...................
 */
namespace web\common\controller;
use think\Request;
use think\Validate;
class Service {

    protected $request; // 用来处理参数
    protected $validater; // 用来验证数据/参数
    protected $params; // 过滤后符合要求的参数
    protected $action;
    protected $rules = array(
    );

    public function __construct()
    {
        $this->request = Request::instance();
    }

    /**
     * 验证参数 参数过滤
     * @param  [array] $arr [除time和token外的所有参数]
     * @return [return]   $msg 错误信息
     * @return [return]    $arr  [合格的参数数组]
     */
    public function check_params($action, &$msg) {
        /*********** 验证参数并返回错误  ***********/
        $this->validater->scene($action);
        if (!$this->validater->check($this->request->param())) {
            $msg = $this->validater->getError();
            return false;
        }
        /*********** 如果正常,通过验证  ***********/
        return $this->request->param();
    }


    /**
     * 获取POST值
     * @param type $name 变量名
     * @param type $default 默认值
     * @param type $filter 过滤方法
     * @return type
     */
    protected function _post($name, $default = '', $filter = '') {
        return request()->param($name, $default, $filter);
    }

    /**
     * 获取GET值
     * @param type $name
     * @param type $default 默认值
     * @param type $filter 过滤方法
     * @return type
     */
    protected function _get($name, $default = '', $filter = '') {
        return request()->param($name, $default, $filter);
    }
    /**
     * 返回错误信息。
     * @param type $message
     */
    protected function failData($message = null) {
        return array('success' => false, 'message' => $message);
    }

    /**
     * 返回成功信息
     * @param type $data
     */
    protected function successData($data = NULL) {
        return array('success' => true, 'data' => $data);
    }

    /**
     * 保存base64 图片
     * @param type $base64
     * @param type $user_id
     * @return boolean|string
     */
    protected function base_img_upload($base64 ,$user_id,$savePath){

        $_message = array(
            'success' =>false,
            'message' =>'',
        );

        try{
            // 获取表单上传文件 例如上传了001.jpg
            if(empty($base64)){
                $_message['message'] = 'base64为空';
                return $_message;
            }
            $rootPath = UPLOADFOLDER;
            $uploadFolder = substr($rootPath, 1);
            $uploadPath = $uploadFolder . $savePath;
            $path = $_SERVER['DOCUMENT_ROOT'] . $uploadPath;
            $file_name= time(). getMD5Name(3,$user_id);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
                $ext = array('jpg', 'gif', 'png', 'jpeg');
                $type = strtolower($result[2]);
                if(!in_array($type, $ext)){
                    $_message['message'] = '图片格式错误';
                    return $_message;
                }
                $pic_path = $path. $file_name. "." .$type;
                $file_size = file_put_contents($pic_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64)));
                if(!$pic_path || $file_size > 10 * 1024 * 1024){
                    unlink($pic_path);
                    $_message['message'] = '图片保存失败';
                    return $_message;
                }

            }else{
                $_message['message'] = '图片格式编码错误';
                return $_message;
            }
            $this->_image_png_size_add($pic_path,$pic_path,200,200);
            $_message['success'] = true;
            $_message['message'] = '上传成功';
            $_message['path'] = $uploadPath.$file_name.'.'.$type;
            return $_message;
        }catch(Exception $ex){
            $_message['success'] = false;
            $_message['message'] = $ex->getMessage();
            return $_message;
        }


    }


    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     */
    private function _image_png_size_add($imgsrc,$imgdst,$max_width=1024,$max_height=1024){
        list($width,$height,$type) = getimagesize($imgsrc);
        $new_width = ($width>$max_width ? $max_width : $width)*2;
        $new_height =($height>$max_height ? $max_height : $height)*2;
        switch($type){
            case 1:
                $giftype = $this->check_gifcartoon($imgsrc);
                if($giftype){
//                    header('Content-Type:image/gif');
                    $image_wp=imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst,75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
//                header('Content-Type:image/jpeg');
                $image_wp = imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
            case 3:
//                header('Content-Type:image/png');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
        }
    }

    /**
     * desription 判断是否gif动画
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    private function check_gifcartoon($image_file){
        $fp = fopen($image_file,'rb');
        $image_head = fread($fp,1024);
        fclose($fp);
        return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
    }
}