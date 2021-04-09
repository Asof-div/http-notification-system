<?php

namespace App\Traits\Seller;

use App\Models\SellerDeliveryMethod;
use App\Services\ElasticSearches\ElasticSearch;
use App\Models\SellerPromoServiceInfo;
use App\Models\SellerSearchAlgorithm;
use App\Services\Currency\AccountCurrencyService;
use Illuminate\Support\Facades\Log;

trait SellerPromoSearchTrait
{


    public function rePromoIndex()
    {
        $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);
        $profiles = $this->promoServiceInfos;
        
        foreach ($profiles as $profile) {
            
            $qualifications = [];
            foreach ($profile->seller->sellerPocs as $q) {
                $qualifications[] = $q->qualification;
            }

            $dms = [];
            $sdms = SellerDeliveryMethod::where('seller_id', $profile->seller_id)
                ->where('profile_id', $profile->profile_id)->where('active', true)->get();
            foreach ($sdms as $dm) {
                $dms[] = $dm->deliveryMethod->name;
            }

            $dis = [];
            foreach ($profile->promoDeliveryInfos as $di) {
                $dis[] = $di->location;
            }

            $ls = [];
            foreach ($profile->seller->languages as $l) {
                $ls[] = $l->language->name;
            }



            $servicePackages = [];
            foreach ($profile->promoServiceModules as $m) {
            
                $local_price = $profile->currency && $profile->currency_id ? AccountCurrencyService::convertCurrency($m->delivery_charge, $profile->currency->short, 'NGN') : "0.00";
                
                $servicePackages[]["description"] = $m->service_module_description;
                $servicePackages[]["unit_price"] = $m->delivery_charge;
                $servicePackages[]["local_unit_price"] = "$local_price";
                $servicePackages[]["percentage_discount"] = $m->percentage_discount;
                $servicePackages[]["delivery_time_in_hours"] = $m->deliveryTimeInHours();
            }

            $params = [
                'index' => 'promo_services',
                'type' => 'public',
                'id' => $profile->seller->id . '-' . $profile->promo_profile_id,
                'body' => [
                    'cust_id' => $profile->seller->id,
                    'avatar' => $profile->seller->avatar,
                    'summary' => $profile->seller->summary,
                    'url_name' => $profile->seller->url_name,
                    'profile_id' => $profile->profile_id,
                    'promo_id' => $profile->promo_id,
                    'promo_title' => $profile->promo ? $profile->promo->title : '',
                    'promo_profile_id' => $profile->promo_profile_id,
                    'currency_id' => $profile->currency ? $profile->currency_id : null,
                    'currency_name' => $profile->currency ? $profile->currency->short : null,
                    'profile_status' => $profile->initial,
                    'search_weight' => $profile->seller->searchAuthorityInfo->search_authority / SellerSearchAlgorithm::ELASTIC_NORMALIZER,
                    'search_premium_subscription' => $profile->seller->premium_service_subscriptions,
                    'public_status' => (bool)$profile->seller->public_profile_status,
                    'vacation_mode' => (bool)$profile->seller->workInfo->vacation_mode,
                    "url_name" => $profile->seller->url_name,
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
                    "location" => [
                        "country" => $profile->seller->contactInfo->country->name,
                        "city" => (isset($profile->seller->contactInfo->city) ? $profile->seller->contactInfo->city : null),
                        "area_of_city" => $profile->seller->contactInfo->area_of_city
                    ],
                    "qualifications" => $qualifications,
                    "service_packages" => $servicePackages,
                    "not_interested_in" => $profile->seller->workInfo->type_of_works_not_interested_in,
                    "cancel_work" => (bool)$profile->seller->workInfo->cancel_work,
                    'contact_me_before_order' => (bool)$profile->seller->workInfo->contact_me_before_order,
                    'quote_validity_period' => $profile->seller->workInfo->quote_validity_period,
                    "available_delivery_mode" => $dms,
                    "delivery_locations" => $dis,
                    "payment_terms_accepted" => null, //not yet in use by elasticsearch
                    "joined_since" => $profile->seller->created_at,
                    "review_score" => $profile->seller->sellerPostWorkReviewStatInfo->average_overall_score,
                    "no_of_reviews" => $profile->seller->sellerPostWorkReviewStatInfo->review_count,
                    "level_score" => $profile->seller->level_score,
                    "level_tag" => $profile->seller->level_tag,
                    "notice_period_in_hours" => $profile->seller->workInfo->notice_period,
                    "languages" => $ls,
                    "community" => ($profile->seller->community_id != null)? $profile->seller->community->name : ""
                ]
            ];

            try {
                $response = $elasticSearch->getClient()->index($params);
            } catch (\Exception $e) {
                Log::info($params);
                report($e);
                
            }
        }
    }

    public function deletePromoIndex()
    {
        $profiles = $this->serviceInfos;
        $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);

        foreach ($profiles as $profile) {
            $params = [
                'index' => 'promo_services',
                'type' => 'public',
                'id' => $profile->seller->id . '-' . $profile->promo_profile_id,
            ];
            try {
                $elasticSearch->getClient()->delete($params);
            } catch (\Exception $e) {
                Log::info($params);
                report($e);
            }
        }
    }

}