<?php

namespace web\common\behavior;

/**
 * Behavior父类
 */
class BehaviorBase {

    protected $crontab_minute = 0;
    protected $option = array();

    public function __construct($crontab_minute, $option) {
        $this->crontab_minute = $crontab_minute;
        $this->option = $option;
    }

}
