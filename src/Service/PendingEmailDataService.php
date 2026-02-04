<?php
/**
 * Copyright since 2024 Griiv
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    Griiv
 * @copyright Since 2024 Griiv
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL") v. 3.0
 */

declare(strict_types=1);

namespace Griiv\EmailResend\Service;

/**
 * Service to manage pending email data during the same PHP request.
 * Uses a static array to share data between hooks (actionEmailSendBefore and actionObjectMailAddAfter).
 */
class PendingEmailDataService
{
    /**
     * Pending email data indexed by unique key to handle concurrent sends
     */
    private static array $pendingEmailData = [];

    /**
     * Store pending email data (called from ActionEmailSendBefore hook)
     */
    public function store(string $key, array $data): void
    {
        self::$pendingEmailData[$key] = $data;
    }

    /**
     * Get and remove pending email data (called from ActionObjectMailAddAfter hook)
     */
    public function getByRecipient(string $recipient): ?array
    {
        foreach (self::$pendingEmailData as $key => $data) {
            if (($data['to'] ?? '') === $recipient) {
                unset(self::$pendingEmailData[$key]);
                return $data;
            }
        }
        return null;
    }

    /**
     * Clean old pending data (entries older than 60 seconds)
     */
    public function cleanOld(): void
    {
        $now = microtime(true);
        foreach (self::$pendingEmailData as $key => $data) {
            if (($now - ($data['timestamp'] ?? 0)) > 60) {
                unset(self::$pendingEmailData[$key]);
            }
        }
    }

    /**
     * Get all pending data (for debugging purposes)
     */
    public function getAll(): array
    {
        return self::$pendingEmailData;
    }

    /**
     * Clear all pending data
     */
    public function clear(): void
    {
        self::$pendingEmailData = [];
    }
}
