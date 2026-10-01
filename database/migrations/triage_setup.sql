-- Run this in phpMyAdmin (SQL tab) to set up the triage workflow.
-- Safe to run multiple times (uses IF NOT EXISTS).

-- 1. Add triage workflow columns to appointments table
ALTER TABLE `appointments`
    ADD COLUMN IF NOT EXISTS `triager_status` VARCHAR(30) NOT NULL DEFAULT 'Pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `triager_action` VARCHAR(50) NULL AFTER `triager_status`,
    ADD COLUMN IF NOT EXISTS `triager_remarks` TEXT NULL AFTER `triager_action`,
    ADD COLUMN IF NOT EXISTS `processed_by` BIGINT UNSIGNED NULL AFTER `triager_remarks`,
    ADD COLUMN IF NOT EXISTS `processed_at` TIMESTAMP NULL AFTER `processed_by`,
    ADD COLUMN IF NOT EXISTS `request_mode` VARCHAR(10) NULL AFTER `mode`;

-- 2. Add role column to admin table
ALTER TABLE `admin`
    ADD COLUMN IF NOT EXISTS `role` VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER `lastname`;

-- 3. Create the triager login account (username: triager, password: Password@123)
INSERT INTO `admin` (`firstname`, `lastname`, `username`, `password`, `email`, `contact_no`, `role`, `created_at`, `updated_at`)
SELECT 'Triage', 'Staff', 'triager', '$2y$10$06VyxBvCxixcydwvUjo7se8Nd7uIfyyNwROFqD16sekg3c.f3NY2K', 'triager@qmmc.local', '09293470607', 'triager', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `admin` WHERE `username` = 'triager');
