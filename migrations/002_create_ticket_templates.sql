-- Migration: Create ticket_templates table
-- Run this SQL in your database

CREATE TABLE IF NOT EXISTS ticket_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    template_name VARCHAR(100) DEFAULT 'Default Ticket',
    background_color VARCHAR(7) DEFAULT '#FFFFFF',
    header_color VARCHAR(7) DEFAULT '#1a1a1a',
    text_color VARCHAR(7) DEFAULT '#333333',
    show_qr_code TINYINT(1) DEFAULT 1,
    show_fields JSON COMMENT 'Array of field names to display, e.g., ["name", "email", "phone"]',
    custom_message TEXT,
    logo_position ENUM('top', 'bottom', 'none') DEFAULT 'top',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Add index for faster lookups
CREATE INDEX idx_ticket_templates_org ON ticket_templates(organization_id);
