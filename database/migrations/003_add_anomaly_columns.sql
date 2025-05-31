-- Add anomaly detection columns to health_metrics table
ALTER TABLE health_metrics
ADD COLUMN is_anomaly TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN anomaly_score FLOAT DEFAULT 0.0,
ADD COLUMN anomaly_checked_at TIMESTAMP NULL DEFAULT NULL,
ADD INDEX idx_anomaly (is_anomaly, anomaly_checked_at);

-- Update existing records to have default values
UPDATE health_metrics 
SET is_anomaly = 0, 
    anomaly_score = 0.0,
    anomaly_checked_at = CURRENT_TIMESTAMP
WHERE is_anomaly IS NULL OR anomaly_score IS NULL;
