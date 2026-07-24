ALTER TABLE bus_passes ADD COLUMN original_pass_id INT(11) NULL AFTER route_id;
ALTER TABLE bus_passes ADD FOREIGN KEY (original_pass_id) REFERENCES bus_passes(id) ON DELETE SET NULL;
