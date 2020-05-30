<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Console\Command;

use Aura\Router\RouterContainer;
use Closure;
use Daikon\Boot\Middleware\RoutingHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutes extends Command
{
    private RoutingHandler $routingHandler;

    public function __construct(RoutingHandler $routingHandler)
    {
        $this->routingHandler = $routingHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('route:ls')
            ->setDescription('List registered routes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $getRouter = Closure::bind(
            function (RoutingHandler $routingHandler): RouterContainer {
                /** @psalm-suppress InaccessibleProperty */
                return $routingHandler->router;
            },
            null,
            $this->routingHandler
        );

        foreach ($getRouter($this->routingHandler)->getMap()->getRoutes() as $route) {
            $output->write("<info>$route->name</info> => ");
            $output->write('<comment>'.implode('|', $route->allows).'</comment> ');
            $output->writeln($route->path);
        }

        return 0;
    }
}
