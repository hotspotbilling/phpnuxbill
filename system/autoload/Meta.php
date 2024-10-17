<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * Meta is for save adtional information in database for every existing table
 *
 * * Good for Plugin, so plugin don't need to modify existing tables
 *
 * Example to put data
 * if plan need to add point value for loyalty poin
 * Meta::for("tbl_plans")->set(1, 'point', '24');
 * it means tbl_plans with id 1 have point value 24, customer will get 24 point for loyalty if buy plan with id 1
 * You need to create the logic for that, Meta only hold the data
 *
 * Example to get data
 * $point = Meta::for("tbl_plans")->get(1, 'point');
 * this will return the point value of plan with id 1
 *
 * to get all key value
 * $metas = Meta::for("tbl_plans")->getAll(1);
 *
 * to delete 1 data
 * Meta::for("tbl_plans")->delete(1, 'point');
 *
 * to delete all data
 * Meta::for("tbl_plans")->deleteAll(1);
 **/


class Meta
{
    protected $table = '';

    protected function __construct($table)
    {
        $this->table = $table;
    }

    public static function for($table)
    {
        return new self($table);
    }

    public function get($id, $key)
    {
        // get the Value
        return ORM::for_table('tbl_meta')
            ->select('value')
            ->where('tbl', $this->table)
            ->where('tbl_id', $id)
            ->where('name', $key)
            ->find_one()['value'];
    }

    public function getAll($id)
    {
        //get all key Value
        $metas = [];
        $result =  ORM::for_table('tbl_meta')
            ->select('name')
            ->select('value')
            ->where('tbl', $this->table)
            ->where('tbl_id', $id)
            ->find_array();
        foreach ($result as $value) {
            $metas[$value['name']] = $value['value'];
        }
        return $metas;
    }

    public function set($id, $key, $value = '')
    {
        $meta = ORM::for_table('tbl_meta')
            ->where('tbl', $this->table)
            ->where('tbl_id', $id)
            ->where('name', $key)
            ->find_one();
        if (!$meta) {
            $meta = ORM::for_table('tbl_meta')->create();
            $meta->tbl = $this->table;
            $meta->tbl_id = $id;
            $meta->name = $key;
            $meta->value = $value;
            $meta->save();
            $result = $meta->id();
            if ($result) {
                return $result;
            }
        } else {
            $meta->value = $value;
            $meta->save();
            return $meta['id'];
        }
    }

    public function delete($id, $key = '')
    {
        // get the Value
        return ORM::for_table('tbl_meta')
            ->select('value')
            ->where('tbl', $this->table)
            ->where('tbl_id', $id)
            ->where('name', $key)
            ->delete();
    }

    public function deleteAll($id)
    {
        //get all key Value
        return ORM::for_table('tbl_meta')
            ->select('value')
            ->where('tbl', $this->table)
            ->where('tbl_id', $id)
            ->delete_many();
    }
}
