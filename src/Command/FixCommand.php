<?php

namespace App\Command;

use App\Controller\AppController;
use App\Services\FixNamespaceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/*
 * Get all project classes.
 */
class FixCommand extends Command
{
    private $uses = [];

    private $includes = []; // track each include/require, so we can map to use statement

    private string $usesString;

    private $autoloadMap = [];

    protected static $defaultName = 'app:fix';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var FixNamespaceService
     */
    private FixNamespaceService $fixNamespaceService;

    /**
     * @var AppController
     */
    private AppController $appController;

    public function __construct(LoggerInterface $logger, AppController  $appController, FixNamespaceService $fixNamespaceService, string $name = null)
    {
        parent::__construct($name);
        $this->logger = $logger;
        $this->fixNamespaceService = $fixNamespaceService;
        $this->appController = $appController;
    }

    protected function configure()
    {
        $this
            ->setDescription('Change include/require to namespaces')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to process', "vendor/collectiveaccess/providence/")
            ->addOption('expand-classes', null, InputOption::VALUE_NONE, 'expand files that have multiple classes')
            ->addOption('add-namespaces', null, InputOption::VALUE_NONE, 'add namespace to top of file')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $response = $this->appController->reflect($this->fixNamespaceService, null, [
            'write' => true,
            'process' => true,
            'filter' => ''
        ]);
        // the logger gives us what we need...
        $io->success('Finished ' . __CLASS__);
        return self::SUCCESS;
    }
}
