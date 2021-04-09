<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Traits\APIResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    use APIResponseTrait;
    
    /**
     * @OA\Get(
     *      path="/api/subscriptions",
     *      tags={"Subscription"},
     *      summary="List Subscribed urls ",
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

		return $this->success([
			"subscriptions" => $subscriptions,
		]);
	}


    /**
     * @OA\Get(
     *      path="/api/subscriptions/{id}",
     *      tags={"Subscription"},
     *      summary="Get Topic and Subscribed urls ",
     *      @OA\Parameter(
	 *          name="id",
	 *          description="Subscription id",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="integer"
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
    public function show(Request $request, $id)
	{
        
		$subscription = Subscription::find($id);

        if($subscription){
            return $this->notFoundResponse();
        }

		return $this->success([
			"subscription" => $subscription,
		]);
	}


    /**
     * @OA\Post(
     *      path="/api/subscribe/{topic}",
     *      tags={"Subscription"},
     *      summary="Subscribe your URL for a topic notification ",
	 *	  	@OA\Parameter(
	 *          name="topic",
	 *          description="Topic to subscribe to",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="string"
	 *          )
	 * 		),
	 *	  	@OA\RequestBody(
	 * 			@OA\JsonContent(
     *                 example= {   
     *                           "url":"http://localhost:9000/test1",
     *                          }
     *             )
	 *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful"
     *       ),
     *       @OA\Response(response=401, description="Unautorized"),
     *     )
     *
     */
	public function subscribe(Request $request, $topic)
	{
        $validator = $this->handleValidation([
            'url' => ['required', Rule::unique('subscriptions', 'subscriber_url')->where(function ($query) use ($topic){
                return $query->where('topic', $topic);
            })],
        ]);

        if ($validator) {
            return $validator;
        }
        
		$subscription = Subscription::create([
            'topic' => $topic,
            'subscriber_url' => $request->url
        ]);

		return $this->success([
			"message" => "you have successfully subscribed your url: $request->url to the topic: $request->topic",
			"url" => $subscription->subscriber_url,
            "topic" => $subscription->topic
		]);
	}


    /**
     * @OA\Post(
     *      path="/api/update-subscription/{topic}/{id}",
     *      tags={"Subscription"},
     *      summary="Subscribe your URL for a topic notification ",
	 *	  	@OA\Parameter(
	 *          name="topic",
	 *          description="Topic to subscribe to",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="string"
	 *          )
	 * 		),
	 *	  	@OA\Parameter(
	 *          name="id",
	 *          description="subscription id",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="integer"
	 *          )
	 * 		),
	 *	  	@OA\RequestBody(
	 * 			@OA\JsonContent(
     *                 example= {   
     *                           "url":"http://localhost:9000/test1",
     *                          }
     *             )
	 *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful"
     *       ),
     *       @OA\Response(response=401, description="Unautorized"),
     *     )
     *
     */
    public function update(Request $request, $topic, $id)
	{
        $validator = $this->handleValidation([
            'url' => ['required', Rule::unique('subscriptions', 'subscriber_url')->where(function ($query) use ($topic){
                return $query->where('topic', $topic);
            })->ignore($id)],
        ]);

        if ($validator) {
            return $validator;
        }
        
        
		$subscription = Subscription::find($id);
        
        if(!$subscription){
            return $this->notFoundResponse();
        }

        $subscription->update([
            'topic' => $topic,
            'subscriber_url' => $request->url
        ]);

		return $this->success([
			"message" => "you have successfully updated your subscription",
			"subscription" => $subscription,
		]);
	}


    /**
     * @OA\Delete(
     *      path="/api/unsubscribe/{id}",
     *      tags={"Subscription"},
     *      summary="Unsubscribe ",
	 *	  	@OA\Parameter(
	 *          name="id",
	 *          description="subscription id",
	 *          required=true,
	 *			in="path",
	 *          @OA\Schema(
	 *              type="integer"
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
    public function unsubscribe(Request $request, $id)
	{

		$subscription = Subscription::find($id);
        
        if(!$subscription){
            return $this->notFoundResponse();
        }

        $subscription->delete();

		return $this->success([
			"message" => "you have successfully deleted your subscription"
		]);
	}


}
