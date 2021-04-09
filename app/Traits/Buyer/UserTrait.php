<?php

namespace App\Traits\Buyer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Language;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralNotification;
use App\Models\UserOnlineStatus;

trait UserTrait
{

    protected function initLanguage(){

        $this->language_id = Language::where('name', 'English')->first()->id;
    }

    public function initUserOnlineStatus(){
        $onlineStatus = UserOnlineStatus::where('username', $this->username)->first();
        if(!$onlineStatus){
            $onlineStatus = UserOnlineStatus::create([
                'username' => $this->username,
            ]);
        }
        return $onlineStatus;
    }


    public function sendGeneralMail($message, $title, $hasActionButton = false, $actionButtonText = '', $actionButtonRoute = '', $afterContent = null)
    {
        $subject = $this->username . ': ' . $title;
    
        Mail::to($this->email)->send((new GeneralNotification($subject, $message, $title, $hasActionButton, $actionButtonText, $actionButtonRoute, $afterContent)) );

    }

    public function displayInPreferredCurrency($amount)
    {
        return CurrencyHelper::display(CurrencyHelper::getAmountInLocale($amount, $this->preferedCurrency->locale), $this->preferedCurrency->locale);
    }


    public function displayCurrency($amount)
    {
        return CurrencyHelper::display($amount);
    }

    public function sendNotification($message, $title = null, $href = "#"){
        $this->notifications()->create([
            'title' => $title,
            'message' => $message,
            'href' => $href
        ]);
    }

}