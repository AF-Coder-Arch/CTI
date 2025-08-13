-- Migration script to add contact_person column to existing installations
-- Run this if you already have the database set up

USE cti_tracker;

-- Add contact_person column to findings table
ALTER TABLE findings ADD COLUMN contact_person VARCHAR(255) NULL AFTER description;

-- Update existing records with sample contact persons (optional)
UPDATE findings SET contact_person = 'John Smith' WHERE id = 1;
UPDATE findings SET contact_person = 'Jane Doe' WHERE id = 2;
UPDATE findings SET contact_person = 'Mike Johnson' WHERE id = 3;
UPDATE findings SET contact_person = 'Sarah Wilson' WHERE id = 4;
UPDATE findings SET contact_person = 'David Brown' WHERE id = 5;
UPDATE findings SET contact_person = 'Lisa Davis' WHERE id = 6;