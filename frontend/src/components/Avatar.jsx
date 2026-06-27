import { initials } from '../lib/format.js';

export default function Avatar({ name, size = 'md' }) {
  const dims = size === 'sm' ? 'h-7 w-7 text-xs' : 'h-9 w-9 text-sm';
  return (
    <div className={`flex ${dims} shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700`}>
      {initials(name)}
    </div>
  );
}
