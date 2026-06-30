<?php
// includes/theme.php
// Call include_theme_toggle() after your page's main CSS to inject dark mode support + toggle button markup + script.
// Place the button in your topbar right area.

if (!function_exists('render_theme_toggle')) {
function render_theme_toggle() {
    echo '<button id="theme-toggle" class="theme-toggle" title="Toggle dark/light mode" type="button">🌙</button>';
}
}

if (!function_exists('inject_theme_styles_and_script')) {
function inject_theme_styles_and_script() {
    static $injected = false;
    if ($injected) return;
    $injected = true;
?>
<style>
/* ========== DARK / LIGHT THEME SUPPORT ========== */
:root {
    --bg: #f4f8f9;
    --text: #20363a;
    --muted: #6b7b8c;
    --line: #eef3f6;
    --card: #ffffff;
    --sidebar-bg: #ffffff;
    --input-bg: #f7fafb;
    --surface-alt: #f8fbfc;
    --surface-muted: #eef3f6;
    --teal: #116979;
    --teal-dark: #0b4e5a;
    --teal-light: #1b90a5;
    --teal-pale: #eaf6f8;
    --orange: #f0672b;
    --orange-light: #ffb26b;
    --orange-pale: #fff3ee;
}

:root[data-theme="dark"] {
    --bg: #0f172a;
    --text: #e2e8f0;
    --muted: #94a3b8;
    --line: #334155;
    --card: #1e293b;
    --sidebar-bg: #1e293b;
    --input-bg: #334155;
    --surface-alt: #1e293b;
    --surface-muted: #334155;
    --shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    --shadow-sm: 0 4px 14px rgba(0, 0, 0, 0.25);
    --teal-pale: #1e3a5f;
    --orange-pale: #3f2a1f;
}

body {
    background: var(--bg);
    color: var(--text);
}

.sidebar {
    background: var(--sidebar-bg) !important;
    border-right-color: var(--line) !important;
}

.tutor-card,
.booking-item,
.card:not(.leaderboard-teaser):not(.progress-overview),
.profile-card {
    background: var(--card) !important;
    border-color: var(--line) !important;
}

input, select, textarea {
    background: var(--input-bg) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}

.menu a:hover, .menu a.active {
    background: rgba(17,105,121,0.1) !important;
}

/* ========== ENHANCED DARK MODE TEXT & BACKGROUND OVERRIDES ========== */
:root[data-theme="dark"] {
    --text: #e2e8f0;
    --muted: #94a3b8;
    --line: #334155;
    --card: #1e293b;
    --bg: #0f172a;
    --sidebar-bg: #1e293b;
    --input-bg: #334155;
}

/* Force readable text in dark mode on common elements (prevents dark text on dark bg) */
:root[data-theme="dark"] body,
:root[data-theme="dark"] .main,
:root[data-theme="dark"] h1, :root[data-theme="dark"] h2, :root[data-theme="dark"] h3, :root[data-theme="dark"] h4,
:root[data-theme="dark"] p:not(.leaderboard-teaser *), :root[data-theme="dark"] span:not(.leaderboard-teaser *), :root[data-theme="dark"] div:not(.leaderboard-teaser):not(.leaderboard-teaser *),
:root[data-theme="dark"] a:not(.action-button):not(.btn):not(.qa-card):not(.leaderboard-teaser *),
:root[data-theme="dark"] .menu a,
:root[data-theme="dark"] .tutor-name,
:root[data-theme="dark"] .tutor-bio,
:root[data-theme="dark"] .tutor-stat,
:root[data-theme="dark"] .booking-info .subject,
:root[data-theme="dark"] .booking-info .tutor,
:root[data-theme="dark"] .booking-info .date,
:root[data-theme="dark"] .headline-title,
:root[data-theme="dark"] .body-description,
:root[data-theme="dark"] label,
:root[data-theme="dark"] .form-helper-text,
:root[data-theme="dark"] .registration-footer-prompt,
:root[data-theme="dark"] .results-count,
:root[data-theme="dark"] .tutor-expertise,
:root[data-theme="dark"] .no-results p,
:root[data-theme="dark"] .tutor-stat,
:root[data-theme="dark"] .level-name,
:root[data-theme="dark"] .xp-bar-labels span,
:root[data-theme="dark"] .streak-label,
:root[data-theme="dark"] .badge-desc,
:root[data-theme="dark"] .progress-info .tutor,
:root[data-theme="dark"] .review-tutor,
:root[data-theme="dark"] .review-subject,
:root[data-theme="dark"] .qa-sub,
:root[data-theme="dark"] .hint,
:root[data-theme="dark"] .stat-card p,
:root[data-theme="dark"] .topbar-left p,
:root[data-theme="dark"] .card p,
:root[data-theme="dark"] .form-panel *,
:root[data-theme="dark"] .step-view-card *,
:root[data-theme="dark"] .redeem-cost,
:root[data-theme="dark"] .cert-status,
:root[data-theme="dark"] .decay-text,
:root[data-theme="dark"] .lb-scarcity-text,
:root[data-theme="dark"] .menu li a,
:root[data-theme="dark"] .tutor-grid .tutor-card * {
    color: var(--text) !important;
}

/* Muted text in dark */
:root[data-theme="dark"] .muted,
:root[data-theme="dark"] .qa-sub,
:root[data-theme="dark"] .hint,
:root[data-theme="dark"] .booking-info .tutor,
:root[data-theme="dark"] .booking-info .date,
:root[data-theme="dark"] .level-xp,
:root[data-theme="dark"] .streak-sub,
:root[data-theme="dark"] .xp-bar-pct,
:root[data-theme="dark"] .form-helper-text,
:root[data-theme="dark"] .registration-footer-prompt,
:root[data-theme="dark"] .tutor-stat,
:root[data-theme="dark"] .results-count {
    color: var(--muted) !important;
}

/* ── XP CARD: lock all text to white in BOTH light and dark mode ── */
.xp-card,
.xp-card h3,
.xp-card p,
.xp-card span,
.xp-card div,
.xp-card .xp-level-info,
.xp-card .level-name,
.xp-card .level-num,
.xp-card .level-xp,
.xp-card .xp-bar-labels,
.xp-card .xp-bar-labels span,
.xp-card .xp-bar-pct,
:root[data-theme="dark"] .xp-card,
:root[data-theme="dark"] .xp-card h3,
:root[data-theme="dark"] .xp-card p,
:root[data-theme="dark"] .xp-card span,
:root[data-theme="dark"] .xp-card div,
:root[data-theme="dark"] .xp-card .xp-level-info,
:root[data-theme="dark"] .xp-card .level-name,
:root[data-theme="dark"] .xp-card .level-num,
:root[data-theme="dark"] .xp-card .level-xp,
:root[data-theme="dark"] .xp-card .xp-bar-labels,
:root[data-theme="dark"] .xp-card .xp-bar-labels span,
:root[data-theme="dark"] .xp-card .xp-bar-pct {
    color: white !important;
}

/* Fix light backgrounds in dark mode for login/register and cards */
:root[data-theme="dark"] .form-panel,
:root[data-theme="dark"] .step-view-card {
    background-color: var(--card) !important;
}

/* Dark mode for plain stat-cards (tutor dashboard style), keep colored gradients in student/bookings */
:root[data-theme="dark"] .stat-card:not(.orange):not(.teal):not(.green) {
    background: var(--card) !important;
    border-color: var(--line) !important;
}

/* Preserve original white text on colored stat cards even in dark mode */
.stat-card.orange, .stat-card.orange *,
.stat-card.teal, .stat-card.teal *,
.stat-card.green, .stat-card.green * {
    color: white !important;
}

/* Ensure sidebar text is light */
:root[data-theme="dark"] .menu a {
    color: #cbd5e1 !important;
}
:root[data-theme="dark"] .menu a:hover, :root[data-theme="dark"] .menu a.active {
    color: var(--text) !important;
}

/* === AVAILABILITY / TUTORS AVAILABLE CHIPS DARK FIX === */
:root[data-theme="dark"] .avail-chip {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .avail-subject {
    color: var(--text) !important;
}
:root[data-theme="dark"] .avail-count {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .avail-status-pill.high {
    background: #166534 !important;
    color: #86efac !important;
}
:root[data-theme="dark"] .avail-status-pill.medium {
    background: #9a3412 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .avail-status-pill.low {
    background: #334155 !important;
    color: #94a3b8 !important;
}

/* Additional comprehensive dark mode fixes for remaining elements */
:root[data-theme="dark"] .sidebar {
    background: var(--sidebar-bg) !important;
    border-right-color: var(--line) !important;
}
:root[data-theme="dark"] .menu a {
    color: #cbd5e1 !important;
}
:root[data-theme="dark"] .stat-card {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .booking-row,
:root[data-theme="dark"] .slot-pill {
    background: #1e293b !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .slot-pill {
    color: #e0f2fe !important;
}
:root[data-theme="dark"] .graph-box {
    background: var(--card) !important;
}
:root[data-theme="dark"] .fox-motivation {
    background: linear-gradient(135deg, #1e3a5f, #0f172a) !important;
}
:root[data-theme="dark"] .fox-motivation p {
    color: #bae6fd !important;
}
:root[data-theme="dark"] .btn-outline {
    background: var(--card) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .activity-time,
:root[data-theme="dark"] .review-date,
:root[data-theme="dark"] .review-comment {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .avail-chip {
    background: var(--card) !important;
}
:root[data-theme="dark"] .status-badge,
:root[data-theme="dark"] .pay-badge {
    /* keep some contrast but adapt */
}
:root[data-theme="dark"] .no-data-chart {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .profile-card p,
:root[data-theme="dark"] .customise-link {
    color: var(--text) !important;
}
:root[data-theme="dark"] .role-pill {
    background: #1e3a5f !important;
    color: #67e8f9 !important;
}

/* Fix remaining shapes, graphs, chips, and text for full dark mode support */
:root[data-theme="dark"] .stat-card::after {
    background: rgba(255,255,255,0.06) !important;
}
:root[data-theme="dark"] .avail-dot.high { background:#4ade80 !important; }
:root[data-theme="dark"] .avail-dot.medium { background:#fb923c !important; }
:root[data-theme="dark"] .avail-dot.low { background:#94a3b8 !important; }
:root[data-theme="dark"] .fox-motivation {
    background: linear-gradient(135deg,#1e3a5f,#0f172a) !important;
}
:root[data-theme="dark"] .graph-title {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .booking-row {
    background: #1e293b !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .slot-pill {
    background: #1e3a5f !important;
    color: #bae6fd !important;
}
:root[data-theme="dark"] .slot-pill .del:hover {
    color: #f87171 !important;
}
:root[data-theme="dark"] .btn-outline {
    background: var(--card) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .activity-time,
:root[data-theme="dark"] .review-date,
:root[data-theme="dark"] .review-comment {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .no-data-chart {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .profile-card p {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .customise-link {
    background: #1e3a5f !important;
    color: #67e8f9 !important;
}

:root[data-theme="dark"] .room-card {
    background: var(--card) !important;
    border-color: var(--line) !important;
}

/* Additional global safety for text in dark mode on common containers */
:root[data-theme="dark"] .card,
:root[data-theme="dark"] .room-card,
:root[data-theme="dark"] .stat-card,
:root[data-theme="dark"] .booking-row,
:root[data-theme="dark"] .slot-pill,
:root[data-theme="dark"] .graph-box,
:root[data-theme="dark"] .users-card,
:root[data-theme="dark"] .chat-card,
:root[data-theme="dark"] .profile-card {
    color: var(--text);
}
:root[data-theme="dark"] .menu a,
:root[data-theme="dark"] p,
:root[data-theme="dark"] .tutor-bio,
:root[data-theme="dark"] .tutor-stat,
:root[data-theme="dark"] .hint,
:root[data-theme="dark"] .qa-sub {
    color: var(--text) !important;
}

/* === MESSAGES / CHAT DARK MODE FIXES === */
:root[data-theme="dark"] .users-card,
:root[data-theme="dark"] .chat-card {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .contact {
    color: var(--text) !important;
}
:root[data-theme="dark"] .contact:hover {
    background: #1e3a5f !important;
}
:root[data-theme="dark"] .contact.active-contact {
    background: #1e3a5f !important;
    border-left-color: var(--teal) !important;
}
:root[data-theme="dark"] .contact-name {
    color: var(--text) !important;
}
:root[data-theme="dark"] .contact-role {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .bubble-wrap.theirs .bubble {
    background: #334155 !important;
    color: var(--text) !important;
}
:root[data-theme="dark"] .bubble-sender,
:root[data-theme="dark"] .bubble-time,
:root[data-theme="dark"] .date-sep {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .chat-header {
    border-bottom-color: var(--line) !important;
}
:root[data-theme="dark"] .chat-header-name {
    color: var(--text) !important;
}
:root[data-theme="dark"] .chat-header-role {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .message-form {
    background: var(--card) !important;
    border-top-color: var(--line) !important;
}
:root[data-theme="dark"] .message-form input {
    background: var(--input-bg) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .empty-chat h3,
:root[data-theme="dark"] .no-messages p {
    color: var(--text) !important;
}

.theme-toggle {
    background: var(--card);
    border: 1px solid var(--line);
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    margin-left: 12px;
}
.theme-toggle:hover {
    transform: scale(1.05);
    border-color: var(--teal, #116979);
}

/* === COMPREHENSIVE DARK MODE OVERRIDES (covers all reported + more) === */
:root[data-theme="dark"] .sidebar {
    background: var(--sidebar-bg) !important;
    border-right-color: var(--line) !important;
}
:root[data-theme="dark"] .menu a {
    color: #cbd5e1 !important;
}
:root[data-theme="dark"] .menu a:hover, :root[data-theme="dark"] .menu a.active {
    background: rgba(56, 189, 248, 0.15) !important;
    color: var(--text) !important;
}
:root[data-theme="dark"] .logout a {
    background: #3f2a1f !important;
    color: #fb923c !important;
}
:root[data-theme="dark"] .qa-card {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .qa-card:hover {
    background: rgba(56, 189, 248, 0.1) !important;
}
:root[data-theme="dark"] .graph-box {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .graph-title {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .fox-motivation {
    background: linear-gradient(135deg, #1e3a5f, #0f172a) !important;
}
:root[data-theme="dark"] .fox-motivation p {
    color: #bae6fd !important;
}
:root[data-theme="dark"] .stat-card:not(.orange):not(.teal):not(.green) {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .stat-card .value,
:root[data-theme="dark"] .stat-card .label,
:root[data-theme="dark"] .stat-card .hint {
    color: var(--text) !important;
}
:root[data-theme="dark"] .xp-bar-track {
    background: #334155 !important;
}
:root[data-theme="dark"] .xp-bar-fill {
    background: linear-gradient(to right, #38bdf8, #67e8f9) !important;
}
:root[data-theme="dark"] .level-name,
:root[data-theme="dark"] .level-xp,
:root[data-theme="dark"] .xp-bar-pct {
    color: var(--text) !important;
}
:root[data-theme="dark"] .streak-card {
    background: var(--card) !important;
}
:root[data-theme="dark"] .streak-count,
:root[data-theme="dark"] .streak-label {
    color: var(--text) !important;
}
:root[data-theme="dark"] .streak-sub {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .progress-item {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .progress-info .subject {
    color: var(--text) !important;
}
:root[data-theme="dark"] .progress-info .tutor {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .status-badge,
:root[data-theme="dark"] .badge {
    color: var(--text) !important;
}
:root[data-theme="dark"] .status-badge.done,
:root[data-theme="dark"] .badge.completed {
    background: #166534 !important;
    color: #86efac !important;
}
:root[data-theme="dark"] .status-badge.pending,
:root[data-theme="dark"] .badge.pending {
    background: #9a3412 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .status-badge.confirmed,
:root[data-theme="dark"] .badge.confirmed {
    background: #1e40af !important;
    color: #93c5fd !important;
}
:root[data-theme="dark"] .status-badge.cancelled,
:root[data-theme="dark"] .badge.cancelled {
    background: #7f1d1d !important;
    color: #fca5a5 !important;
}
:root[data-theme="dark"] .status-badge.pending {
    background: #9a3412 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .status-badge.confirmed {
    background: #1e40af !important;
    color: #93c5fd !important;
}
:root[data-theme="dark"] .status-badge.cancelled {
    background: #7f1d1d !important;
    color: #fca5a5 !important;
}
:root[data-theme="dark"] .pay-badge.paid {
    background: #166534 !important;
    color: #86efac !important;
}
:root[data-theme="dark"] .pay-badge.unpaid {
    background: #9a3412 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .booking-row {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .booking-row:hover {
    background: #1e293b !important;
}
:root[data-theme="dark"] .slot-pill {
    background: #1e3a5f !important;
    color: #bae6fd !important;
}
:root[data-theme="dark"] .slot-pill .del {
    color: #f87171 !important;
}
:root[data-theme="dark"] .avail-chip {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .avail-subject {
    color: var(--text) !important;
}
:root[data-theme="dark"] .avail-count {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .review-comment,
:root[data-theme="dark"] .review-date {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .activity-time {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .no-data-chart {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .switch-to-tutor {
    background: #1e3a5f !important;
    color: #67e8f9 !important;
}
:root[data-theme="dark"] .switch-to-student {
    background: #1e3a5f !important;
    color: #67e8f9 !important;
}

/* === SIDEBAR COLLAPSE === */
.sidebar {
    transition: width 0.25s ease;
}
.sidebar.collapsed {
    width: 0 !important;
    min-width: 0;
    overflow: hidden;
    border-right: none;
    padding: 0;
    transition: width 0.25s ease;
}
.sidebar.collapsed .logo h2,
.sidebar.collapsed .menu li a span.text,
.sidebar.collapsed .menu li a:after {
    display: none;
}
.sidebar.collapsed .menu a {
    padding: 12px 8px;
    justify-content: center;
    font-size: 0; /* hide text, show emoji */
}
.sidebar.collapsed .menu a::first-letter {
    font-size: 1.3rem;
}
.sidebar.collapsed .logout a {
    padding: 8px;
    text-align: center;
}
.sidebar-toggle {
    background: none;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    padding: 6px 8px;
    margin-right: 8px;
    color: var(--text);
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}
.sidebar-toggle:hover {
    background: rgba(0,0,0,0.05);
    border-radius: 6px;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.main {
    transition: margin-left 0.25s ease;
}

/* Collapsible sections */
.collapsible-header {
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.collapsible-header::after {
    content: "▾";
    transition: transform 0.2s;
}
.collapsible-header.collapsed::after {
    transform: rotate(-90deg);
}
.collapsible-content {
    overflow: hidden;
    transition: max-height 0.3s ease, opacity 0.2s;
}
.collapsible-content.collapsed {
    max-height: 0 !important;
    opacity: 0;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

/* ========== LOGIN / REGISTER PAGES ========== */
:root[data-theme="dark"] {
    --canvas-bg: #0f172a;
    --text-dark: #e2e8f0;
}
:root[data-theme="dark"] body {
    background-color: var(--bg) !important;
    color: var(--text) !important;
}
:root[data-theme="dark"] .form-panel {
    background-color: var(--card) !important;
}
:root[data-theme="dark"] .input-group input {
    background-color: var(--input-bg) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .input-group label,
:root[data-theme="dark"] .headline-title,
:root[data-theme="dark"] .body-description,
:root[data-theme="dark"] .privacy-link-text {
    color: var(--text) !important;
}
:root[data-theme="dark"] .error-banner {
    background-color: #450a0a !important;
    color: #fca5a5 !important;
    border-left-color: #dc2626 !important;
}
:root[data-theme="dark"] .back-navigation-btn {
    color: #67e8fd !important;
}

/* ========== ADMIN CONSOLE ========== */
:root[data-theme="dark"] .sidebar .logo h2 {
    color: #67e8fd !important;
}
:root[data-theme="dark"] .sidebar .menu .nav-label {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .sidebar .menu li a {
    color: #cbd5e1 !important;
}
:root[data-theme="dark"] .sidebar .menu li a:hover,
:root[data-theme="dark"] .sidebar .menu li a.active {
    background: rgba(56, 189, 248, 0.12) !important;
    color: #e2e8f0 !important;
}
:root[data-theme="dark"] .sidebar .logout a {
    background: #3f2a1f !important;
    color: #fb923c !important;
}
:root[data-theme="dark"] .admin-pill {
    background: #1e3a5f !important;
    color: #67e8fd !important;
}
:root[data-theme="dark"] th,
:root[data-theme="dark"] th.sortable a {
    background: var(--surface-alt, #1e293b) !important;
    color: var(--muted) !important;
}
:root[data-theme="dark"] th.sort-active a {
    background: #1e3a5f !important;
    color: #67e8fd !important;
}
:root[data-theme="dark"] tr:hover td {
    background: var(--surface-alt, #1e293b) !important;
}
:root[data-theme="dark"] td {
    color: var(--text) !important;
    border-bottom-color: var(--line) !important;
}
:root[data-theme="dark"] .card-header {
    border-bottom-color: var(--line) !important;
}
:root[data-theme="dark"] .card-header h3,
:root[data-theme="dark"] .topbar-left h1 {
    color: var(--text) !important;
}
:root[data-theme="dark"] .stat-card:not(.teal):not(.orange) .label,
:root[data-theme="dark"] .stat-card:not(.teal):not(.orange) .value,
:root[data-theme="dark"] .stat-card:not(.teal):not(.orange) .hint {
    color: var(--text) !important;
}
:root[data-theme="dark"] .badge-admin {
    background: #1e3a5f !important;
    color: #67e8fd !important;
}
:root[data-theme="dark"] .badge-student {
    background: #451a03 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .badge-completed,
:root[data-theme="dark"] .badge-confirmed,
:root[data-theme="dark"] .badge-paid,
:root[data-theme="dark"] .badge-active {
    background: #14532d !important;
    color: #86efac !important;
}
:root[data-theme="dark"] .badge-pending {
    background: #451a03 !important;
    color: #fed7aa !important;
}
:root[data-theme="dark"] .badge-cancelled,
:root[data-theme="dark"] .badge-failed,
:root[data-theme="dark"] .badge-inactive {
    background: #334155 !important;
    color: #94a3b8 !important;
}
:root[data-theme="dark"] .btn:not(.btn-teal):not(.btn-orange):not(.btn-danger) {
    background: var(--card) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .btn:not(.btn-teal):not(.btn-orange):hover {
    background: var(--surface-alt, #1e293b) !important;
}
:root[data-theme="dark"] .filter-bar select,
:root[data-theme="dark"] .filter-bar input,
:root[data-theme="dark"] .form-group input,
:root[data-theme="dark"] .form-group select,
:root[data-theme="dark"] .form-group textarea {
    background: var(--input-bg) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .sort-hint,
:root[data-theme="dark"] .empty-state,
:root[data-theme="dark"] .bar-row .name,
:root[data-theme="dark"] .bar-count,
:root[data-theme="dark"] .chart-bar span {
    color: var(--muted) !important;
}
:root[data-theme="dark"] .user-av {
    background: #1e3a5f !important;
    color: #67e8fd !important;
}
:root[data-theme="dark"] .flash-success {
    background: #14532d !important;
    color: #86efac !important;
}
:root[data-theme="dark"] .flash-error {
    background: #450a0a !important;
    color: #fca5a5 !important;
}
:root[data-theme="dark"] .sidebar-toggle {
    background: var(--card) !important;
    border-color: var(--line) !important;
    color: var(--text) !important;
}
:root[data-theme="dark"] .sidebar-toggle:hover {
    background: var(--surface-alt, #1e293b) !important;
}

/* ========== GLOBAL TABLE / FORM FIXES ========== */
:root[data-theme="dark"] table {
    color: var(--text);
}
:root[data-theme="dark"] select,
:root[data-theme="dark"] textarea {
    background: var(--input-bg) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .theme-toggle {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] a {
    color: inherit;
}
:root[data-theme="dark"] .card a[style*="color:var(--teal)"],
:root[data-theme="dark"] .card-body a {
    color: #67e8fd !important;
}

/* ========== TUTOR / STUDENT REWARDS PAGES ========== */
:root[data-theme="dark"] .badge-item {
    background: var(--card) !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .badge-item.unlocked {
    background: linear-gradient(135deg, #14532d, #166534) !important;
    border-color: rgba(134, 239, 172, 0.35) !important;
}
:root[data-theme="dark"] .badge-item .badge-name {
    color: var(--text) !important;
}
:root[data-theme="dark"] .cert-item.earned {
    background: linear-gradient(135deg, #451a03, #78350f) !important;
    border-color: rgba(251, 191, 36, 0.35) !important;
}
:root[data-theme="dark"] .cert-item .cert-title,
:root[data-theme="dark"] .level-row.current {
    color: var(--text) !important;
}
:root[data-theme="dark"] .level-row.current {
    background: #1e3a5f !important;
    border-color: var(--teal) !important;
}
:root[data-theme="dark"] .milestone.done,
:root[data-theme="dark"] .next-item,
:root[data-theme="dark"] .perk-row.on {
    background: #1e3a5f !important;
    border-color: var(--line) !important;
}
:root[data-theme="dark"] .rank-banner {
    background: linear-gradient(135deg, #451a03, #78350f) !important;
    border-color: #b45309 !important;
}
:root[data-theme="dark"] .rank-banner strong,
:root[data-theme="dark"] .rank-banner div {
    color: #fde68a !important;
}
:root[data-theme="dark"] .hero {
    /* keep gradient hero readable */
    color: #fff !important;
}
:root[data-theme="dark"] .redeem-item.can-redeem:hover {
    background: #1e3a5f !important;
}

/* ── LEADERBOARD TEASER (quest.php): lock to teal/white in BOTH light and dark mode ── */
.leaderboard-teaser,
.leaderboard-teaser *,
:root[data-theme="dark"] .leaderboard-teaser,
:root[data-theme="dark"] .leaderboard-teaser * {
    background-color: transparent !important;
    color: #ffffff !important;
}
.leaderboard-teaser,
:root[data-theme="dark"] .leaderboard-teaser {
    background: linear-gradient(135deg, #0b4e5a, var(--teal)) !important;
}
.lt-btn,
:root[data-theme="dark"] .lt-btn {
    background: rgba(255,255,255,0.2) !important;
    border-color: rgba(255,255,255,0.3) !important;
}

/* ── PROGRESS OVERVIEW (quest.php "Your Summary"): lock to orange/white in light, navy/white in dark ── */
.progress-overview,
.progress-overview * {
    background-color: transparent !important;
    color: #ffffff !important;
}
.progress-overview {
    background: var(--orange) !important;
}
:root[data-theme="dark"] .progress-overview {
    background: #9a3412 !important;
}
.po-stat,
:root[data-theme="dark"] .po-stat {
    background: rgba(255,255,255,0.15) !important;
}
</style>

<script>
(function() {
    const root = document.documentElement;
    const toggleBtn = () => document.getElementById('theme-toggle');

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        const btn = toggleBtn();
        if (btn) btn.textContent = (theme === 'dark') ? '☀️' : '🌙';
        localStorage.setItem('studytwin-theme', theme);
    }

    function initTheme() {
        let saved = localStorage.getItem('studytwin-theme');
        if (!saved) {
            saved = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        applyTheme(saved);

        const btn = toggleBtn();
        if (btn) {
            btn.addEventListener('click', () => {
                const current = root.getAttribute('data-theme') || 'light';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // Collapsible support
    function initCollapsibles() {
        document.querySelectorAll('.collapsible-header').forEach(header => {
            const targetSel = header.getAttribute('data-target');
            const content = targetSel ? document.querySelector(targetSel) : header.nextElementSibling;
            if (!content) return;

            // restore state
            const key = 'collapse-' + (header.textContent.trim().slice(0,20));
            if (localStorage.getItem(key) === '1') {
                header.classList.add('collapsed');
                content.classList.add('collapsed');
            }

            header.addEventListener('click', () => {
                const isCollapsed = header.classList.toggle('collapsed');
                content.classList.toggle('collapsed', isCollapsed);
                localStorage.setItem(key, isCollapsed ? '1' : '0');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initCollapsibles);

    // Sidebar collapse (hamburger)
    function initSidebarCollapse() {
        const toggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const main = document.querySelector('.main');
        if (!toggle || !sidebar) return;

        const defaultMargin = main ? (getComputedStyle(main).marginLeft || '260px') : '260px';
        const saved = localStorage.getItem('sidebar-collapsed') === 'true';
        if (saved) {
            sidebar.classList.add('collapsed');
            if (main) main.style.marginLeft = '0px';
        }

        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            if (main) main.style.marginLeft = isCollapsed ? '0px' : defaultMargin;
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });
    }

    document.addEventListener('DOMContentLoaded', initSidebarCollapse);

    // Ensure dark/light toggle is available on every page (floating if not present)
    function ensureThemeToggle() {
        if (document.getElementById('theme-toggle')) return; // already has one

        const btn = document.createElement('button');
        btn.id = 'theme-toggle';
        btn.className = 'theme-toggle';
        btn.title = 'Toggle dark/light mode';
        btn.type = 'button';
        btn.textContent = '🌙';
        btn.style.position = 'fixed';
        btn.style.top = '15px';
        btn.style.right = '15px';
        btn.style.zIndex = '9999';
        btn.style.width = '36px';
        btn.style.height = '36px';
        btn.style.fontSize = '1rem';
        btn.style.borderRadius = '50%';
        btn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';

        document.body.appendChild(btn);

        // re-attach listener
        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            btn.textContent = (newTheme === 'dark') ? '☀️' : '🌙';
            localStorage.setItem('studytwin-theme', newTheme);
        });

        // set initial text
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        btn.textContent = (current === 'dark') ? '☀️' : '🌙';
    }

    document.addEventListener('DOMContentLoaded', ensureThemeToggle);
})();
</script>
<?php
}
}
?>