import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { LoaderCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

export default function ProtectedRoute({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth();
  const location = useLocation();
  if (loading) return <div className="screen-loader"><LoaderCircle className="spin" size={32} /><span>Securing your session…</span></div>;
  if (!user) return <Navigate to="/login" replace state={{ from: location }} />;
  return children;
}
