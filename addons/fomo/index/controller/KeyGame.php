<?php

namespace addons\fomo\index\controller;
use function PHPSTORM_META\elementType;

/**
 * Description of Game
 * f3d游戏界面
 * @author shilinqing
 */
class KeyGame extends Fomobase
{

    private $usdt_rate = 0;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $maketM = new \web\api\model\MarketModel();
        $this->usdt_rate = $maketM->getUsdtRateByCoinId(1);
    }

    public function index()
    {
        $this->assign('title', 'DD.POR');
        //判断游戏是否结束
//        $this->getTeams();
        $this->getInc();
        return $this->fetch();
    }

    private function test()
    {
        $rewardRecordM = new \addons\fomo\model\RewardRecord();
        $data = $rewardRecordM->field('sum(amount) as amount,user_id')->group('user_id')->select();
        $keyM = new \addons\fomo\model\KeyRecord();
        foreach ($data as $v)
        {
            $keyM->save([
                'bonus_amount' => $v['amount'],
            ],[
                'user_id'   => $v['user_id']
            ]);
        }

        echo 22;exit();
    }

    public function buy()
    {
        return $this->failData();
        if (IS_POST) {
            $user_id = $this->user_id;
            //投注 需要验证
            if ($user_id<= 0) {
                return $this->failData(lang('Please login'));
            }
            $game_id = $this->_post('game_id');
            $key_num = $this->_post('key_num'); //数量

            //是否有上级,余额是否足够,是否空投
            $gameM = new \addons\fomo\model\Game();
            $game = $gameM->getDetail($game_id);

            //游戏是否结束
            $end_game_time = $game['end_game_time'];
            if ($end_game_time <= time()) {
                return $this->failData(lang('The game is over'));
            }

            $gameS = new \addons\fomo\service\Game();
            $is_true = $gameS->isGameTotalKey($key_num,$game['key_total']);
            if(!$is_true)
                return $this->failData(lang('The upper limit of key of the authority has been reached'));

            //用户是否有该币种余额
            $coin_id = $game['coin_id']; //币种

            //游戏当前价格
            $priceM = new \addons\fomo\model\KeyPrice();
            $current_price_data = $priceM->getGameCurrentPrice($game_id);
            $current_price = $current_price_data['key_amount'];

            //余额是否足够
            $balanceS = new \addons\fomo\service\Balance();
            $key_total_price = $balanceS->isEnoughBalance($user_id,$game_id,$key_num,$coin_id);
            if(!$key_total_price)
                return $this->failData(lang('Lack of balance'));

            $confM = new \addons\fomo\model\Conf();
            $key_inc_amount = $confM->getValByName('key_inc_amount'); //key递增值

            //可购买数量
            $res = $gameS->isBuyKey($game_id,$user_id,$key_num);
            if(!$res['res'])
                return $this->failData(lang('The purchase limit has been reached') . $res['num']);

            try {
                $gameM->startTrans();

                //扣除用户余额
                $is_true = $balanceS->updateBalance($user_id,$key_total_price,$coin_id);
//                $balance['before_amount'] = $balance['amount'];
//                $balance['amount'] = $balance['amount'] - $key_total_price;
//                $balance['update_time'] = NOW_DATETIME;
//                $is_save = $balanceM->save($balance);
                if(!$is_true)
                {
                    $gameM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#1');
                }

                //添加交易记录
                $recordM = new \addons\member\model\TradingRecord();
                $type = 10;
                $before_amount = 0;
                $after_amount = 0;
                $change_type = 0; //减少
                $remark = '购买key';
                $r_id = $recordM->addRecord($user_id, $coin_id, $key_total_price, $before_amount, $after_amount, $type, $change_type, '', '', '', $remark,$game_id);
                if (!$r_id) {
                    $gameM->rollback();
                    return $this->failData(lang('Purchase failed'));
                }

                //全网分红
                $this->wholeDividend($coin_id,$game_id,$key_total_price);

                //动态分红：邀请人逆推3代奖励
                $invite_rate = $confM->getValByName('invite_rate');  //投注推荐奖励
                $remark = '动态分红';
                $this->parentDividend($user_id, $invite_rate, $key_total_price, $coin_id, 3, $game_id, $remark);

                //父级加入节点分红
                $this->addNode($user_id,$key_total_price,$game_id);

                //节点分红
                $this->nodeIncome($coin_id,$game_id,$key_total_price);

                //战队:投注p3d,f3d奖励队列,奖池+,用户key+,时间+
                $pool_rate = $confM->getValByName('pool_rate'); //投注进入奖池比率
                $pool_amount = $this->countRate($key_total_price, $pool_rate); //进入奖池金额
                $release_amount = $key_total_price - $pool_amount; //已发金额
                $buy_inc_second = $confM->getValByName('buy_inc_second');
                $inc_time = $key_num * $buy_inc_second; //游戏增加时间
//                更新数据 
//                用户key+

                $out_mom = $confM->getValByName('out_mom'); //出局倍数
                $limit_amount = $key_total_price * $out_mom;
                $keyRecordM = new \addons\fomo\model\KeyRecord();
                $save_key = $keyRecordM->saveUserKey($user_id, $game_id, $key_num,$limit_amount);
                if($save_key <= 0)
                {
                    $gameM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#2');
                }
//              更新游戏设置：奖池+ ,时间+
                $time = time();
                $end_game_time = $game['end_game_time'] + $inc_time;
                if (($end_game_time - $time) > 60 * $game['hour']) $end_game_time = $time + 60 * $game['hour'];
                $game['end_game_time'] = $end_game_time;
                $game['total_buy_seconds'] = $game['total_buy_seconds'] + $inc_time;
                $game['total_amount'] = $game['total_amount'] + $key_total_price;
                $game['pool_total_amount'] = $game['pool_total_amount'] + $pool_amount;
                $game['release_total_amount'] = $game['release_total_amount'] + $release_amount;
//                $game['drop_total_amount'] = $game['drop_total_amount'] + $drop_amount;
                $game['update_time'] = NOW_DATETIME;
                $game['key_total'] = $game['key_total'] + $key_num;
                $is_save = $gameM->save($game);
                if($is_save <= 0)
                {
                    $gameM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#3');
                }

//              key 价格+
                $current_price_data['key_amount'] = $current_price + $key_inc_amount * $key_num;
                $current_price_data['update_time'] = NOW_DATETIME;
                $is_save = $priceM->save($current_price_data);
                if($is_save <= 0)
                {
                    $gameM->rollback();
                    return $this->failData('系统繁忙，请稍后再试，#4');
                }

                //中期奖
                $this->interimAward($coin_id,$game_id);

                $gameM->commit();
                return $this->successData();
            } catch (\Exception $ex) {
                $gameM->rollback();
                return $this->failData($ex->getMessage());
            }
        }
    }

    /**
     * 封顶限制
     */
    private function limitAmount($user_id,$coin_id,$game_id,$amount)
    {
//        $rewardRecordM = new \addons\fomo\model\RewardRecord();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
//        $user_amount = $rewardRecordM->getTotalAmount($user_id,$coin_id,$game_id);
        $data = $keyRecordM->where(['game_id' => $game_id, 'user_id' => $user_id])->field('limit_amount,bonus_amount')->find();
        $limit_amount = $data['limit_amount'];
        $user_amount = $data['bonus_amount'];

        if($limit_amount <= $user_amount)
        {
            $keyRecordM->save([
                'status' => 2,
                'current_key' => 0,
            ],[
                'user_id'   => $user_id,
                'game_id'   => $game_id,
            ]);
            return false;
        }

        $total_amount = $user_amount + $amount;
        if($limit_amount <= $total_amount)
        {
            $keyRecordM->save([
                'status' => 2,
                'current_key'   => 0,
            ],[
                'user_id'   => $user_id,
                'game_id'   => $game_id,
            ]);
            $amount = $limit_amount - $user_amount;
        }

        return $amount;
    }

    /**
     * 封顶限制
     */
    private function limitAmount2($amount,$id)
    {
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $data = $keyRecordM->where('id',$id)->field('limit_amount,bonus_amount')->find();

        $limit_amount = $data['limit_amount'];
        $user_amount = $data['bonus_amount'];
        if($limit_amount <= $user_amount)
        {
            $keyRecordM->save([
                'status' => 2,
                'current_key'   => 0,
            ],[
                'id' => $id,
            ]);
            return false;
        }

        $total_amount = $user_amount + $amount;
        if($limit_amount <= $total_amount)
        {
            $keyRecordM->save([
                'status' => 2,
                'current_key'   => 0,
            ],[
                'id' => $id,
            ]);
            $amount = $limit_amount - $user_amount;
        }

        return $amount;
    }

    /**
     * 中期奖
     */
    private function interimAward($coin_id,$game_id)
    {
        //是否开启中期奖
        $sysM = new \web\common\model\sys\SysParameterModel();
        $is_node = $sysM->getValByName('is_mid_award');
        if($is_node != 1)
            return;

        $gameM = new \addons\fomo\model\Game();
        $pool_amount = $gameM->where('id',$game_id)->value('pool_total_amount');
        if($pool_amount < 200000)
            return;

        $grant_amount = 0;  //发放金额
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $sequeueM = new \addons\fomo\model\BonusSequeue();
        $data = $keyRecordM->getMaxWinner($game_id);

        foreach ($data as $key=>$v)
        {
            if($key < 10)
            {
                //封顶限制
                $amount = $this->limitAmount($v['user_id'],$coin_id,$game_id,6000);
                if(!$amount)
                    continue;

                $grant_amount += $amount;
                $sequeueM->addSequeue($v['user_id'],$coin_id,$amount,1,8,$game_id);
                $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);
            }else if($key < 20)
            {
                //封顶限制
                $amount = $this->limitAmount($v['user_id'],$coin_id,$game_id,3000);
                if(!$amount)
                    continue;

                $grant_amount += $amount;
                $sequeueM->addSequeue($v['user_id'],$coin_id,$amount,1,8,$game_id);
                $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);
            }else if($key < 50)
            {
                //封顶限制
                $amount = $this->limitAmount($v['user_id'],$coin_id,$game_id,2000);
                if(!$amount)
                    continue;

                $grant_amount += $amount;
                $sequeueM->addSequeue($v['user_id'],$coin_id,$amount,1,8,$game_id);
                $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);
            }else
            {
                //封顶限制
                $amount = $this->limitAmount($v['user_id'],$coin_id,$game_id,1000);
                if(!$amount)
                    continue;

                $grant_amount += $amount;
                $sequeueM->addSequeue($v['user_id'],$coin_id,$amount,1,8,$game_id);
                $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);
            }
        }

        $gameM->where('id',$game_id)->setDec('pool_total_amount',$grant_amount);
    }


    /**
     * 节点分红
     */
    private function nodeIncome($coin_id,$game_id,$key_total_price)
    {
        //是否开启节点分红
        $sysM = new \web\common\model\sys\SysParameterModel();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $is_node = $sysM->getValByName('is_node_award');
        if($is_node != 1)
            return;

        $confM = new \addons\fomo\model\Conf();
        $nodeM = new \addons\fomo\model\Node();
        $sequeueM = new \addons\fomo\model\BonusSequeue();
        $users = $nodeM->getNodeUsers($game_id);
        if(empty($users))
            return;

        $user_num = count($users);
        $node_rate = $confM->getValByName('node_rate'); //节点分红比率
        $node_amount = $this->countRate($key_total_price,$node_rate);
        $amount = bcdiv($node_amount,$user_num,8);
        foreach ($users as $v)
        {
            //封顶限制
            $amount = $this->limitAmount($v['user_id'],$coin_id,$game_id,$amount);
            if(!$amount)
                continue;

            $sequeueM->addSequeue($v['user_id'],$coin_id,$amount,1,7,$game_id);
            $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);

        }
    }

    /**
     * 父级加入节点分红表
     */
    private function addNode($user_id,$key_total_price,$game_id)
    {
        $memberM = new \addons\member\model\MemberAccountModel();
        $user = $memberM->getDetail($user_id);
        $puser = $memberM->getDetail($user['pid']);
        if(empty($puser))
            return false;

        $nodeM = new \addons\fomo\model\Node();
        $node = $nodeM->where(['user_id' => $user['pid'], 'game_id' => $game_id])->find();
        if(!empty($node))
            return false;

        $recordM = new \addons\member\model\TradingRecord();
        $map = array(
            'user_id'   => $user['pid'],
            'type'      => 10,
            'game_id'   => $game_id,
        );
        $amount = $recordM->where($map)->sum('amount');
        if($amount < 100)
            return false;

        $users = $memberM->where('pid',$user['pid'])->column('id');
        $user_ids = '';
        foreach ($users as $v)
        {
            $user_ids = $v . ',';
        }

        $user_ids = rtrim($user_ids,',');
        $where['user_id'] = array('in',$user_ids);
        $where['type'] = 10;
        $where['game_id'] = $game_id;
        $total_amount = $recordM->where($where)->sum('amount');
        $total_amount += $key_total_price;
        if($total_amount < 5000)
            return false;

        $data = array(
            'user_id' => $user['pid'],
            'game_id' => $game_id,
            'create_time' => NOW_DATETIME
        );
        $res = $nodeM->add($data);
        if($res)
            return true;
        else
            return false;

    }

    /**
     * 全网分红
     * @param $coin_id
     * @param $game_id
     * @param $key_total_price
     */
    private function wholeDividend($coin_id,$game_id,$key_total_price)
    {
        $confM = new \addons\fomo\model\Conf();
        //是否开启全网分红
        $is_whole_award = $confM->getValByName('is_whole_award');
        if($is_whole_award != 1)
            return;

        $sequeueM = new \addons\fomo\model\BonusSequeue();
        $keyRecordM = new \addons\fomo\model\KeyRecord(); //用户key记录
        $whole_rate = $confM->getValByName('whole_rate'); //全网分红比率

        //分红用户
        $record_user_data = $keyRecordM->where(['game_id' => $game_id, 'status' => 1])->field('user_id,id')->select();
        if(empty($record_user_data))
            return;

        $key_total = $keyRecordM->getKeyNumTotal($game_id);
        if($key_total == 0)
            return;

        $whole_amount = $this->countRate($key_total_price,$whole_rate);

        //分红发放
        foreach ($record_user_data as $key => $v)
        {
            $user_amount =  $keyRecordM->getTotalByGameID($v['user_id'],$game_id);
            if(empty($user_amount))
                continue;

            $rate = bcdiv($user_amount,$key_total,8);  //分红比率 = 会员本局key总和 / 本局key总和
            $amount = bcmul($whole_amount,$rate,8);  //分红数量 = 全网数量 * 权重占比

            //封顶限制
            $amount = $this->limitAmount2($amount,$v['id']);
            if(!$amount)
                continue;

            $sequeueM->addSequeue($v['user_id'], $coin_id, $amount, 1, 0, $game_id);
            $keyRecordM->where('id',$v['id'])->setInc('bonus_amount',$amount);
        }
    }

    /**
     * 获取空投
     */
    private function getAirDrop($key_num, $key_total_price, $game_id, $coin_id, $drop_total_amount)
    {
        $confM = new \addons\fomo\model\Conf();
        $need_key = $confM->getValByName('need_key');
        $drop_rate = $confM->getValByName('get_drop_rate');
        //判断key数量
        if ($key_num < $need_key)
            return true;
        //是否中奖
        $win_num = rand(1, 100);
        if ($win_num > $drop_rate)
            return true;
        //奖金比率
        $bonus_rate = $this->getDropRate($key_total_price);
        //奖金
        $bonus = $drop_total_amount * $bonus_rate / 100;

        try {
            //更新空投总额
            $gameM = new \addons\fomo\model\Game();
            $gameM->startTrans();
            $gameM->where('id', $game_id)->setDec('drop_total_amount', $bonus);

            //更新用户资金
            $balanceM = new \addons\member\model\Balance();
            $balance = $balanceM->updateBalance($this->user_id, $bonus, $coin_id, true);
            //添加分红记录
            $recordM = new \addons\fomo\model\RewardRecord();
            $after_amount = $balance['amount'];
            $remark = '空投奖励';
            $recordM->addRecord($this->user_id, $coin_id, $balance['before_amount'], $bonus, $after_amount, 0, $game_id, $remark);

            $gameM->commit();
            return true;
        } catch (\Exception $e) {
            return false;
            $gameM->rollback();
        }
    }

    //奖金比率
    private function getDropRate($num = null)
    {
        $airdropM = new \addons\fomo\model\Airdrop();
        $result = $airdropM->where(function ($query) use ($num) {
            $query->where('min', '<=', $num)->where('max', '>=', $num)->where('min', '<>', 0);
        })->whereOr(function ($query) use ($num) {
            $query->where('max', '<=', $num)->where('min', 0);
        })->find();

        return $result['rate'];
    }

    private function getTeams()
    {
        $m = new \addons\fomo\model\Team();
        $filter = 'status=1';
        $fields = "id,name,detail,pic";
        $list = $m->getDataList(-1, -1, $filter, $fields, 'id asc');
        foreach ($list as $key => $val) {
            switch (cookie('think_var')) {
                case 'en-us':
                    $baidu = new BaiduApi();
                    $list[$key]['name'] = $baidu->translate($val['name'], "zh", "en");
                    $list[$key]['detail'] = $baidu->translate($val['detail'], "zh", "en");
                    break;
                case 'zh-cn':
                    # code...
                    break;
                case 'zh-tw':
                    $baidu = new BaiduApi();
                    $list[$key]['name'] = $baidu->translate($val['name'], "zh", "cht");
                    $list[$key]['detail'] = $baidu->translate($val['detail'], "zh", "cht");
                    break;
                default:
                    # code...
                    break;
            }
        }
        $this->assign('teams', $list);
    }

    private function getInc()
    {
        $m = new \addons\fomo\model\Conf();
        $inc = $m->getValByName('key_inc_amount');
        $this->assign('inc', $inc);
    }

    public function getGame()
    {
        $m = new \addons\fomo\model\Game();
        $confM = new \addons\fomo\model\Conf();
        $game = $m->getRunGame();
        if (!empty($game)) {
            $game = $game[0];
            $end_game_time = $game['end_game_time'];

            //region start 游戏结束：奖金池分红
            if ($end_game_time <= time()) {
                //if 游戏的当前结束时间小于等于当前时间,则结束游戏
                try {
                    $m->startTrans();
                    //更新游戏状态为结束 status = 2 
                    $game['status'] = 2;
                    $is_over = $m->save($game);
                    if ($is_over <= 0)
                        return $this->failData();

                    $game_id = $game['id'];
                    $coin_id = $game['coin_id'];
                    $pool_total_amount = $game['pool_total_amount']; //奖池总数

                    //最后投注者分红
                    $this->lastWinnerDividend($game_id, $pool_total_amount, $coin_id);

                    //更新进入下一轮奖池金额
                    $next_rate = $confM->getValByName('next_rate'); //最后投注者分红比率
                    $to_next_amount = $this->countRate($pool_total_amount, $next_rate);
                    $game['to_next_amount'] = $to_next_amount;
                    $m->save($game);

                    //大赢家
//                    $winner_rate = $confM->getValByName('winner_rate');
                    $this->bigWinnerDividend($game_id,$pool_total_amount, $coin_id);

                    //触手分红
                    $pool_winner_rate = $confM->getValByName('pool_winner_rate');
                    $this->poolParentDividend($game_id, $pool_winner_rate, $pool_total_amount, $coin_id);

                    $m->commit();
                    return $this->failData(lang('The game is over'));
                } catch (\Exception $ex) {
                    $m->rollback();
                    return $this->failData($ex->getMessage());
                }
            }
            //region end 游戏结束：分红

            $maketM = new \web\api\model\MarketModel();
            $rate = $maketM->getUsdtRateByCoinId($game['coin_id']);
            $game['rate'] = $rate;
            $game['pool_total_amount'] = round($game['pool_total_amount'],2);
            $game['pool_total_cny'] = bcmul($game['pool_total_amount'], $rate, 2);
            $game['release_total_cny'] = bcmul($game['release_total_amount'], $rate, 2);
            $game['end_game_time'] = $end_game_time;

            $keyRecordM = new \addons\fomo\model\KeyRecord();
            $game_total_keys = $keyRecordM->getSum("game_id = {$game['id']}", "key_num");
            $game['game_total_keys'] = $game_total_keys;
            $game['game_total_keys_usdt'] = bcmul($game_total_keys, $rate, 4);
            $game['total_amount_usdt'] = bcmul($game['total_amount'],$rate,4);
            return $this->successData($game);
        }
        //如果有已结束的 查询已结束的游戏与 开奖结果
        $m = new \addons\fomo\model\Game();
        $end_game = $m->getLastEndGame();
        if (!empty($end_game)) {
            $game = $end_game[0];
            $maketM = new \web\api\model\MarketModel();
            $rate = $maketM->getUsdtRateByCoinId($game['coin_id']);
            $game['pool_total_amount'] = round($game['pool_total_amount'],2);
            $game['rate'] = $rate;
            $game['pool_total_cny'] = bcmul($game['pool_total_amount'], $rate, 2);
            $game['release_total_cny'] = bcmul($game['release_total_amount'], $rate, 2);

            $keyRecordM = new \addons\fomo\model\KeyRecord();
            $game_total_keys = $keyRecordM->getSum("game_id = {$game['id']}", "key_num");
            $game['game_total_keys'] = $game_total_keys;
            $game['game_total_keys_usdt'] = bcmul($game_total_keys, $rate, 4);

            $game['total_amount_usdt'] = bcmul($game['total_amount'],$rate,4);

            $recordM = new \addons\fomo\model\RewardRecord();
//            $last_winner = $recordM->getGameWinner($game['id']);

            $keyRecordM = new \addons\fomo\model\KeyRecord();
            $last_winner = $keyRecordM->getWinnerRank($game['id'], 10);
            if(!empty($last_winner))
            {
                $game['last_winner'] = $last_winner;
                $big_winner_amount = $recordM->where(['game_id' => $game['id'], 'user_id' => $last_winner[0]['id']])->sum('amount');
                $game['big_winner_amount'] = round($big_winner_amount,2);
            }

            return $this->successData($game);
        } else {
            return $this->failData(lang('Waiting to start another round'));
        }
    }

    /**
     * 大赢家分红
     * @param $game_id
     * @param $winner_rate
     * @param $amount
     * @param $coin_id
     * @param $type
     * @param $remark
     */
    private function bigWinnerDividend($game_id,$pool_total_amount, $coin_id)
    {
        $queueM = new \addons\fomo\model\BonusSequeue();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        //大赢家30名
        $winner_list = $keyRecordM->getWinner($game_id, 30);

        if(empty($winner_list))
            return;

        $count = count($winner_list);

        $confM = new \addons\fomo\model\Conf();
        $rate = $confM->getValByName('winner_rate'); //最后投注者分红比率
        $total_amount = $this->countRate($pool_total_amount, $rate); //胜利者所得
        foreach ($winner_list as $key=>$v)
        {
            if($key < 1)
            {
                $amount = $this->countRate($total_amount,35);
            }else if($key < 10)
            {
                $amount = $this->countRate($total_amount,30);
                $num = ($count > 10) ? 9 : ($count - 1);
                $amount = bcdiv($amount,$num,8);
            }else if($key < 20)
            {
                $amount = $this->countRate($total_amount,20);
                $num = ($count > 20) ? 10 : ($count - 10);
                $amount = bcdiv($amount,$num,8);
            }else if($key < 30)
            {
                $amount = $this->countRate($total_amount,15);
                $num = ($count > 30) ? 10 : ($count - 20);
                $amount = bcdiv($amount,$num,8);
            }

            //封顶限制
            $amount = $this->limitAmount2($amount,$v['id']);
            if(!$amount)
                continue;

            //添加队列 scene = 2 ,type=0
            $type = 1;
            $scene = 5; //奖金池-大赢家分红
            $queueM->addSequeue($v['user_id'], $coin_id, $amount, $type, $scene, $game_id);
        }
    }

    private function bigWinnerDividend2($game_id, $winner_rate, $amount, $coin_id)
    {
        $queueM = new \addons\fomo\model\BonusSequeue();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        //大赢家3名
        $winner_list = $keyRecordM->getWinner($game_id, 3);

        $count = count($winner_list);
        $start = 0;

        $rate = explode(",", $winner_rate);
        foreach ($rate as $val) {
            if ($start >= $count)
                break;

            if (!$val) {
                continue;
            }
            $user_id = $winner_list[$start]['user_id'];

            $rate_amount = $this->countRate($amount, $val);

            //添加队列 scene = 2 ,type=0
            $type = 1;
            $scene = 5; //奖金池-大赢家分红
            $queueM->addSequeue($user_id, $coin_id, $rate_amount, $type, $scene, $game_id);

            $start += 1;
        }
    }

    /**
     * 奖金池触手分红
     * @param $game_id
     * @param $conf_rate
     * @param $amount
     * @param $coin_id
     */
    private function poolParentDividend($game_id, $conf_rate, $amount, $coin_id)
    {
        $queueM = new \addons\fomo\model\BonusSequeue();
        $userM = new \addons\member\model\MemberAccountModel();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        //大赢家3名
        $winner_list = $keyRecordM->getWinner($game_id, 3);

        //大赢家直系推荐人
        $user_list = array();
        foreach ($winner_list as $val) {
            if (empty($val))
                continue;

            $pOne = $userM->getPID($val['user_id']);
            if (empty($pOne))
                continue;
            array_push($user_list, $pOne);

            $pTwo = $userM->getPID($pOne);
            if (empty($pTwo))
                continue;
            array_push($user_list, $pTwo);

            $pThree = $userM->getPID($pTwo);
            if (empty($pThree))
                continue;
            array_push($user_list, $pThree);
        }

        $num = count($user_list);
        if($num == 0)
            return;
        $rate = bcdiv($conf_rate, $num, 4);

        $pool_parent_amount = $this->countRate($amount, $rate);
        foreach ($user_list as $v) {
            //封顶限制
            $pool_parent_amount = $this->limitAmount2($pool_parent_amount,$v['id']);
            if(!$pool_parent_amount)
                continue;
            //添加队列 scene = 2 ,type=0
            $type = 1;
            $scene = 6; //奖金池触手分红
            $queueM->addSequeue($v, $coin_id, $pool_parent_amount, $type, $scene, $game_id);
        }

    }

    /**
     * 父级分红（触手分红）
     * @param $user_id
     * @param $conf_rate /分红比率，代数根据以逗号分隔的个数
     * @param $amount /总金额
     * @param $coin_id
     * @param $type
     * @param $game_id
     * @param $remark
     */
    private function parentDividend($user_id, $conf_rate, $amount, $coin_id, $type, $game_id, $remark)
    {
        $sysM = new \addons\fomo\model\Conf();
        $is_parent_award = $sysM->getValByName('is_parent_award');
        if($is_parent_award != 1)
            return;

        //触手分红：邀请人逆推3代奖励
        $userM = new \addons\member\model\MemberAccountModel();
        $keyRecordM = new \addons\fomo\model\KeyRecord(); //用户key记录
        $pid = $userM->getPID($user_id);
        if (!empty($pid)) {
            $rate = explode(",", $conf_rate);
            foreach ($rate as $val) {
                if (!$val) {
//                    $pid = $userM->getPID($pid);
//                    if (!$pid) {
//                        break;
//                    }
                    continue;
                }

                //父级是否参与本局游戏及是否出局
                $key_record = $keyRecordM->where(['game_id' => $game_id, 'user_id' => $pid, 'status' => 1])->find();
                if(empty($key_record))
                    continue;

                $invite_amount = $this->countRate($amount, $val); //邀请奖励

                //封顶限制
                $invite_amount = $this->limitAmount($pid,$coin_id,$game_id,$invite_amount);
                if(!$invite_amount)
                    continue;

                //更新余额
                $balanceS = new \addons\fomo\service\Balance();
                $is_true = $balanceS->updateBalanceByBonus($pid,$invite_amount,$coin_id);
                //添加分红记录
                if ($is_true) {
                    $rewardM = new \addons\fomo\model\RewardRecord();
                    $before_amount = 0;
                    $after_amount = 0;
                    $rewardM->addRecord($pid, $coin_id, $before_amount, $invite_amount, $after_amount, $type, $game_id, $remark);
                    //分红值增加
                    $keyRecordM->where(['game_id' => $game_id, 'user_id' => $pid])->setInc('bonus_amount',$invite_amount);
                    $pid = $userM->getPID($pid);
                    if (!$pid) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * 最后投资500名分红
     * @param $game_id
     */
    private function lastWinnerDividend($game_id, $pool_total_amount, $coin_id)
    {
        $queueM = new \addons\fomo\model\BonusSequeue();
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $list = $keyRecordM->getLastWinner($game_id);
        if (empty($list))
            return;

        $confM = new \addons\fomo\model\Conf();
        $rate = $confM->getValByName('last_rate'); //最后投注者分红比率
        $total_amount = $this->countRate($pool_total_amount, $rate); //胜利者所得
        $user_num = count($list);

        foreach ($list as $key=>$v) {
            if($key < 1)
            {
                $amount = $this->countRate($total_amount,40);
            }else if($key < 20)
            {
                //倒数2至20名的人数
                $num = ($user_num > 20) ? 19 : ($user_num - 1);
                $amount = $this->countRate($total_amount,30);
                $amount = bcdiv($amount,$num,8);
            }else if($key < 200)
            {
                //倒数21至200名的人数
                $num = ($user_num > 200) ? 180 : ($user_num - 20);
                $amount = $this->countRate($total_amount,20);
                $amount = bcdiv($amount,$num,8);
            }else if($key < 500)
            {
                //倒数21至200名的人数
                $num = ($user_num > 500) ? 300 : ($user_num - 200);
                $amount = $this->countRate($total_amount,10);
                $amount = bcdiv($amount,$num,8);
            }

            //封顶限制
            $amount = $this->limitAmount2($amount,$v['id']);
            if(!$amount)
                continue;
            //添加队列 scene = 2 ,type=0
            $type = 1;
            $scene = 4; //奖金池触手分红
            $queueM->addSequeue($v['user_id'], $coin_id, $amount, $type, $scene, $game_id);
            $keyRecordM->where(['game_id' => $game_id, 'user_id' => $v['user_id']])->setInc('bonus_amount',$amount);
        }
    }

    public function getTeamTotal()
    {
        $game_id = $this->_get('game_id');
        $m = new \addons\fomo\model\TeamTotal();
        $data = $m->getTotalByGameId($game_id);
        return $this->successData($data);
    }

    public function getPrice()
    {
        $game_id = $this->_get('game_id');
        $m = new \addons\fomo\model\KeyPrice();
        $data = $m->getGameCurrentPrice($game_id);
        return $this->successData($data ? $data['key_amount'] : 0);
    }

    public function getKeys()
    {
        $game_id = $this->_get('game_id');
        $coin_id = $this->_get('coin_id');
        if ($this->user_id <= 0) {
            return $this->failData(lang('Not logged in'));
        }
        $maketM = new \web\api\model\MarketModel();
        $rate = $maketM->getUsdtRateByCoinId($coin_id);
        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $key_num = $keyRecordM->getTotalByGameID($this->user_id, $game_id);
        $data['key_num'] = $key_num;
        $data['key_num_usdt'] = bcmul($key_num, $rate, 8);
        $rewardM = new \addons\fomo\model\RewardRecord();
        $current_game_total_reward = $rewardM->getUserTotal($this->user_id, $coin_id, $game_id);
        $data['current_game_total_reward'] = $current_game_total_reward;
        $data['current_game_total_reward_usdt'] = bcmul($current_game_total_reward, $rate, 8);
        return $this->successData($data);
    }

    public function getRank()
    {
        $game_id = $this->_get('game_id');
        if ($this->user_id <= 0) {
            return $this->failData(lang('Not logged in'));
        }

        $keyRecordM = new \addons\fomo\model\KeyRecord();
        $winner_list = $keyRecordM->getWinnerRank($game_id, 30);
        $data = array();
        for($i = 0; $i < count($winner_list); $i++)
        {
            $temp['username'] = $winner_list[$i]['username'];
            $data[] = $temp;
        }

        return $this->successData($data);
    }
    
    public function getHelperText(){
        $m = new \addons\fomo\model\Conf();
        $data = $m->getDataByControlType();
        foreach ($data as $key => $val) {
            if (!empty($val['parameter_val'])){
                switch (cookie('think_var')) {
                case 'en-us':
                    $baidu = new BaiduApi();
                    $data[$key]['parameter_val'] = $baidu->translate($val['parameter_val'],"zh","en");
                    break;
                case 'zh-cn':
                    # code...
                    break;
                case 'zh-tw':
                    $baidu = new BaiduApi();
                    $data[$key]['parameter_val'] = $baidu->translate($val['parameter_val'],"zh","cht");
                    break;  
                default:
                    # code...
                    break;
                }
            }     
        }
        return $this->successData($data);
    }

    public function setLang(){
        $lang = request()->param('lang');
        switch ($lang) {
            //英文
            case 'en':
                cookie('think_var','en-us');
                break;
            //中文简体
            case 'cn':
                cookie('think_var','zh-cn');
                break;
            //中文繁体
            case 'tw':
                cookie('think_var','zh-tw');
                break;
            default:
                //code
                break;
        }
    }

    public function getNode()
    {
        $game_id = $this->_get('game_id');
//        $game_id = 7;
//        if ($this->user_id <= 0) {
//            return $this->failData(lang('Not logged in'));
//        }

        $recordM = new \addons\fomo\model\RewardRecord();
        $data = $recordM->alias('r')
                ->field('r.amount,r.update_time,u.username')
                ->join('member_account u','u.id = r.user_id','left')
                ->where(['r.game_id' => $game_id, 'r.type' => 7, 'user_id' => $this->user_id])
                ->select();

        return $this->successData($data);
    }

}































