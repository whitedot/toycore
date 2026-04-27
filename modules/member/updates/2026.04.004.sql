ALTER TABLE toy_member_auth_logs
    ADD KEY idx_toy_member_auth_logs_ip_created (ip_address, created_at);

UPDATE toy_modules
SET version = '2026.04.004',
    updated_at = NOW()
WHERE module_key = 'member';
