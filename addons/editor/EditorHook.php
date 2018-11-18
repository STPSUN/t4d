<?php

namespace addons\editor;

/**
 * 编辑器插件
 *  
 */
class EditorHook extends \web\addons\controller\AddonHook {

    /**
     * 编辑器钩子
     * @param
     * array('name'=>'表单name','value'=>'表单对应的值')
     */
    public function editor($data) {
        if (!isset($data['is_mult'])) // 默认不传时为0
            $data['is_mult'] = 0;
        else
            $data['is_mult'] = intval($data['is_mult']);
        if (!isset($data['toolbars']))
            $data['toolbars'] = '';
        $this->assign('data', $data);
        $this->assign('addons_config', $this->getConfig());
        $this->display('content');
    }

    /**
     * 微信编辑器
     * @param type $data
     */
    public function wxeditor($data) {
        if (!isset($data['is_mult'])) // 默认不传时为0
            $data['is_mult'] = 0;
        else
            $data['is_mult'] = intval($data['is_mult']);
        if (!isset($data['toolbars']))
            $data['toolbars'] = '';
        $this->assign('data', $data);
        $this->assign('addons_config', $this->getConfig());
        $this->display('wx_content');
    }

    /**
     * 编辑器挂载的后台文档模型文章内容钩子
     * 
     * @param
     *        	array('name'=>'表单name','value'=>'表单对应的值')
     */
    public function uploadImg($data) {
        $this->assign('data', $data);
        $this->assign('addons_config', $this->getConfig());
        $this->display('upload_btn');
    }

}
