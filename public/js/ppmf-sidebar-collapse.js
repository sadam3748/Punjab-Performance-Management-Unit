/* Desktop sidebar: collapsed by default, hover-expand, pin via toggle + localStorage */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const sidebar = document.querySelector('.ppmf-sidebar');
  const toggles = document.querySelectorAll('#sidebarToggle, .header-toggle, .sidebar-toggle');

  if (!sidebar) return;

  const STORAGE_KEY = 'ppmuSidebarExpandedV3';
  const isMobile = () => window.matchMedia && window.matchMedia('(max-width: 1024px)').matches;

  function readPinned() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      return saved === '1';
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
    root.classList.remove('ppmu-sidebar-peek');
    if (pinned) {
      root.classList.add('ppmf-sidebar-pinned');
      root.classList.remove('ppmf-sidebar-collapsed');
    } else {
      root.classList.remove('ppmf-sidebar-pinned');
      root.classList.add('ppmf-sidebar-collapsed');
    }
  }

  function openPeek() {
    if (!isMobile() && !root.classList.contains('ppmf-sidebar-pinned')) {
      root.classList.add('ppmu-sidebar-peek');
    }
  }

  function closePeek() {
    root.classList.remove('ppmu-sidebar-peek');
  }

  // Initial desktop state
  applyPinned(readPinned());

  sidebar.addEventListener('mouseenter', openPeek);
  sidebar.addEventListener('mouseleave', closePeek);

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
      closePeek();
      const overlay = document.querySelector('.sidebar-overlay');
      if (overlay) overlay.classList.remove('show');
      applyPinned(readPinned());
    }
  });
});
