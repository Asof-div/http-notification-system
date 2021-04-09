<?php

namespace App\Traits\Seller;

use App\Services\ElasticSearches\ElasticSearch;
use App\Models\SellerServiceInfo;
use App\Models\SellerDeliveryMethod;
use App\Models\SellerSearchAlgorithm;
use App\Models\SellerServiceModule;
use App\Services\Currency\AccountCurrencyService;
use Illuminate\Support\Facades\Log;

trait SellerServiceInfoTrait
{


    public function reIndex()
    {
        $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);
        $profile = $this;

        $skills = [];
        /** SellerSkillSets $skill */
        foreach ($profile->skillSets as $skill) {
            $skills[] = $skill->skill;
        }

        $qualifications = [];
        /** SellerPocs $q */
        foreach ($profile->seller->sellerPocs as $q) {
            $qualifications[] = $q->qualification;
        }

        $dms = [];
        $sdms = SellerDeliveryMethod::where('seller_id', $profile->seller_id)
                ->where('profile_id', $profile->profile_id)->where('active', true)->get();
        /** SellerDeliveryMethod $dm */
        foreach ($sdms as $dm) {
            $dms[] = $dm->deliveryMethod->name;
        }

        $dis = [];
        foreach ($profile->sellerDeliveryInfos as $di) {
            $dis[] = $di->location;
        }

        $ls = [];
        foreach ($profile->seller->languages as $l) {
            $ls[] = $l->language->name;
        }

        $servicePackages = [];
        /** SellerServiceModules $m */
        foreach ($profile->sellerServiceModules as $m) {
            
            $local_price = $profile->currency && $profile->currency_id ? AccountCurrencyService::convertCurrency($m->standard_delivery_charge, $profile->currency->short, 'NGN') : "0.00";

            $servicePackages[]["description"] = $m->service_module_description;
            $servicePackages[]["unit_price"] = $m->standard_delivery_charge;
            $servicePackages[]["local_unit_price"] = "$local_price";
            $servicePackages[]["expr_delivery_charge"] = $m->express_delivery_charge;
            $servicePackages[]["delivery_time_in_hours"] = $m->standard_delivery_time;
            $servicePackages[]["expr_delivery_time_in_hours"] = $m->express_delivery_time;
        }

        $params = [
            'index' => 'services',
            'type' => 'public',
            'id' => $profile->seller->id . '-' . $profile->profile_id,
            'body' => [
                'cust_id' => $profile->seller->id,
                'avatar' => $profile->seller->avatar,
                'url_name' => $profile->seller->url_name,
                'summary' => $profile->seller->summary,
                'profile_id' => $profile->profile_id,
                'profile_status' => $profile->initial,
                'currency_id' => $profile->currency ? $profile->currency_id : null,
                'currency_name' => $profile->currency ? $profile->currency->short : null,
                'search_weight' => $profile->seller->searchAuthorityInfo->search_authority / 35,
                'search_premium_subscription' => $profile->seller->premium_service_subscriptions,
                'public_status' => (bool)$profile->seller->public_profile_status,
                'vacation_mode' => (bool)$profile->seller->workInfo->vacation_mode,
                "display_name" => $profile->seller->username,
                "idle_level" => $profile->seller->searchInfo->idle_level,
                "starting_from" => $profile->getMinimumPrice(),
                "category" => $profile->category->category,
                "category_description" => $profile->category->category_description,
                "service" => $profile->service->service,
                "service_unanalyzed" => $profile->service->service,
                "service_description" => $profile->service->service_description,
                "service_expression" => $profile->service->service_expression,
                'service_photo' => $profile->service_overview_photo . '-mini',
                "reg_type" => $profile->seller->registrationType->name,
                "status_tag" => $profile->seller->status_tag,
                "work_completed" => $profile->seller->statisticInfo->no_of_completed_work,
                "work_successfully_completed" => $profile->seller->statisticInfo->no_of_work_completed_successfully,
                "work_unsuccessfully_completed" => $profile->seller->statisticInfo->no_of_work_completed_unsuccessfully,
                "services_overview_title" => $profile->service_overview_header,
                "services_overview_body" => $profile->service_overview_body,
                "skills" => $skills,
                "location" => [
                    "country" => $profile->seller->contactInfo->country->name,
                    "city" => (isset($profile->seller->contactInfo->city) ? $profile->seller->contactInfo->city : null),
                    "area_of_city" => $profile->seller->contactInfo->area_of_city
                ],
                "qualifications" => $qualifications,
                "service_packages" => $servicePackages,
                "not_interested_in" => $profile->seller->workInfo->type_of_works_not_interested_in,
                "cancel_work" => (bool)$profile->seller->workInfo->cancel_work,
                // 'contact_me_before_order' => $profile->seller->workInfo->contact_me_before_order ? true : false,
                'quote_validity_period' => $profile->seller->workInfo->quote_validity_period,
                "available_delivery_mode" => $dms,
                "delivery_locations" => $dis,
                "payment_terms_accepted" => null, //not yet in use by elasticsearch
                "joined_since" => $profile->seller->created_at,
                "review_score" => $profile->seller->sellerPostWorkReviewStatInfo->average_overall_score,
                "level_score" => $profile->seller->level_score,
                "level_tag" => $profile->seller->level_tag,
                "no_of_reviews" => $profile->seller->sellerPostWorkReviewStatInfo->review_count,
                "notice_period_in_hours" => $profile->seller->workInfo->notice_period,
                "languages" => $ls,
                "community" => ($profile->seller->community_id != null)? $profile->seller->community->name : ""
            
            
            ]
        ];
        try{
            $response = $elasticSearch->getClient()->index($params);
        } catch(\Exception $e){
            Log::info($params);
            report($e);
        }
    }

    public function deleteIndex()
    {
        $profile = $this;
        $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);

        $params = [
            'index' => 'services',
            'type' => 'public',
            'id' => $profile->seller->id . '-' . $profile->profile_id,
        ];
        try {
            $elasticSearch->getClient()->delete($params);
        } catch (\Exception $e) {
            Log::info($params);
            report($e);                
        }

    }
    public function getMinimumPrice()
    {
        $array = SellerServiceModule::where('seller_id', $this->seller_id)
        ->where('profile_id', $this->profile_id)->get()->pluck('standard_delivery_charge')->toArray();
        $min = 10000000000000;
        if (sizeof($array) > 0) {
            foreach ($array as $a) {
                if ($min > $a) {
                    $min = $a;
                }
            }
            // if ($min < 2000) {
                // return 2000;
            // }
            return $min;
        } else return 2000;
    }

}