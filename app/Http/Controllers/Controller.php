<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;


/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="HTTP Notification System API",
 *      description="L5 Swagger OpenApi description",
 *      @OA\Contact(
 *          email="aadeyinkab24@gmail.com"
 *      )
 * )
 */

/**
 *  @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="HTTP Notification System API Server"
 *  )
 *
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
