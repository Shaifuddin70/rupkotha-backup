const sidebarToggleBtns = document.querySelectorAll(".sidebar-toggle");
const sidebar = document.querySelector(".sidebar");
const searchForm = document.querySelector(".search-form");
const themeToggleBtn = document.querySelector(".theme-toggle");
const themeIcon = themeToggleBtn.querySelector(".theme-icon");
const menuLinks = document.querySelectorAll(".menu-link"); // Assuming this is used elsewhere

// Updates the theme icon based on current theme and sidebar state
const updateThemeIcon = () => {
  const isDark = document.body.classList.contains("dark-theme");
  themeIcon.textContent = sidebar.classList.contains("collapsed") ? (isDark ? "light_mode" : "dark_mode") : "dark_mode";
};

// Apply dark theme if saved or system prefers, then update icon
const savedTheme = localStorage.getItem("theme");
const systemPrefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
const shouldUseDarkTheme = savedTheme === "dark" || (!savedTheme && systemPrefersDark);

document.body.classList.toggle("dark-theme", shouldUseDarkTheme);
updateThemeIcon();

// Toggle between themes on theme button click
themeToggleBtn.addEventListener("click", () => {
  const isDark = document.body.classList.toggle("dark-theme");
  localStorage.setItem("theme", isDark ? "dark" : "light");
  updateThemeIcon();
});

// --- NEW/MODIFIED CODE FOR SIDEBAR PERSISTENCE ---

// Function to set sidebar state
const setSidebarState = (isCollapsed) => {
  sidebar.classList.toggle("collapsed", isCollapsed);
  localStorage.setItem("sidebarState", isCollapsed ? "collapsed" : "expanded");
  updateThemeIcon(); // Update theme icon after sidebar state changes
};

// Load sidebar state on page load
const savedSidebarState = localStorage.getItem("sidebarState");

if (savedSidebarState === "collapsed") {
  // If saved state is collapsed, apply it
  setSidebarState(true);
} else if (savedSidebarState === "expanded") {
  // If saved state is expanded, apply it
  setSidebarState(false);
} else {
  // No saved state, apply default based on screen size (original logic)
  if (window.innerWidth > 768) {
    setSidebarState(false); // Expanded by default on large screens
  } else {
    setSidebarState(true); // Collapsed by default on small screens
  }
}

// Toggle sidebar collapsed state on buttons click
sidebarToggleBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    // Toggle the state and save it
    const isCurrentlyCollapsed = sidebar.classList.contains("collapsed");
    setSidebarState(!isCurrentlyCollapsed);
  });
});

// Expand the sidebar when the search form is clicked
searchForm.addEventListener("click", () => {
  if (sidebar.classList.contains("collapsed")) {
    setSidebarState(false); // Expand and save state
    searchForm.querySelector("input").focus();
  }
});

