ALTER TABLE `VOLUME_METADATA` ADD `date_creation` DATETIME NULL AFTER `titles`, ADD `date_updated` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_creation`;