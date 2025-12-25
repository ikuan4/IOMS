// resources/js/login.js
(function() {
  // Only run on the login page (layout-root present)
  const root = document.querySelector('.layout-root');
  if (!root) return; // not the login page — bail

  const pwd = document.getElementById('password');
  const toggle = document.getElementById('pwd-toggle');
  const eyeOn = document.getElementById('eye-on');
  const eyeOff = document.getElementById('eye-off');

  if (!pwd) return console.warn('login.js: password input not found — skipping toggle');
  if (!toggle) return console.warn('login.js: pwd-toggle button not found — skipping toggle');

  // Defensive: if icons missing, keep going but warn
  if (!eyeOn || !eyeOff) console.warn('login.js: one or both eye icons missing (non-fatal)');

  // Remove previous handlers to avoid duplicates (clone node trick)
  const newToggle = toggle.cloneNode(true);
  toggle.parentNode.replaceChild(newToggle, toggle);

  newToggle.addEventListener('click', function (e) {
    e.preventDefault();

    const eyeOnIcon = newToggle.querySelector('#eye-on');
    const eyeOffIcon = newToggle.querySelector('#eye-off');

    if (pwd.type === 'password') {
      pwd.type = 'text';
      if (eyeOnIcon) eyeOnIcon.style.display = 'none';
      if (eyeOffIcon) eyeOffIcon.style.display = 'block';
    } else {
      pwd.type = 'password';
      if (eyeOnIcon) eyeOnIcon.style.display = 'block';
      if (eyeOffIcon) eyeOffIcon.style.display = 'none';
    }
    pwd.focus();
  }, { passive: false });

})();

// Toggle between mobile and email sign-in
(function(){
  const toggleContainer = document.querySelector('.toggle-buttons');
  if (!toggleContainer) return;

  const mobileBtn = toggleContainer.querySelector('[data-mode="mobile"]');
  const emailBtn = toggleContainer.querySelector('[data-mode="email"]');
  const mobileInput = document.getElementById('mobile');
  const emailInput = document.getElementById('email');
  const mobileLabel = document.getElementById('mobile-label');
  const emailLabel = document.getElementById('email-label');
  const mobileHelp = document.getElementById('mobile-help');
  const emailHelp = document.getElementById('email-help');

  function setMode(mode){
    if(mode === 'email'){
      mobileBtn.classList.remove('active'); mobileBtn.setAttribute('aria-pressed','false');
      emailBtn.classList.add('active'); emailBtn.setAttribute('aria-pressed','true');

      if(mobileInput){ mobileInput.style.display='none'; mobileInput.disabled=true; }
      if(emailInput){ emailInput.style.display='block'; emailInput.disabled=false; }
      if(mobileLabel) mobileLabel.style.display='none';
      if(emailLabel) emailLabel.style.display='block';
      if(mobileHelp) mobileHelp.style.display='none';
      if(emailHelp) emailHelp.style.display='block';
      if(emailInput) emailInput.focus();
    } else {
      mobileBtn.classList.add('active'); mobileBtn.setAttribute('aria-pressed','true');
      emailBtn.classList.remove('active'); emailBtn.setAttribute('aria-pressed','false');

      if(mobileInput){ mobileInput.style.display='block'; mobileInput.disabled=false; }
      if(emailInput){ emailInput.style.display='none'; emailInput.disabled=true; }
      if(mobileLabel) mobileLabel.style.display='block';
      if(emailLabel) emailLabel.style.display='none';
      if(mobileHelp) mobileHelp.style.display='block';
      if(emailHelp) emailHelp.style.display='none';
      if(mobileInput) mobileInput.focus();
    }
  }

  mobileBtn.addEventListener('click', function(){ setMode('mobile'); });
  emailBtn.addEventListener('click', function(){ setMode('email'); });

  // default to mobile
  setMode('mobile');
})();
