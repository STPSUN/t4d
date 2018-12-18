<?php

/*
Obfuscation provided by FOPO - Free Online PHP Obfuscator: http://www.fopo.com.ar/
This code was created on Monday, July 30th, 2018 at 6:17 UTC from IP 117.25.125.152
Checksum: a5cca5d66818a14cf89578336b83efcb01b91779
*/


// [ 应用入口文件 ]
// 定义应用目录
define('APP_NAMESPACE', 'web');
define('APP_PATH', __DIR__ . '/../web/');
// 定义插件命名空间
define('ADDONS_NAMESPACE', 'addons');
// 定义插件目录
define('ADDONS_PATH', __DIR__ . '/../addons/');
//上传目录
define('UPLOADFOLDER', './uploads/');
$url = $_SERVER['SERVER_NAME'];
if($url != 'www.ddpor.vip')
    exit();

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
