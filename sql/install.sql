CREATE TABLE IF NOT EXISTS `DB_PREFIXgriiv_email_content` (
    `id_content` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_mail` INT(11) UNSIGNED NOT NULL,
    `html_content` LONGTEXT,
    `text_content` TEXT,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_content`),
    KEY `id_mail` (`id_mail`),
    KEY `date_add` (`date_add`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `DB_PREFIXgriiv_email_attachment` (
    `id_attachment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_mail` INT(11) UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `content` LONGBLOB,
    `file_path` VARCHAR(500),
    `storage_mode` ENUM('database', 'file') NOT NULL DEFAULT 'database',
    PRIMARY KEY (`id_attachment`),
    KEY `id_mail` (`id_mail`)
) ENGINE=MYSQL_ENGINE DEFAULT CHARSET=utf8mb4;
