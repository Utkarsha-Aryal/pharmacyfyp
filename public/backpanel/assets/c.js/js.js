const chartElement = document.querySelector('#chart').getContext('2d');

new Chart(chartElement, {
  type: 'line',
  data: {
    labels: [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ],
    datasets: [
      {
        label: 'Men',
        data: [10, 13, 12, 9, 2, 4, 2, 6, 6, 3, 2, 4],
        borderColor: 'red',
        borderWidth: 2,
      },
      {
        label: 'Women',
        data: [1, 2, 3, 6, 4, 3, 6, 2, 9, 10, 11, 12],
        borderColor: 'yellow',
        borderWidth: 2,
      },
      {
        label: 'Kid',
        data: [3, 4, 5, 6, 7, 8, 1, 2, 12, 11, 9, 10],
        borderColor: 'blue',
        borderWidth: 2,
      },
    ],
  },
  options: {
    responsive: true,
  },
});

const sidebar = document.querySelector('.sidebar');
const close = document.querySelector('.sidebar_close-btn');
const open = document.querySelector('.nav_menu-btn');

open.addEventListener('click', () => {
  sidebar.style.display = 'flex';
});
close.addEventListener('click', () => {
  sidebar.style.display = 'none';
});

const themeBtn = document.querySelector('.nav_theme-btn');

themeBtn.addEventListener('click', () => {
  document.body.classList.toggle('dark-theme');
  if (document.body.classList.contains('dark-theme')) {
    themeBtn.innerHTML = `<i class="uil uil-sun"></i>`;
    localStorage.setItem('currentTheme', 'dark-theme');
  } else {
    themeBtn.innerHTML = `<i class="uil uil-moon"></i>`;
    localStorage.setItem('currentTheme', '');
  }
});

document.body.className = localStorage.getItem('currentTheme') || '';
if (document.body.classList.contains('dark-theme')) {
  themeBtn.innerHTML = `<i class="uil uil-sun"></i>`;
} else {
  themeBtn.innerHTML = `<i class="uil uil-moon"></i>`;
}
