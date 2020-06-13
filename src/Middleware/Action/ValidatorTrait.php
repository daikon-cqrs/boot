<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Middleware\Action;

use Daikon\Interop\Assertion;
use Daikon\Interop\LazyAssertionException;
use Daikon\Interop\RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Stringy\Stringy;

trait ValidatorTrait
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface
    {
        $prevPayload = (array)$request->getAttribute($this->payload);
        $prevErrors = (array)$request->getAttribute($this->exportErrors);
        $errorCode = $request->getAttribute($this->exportErrorCode);
        $errors = $prevErrors;
        // @todo better handle INFO/SUCCESS severity

        $queryParams = [];
        parse_str($request->getUri()->getQuery(), $queryParams);
        $payload = array_merge(
            //@todo parse request headers
            $this->parseBody($request),
            $prevPayload,
            $queryParams,
            $request->getAttributes()
        );

        $exports = $this->validateInput(
            $payload,
            $this->input,
            $this->export,
            $this->default,
            $this->required,
            $this->import ?? [],
            $errors,
            $errorCode
        );

        if ($errors === $prevErrors || $this->severity < ValidatorInterface::SEVERITY_SUCCESS) {
            $request = $request->withAttribute($this->payload, array_merge_recursive($prevPayload, $exports));
        }

        if ($errors !== $prevErrors && $this->severity > ValidatorInterface::SEVERITY_SUCCESS) {
            $request = $request->withAttribute($this->exportErrors, $errors)
                ->withAttribute($this->exportErrorSeverity, $this->severity)
                ->withAttribute($this->exportErrorCode, $errorCode ?? ValidatorInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $request;
    }

    /**
     * @param mixed $export
     * @param mixed $default
     */
    private function validateInput(
        array $payload,
        string $input,
        $export,
        $default,
        bool $required,
        array $import,
        array &$errors,
        ?int &$errorCode
    ): array {
        if (!array_key_exists($input, $payload)) {
            if ($required === true) {
                $errors['_'][] = "Required input for '$input' is missing.";
            }
            return !is_null($default) ? [($export ?? $input) => $default] : [];
        }

        $import = array_intersect_key($payload, array_flip($import));
        $result = $this->executeValidator($input, $payload[$input], $errorCode, $errors, $import);
        return $export !== false ? [($export ?? $input) => $result] : [];
    }

    /**
     * @param mixed $input
     * @return null|mixed
     */
    private function executeValidator(string $name, $input, ?int &$errorCode, array &$errors, array $import)
    {
        $validationMethod = 'validate'.Stringy::create($name)->upperCamelize();
        $validationCallback = [$this, $validationMethod];
        $validationCallback = is_callable($validationCallback) ? $validationCallback : [$this, 'validate'];

        if (!is_callable($validationCallback)) {
            throw new RuntimeException("Missing required validation method 'validate' or '$validationMethod'.");
        }

        try {
            //Allow validation by assertion and transformation based on return
            //otherwise set explicitly set errors and errorCode in validation method
            $output = $validationCallback($name, $input, $errorCode, $errors, $import);
        } catch (LazyAssertionException $exception) {
            foreach ($exception->getErrorExceptions() as $error) {
                $errors[$name][] = $error->getMessage();
            }
        } catch (InvalidArgumentException $error) {
            $errors[$name][] = $error->getMessage();
        }

        return $output ?? null;
    }

    private function parseBody(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (strpos(trim($contentType), 'application/json') === 0) {
            $data = json_decode((string)$request->getBody(), true);
        } else {
            $data = $request->getParsedBody();
        }

        Assertion::nullOrIsArray($data, 'Failed to parse data from request body.');

        return (array)$data;
    }
}
