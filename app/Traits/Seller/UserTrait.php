<?php

namespace App\Traits\Seller;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Language;
use App\Models\SellerLanguage;
use App\Models\SellerSearchAlgorithm;
use App\Models\DeliveryType;
use App\Models\SellerDeliveryMethod;
use App\Helpers\CurrencyHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SMSHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralNotification;
use App\Mail\PublicChangeApprovalNotification;
use App\Models\Seller;
use App\Models\SellerWorkInfo;
use App\Services\Transactions\Transaction;
use App\Services\Transactions\PTransaction;


trait UserTrait
{

    public function getMaximumProfiles()
    {
        $total = 0;

        if ($this->registrationType->name == 'Individual') {
            $total += Seller::MAX_SERVICE_PROFILES_INDIVIDUAL;
        } else {
            $total += Seller::MAX_SERVICE_PROFILES_CORPORATE;
        }
        if (!is_null($this->activeAPPSubscription)) {
            $total += Seller::MAX_APP_SERVICE_PROFILES;
        }

        return $total;
    }

    public function getMaximumPromoProfiles()
    {
        $total = 0;

        if ($this->registrationType->name == 'Individual') {
            $total += Seller::MAX_PROMO_SERVICE_PROFILES_INDIVIDUAL;
        } else {
            $total += Seller::MAX_PROMO_SERVICE_PROFILES_CORPORATE;
        }

        return $total;
    }
    
    public function canAddNewProfiles()
    {
        return $this->serviceInfoAdminsCount() < $this->getMaximumProfiles();
    }

    public function getCurrentOnboarding() {

        if($this->onboarding_complete3) {
            return Seller::ONBOARDING_PASSED;
        } else if ($this->onboarding_complete2) {
            return Seller::ONBOARDING_STAGE_3;
        } else if ($this->onboarding_complete) {
            return Seller::ONBOARDING_STAGE_2;
        } else {
            return Seller::ONBOARDING_STAGE_1;
        }
    }

    public function initDeliveryMethods($profile_id)
    {
        $deliveryTypes = DeliveryType::get();
        foreach ($deliveryTypes as $deliveryType) {
            $tempDM = new SellerDeliveryMethod();
            $tempDM->profile_id = $profile_id;
            $tempDM->seller_id = $this->id;
            $tempDM->delivery_method_id = $deliveryType->id;
            if ($deliveryType->name == 'Courier') {
                $tempDM->active = false;
            } else {
                $tempDM->active = true;
            }
            $tempDM->save();
        }
    }
    
    protected function initLanguage(){

        $lang = new SellerLanguage();
        $lang->seller_id = $this->id;
        $lang->language_id = Language::where('name', 'English')->first()->id;
        $lang->save();
    }


    public function sendGeneralMail($message, $title, $hasActionButton = false, $actionButtonText = '', $actionButtonRoute = '', $afterContent = null)
    {
        $subject = $this->username . ': ' . $title;
    
        // Mail::to($this->email)->queue((new GeneralNotification($subject, $message, $title, $hasActionButton, $actionButtonText, $actionButtonRoute, 'seller')) );
        Mail::to($this->email)->send((new GeneralNotification($subject, $message, $title, $hasActionButton, $actionButtonText, $actionButtonRoute, $afterContent)) );

    }

    public function sendGoPublicMail()
    {

        // $subject = 'Welcome to terawork.com';
        
        // Mail::to($this->email)->send((new PublicChangeApprovalNotification($subject, $this)) );

        $seller = $this;

        NotificationHelper::welcomeSeller($seller);
    }

    public function sendSMS($message)
    {
        if (isset($this->contactInfo->telephone_no) && strlen($this->contactInfo->telephone_no) && strlen($message)) {
            SMSHelper::sendSMS($message, $this->contactInfo->telephone_no);
        }
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

    public function setLastEditDate()
    {
        $this->updated_at = time();
        $this->admin_last_update = time();
        $this->save();
    }


    public function discipline($points)
    {
        $search = SellerSearchAlgorithm::find($this->search_info);
        if($search){
         
            $search->update(['disciplinary' => $search->disciplinary - $points]);
   
        }
    }

    public function unDiscipline($points)
    {
        $search = SellerSearchAlgorithm::find($this->search_info);
        $search->disciplinary += $points;
        if($search){
            $disciplinary = $search->disciplinary + $points;
            $disciplinary = $disciplinary > 0 ? 0 : $disciplinary;
            $search->update(['disciplinary' => $disciplinary]);
        }

    }

    public function fine($penaltyDebit)
    {
        $seller = $this;
        $transaction = new PTransaction(null, null, null, null, Transaction::TRANSACTION_TYPE_PENALTY);
        $transaction->setSWalletId($seller->wallet_id);
        $transaction->setDescription("Penalty");
        $transaction->setPenaltyDebit($penaltyDebit);
        $transaction->executeTransaction();
    }

    public function getSingleProfileApproval()
    {
        $sia = $this->serviceInfoAdmins;
        $approved = false;
        foreach ($sia as $s) {
            $approved = $approved || ($s->getOverallApproval() && $s->approved);
        }

        if ($this->registrationType == "Corporate") {
            $approved = $approved && $this->company_rc_no_approval;
        }
        return $approved && $this->paymentInfoAdmin->approved;
    }

    public function needAnyApproval()
    {
        $sia = $this->serviceInfoAdmins;
        $approved = false;
        foreach ($sia as $s) {
            $approved = $approved && ($s->getOverallApproval() && $s->approved);
        }

        $approved = $approved && $this->workInfoAdmin->summary_approved;

        if ($this->registrationType == "Corporate") {
            $approved = $approved && $this->company_rc_no_approval;
        }
        return !($approved && $this->paymentInfoAdmin->approved);
    }

    public function getPocAdminsApproval()
    {
        $approve = true;

        foreach ($this->sellerPocAdmins as $admin) {
            $approve = $approve && $admin->approved;
        }
        return $approve;
    }

    public function getOverallApproval()
    {
        $sia = $this->serviceInfoAdmins;
        $approved = true;
        foreach ($sia as $s) {
            $approved = $approved && $s->getOverallApproval() && $s->approved;
        }

        if ($this->registrationType == "Corporate") {
            $approved = $approved && $this->company_rc_no_approval;
        }
        return $approved && $this->paymentInfoAdmin->approved;
    }

    public function generateAdminOTP() {
        $this->admin_otp = Str::random(32);
        $this->admin_otp_use_no = 0;
        $this->update();
    }

    public function generateAuthKey()
    {
        $this->auth_key = Str::random(32);
    }

    public function requestOTP() {
        $this->generateAuthKey();
        $this->update();
        NotificationHelper::sellerAcceptAdminOTP($this);
    }

    public function toggleVacationMode()
    {
        $workInfo = SellerWorkInfo ::find($this->work_info);
        if($workInfo){
            $workInfo->vacation_mode = !((bool)$workInfo->vacation_mode);
            $workInfo->vacation_toggle_date = new \DateTime();
            $workInfo->update();
            return $workInfo;
        }

        return null;
    }

    public function isAvailable()
    {
        $seller = $this;
        $sellerFree = ($seller->workInfo->no_of_ongoing_work < $seller->workInfo->max_concurrent_work);
        //notice period validation was added to model validation rules
        $notOnVacation = !($seller->workInfo->vacation_mode);
        return ($sellerFree && $notOnVacation);
    }

}