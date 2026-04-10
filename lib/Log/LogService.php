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
namespace OCA\LogCleaner\Log;

use Psr\Log\LoggerInterface;
use OCP\IConfig;
use OCP\IL10N;
use OCA\LogCleaner\Controller\Helper;

class LogService {
    private string $path;
    private array $lines = [];
    private bool $loaded = false;
    private array $pendingDeletes = [];
    private \Mutex|null $mutex = null;
    private int $nextId = 1;


    public function __construct(private IConfig $config, private readonly LoggerInterface $logger, private IL10N $l, private Helper $helper, string $filePath='/var/www/html/data/nextcloud.log') {
        $filePath = $this->config->getSystemValue('logfile');
		if (!file_exists($filePath)) {
			$filePath = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
        if (!is_string($filePath) || $filePath === '') {
            throw new \InvalidArgumentException('LogService: empty filePath');
        }
        if (!file_exists($filePath)) {
            throw new \RuntimeException("LogService: file not found: {$filePath}");
        }
        if (!is_readable($filePath)) {
            throw new \RuntimeException("LogService: file not readable: {$filePath}");
        }
        $this->path = $filePath;
        if (class_exists(\Mutex::class)) {
            $this->mutex = new \Mutex();
        }
        $this->helper = $helper;
    }

    public function load(): void {
        if ($this->loaded) return;
        $this->lines = [];
        $this->nextId = 1;
        $fh = fopen($this->path, 'r');
        if ($fh === false) { $this->loaded = true; return; }
        while (($line = fgets($fh)) !== false) {
            $id = $this->nextId++;
            $trim = rtrim($line, "\r\n");
            $parsed = json_decode($trim, true);
            $this->lines[$id] = [
                'raw' => $trim,
                'json' => $parsed === null ? null : $parsed,
                'deleted' => false,
            ];
        }
        $this->lines = array_reverse($this->lines, true);

        fclose($fh);
        $this->loaded = true;
    }

     public function getSnapshot(array $filters = [], int $limit = 100, int $offset = 0, array $fields = []): array {
        $this->load();
        $result = [];
        $endresult = [];
        $corruptline = [];
            $wt_offset = (int)$this->helper->getAppValue('logcleaner_wt_offset');
        foreach ($this->lines as $id => $entry) {
            if ($entry['deleted']) continue;

            if (isset($filters['level']) && (!isset($entry['json']['level']) || (int)$entry['json']['level'] !== (int)$filters['level'])) {
                continue;
            }
            if (isset($filters['app']) && (!isset($entry['json']['app']) || strpos($entry['json']['app'], $filters['app']) === false)) {
                continue;
            }
            if (isset($filters['message']) && (!isset($entry['json']['message']) || strpos($entry['json']['message'], $filters['message']) === false)) {
                continue;
            }
            $item = ['id' => $id-1];
            if (empty($fields)) {
                $item['raw'] = $entry['raw'];
                $item['json'] = $entry['json'];
            } else {
                foreach ($fields as $f) {
                    $item[$f] = $entry['json'][$f] ?? null;
                }
            }

            if (isset($item['time'])) {
            $wttimelog = strtotime($item['time']) + 3600*$wt_offset;
            $item['formattedtimewithoffset'] = $this->l->l('date', $wttimelog) . ' - ' . $this->l->l('time', $wttimelog);
            }

            if (!isset($item['reqId']) || !isset($item['message'])) {
                $endresult[2] = 'corrupt';
                $corruptline[0] = $this->helper->corruptline($id, $this->path);
                $wttimelog = strtotime($corruptline[0]['time']) + 3600*$wt_offset;
                $item['formattedtimewithoffset'] = $this->l->l('date', $wttimelog) . ' - ' . $this->l->l('time', $wttimelog);
                $item['message'] = 'LogCleaner: Corrupted line detected within your logfile.--------------------------------> Please reload this page.';
                $item['level'] = 5;
            }

            $result[] = $item;
            $endresult[0] = count($result);
            $endresult[1] = array_slice($result, $offset, $limit);
        }

        return $endresult;
    }

    public function count(): int {
        $this->load();
        $c = 0;
        foreach ($this->lines as $entry) if (!$entry['deleted']) $c++;
        return $c;
    }

    public function getlogpath() {
        $filePath = $this->config->getSystemValue('logfile');
		if (!file_exists($filePath)) {
			$filePath = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
        return $filePath;
    }
}
