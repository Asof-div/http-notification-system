<?php
namespace App\Services\Publisher;

use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublisherService
{
    
    
    public static function doesTopicExists($topic)
    {
        return Subscription::where("topic", $topic)->count() > 0;
    }

    public static function sendMulticastMessage($sub_urls, $topic, $message)
    {
        $response_obj = [];
        foreach ($sub_urls as $url) {
            try {

                $response = Http::post($url, ['topic' => $topic, 'data' => $message]);

                $response_obj[] = [
                    "url" => $url,
                    "successful" => $response->successful(),
                    "status_code" => $response->status(),
                    "response_body" => $response->body(),
                ];
            } catch (Exception $ex) {
                Log::debug("Connection to $url failed due to ($ex->getMessage()) : \n\n($ex->getTraceAsString())");
                $response_obj[] = [ 
                    "url" => $url,
                    "message" => "Failed to connect to $url. ($ex->getMessage())"
                ];
            }
        }
        return $response_obj;
    }
    

}