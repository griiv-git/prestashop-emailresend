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

use Doctrine\ORM\EntityManagerInterface;
use Griiv\EmailResend\Entity\GriivEmailAttachment;
use Griiv\EmailResend\Entity\GriivEmailContent;

class EmailCaptureService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Store email content after Mail object is created
     */
    public function storeEmailContent(int $idMail, string $htmlContent, ?string $textContent = null): void
    {
        $emailContent = new GriivEmailContent();
        $emailContent->setIdMail($idMail);
        $emailContent->setHtmlContent($htmlContent);
        $emailContent->setTextContent($textContent);
        $emailContent->setDateAdd(new \DateTime());

        $this->entityManager->persist($emailContent);
        $this->entityManager->flush();
    }

    /**
     * Store email attachments
     *
     * @param int $idMail
     * @param array $attachments
     */
    public function storeAttachments(int $idMail, array $attachments): void
    {
        if (!\Configuration::get('GRIIV_EMAILRESEND_STORE_ATTACHMENTS')) {
            return;
        }

        $maxSize = (int) \Configuration::get('GRIIV_EMAILRESEND_MAX_SIZE') * 1024 * 1024;
        $storageMode = \Configuration::get('GRIIV_EMAILRESEND_STORAGE_MODE') ?: 'database';
        $uploadDir = _PS_MODULE_DIR_ . 'griivemailresend/uploads/';

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $content = $attachment['content'] ?? null;
            $name = $attachment['name'] ?? 'attachment';
            $mime = $attachment['mime'] ?? 'application/octet-stream';

            if (!$content) {
                continue;
            }

            $fileSize = strlen($content);

            // Check size limit
            if ($fileSize > $maxSize) {
                \PrestaShopLogger::addLog(
                    sprintf('GriivEmailResend: Attachment "%s" exceeds size limit (%.2f MB)', $name, $fileSize / 1024 / 1024),
                    2,
                    null,
                    'Mail',
                    $idMail
                );
                continue;
            }

            $emailAttachment = new GriivEmailAttachment();
            $emailAttachment->setIdMail($idMail);
            $emailAttachment->setFilename($name);
            $emailAttachment->setMimeType($mime);
            $emailAttachment->setFileSize($fileSize);
            $emailAttachment->setStorageMode($storageMode);

            if ($storageMode === 'file') {
                // Store as file
                $uniqueName = md5($name . microtime(true) . mt_rand()) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
                $filePath = $uploadDir . $uniqueName;

                if (file_put_contents($filePath, $content) !== false) {
                    $emailAttachment->setFilePath($uniqueName);
                } else {
                    \PrestaShopLogger::addLog(
                        'GriivEmailResend: Failed to write attachment file for id_mail=' . $idMail,
                        3,
                        null,
                        'Mail',
                        $idMail
                    );
                    continue;
                }
            } else {
                // Store in database
                $emailAttachment->setContent($content);
            }

            $this->entityManager->persist($emailAttachment);
        }

        $this->entityManager->flush();
    }

    /**
     * Render email HTML by loading template and replacing variables
     */
    public function renderEmailHtml(?string $templatePath, string $template, int $idLang, array $templateVars): ?string
    {
        if (empty($template)) {
            return null;
        }

        // Build template file path
        $iso = \Language::getIsoById($idLang);
        $paths = [];

        if (!empty($templatePath)) {
            $paths[] = $templatePath . $iso . '/' . $template . '.html';
            $paths[] = $templatePath . 'en/' . $template . '.html';
        }

        // Default paths
        $paths[] = _PS_MAIL_DIR_ . $iso . '/' . $template . '.html';
        $paths[] = _PS_MAIL_DIR_ . 'en/' . $template . '.html';

        $htmlContent = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $htmlContent = \Tools::file_get_contents($path);
                break;
            }
        }

        if (!$htmlContent) {
            return null;
        }

        // Replace template variables
        foreach ($templateVars as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $htmlContent = str_replace('{' . $key . '}', (string) $value, $htmlContent);
            }
        }

        return $htmlContent;
    }
}
