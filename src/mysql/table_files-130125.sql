CREATE TABLE `episciences`.`files` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `docid` INT UNSIGNED NOT NULL , `name` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL , `extension` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL , `type_mime` VARCHAR(200) NOT NULL , `size` BIGINT UNSIGNED NOT NULL , `md5` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL , `source` INT UNSIGNED NOT NULL , `uploaded_date` DATETIME NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE `episciences`.`files` ADD INDEX `INDEX_DOCID` (`docid`);
ALTER TABLE `episciences`.`files` ADD INDEX `INDEX_SOURCE` (`source`);
ALTER TABLE `data_descriptor` ADD CONSTRAINT `FK_DD_FILES` FOREIGN KEY (`fileid`) REFERENCES `files`(`id`) ON DELETE CASCADE;
