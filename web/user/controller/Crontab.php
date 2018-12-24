<?php

namespace web\user\controller;
/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/12/18
 * Time: 16:45
 */
class Crontab extends \web\common\controller\BaseController
{
    public function bonusRelease()
    {
        $m = new \addons\fomo\index\controller\Crontab();
        $m->excute();
    }
}