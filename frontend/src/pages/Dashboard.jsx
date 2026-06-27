import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import Spinner from '../components/Spinner.jsx';
import {
  PRIORITY_LABELS,
  PRIORITY_STYLES,
  STATUS_LABELS,
  STATUS_STYLES,
} from '../lib/format.js';

export default function Dashboard() {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/dashboard/metrics').then((d) => setMetrics(d.data)).finally(() => setLoading(false));
  }, []);

  if (loading) return <Spinner className="min-h-[60vh]" />;
  if (!metrics) return null;

  const avg = metrics.avg_first_response_minutes;

  return (
    <div className="mx-auto max-w-6xl">
      <h1 className="text-2xl font-semibold text-slate-800">Dashboard</h1>
      <p className="text-sm text-slate-500">Support performance at a glance.</p>

      <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Stat label="Total tickets" value={metrics.total} />
        <Stat label="Open / pending" value={metrics.open} />
        <Stat label="Avg first response" value={avg != null ? `${formatMins(avg)}` : '—'} />
        <Stat label="SLA breach rate" value={`${metrics.sla_breach_rate}%`} accent={metrics.sla_breach_rate > 25} />
      </div>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <DistroCard title="By status" data={metrics.by_status} labels={STATUS_LABELS} styles={STATUS_STYLES} total={metrics.total} />
        <DistroCard title="By priority" data={metrics.by_priority} labels={PRIORITY_LABELS} styles={PRIORITY_STYLES} total={metrics.total} />
      </div>

      <div className="mt-6 card p-5">
        <h3 className="mb-4 text-sm font-semibold text-slate-700">Tickets created (last 7 days)</h3>
        <TrendChart data={metrics.created_per_day} />
      </div>
    </div>
  );
}

function Stat({ label, value, accent }) {
  return (
    <div className="card p-5">
      <p className="text-sm text-slate-500">{label}</p>
      <p className={`mt-1 text-2xl font-semibold ${accent ? 'text-red-600' : 'text-slate-800'}`}>{value}</p>
    </div>
  );
}

function DistroCard({ title, data, labels, styles, total }) {
  const entries = Object.keys(labels).map((key) => [key, data?.[key] || 0]);
  return (
    <div className="card p-5">
      <h3 className="mb-4 text-sm font-semibold text-slate-700">{title}</h3>
      <div className="space-y-3">
        {entries.map(([key, count]) => (
          <div key={key}>
            <div className="mb-1 flex items-center justify-between text-sm">
              <span className="text-slate-600">{labels[key]}</span>
              <span className="font-medium text-slate-700">{count}</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                className={`h-full rounded-full ${styles[key]?.split(' ')[0] || 'bg-brand-500'}`}
                style={{ width: `${total ? (count / total) * 100 : 0}%` }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function TrendChart({ data }) {
  const days = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    days.push([key, data?.[key] || 0]);
  }
  const max = Math.max(1, ...days.map(([, v]) => v));

  return (
    <div className="flex h-40 items-end gap-3">
      {days.map(([key, value]) => (
        <div key={key} className="flex flex-1 flex-col items-center gap-2">
          <div className="flex w-full flex-1 items-end">
            <div
              className="w-full rounded-t bg-brand-500/80"
              style={{ height: `${(value / max) * 100}%`, minHeight: value ? '4px' : '0' }}
              title={`${value} tickets`}
            />
          </div>
          <span className="text-[11px] text-slate-400">{key.slice(5)}</span>
        </div>
      ))}
    </div>
  );
}

function formatMins(mins) {
  if (mins < 60) return `${Math.round(mins)}m`;
  return `${(mins / 60).toFixed(1)}h`;
}
