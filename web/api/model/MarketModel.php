<?php

namespace web\api\model;

class MarketModel extends \web\common\model\BaseModel
{

    protected function _initialize()
    {
        $this->tableName = 'market';
    }

    /**
     * @param $coin_id
     */
    public function getCnyRateByCoinId($coin_id)
    {
        $data = $this->where("coin_id", $coin_id)->value("cny");
        return $data ? $data : 0;
    }
    /**
     * @param $coin_id
     */
    public function getUsdtRateByCoinId($coin_id)
    {
        $data = $this->where("coin_id", $coin_id)->value("close");
        return $data ? $data : 0;
    }

}

