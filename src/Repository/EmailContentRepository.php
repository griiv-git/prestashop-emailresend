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
use Griiv\EmailResend\Entity\GriivEmailContent;

class EmailContentRepository extends EntityRepository
{
    /**
     * Find content by mail ID
     */
    public function findByMailId(int $idMail): ?GriivEmailContent
    {
        return $this->findOneBy(['idMail' => $idMail]);
    }

    /**
     * Delete orphan records (content without matching mail)
     */
    public function deleteOrphans(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $prefix = _DB_PREFIX_;

        $sql = "DELETE c FROM `{$prefix}griiv_email_content` c
                LEFT JOIN `{$prefix}mail` m ON c.id_mail = m.id_mail
                WHERE m.id_mail IS NULL";

        return $conn->executeStatement($sql);
    }
}
