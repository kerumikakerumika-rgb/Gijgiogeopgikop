const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('nav');
menuToggle.addEventListener('click', () => nav.classList.toggle('show'));
nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => nav.classList.remove('show')));

function openModal(id) {
  document.querySelectorAll('.modal').forEach(modal => {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  });
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    const firstInput = modal.querySelector('input');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
  }
}
function closeModal(modal) {
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
}
document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => openModal(button.dataset.open)));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => closeModal(button.closest('.modal'))));
document.querySelectorAll('[data-switch]').forEach(button => button.addEventListener('click', () => openModal(button.dataset.switch)));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal);
});

async function sendAuth(form, action) {
  const message = form.parentElement.querySelector('.form-message');
  const submit = form.querySelector('button[type="submit"]');
  message.textContent = 'Пожалуйста, подождите...';
  submit.disabled = true;
  try {
    const data = Object.fromEntries(new FormData(form).entries());
    const response = await fetch('auth.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ action, ...data })
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Не удалось выполнить запрос.');
    message.textContent = result.message;
    if (result.success) setTimeout(() => window.location.reload(), 800);
  } catch (error) {
    message.textContent = error.message || 'Ошибка соединения с сервером.';
  } finally {
    submit.disabled = false;
  }
}
document.getElementById('loginForm').addEventListener('submit', event => {
  event.preventDefault();
  sendAuth(event.currentTarget, 'login');
});
document.getElementById('registerForm').addEventListener('submit', event => {
  event.preventDefault();
  sendAuth(event.currentTarget, 'register');
});
const logoutButton = document.getElementById('logoutButton');
if (logoutButton) {
  logoutButton.addEventListener('click', async () => {
    try {
      const response = await fetch('auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ action: 'logout' })
      });
      const result = await response.json();
      if (result.success) window.location.reload();
      else alert(result.message || 'Не удалось выйти.');
    } catch (error) {
      alert('Ошибка соединения с сервером.');
    }
  });
}


// Слайдер из шести изображений: стрелки, точки и автоматическая прокрутка.
(() => {
  const track = document.getElementById('sliderTrack');
  const dots = [...document.querySelectorAll('#sliderDots .dot')];
  const slides = [...document.querySelectorAll('.slide')];
  const windowEl = document.querySelector('.slider-window');
  if (!track || !slides.length) return;
  let index = 0;
  let timer;
  function visibleCount() {
    return 1; // один слайд — одно изображение
  }
  function show(next) {
    const max = slides.length - visibleCount();
    index = (next + max + 1) % (max + 1);
    const gap = parseFloat(getComputedStyle(track).gap) || 0;
    const slideWidth = slides[0].getBoundingClientRect().width;
    track.style.transform = `translateX(-${index * (slideWidth + gap)}px)`;
    dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
  }
  document.getElementById('prevSlide').addEventListener('click', () => { show(index - 1); restart(); });
  document.getElementById('nextSlide').addEventListener('click', () => { show(index + 1); restart(); });
  dots.forEach((dot, i) => dot.addEventListener('click', () => { show(Math.min(i, slides.length - visibleCount())); restart(); }));
  function restart() { clearInterval(timer); timer = setInterval(() => show(index + 1), 4000); }
  window.addEventListener('resize', () => show(index));
  show(0);
  restart();
})();
