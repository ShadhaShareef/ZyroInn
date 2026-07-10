-- 014_add_property_type_to_properties.sql
-- Adds a property_type column to distinguish resort / homestay / lodge / budget / luxury

ALTER TABLE properties
  ADD COLUMN property_type ENUM('resort','homestay','lodge','budget','luxury') NULL AFTER code,
  ADD INDEX idx_properties_property_type (property_type);

-- Update existing seeded property
UPDATE properties SET property_type = 'resort' WHERE id = 1;
