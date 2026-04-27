ALTER TABLE toy_member_auth_logs
    ADD KEY idx_toy_member_auth_logs_account_event_created (account_id, event_type, created_at),
    ADD KEY idx_toy_member_auth_logs_ip_event_created (ip_address, event_type, created_at);

UPDATE toy_modules
SET version = '2026.04.005',
    updated_at = NOW()
WHERE module_key = 'member';
