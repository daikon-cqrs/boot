<?php

declare(strict_types=1);

namespace Oroshi\Core\Middleware\Action;

use function GuzzleHttp\json_decode;
use Oroshi\Core\Middleware\ValidationInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Stringy\Stringy;

trait ValidatorTrait
{
    private function validateFields(array $fields, ServerRequestInterface $request, array &$errors): array
    {
        $output = [];
        $input = $this->getFields($this->getInput($request), $errors, $fields);
        foreach ($input as $fieldname => $rawInput) {
            $validationMethod = 'validate'.Stringy::create($fieldname)->toTitleCase();
            $validationCallback = [$this, $validationMethod];
            if (!is_callable($validationCallback)) {
                throw new RuntimeException("Missing required validation callback: $validationMethod");
            }
            $output[$fieldname] = call_user_func_array($validationCallback, [$rawInput, &$errors]);
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
                $errors[] = "Required input for field '$fieldname' is missing.";
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
            throw new RuntimeException('Failed to parse data from request body.');
        }
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