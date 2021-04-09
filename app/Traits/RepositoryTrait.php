<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Validator;
use Carbon\Carbon;
use App\Services\Calendar;
use App\Models\AdminAuditLog;

trait RepositoryTrait
{
    

    protected function getFilteredAttributes($attributes, $keys=[]){
        $modelAttributes = static::getModelAttributes($keys);
        $data = [];

        foreach ($modelAttributes as $value) {
            if(array_key_exists($value, $attributes) ){
                $data[$value] = $attributes[$value];
            }
        }

        return $data;

    }


    protected static function getModelAttributes($attributes = []){

        if(count($attributes) > 0){
            return $attributes;
        }

        if(isset(static::$modelAttributes)){

            return static::$modelAttributes;
        }
        

        return [];
    }


    protected function whereClause(&$query, $key, $value){
        switch($key){
            case 'name':
                    $query->where('name', 'like', '%'.$value.'%');                  
                return $query;
                break;
            case 'phone': 
                    $query->where('phone', 'like', '%'.$value.'%');
                return $query;
                break;
            case 'email': 
                    $query->where('email', 'like', '%'.$value.'%');
                return $query;
                break;
            default:
                    $query->where($key, 'like', '%'.$value.'%');
                return $query;
                break;
        }
    }

    protected function where(&$query, $key, $value){
        $query->where($key, '=', $value);                  
        return $query;
    
    }

    protected function orWhere(&$query, $key, $value){
        $query->orWhere($key, '=', $value);                  
        return $query;
        
    }

    protected function orWhereHas(&$query, $relation, $key, $value){
        $query->orWhereHas($relation, function($q) use($value, $key){
               $q->where($key,"like",$value);
            });
        return $query;
    }

    protected function whereHas(&$query, $relation, $key, $value){
        $query->whereHas($relation, function($q) use($value, $key){
               $q->where($key,"like",$value);
            });
        return $query;
    }


    static function logAction($admin_id, $action)
    {
        $newLog = new AdminAuditLog();
        $newLog->admin_id = $admin_id;
        $newLog->action = $action;
        $newLog->save();
    }
}