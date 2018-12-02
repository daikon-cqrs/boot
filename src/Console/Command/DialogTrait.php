<?php

declare(strict_types=1);

namespace Oroshi\Core\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

trait DialogTrait
{
    protected function confirm(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure? [y\N]: ', false);
        return $helper->ask($input, $output, $question);
    }
}
