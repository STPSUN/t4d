<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace addons\fomo\user\controller;
use function PHPSTORM_META\elementType;

/**
 * Description of KeyRecord
 * 购买记录 p3d f3d
 * @author shilinqing
 */
class KeyRecord extends \web\user\controller\AddonUserBase{
    
    public function index(){
        $type = $this->_get('type');
        if($type == '' || $type==0){
            $type = 0;
            $m = new \addons\fomo\model\Game();
            $games = $m->getDataList();
            $this->assign('games',$games);
            $t = new \addons\fomo\model\Team();
            $teams = $t->getDataList();
            $this->assign('teams',$teams);
        }
        $this->assign('type',$type);
        return $this->fetch();
    }
    
    public function loadList() {
        $keyword = $this->_get('keyword');
        $type = $this->_get('type');
        $game_id = $this->_get('game_id');
//        $team_id = $this->_get('team_id');
        $rank = $this->_get('rank');
        $filter = '1=1';
        $order = 'update_time desc';
        if($type == 0){
            $m = new \addons\fomo\model\KeyRecord();
            if($game_id != ''){
                $filter .= ' and game_id='.$game_id;
            }
            if($rank == 1){
                $order = 'key_num desc';
            }
        }else{
            $m = new \addons\fomo\model\TokenRecord();
        }
        if ($keyword != null) {
            $filter .= ' and username like \'%' . $keyword . '%\'';
        }
        $total = $m->getTotal($filter);
        $rows = $m->getList($this->getPageIndex(), $this->getPageSize(), $filter,$order);
        $count_total = $m->getCountTotal($filter);
        return $this->toTotalDataGrid($total, $rows,$count_total);
    }
    
    
    public function set_winner(){
        $id = $this->_get('id');
        $rank = $this->_get('rank');    //排名
        $rank = intval($rank);

        $m = new \addons\fomo\model\KeyRecord();
        $m->startTrans();
        try{

            $data = $m->getDetail($id);
            if(empty($data)){
                return $this->failData('数据不存在');
            }

            $game_id = $data['game_id'];
            $g = new \addons\fomo\model\Game();
            $game = $g->getDetail($game_id);
            if(empty($game) || $game['status'] != 1){
                return $this->failData('游戏不存在或者不是进行中');
            }

            if($rank == 0)
            {
                return $this->failData('请指定1-10名');
            }

            if($rank > 10 && $rank != 99)
                return $this->failData('请指定1-10名');

            $record = $m->where(['game_id' => $game_id, 'winner' => $rank])->find();
            $record['update_time'] = NOW_DATETIME;
            $record['winner'] = 99;
            $m->save($record);

            $data['update_time'] = NOW_DATETIME;
            $data['winner'] = $rank;
            $m->save($data);

            if($rank == 99)
            {
                $m->where('game_id',$game_id)->update(['winner' => 99]);
            }else
            {
                for($i = 1; $i < $rank; $i++)
                {
                    $record = $m->where(['game_id' => $game_id, 'winner' => $i])->find();
                    if(empty($record))
                    {
                        return $this->failData('请先指定第' . $i . '名');
                    }
                }
            }

            $m->commit();
            return $this->successData();
        } catch (\Exception $ex) {
            $m->rollback();
            return $this->failData($ex->getMessage());
        }
    }

    private function checkRank($game_id,$rank)
    {
        $m = new \addons\fomo\model\KeyRecord();
        for($i = 1; $i < $rank; $i++)
        {
            $record = $m->where(['game_id' => $game_id, 'winner' => $i])->find();
            if(empty($record))
            {
                return $this->failData('请先指定第' . $i . '名');
            }
        }
    }
    
    
}
