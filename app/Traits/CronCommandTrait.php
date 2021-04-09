<?php

namespace App\Traits;;

use App\Services\Admin\SendMailService;
use ReflectionClass;

trait CronCommandTrait
{
    
    protected static function sendCronStatus($title=null, $file=null){

        $subject = (new ReflectionClass(get_called_class()))->getShortName();
        $title = $title ? $title : $subject. " successfully completed at " . date('Y-m-d H:i:s');
        SendMailService::sendCronStatus($title, $file, $subject);
    }
        
    protected static function sendCashoutFile($subject, $attachments){

        SendMailService::sendCashoutFile($subject, $attachments);
    }


}