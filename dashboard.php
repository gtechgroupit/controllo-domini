<?php
/**
 * User Dashboard
 *
 * @package ControlDomini
 * @version 4.2.1
 */

require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

$auth = getAuth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get user statistics
$stats = [
    'total_analyses' => $db->queryOne(
        'SELECT COUNT(*) as count FROM analysis_history WHERE user_id = :user_id',
        [':user_id' => $user['id']]
    )['count'] ?? 0,

    'saved_domains' => $db->queryOne(
        'SELECT COUNT(*) as count FROM saved_domains WHERE user_id = :user_id',
        [':user_id' => $user['id']]
    )['count'] ?? 0,

    'active_monitors' => $db->queryOne(
        'SELECT COUNT(*) as count FROM monitors WHERE user_id = :user_id AND status = :status',
        [':user_id' => $user['id'], ':status' => 'active']
    )['count'] ?? 0,

    'api_keys' => $db->queryOne(
        'SELECT COUNT(*) as count FROM api_keys WHERE user_id = :user_id AND status = :status',
        [':user_id' => $user['id'], ':status' => 'active']
    )['count'] ?? 0
];

// Get recent analyses
$recent_analyses = $db->query(
    'SELECT domain, analysis_type, created_at
     FROM analysis_history
     WHERE user_id = :user_id
     ORDER BY created_at DESC
     LIMIT 10',
    [':user_id' => $user['id']]
);

// Get recent alerts
$recent_alerts = $db->query(
    'SELECT domain, alert_type, severity, message, created_at
     FROM alerts
     WHERE user_id = :user_id AND status = :status
     ORDER BY created_at DESC
     LIMIT 5',
    [':user_id' => $user['id'], ':status' => 'open']
);

$page_title = 'Dashboard - Controllo Domini';
$page_description = 'Manage your domain analyses and monitoring';
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-plan"><?php echo ucfirst($user['plan']); ?> Plan</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard" class="nav-item active">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="/domains" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                Saved Domains
            </a>
            <a href="/monitors" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Monitors
                <?php if ($stats['active_monitors'] > 0): ?>
                    <span class="nav-badge"><?php echo $stats['active_monitors']; ?></span>
                <?php endif; ?>
            </a>
            <a href="/api-keys" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                API Keys
            </a>
            <a href="/history" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                History
            </a>
            <a href="/settings" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
            <a href="/logout.php" class="nav-item">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <a href="/" class="btn btn-primary">Analyze New Domain</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Analyses</div>
                    <div class="stat-value"><?php echo number_format($stats['total_analyses']); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #f3e5f5; color: #7b1fa2;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Saved Domains</div>
                    <div class="stat-value"><?php echo number_format($stats['saved_domains']); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Active Monitors</div>
                    <div class="stat-value"><?php echo number_format($stats['active_monitors']); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">API Keys</div>
                    <div class="stat-value"><?php echo number_format($stats['api_keys']); ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="content-grid">
            <!-- Recent Analyses -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Analyses</h2>
                    <a href="/history" class="card-action">View All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_analyses)): ?>
                        <div class="empty-state">
                            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>No analyses yet. <a href="/">Analyze your first domain</a></p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_analyses as $analysis): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($analysis['domain']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $analysis['analysis_type']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $analysis['analysis_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($analysis['created_at'])); ?></td>
                                        <td>
                                            <a href="/analysis?domain=<?php echo urlencode($analysis['domain']); ?>&type=<?php echo $analysis['analysis_type']; ?>" class="btn-link">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Alerts -->
            <div class="content-card">
                <div class="card-header">
                    <h2>Recent Alerts</h2>
                    <a href="/alerts" class="card-action">View All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_alerts)): ?>
                        <div class="empty-state">
                            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p>No alerts. All systems normal.</p>
                        </div>
                    <?php else: ?>
                        <div class="alerts-list">
                            <?php foreach ($recent_alerts as $alert): ?>
                                <div class="alert-item alert-<?php echo $alert['severity']; ?>">
                                    <div class="alert-header">
                                        <span class="alert-domain"><?php echo htmlspecialchars($alert['domain']); ?></span>
                                        <span class="alert-time"><?php echo timeAgo($alert['created_at']); ?></span>
                                    </div>
                                    <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
.dashboard-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    min-height: 100vh;
    background: var(--bg-color, #f5f5f5);
}

.dashboard-sidebar {
    background: var(--card-bg, #fff);
    border-right: 1px solid var(--border-color, #e0e0e0);
    padding: 24px;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
    margin-bottom: 24px;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color, #007bff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-plan {
    font-size: 12px;
    color: var(--text-secondary, #666);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    color: var(--text-primary, #1a1a1a);
    text-decoration: none;
    transition: all 0.2s;
    position: relative;
}

.nav-item:hover {
    background: var(--bg-hover, #f5f5f5);
}

.nav-item.active {
    background: var(--primary-color, #007bff);
    color: white;
}

.nav-icon {
    width: 20px;
    height: 20px;
}

.nav-badge {
    margin-left: auto;
    background: var(--danger-color, #dc3545);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}

.dashboard-main {
    padding: 40px;
    overflow-y: auto;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.dashboard-header h1 {
    margin: 0;
    font-size: 32px;
}

.btn-primary {
    background: var(--primary-color, #007bff);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: var(--primary-hover, #0056b3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon svg {
    width: 28px;
    height: 28px;
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary, #666);
    margin-bottom: 4px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-primary, #1a1a1a);
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.content-card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-header {
    padding: 24px;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 20px;
}

.card-action {
    color: var(--primary-color, #007bff);
    text-decoration: none;
    font-size: 14px;
}

.card-content {
    padding: 24px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    width: 48px;
    height: 48px;
    color: var(--text-secondary, #999);
    margin-bottom: 16px;
}

.empty-state p {
    color: var(--text-secondary, #666);
    margin: 0;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
}

.data-table th {
    font-weight: 600;
    color: var(--text-secondary, #666);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.btn-link {
    color: var(--primary-color, #007bff);
    text-decoration: none;
    font-size: 14px;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.alert-item {
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid;
}

.alert-critical {
    background: #fee;
    border-color: #c33;
}

.alert-warning {
    background: #ffe;
    border-color: #f90;
}

.alert-info {
    background: #eff;
    border-color: #09f;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.alert-domain {
    font-weight: 600;
}

.alert-time {
    font-size: 12px;
    color: var(--text-secondary, #666);
}

.alert-message {
    font-size: 14px;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 968px) {
    .dashboard-container {
        grid-template-columns: 1fr;
    }

    .dashboard-sidebar {
        border-right: none;
        border-bottom: 1px solid var(--border-color, #e0e0e0);
    }
}
</style>

<?php
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

include __DIR__ . '/includes/footer.php';
?>
