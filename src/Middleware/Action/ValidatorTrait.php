<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use function GuzzleHttp\json_decode;
use function GuzzleHttp\Psr7\parse_query;
use Assert\InvalidArgumentException;
use Assert\LazyAssertionException;
use Oroshi\Core\Middleware\ValidationInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stringy\Stringy;

trait ValidatorTrait
{
    private function validateFields(array $fields, ServerRequestInterface $request, array &$errors): array
    {
        $output = [];
        $input = $this->getFields($this->getInput($request), $errors, $fields);

        foreach ($input as $fieldname => $rawInput) {
            $validationMethod = 'validate'.Stringy::create($fieldname)->camelize()->toTitleCase();
            $validationCallback = [$this, $validationMethod];
            if (!is_callable($validationCallback)) {
                throw new \RuntimeException("Missing required validation callback: $validationMethod");
            }

            try {
                //Allow validation by assertion and transformation based on return
                $$output[$fieldname] = $validationCallback($fieldname, $rawInput, $errors);
            } catch(LazyAssertionException $exception) {
                foreach ($exception->getErrorExceptions() as $error) {
                    $errors[$error->getPropertyPath()][] = $error->getMessage();
                }
            } catch(InvalidArgumentException $error) {
                $errors[$error->getPropertyPath()][] = $error->getMessage();
            }
        }

        return $output;
    }

    private function getFields(array $input, array &$errors, array $fields, bool $required = true): array
    {
        $output = [];
        foreach ($fields as $fieldname) {
            if (isset($input[$fieldname])) {
                $output[$fieldname] = $input[$fieldname];
            } elseif ($required) {
                $errors['_'] = ["Required input for field '$fieldname' is missing."];
            }
        }
        return $output;
    }

    private function getInput(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (Stringy::create($contentType)->startsWith('application/json')) {
            $data = json_decode($request->getBody()->getContents(), true);
        } else {
            $data = $request->getParsedBody();
        }
        if (!is_array($data)) {
            throw new \RuntimeException('Failed to parse data from request body.');
        }

        $data = array_merge(parse_query($request->getUri()->getQuery()), $data);
        $trimStrings = function ($value) {
            if (is_string($value)) {
                return trim($value);
            }
            if (is_array($value)) {
                return array_map($trimStrings, $value);
            }
            return $value;
        };

        return $trimStrings($data);
    }
}
