<?php
// Common layout CSS variables and shared styles

$shared_css = '
:root {
    --bg:        #F5F1EA;
    --surface:   #FDFAF5;
    --border:    #DDD8CF;
    --ink:       #1A1714;
    --ink-2:     #4A4540;
    --ink-3:     #8A8480;
    --accent:    #1C1915;
    --sidebar-w: 260px;
    --top-h:     72px;
    --radius:    2px;
}

html, body {
    min-height: 100%;
    background: var(--bg);
    font-family: "DM Sans", sans-serif;
    font-size: 14px;
    color: var(--ink);
    -webkit-font-smoothing: antialiased;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.shell { display: flex; min-height: 100vh; }

/* ── Sidebar ──────────────────────────────── */
.sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: var(--ink);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 200;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
}

.sidebar-logo {
    padding: 32px 28px 28px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar-logo-mark {
    font-family: "DM Serif Display", serif;
    font-size: 22px;
    color: #fff;
    letter-spacing: -0.5px;
}

.sidebar-logo-sub {
    font-size: 11px;
    color: rgba(255,255,255,0.35);
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 4px;
}

.sidebar-section { padding: 20px 0 8px; }

.sidebar-section-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.25);
    padding: 0 28px;
    margin-bottom: 8px;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 28px;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    font-size: 13.5px;
    transition: color 0.15s, background 0.15s;
    position: relative;
}

.sidebar-nav a svg {
    width: 16px; height: 16px;
    flex-shrink: 0;
    opacity: 0.6;
    transition: opacity 0.15s;
}

.sidebar-nav a:hover { color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.04); }
.sidebar-nav a:hover svg { opacity: 1; }

.sidebar-nav a.active {
    color: #fff;
    background: rgba(255,255,255,0.07);
}

.sidebar-nav a.active::before {
    content: "";
    position: absolute;
    left: 0; top: 6px; bottom: 6px;
    width: 3px;
    background: var(--accent);
    border-radius: 0 2px 2px 0;
}

.sidebar-nav a.active svg { opacity: 1; }

.sidebar-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 8px 0; }

.sidebar-footer {
    margin-top: auto;
    padding: 20px 28px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.sf-row { display: flex; align-items: center; gap: 12px; }

.sf-avatar {
    width: 34px; height: 34px;
    background: var(--accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    color: #fff;
    flex-shrink: 0;
    overflow: hidden;
}

.sf-avatar img { width: 100%; height: 100%; object-fit: cover; }

.sf-name { font-size: 13px; font-weight: 500; color: #fff; line-height: 1.2; }

.sf-action {
    font-size: 11.5px;
    color: rgba(255,255,255,0.35);
    text-decoration: none;
    transition: color 0.15s;
    display: block;
    margin-top: 2px;
}

.sf-action:hover { color: rgba(255,255,255,0.65); }

.sf-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    transition: opacity 0.15s;
}

.sf-link:hover { opacity: 0.8; }

/* ── Main ─────────────────────────────────── */
.main {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ── Topbar ───────────────────────────────── */
.topbar {
    height: var(--top-h);
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.menu-toggle {
    display: none;
    width: 38px; height: 38px;
    background: var(--ink);
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.menu-toggle svg { width: 18px; height: 18px; stroke: #fff; }

.topbar-title {
    font-family: "DM Serif Display", serif;
    font-size: 22px;
    color: var(--ink);
    letter-spacing: -0.3px;
}

.topbar-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--ink-3);
}

.topbar-breadcrumb a { color: var(--ink-3); text-decoration: none; }
.topbar-breadcrumb a:hover { color: var(--ink); }
.topbar-breadcrumb span { opacity: 0.5; }

.topbar-actions { display: flex; align-items: center; gap: 10px; }

/* ── Content ──────────────────────────────── */
.content {
    padding: 36px 40px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* ── Buttons ──────────────────────────────── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    font-family: "DM Sans", sans-serif;
    font-size: 12.5px;
    font-weight: 500;
    letter-spacing: 0.2px;
    border-radius: var(--radius);
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}

.btn svg { width: 14px; height: 14px; flex-shrink: 0; }

.btn-ghost {
    background: transparent;
    border-color: var(--border);
    color: var(--ink-2);
}

.btn-ghost:hover {
    background: var(--bg);
    border-color: var(--ink-3);
    color: var(--ink);
}

.btn-primary {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
}

.btn-primary:hover { background: #2C2825; border-color: #2C2825; }

.btn-sm { padding: 6px 13px; font-size: 12px; }

.btn-danger-ghost {
    background: transparent;
    border-color: var(--border);
    color: var(--ink-3);
}

.btn-danger-ghost:hover {
    background: #fdf0ec;
    border-color: #e8a090;
    color: #c0392b;
}

.btn-danger {
    background: #c0392b;
    border-color: #c0392b;
    color: #fff;
}

.btn-danger:hover { background: #a93226; border-color: #a93226; }

/* ── Alerts ───────────────────────────────── */
.alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: var(--radius);
    margin-bottom: 24px;
    font-size: 13.5px;
    border: 1px solid;
}

.alert svg { width: 16px; height: 16px; flex-shrink: 0; }

.alert-success {
    background: #F0F7F0;
    border-color: #B4D9B4;
    color: #2A6B2A;
}

.alert-danger {
    background: #FDF1F0;
    border-color: #F0B4A8;
    color: #8B2310;
}

/* ── Form Elements ─────────────────────────── */
.form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 32px 36px;
    flex: 1;
}

.form-group { margin-bottom: 22px; }

.form-label {
    display: block;
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: var(--ink-3);
    margin-bottom: 8px;
}

.form-label .required { color: var(--accent); margin-left: 2px; }

.form-control {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: "DM Sans", sans-serif;
    font-size: 14px;
    color: var(--ink);
    outline: none;
    transition: border-color 0.15s;
}

.form-control::placeholder { color: var(--ink-3); }

.form-control:focus {
    border-color: var(--ink-3);
    background: #fff;
}

textarea.form-control {
    resize: vertical;
    min-height: 200px;
}

.form-hint { font-size: 12px; color: var(--ink-3); margin-top: 6px; }

.form-card-title {
    font-family: "DM Serif Display", serif;
    font-size: 20px;
    color: var(--ink);
    margin-bottom: 28px;
    letter-spacing: -0.2px;
}

.form-actions { display: flex; align-items: center; gap: 10px; }

/* ── Overlay ──────────────────────────────── */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 199;
    backdrop-filter: blur(2px);
}

/* ── Responsive ───────────────────────────── */
@media (max-width: 991px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.open { display: block; }
    .main { margin-left: 0; }
    .menu-toggle { display: flex; }
    .topbar { padding: 0 20px; }
    .content { padding: 24px 20px; }
}

@media (max-width: 600px) {
    .form-card { padding: 24px; }
    .form-card-title { font-size: 18px; }
}
';