-- Enhanced Database Schema for Thread Management
-- Create database and tables with enhanced features

-- Create database (run this in phpMyAdmin or MySQL command line)
CREATE DATABASE IF NOT EXISTS angular_php_demo;
USE angular_php_demo;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Enhanced chat sessions table with thread management
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT 'New Chat',
    category VARCHAR(100) DEFAULT 'General',
    thread_id VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    message_count INT DEFAULT 0,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Enhanced messages table with metadata support
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
);

-- Usage tracking table
CREATE TABLE IF NOT EXISTS usage_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NULL,
    tokens_input INT DEFAULT 0,
    tokens_output INT DEFAULT 0,
    cost_usd DECIMAL(10,6) DEFAULT 0.000000,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_session_date (user_id, session_id, date)
);

-- Thread statistics table for analytics
CREATE TABLE IF NOT EXISTS thread_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_threads INT DEFAULT 0,
    active_threads INT DEFAULT 0,
    archived_threads INT DEFAULT 0,
    total_messages INT DEFAULT 0,
    avg_messages_per_thread DECIMAL(8,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stats (user_id)
);

-- Create indexes for better performance
CREATE INDEX idx_chat_sessions_user_id ON chat_sessions(user_id);
CREATE INDEX idx_chat_sessions_status ON chat_sessions(status);
CREATE INDEX idx_chat_sessions_category ON chat_sessions(category);
CREATE INDEX idx_chat_sessions_last_message ON chat_sessions(last_message_at);
CREATE INDEX idx_messages_session_id ON messages(session_id);
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_role ON messages(role);
CREATE INDEX idx_usage_tracking_user_date ON usage_tracking(user_id, date);
CREATE INDEX idx_usage_tracking_session ON usage_tracking(session_id);

-- Full-text search index for message content
ALTER TABLE messages ADD FULLTEXT(content);

-- Insert sample user for testing (password is 'password123')
INSERT INTO users (username, email, password_hash) VALUES 
('demo_user', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;

-- Create a view for session summaries
CREATE OR REPLACE VIEW session_summary AS
SELECT 
    cs.id,
    cs.user_id,
    cs.title,
    cs.category,
    cs.status,
    cs.message_count,
    cs.last_message_at,
    cs.created_at,
    cs.updated_at,
    COALESCE(ut.total_cost, 0) as total_cost_usd,
    COALESCE(ut.total_tokens, 0) as total_tokens
FROM chat_sessions cs
LEFT JOIN (
    SELECT 
        session_id,
        SUM(cost_usd) as total_cost,
        SUM(tokens_input + tokens_output) as total_tokens
    FROM usage_tracking 
    GROUP BY session_id
) ut ON cs.id = ut.session_id;

-- Migration script to update existing schema
-- Run this if you already have the old schema

-- Add new columns to existing chat_sessions table
ALTER TABLE chat_sessions 
ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'General' AFTER title,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'archived', 'deleted') DEFAULT 'active' AFTER thread_id,
ADD COLUMN IF NOT EXISTS message_count INT DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS last_message_at TIMESTAMP NULL AFTER message_count;

-- Add new column to existing messages table
ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER content,
MODIFY COLUMN role ENUM('user', 'assistant', 'system') NOT NULL;

-- Update message counts for existing sessions
UPDATE chat_sessions cs 
SET message_count = (
    SELECT COUNT(*) FROM messages m WHERE m.session_id = cs.id
),
last_message_at = (
    SELECT MAX(created_at) FROM messages m WHERE m.session_id = cs.id
);

-- Create new indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_chat_sessions_status ON chat_sessions(status);
CREATE INDEX IF NOT EXISTS idx_chat_sessions_category ON chat_sessions(category);
CREATE INDEX IF NOT EXISTS idx_chat_sessions_last_message ON chat_sessions(last_message_at);
CREATE INDEX IF NOT EXISTS idx_messages_role ON messages(role);
