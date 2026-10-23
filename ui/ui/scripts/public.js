// DOM Elements
const loginForm = document.getElementById("loginForm");
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");
const loginBtn = document.querySelector(".login-btn");
const usernameInput = document.getElementById("username");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const app_url = "?_route=";

// Verify critical elements exist (but don't exit - allow registration page features)
const isLoginPage = !!(loginForm && usernameInput && passwordInput && loginBtn);
if (!isLoginPage) {
  console.warn('Login form elements not found. This may be a registration or other page.');
}

// Get CSRF token from form data attribute (secure approach)
function getCSRFToken() {
  const loginForm = document.getElementById("loginForm");
  return loginForm ? loginForm.dataset.csrfToken || "" : "";
}

let csrfToken = getCSRFToken();

// Fallback to empty string if not set
if (!csrfToken) {
  csrfToken = "";
}

const fillAllText = window.fillAll || "Please fill in all fields";
const invalidCredentialsText = window.invalidCredentials || "Invalid username or password";
const accountInactiveText = window.accountInactive || "Your account is inactive. Please contact support.";
const successText = window.successText || "Login successful! Redirecting...";
const usernameMinLengthText = window.usernameMinLength || "Username must be at least 3 characters";
const passwordMinLengthText = window.passwordMinLength || "Password must be at least 8 characters";
const tooManyAttemptsText = window.tooManyAttempts || "Too many login attempts. Please wait before trying again.";
const tokenExpiredText = window.tokenExpired || "Security token expired. Please refresh the page and try again.";
const tokenRefreshedText = window.tokenRefreshed || "Security token refreshed. Please try again.";
const tokenRefreshErrorText = window.tokenRefreshError || "Failed to refresh security token. Please refresh the page and try again.";
const accountSuspendedText = window.accountSuspended || "Your account has been suspended. Please contact support.";
const connectionErrorText = window.connectionError || "Connection problem. Please check your internet and try again.";
const securityErrorText = window.securityError || "Security token missing. Refreshing page...";
const themeChangeText = window.themeChangeText || "Theme changed to";

// Theme management
let currentTheme = "system";
let isTogglingTheme = false;

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  initializeTheme();
  initializeAnimations();
  setupEventListeners();
});

// Get fresh CSRF token if needed
async function refreshCSRFToken() {
  try {
    const response = await fetch(app_url + "login", {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (response.ok) {
      const text = await response.text();
      // Extract CSRF token from response (if it's in a meta tag or similar)
      const tokenMatch = text.match(/csrf_token['"]\s*:\s*['"]([^'"]+)['"]/);
      if (tokenMatch) {
        // Update form data attribute instead of global window
        const loginForm = document.getElementById("loginForm");
        if (loginForm) {
          loginForm.dataset.csrfToken = tokenMatch[1];
        }
        csrfToken = tokenMatch[1]; // Update local variable too
        return tokenMatch[1];
      }
    }
  } catch (error) {
    console.log("Failed to refresh CSRF token:", error);
  }
  return null;
}

// Validate CSRF token before making requests
function validateCSRFToken() {
  if (!csrfToken || csrfToken.length === 0) {
    showNotification(securityErrorText, "warning");
    setTimeout(() => {
      window.location.reload();
    }, 1500);
    return false;
  }
  return true;
}

// Setup event listeners
function setupEventListeners() {
  // Theme toggle
  if (themeToggle) {
    themeToggle.addEventListener("click", function (e) {
      e.preventDefault();
      //console.log("Theme toggle clicked, current theme:", currentTheme);
      toggleTheme();
    });
  }

  // Toggle password visibility with accessibility
  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function (e) {
      e.preventDefault();
      const isPassword = passwordInput.getAttribute("type") === "password";
      const newType = isPassword ? "text" : "password";
      const newPressed = isPassword ? "true" : "false";
      
      // Get localized labels or fallback to English
      const showText = window.showPasswordText || "Show password";
      const hideText = window.hidePasswordText || "Hide password";
      const newLabel = isPassword ? hideText : showText;

      // Update input type
      passwordInput.setAttribute("type", newType);
      
      // Update button accessibility attributes
      this.setAttribute("aria-pressed", newPressed);
      this.setAttribute("aria-label", newLabel);

      // Update icon
      const icon = this.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
      }
    });
  }

  // Toggle confirm password visibility (for registration page)
  const togglePasswordConfirm = document.getElementById("togglePasswordConfirm");
  const cpasswordInput = document.getElementById("cpassword");
  
  if (togglePasswordConfirm && cpasswordInput) {
    togglePasswordConfirm.addEventListener("click", function (e) {
      e.preventDefault();
      const isPassword = cpasswordInput.getAttribute("type") === "password";
      const newType = isPassword ? "text" : "password";
      const newPressed = isPassword ? "true" : "false";
      
      // Get localized labels or fallback to English
      const showText = window.showPasswordText || "Show password";
      const hideText = window.hidePasswordText || "Hide password";
      const newLabel = isPassword ? hideText : showText;

      // Update input type
      cpasswordInput.setAttribute("type", newType);
      
      // Update button accessibility attributes
      this.setAttribute("aria-pressed", newPressed);
      this.setAttribute("aria-label", newLabel);

      // Update icon
      const icon = this.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
      }
    });
  }

  // Form submission (only for login form)
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      handleLogin();
    });
  }

  // Input focus animations
  const inputs = document.querySelectorAll(
    'input[type="text"], input[type="password"]'
  );
  inputs.forEach((input) => {
    input.addEventListener("focus", function () {
      this.parentElement.classList.add("focused");
    });

    input.addEventListener("blur", function () {
      if (!this.value) {
        this.parentElement.classList.remove("focused");
      }
    });
  });

  // Connection button clicks
  const connectionBtns = document.querySelectorAll(".connection-btn");
  connectionBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const connectionType = this.querySelector("span").textContent;
      showNotification(`Connecting via ${connectionType}...`, "info");
    });
  });

  // Listen for system theme changes
  if (window.matchMedia) {
    window
      .matchMedia("(prefers-color-scheme: dark)")
      .addEventListener("change", function () {
        if (currentTheme === "system") {
          updateThemeIcon();
        }
      });
  }
}

// Handle login process
async function handleLogin() {
  const username = usernameInput.value.trim();
  const password = passwordInput.value;

  // Client-side validation
  if (!username || !password) {
    showNotification(fillAllText, "error");
    usernameInput.focus();
    return;
  }

  if (username.length < 3) {
    showNotification(usernameMinLengthText, "error");
    usernameInput.focus();
    return;
  }

  if (password.length < 4) {
    showNotification(passwordMinLengthText, "error");
    passwordInput.focus();
    return;
  }

  // Validate CSRF token
  if (!validateCSRFToken()) {
    return;
  }

  // Show loading state
  showLoginLoading(true);

  // Disable inputs during login
  usernameInput.disabled = true;
  passwordInput.disabled = true;

  try {
    // Perform actual login
    const result = await performLoginAPI(username, password);

    showNotification(successText, "success");

    // Clear form for security
    passwordInput.value = '';
    usernameInput.value = '';

    // Redirect to dashboard
    setTimeout(() => {
      window.location.href = app_url + "home";
    }, 1500);
  } catch (error) {
    // Handle specific error types
    let errorMessage = error.message;

    if (errorMessage.includes("CSRF") || errorMessage.includes("csrf")) {
      showNotification(
        tokenExpiredText,
        "warning"
      );
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else if (errorMessage.includes("Too many")) {
      showNotification(
        tooManyAttemptsText,
        "warning"
      );
    } else if (
      errorMessage.includes("Banned") ||
      errorMessage.includes("banned")
    ) {
      showNotification(
        accountSuspendedText,
        "error"
      );
    } else if (errorMessage.includes("Network error")) {  
      showNotification(
        connectionErrorText,
        "warning"
      );
    } else {
      showNotification(
        errorMessage || "Login failed. Please try again.",
        "error"
      );
    }

    // Reset captcha on failure so user can re-verify without refreshing
    if (typeof turnstile !== "undefined" && typeof turnstile.reset === "function") {
      try { turnstile.reset(); } catch (e) {}
    }
    if (typeof grecaptcha !== "undefined" && typeof grecaptcha.reset === "function") {
      try { grecaptcha.reset(); } catch (e) {}
    }

    // Clear password field on error for security
    passwordInput.value = "";
    passwordInput.focus();
  } finally {
    // Hide loading state and re-enable inputs
    showLoginLoading(false);
    usernameInput.disabled = false;
    passwordInput.disabled = false;
  }
}

// Perform actual login API call
async function performLoginAPI(username, password) {
  try {
    // Prepare form data with CSRF token
    const formData = new URLSearchParams({
      username: username,
      password: password,
    });

    // Add CSRF token if available
    if (csrfToken) {
      formData.append("csrf_token", csrfToken);
    }

    // Add Captcha tokens if available
    let turnstileToken = "";
    if (typeof turnstile !== "undefined" && typeof turnstile.getResponse === "function") {
      try { turnstileToken = turnstile.getResponse() || ""; } catch (e) {}
    }
    if (!turnstileToken) {
      const turnstileInput = document.querySelector('[name="cf-turnstile-response"]');
      if (turnstileInput && turnstileInput.value) {
        turnstileToken = turnstileInput.value;
      }
    }
    if (turnstileToken) {
      formData.append("cf-turnstile-response", turnstileToken);
    }

    let recaptchaToken = "";
    if (typeof grecaptcha !== "undefined" && typeof grecaptcha.getResponse === "function") {
      try { recaptchaToken = grecaptcha.getResponse() || ""; } catch (e) {}
    }
    if (!recaptchaToken) {
      const recaptchaInput = document.querySelector('[name="g-recaptcha-response"]');
      if (recaptchaInput && recaptchaInput.value) {
        recaptchaToken = recaptchaInput.value;
      }
    }
    if (recaptchaToken) {
      formData.append("g-recaptcha-response", recaptchaToken);
    }

    const response = await fetch(app_url + "login/api", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      return data;
    } else {
      const resetCaptchaWidgets = () => {
        if (typeof turnstile !== "undefined" && turnstile.reset) {
          try { turnstile.reset(); } catch (e) {}
        }
        if (typeof grecaptcha !== "undefined" && grecaptcha.reset) {
          try { grecaptcha.reset(); } catch (e) {}
        }
      };

      // If CSRF token error, try to refresh and retry once
      if (
        data.message &&
        (data.message.includes("CSRF") || data.message.includes("csrf"))
      ) {
        const newToken = await refreshCSRFToken();
        if (newToken) {
          // Update the local variable
          csrfToken = newToken;
          
          // Retry with new token
          const retryFormData = new URLSearchParams({
            username: username,
            password: password,
            csrf_token: newToken,
          });
          if (turnstileToken) {
            retryFormData.append("cf-turnstile-response", turnstileToken);
          }
          if (recaptchaToken) {
            retryFormData.append("g-recaptcha-response", recaptchaToken);
          }

          try {
            const retryResponse = await fetch(app_url + "login/api", {
              method: "POST",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest",
              },
              body: retryFormData,
            });

            if (retryResponse.ok) {
              const retryData = await retryResponse.json();
              if (retryData.success) {
                return retryData;
              }
            }
          } catch (retryError) {
            // Network/parse failure on retry: fall through, reset widgets and report the original failure
          }
        }
      }
      // Reset captcha widgets only after the attempt (and any retry) failed
      resetCaptchaWidgets();
      throw new Error(data.message || "Login failed");
    }
  } catch (error) {
    // Handle network errors or parsing errors
    if (error.name === "TypeError" || /failed to fetch|networkerror/i.test(error.message)) {
      throw new Error("Network error. Please check your connection.");
    }
    throw error;
  }
}

// Show notification
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notif) => notif.remove());

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;

  // Add styles
  notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        transform: translateX(100%);
        transition: all 0.3s ease;
        font-weight: 500;
        backdrop-filter: blur(10px);
    `;

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 100);

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 300);
  }, 5000);
}

// Show/hide login loading state
function showLoginLoading(show) {
  if (!loginBtn) {
   // console.error('Login button not found');
    return;
  }

  if (show) {
    // Just add the loading class - the CSS handles the rest
    loginBtn.classList.add("loading");
    loginBtn.disabled = true;
  } else {
    // Remove loading class and re-enable button
    loginBtn.classList.remove("loading");
    loginBtn.disabled = false;
  }
}

// Get notification icon based on type
function getNotificationIcon(type) {
  switch (type) {
    case "success":
      return "fa-check-circle";
    case "error":
      return "fa-exclamation-circle";
    case "warning":
      return "fa-exclamation-triangle";
    default:
      return "fa-info-circle";
  }
}

// Get notification color based on type
function getNotificationColor(type) {
  switch (type) {
    case "success":
      return "linear-gradient(135deg, #48bb78, #38a169)";
    case "error":
      return "linear-gradient(135deg, #f56565, #e53e3e)";
    case "warning":
      return "linear-gradient(135deg, #ed8936, #dd6b20)";
    default:
      return "linear-gradient(135deg, #667eea, #764ba2)";
  }
}

// Theme Management Functions
function initializeTheme() {
  // Get saved theme or default to system
  const savedTheme = localStorage.getItem("theme") || "system";

  // Validate the saved theme
  if (["system", "light", "dark"].includes(savedTheme)) {
    currentTheme = savedTheme;
  } else {
    currentTheme = "system";
    localStorage.setItem("theme", "system");
  }

  applyTheme();
  updateThemeIcon();
}

function toggleTheme() {
  if (isTogglingTheme) {
    return;
  }

  isTogglingTheme = true;

  // Cycle through: system -> light -> dark -> system
  switch (currentTheme) {
    case "system":
      currentTheme = "light";
      break;
    case "light":
      currentTheme = "dark";
      break;
    case "dark":
      currentTheme = "system";
      break;
    default:
      // Reset to system if unknown value
      currentTheme = "system";
      break;
  }

  localStorage.setItem("theme", currentTheme);
  applyTheme();
  updateThemeIcon();
  showNotification(`${themeChangeText} ${currentTheme}`, "info");

  setTimeout(() => {
    isTogglingTheme = false;
  }, 100);
}

function applyTheme() {
  const html = document.documentElement;

  // Remove existing theme classes
  html.classList.remove("theme-light", "theme-dark");

  if (currentTheme === "light") {
    html.classList.add("theme-light");
    html.style.colorScheme = "light";
    //console.log("Applied light theme");
  } else if (currentTheme === "dark") {
    html.classList.add("theme-dark");
    html.style.colorScheme = "dark";
    // console.log("Applied dark theme");
  } else {
    html.style.colorScheme = "";
    // console.log("Applied system theme");
  }
}

function updateThemeIcon() {
  const isDarkMode =
    currentTheme === "dark" ||
    (currentTheme === "system" &&
      window.matchMedia("(prefers-color-scheme: dark)").matches);

  if (currentTheme === "system") {
    themeIcon.className = "fas fa-circle-half-stroke";
  } else if (isDarkMode) {
    themeIcon.className = "fas fa-sun";
  } else {
    themeIcon.className = "fas fa-moon";
  }
}

// Initialize animations
function initializeAnimations() {
  // Animate elements on load
  const animatedElements = document.querySelectorAll(
    ".login-card, .announcement-panel"
  );
  animatedElements.forEach((element, index) => {
    element.style.opacity = "0";
    element.style.transform = "translateY(30px)";

    setTimeout(() => {
      element.style.transition = "all 0.8s cubic-bezier(0.4, 0, 0.2, 1)";
      element.style.opacity = "1";
      element.style.transform = "translateY(0)";
    }, index * 200);
  });
}


// Add keyboard shortcuts
document.addEventListener("keydown", function (e) {
  // Enter key to submit form
  if (
    e.key === "Enter" &&
    (e.target === usernameInput || e.target === passwordInput)
  ) {
    e.preventDefault();
    if (!loginBtn.disabled) {
      loginForm.dispatchEvent(new Event("submit"));
    }
  }

  // ESC key to clear form
  if (e.key === "Escape") {
    if (!usernameInput.disabled && !passwordInput.disabled) {
      usernameInput.value = "";
      passwordInput.value = "";
      usernameInput.focus();
      showNotification("Form cleared", "info");
    }
  }
});

// Auto-focus username field when page loads
window.addEventListener("load", function () {
  if (usernameInput && !usernameInput.disabled) {
    usernameInput.focus();
  }
});

// Add ripple effect to buttons
function createRipple(event) {
  const button = event.currentTarget;
  const circle = document.createElement("span");
  const diameter = Math.max(button.clientWidth, button.clientHeight);
  const radius = diameter / 2;

  const rect = button.getBoundingClientRect();
  const left = event.clientX - rect.left - radius;
  const top = event.clientY - rect.top - radius;

  circle.style.cssText = `
        width: ${diameter}px;
        height: ${diameter}px;
        left: ${left}px;
        top: ${top}px;
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple 600ms linear;
        pointer-events: none;
    `;

  const rippleEffect = button.querySelector(".ripple");
  if (rippleEffect) {
    rippleEffect.remove();
  }

  circle.classList.add("ripple");
  button.style.position = "relative";
  button.style.overflow = "hidden";
  button.appendChild(circle);
}

// Add ripple effect CSS
const rippleCSS = `
@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
`;

const style = document.createElement("style");
style.textContent = rippleCSS;
document.head.appendChild(style);

// Add ripple effect to buttons
document.querySelectorAll(".login-btn, .connection-btn").forEach((button) => {
  button.addEventListener("click", createRipple);
});

window.logout = function () {
  // Clean up client-side session data
  sessionStorage.removeItem("userDisplay");
  
  // Security: Auth tokens should be cleared server-side via logout endpoint
  // TODO: Call server logout endpoint to invalidate httpOnly cookies
  // Example: fetch(app_url + "logout", { method: "POST" });
  
  showNotification("Logged out successfully", "success");
  setTimeout(() => {
    window.location.href = app_url + "login";
  }, 1000);
};
