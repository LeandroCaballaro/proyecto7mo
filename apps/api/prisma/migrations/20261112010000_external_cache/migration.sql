-- CreateTable
CREATE TABLE `external_cache` (
    `key` VARCHAR(191) NOT NULL,
    `value` LONGTEXT NOT NULL,
    `expires_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `external_cache_expires_at_idx`(`expires_at`),
    PRIMARY KEY (`key`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `translation_cache` (
    `hash` CHAR(64) NOT NULL,
    `lang_pair` VARCHAR(10) NOT NULL,
    `translated` TEXT NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`hash`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
