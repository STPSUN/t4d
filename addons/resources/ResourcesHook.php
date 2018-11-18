<?php

namespace addons\Resources;

/**
 * 本地资源勾子。
 */
class ResourcesHook extends \web\addons\controller\AddonHook {

    /**
     * 
     * @param type $data
     */
    public function picResource($data) {
        if (!empty($data['value'])) {
            $picList = explode(',', $data['value']);
            $data['picList'] = $picList;
        }
        if (empty($data['checktype']))
            $data['checktype'] = 1;
        if (empty($data['type']))
            $data['type'] = 'add';
        if (!isset($data['callback']))
            $data['callback'] = '';
        $this->assign('addons_data', $data);
        $this->assign('upload_folder', $uploadFolder = substr(UPLOADFOLDER, 1));
        $this->assign('addons_config', $this->getConfig());
        $this->display('pic');
    }

}
