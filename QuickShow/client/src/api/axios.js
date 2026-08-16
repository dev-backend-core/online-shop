import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:80',
  withCredentials: true, // ⚠️ Заставляет браузер отправлять и принимать Cookies!
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Перехватчик: вытаскиваем CSRF-токен из куки и принудительно ставим заголовок!
api.interceptors.request.use((config) => {
  const match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
  if (match) {
    // decodeURIComponent важен, так как в куках токен закодирован
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[2]);
  }
  return config;
});

export default api;