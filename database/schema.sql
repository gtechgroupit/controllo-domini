-- Controllo Domini - Database Schema
-- PostgreSQL 13+
-- Version: 4.2.0

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ============================================================================
-- USERS & AUTHENTICATION
-- ============================================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    company VARCHAR(255),
    plan VARCHAR(50) DEFAULT 'free' CHECK (plan IN ('free', 'pro', 'enterprise')),
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended', 'pending_verification')),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    email_verification_expires TIMESTAMP,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    last_login_at TIMESTAMP,
    last_login_ip INET,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- API Keys table
CREATE TABLE IF NOT EXISTS api_keys (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    key_hash VARCHAR(255) UNIQUE NOT NULL,
    key_prefix VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    scopes TEXT[] DEFAULT '{}',
    rate_limit_per_hour INTEGER DEFAULT 100,
    rate_limit_per_day INTEGER DEFAULT 1000,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'revoked', 'expired')),
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address INET,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- OAuth Connections table
CREATE TABLE IF NOT EXISTS oauth_connections (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider VARCHAR(50) NOT NULL CHECK (provider IN ('google', 'github', 'microsoft')),
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(provider, provider_user_id)
);

-- ============================================================================
-- ANALYSIS & HISTORY
-- ============================================================================

-- Analysis History table
CREATE TABLE IF NOT EXISTS analysis_history (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    domain VARCHAR(253) NOT NULL,
    analysis_type VARCHAR(50) NOT NULL CHECK (analysis_type IN (
        'dns', 'whois', 'blacklist', 'ssl', 'cloud', 'security_headers',
        'technology', 'social_meta', 'performance', 'seo', 'redirects',
        'ports', 'full'
    )),
    results JSONB NOT NULL,
    execution_time_ms INTEGER,
    from_cache BOOLEAN DEFAULT FALSE,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Saved Domains table (user favorites/portfolio)
CREATE TABLE IF NOT EXISTS saved_domains (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    domain VARCHAR(253) NOT NULL,
    tags TEXT[] DEFAULT '{}',
    notes TEXT,
    monitoring_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, domain)
);

-- ============================================================================
-- MONITORING & ALERTS
-- ============================================================================

-- Monitors table
CREATE TABLE IF NOT EXISTS monitors (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    domain VARCHAR(253) NOT NULL,
    monitor_type VARCHAR(50) NOT NULL CHECK (monitor_type IN (
        'dns_changes', 'whois_expiry', 'ssl_expiry', 'blacklist',
        'security_headers', 'performance'
    )),
    frequency VARCHAR(50) NOT NULL CHECK (frequency IN ('hourly', 'daily', 'weekly', 'monthly')),
    alert_threshold JSONB,
    channels TEXT[] DEFAULT '{}',
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'paused', 'disabled')),
    last_check_at TIMESTAMP,
    next_check_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    monitor_id UUID REFERENCES monitors(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    domain VARCHAR(253) NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    severity VARCHAR(50) DEFAULT 'warning' CHECK (severity IN ('info', 'warning', 'critical')),
    message TEXT NOT NULL,
    details JSONB,
    status VARCHAR(50) DEFAULT 'open' CHECK (status IN ('open', 'acknowledged', 'resolved')),
    acknowledged_at TIMESTAMP,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- RATE LIMITING & USAGE
-- ============================================================================

-- Rate Limits table
CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    api_key_id UUID REFERENCES api_keys(id) ON DELETE CASCADE,
    ip_address INET,
    request_count INTEGER DEFAULT 0,
    window_start TIMESTAMP DEFAULT NOW(),
    window_end TIMESTAMP DEFAULT (NOW() + INTERVAL '1 hour'),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Usage Statistics table
CREATE TABLE IF NOT EXISTS usage_stats (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    api_key_id UUID REFERENCES api_keys(id) ON DELETE SET NULL,
    date DATE NOT NULL,
    analysis_type VARCHAR(50),
    request_count INTEGER DEFAULT 0,
    cached_count INTEGER DEFAULT 0,
    error_count INTEGER DEFAULT 0,
    total_execution_time_ms BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, date, analysis_type)
);

-- ============================================================================
-- ORGANIZATIONS & TEAMS
-- ============================================================================

-- Organizations table
CREATE TABLE IF NOT EXISTS organizations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    plan VARCHAR(50) DEFAULT 'team' CHECK (plan IN ('team', 'business', 'enterprise')),
    billing_email VARCHAR(255),
    billing_address TEXT,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'cancelled')),
    settings JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Team Members table
CREATE TABLE IF NOT EXISTS team_members (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL CHECK (role IN ('owner', 'admin', 'member', 'viewer')),
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'invited', 'suspended')),
    invited_by UUID REFERENCES users(id),
    joined_at TIMESTAMP DEFAULT NOW(),
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(organization_id, user_id)
);

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    role VARCHAR(50) NOT NULL,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    UNIQUE(role, resource, action)
);

-- ============================================================================
-- WEBHOOKS & INTEGRATIONS
-- ============================================================================

-- Webhooks table
CREATE TABLE IF NOT EXISTS webhooks (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    url TEXT NOT NULL,
    secret VARCHAR(255),
    events TEXT[] NOT NULL,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'disabled', 'failed')),
    retry_count INTEGER DEFAULT 0,
    last_success_at TIMESTAMP,
    last_failure_at TIMESTAMP,
    last_failure_reason TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Webhook Logs table
CREATE TABLE IF NOT EXISTS webhook_logs (
    id BIGSERIAL PRIMARY KEY,
    webhook_id UUID NOT NULL REFERENCES webhooks(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL,
    payload JSONB NOT NULL,
    response_status INTEGER,
    response_body TEXT,
    execution_time_ms INTEGER,
    success BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- AUDIT LOGS
-- ============================================================================

-- Audit Logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    organization_id UUID REFERENCES organizations(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id VARCHAR(255),
    details JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- EXPORTS & REPORTS
-- ============================================================================

-- Exports table
CREATE TABLE IF NOT EXISTS exports (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    export_type VARCHAR(50) NOT NULL CHECK (export_type IN ('pdf', 'csv', 'json', 'excel')),
    domain VARCHAR(253),
    analysis_ids BIGINT[],
    file_path TEXT,
    file_size INTEGER,
    status VARCHAR(50) DEFAULT 'processing' CHECK (status IN ('processing', 'completed', 'failed')),
    error_message TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP
);

-- Scheduled Reports table
CREATE TABLE IF NOT EXISTS scheduled_reports (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    frequency VARCHAR(50) NOT NULL CHECK (frequency IN ('daily', 'weekly', 'monthly')),
    domains TEXT[] NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    format VARCHAR(50) DEFAULT 'pdf',
    recipients TEXT[] NOT NULL,
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'paused', 'disabled')),
    last_sent_at TIMESTAMP,
    next_send_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- MIGRATIONS
-- ============================================================================

-- Migrations table
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) UNIQUE NOT NULL,
    batch INTEGER NOT NULL,
    executed_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- INDEXES
-- ============================================================================

-- Users indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_plan ON users(plan);
CREATE INDEX idx_users_created_at ON users(created_at);

-- API Keys indexes
CREATE INDEX idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX idx_api_keys_key_hash ON api_keys(key_hash);
CREATE INDEX idx_api_keys_status ON api_keys(status);

-- Sessions indexes
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_token ON sessions(session_token);
CREATE INDEX idx_sessions_expires_at ON sessions(expires_at);

-- Analysis History indexes
CREATE INDEX idx_analysis_user_id ON analysis_history(user_id);
CREATE INDEX idx_analysis_domain ON analysis_history(domain);
CREATE INDEX idx_analysis_type ON analysis_history(analysis_type);
CREATE INDEX idx_analysis_created_at ON analysis_history(created_at DESC);
CREATE INDEX idx_analysis_user_domain ON analysis_history(user_id, domain);

-- Saved Domains indexes
CREATE INDEX idx_saved_domains_user_id ON saved_domains(user_id);
CREATE INDEX idx_saved_domains_domain ON saved_domains(domain);
CREATE INDEX idx_saved_domains_monitoring ON saved_domains(monitoring_enabled);

-- Monitors indexes
CREATE INDEX idx_monitors_user_id ON monitors(user_id);
CREATE INDEX idx_monitors_domain ON monitors(domain);
CREATE INDEX idx_monitors_status ON monitors(status);
CREATE INDEX idx_monitors_next_check ON monitors(next_check_at);

-- Alerts indexes
CREATE INDEX idx_alerts_user_id ON alerts(user_id);
CREATE INDEX idx_alerts_monitor_id ON alerts(monitor_id);
CREATE INDEX idx_alerts_status ON alerts(status);
CREATE INDEX idx_alerts_created_at ON alerts(created_at DESC);

-- Rate Limits indexes
CREATE INDEX idx_rate_limits_user_id ON rate_limits(user_id);
CREATE INDEX idx_rate_limits_api_key ON rate_limits(api_key_id);
CREATE INDEX idx_rate_limits_ip ON rate_limits(ip_address);
CREATE INDEX idx_rate_limits_window ON rate_limits(window_start, window_end);

-- Usage Stats indexes
CREATE INDEX idx_usage_stats_user_id ON usage_stats(user_id);
CREATE INDEX idx_usage_stats_date ON usage_stats(date DESC);
CREATE INDEX idx_usage_stats_user_date ON usage_stats(user_id, date);

-- Organizations indexes
CREATE INDEX idx_organizations_slug ON organizations(slug);
CREATE INDEX idx_organizations_status ON organizations(status);

-- Team Members indexes
CREATE INDEX idx_team_members_org_id ON team_members(organization_id);
CREATE INDEX idx_team_members_user_id ON team_members(user_id);
CREATE INDEX idx_team_members_role ON team_members(role);

-- Webhooks indexes
CREATE INDEX idx_webhooks_user_id ON webhooks(user_id);
CREATE INDEX idx_webhooks_status ON webhooks(status);

-- Webhook Logs indexes
CREATE INDEX idx_webhook_logs_webhook_id ON webhook_logs(webhook_id);
CREATE INDEX idx_webhook_logs_created_at ON webhook_logs(created_at DESC);

-- Audit Logs indexes
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_org_id ON audit_logs(organization_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);

-- Exports indexes
CREATE INDEX idx_exports_user_id ON exports(user_id);
CREATE INDEX idx_exports_status ON exports(status);
CREATE INDEX idx_exports_created_at ON exports(created_at DESC);

-- ============================================================================
-- DEFAULT PERMISSIONS
-- ============================================================================

-- Insert default permissions for roles
INSERT INTO permissions (role, resource, action) VALUES
    -- Owner permissions (all access)
    ('owner', '*', '*'),

    -- Admin permissions
    ('admin', 'analysis', 'read'),
    ('admin', 'analysis', 'create'),
    ('admin', 'analysis', 'delete'),
    ('admin', 'domain', 'read'),
    ('admin', 'domain', 'create'),
    ('admin', 'domain', 'update'),
    ('admin', 'domain', 'delete'),
    ('admin', 'monitor', 'read'),
    ('admin', 'monitor', 'create'),
    ('admin', 'monitor', 'update'),
    ('admin', 'monitor', 'delete'),
    ('admin', 'team', 'read'),
    ('admin', 'team', 'invite'),
    ('admin', 'export', 'create'),

    -- Member permissions
    ('member', 'analysis', 'read'),
    ('member', 'analysis', 'create'),
    ('member', 'domain', 'read'),
    ('member', 'domain', 'create'),
    ('member', 'monitor', 'read'),
    ('member', 'monitor', 'create'),
    ('member', 'export', 'create'),

    -- Viewer permissions (read-only)
    ('viewer', 'analysis', 'read'),
    ('viewer', 'domain', 'read'),
    ('viewer', 'monitor', 'read')
ON CONFLICT (role, resource, action) DO NOTHING;

-- ============================================================================
-- TRIGGERS & FUNCTIONS
-- ============================================================================

-- Update updated_at timestamp automatically
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger to tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_oauth_updated_at BEFORE UPDATE ON oauth_connections
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_saved_domains_updated_at BEFORE UPDATE ON saved_domains
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_monitors_updated_at BEFORE UPDATE ON monitors
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_organizations_updated_at BEFORE UPDATE ON organizations
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_webhooks_updated_at BEFORE UPDATE ON webhooks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_scheduled_reports_updated_at BEFORE UPDATE ON scheduled_reports
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- VIEWS
-- ============================================================================

-- User statistics view
CREATE OR REPLACE VIEW user_statistics AS
SELECT
    u.id,
    u.email,
    u.plan,
    u.status,
    COUNT(DISTINCT ah.id) as total_analyses,
    COUNT(DISTINCT sd.id) as saved_domains,
    COUNT(DISTINCT m.id) as active_monitors,
    COUNT(DISTINCT ak.id) as api_keys,
    MAX(ah.created_at) as last_analysis_at,
    u.created_at
FROM users u
LEFT JOIN analysis_history ah ON u.id = ah.user_id
LEFT JOIN saved_domains sd ON u.id = sd.user_id
LEFT JOIN monitors m ON u.id = m.user_id AND m.status = 'active'
LEFT JOIN api_keys ak ON u.id = ak.user_id AND ak.status = 'active'
GROUP BY u.id;

-- Organization statistics view
CREATE OR REPLACE VIEW organization_statistics AS
SELECT
    o.id,
    o.name,
    o.plan,
    o.status,
    COUNT(DISTINCT tm.user_id) as member_count,
    COUNT(DISTINCT ah.id) as total_analyses,
    COUNT(DISTINCT sd.id) as saved_domains,
    o.created_at
FROM organizations o
LEFT JOIN team_members tm ON o.id = tm.organization_id AND tm.status = 'active'
LEFT JOIN analysis_history ah ON tm.user_id = ah.user_id
LEFT JOIN saved_domains sd ON tm.user_id = sd.user_id
GROUP BY o.id;

-- ============================================================================
-- COMMENTS
-- ============================================================================

COMMENT ON TABLE users IS 'User accounts and authentication';
COMMENT ON TABLE api_keys IS 'API keys for programmatic access';
COMMENT ON TABLE analysis_history IS 'Historical analysis results';
COMMENT ON TABLE monitors IS 'Domain monitoring configuration';
COMMENT ON TABLE alerts IS 'Monitoring alerts and notifications';
COMMENT ON TABLE rate_limits IS 'Rate limiting tracking';
COMMENT ON TABLE organizations IS 'Organizations for team collaboration';
COMMENT ON TABLE team_members IS 'Organization team members';
COMMENT ON TABLE webhooks IS 'Webhook configurations';
COMMENT ON TABLE audit_logs IS 'Audit trail of all actions';
COMMENT ON TABLE exports IS 'Export jobs and generated files';
