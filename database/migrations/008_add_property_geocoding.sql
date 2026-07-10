-- 008_add_property_geocoding.sql
-- Adds latitude and longitude columns to properties for map-based search

ALTER TABLE properties
  ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER postal_code,
  ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude,
  ADD INDEX idx_properties_lat_lng (latitude, longitude);

-- Seed sample coordinates for seeded properties (approximate USA locations)
UPDATE properties SET latitude = 25.7617, longitude = -80.1918 WHERE city = 'Miami' AND state = 'FL' AND latitude IS NULL;
UPDATE properties SET latitude = 40.7128, longitude = -74.0060 WHERE city = 'New York' AND state = 'NY' AND latitude IS NULL;
UPDATE properties SET latitude = 34.0522, longitude = -118.2437 WHERE city = 'Los Angeles' AND state = 'CA' AND latitude IS NULL;
UPDATE properties SET latitude = 41.8781, longitude = -87.6298 WHERE city = 'Chicago' AND state = 'IL' AND latitude IS NULL;
UPDATE properties SET latitude = 29.7604, longitude = -95.3698 WHERE city = 'Houston' AND state = 'TX' AND latitude IS NULL;
UPDATE properties SET latitude = 33.4484, longitude = -112.0740 WHERE city = 'Phoenix' AND state = 'AZ' AND latitude IS NULL;
UPDATE properties SET latitude = 29.4241, longitude = -98.4936 WHERE city = 'San Antonio' AND state = 'TX' AND latitude IS NULL;
UPDATE properties SET latitude = 32.7157, longitude = -117.1611 WHERE city = 'San Diego' AND state = 'CA' AND latitude IS NULL;
UPDATE properties SET latitude = 47.6062, longitude = -122.3321 WHERE city = 'Seattle' AND state = 'CA' AND latitude IS NULL;
UPDATE properties SET latitude = 38.9072, longitude = -77.0369 WHERE city = 'Washington' AND state = 'DC' AND latitude IS NULL;
