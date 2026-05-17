/* Desktop sidebar: collapsed by default, hover-expand, pin via toggle + localStorage */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const sidebar = document.querySelector('.ppmf-sidebar');
  const toggles = document.querySelectorAll('#sidebarToggle, .header-toggle, .sidebar-toggle');

  if (!sidebar) return;

  const STORAGE_KEY = 'ppmfSidebarPinned';
  const isMobile = () => window.matchMedia && window.matchMedia('(max-width: 1024px)').matches;

  function readPinned() {
    try {
      return localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  function writePinned(pinned) {
    try {
      localStorage.setItem(STORAGE_KEY, pinned ? '1' : '0');
    } catch (e) {}
  }

  function applyPinned(pinned) {
    if (pinned) {
      root.classList.add('ppmf-sidebar-pinned');
      root.classList.remove('ppmf-sidebar-collapsed');
    } else {
      root.classList.remove('ppmf-sidebar-pinned');
      root.classList.add('ppmf-sidebar-collapsed');
    }
  }

  // Initial desktop state
  applyPinned(readPinned());

  // Toggle button pins/unpins on desktop (mobile handled by initSidebar in ppmf-dashboard.js)
  toggles.forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (isMobile()) return;
      e.preventDefault();
      const nextPinned = !root.classList.contains('ppmf-sidebar-pinned');
      writePinned(nextPinned);
      applyPinned(nextPinned);
    });
  });

  // If screen resizes to desktop, ensure pinned/collapsed classes are re-applied
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      sidebar.classList.remove('open');
      const overlay = document.querySelector('.sidebar-overlay');
      if (overlay) overlay.classList.remove('show');
      applyPinned(readPinned());
    }
  });
});
