ALTER TABLE `paper_files` ADD `source` INT NOT NULL DEFAULT '4' AFTER `doc_id`, ADD INDEX `source` (`source`);