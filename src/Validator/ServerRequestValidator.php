<?php declare(strict_types=1);

namespace Daikon\Boot\Validator;

use Daikon\Boot\Middleware\ActionHandler;
use Daikon\Boot\Middleware\ResolvesDependency;
use Daikon\Interop\Assertion;
use Daikon\Interop\AssertionFailedException;
use Daikon\Interop\InvalidArgumentException;
use Daikon\Interop\LazyAssertionException;
use Daikon\Interop\RuntimeException;
use Daikon\Validize\Validation\ValidationIncident;
use Daikon\Validize\Validation\ValidationReport;
use Daikon\Validize\Validation\ValidatorDefinition;
use Daikon\Validize\Validator\ValidatorInterface;
use Daikon\Validize\ValueObject\Severity;
use DomainException;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestValidator implements ValidatorInterface, StatusCodeInterface
{
    use ResolvesDependency;

    private ContainerInterface $container;

    private ValidationReport $validationReport;

    private array $validatorDefinitions = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->validationReport = new ValidationReport;
    }

    /** @return array */
    public function __invoke(ValidatorDefinition $requestValidatorDefinition)
    {
        $request = $requestValidatorDefinition->getArgument();
        Assertion::isInstanceOf($request, ServerRequestInterface::class);
        if (!empty($payload = $request->getAttribute(ActionHandler::ATTR_PAYLOAD, []))) {
            throw new RuntimeException('Action payload already exists.');
        }

        $queryParams = [];
        parse_str($request->getUri()->getQuery(), $queryParams);
        $source = array_merge($queryParams, $request->getParsedBody(), $request->getAttributes());

        /**
         * @var string $implementor
         * @var ValidatorDefinition $validatorDefinition
         */
        foreach ($this->validatorDefinitions as list($implementor, $validatorDefinition)) {
            $path = $validatorDefinition->getPath();
            $severity = $validatorDefinition->getSeverity();
            $settings = $validatorDefinition->getSettings();
            try {
                // Check dependents are executed
                if (array_key_exists('depends', $settings)) {
                    foreach ((array)$settings['depends'] as $depends) {
                        if (!$this->validationReport->isProvided($depends)) {
                            throw new DomainException("Dependent validator '$depends' not provided.");
                        }
                    }
                }
                // Check imports set
                if (array_key_exists('import', $settings)) {
                    foreach ((array)$settings['import'] as $import) {
                        Assertion::keyExists($payload, $import, "Missing required import '$import'.");
                        $validatorDefinition = $validatorDefinition->withImport($import, $payload[$import]);
                    }
                }
                // Check argument set
                if (!array_key_exists($path, $source)) {
                    if ($settings['required'] ?? true) {
                        throw new InvalidArgumentException('Missing required input.');
                    } else {
                        $result = $settings['default'] ?? null; // Default value infers success
                    }
                } else {
                    // Run validation
                    $validator = $this->resolveValidator($this->container, $implementor, $validatorDefinition);
                    $result = $validator($validatorDefinition->withArgument($source[$path]));
                }
                // Export result
                if ($settings['export'] ?? true) {
                    $payload = array_merge_recursive($payload, [($settings['export'] ?? $path) => $result]);
                }

                $incident = new ValidationIncident($validatorDefinition, Severity::success());
                $this->validationReport = $this->validationReport->push($incident);
            } catch (DomainException $error) {
                $incident = new ValidationIncident($validatorDefinition, Severity::unprocessed());
                $this->validationReport = $this->validationReport->push($incident->addMessage($error->getMessage()));
            } catch (AssertionFailedException $error) {
                if ($severity->isLessThanOrEqual(Severity::silent())) {
                    continue;
                }
                $incident = new ValidationIncident($validatorDefinition, $severity);
                switch (true) {
                    case $error instanceof LazyAssertionException:
                        /** @var LazyAssertionException $error */
                        foreach ($error->getErrorExceptions() as $exception) {
                            $incident = $incident->addMessage($exception->getMessage());
                        }
                        break;
                    default:
                        $incident = $incident->addMessage($error->getMessage());
                }
                $this->validationReport = $this->validationReport->push($incident);
                if ($severity->isCritical()) {
                    break;
                }
            }
        }

        // Handle request validator reporting
        if (!$this->validationReport->getErrors()->isEmpty()) {
            $severity = $requestValidatorDefinition->getSeverity();
            if ($severity->isGreaterThanOrEqual(Severity::notice())) {
                $incident = new ValidationIncident($requestValidatorDefinition, $severity);
                $this->validationReport = $this->validationReport->unshift(
                    $incident->addMessage('Request validator reports errors.')
                );
                if ($severity->isCritical()) {
                    throw new InvalidArgumentException;
                }
            }
        } else {
            $this->validationReport = $this->validationReport->unshift(
                new ValidationIncident($requestValidatorDefinition, Severity::success())
            );
        }

        return $payload;
    }

    public function getValidationReport(): ValidationReport
    {
        return $this->validationReport;
    }

    public function critical(string $path, string $validator, array $settings = []): self
    {
        return $this->register(Severity::critical(), $path, $validator, $settings);
    }

    public function error(string $path, string $validator, array $settings = []): self
    {
        return $this->register(Severity::error(), $path, $validator, $settings);
    }

    public function notice(string $path, string $validator, array $settings = []): self
    {
        return $this->register(Severity::notice(), $path, $validator, $settings);
    }

    public function silent(string $path, string $validator, array $settings = []): self
    {
        return $this->register(Severity::silent(), $path, $validator, $settings);
    }

    private function register(Severity $severity, string $path, string $implementor, array $settings): self
    {
        $validatorDefinition = new ValidatorDefinition($path, $severity, $settings);
        $this->validatorDefinitions[] = [$implementor, $validatorDefinition];
        return $this;
    }

    private function resolveValidator(
        ContainerInterface $container,
        string $implementor,
        ValidatorDefinition $validatorDefinition
    ): ValidatorInterface {
        $dependency = [$implementor, [':validatorDefinition' => $validatorDefinition]];
        /** @var ValidatorInterface $validator */
        $validator = $this->resolve($container, $dependency, ValidatorInterface::class);
        return $validator;
    }
}
