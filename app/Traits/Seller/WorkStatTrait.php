<?php

namespace App\Traits\Seller;

use App\Models\Seller;
use App\Models\SellerWorkInfo;
use App\Models\SellerStatisticInfo;
use App\Models\SellerTransaction;

trait WorkStatTrait
{
    
    public function updateLevel(){

        $level = $this->calculateLevel();
        $this->update([
            'level_score' => $level,
            'level_tag' => $this->getLevelTag($level)
        ]);
    }

    public function calculateLevel(){

        $reviewStat = $this->sellerPostWorkReviewStatInfo;
        $statisticInfo = $this->statisticInfo;

        $level = (($reviewStat->average_overall_score * $reviewStat->review_count) + (($statisticInfo->no_of_work_completed_successfully - $reviewStat->review_count) * 2.5) - ($statisticInfo->no_of_work_completed_unsuccessfully * 5)) * $this->getHPC() ;

        return $level;
    }

    public function getHPC(){
        $trans = SellerTransaction::where('transaction_type', 2)->where('s_wallet_id', $this->wallet_id)->orderBy('amount_credited_pfs_actual', 'desc')->first();
        if($trans){
            return $this->getHPCF($trans->amount_credited_pfs_actual);
        }
        return 1;
    }

    public function getLevelTag($level){
        
        $level_tag = Seller::LEVEL_1_TAG;

        if($level <= Seller::LEVEL_1_SCORE){

            $level_tag = Seller::LEVEL_1_TAG;

        }elseif ($level > Seller::LEVEL_1_SCORE && $level <= Seller::LEVEL_2_SCORE) {
        
            $level_tag = Seller::LEVEL_2_TAG;
        
        }elseif ($level > Seller::LEVEL_2_SCORE && $level <= Seller::LEVEL_3_SCORE) {
        
            $level_tag = Seller::LEVEL_3_TAG;
        
        }elseif ($level > Seller::LEVEL_3_SCORE && $level <= Seller::LEVEL_4_SCORE) {
        
            $level_tag = Seller::LEVEL_4_TAG;
        
        }elseif ($level > Seller::LEVEL_4_SCORE ) {

            $level_tag = Seller::LEVEL_5_TAG;
            
        }else{
            $level_tag = Seller::LEVEL_1_TAG;
            
        }

        return $level_tag;
    }

    

    public function getHPCF($HPC){
        
        $HPCF = Seller::HPCF_1;

        if($HPC <= Seller::HPC_1){

            $HPCF = Seller::HPCF_1;

        }elseif ($HPC > Seller::HPC_1 && $HPC <= Seller::HPC_2) {
        
            $HPCF = Seller::HPCF_2;
        
        }elseif ($HPC > Seller::HPC_2 && $HPC <= Seller::HPC_3) {
        
            $HPCF = Seller::HPCF_3;
        
        }elseif ($HPC > Seller::HPC_3 && $HPC <= Seller::HPC_4) {
        
            $HPCF = Seller::HPCF_4;

        }elseif ($HPC > Seller::HPC_4 && $HPC <= Seller::HPC_5) {
        
            $HPCF = Seller::HPCF_5;
        
        }elseif ($HPC > Seller::HPC_5 && $HPC <= Seller::HPC_6) {
        
            $HPCF = Seller::HPCF_6;
        
        }elseif ($HPC > Seller::HPC_6 ) {

            $HPCF = Seller::HPCF_7;
            
        }else{
            $HPCF = Seller::HPCF_1;
            
        }

        return $HPCF;
    }


    public function incrementOngoingWork()
    {
        $workInfo = SellerWorkInfo::find($this->work_info);
        $workInfo->no_of_ongoing_work = $workInfo->no_of_ongoing_work + 1;
        $workInfo->update();

        $statInfo = SellerStatisticInfo::find($this->statistic_info);
        $statInfo->no_of_ongoing_work = $workInfo->no_of_ongoing_work;
        $statInfo->update();
    }

    public function decrementOngoingWork()
    {
        $workInfo = SellerWorkInfo::find($this->work_info);
        $workInfo->no_of_ongoing_work = $workInfo->no_of_ongoing_work - 1;
        $workInfo->update();

        $statInfo = SellerStatisticInfo::find($this->statistic_info);
        $statInfo->no_of_ongoing_work = $workInfo->no_of_ongoing_work;
        $statInfo->free_cashout_count = $statInfo->free_cashout_count + 1;
        $statInfo->update();
    }


}