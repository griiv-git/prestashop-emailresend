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

use Griiv\EmailResend\Repository\EmailAttachmentRepository;
use Griiv\EmailResend\Repository\EmailContentRepository;
use Symfony\Component\Filesystem\Filesystem;

class OrphanCleanerService
{
    private EmailContentRepository $contentRepository;
    private EmailAttachmentRepository $attachmentRepository;
    private Filesystem $filesystem;

    public function __construct(
        EmailContentRepository $contentRepository,
        EmailAttachmentRepository $attachmentRepository
    ) {
        $this->contentRepository = $contentRepository;
        $this->attachmentRepository = $attachmentRepository;
        $this->filesystem = new Filesystem();
    }

    /**
     * Clean orphan records and files
     *
     * @return int Number of deleted records
     */
    public function clean(): int
    {
        $deleted = 0;
        $uploadDir = _PS_MODULE_DIR_ . 'griivemailresend/uploads/';

        // Get file paths before deleting attachments
        $orphanFiles = $this->attachmentRepository->getOrphanFilePaths();

        // Delete files
        foreach ($orphanFiles as $filePath) {
            $fullPath = $uploadDir . $filePath;
            if ($this->filesystem->exists($fullPath)) {
                $this->filesystem->remove($fullPath);
            }
        }

        // Delete orphan content records
        $deleted += $this->contentRepository->deleteOrphans();

        // Delete orphan attachment records
        $deleted += $this->attachmentRepository->deleteOrphans();

        return $deleted;
    }
}
