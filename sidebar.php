<?php
$activePage = $activePage ?? 'dashboard';
$notifCount = $notifCount ?? 0;
?>
<aside class="sidebar" id="sidebarNav">
  <div class="sidebar-brand">
    <div class="brand-mark">
      <div class="brand-square">
        <svg viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      </div>
      <span class="brand-name">HealthKiosk</span>
    </div>
    <div class="brand-sub">RHU Pozorrubio</div>
  </div>
  <div class="nav-section-label">Main</div>
  <div class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" onclick="nav('dashboard',this)">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </div>
  <div class="nav-link <?= $activePage === 'patients' ? 'active' : '' ?>" onclick="nav('patients',this)">
    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Patients
  </div>
  <div class="nav-link <?= $activePage === 'vitals' ? 'active' : '' ?>" onclick="nav('vitals',this)">
    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    Vital Monitoring
  </div>
  <div class="nav-section-label">Reports</div>
  <div class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>" onclick="nav('reports',this)">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    Reports Printed
  </div>
  <div class="nav-link <?= $activePage === 'analytics' ? 'active' : '' ?>" onclick="nav('analytics',this)">
    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Analytics
  </div>
  <div class="nav-section-label">System</div>
  <div class="nav-link <?= $activePage === 'notifications' ? 'active' : '' ?>" onclick="nav('notifications',this)" id="navNotif">
    <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    Notifications
    <span class="nav-badge" id="navNotifBadge"><?= (int)$notifCount ?></span>
  </div>
  <div class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" onclick="nav('settings',this)">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    Settings
  </div>
  <div class="sidebar-footer">
    <div class="admin-card">
      <div class="admin-avatar">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
        <div class="admin-role">Superadmin</div>
      </div>
    </div>
    <a href="admin_logout.php" class="logout-link">Sign Out</a>
  </div>
</aside>