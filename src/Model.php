<?php
declare(strict_types=1);
namespace SilangSimplePHP;

use Illuminate\Database\Eloquent\Model as Eloquent_Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
// use Illuminate\Events\Dispatcher;
// use Illuminate\Container\Container;

/**
 * 
 * laravel orm model简单
 */
class Model extends Eloquent_Model
{
    //表格名
    public $table = "";
    //每页条数
    public $limit = 20;
    //指定数据库 database
    public $connection = '';
    //当前页数
    public $page = 1;
    //主键
    public $primaryKey = 'id';
    //数据库类型，暂时只支持mysql
    public $fields = '*';
    //表格数据
    public $attr;
    public $timestamps = false;
    public function __construct(array $attributes = [])
    {
        try{
            //自动效验表格名
            parent::__construct($attributes);
        }catch(\Exception $e)
        {
            throw new \PDOException($e->getMessage());
        }
    }

    public function recordError($e)
    {
        $sql = $e->getSql();
        $message = $e->getMessage();
        $filepath = $e->getFile();
        // $trace = $e->getTrace();
        \file_put_contents(PS_RUNTIME_PATH.'/sqlerror.txt',"sql:".$sql."|message:".$message."\r\n".$filepath,FILE_APPEND|LOCK_EX);
        throw $e;
    }


    /**
     * 获取指定sql一条数据
     */
    public function get_sql_one($sql)
    {
        try{
            $data = Capsule::connection($this->connection)->selectOne($sql);
            $data = json_decode(json_encode($data), true);
            return $data;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

    /**
     * 获取指定sql所有数据
     */
    public function get_sql_all($sql)
    {
        try{
            $data = Capsule::connection($this->connection)->select($sql);
            $data = json_decode(json_encode($data), true);
            return $data;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

    /**
     * 指定字段
     * @param string $fields
     * @return $this
     */
    public function field($fields = '*')
    {
        $this->fields = $fields;
        $this->fields = explode(",",$this->fields);
        if(count($this->fields) == 1)
        {
            $this->fields = $fields;
        }
        return $this;
    }

    /**
     * get_one
     */
    public function get_one($where = [])
    {
        try{
            $tmp = self::where($where)->select($this->fields)->first();
            $this->fields = '*';
            if($tmp)
            {
                $tmp = $tmp->toArray();
            }
            return $tmp;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

    /**
     * 返回所有数据
     */
    public function get_all($where = [])
    {
        try{
            // $tmp = parent::select($this->table_name,$this->fields,$where);
            $tmp = self::where($where)->select($this->fields)->get();
            $this->fields = '*';
            if($tmp)
            {
                $tmp = $tmp->toArray();
            }
            return $tmp;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

    /**
     * 列出列表
     */
    public function list($where = [])
    {
        $limit = [($this->page-1) * $this->limit,$this->limit];
        $where['LIMIT'] = $limit;
        $data = $this->get_all($where);
        unset($where['LIMIT']);
        $total = self::where($where)->count();
        return [
            'list' => $data,
            'total' => $total
        ];
    }

    /**
     * 插入新数据
     * @param $attrs
     */
    public function insert1($attrs = '')
    {
        if(empty($attrs) && !empty($this->attr) )
        {
            $attrs = $this->attr;
        }
        try{
            // insertGetId | insert
            $tmp = self::insertGetId($attrs);
            return $tmp;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

    /**
     * 更新数据
     * @param $attrs
     */
    public function update1($attrs,$where){
        try{
            //这个里where
            $data = self::where($where)->update($attrs);
            return $data;
            // return  $data->rowCount();
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }
    }

	/**
     * 执行sql
     * @param $attrs
     */
    public function query1($sql)
    {
        try{
            $result = Capsule::connection($this->connection)->statement($sql);
            return $result;
        }catch (QueryException $e) {
            $this->recordError($e);
            return false;
        }

    }

    /**
     * 删除数据
     * 只针对id处理
     * @param $id
     */
    public function delete1($id){
        try{
            $status = self::where(['id'=>$id])->delete();
            return $status;
        }catch (QueryException $e) {
            $this->recordError($e);
            throw $e;
        }

    }

    /**
     * 解释排序字段
     * game_id|ascend  字段|升降  ascend descend
     */
    public function orderField($sort_field = '')
    {
        $sort_field = explode("_",$sort_field);
        if(empty($sort_field) || !isset($sort_field['1']))
        {
            return '';
        }
        if($sort_field['1'] == 'ascend')
        {
            $sort_type = 'ASC';
        }else{
            $sort_type = 'DESC';
        }
        $order[$sort_field['0']] = $sort_type;
        return $order;
    }
}
