function formatMinutes(mins) {
  if (mins == null) return '—';
  const abs = Math.abs(Math.round(mins));
  const h = Math.floor(abs / 60);
  const m = abs % 60;
  const text = h > 0 ? `${h}h ${m}m` : `${m}m`;
  return mins < 0 ? `${text} overdue` : `${text} left`;
}

export default function SlaBadge({ sla, resolved }) {
  if (!sla) {
    return (
      <div className="card p-5">
        <h3 className="mb-1 text-sm font-semibold text-slate-700">SLA</h3>
        <p className="text-sm text-slate-400">No policy for this priority.</p>
      </div>
    );
  }

  const breached = sla.response_breached || sla.resolution_breached;

  return (
    <div className="card p-5">
      <h3 className="mb-3 text-sm font-semibold text-slate-700">SLA</h3>
      <Row label="Response" breached={sla.response_breached} done={resolved} />
      <Row label="Resolution" breached={sla.resolution_breached} done={resolved} />
      {!resolved && (
        <p className={`mt-3 text-sm font-medium ${sla.resolution_breached ? 'text-red-600' : 'text-emerald-600'}`}>
          {formatMinutes(sla.resolution_minutes_remaining)}
        </p>
      )}
      {resolved && <p className="mt-3 text-sm text-emerald-600">Resolved</p>}
      {breached && !resolved && <p className="mt-1 text-xs text-red-500">SLA breached</p>}
    </div>
  );
}

function Row({ label, breached, done }) {
  const color = done ? 'bg-emerald-500' : breached ? 'bg-red-500' : 'bg-amber-400';
  return (
    <div className="flex items-center justify-between py-1 text-sm">
      <span className="text-slate-500">{label}</span>
      <span className="flex items-center gap-2 text-slate-600">
        <span className={`h-2 w-2 rounded-full ${color}`} />
        {done ? 'Met' : breached ? 'Breached' : 'On track'}
      </span>
    </div>
  );
}
