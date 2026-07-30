import type { ReactNode } from 'react';
import { CheckCircle2, ShieldCheck, Smartphone } from 'lucide-react';
import Brand from './Brand';

export default function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <main className="auth-page">
      <section className="auth-visual" aria-label="TransitPass benefits">
        <div className="visual-orb orb-one" /><div className="visual-orb orb-two" />
        <Brand light />
        <div className="visual-copy">
          <span className="eyebrow"><ShieldCheck size={16} /> Secure commuter access</span>
          <h1>Your city.<br />One pass away.</h1>
          <p>Manage bus passes, renewals, and travel details through one simple, trusted portal.</p>
          <div className="benefit-row"><span><CheckCircle2 size={18} /> Safe & verified</span><span><Smartphone size={18} /> Mobile ready</span></div>
        </div>
        <div className="route-line"><i /><i /><i /><span /></div>
        <p className="visual-footer">Bus Pass Management System</p>
      </section>
      <section className="auth-form-side">
        <div className="mobile-brand"><Brand /></div>
        {children}
        <p className="copyright">© {new Date().getFullYear()} TransitPass · Secure commuter services</p>
      </section>
    </main>
  );
}
