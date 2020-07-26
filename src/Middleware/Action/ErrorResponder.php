<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Middleware\ActionHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ErrorResponder extends Responder
{
    public function respondToJson(ServerRequestInterface $request): ResponseInterface
    {
        $message = $request->getAttribute(ActionHandler::ATTR_STATUS_MESSAGE, 'Internal server error.');
        $statusCode = $request->getAttribute(ActionHandler::ATTR_STATUS_CODE, self::STATUS_INTERNAL_SERVER_ERROR);
        $errors = $request->getAttribute(ActionHandler::ATTR_ERRORS, []);

        return new JsonResponse(
            ['message' => $message] + (!empty($errors) ? ['errors' => $errors] : []),
            $statusCode
        );
    }
}
