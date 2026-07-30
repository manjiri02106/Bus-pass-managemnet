import { BusFront } from 'lucide-react';

export default function Brand({ light = false }: { light?: boolean }) {
  return (
    <div className="brand-lockup">
      <span className="brand-mark"><BusFront size={25} strokeWidth={2.3} /></span>
      <span className={light ? 'text-white' : 'text-slate-900'}>Transit<span className="text-teal-600">Pass</span></span>
    </div>
  );
}
