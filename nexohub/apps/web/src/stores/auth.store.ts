import { create } from 'zustand';
import type { AuthUser, LoginInput, RegisterInput } from '@nexohub/shared';
import { apiClient } from '../lib/api-client';

interface AuthState {
  user: AuthUser | null;
  isLoading: boolean;
  hasInitialized: boolean;
  fetchMe: () => Promise<void>;
  login: (input: LoginInput) => Promise<void>;
  register: (input: RegisterInput) => Promise<void>;
  logout: () => Promise<void>;
  setUser: (user: AuthUser | null) => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isLoading: true,
  hasInitialized: false,

  fetchMe: async () => {
    try {
      const res = await apiClient<{ data: { user: AuthUser } }>('/auth/me');
      set({ user: res.data.user, isLoading: false, hasInitialized: true });
    } catch {
      set({ user: null, isLoading: false, hasInitialized: true });
    }
  },

  login: async (input) => {
    const res = await apiClient<{ data: { user: AuthUser } }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify(input),
    });
    set({ user: res.data.user });
  },

  register: async (input) => {
    const res = await apiClient<{ data: { user: AuthUser } }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(input),
    });
    set({ user: res.data.user });
  },

  logout: async () => {
    try {
      await apiClient('/auth/logout', { method: 'POST' });
    } finally {
      set({ user: null });
    }
  },

  setUser: (user) => set({ user }),
}));

