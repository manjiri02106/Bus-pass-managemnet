import { Eye, EyeOff, LockKeyhole } from 'lucide-react';
import { useState } from 'react';

interface Props { id: string; label: string; value: string; onChange: (value: string) => void; placeholder?: string; autoComplete?: string; error?: string; }

export default function PasswordField({ id, label, value, onChange, placeholder = 'Enter your password', autoComplete = 'current-password', error }: Props) {
  const [visible, setVisible] = useState(false);
  return (
    <div className="field-group">
      <label htmlFor={id}>{label}</label>
      <div className={`input-shell ${error ? 'input-error' : ''}`}>
        <LockKeyhole size={18} />
        <input id={id} type={visible ? 'text' : 'password'} value={value} onChange={(e) => onChange(e.target.value)} placeholder={placeholder} autoComplete={autoComplete} aria-invalid={!!error} required />
        <button type="button" className="eye-button" onClick={() => setVisible(!visible)} aria-label={visible ? 'Hide password' : 'Show password'}>{visible ? <EyeOff size={18} /> : <Eye size={18} />}</button>
      </div>
      {error && <span className="field-error">{error}</span>}
    </div>
  );
}
