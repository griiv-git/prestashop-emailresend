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

class EmailResendService
{
    private EmailContentRepository $contentRepository;
    private EmailAttachmentRepository $attachmentRepository;

    public function __construct(
        EmailContentRepository $contentRepository,
        EmailAttachmentRepository $attachmentRepository
    ) {
        $this->contentRepository = $contentRepository;
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Resend email to specified recipients
     *
     * @param int $idMail
     * @param string[] $recipients
     * @param bool $includeAttachments
     * @return bool
     * @throws \Exception
     */
    public function resend(int $idMail, array $recipients, bool $includeAttachments = false): bool
    {
        // Get content
        $content = $this->contentRepository->findByMailId($idMail);
        if (!$content) {
            throw new \Exception('Email content not found');
        }

        // Get original mail info for subject
        $mailInfo = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mail` WHERE `id_mail` = ' . $idMail
        );
        $subject = $mailInfo['subject'] ?? 'Resent email';

        // Get attachments if requested
        $attachments = [];
        if ($includeAttachments) {
            $attachments = $this->getAttachmentsData($idMail);
        }

        // Send email directly
        return $this->sendDirectEmail($recipients, $subject, $content->getHtmlContent(), $attachments);
    }

    /**
     * Get attachments data for resending
     */
    private function getAttachmentsData(int $idMail): array
    {
        $attachmentEntities = $this->attachmentRepository->findByMailId($idMail);
        $attachments = [];
        $uploadDir = _PS_MODULE_DIR_ . 'griivemailresend/uploads/';

        foreach ($attachmentEntities as $attachment) {
            if ($attachment->getStorageMode() === 'database') {
                $content = $attachment->getContent();
                if (is_resource($content)) {
                    $content = stream_get_contents($content);
                }
                $attachments[] = [
                    'content' => $content,
                    'name' => $attachment->getFilename(),
                    'mime' => $attachment->getMimeType(),
                ];
            } else {
                $filePath = $uploadDir . $attachment->getFilePath();
                if (file_exists($filePath)) {
                    $attachments[] = [
                        'content' => file_get_contents($filePath),
                        'name' => $attachment->getFilename(),
                        'mime' => $attachment->getMimeType(),
                    ];
                }
            }
        }

        return $attachments;
    }

    /**
     * Send email directly using SwiftMailer or Symfony Mailer
     */
    private function sendDirectEmail(array $recipients, string $subject, string $htmlContent, array $attachments = []): bool
    {
        $configuration = \Configuration::getMultiple([
            'PS_SHOP_EMAIL',
            'PS_SHOP_NAME',
            'PS_MAIL_METHOD',
            'PS_MAIL_SERVER',
            'PS_MAIL_USER',
            'PS_MAIL_PASSWD',
            'PS_MAIL_SMTP_ENCRYPTION',
            'PS_MAIL_SMTP_PORT',
        ]);

        // Check if mail is disabled
        if ((int) $configuration['PS_MAIL_METHOD'] === 3) {
            return false;
        }

        // Detect PrestaShop version to choose mailer
        if (class_exists('Symfony\Component\Mime\Email')) {
            return $this->sendWithSymfonyMailer($recipients, $subject, $htmlContent, $attachments, $configuration);
        }

        return $this->sendWithSwiftMailer($recipients, $subject, $htmlContent, $attachments, $configuration);
    }

    /**
     * Send with SwiftMailer (PrestaShop 1.7.x)
     */
    private function sendWithSwiftMailer(array $recipients, string $subject, string $htmlContent, array $attachments, array $config): bool
    {
        if ((int) $config['PS_MAIL_METHOD'] === 2) {
            // SMTP
            $transport = \Swift_SmtpTransport::newInstance(
                $config['PS_MAIL_SERVER'],
                (int) $config['PS_MAIL_SMTP_PORT']
            );

            if (!empty($config['PS_MAIL_USER'])) {
                $transport->setUsername($config['PS_MAIL_USER']);
            }
            if (!empty($config['PS_MAIL_PASSWD'])) {
                $transport->setPassword($config['PS_MAIL_PASSWD']);
            }
            if (!empty($config['PS_MAIL_SMTP_ENCRYPTION'])) {
                $transport->setEncryption($config['PS_MAIL_SMTP_ENCRYPTION']);
            }
        } else {
            // PHP mail()
            $transport = \Swift_MailTransport::newInstance();
        }

        $mailer = \Swift_Mailer::newInstance($transport);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom([$config['PS_SHOP_EMAIL'] => $config['PS_SHOP_NAME']])
            ->setTo($recipients)
            ->setBody($htmlContent, 'text/html', 'UTF-8');

        foreach ($attachments as $att) {
            $attachment = \Swift_Attachment::newInstance(
                $att['content'],
                $att['name'],
                $att['mime']
            );
            $message->attach($attachment);
        }

        return $mailer->send($message) > 0;
    }

    /**
     * Send with Symfony Mailer (PrestaShop 9)
     */
    private function sendWithSymfonyMailer(array $recipients, string $subject, string $htmlContent, array $attachments, array $config): bool
    {
        if ((int) $config['PS_MAIL_METHOD'] === 2) {
            // SMTP
            $scheme = 'smtp';
            if ($config['PS_MAIL_SMTP_ENCRYPTION'] === 'ssl') {
                $scheme = 'smtps';
            }

            $dsn = sprintf(
                '%s://%s:%s@%s:%s',
                $scheme,
                urlencode($config['PS_MAIL_USER'] ?? ''),
                urlencode($config['PS_MAIL_PASSWD'] ?? ''),
                $config['PS_MAIL_SERVER'],
                $config['PS_MAIL_SMTP_PORT']
            );

            if ($config['PS_MAIL_SMTP_ENCRYPTION'] === 'tls') {
                $dsn .= '?encryption=tls';
            }
        } else {
            $dsn = 'sendmail://default';
        }

        $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($config['PS_SHOP_EMAIL'], $config['PS_SHOP_NAME']))
            ->subject($subject)
            ->html($htmlContent);

        foreach ($recipients as $recipient) {
            $email->addTo($recipient);
        }

        foreach ($attachments as $att) {
            $email->attach($att['content'], $att['name'], $att['mime']);
        }

        $mailer->send($email);

        return true;
    }
}
