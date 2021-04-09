<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Traits\APIResponseTrait;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    use APIResponseTrait;
    
	/**
     * @OA\Get(
     *      path="/api/topics",
     *      tags={"Topic"},
     *      summary="List Topics ",
     *      @OA\Response(
     *          response=200,
     *          description="Successful"
     *       ),
     *       @OA\Response(response=401, description="Unautorized"),
     *     )
     *
     */
    public function index(Request $request)
	{
        $subscriptions = Subscription::get();
		
		$groupTopics = $subscriptions->groupBy(function($grouped){
			return $grouped->topic;
		});

		$topics = array();

        foreach($groupTopics as $index => $filteredSubscriptions){
			
			$topics[] = array('name' => $index, 'no_of_subscriptions' => count($filteredSubscriptions));
		}

		return $this->success([
			"topics" => $topics,
		]);
	}


	/**
     * @OA\Get(
     *      path="/api/topics/{topic}",
     *      tags={"Topic"},
     *      summary="Get Topic and Subscribed urls ",
     *      @OA\Parameter(
	 *          name="topic",
	 *          description="Topic",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="string"
	 *          )
	 * 		),
     *      @OA\Response(
     *          response=200,
     *          description="Successful"
     *       ),
     *       @OA\Response(response=401, description="Unautorized"),
     *     )
     *
     */
    public function show(Request $request, $topic)
	{
        $subscriptions = Subscription::where("topic", $topic)->get(['topic', 'subscriber_url']);

		return $this->success([
			"topic" => $topic,
			"subscriptions" => $subscriptions
		]);
	}

}
