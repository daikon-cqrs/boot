<?php declare(strict_types=1);

namespace Daikon\Boot\Middleware;

use Daikon\Boot\Middleware\ActionHandler;
use Daikon\Boot\Middleware\ResolvesDependency;
use Daikon\Interop\AssertionFailedException;
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

final class ValidationService implements ValidatorInterface, StatusCodeInterface
{
    use ResolvesDependency;

    public const ATTR_VALIDATION_REPORT = '_validation_report';

    private ContainerInterface $container;

    /** @var ValidatorDefinition[] */
    private array $definitions = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function critical(string $argument, string $validator, array $settings = []): self
    {
        return $this->register(Severity::critical(), $argument, $validator, $settings);
    }

    public function error(string $argument, string $validator, array $settings = []): self
    {
        return $this->register(Severity::error(), $argument, $validator, $settings);
    }

    public function notice(string $argument, string $validator, array $settings = []): self
    {
        return $this->register(Severity::notice(), $argument, $validator, $settings);
    }

    public function silent(string $argument, string $validator, array $settings = []): self
    {
        return $this->register(Severity::silent(), $argument, $validator, $settings);
    }

    private function register(Severity $severity, string $argument, string $validator, array $settings): self
    {
        $definition = new ValidatorDefinition($argument, $validator, $severity, $settings);
        $this->definitions[] = $definition;
        return $this;
    }

    public function __invoke(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!empty($request->getAttribute(ActionHandler::ATTR_PAYLOAD))) {
            throw new RuntimeException('Action payload already exists.');
        }

        $request = $this->validate($request);

        /** @var ValidationReport $report */
        if ($report = $request->getAttribute(self::ATTR_VALIDATION_REPORT)) {
            $errorReport = $report->getErrors();
            if (!$errorReport->isEmpty()) {
                $request = $request->withAttribute(ActionHandler::ATTR_ERRORS, $errorReport->getMessages());
                if ($statusCode = $errorReport->getStatusCode()) {
                    $request = $request->withAttribute(ActionHandler::ATTR_STATUS_CODE, $statusCode);
                }
            }
        }

        return $request;
    }

    public function validate(ServerRequestInterface $request): ServerRequestInterface
    {
        $report = new ValidationReport;
        foreach ($this->definitions as $definition) {
            $settings = $definition->getSettings();
            $severity = $definition->getSeverity();
            $validator = $this->getValidator($this->container, $definition);
            try {
                if (array_key_exists('depends', $settings)) {
                    foreach ((array)$settings['depends'] as $dependent) {
                        if (!$report->isProvided($dependent)) {
                            throw new DomainException("Dependent validator '$dependent' not provided.");
                        }
                    }
                }
                $result = $validator($request);
                $request = ($settings['export'] ?? true) ? $result : $request;
                $report = $report->push(new ValidationIncident($definition, Severity::success()));
            } catch (DomainException $error) {
                $incident = new ValidationIncident($definition, Severity::unexecuted());
                $report = $report->push($incident->addMessage($error->getMessage()));
            } catch (AssertionFailedException $error) {
                if ($severity->isGreaterThanOrEqual(Severity::notice())) {
                    $incident = new ValidationIncident($definition, $severity);
                    switch (true) {
                        case $error instanceof LazyAssertionException:
                            /** @var LazyAssertionException $error */
                            foreach ($error->getErrorExceptions() as $exception) {
                                $incident = $incident->addMessage($exception->getMessage());
                            }
                            break;
                        default:
                            $incident = $incident->addMessage($error->getMessage());
                            break;
                    }
                    $report = $report->push($incident);
                }
                if ($severity->equals(Severity::critical())) {
                    break;
                }
            }
        }

        return $request->withAttribute(self::ATTR_VALIDATION_REPORT, $report);
    }

    private function getValidator(ContainerInterface $container, ValidatorDefinition $definition): ValidatorInterface
    {
        $dependency = [$definition->getImplementor(), [':definition' => $definition]];
        /** @var ValidatorInterface $validator */
        $validator = $this->resolve($container, $dependency, ValidatorInterface::class);
        return $validator;
    }
}
