<?php
/**
 * Copyright (c) 2016 Francois Raubenheimer.
 */

namespace FR;

use FR\Console\Command\TestCommand;

/**
 * Class Application
 * @package FR
 */
class Application extends \Symfony\Component\Console\Application
{
    const NAME = 'dev setup';
    const VERSION = '1.0.0-alpha';

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
    }

    /**
     * @return int
     */
    public function runApp()
    {
        $commands = [
            new TestCommand()
        ];
        $this->addCommands($commands);
        return parent::run();
    }
}