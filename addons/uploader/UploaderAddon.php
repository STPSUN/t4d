<?php

namespace addons\uploader;

class UploaderAddon extends \web\addons\controller\Addon {

    public $info = array(
        'name' => 'Uploader',
        'title' => '',
        'description' => '',
        'status' => 1,
        'author' => 'lcj',
        'version' => '0.1',
        'has_adminlist' => 1,
        'type' => 1
    );

    public function install() {
        $install_sql = './Addons/Uploader/install.sql';
        if (file_exists($install_sql)) {
            execute_sql_file($install_sql);
        }
        return true;
    }

    public function uninstall() {
        $uninstall_sql = './Addons/Uploader/uninstall.sql';
        if (file_exists($uninstall_sql)) {
            execute_sql_file($uninstall_sql);
        }
        return true;
    }

}
