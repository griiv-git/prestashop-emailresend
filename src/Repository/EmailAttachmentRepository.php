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

namespace Griiv\EmailResend\Repository;

use Doctrine\ORM\EntityRepository;
use Griiv\EmailResend\Entity\GriivEmailAttachment;

class EmailAttachmentRepository extends EntityRepository
{
    /**
     * Find attachments by mail ID
     *
     * @return GriivEmailAttachment[]
     */
    public function findByMailId(int $idMail): array
    {
        return $this->findBy(['idMail' => $idMail]);
    }

    /**
     * Check if mail has attachments
     */
    public function hasAttachments(int $idMail): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.idMail = :idMail')
            ->setParameter('idMail', $idMail)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Get orphan file paths before deletion
     *
     * @return string[]
     */
    public function getOrphanFilePaths(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $prefix = _DB_PREFIX_;

        $sql = "SELECT a.file_path FROM `{$prefix}griiv_email_attachment` a
                LEFT JOIN `{$prefix}mail` m ON a.id_mail = m.id_mail
                WHERE m.id_mail IS NULL AND a.storage_mode = 'file' AND a.file_path IS NOT NULL";

        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return array_column($result, 'file_path');
    }

    /**
     * Delete orphan records
     */
    public function deleteOrphans(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $prefix = _DB_PREFIX_;

        $sql = "DELETE a FROM `{$prefix}griiv_email_attachment` a
                LEFT JOIN `{$prefix}mail` m ON a.id_mail = m.id_mail
                WHERE m.id_mail IS NULL";

        return $conn->executeStatement($sql);
    }
}
