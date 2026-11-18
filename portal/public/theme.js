(() => {
  let theme;
  try { theme = localStorage.getItem('siclin-theme'); } catch {}
  document.documentElement.dataset.theme = ['light', 'dark'].includes(theme)
    ? theme : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
})();
