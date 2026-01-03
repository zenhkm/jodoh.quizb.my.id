-- SQL to add gender column to users table
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `gender` VARCHAR(16) DEFAULT NULL;

-- Optional: set default for existing users (example sets to 'unspecified')
-- UPDATE `users` SET `gender` = 'unspecified' WHERE `gender` IS NULL;