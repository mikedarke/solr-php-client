<?php
/**
 * Created by PhpStorm.
 * User: mike.darke
 * Date: 05/04/2016
 * Time: 10:22
 */

namespace Darke\Solr\Exception;

use Psr\Http\Message\ResponseInterface;

class HttpException extends \Exception
{
    public function __construct(ResponseInterface $response) {
        parent::__construct("'{$response->getStatusCode()}' Status: {$response->getReasonPhrase()}", $response->getStatusCode());
    }
}