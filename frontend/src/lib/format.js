export const STATUS_LABELS = {
  open: 'Open',
  pending: 'Pending',
  resolved: 'Resolved',
  closed: 'Closed',
};

export const PRIORITY_LABELS = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  urgent: 'Urgent',
};

export const STATUS_STYLES = {
  open: 'bg-blue-100 text-blue-700',
  pending: 'bg-amber-100 text-amber-700',
  resolved: 'bg-emerald-100 text-emerald-700',
  closed: 'bg-slate-200 text-slate-600',
};

export const PRIORITY_STYLES = {
  low: 'bg-slate-100 text-slate-600',
  medium: 'bg-sky-100 text-sky-700',
  high: 'bg-orange-100 text-orange-700',
  urgent: 'bg-red-100 text-red-700',
};

export function relativeTime(iso) {
  if (!iso) return '';
  const date = new Date(iso);
  const diff = (Date.now() - date.getTime()) / 1000;
  const units = [
    ['year', 31536000],
    ['month', 2592000],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
  ];
  for (const [name, secs] of units) {
    const value = Math.floor(diff / secs);
    if (value >= 1) return `${value} ${name}${value > 1 ? 's' : ''} ago`;
  }
  return 'just now';
}

export function initials(name) {
  if (!name) return '?';
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}
