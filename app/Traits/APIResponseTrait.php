<?php

namespace App\Traits;
use App\Services\Response\APIResponse;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Illuminate\Support\Facades\Validator;

trait APIResponseTrait
{
    
    public function success(array $data, $code = 200)
    {
        
        $response = (new APIResponse)->success($data);

        return response()->json($response, $code);

    }

    public function error($message, $code = 422)
    {
        
        $response = (new APIResponse)->error($message);

        return response()->json($response, $code);

    }


    public function handleValidation($data, $customMessages=[]){

        $validator = Validator::make(request()->all(), $data, $customMessages);

        if ($validator->fails()){
        
            $response = $validator->errors();
            return $this->error($response, 422);
        }
        
    }

    public function notFoundResponse($msg=null){
        $message = $msg ? $msg : $this->splitWords(static::getModelName()).' details not found.';
        return $this->error($message , 404);
    }


    public function handleArchiveResponse(){

        return $this->success([
            'msg' => $this->splitWords(static::getModelName()).' successfully archived.'
        ]);

    }

    public function handleRestoreResponse(){

        return $this->success([
            'msg' => $this->splitWords(static::getModelName()).' successfully restored.'
        ]);

    }

    public function handleDeleteResponse(){

        return $this->success([
            'msg' => $this->splitWords(static::getModelName()).' successfully deleted.'
        ]);

    }

    public function handleShowResponse($model, $response = null){

        if (!$model) {
            return $this->notFoundResponse();
        }

        if($response){
            return $this->success($response);
        }

        return $this->success([$this->toCamelCase(static::getModelName()) => $model]);

    }

    public function handleUpdateResponse(array $data = []){
        
        $data['msg'] = $this->splitWords(static::getModelName()).' successfully updated.';
        
        return $this->success($data);
    }

    public function handleAddResponse(array $data = []){
        $data['msg'] = $this->splitWords(static::getModelName()).' successfully added.';
        
        return $this->success($data);
    }

    protected static function getModelName(){

        if(isset(static::$modelName)){

            return static::$modelName;
        }
        $subject = (new ReflectionClass(get_called_class()))->getShortName();

        return strstr($subject, 'Controller', true);
    }



    protected function toCamelCase($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }


    protected function splitWords($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        return implode(' ', $ret);
    }

    protected function getFilteredAttributes($attributes, $keys){
        $modelAttributes = $keys;
        $data = [];

        foreach ($modelAttributes as $value) {
            if(array_key_exists($value, $attributes) ){
                $data[$value] = $attributes[$value];
            }
        }

        return $data;

    }


}