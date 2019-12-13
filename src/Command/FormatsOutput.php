<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

trait FormatsOutput
{
    protected function formatOutput(OutputInterface $output): void
    {
        $outputFormatter = $output->getFormatter();
        $outputFormatter->setStyle('header', new OutputFormatterStyle('green', null));
        $outputFormatter->setStyle('highlight', new OutputFormatterStyle('green', null, ['bold']));
        $outputFormatter->setStyle('action', new OutputFormatterStyle('blue', null));
    }
}
