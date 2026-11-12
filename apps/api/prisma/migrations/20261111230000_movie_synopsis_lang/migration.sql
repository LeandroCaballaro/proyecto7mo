-- AlterTable
ALTER TABLE `movies` ADD COLUMN `synopsis_lang` VARCHAR(5) NULL;

-- CreateIndex
CREATE INDEX `movies_synopsis_lang_idx` ON `movies`(`synopsis_lang`);
