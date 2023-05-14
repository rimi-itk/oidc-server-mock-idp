<?php

namespace App\Command;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'build')]
class BuildCommand extends Command implements LoggerAwareInterface
{
    use LoggerTrait;
    use LoggerAwareTrait;

    private const Dockerfile = <<<EOF
FROM ghcr.io/soluto/oidc-server-mock:latest

###ENVIRONMENT###

# @see https://github.com/Soluto/oidc-server-mock/blob/master/src/Dockerfile
ENTRYPOINT ["dotnet", "OpenIdConnectServerMock.dll" ]
EOF;

    private const TAG = 'itkdev/oidc-server-mock-idp';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Make output at lease very verbose.
        $output->setVerbosity($output->getVerbosity() | OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->setLogger(new ConsoleLogger($output));

        $this->info(sprintf('Removing dokcer image %s', self::TAG));
        $command = [
            'docker',
            'rm',
            '--force',
            self::TAG,
        ];
        $process = new Process($command);
        $process->run();

        $environment = $this->getEnvironment();
        if ($output->isDebug()) {
            $io->section('Environment');
            $io->writeln(Yaml::dump($environment, PHP_INT_MAX, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        }

        $dockerFilePath = $this->writeDockerfile($environment);
        $this->info(sprintf('Dockerfile written to %s', $dockerFilePath));

        if ($output->isDebug()) {
            $io->section('Dockerfile');
            $io->writeln(file_get_contents($dockerFilePath));
        }

        $command = [
            'docker',
            'build',
//            '--quiet',
            '--tag',
            self::TAG,
            dirname($dockerFilePath),
            '--file',
            basename($dockerFilePath),
        ];

        foreach ($environment as $name => $value) {
            $command[] = '--build-arg';
            $command[] = $name . '=' . $value;
        }

        try {
            $this->info(sprintf('Building docker image from %s', $dockerFilePath));
            $process = new Process($command, dirname($dockerFilePath));
            if ($output->isDebug()) {
                $io->section('Command');
                $io->writeln($process->getCommandLine());
            }

            $process->start();

            if ($output->isDebug()) {
                foreach ($process as $type => $data) {
                    fwrite($process::OUT === $type ? STDOUT : STDERR, $data);
                }
            }

            $this->info(sprintf('Docker image %s built', self::TAG));
        } finally {
            if (file_exists($dockerFilePath)) {
                unlink($dockerFilePath);
            }
        }

        return Command::SUCCESS;
    }

    private function getEnvironment(): array
    {
        return Yaml::parseFile(__DIR__ . '/environment.yaml');
    }

    private function writeDockerfile(array $environment): string
    {
        $path = __DIR__ . '/Dockerfile.tmp';

        $stuff = implode(PHP_EOL, array_merge(...array_map(
            static fn($name) => [
                'ARG ' . $name,
                'ENV ' . $name . ' $' . $name,
            ],
            array_keys($environment)
        )));

        $content = str_replace(
            '###ENVIRONMENT###',
            $stuff,
            self::Dockerfile
        );

        file_put_contents($path, $content);

        return $path;
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
