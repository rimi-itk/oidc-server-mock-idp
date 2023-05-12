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

        $this->setLogger(new ConsoleLogger($output));

        $environment = $this->getEnvironment();
        if ($output->isDebug()) {
            $io->section('Environment');
            $io->writeln(Yaml::dump($environment, PHP_INT_MAX, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        }

        $dockerFilePath = $this->writeDockerfile($environment);

        if ($output->isDebug()) {
            $io->section('Dockerfile');
            $io->writeln(file_get_contents($dockerFilePath));
        }

        $command = [
            'docker',
            'build',
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
            $process = new Process($command);
            if ($output->isDebug()) {
                $io->section('Command');
                $io->writeln($process->getCommandLine());
            }

            $process->start();

            foreach ($process as $type => $data) {
                fwrite($process::OUT === $type ? STDOUT : STDERR, $data);
            }
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

        $content = str_replace(
            '###ENVIRONMENT###',
            $stuff = implode(PHP_EOL, array_merge(...array_map(
                static fn($name) => [
                    'ARG ' . $name,
                    'ENV ' . $name . ' $' . $name,
                ],
                array_keys($environment)
            ))),
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
