import React, { useState, useEffect } from 'react';
import { useAppContext } from "../context/AppContext";

export const AuthModal = () => {
  const {isOpen,setIsOpen} = useAppContext();
  const [isRegister, setIsRegister] = useState(false); // true - Регистрация, false - Вход

  // Поля формы
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  // Состояния загрузки и ошибок
  const [loadingGoogle, setLoadingGoogle] = useState(false);
  const [loadingForm, setLoadingForm] = useState(false);
  const [error, setError] = useState(null);

  // Закрытие по клавише Escape
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') setIsOpen(false);
    };
    if (isOpen) {
      window.addEventListener('keydown', handleKeyDown);
    }
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen]);

  // Сброс полей при переключении режима
  const toggleMode = () => {
    setIsRegister(!isRegister);
    setError(null);
  };

  // 1. Авторизация / Регистрация через Google
  const handleGoogleLogin = async () => {
    try {
      setLoadingGoogle(true);
      setError(null);

      const response = await fetch('http://localhost:8000/api/auth/google');
      if (!response.ok) {
        throw new Error('Не удалось получить ссылку для авторизации Google');
      }

      const data = await response.json();
      window.location.href = data.url;
    } catch (err) {
      setError(err.message || 'Произошла ошибка при входе через Google');
    } finally {
      setLoadingGoogle(false);
    }
  };

  // 2. Отправка формы (Вход или Регистрация по email)
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setLoadingForm(true);
      setError(null);

      // Определяем URL в зависимости от режима
      const endpoint = isRegister 
        ? 'http://localhost:8000/api/register' 
        : 'http://localhost:8000/api/login';

      const payload = isRegister 
        ? { name, email, password } 
        : { email, password };

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || (isRegister ? 'Ошибка при регистрации' : 'Ошибка при входе'));
      }

      // Сохраняем Sanctum-токен
      if (data.token) {
        localStorage.setItem('auth_token', data.token);
      }

      setIsOpen(false);
      alert(isRegister ? 'Регистрация прошла успешно!' : 'Успешная авторизация!');
    } catch (err) {
      setError(err.message || 'Произошла ошибка');
    } finally {
      setLoadingForm(false);
    }
  };

  return (
    <div>
     

      {/* Модальное окно */}
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          {/* Клик по фону для закрытия */}
          <div className="absolute inset-0" onClick={() => setIsOpen(false)} />

          <div className="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            {/* Крестик */}
            <button
              onClick={() => setIsOpen(false)}
              className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 p-1"
            >
              ✕
            </button>

            {/* Динамический заголовок */}
            <h2 className="text-2xl font-bold text-gray-900 text-center mb-6">
              {isRegister ? 'Создать аккаунт' : 'Вход в аккаунт'}
            </h2>

            {/* Вывод ошибки */}
            {error && (
              <div className="mb-4 p-3 text-sm text-red-600 bg-red-50 rounded-lg border border-red-200">
                {error}
              </div>
            )}

            {/* Кнопка Google */}
            <button
              type="button"
              onClick={handleGoogleLogin}
              disabled={loadingGoogle}
              className="w-full flex items-center justify-center gap-3 py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-colors"
            >
              <svg className="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
              </svg>
              {loadingGoogle 
                ? 'Переход на Google...' 
                : ('Войти через Google')}
            </button>

            {/* Разделитель */}
            <div className="relative my-6">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-200" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">или </span>
              </div>
            </div>

            {/* Форма */}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Email
                </label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@example.com"
                  style={{color:'black'}}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                />
              </div>

              {isRegister && (
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                    Password
                    </label>
                    <input
                    type="password"
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    style={{color:'black'}}
                    placeholder="••••••••"
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                    />
                </div>
              )}
              

              {/* Динамическая кнопка отправки */}
              <button
                type="submit"
                disabled={loadingForm}
                style={{backgroundColor: '#2F3037',fontWeight:700}}
                className="w-full py-2.5 px-4 text-white font-medium rounded-lg shadow-sm disabled:opacity-50 transition-colors text-sm"
              >
                {loadingForm 
                  ? 'Загрузка...' 
                  : (isRegister ? 'Зарегистрироваться' : 'Войти')}
              </button>
            </form>

            {/* Переключатель Вход / Регистрация */}
            <div className="mt-6 text-center text-sm text-gray-600">
              {isRegister ? (
                <p>
                  Уже есть аккаунт?{' '}
                  <button
                    type="button"
                    onClick={toggleMode}
                    className="font-medium text-indigo-600 hover:text-indigo-500 underline ml-1"
                  >
                    Войти
                  </button>
                </p>
              ) : (
                <p>
                  Ещё нет аккаунта?{' '}
                  <button
                    type="button"
                    onClick={toggleMode}
                    className="font-medium text-indigo-600 hover:text-indigo-500 underline ml-1"
                  >
                    Зарегистрироваться
                  </button>
                </p>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};