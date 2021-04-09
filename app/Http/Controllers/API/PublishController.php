<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Publisher\PublisherService;
use App\Traits\APIResponseTrait;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    use APIResponseTrait;


    /**
     * @OA\Post(
     *      path="/api/publish/{topic}",
     *      tags={"Publish"},
     *      summary="Publish message to subscribers ",
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
     *                           "message":"hello",
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
    public function publish(Request $request, $topic)
	{

        if (empty($request->all())) {
			return $this->error("Cannot broadcast empty messages to subscribers. Set a valid broadcast message and try again", 401);
        }
        
		$data = $request->all();
		
        $topic_exists = PublisherService::doesTopicExists($topic);

        if (!$topic_exists) {
            return $this->error("The topic you want to publish to does not exist.", 400);
        }
        
        $subscriber_urls = Subscription::where("topic", $topic)->get(['subscriber_url'])->pluck('subscriber_url')->toArray();
        
        $response_sent = PublisherService::sendMulticastMessage($subscriber_urls, $topic, $data);

        return $this->success([
            "topic" => $topic,
            "broadcast_message" => $data,
            "subscribers" => $subscriber_urls,
            "publish_report" => $response_sent 
        ]);
		
	}

}
