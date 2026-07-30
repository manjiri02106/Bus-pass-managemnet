import { FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Mail, Send } from 'lucide-react';
import AuthLayout from '../components/AuthLayout';
import supabase from '../lib/supabase';

export default function ForgotPassword(){const[email,setEmail]=useState('');const[busy,setBusy]=useState(false);const[error,setError]=useState('');const[sent,setSent]=useState(false);
const submit=async(e:FormEvent)=>{e.preventDefault();setError('');if(!/^\S+@\S+\.\S+$/.test(email))return setError('Enter a valid email address.');setBusy(true);const{error:resetError}=await supabase.auth.resetPasswordForEmail(email.trim(),{redirectTo:`${window.location.origin}/reset-password`});setBusy(false);if(resetError)return setError(resetError.message);setSent(true);};
return <AuthLayout><div className="auth-card"><Link className="back-link" to="/login"><ArrowLeft size={17}/> Back to sign in</Link><div className="card-heading"><span className="mini-icon"><Mail size={20}/></span><h2>Reset your password</h2><p>Enter your registered email and we’ll send a secure, time-limited reset link.</p></div>
{error&&<div className="notice error">{error}</div>}{sent?<div className="success-panel"><span><Send size={25}/></span><h3>Check your inbox</h3><p>If an account exists for <strong>{email}</strong>, a verification email is on its way. Check spam if it doesn’t arrive shortly.</p><Link className="primary-button" to="/login">Return to sign in</Link></div>:<form onSubmit={submit} noValidate><div className="field-group"><label htmlFor="email">Email address</label><div className="input-shell"><Mail size={18}/><input id="email" type="email" value={email} onChange={e=>setEmail(e.target.value)} placeholder="you@example.com" autoComplete="email"/></div></div><button className="primary-button" disabled={busy}>{busy?<span className="button-loader"/>:<>Send reset link <Send size={18}/></>}</button></form>}
</div></AuthLayout>}
