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
use Symfony\Component\Console\Style\SymfonyStyle;


use Symfony\Component\Console\Input\InputArgument;
use OCA\LogCleaner\Controller\LogsController;

class Deduplicate extends Command {

    /** @var LogsController */
    private $logsController;

    public function __construct(LogsController $logsController) {
        parent::__construct();
        $this->logsController = $logsController;
    }

    protected function configure(): void {
        $this
            ->setName('logcleaner:deduplicate')
            ->setDescription('Removes duplicate entries from the Nextcloud log file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $io->info('Start duplicate cleanup via OCC...');

        try {
            /** @var \OCP\AppFramework\Http\DataResponse $response */
            $response = $this->logsController->removeDub(0);

            $data = $response->getData();

            $anzahl = $data['cntdub'] ?? 0;
            $speicherplatz = $data['sizediff'] ?? '0 B';

            if ($anzahl === 0) {
                $io->success('No duplicates were found. The log file is already clean.');
            } else {
                $io->success(sprintf(
                    'Cleanup successful! Deleted duplicates: %d | Freed Disk Space: %s',
                    $anzahl,
                    $speicherplatz
                ));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Execution error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
