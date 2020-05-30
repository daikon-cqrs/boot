<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

trait DialogTrait
{
    protected function confirm(InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure? [y\N]: ', false);
        return $helper->ask($input, $output, $question);
    }
}
