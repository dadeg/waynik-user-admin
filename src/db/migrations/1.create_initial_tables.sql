-- up
ALTER TABLE `user_custom_fields`
  CHANGE COLUMN `value` `value` VARCHAR(1024) DEFAULT NULL;
  
-- down
ALTER TABLE `user_custom_fields`
  CHANGE COLUMN `value` `value` VARCHAR(255) DEFAULT NULL;