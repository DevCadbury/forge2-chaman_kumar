import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api, getToken, setToken } from '../api/client.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!getToken()) {
      setLoading(false);
      return;
    }

    api
      .get('/me')
      .then((data) => setUser(data.data))
      .catch(() => setToken(null))
      .finally(() => setLoading(false));
  }, []);

  async function login(email, password) {
    const data = await api.post('/login', { email, password });
    setToken(data.token);
    setUser(data.user);
  }

  async function register(payload) {
    const data = await api.post('/register', payload);
    setToken(data.token);
    setUser(data.user);
  }

  async function logout() {
    try {
      await api.post('/logout');
    } catch {
      // ignore network errors on logout
    }
    setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
