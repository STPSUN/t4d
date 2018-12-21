<?php
/**
 * Created by PhpStorm.
 * User: stp
 * Date: 2018/12/21
 * Time: 11:31
 */

namespace addons\fomo\service;


class Game extends \web\index\controller\AddonIndexBase
{
    /**
     * 是否达到当局key上限
     * @param $key_num
     * @param $game_key_num
     * @return bool
     */
    public function isGameTotalKey($key_num,$game_key_num)
    {
        $confM = new \addons\fomo\model\Conf();
        $key_total_conf = $confM->getValByName('key_total');
        $total_key = $key_num + $game_key_num;
        if($total_key > $key_total_conf)
            return false;

        return true;
    }


    public function isBuyKey($game_id,$user_id,$key_num)
    {
        $confM = new \addons\fomo\model\Conf();
        $keyRecordM = new \addons\fomo\model\KeyRecord(); //用户key记录

        $key_limit = $confM->getValByName('key_limit');
        if($key_limit <= 0)
            return false;

        $data = $keyRecordM->where(['game_id' => $game_id, 'user_id' => $user_id])->field('id,current_key,status')->find();
        if(empty($data))
        {
            if($key_num > $key_limit)
                return $key_limit;

            $data['game_id'] = $game_id;
            $data['user_id'] = $user_id;
            $data['key_num'] = 0;
            $data['limit_amount'] = 0;
            $data['update_time'] = NOW_DATETIME;

            $keyRecordM->add($data);

            return true;
        }else
        {
            if($data['status'] == 2)
            {
                $keyRecordM->save([
                    'status' => 1,
                    'update_time' => NOW_DATETIME,
                ],[
                    'id' => $data['id'],
                ]);
            }

            $current_key = $data['current_key'];
            if($current_key >= $key_limit)
            {
                return 0;
            }

            $total_key = $current_key + $key_num;
            if($total_key > $key_limit)
            {
                $buy_num = $key_limit - $current_key;
                return $buy_num;
            }

            return true;
        }
    }
}








