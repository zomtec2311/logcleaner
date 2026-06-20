<?php
/**
 *
 * LogCleaner APP (Nextcloud)
 *
 * @author Wolfgang Tödt <wtoedt@gmail.com>
 *
 * @copyright Copyright (c) 2025 Wolfgang Tödt
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
declare(strict_types=1);

namespace OCA\LogCleaner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use OCA\LogCleaner\Controller\LogsController;
use OCA\LogCleaner\Log\LogService;

class CleanLevel extends Command {

    /** @var LogsController */
    private $logsController;

    /** @var LogService */
    private $logService;

    public function __construct(LogsController $logsController, LogService $logService) {
        parent::__construct();
        $this->logsController = $logsController;
        $this->logService = $logService;
    }

    protected function configure(): void {
        $this
            ->setName('logcleaner:clean-level')
            ->setDescription('Deletes all log entries of a specific log level')
            ->addArgument(
                'level',
                InputArgument::REQUIRED,
                'The log level (0=Debug, 1=Info, 2=Warn, 3=Error, 4=Fatal)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $level = (int)$input->getArgument('level');

        if (!in_array($level, [0, 1, 2, 3, 4], true)) {
            $io->error('Invalid log level. 0 to 4 is allowed (0=DEBUG, 1=INFO, 2=WARN, 3=ERROR, 4=FATAL).');
            return Command::INVALID;
        }

        $io->info("Analyze log file to find rows by level $level...");

        try {
            $inputFile = $this->logService->getLogFile();
            $outputJson = $inputFile . 'analysis.json';

            if (!file_exists($outputJson)) {
                $io->error('The analysis file (analysis.json) does not exist. Please do an analysis first.');
                return Command::FAILURE;
            }

            $json = file_get_contents($outputJson);
            $analysis = json_decode($json, true);

            $dellines = [];
            $levelname = 'UNKNOWN';

            if (isset($analysis['levels']) && is_array($analysis['levels'])) {
                foreach ($analysis['levels'] as $levelData) {
                    if (isset($levelData['level']) && (int)$levelData['level'] === $level) {
                        $dellines = $levelData['lines'] ?? [];
                        $levelname = $levelData['label'] ?? 'LEVEL ' . $level;
                        break;
                    }
                }
            }

            $anzahlZeilen = count($dellines);

            if ($anzahlZeilen === 0) {
                $io->success("No entries for the log level $levelname ($level) found in the log file.");
                return Command::SUCCESS;
            }

            $io->info("Delete $anzahlZeilen rows for log level $levelname...");

            $response = $this->logsController->dellines($level, $dellines);
            $data = $response->getData();

            if (isset($data['status']) && $data['status'] === 'success') {
                $geloescht = $data['deleted_count'] ?? $anzahlZeilen;
                $speicher = $data['size_diff'] ?? '0 B';

                $io->success(sprintf(
                    'Cleanup successful! Deleted rows (%s): %d | Freed Disk Space: %s',
                    $levelname,
                    $geloescht,
                    $speicher
                ));
                return Command::SUCCESS;
            } else {
                $io->error('The controller reported an error during the cleanup.');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Execution error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
