CREATE TABLE IF NOT EXISTS traits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO traits (name) VALUES 
('Humoris'),
('Religius'),
('Penyabar'),
('Suka Traveling'),
('Pekerja Keras'),
('Penyayang Binatang'),
('Suka Memasak'),
('Disiplin');
