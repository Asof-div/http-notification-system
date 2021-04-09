<?php

namespace App\Traits\Buyer;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuyerWallet;
use App\Models\GiftCard;
use App\Models\DiscountCodeUsage;
use App\Models\BuyerPrimaryWalletTransaction;
use App\Helpers\NotificationHelper;
use App\Models\Buyer;
use App\Models\BuyerWalletTransaction;
use DateTime;

trait WalletTrait
{
    
    public function initWallet(){

        $wallet_id = $this->id - 210000000;
        $user_wallet = new BuyerWallet;
        $user_wallet->id = $wallet_id;
        $user_wallet->currency_id = 1;
        $user_wallet->save();

        $this->wallet_id = $wallet_id;
        $this->save();
    }

    public function canDebit($amount) {
        return $this->wallet_balance >= $amount;
    }

    public function credit($amount) {
        $this->wallet_balance += $amount;
        $this->save();
        $this->logTransaction($amount, Buyer::CREDIT );
    }

    public function debit($amount) {
        $this->wallet_balance -= $amount;
        $this->save();
        $this->logTransaction($amount, Buyer::DEBIT );
    }

    public function creditViaGiftCard($code) {
        $gc = GiftCard::where('voucher', $code)->where('status', GiftCard::STATUS_APPROVED)->first();

        if ($gc != null && $gc->isValid()) {
            $oldbalance = $this->wallet_balance;
            $this->credit($gc->reward);
            $desc = "Credit received via GiftCard Voucher!";
            $amount = $gc->reward;
            $newBalance = $oldbalance + $amount;
            $this->walletTransactionLog(BuyerWalletTransaction::CREDIT_TYPE, $desc, $amount, $newBalance);
            $gc->load($this);

            NotificationHelper::buyerPrimaryWalletCredit($this, "Credit From GiftCard", $gc->reward);
            return true;
        }
        return false;
    }

    public function creditViaSellerWallet($amount) {

        $oldbalance = $this->wallet_balance;
        $desc = "Credit received via Seller Wallet Transfer!";
        $newBalance = $oldbalance + $amount;
        $this->credit($amount);
        $this->walletTransactionLog(BuyerWalletTransaction::CREDIT_TYPE, $desc, $amount, $newBalance);
        NotificationHelper::buyerPrimaryWalletCredit($this, "Transfer from your Seller Wallet", $amount);
    }

    public function creditViaRefund($amount) {

        $oldbalance = $this->wallet_balance;
        $desc = "Credit received via Job Refund!";
        $newBalance = $oldbalance + $amount;
        $this->credit($amount);
        $this->walletTransactionLog(BuyerWalletTransaction::CREDIT_TYPE, $desc, $amount, $newBalance);
        NotificationHelper::buyerPrimaryWalletCredit($this, "Refund", $amount);
    }

    public function debitViaOrder($amount) {

        $oldbalance = $this->wallet_balance;
        $desc = "Debit received via Job order!";
        $newBalance = $oldbalance - $amount;
        $this->debit($amount);
        $this->walletTransactionLog(BuyerWalletTransaction::DEBIT_TYPE, $desc, $amount, $newBalance);
        NotificationHelper::buyerPrimaryWalletDebit($this, "Order", $amount);
    }


    public function resetWalletBalance() {

        $oldbalance = $this->wallet_balance;
        $amount = $oldbalance;
        $desc = "Debit received via wallet balance reset!";
        $newBalance = $oldbalance - $amount;
        $this->debit($amount);
        $this->walletTransactionLog(BuyerWalletTransaction::DEBIT_TYPE, $desc, $amount, $newBalance);
        NotificationHelper::buyerPrimaryWalletDebit($this, "Wallet balance reset", $amount);
    }

    public function walletTransactionLog($type, $description, $amount, $balance){


        BuyerWalletTransaction::create([
            'buyer_id' => $this->id,
            'type' => $type,
            'currency_id' => $this->wallet->currency_id,
            'description' => $description,
            'amount' => $amount,
            'balance' => $balance,
            'transaction_date' => new DateTime(),
        ]);

    }

    public function logTransaction($amount, $type) {
        BuyerPrimaryWalletTransaction::create([     
            'buyer_id' => $this->id,
            'amount' => $amount,
            'type' => $type,
        ]);
    }

}