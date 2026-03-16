<?php

// src/Command/TestRedisCommand.php
namespace App\Command;

use Predis\Client as RedisClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test:redis')]
class TestRedisCommand extends Command
{
    private RedisClient $redis;

    public function __construct(RedisClient $redis)
    {
        parent::__construct();
        $this->redis = $redis;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->redis->set('symfony:test', 'ok');
            $value = $this->redis->get('symfony:test');

            if ($value === 'ok') {
                $output->writeln('<info>✅ Redis connection works!</info>');
                return Command::SUCCESS;
            } else {
                $output->writeln('<error>❌ Could not read back from Redis.</error>');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Redis error: '.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
    }
}
