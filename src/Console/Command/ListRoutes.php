<?php

namespace Oroshi\Core\Console\Command;

use Oroshi\Core\Middleware\RoutingHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutes extends Command
{
    /** @var RoutingHandler */
    private $routingHandler;

    public function __construct(RoutingHandler $routingHandler)
    {
        $this->routingHandler = $routingHandler;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('route:ls')
            ->setDescription('List registered routes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $getRouter = \Closure::bind(
            function (RoutingHandler $routingHandler) {
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
    }
}
