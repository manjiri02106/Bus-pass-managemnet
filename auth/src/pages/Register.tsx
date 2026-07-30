import { FormEvent, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { ArrowRight, Mail, UserRound } from 'lucide-react';
import AuthLayout from '../components/AuthLayout';
import PasswordField from '../components/PasswordField';
import supabase from '../lib/supabase';
import { useAuth } from '../contexts/AuthContext';

export default function Register() {
  const { user } = useAuth();
  const [name,setName]=useState(''); const [email,setEmail]=useState(''); const [password,setPassword]=useState(''); const [confirm,setConfirm]=useState('');
  const [busy,setBusy]=useState(false); const [error,setError]=useState(''); const [success,setSuccess]=useState('');
  if (user) return <Navigate to="/dashboard" replace/>;
  const strength = [password.length>=8, /[A-Z]/.test(password), /[0-9]/.test(password)].filter(Boolean).length;
  const submit=async(e:FormEvent)=>{e.preventDefault();setError('');setSuccess('');
    if(name.trim().length<2)return setError('Please enter your full name.'); if(!/^\S+@\S+\.\S+$/.test(email))return setError('Enter a valid email address.'); if(strength<3)return setError('Use 8+ characters with an uppercase letter and a number.'); if(password!==confirm)return setError('Passwords do not match.');
    setBusy(true); const {data,error:authError}=await supabase.auth.signUp({email:email.trim(),password,options:{data:{full_name:name.trim()}}});
    if(authError){setBusy(false);return setError(authError.message);} if(data.session){await fetch('/api/profiles',{method:'POST',headers:{'Content-Type':'application/json','Authorization':`Bearer ${data.session.access_token}`},body:JSON.stringify({full_name:name.trim()})});}
    setBusy(false);setSuccess('Account created. Check your inbox to verify your email, then sign in.'); setPassword('');setConfirm('');
  };
  return <AuthLayout><div className="auth-card compact-card"><div className="card-heading"><h2>Create your account</h2><p>Start managing your commuter pass in minutes.</p></div>
    {error&&<div className="notice error" role="alert">{error}</div>}{success&&<div className="notice success" role="status">{success}</div>}
    <form onSubmit={submit} noValidate><div className="field-group"><label htmlFor="name">Full name</label><div className="input-shell"><UserRound size={18}/><input id="name" value={name} onChange={e=>setName(e.target.value)} placeholder="Your full name" autoComplete="name" required/></div></div>
    <div className="field-group"><label htmlFor="email">Email address</label><div className="input-shell"><Mail size={18}/><input id="email" type="email" value={email} onChange={e=>setEmail(e.target.value)} placeholder="you@example.com" autoComplete="email" required/></div></div>
    <PasswordField id="password" label="Password" value={password} onChange={setPassword} autoComplete="new-password" placeholder="Create a strong password"/>
    {password&&<div className="strength"><div><i className={strength>0?'on':''}/><i className={strength>1?'on':''}/><i className={strength>2?'on':''}/></div><span>{['Weak','Weak','Good','Strong'][strength]}</span></div>}
    <PasswordField id="confirm" label="Confirm password" value={confirm} onChange={setConfirm} autoComplete="new-password" placeholder="Repeat your password"/>
    <button className="primary-button" disabled={busy}>{busy?<span className="button-loader"/>:<>Create account <ArrowRight size={18}/></>}</button></form>
    <p className="auth-switch">Already have an account? <Link to="/login">Sign in</Link></p></div></AuthLayout>;
}
