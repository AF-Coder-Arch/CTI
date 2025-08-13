-- Add team and comment fields to findings table
USE cti_tracker;

-- Add team field
ALTER TABLE findings ADD COLUMN team VARCHAR(255) NULL AFTER contact_person;

-- Add comment field
ALTER TABLE findings ADD COLUMN comment TEXT NULL AFTER description;

-- Update existing sample data with team information
UPDATE findings SET team = 'IT Security Team' WHERE id = 1;
UPDATE findings SET team = 'Network Operations' WHERE id = 2;
UPDATE findings SET team = 'Security Operations' WHERE id = 3;
UPDATE findings SET team = 'IT Security Team' WHERE id = 4;
UPDATE findings SET team = 'Network Operations' WHERE id = 5;
UPDATE findings SET team = 'Compliance Team' WHERE id = 6;