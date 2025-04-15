CREATE DATABASE podcast_db;

USE podcast_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    dob DATE,
    gender VARCHAR(10),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
    
);

CREATE TABLE podcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255),
    description TEXT,
    genre VARCHAR(50),
    audio_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE users ADD profile_pic VARCHAR(255);
