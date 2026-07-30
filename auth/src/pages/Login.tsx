import { FormEvent, useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ArrowRight, Mail, ShieldCheck } from 'lucide-react';
import AuthLayout from '../components/AuthLayout';
import PasswordField from '../components/PasswordField';
import supabase from '../lib/supabase';
import { useAuth } from '../contexts/AuthContext';

export default function Login() {
  const { user, timedOut } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  if (user) return <Navigate to="/dashboard" replace />;

  const submit = async (e: FormEvent) => {
    e.preventDefault(); setError('');
    if (!/^\S+@\S+\.\S+$/.test(email)) return setError('Enter a valid email address.');
    if (!password) return setError('Password is required.');
    setBusy(true);
    const { error: authError } = await supabase.auth.signInWithPassword({ email: email.trim(), password });
    setBusy(false);
    if (authError) return setError(authError.message === 'Invalid login credentials' ? 'Email or password is incorrect.' : authError.message);
    if (!remember) window.addEventListener('beforeunload', () => supabase.auth.signOut(), { once: true });
    navigate((location.state as { from?: { pathname?: string } })?.from?.pathname || '/dashboard', { replace: true });
  };

  return <AuthLayout><div className="auth-card">
    <div className="card-heading"><span className="mini-icon"><ShieldCheck size={20} /></span><h2>Welcome back</h2><p>Sign in to continue to your commuter portal.</p></div>
    {timedOut && <div className="notice warning">Your session ended after 15 minutes of inactivity. Please sign in again.</div>}
    {error && <div className="notice error" role="alert">{error}</div>}
    <form onSubmit={submit} noValidate>
      <div className="field-group"><label htmlFor="email">Email address</label><div className="input-shell"><Mail size={18}/><input id="email" type="email" value={email} onChange={(e)=>setEmail(e.target.value)} placeholder="you@example.com" autoComplete="email" required /></div></div>
      <PasswordField id="password" label="Password" value={password} onChange={setPassword} />
      <div className="form-options"><label className="check-label"><input type="checkbox" checked={remember} onChange={(e)=>setRemember(e.target.checked)} /> Remember me</label><Link to="/forgot-password">Forgot password?</Link></div>
      <button className="primary-button" disabled={busy}>{busy ? <span className="button-loader" /> : <>Sign in <ArrowRight size={18}/></>}</button>
    </form>
    <p className="auth-switch">New to TransitPass? <Link to="/register">Create an account</Link></p>
    <div className="secure-note"><ShieldCheck size={15}/> Protected with secure encrypted authentication</div>
  </div></AuthLayout>;
}
