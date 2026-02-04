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

namespace Griiv\EmailResend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Griiv\EmailResend\Repository\EmailContentRepository")
 */
class GriivEmailContent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id_content", type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(name="id_mail", type="integer")
     */
    private int $idMail;

    /**
     * @ORM\Column(name="html_content", type="text", nullable=true)
     */
    private ?string $htmlContent = null;

    /**
     * @ORM\Column(name="text_content", type="text", nullable=true)
     */
    private ?string $textContent = null;

    /**
     * @ORM\Column(name="date_add", type="datetime")
     */
    private \DateTimeInterface $dateAdd;

    public function __construct()
    {
        $this->dateAdd = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdMail(): int
    {
        return $this->idMail;
    }

    public function setIdMail(int $idMail): self
    {
        $this->idMail = $idMail;
        return $this;
    }

    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(?string $htmlContent): self
    {
        $this->htmlContent = $htmlContent;
        return $this;
    }

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(?string $textContent): self
    {
        $this->textContent = $textContent;
        return $this;
    }

    public function getDateAdd(): \DateTimeInterface
    {
        return $this->dateAdd;
    }

    public function setDateAdd(\DateTimeInterface $dateAdd): self
    {
        $this->dateAdd = $dateAdd;
        return $this;
    }
}
