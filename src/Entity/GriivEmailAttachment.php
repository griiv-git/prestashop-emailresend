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
 * @ORM\Entity(repositoryClass="Griiv\EmailResend\Repository\EmailAttachmentRepository")
 */
class GriivEmailAttachment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id_attachment", type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(name="id_mail", type="integer")
     */
    private int $idMail;

    /**
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private string $filename;

    /**
     * @ORM\Column(name="mime_type", type="string", length=100)
     */
    private string $mimeType;

    /**
     * @ORM\Column(name="file_size", type="integer")
     */
    private int $fileSize = 0;

    /**
     * @ORM\Column(name="content", type="blob", nullable=true)
     */
    private $content = null;

    /**
     * @ORM\Column(name="file_path", type="string", length=500, nullable=true)
     */
    private ?string $filePath = null;

    /**
     * @ORM\Column(name="storage_mode", type="string", length=20)
     */
    private string $storageMode = 'database';

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

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getStorageMode(): string
    {
        return $this->storageMode;
    }

    public function setStorageMode(string $storageMode): self
    {
        $this->storageMode = $storageMode;
        return $this;
    }
}
