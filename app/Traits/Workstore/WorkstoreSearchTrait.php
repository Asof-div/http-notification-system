<?php

namespace App\Traits\Workstore;

use App\Services\Currency\AccountCurrencyService;
use App\Services\ElasticSearches\ElasticSearch;
use Illuminate\Support\Facades\Log;

trait WorkstoreSearchTrait
{

    public function reIndex()
    {
        try {

            $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);
            $work = $this;
            $local_price = $work->budget && $work->currency ? AccountCurrencyService::convertCurrency($work->budget, $work->currency->short, 'NGN') : "0.00";
            $params = [
                'index' => 'workstore',
                'type' => 'public',
                'id' => $work->message_id,
                'body' => [
                    'message_id' => $work->message_id,
                    'buyer_id' => $work->buyer_id,
                    'currency_id' => $work->currency_id,
                    'buyer_username' => $work->buyer->username,
                    'country' => $work->country ? $work->country->name : ($work->buyer->country ? $work->buyer->country->name : ''),
                    'custom_request' => $work->custom_request,
                    'work_start_date' => $work->work_start_date,
                    'category_id' => $work->category->id,
                    'category' => $work->category->category,
                    'service' => $work->service->service,
                    'avatar' => $work->buyer->logo_or_profile_pic,
                    'budget' =>  $work->budget ? $work->budget : "0.00",
                    'local_price' =>  $local_price,
                    "file" => $work->file,
                    "post_date" => $work->post_date,
                    "no_of_replies" => $work->no_of_replies,
                    "awarded" => $work->awarded,
                    "awarded_date" => $work->awarded_date,
                    "status" => $work->status,
                    'title' => $work->title,
                    'project_type' => $work->project_type,
                    'custom' => $work->custom,
                    'project_skills' => $work->project_skills,
                    'level_of_expertise' => $work->level_of_expertise,
                    'candidate_preference' => $work->candidate_preference,
                    "max_no_of_expected_response" => $work->max_no_of_expected_response,
                    "expected_no_freelancers_to_be_hired" => $work->expected_no_freelancers_to_be_hired,
                    "actual_no_freelancers_hired" => $work->actual_no_freelancers_hired,
                    "no_of_freelancers_failed_job" => $work->no_of_freelancers_failed_job,
                    "no_of_freelancers_completed_job" => $work->no_of_freelancers_completed_job,
                    "stage" => $work->stage
                ]
            ];

            $response = $elasticSearch->getClient()->index($params);
            return $response;
        
        } catch (\Exception $e) {
            report($e);                
        }
    }

    public function deleteIndex()
    {

        $elasticSearch = new ElasticSearch([env('ELASTIC_SEARCH_HOST')]);

        try {
            $params = [
                'index' => 'workstore',
                'type' => 'public',
                'id' => $this->message_id,
            ];
            $elasticSearch->getClient()->delete($params);

        } catch (\Exception $e) {
            report($e);                
        }
    
    }
}