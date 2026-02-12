const root = document.documentElement;
const themeToggle = document.getElementById('themeToggle');
const menuBtn = document.getElementById('menuBtn');
const navLinks = document.getElementById('navLinks');
const intro = document.getElementById('intro');
const form = document.getElementById('contactForm');
const formFeedback = document.getElementById('formFeedback');

const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
  root.setAttribute('data-theme', savedTheme);
}

const syncThemeButton = () => {
  const isLight = root.getAttribute('data-theme') === 'light';
  themeToggle.textContent = isLight ? '☀️' : '🌙';
};

syncThemeButton();

themeToggle.addEventListener('click', () => {
  const current = root.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  root.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  syncThemeButton();
});

menuBtn.addEventListener('click', () => {
  navLinks.classList.toggle('open');
});

for (const link of navLinks.querySelectorAll('a')) {
  link.addEventListener('click', () => navLinks.classList.remove('open'));
}

const observer = new IntersectionObserver(
  entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.15 }
);

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

document.getElementById('year').textContent = new Date().getFullYear();

window.addEventListener('load', () => {
  setTimeout(() => {
    intro.style.display = 'none';
  }, 1850);
});

if (form) {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';
    formFeedback.textContent = '';

    try {
      const response = await fetch('send-email.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      formFeedback.textContent = result.message;
      formFeedback.classList.toggle('success', response.ok && result.success);
      formFeedback.classList.toggle('error', !response.ok || !result.success);

      if (response.ok && result.success) {
        form.reset();
      }
    } catch (error) {
      formFeedback.textContent = 'Falha ao enviar mensagem. Tente novamente em instantes.';
      formFeedback.classList.add('error');
      formFeedback.classList.remove('success');
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = 'Enviar mensagem';
    }
  });
}