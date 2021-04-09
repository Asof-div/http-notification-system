<?php

namespace App\Traits\Seller;

use Illuminate\Database\Eloquent\Model;
use App\Models\SellerWallet;
use App\Models\SellerTransaction;
use App\Models\SellerCommissionInfo;
use App\Helpers\NotificationHelper;
use App\Events\SellerCommissionChangeEvent;
use App\Models\SellerCommissionMatrix;
use App\Services\Transactions\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;

trait WalletTrait
{
    
    public function initWallet(){

        $wallet_id = $this->id - 210000000;
        $user_wallet = new SellerWallet;
        $user_wallet->id = $wallet_id;
        $user_wallet->currency_id = 1;
        $user_wallet->save();

        $this->wallet_id = $wallet_id;
        $this->save();
    }


    public function getLastTransaction()
    {
        return SellerTransaction::where('s_wallet_id', $this->wallet_id)->last();
    }

    public function getEarningsInLast60Days()
    {
        $time = new \DateTime('now');
        $time->sub(new \DateInterval('P60D'));
        $sixtyDaysAgo = $time->format('Y-m-d');
        $earnings = SellerTransaction::where('s_wallet_id', $this->wallet_id)
            ->whereDate('transaction_date', '>=', $sixtyDaysAgo)
            ->where('currency_id', $this->wallet->currency_id)
            ->where('transaction_type', Transaction::TRANSACTION_TYPE_PFS_ACTUAL)->get();
        $total = 0;
        foreach ($earnings as $tx) {
            $total += ($tx->amount_credited_pfs_actual / ((100 - $tx->percentage_commission) / 100));
        }
        return $total;
    }

    public function getNoOfSuccessfulTransactionsInLast60Days()
    {
        $time = new \DateTime('now');
        $time->sub(new \DateInterval('P60D'));
        $sixtyDaysAgo = $time->format('Y-m-d');
        $number = SellerTransaction::where('s_wallet_id', $this->wallet_id)
            ->where('currency_id', $this->wallet->currency_id)
            ->whereDate('transaction_date', '>=', $sixtyDaysAgo)
            ->where('transaction_type', Transaction::TRANSACTION_TYPE_PFS_ACTUAL)
            ->orWhere('transaction_type', Transaction::TRANSACTION_TYPE_REFUND_CW)->count();
        return $number;
    }


    public function resetWalletBalance() {

        $wallet = $this->wallet;
        $wallet->balance_available = 0.00;
        $wallet->update();
    }

    public function resetCommissionStatus(){

        $seller = $this;
        $sellerCommission = SellerCommissionInfo::find($seller->commission_info);

        if ($sellerCommission->percentage_commission < SellerCommissionInfo::BRONZE_COMMISSION) {
            $sellerCommission->change_direction = -1;
            // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::BRONZE_COMMISSION, SellerCommissionInfo::BRONZE_CAPS);
        }

        $sellerCommission->percentage_commission = SellerCommissionInfo::BRONZE_COMMISSION;
        $sellerCommission->commission_level = SellerCommissionInfo::BRONZE_CAPS;

        $now = new \DateTime('now');
        $now = $now->format('Y-m-d');
        $sellerCommission->date = $now;
        $sellerCommission->save();

        event(new SellerCommissionChangeEvent($this));
        
    }

    public function updateCommissionStatus()
    {
        try{

            $seller = $this;
            $currency = $this->wallet->currency;
            $sellerCommission = SellerCommissionInfo::find($seller->commission_info);
            $bronze = SellerCommissionMatrix::where('currency', $currency->short)->where('name', SellerCommissionInfo::BRONZE_CAPS)->first();
            $silver = SellerCommissionMatrix::where('currency', $currency->short)->where('name', SellerCommissionInfo::SILVER_CAPS)->first();
            $gold = SellerCommissionMatrix::where('currency', $currency->short)->where('name', SellerCommissionInfo::GOLD_CAPS)->first();
            $platinum = SellerCommissionMatrix::where('currency', $currency->short)->where('name', SellerCommissionInfo::PLATINUM_CAPS)->first();
            $diamond = SellerCommissionMatrix::where('currency', $currency->short)->where('name', SellerCommissionInfo::DIAMOND_CAPS)->first();

            if ($seller->getNoOfSuccessfulTransactionsInLast60Days() >= SellerCommissionInfo::DIAMOND_TX_LEVEL || ($diamond->lower_bound <= $seller->getEarningsInLast60Days() && $diamond->upper_bound >= $seller->getEarningsInLast60Days()) ) {

                if ($sellerCommission->percentage_commission > SellerCommissionInfo::DIAMOND_COMMISSION) {
                    $sellerCommission->change_direction = 1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::DIAMOND_COMMISSION, SellerCommissionInfo::DIAMOND_CAPS);
                
                } else {
                    $sellerCommission->change_direction = 0;
                }
                $sellerCommission->percentage_commission = SellerCommissionInfo::DIAMOND_COMMISSION;
                $sellerCommission->commission_level = SellerCommissionInfo::DIAMOND_CAPS;
            } else if ($seller->getNoOfSuccessfulTransactionsInLast60Days() >= SellerCommissionInfo::PLATINUM_TX_LEVEL || ($platinum->lower_bound <= $seller->getEarningsInLast60Days() && $platinum->upper_bound >= $seller->getEarningsInLast60Days()) ) {

                if ($sellerCommission->percentage_commission < SellerCommissionInfo::PLATINUM_COMMISSION) {
                    $sellerCommission->change_direction = -1;
                    
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::PLATINUM_COMMISSION, SellerCommissionInfo::PLATINUM_CAPS);
                
                } else if ($sellerCommission->percentage_commission > SellerCommissionInfo::PLATINUM_COMMISSION) {
                    $sellerCommission->change_direction = 1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::PLATINUM_COMMISSION, SellerCommissionInfo::PLATINUM_CAPS);
                
                } else {
                    $sellerCommission->change_direction = 0;
                }
                $sellerCommission->percentage_commission = SellerCommissionInfo::PLATINUM_COMMISSION;
                $sellerCommission->commission_level = SellerCommissionInfo::PLATINUM_CAPS;
            } else if ($seller->getNoOfSuccessfulTransactionsInLast60Days() >= SellerCommissionInfo::GOLD_TX_LEVEL || ($gold->lower_bound <= $seller->getEarningsInLast60Days() && $gold->upper_bound >= $seller->getEarningsInLast60Days()) ) {

                if ($sellerCommission->percentage_commission < SellerCommissionInfo::GOLD_COMMISSION) {
                    $sellerCommission->change_direction = -1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::GOLD_COMMISSION, SellerCommissionInfo::GOLD_CAPS);
                
                } else if ($sellerCommission->percentage_commission > SellerCommissionInfo::GOLD_COMMISSION) {
                    $sellerCommission->change_direction = 1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::GOLD_COMMISSION, SellerCommissionInfo::GOLD_CAPS);
                
                } else {
                    $sellerCommission->change_direction = 0;
                }
                $sellerCommission->percentage_commission = SellerCommissionInfo::GOLD_COMMISSION;
                $sellerCommission->commission_level = SellerCommissionInfo::GOLD_CAPS;
            } else if ($seller->getNoOfSuccessfulTransactionsInLast60Days() >= SellerCommissionInfo::SILVER_TX_LEVEL || ($silver->lower_bound <= $seller->getEarningsInLast60Days() && $silver->upper_bound >= $seller->getEarningsInLast60Days()) ) {

                if ($sellerCommission->percentage_commission < SellerCommissionInfo::SILVER_COMMISSION) {
                    $sellerCommission->change_direction = -1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::SILVER_COMMISSION, SellerCommissionInfo::SILVER_CAPS);
                
                } else if ($sellerCommission->percentage_commission > SellerCommissionInfo::SILVER_COMMISSION) {
                    $sellerCommission->change_direction = 1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::SILVER_COMMISSION, SellerCommissionInfo::SILVER_CAPS);
                
                } else {
                    $sellerCommission->change_direction = 0;
                }
                $sellerCommission->percentage_commission = SellerCommissionInfo::SILVER_COMMISSION;
                $sellerCommission->commission_level = SellerCommissionInfo::SILVER_CAPS;
            } else if ($seller->getNoOfSuccessfulTransactionsInLast60Days() >= SellerCommissionInfo::BRONZE_TX_LEVEL || ($bronze->lower_bound <= $seller->getEarningsInLast60Days() && $bronze->upper_bound >= $seller->getEarningsInLast60Days()) ) {

                if ($sellerCommission->percentage_commission < SellerCommissionInfo::BRONZE_COMMISSION) {
                    $sellerCommission->change_direction = -1;
                
                    // NotificationHelper::changeInCommissionLevel($seller, $sellerCommission->percentage_commission, $sellerCommission->getCommissionNameCaps(), SellerCommissionInfo::BRONZE_COMMISSION, SellerCommissionInfo::BRONZE_CAPS);
                
                }
                $sellerCommission->percentage_commission = SellerCommissionInfo::BRONZE_COMMISSION;
                $sellerCommission->commission_level = SellerCommissionInfo::BRONZE_CAPS;
            }

            $now = new \DateTime('now');
            $now = $now->format('Y-m-d');
            $sellerCommission->date = $now;
            $sellerCommission->save();

            event(new SellerCommissionChangeEvent($this));

        }catch(Exception $e){
            Log::info( ["msg" => "Unable to update seller commission", "seller_id" => $this->id, "Wallet_id" => $this->wallet_id, "Currency_id" => $this->wallet->currency_id] );
            report($e);
        }

    }

}